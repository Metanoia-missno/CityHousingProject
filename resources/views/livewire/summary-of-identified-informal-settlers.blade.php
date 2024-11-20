<div x-data="{ openFilters: false }" class="p-10 h-screen ml-[17%] mt-[60px]">
    <div class="flex bg-gray-100 text-[12px]">
        <!-- Main Content -->
        <div class="flex-1 h-screen p-6 overflow-auto">
            <div class="bg-white rounded shadow mb-2 flex items-center justify-between relative p-3">
                <div class="flex items-center">
                    <h2 class="text-[13px] ml-2 items-center text-gray-700">Summary of Identified Informal Settlers</h2>
                </div>
                <img src="{{ asset('storage/images/design.png') }}" alt="Design"
                     class="absolute right-0 top-0 h-full object-cover opacity-100 z-0">
                <div class="relative z-0">
                    <button class="bg-gradient-to-r from-custom-red to-custom-green hover:bg-gradient-to-r hover:from-custom-red hover:to-custom-red text-white px-4 py-2 rounded">
                        <span wire:loading wire:target="export">Exporting Excel...</span>
                        <span wire:loading.remove>Export to Excel</span>
                    </button>
                </div>
            </div>
            <!-- Summary Stats -->
            <div class="flex items-center mb-2 ml-2">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-sm font-semibold">Total Occupants :
                            <span class="text-lg font-bold text-red-600">{{ number_format($totals->total_occupants) }}</span>
                        </h3>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold">Total Awardees:
                            <span class="text-lg font-bold text-red-600">{{ number_format($totals->total_awarded) }}</span>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded shadow">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-2">
                        <button @click="openFilters = !openFilters" class="flex space-x-2 items-center hover:bg-yellow-500 py-2 px-4 rounded bg-iroad-orange">
                            <div class="text-white">
                                <!-- Filter Icon (You can use an icon from any library) -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.447.894l-4 2.5A1 1 0 017 21V13.414L3.293 6.707A1 1 0 013 6V4z" />
                                </svg>
                            </div>
                            <div class="text-[13px] text-white font-medium">
                                Filter
                            </div>
                        </button>
                        <!-- Search -->
                        <div class="relative hidden md:block border-gray-300">
                            <svg class="absolute top-[13px] left-4" width="19" height="19" viewBox="0 0 21 21"
                                 fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.625 16.625C13.491 16.625 16.625 13.491 16.625 9.625C16.625 5.75901 13.491 2.625 9.625 2.625C5.75901 2.625 2.625 5.75901 2.625 9.625C2.625 13.491 5.75901 16.625 9.625 16.625Z"
                                      stroke="#787C7F" stroke-width="1.75" stroke-linecap="round"
                                      stroke-linejoin="round" />
                                <path d="M18.3746 18.375L14.5684 14.5688" stroke="#787C7F" stroke-width="1.75"
                                      stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <input wire:model.live="search"
                                   type="search"
                                   class="rounded-md px-12 py-2 placeholder:text-[13px] z-10 border border-gray-300 bg-[#f7f7f9] hover:ring-custom-yellow focus:ring-custom-yellow"
                                   placeholder="Search">
                        </div>
                    </div>
                </div>


                <div x-show="openFilters" class="flex space-x-2 mb-1 mt-5">
                    <label class="text-center mt-2">Date From:</label>
                    <input type="date" id="start-date" class="border text-[13px] border-gray-300 rounded px-2 py-1">
                    <label class="text-center mt-2">To:</label>
                    <input type="date" id="end-date" class="border text-[13px] border-gray-300 rounded px-2 py-1">
                    <select wire:model.live="filterBarangay"
                            class="border text-[12px] border-gray-300 text-gray-600 rounded px-1 py-1 shadow-sm">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay }}">{{ $barangay }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterPurok"
                            class="border text-[12px] border-gray-300 text-gray-600 rounded px-1 py-1 shadow-sm">
                        <option value="">All Puroks</option>
                        @foreach($puroks as $purok)
                            <option value="{{ $purok }}">{{ $purok }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterLivingSituation"
                            class="border text-[12px] border-gray-300 text-gray-600 rounded px-1 py-1 shadow-sm">
                        <option value="">All Living Situations</option>
                        @foreach($livingSituations as $situation)
                            <option value="{{ $situation }}">{{ $situation }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterCaseSpecification"
                            class="border text-[12px] border-gray-300 text-gray-600 rounded px-1 py-1 shadow-sm">
                        <option value="">All Case Specifications</option>
                        @foreach($caseSpecifications as $specification)
                            <option value="{{ $specification }}">{{ $specification }}</option>
                        @endforeach
                    </select>

                    <select class="border text-[12px] border-gray-300 text-gray-600 rounded px-1 py-1 shadow-sm">
                        <option value="">Assigned Relocation Site</option>
                        <option value="barangay1">Canocotan Relocation Site</option>
                        <option value="barangay2">Barangay 2</option>
                        <option value="barangay3">Barangay 3</option>
                    </select>

                    <button wire:click="resetFilters"  class="bg-custom-yellow text-white px-4 py-2 rounded">Reset Filters</button>
                </div>
            </div>

            <!-- Table for Summary of Identified Informal Settlers -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 mb-1">
                    <thead class="bg-gray-100">
                        <tr>
                            <th wire:click="sortBy('tagging_date')"
                                class="py-2 px-10  text-center font-medium">
                                <div class="flex items-center">
                                    Date Tagged
                                    @if($sortField === 'tagging_date')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                            <th class="py-2 px-10 border-b text-center font-medium">Barangay</th>
                            <th class="py-2 px-10 border-b text-center font-medium">Purok</th>
                            <th class="py-2 px-10 border-b text-center font-medium">Living Situation(Case)</th>
                            <th class="py-2 px-10 border-b text-center font-medium">Case Specification</th>
                            <th class="py-2 px-10 border-b text-center font-medium">No. of Actual Occupants</th>
                            <th class="py-2 px-10 border-b text-center font-medium">Assigned Relocation Site</th>
                            <th class="py-2 px-10 border-b text-center font-medium">Awarded</th>
                            <th class="py-2 px-10 border-b text-center font-medium">Actual Relocation Site</th>
                            <th class="py-2 px-10 border-b text-center font-medium">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groupedApplicants as $group)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($group->tagging_date)->format('M d, Y') }}
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    {{ $group->barangay }}
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    {{ $group->purok }}
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    {{ $group->living_situation }}
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    {{ $group->case_specification ?? 'N/A' }}
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    {{ number_format($group->occupants_count) }}
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    Assigned relocation site here...
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    {{ number_format($group->awarded_count) }}
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">
                                    Actual relocation site here...
                                </td>
                                <td class="py-4 px-2 text-center border-b capitalize whitespace-normal break-words">

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 px-2 text-center border-b capitalize">
                                    No records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="mt-4">
                {{ $groupedApplicants->links() }}
            </div>
        </div>
    </div>
</div>
