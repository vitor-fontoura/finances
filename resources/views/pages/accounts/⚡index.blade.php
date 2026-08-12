<?php

use App\Livewire\DataTable\DataTable;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;

new #[Title('Accounts')] class extends DataTable
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('title', 'asc');
        $this->setPerPage(25);
    }

    public function columns(): array
    {
        return [
            ['field' => 'id', 'sortable' => true, 'hidden' => true],
            ['field' => 'title', 'sortable' => true, 'searchable' => true],
            ['field' => 'type', 'sortable' => true, 'component' => 'datatable.enum'],
            ['field' => 'acct_id', 'sortable' => true],
            ['field' => 'currency', 'sortable' => true],
            ['field' => 'initial_balance', 'sortable' => true, 'component' => 'datatable.currency'],
            ['field' => 'created_at', 'sortable' => true, 'component' => 'datatable.diff', 'hidden' => true],
            ['field' => 'updated_at', 'sortable' => true, 'component' => 'datatable.diff', 'hidden' => true],
        ];
    }

    public function query(): Builder
    {
        return Account::query()
            ->where('team_id', auth()->user()->current_team_id);
    }
};
?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('cruds.account.title') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('cruds.account.subtitle') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mt-6">
        @include('livewire.datatable')
    </div>
</section>
