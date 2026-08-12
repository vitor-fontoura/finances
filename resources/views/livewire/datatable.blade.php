<div id="datatable" x-data="{
    hiddenColumns: @js(collect($this->columns)->where('hidden', true)->pluck('field')->toArray()),
    columns: @js($this->columns),
    get visibleCount() { return this.columns.length - this.hiddenColumns.length; }
}">
    {{-- Search and Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div class="flex-1 max-w-sm">
            @include('livewire.datatable.search')
        </div>

        <div class="flex items-center justify-end gap-2">
            <flux:button variant="ghost" icon="arrow-path" wire:click="refresh" wire:loading.attr="disabled">
                {{ __('datatable.refresh') }}
            </flux:button>

            @if($this->exportable())
                @include('livewire.datatable.export')
            @endif

            @include('livewire.datatable.column-toggle')
        </div>
    </div>

    {{-- Table --}}
    @php $results = $this->data(); @endphp
    <flux:table :paginate="$results">
        <x-slot:perPage>
            <flux:select wire:model.live="perPage" class="w-20" size="xs">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </flux:select>
        </x-slot:perPage>
        <flux:table.columns>
            @if($this->checkable())
                <flux:table.column class="w-10">
                    <!--
                    <flux:checkbox
                        wire:model.live="selected"
                        value="all"
                    />
                    -->
                </flux:table.column>
            @endif

            @foreach($this->columns as $column)
                @if($column['sortable'] ?? false)
                    <flux:table.column
                        x-show="!hiddenColumns.includes('{{ $column['field'] }}')"
                        sortable
                        :sorted="$sortBy === ($column['field'] ?? '')"
                        :direction="$sortDirection"
                        wire:click="sort('{{ $column['field'] }}')"
                        class="{{ $column['class'] ?? '' }}"
                    >
                        {{ $column['title'] ?? $this->translateField($column['field']) }}
                    </flux:table.column>
                @else
                    <flux:table.column
                        x-show="!hiddenColumns.includes('{{ $column['field'] }}')"
                        class="{{ $column['class'] ?? '' }}"
                    >
                        {{ $column['title']  ?? $this->translateField($column['field']) }}
                    </flux:table.column>
                @endif
            @endforeach
        </flux:table.columns>

        <flux:table.rows>
            @forelse($results as $item)
                <flux:table.row :key="$item->id">
                    @if($this->checkable())
                        <flux:table.cell>
                            <flux:checkbox
                                wire:model.live="selected"
                                value="{{ $item->id }}"
                            />
                        </flux:table.cell>
                    @endif

                    @foreach($this->columns as $column)
                        <flux:table.cell
                            x-show="!hiddenColumns.includes('{{ $column['field'] }}')"
                        >
                            @if(isset($column['component']))
                                @include($column['component'], [
                                    'data' => $column['field'] ? data_get($item, $column['field']) : $item,
                                    'item' => $item,
                                    'column' => $column,
                                ])
                            @else
                                {{ data_get($item, $column['field']) }}
                            @endif
                        </flux:table.cell>
                    @endforeach
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell :colspan="($this->checkable() ? 1 : 0) + $this->visibleCount()">
                        <div class="py-12 text-center">
                            <flux:icon name="document-magnifying-glass" class="mx-auto h-12 w-12 text-zinc-400" />
                            <flux:heading size="sm" class="mt-4">{{ __('datatable.no_results') }}</flux:heading>
                            <flux:text class="mt-2">{{ __('datatable.no_results_hint') }}</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Selected count --}}
    @if($this->checkable() && $this->selectedCount() > 0)
        <div class="mt-4 flex items-center gap-2">
            <flux:badge color="blue">
                {{ trans_choice('datatable.selected', $this->selectedCount()) }}
            </flux:badge>
            <flux:button variant="ghost" size="sm" wire:click="resetSelected">
                {{ __('datatable.clear_selection') }}
            </flux:button>
        </div>
    @endif
</div>
