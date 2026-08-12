<?php

use App\Livewire\DataTable\DataTable;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;

new #[Title('Transactions')] class extends DataTable
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
            ['field' => 'id', 'sortable' => true, 'hidden' => true],
            ['field' => 'date', 'sortable' => true, 'component' => 'datatable.date'],
            ['field' => 'category.title', 'title' => 'Categoria', 'sortable' => true, 'searchable' => true, 'component' => 'datatable.relation'],
            ['field' => 'schedule.title', 'title' => 'Programação', 'sortable' => true, 'searchable' => true, 'component' => 'datatable.relation'],
            ['field' => 'description', 'sortable' => true, 'searchable' => true],
            ['field' => 'amount', 'sortable' => true, 'component' => 'datatable.currency'],
            ['field' => 'type', 'sortable' => true, 'component' => 'datatable.enum'],
            ['field' => 'origin', 'sortable' => true],
            ['field' => 'created_at', 'sortable' => true, 'component' => 'datatable.diff', 'hidden' => true],
            ['field' => 'updated_at', 'sortable' => true, 'component' => 'datatable.diff', 'hidden' => true],
        ];
    }

    public function query(): Builder
    {
        return Transaction::query()
            ->with('category:id,title', 'schedule:id,title')
            ->where('transactions.team_id', auth()->user()->current_team_id);
    }
};
?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('cruds.transaction.title') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('cruds.transaction.subtitle') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mt-6">
        @include('livewire.datatable')
    </div>
</section>
