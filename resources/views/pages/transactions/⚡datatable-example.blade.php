<?php

use App\Livewire\DataTable\DataTable;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;

new #[Title('DataTable Example')] class extends DataTable
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('date', 'desc');
        $this->setPerPage(25);
    }

    public function columns(): array
    {
        return [
            ['field' => 'id', 'title' => 'ID', 'sortable' => true, 'hidden' => true],
            ['field' => 'date', 'title' => 'Date', 'sortable' => true],
            ['field' => 'description', 'title' => 'Description', 'sortable' => true, 'searchable' => true],
            ['field' => 'amount', 'title' => 'Amount', 'sortable' => true],
            ['field' => 'type', 'title' => 'Type', 'sortable' => true],
        ];
    }

    public function query(): Builder
    {
        return Transaction::query()
            ->where('team_id', auth()->user()->current_team_id);
    }

    public function checkable(): bool
    {
        return true;
    }

    public function exportable(): bool
    {
        return true;
    }
};
?>

<section class="w-full">
    <flux:heading size="xl">{{ __('DataTable Example') }}</flux:heading>
    <flux:subheading>{{ __('Demonstration of the reusable DataTable component') }}</flux:subheading>

    <div class="mt-8">
        @include('livewire.datatable')
    </div>
</section>
