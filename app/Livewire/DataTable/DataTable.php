<?php

declare(strict_types=1);

namespace App\Livewire\DataTable;

use App\Concerns\JoinsRelations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

abstract class DataTable extends Component
{
    use WithPagination;

    public array $columns = [];

    // =========================================
    // Abstract methods - must be implemented
    // =========================================

    /**
     * Configure table settings (primary key, default sort, per page).
     */
    abstract public function configure(): void;

    /**
     * Define table columns as array of arrays.
     *
     * Each column array should contain:
     *   'field'       => string (required) - dot-notation model attribute
     *   'title'       => string (optional) - column header text
     *   'sortable'    => bool   (optional) - enables sort toggle
     *   'searchable'  => bool   (optional) - included in search queries
     *   'hidden'      => bool   (optional) - hidden by default
     *   'class'       => string (optional) - CSS class on <th> and <td>
     *   'component'   => string (optional) - custom cell view name
     *
     * @return array<int, array{field: string, title?: string, sortable?: bool, searchable?: bool, hidden?: bool, class?: string, component?: string}>
     */
    abstract protected function columns(): array;

    /**
     * Build and return the query for this table.
     *
     * Can include scopes, joins, auth filters, etc.
     */
    abstract public function query(): Builder;

    // =========================================
    // Optional overrides
    // =========================================

    public function exportable(): bool
    {
        return false;
    }

    public function checkable(): bool
    {
        return false;
    }

    // =========================================
    // Configuration state (set by configure())
    // =========================================

    protected string $primaryKey = 'id';

    protected string $defaultSortBy = 'created_at';

    protected string $defaultSortDirection = 'asc';

    protected int $defaultPerPage = 25;

    // =========================================
    // URL-synced state
    // =========================================

    #[Url(as: 's')]
    public string $search = '';

    #[Url(as: 'p')]
    public int $perPage;

    #[Url(as: 'sort')]
    public string $sortBy;

    #[Url(as: 'dir')]
    public string $sortDirection;

    // =========================================
    // Selection state
    // =========================================

    public array $selected = [];

    // =========================================
    // Lifecycle
    // =========================================

    public function boot(): void
    {
        $this->configure();
        $this->prepareColumns();
    }

    public function mount(): void
    {
        $this->perPage = $this->defaultPerPage;
        $this->sortBy = $this->defaultSortBy;
        $this->sortDirection = $this->defaultSortDirection;
    }

    // =========================================
    // Data methods
    // =========================================

    public function data(): LengthAwarePaginator
    {
        $searchableColumns = collect($this->columns)
            ->where('searchable', true)
            ->pluck('field')
            ->toArray();

        $query = $this->query();
        $hasSearch = $this->search && ! empty($searchableColumns);

        /**
         * @var \Illuminate\Database\Eloquent\Model;
         */
        $model = $query->getModel();
        $joinedTables = collect($query->getQuery()->joins)->map(fn ($join) => $join->table);

        // TODO: need some improve for multi-layered relations. Only add joins when user searches OR sorts
        if (in_array(JoinsRelations::class, class_uses($query->getModel()))) {
            collect([...($hasSearch ? $searchableColumns : []), $this->sortBy])
                ->filter(fn ($i) => \Str::contains($i, '.'))
                ->map(fn ($field) => \Str::beforeLast($field, '.'))
                ->unique()
                ->each(function (string $relation) use ($joinedTables, $query, $model) {
                    $relatedTable = $model->{$relation}()->getRelated()->getTable();
                    if ($joinedTables->contains($relatedTable)) {
                        return;
                    }
                    // Scope from JoinsRelations
                    $query->leftJoinRelation($relation);
                });
        }

        $query
            ->when($hasSearch, function ($query) use ($searchableColumns) {
                $sanitized = str_replace(['%', '_'], ['\\%', '\\_'], $this->search);

                $query->where(function ($q) use ($searchableColumns, $sanitized) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'like', "%{$sanitized}%");
                    }
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    public function selectedCount(): int
    {
        return count($this->selected);
    }

    public function visibleCount(): int
    {
        return collect($this->columns)
            ->filter(fn (array $col) => ! ($col['hidden'] ?? false))
            ->count();
    }

    // =========================================
    // Actions
    // =========================================

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

    /**
     * Toggle selection for all items on the current page only.
     */
    public function toggleSelectAll(): void
    {
        $allIds = $this->data()->pluck($this->primaryKey)->toArray();

        if (count($this->selected) === count($allIds)) {
            $this->selected = [];
        } else {
            $this->selected = $allIds;
        }
    }

    public function export(string $format): void
    {
        $this->dispatch('export-data', format: $format, search: $this->search, sortBy: $this->sortBy, sortDirection: $this->sortDirection);
    }

    // =========================================
    // Configuration helpers
    // =========================================

    public function setPrimaryKey(string $key): void
    {
        $this->primaryKey = $key;
    }

    public function setDefaultSort(string $column, string $direction = 'asc'): void
    {
        $this->defaultSortBy = $column;
        $this->defaultSortDirection = $direction;
    }

    public function setPerPage(int $perPage): void
    {
        $this->defaultPerPage = $perPage;
    }

    private function prepareColumns(): void
    {
        $this->columns = collect($this->columns())
            ->map(fn (array $col) => [
                'field' => $col['field'],
                'title' => $col['title'] ?? $this->translateField($col['field']),
                'sortable' => $col['sortable'] ?? false,
                'searchable' => $col['searchable'] ?? false,
                'hidden' => $col['hidden'] ?? false,
                'class' => $col['class'] ?? '',
                'component' => $col['component'] ?? null,
            ])
            ->toArray();
    }

    private function translateField(string $field): string
    {
        return __('cruds.'.\Str::slug(class_basename($this->query()->getModel())).'.fields.'.$field);
    }
}
