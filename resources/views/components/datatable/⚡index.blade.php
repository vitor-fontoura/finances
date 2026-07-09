<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component
{
    public string $id = 'table';
    public ?string $model = null;
    public array $columns = [];
    public array $with = [];
    public ?array $where = null;
    public bool $checkable = false;
    public bool $exportable = false;
    public ?string $details = null;
    public ?string $authorize = null;

    #[Url(as: 's')]
    public string $search = '';

    #[Url(as: 'p')]
    public int $perPage = 25;

    #[Url(as: 'sort')]
    public string $sortBy = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    public array $selected = [];
    public array $orderable = [];
    public array $hiddenColumns = [];

    public function mount(): void
    {
        $this->orderable = collect($this->columns)
            ->where('sortable', true)
            ->pluck('field')
            ->toArray();

        $this->hiddenColumns = collect($this->columns)
            ->filter(fn ($col) => $col['hidden'] ?? false)
            ->pluck('field')
            ->toArray();
    }

    #[Computed]
    public function data(): LengthAwarePaginator
    {
        return $this->model::with($this->with)
            ->when($this->search, function ($query) {
                $searchableColumns = collect($this->columns)
                    ->where('searchable', true)
                    ->pluck('field')
                    ->toArray();

                if (empty($searchableColumns)) {
                    return;
                }

                $sanitizedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $this->search);

                $query->where(function ($q) use ($searchableColumns, $sanitizedSearch) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'like', "%{$sanitizedSearch}%");
                    }
                });
            })
            ->when(!empty($this->where), function ($query) {
                $query->where($this->where);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selected);
    }

    #[Computed]
    public function visibleColumns(): array
    {
        return collect($this->columns)
            ->filter(fn ($col) => !in_array($col['field'] ?? '', $this->hiddenColumns))
            ->values()
            ->toArray();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[On('refresh-table')]
    public function refresh(): void
    {
        $this->dispatch('$refresh');
    }

    public function resetSelected(): void
    {
        $this->selected = [];
    }

    public function toggleColumn(string $field): void
    {
        if (in_array($field, $this->hiddenColumns)) {
            $this->hiddenColumns = array_values(array_diff($this->hiddenColumns, [$field]));
        } else {
            $this->hiddenColumns[] = $field;
        }
    }

    /**
     * Toggle selection for all items on the current page only.
     * Note: This does not select items across all pages.
     */
    public function toggleSelectAll(): void
    {
        $allIds = $this->data->pluck('id')->toArray();

        if (count($this->selected) === count($allIds)) {
            $this->selected = [];
        } else {
            $this->selected = $allIds;
        }
    }

    public function export(string $format): void
    {
        $this->dispatch('export-data', model: $this->model, format: $format, search: $this->search, sortBy: $this->sortBy, sortDirection: $this->sortDirection);
    }
};
?>

<div id="{{ $id }}">
    {{-- Search and Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div class="flex-1 max-w-sm">
            <x-components.datatable.search />
        </div>

        <div class="flex items-center gap-2">
            <x-components.datatable.per-page />

            <flux:button variant="ghost" icon="arrow-path" wire:click="refresh" wire:loading.attr="disabled">
                {{ __('Refresh') }}
            </flux:button>

            @if($exportable)
                <x-components.datatable.export :model="$model" />
            @endif

            <x-components.datatable.column-toggle />
        </div>
    </div>

    {{-- Table --}}
    <flux:table :paginate="$this->data">
        <flux:table.columns>
            @if($checkable)
                <flux:table.column class="w-10">
                    <input
                        type="checkbox"
                        wire:model.live="selected"
                        value="all"
                        class="rounded border-zinc-300"
                    />
                </flux:table.column>
            @endif

            @if($details)
                <flux:table.column class="w-10"></flux:table.column>
            @endif

            @foreach($this->visibleColumns as $column)
                <flux:table.column
                    sortable="{{ $column['sortable'] ?? false }}"
                    :sorted="$sortBy === ($column['field'] ?? '')"
                    :direction="$sortDirection"
                    @if($column['sortable'] ?? false) wire:click="sort('{{ $column['field'] }}')" @endif
                    class="{{ $column['class'] ?? '' }}"
                >
                    {{ $column['title'] ?? '' }}
                </flux:table.column>
            @endforeach
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->data as $item)
                <flux:table.row :key="$item->id" x-data="{ expanded: false }">
                    @if($checkable)
                        <flux:table.cell>
                            <input
                                type="checkbox"
                                wire:model.live="selected"
                                value="{{ $item->id }}"
                                class="rounded border-zinc-300"
                            />
                        </flux:table.cell>
                    @endif

                    @if($details)
                        <flux:table.cell>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="chevron-right"
                                @click="expanded = !expanded"
                                x-bind:icon="expanded ? 'chevron-down' : 'chevron-right'"
                            />
                        </flux:table.cell>
                    @endif

                    @foreach($this->visibleColumns as $column)
                        <flux:table.cell>
                            @if(isset($column['component']))
                                @include($column['component'], [
                                    'data' => $column['field'] ? data_get($item, $column['field']) : $item,
                                    'item' => $item,
                                ])
                            @else
                                {{ data_get($item, $column['field']) }}
                            @endif
                        </flux:table.cell>
                    @endforeach
                </flux:table.row>

                @if($details)
                    <flux:table.row x-show="expanded" x-collapse>
                        <flux:table.cell :colspan="count($this->visibleColumns) + ($checkable ? 1 : 0) + 1">
                            @include($details, ['data' => $item])
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            @empty
                <flux:table.row>
                    <flux:table.cell :colspan="count($this->visibleColumns) + ($checkable ? 1 : 0) + ($details ? 1 : 0)">
                        <x-components.datatable.empty-state />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Selected count --}}
    @if($checkable && $this->selectedCount > 0)
        <div class="mt-4 flex items-center gap-2">
            <flux:badge color="blue">
                {{ $this->selectedCount }} {{ __('selected') }}
            </flux:badge>
            <flux:button variant="ghost" size="sm" wire:click="resetSelected">
                {{ __('Clear selection') }}
            </flux:button>
        </div>
    @endif
</div>
