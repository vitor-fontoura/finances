<?php

use App\Livewire\DataTable\DataTable;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;

new #[Title('Schedules')] class extends DataTable
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('start_date', 'desc');
        $this->setPerPage(25);
    }

    protected function columns(): array
    {
        return [
            ['field' => 'id', 'sortable' => true, 'hidden' => true],
            ['field' => 'title', 'sortable' => true, 'searchable' => true],
            ['field' => 'amount', 'sortable' => true, 'component' => 'datatable.currency'],
            ['field' => 'type', 'sortable' => true, 'component' => 'datatable.enum'],
            ['field' => 'variant', 'sortable' => true, 'component' => 'datatable.enum'],
            ['field' => 'start_date', 'sortable' => true, 'component' => 'datatable.date'],
            ['field' => 'end_date', 'sortable' => true, 'component' => 'datatable.date'],
            ['field' => 'installments', 'sortable' => true],
            ['field' => 'matcher', 'sortable' => true],
            ['field' => 'created_at', 'sortable' => true, 'component' => 'datatable.diff', 'hidden' => true],
            ['field' => 'updated_at', 'sortable' => true, 'component' => 'datatable.diff', 'hidden' => true],
        ];
    }

    public function query(): Builder
    {
        return Schedule::query()
            ->where('team_id', auth()->user()->current_team_id);
    }
};
?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('cruds.schedule.title') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('cruds.schedule.subtitle') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mt-6">
        @include('livewire.datatable')
    </div>
</section>
