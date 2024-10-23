<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\Awardee;
use App\Models\AwardeeAttachmentsList;
use App\Models\AwardeeDocumentsSubmission;
use App\Models\Barangay;
use App\Models\LivingSituation;
use App\Models\LotList;
use App\Models\LotSizeUnit;
use App\Models\Purok;
use App\Models\TaggedAndValidatedApplicant;
use App\Models\TemporaryImageForHousing;
use App\Models\TransactionType;
use http\Env\Request;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;


class TaggedAndValidatedApplicantsForAwarding extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $isLoading = false;
    public $transaction_type_id;
    public $transaction_type_name;

    // Filter properties:
    public $applicantId;
    public $startTaggingDate, $endTaggingDate, $selectedTaggingStatus;
    public $selectedPurok_id;
    public $puroksFilter = []; // Initialize as an empty array
    public $selectedBarangay_id;
    public $barangaysFilter = []; // Initialize as an empty array
    public $selectedLivingSituation_id;
    public $livingSituationsFilter = []; // Initialize as an empty array
    public $taggingStatuses;

//    public $taggedAndValidatedApplicants;

    // Tagged and validated applicant details
    public $first_name, $middle_name, $last_name, $suffix_name, $barangay, $purok, $living_situation, $contact_number, $occupation, $monthly_income, $transaction_type;

    // For awarding modal
    public $grant_date;
    public $taggedAndValidatedApplicantId; // ID of the applicant being awarded
    public $purok_id;
    public $puroks = []; // Initialize as an empty array
    public $barangay_id;
    public $barangays = []; // Initialize as an empty array
    public $lot_id;
    public $lots = []; // Initialize as an empty array
    public $lot_size;
    public $lot_size_unit_id;
    public $lotSizeUnits = []; // Initialize as an empty array

    // For uploading of files
    public $awardee_attachments_list_id;
    public $attachmentLists = [];
    public $description;
    public $awardeeId;
    public $documents = [];
    public $newFileImages = [];

    public function updatingSearch(): void
    {
        // This ensures that the search query is updated dynamically as the user types
        $this->resetPage();
    }
    public function clearSearch(): void
    {
        $this->search = ''; // Clear the search input
    }
    public function resetFilters(): void
    {
        $this->startTaggingDate = null;
        $this->endTaggingDate = null;
        $this->selectedPurok_id = null;
        $this->selectedBarangay_id = null;
        $this->selectedLivingSituation_id = null;
        $this->selectedTaggingStatus = null;

        // Reset pagination and any search terms
        $this->resetPage();
        $this->search = '';
    }

    public function mount(): void
    {
        // Set the default transaction type to 'Walk-in'
        $viaRequest = TransactionType::where('type_name', 'Request')->first();
        if ($viaRequest) {
            $this->transaction_type_id = $viaRequest->id; // This can still be used internally for further logic if needed
            $this->transaction_type_name = $viaRequest->type_name; // Set the name to display
        }

        // Initialize filter options
        $this->puroksFilter = Cache::remember('puroks', 60*60, function() {
            return Purok::all();
        });
        $this->barangaysFilter = Cache::remember('barangays', 60*60, function() {
            return Barangay::all();
        });
        $this->livingSituationsFilter = Cache::remember('living_situations', 60*60, function() {
            return LivingSituation::all();
        });
        $this->taggingStatuses = ['Tagged', 'Not Tagged']; // Add your statuses here

        // For Awarding Modal

        // Set today's date as the default value for date_applied
        $this->grant_date = now()->toDateString(); // YYYY-MM-DD format

        // Initialize dropdowns
        $this->barangays = Barangay::all();
        $this->puroks = Purok::all();
        // Fetch related LotSizeUnits
        $this->lots = LotList::all(); // Fetch all lot size units
        $this->lotSizeUnits = LotSizeUnit::all(); // Fetch all lot size units

        // Fetch attachment types for the dropdown
        $this->attachmentLists = AwardeeAttachmentsList::all(); // Fetch all attachment lists
    }
    public function updatingBarangay(): void
    {
        $this->resetPage();
    }
    public function updatingPurok(): void
    {
        $this->resetPage();
    }
    // For Awarding Modal
    public function updatedBarangayId($barangayId): void
    {
        // Fetch the puroks based on the selected barangay
        $this->puroks = Purok::where('barangay_id', $barangayId)->get();
        $this->lots = LotList::where('barangay_id', $barangayId)->get();

        $this->purok_id = null; // Reset selected purok when barangay changes
        $this->lot_id = null; // Reset selected lot when barangay changes

        $this->isLoading = false; // Reset loading state

        logger()->info('Retrieved Puroks', [
            'barangay_id' => $barangayId,
            'puroks' => $this->puroks,
            'lots' => $this->lots
        ]);
    }
    public function updatedSelectedBarangayId($barangayId): void
    {
        // Fetch the puroks based on the selected barangay
        $this->puroksFilter = Purok::where('barangay_id', $barangayId)->get();
        $this->selectedPurok_id = null; // Reset selected purok when barangay changes
        $this->isLoading = false; // Reset loading state
        logger()->info('Retrieved Puroks', [
            'selectedBarangay_id' => $barangayId,
            'puroksFilter' => $this->puroksFilter
        ]);
    }
    protected function rules(): array
    {
        return [
            // For Awarding Modal
            'grant_date' => 'required|date',
            'barangay_id' => 'required|exists:barangays,id',
            'purok_id' => 'required|exists:puroks,id',
            'lot_id' => 'required|exists:lot_lists,id',
            'lot_size' => 'required|numeric',
            'lot_size_unit_id' => 'required|exists:lot_size_units,id',

            // For file upload modal
            'awardee_attachments_list_id' => 'required|exists:awardee_attachments_lists,id',
            'description' => 'required|string|max:255',
            'newFileImages.*' => 'required|file|max:10240', // File validation for multiple images
        ];
    }
    public function awardApplicant(): void
    {
        // Validate the input data
        $this->validate();

        // Create the address entry first
        $address = Address::create([
            'barangay_id' => $this->barangay_id,
            'purok_id' => $this->purok_id,
        ]);

        // Create the new awardee record and get the ID of the newly created awardee
        $awardees = Awardee::create([
            'tagged_and_validated_applicant_id' => $this->taggedAndValidatedApplicantId,
            'address_id' => $address->id,
            'lot_id' => $this->lot_id,
            'lot_size' => $this->lot_size,
            'lot_size_unit_id' => $this->lot_size_unit_id,
            'grant_date' => $this->grant_date,
            'is_awarded' => false, // Update awardee table for status tracking
        ]);

        // Update the 'tagged_and_validated_applicants' table to mark the applicant as being processed for awarding
        TaggedAndValidatedApplicant::where('id', $this->taggedAndValidatedApplicantId)->update([
            'is_awarding_on_going' => true, // Update to reflect awarding is in process
        ]);

        $this->resetForm();

        // Trigger the alert message
        $this->dispatch('alert', [
            'title' => 'Awarding Successful!',
            'message' => 'Applicant awarded successfully! <br><small>'. now()->calendar() .'</small>',
            'type' => 'success'
        ]);

        $this->redirect('transaction-request');
    }
    public function resetForm(): void
    {
        $this->reset([
            'lot_id', 'lot_size', 'lot_size_unit_id', 'grant_date',
        ]);
    }

    // For file upload
    public function uploadDocuments(): void
    {
        // Debugging line
        logger()->info('uploadDocuments method triggered.');

        $this->validate();

        // Check if validation was successful
        logger()->info('Validation passed.');

        // Log the uploaded files
        logger()->info('Uploaded files:', $this->newFileImages->toArray());

        foreach ($this->newFileImages as $file) {
            try {
                $path = $file->store('awardee-' . $this->awardeeId, 'awardee-photo-requirements');
                AwardeeDocumentsSubmission::create([
                    'awardee_id' => $this->awardeeId,
                    'description' => $this->description,
                    'awardee_attachments_list_id' => $this->attachment_id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                ]);
            } catch (\Exception $e) {
                logger()->error('Database error: ' . $e->getMessage());
            }
        }

        $this->dispatch('alert', [
            'title' => 'Documents Uploaded!',
            'message' => 'Documents uploaded successfully! <br><small>'. now()->calendar() .'</small>',
            'type' => 'success'
        ]);

        $this->resetUpload();
    }
    private function resetUpload(): void
    {
        $this->reset(['newFileImages', 'description', 'attachment_id']);
        $this->show = false;
    }

    public function render()
    {
        $query = TaggedAndValidatedApplicant::with([
            'applicant.address.purok',
            'applicant.address.barangay',
            'applicant.transactionType',
            'livingSituation',
            'caseSpecification'
        ])->whereHas('applicant', function($q) {
                $q->where('applicant_id', 'like', '%'.$this->search.'%')
                    ->orWhere('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('middle_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('suffix_name', 'like', '%'.$this->search.'%')
                    ->orWhere('contact_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('address.purok', function ($query) {
                        $query->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('address.barangay', function ($query) {
                        $query->where('name', 'like', '%'.$this->search.'%');
                    });
            });

        // Apply date range filter (if both dates are present)
        if ($this->startTaggingDate && $this->endTaggingDate) {
            $query->whereBetween('tagging_date', [$this->startTaggingDate, $this->endTaggingDate]);
        }

        // Filter by selected Purok
        if ($this->selectedPurok_id) {
            $query->whereHas('applicant.address', function ($q) {
                $q->where('purok_id', $this->selectedPurok_id);
            });
        }

        // Filter by selected Barangay
        if ($this->selectedBarangay_id) {
            $query->whereHas('applicant.address', function ($q) {
                $q->where('barangay_id', $this->selectedBarangay_id);
            });
        }

//        if ($this->selectedTaggingStatus !== null) {
//            $query->where('is_tagged', $this->selectedTaggingStatus === 'Tagged');
//        }

        // Paginate the filtered results
        $taggedAndValidatedApplicants = $query->paginate(10);

        return view('livewire.tagged_and_validated_applicants_for_awarding', [
            'taggedAndValidatedApplicants' => $taggedAndValidatedApplicants,
            'puroks' => $this->puroksFilter,
            'barangays' => $this->barangaysFilter,
            'livingSituations' => $this->livingSituationsFilter,
            'taggingStatuses' => $this->taggingStatuses,
        ]);
    }
}
