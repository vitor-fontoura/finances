<div x-data="{ open: false }" class="relative">
    <flux:button variant="ghost" icon="cog-6-tooth" @click="open = !open">
        {{ __('datatable.columns') }}
    </flux:button>

    <div
        x-show="open"
        @click.away="open = false"
        x-cloak
        class="absolute right-0 mt-2 min-w-64 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 py-2 z-30"
    >
        @foreach($this->columns as $column)
            <flux:field variant="inline" class="px-4 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <flux:checkbox
                    checked="!hiddenColumns.includes(`{{ $column['field'] }}`)"
                    x-effect="$el.checked = !hiddenColumns.includes(`{{ $column['field'] }}`)"
                    @change="hiddenColumns = $event.target.checked
                        ? hiddenColumns.filter(f => f !== `{{ $column['field'] }}`)
                        : [...hiddenColumns, `{{ $column['field'] }}`]"
                />
                <flux:label>{{ $column['title'] ?? $this->translateField($column['field']) }}</flux:label>
            </flux:field>
        @endforeach
    </div>
</div>
