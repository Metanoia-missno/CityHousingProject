<?php

namespace App\Livewire;

use App\Exports\ApplicantsDataExport;
use App\Exports\SummaryOfIdentifiedInformalSettlersDataExport;
use App\Models\Barangay;
use App\Models\CaseSpecification;
use App\Models\LivingSituation;
use App\Models\Purok;
use App\Models\RelocationSite;
use App\Models\TaggedAndValidatedApplicant;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SummaryOfIdentifiedInformalSettlers extends Component
{
    use WithPagination;

    // Constants
    const DANGER_ZONE_ID = 8;

    // Search and filters properties
    public $search = '', $filterBarangay = '', $filterPurok = '', $filterLivingSituation = '', $filterCaseSpecification = '',
        $filterAssignedRelocationSite, $filterActualRelocationSite, $startDate, $endDate;

    // Sorting properties
    public $sortField = 'tagging_date', $sortDirection = 'desc';

    // Cache for filter options
    protected $filterOptions = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterBarangay' => ['except' => ''],
        'filterPurok' => ['except' => ''],
        'filterLivingSituation' => ['except' => ''],
        'filterCaseSpecification' => ['except' => ''],
        'sortField' => ['except' => 'tagging_date'],
        'sortDirection' => ['except' => 'desc'],
        'page' => ['except' => 1],
    ];

    // Reset pagination when search changes
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingFilterBarangay(): void
    {
        $this->resetPage();
    }
    public function updatingFilterPurok(): void
    {
        $this->resetPage();
    }
    public function updatingFilterLivingSituation(): void
    {
        $this->resetPage();
    }
    public function updatingFilterCaseSpecification(): void
    {
        $this->resetPage();
    }
    // Sort method
    public function sortBy($field)
    {
        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortField = $field;
    }
    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterBarangay',
            'filterPurok',
            'filterLivingSituation',
            'filterCaseSpecification',
            'filterAssignedRelocationSite',
            'filterActualRelocationSite',
            'startDate',
            'endDate',
            'sortField',
            'sortDirection'
        ]);
        $this->resetPage();
    }
// todo try checking comparing table's data in the system and in the excel
    public function export()
    {
        try {
            $filters =  array_filter([
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'filterBarangay' => $this->filterBarangay,
                'filterPurok' => $this->filterPurok,
                'filterLivingSituation' => $this->filterLivingSituation,
                'filterCaseSpecification' => $this->filterCaseSpecification,
                'filterAssignedRelocationSite' => $this->filterAssignedRelocationSite
            ]);

            return Excel::download(
                new SummaryOfIdentifiedInformalSettlersDataExport($filters),
                'summary-of-identified-informal-settlers-' . now()->format('Y-m-d') . '.xlsx'
            );
        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage());
            $this->dispatch('alert', [
                'title' => 'Export failed: ',
                'message' => $e->getMessage() . '<br><small>'. now()->calendar() .'</small>',
                'type' => 'danger'
            ]);
            return null;
        }
    }

    public function render()
    {
        $query = TaggedAndValidatedApplicant::query()
            ->select([
                'tagged_and_validated_applicants.tagging_date',
                'barangays.name as barangay',
                'puroks.name as purok',
                'living_situations.living_situation_description as living_situation',
                DB::raw('CASE 
                    WHEN tagged_and_validated_applicants.living_situation_id = ' . self::DANGER_ZONE_ID . '
                    THEN case_specifications.case_specification_name 
                    ELSE tagged_and_validated_applicants.living_situation_case_specification 
                END as case_specification'),
                'relocation_sites.relocation_site_name as assigned_relocation_site',
                'awardees.relocation_lot_id as actual_relocation_site_id',
                DB::raw('COUNT(DISTINCT tagged_and_validated_applicants.id) as occupants_count'),
                DB::raw('COUNT(DISTINCT awardees.id) as awarded_count'),
                DB::raw('GROUP_CONCAT(DISTINCT relocation_lots.relocation_site_name SEPARATOR ", ") as actual_relocation_sites')
            ])
            ->join('applicants', 'tagged_and_validated_applicants.applicant_id', '=', 'applicants.id')
            ->join('addresses', 'applicants.address_id', '=', 'addresses.id')
            ->join('barangays', 'addresses.barangay_id', '=', 'barangays.id')
            ->join('puroks', 'addresses.purok_id', '=', 'puroks.id')
            ->join('living_situations', 'tagged_and_validated_applicants.living_situation_id', '=', 'living_situations.id')
            ->leftJoin('case_specifications', function($join) {
                $join->on('tagged_and_validated_applicants.case_specification_id', '=', 'case_specifications.id')
                    ->where('tagged_and_validated_applicants.living_situation_id', '=', self::DANGER_ZONE_ID);
            })
            ->leftJoin('relocation_sites', 'tagged_and_validated_applicants.relocation_lot_id', '=', 'relocation_sites.id')
            ->leftJoin('awardees', 'tagged_and_validated_applicants.id', '=', 'awardees.tagged_and_validated_applicant_id')
            // New join for Actual Relocation Site
            ->leftJoin('relocation_sites as relocation_lots', 'awardees.relocation_lot_id', '=', 'relocation_lots.id');
//            ->where('tagged_and_validated_applicants.is_tagged', true);

        // Apply filters
        if ($this->filterBarangay) {
            $query->where('barangays.name', $this->filterBarangay);
        }

        if ($this->filterPurok) {
            $query->where('puroks.name', $this->filterPurok);
        }

        if ($this->filterLivingSituation) {
            $query->where('living_situations.living_situation_description', $this->filterLivingSituation);
        }

        if ($this->filterCaseSpecification) {
            $query->where(function ($q) {
                $q->where(function($subQ) {
                    $subQ->where('tagged_and_validated_applicants.living_situation_id', self::DANGER_ZONE_ID)
                        ->where('case_specifications.case_specification_name', $this->filterCaseSpecification);
                })->orWhere(function($subQ) {
                    $subQ->where('tagged_and_validated_applicants.living_situation_id', '!=', self::DANGER_ZONE_ID)
                        ->where('tagged_and_validated_applicants.living_situation_case_specification', $this->filterCaseSpecification);
                });
            });
        }

        // Date Range Filter
        if ($this->startDate) {
            $query->where('tagged_and_validated_applicants.tagging_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->where('tagged_and_validated_applicants.tagging_date', '<=', $this->endDate);
        }

        // Assigned Relocation Site Filter
        if ($this->filterAssignedRelocationSite) {
            $query->where('relocation_sites.relocation_site_name', $this->filterAssignedRelocationSite);
        }

        // Actual Relocation Site Filter
        if ($this->filterActualRelocationSite) {
            $query->where(function($q) {
                $q->where('relocation_lots.relocation_site_name', $this->filterActualRelocationSite)
                    ->orWhereNull('relocation_lots.relocation_site_name'); // Handle cases where the actual relocation site may be null
            });
        }

        // Get filter options (cached for performance)
        if (empty($this->filterOptions)) {
            $this->filterOptions = [
                'barangays' => Barangay::pluck('name')->unique()->sort()->values(),
                'puroks' => Purok::pluck('name')->unique()->sort()->values(),
                'livingSituations' => LivingSituation::pluck('living_situation_description')->unique()->sort()->values(),
                'caseSpecifications' => collect([
                    // Get Danger Zone case specifications
                    CaseSpecification::pluck('case_specification_name'),
                    // Get other living situation case specifications
                    TaggedAndValidatedApplicant::where('living_situation_id', '!=', self::DANGER_ZONE_ID)
                        ->whereNotNull('living_situation_case_specification')
                        ->pluck('living_situation_case_specification')
                ])->flatten()->unique()->sort()->values(),
                // Add filter options for Assigned and Actual Relocation Sites
                'relocationSites' => RelocationSite::all(),
            ];
        }

        // Create a copy of the base query for totals
        $totalsQuery = (clone $query);

        // Calculate totals without grouping
        $totals = $totalsQuery
            ->select([
                DB::raw('COUNT(DISTINCT tagged_and_validated_applicants.id) as total_occupants'),
                DB::raw('COUNT(DISTINCT awardees.id) as total_awarded')
            ])
            ->first();

        // Group by all relevant fields
        $query->groupBy([
            'tagged_and_validated_applicants.tagging_date',
            'barangays.name',
            'puroks.name',
            'living_situations.living_situation_description',
            DB::raw('CASE 
                WHEN tagged_and_validated_applicants.living_situation_id = ' . self::DANGER_ZONE_ID . '
                THEN case_specifications.case_specification_name 
                ELSE tagged_and_validated_applicants.living_situation_case_specification 
            END'),
            'relocation_sites.relocation_site_name',
            'awardees.relocation_lot_id'
        ]);

        // Apply sorting
        if ($this->sortField === 'occupants_count' || $this->sortField === 'awarded_count') {
            $query->orderBy(DB::raw($this->sortField), $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return view('livewire.summary-of-identified-informal-settlers', [
            'groupedApplicants' => $query->paginate(5),
            'totals' => $totals,
            'barangays' => $this->filterOptions['barangays'],
            'puroks' => $this->filterOptions['puroks'],
            'livingSituations' => $this->filterOptions['livingSituations'],
            'caseSpecifications' => $this->filterOptions['caseSpecifications'],
            'relocationSites' => $this->filterOptions['relocationSites'],
        ]);
    }
}