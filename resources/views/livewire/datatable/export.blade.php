<div x-data="{ open: false }" class="relative" style="z-index: 101;">
    <flux:button variant="ghost" icon="arrow-down-tray" @click="open = !open">
        {{ __('datatable.export') }}
    </flux:button>

    <div
        x-show="open"
        @click.away="open = false"
        x-cloak
        class="absolute right-0 mt-2 w-40 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 py-2"
        style="z-index: 100;"
    >
        <button
            class="w-full px-4 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700"
            wire:click="export('csv')"
        >
            CSV
        </button>
        <button
            class="w-full px-4 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700"
            wire:click="export('xlsx')"
        >
            Excel
        </button>
        <button
            class="w-full px-4 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700"
            wire:click="export('pdf')"
        >
            PDF
        </button>
    </div>
</div>
