<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\{Account, Category, Schedule, Transaction};

new #[Title('Importações')] class extends Component
{
    public function loadReferenceData(): array
    {
        $teamId = auth()->user()->currentTeam->id;

        return [
            'accounts' => Account::where('team_id', $teamId)
                ->get(['id', 'acct_id', 'title'])
                ->toArray(),
            'categories' => Category::where('team_id', $teamId)
                ->get(['id', 'title', 'matcher', 'type'])
                ->toArray(),
            'schedules' => Schedule::where('team_id', $teamId)
                ->get([
                    'id', 'fitid', 'title', 'account_id', 'category_id',
                    'first_amount', 'amount', 'start_date', 'end_date',
                    'installments', 'matcher', 'type', 'variant',
                ])
                ->toArray(),
            'transactions' => Transaction::where('team_id', $teamId)
                ->select('fitid', 'amount', 'date', 'schedule_id', 'account_id')
                ->get()
                ->each->setAppends([])
                ->toArray(),
        ];
    }

    public function confirmImport(array $data): array
    {
        $team = auth()->user()->currentTeam;
        $userId = auth()->id();

        \DB::beginTransaction();
        try {
            $accountsCreated = 0;
            $schedulesUpdated = 0;
            $schedulesCreated = 0;
            $transactionsImported = 0;

            $accountMap = Account::where('team_id', $team->id)
                ->get(['id', 'acct_id'])
                ->keyBy('acct_id')
                ->map->id
                ->toArray();

            foreach ($data['accounts'] ?? [] as $acctData) {
                if (isset($accountMap[$acctData['acct_id']])) continue;

                $account = Account::create([
                    'team_id' => $team->id,
                    'acct_id' => $acctData['acct_id'],
                    'title' => $acctData['title'] ?? $acctData['acct_id'],
                    'type' => 'checking',
                    'currency' => 'BRL',
                    'fid' => '',
                    'user_id' => $userId,
                ]);

                $accountMap[$acctData['acct_id']] = $account->id;
                $accountsCreated++;
            }

            $tempScheduleIdMap = [];
            foreach ($data['scheduleUpdates'] ?? [] as $update) {
                $schedule = Schedule::where('team_id', $team->id)->find($update['id']);
                if (!$schedule) continue;

                $patch = [];
                foreach ($update['changes'] as $field => $change) {
                    $value = $change['to'];
                    if ($field === 'amount' && $schedule->type === 'expense') {
                        $value = -abs($value);
                    }
                    $patch[$field] = $value;
                }

                if (!empty($patch)) {
                    $schedule->update($patch);
                    $schedulesUpdated++;
                }
            }

            foreach ($data['scheduleCreates'] ?? [] as $i => $create) {
                $fields = collect($create)
                    ->except(['_txnIndices', '_predecessorId', '_isSuccessor', '_accountId'])
                    ->toArray();

                if (!empty($fields['accountId']) && isset($accountMap[$fields['accountId']])) {
                    $fields['account_id'] = $accountMap[$fields['accountId']];
                }
                unset($fields['accountId']);

                if (isset($fields['variant'])) {
                    $fields['variant'] = match ($fields['variant']) {
                        'installment' => 'fixed',
                        'subscription' => 'fixed',
                        default => 'variable',
                    };
                }

                if (empty($fields['start_date'])) unset($fields['start_date']);
                if (empty($fields['end_date'])) unset($fields['end_date']);
                if (empty($fields['first_amount'])) $fields['first_amount'] = null;

                $fields['amount'] ??= 0;
                if (($fields['type'] ?? 'expense') === 'expense') {
                    $fields['amount'] = -abs($fields['amount']);
                    if (isset($fields['first_amount'])) {
                        $fields['first_amount'] = -abs($fields['first_amount']);
                    }
                }
                $fields['title'] ??= $fields['matcher'] ?? 'Novo agendamento';
                $fields['team_id'] = $team->id;
                $fields['user_id'] = $userId;
                $fields['matcher'] = $fields['matcher'] ?? null;
                $fields['fitid'] = $fields['fitid'] ?? null;
                $fields['category_id'] = $fields['category_id'] ?? null;
                $fields['installments'] = $fields['installments'] ?? null;

                $schedule = Schedule::create($fields);
                $tempScheduleIdMap[$i] = $schedule->id;
                $schedulesCreated++;
            }

            $batch = [];
            foreach ($data['transactions'] ?? [] as $txn) {
                if (!empty($txn['_duplicate'])) continue;

                $scheduleId = $txn['schedule_id'] ?? null;
                if (!$scheduleId && isset($txn['_tempScheduleIndex'])) {
                    $scheduleId = $tempScheduleIdMap[$txn['_tempScheduleIndex']] ?? null;
                }

                $batch[] = [
                    'team_id' => $team->id,
                    'user_id' => $userId,
                    'fitid' => $txn['fitid'] ?? null,
                    'account_id' => isset($txn['accountId']) && isset($accountMap[$txn['accountId']])
                        ? $accountMap[$txn['accountId']]
                        : null,
                    'category_id' => $txn['category_id'] ?? null,
                    'schedule_id' => $scheduleId,
                    'description' => $txn['description'] ?? '',
                    'amount' => ($txn['type'] ?? 'expense') === 'expense'
                        ? -abs($txn['amount'] ?? 0)
                        : abs($txn['amount'] ?? 0),
                    'date' => $txn['date'] ?? now()->toDateString(),
                    'type' => $txn['type'] ?? 'expense',
                    'origin' => 'import',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($batch, 100) as $chunk) {
                Transaction::insert($chunk);
                $transactionsImported += count($chunk);
            }

            \DB::commit();

            return [
                'success' => true,
                'transactions' => $transactionsImported,
                'schedules_updated' => $schedulesUpdated,
                'schedules_created' => $schedulesCreated,
            ];
        } catch (\Exception $e) {
            \DB::rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
};
?>

<section class="w-full" x-data="importer()">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">Importar Transações</flux:heading>
        <flux:subheading size="lg" class="mb-6">Faça upload de extratos OFX para importar transações</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Stepper --}}
    <nav class="justify-center mb-8 flex items-center gap-4 text-sm">
        <template x-for="(step, i) in steps" :key="step.id">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span
                        class="flex size-8 items-center justify-center rounded-full text-xs font-semibold"
                        :class="stepDone(step.id)
                            ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                            : currentStep === step.id
                                ? 'border-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                                : 'border-2 border-zinc-300 text-zinc-400 dark:border-zinc-600 dark:text-zinc-500'"
                        x-text="i + 1"
                    ></span>
                    <span
                        :class="stepDone(step.id)
                            ? 'text-zinc-900 dark:text-white'
                            : currentStep === step.id
                                ? 'text-zinc-900 dark:text-white'
                                : 'text-zinc-400 dark:text-zinc-500'"
                        x-text="step.label"
                    ></span>
                </div>
                <template x-if="i < steps.length - 1">
                    <div class="h-px w-8 bg-zinc-300 dark:bg-zinc-600"></div>
                </template>
            </div>
        </template>
    </nav>

    {{-- Step: Upload --}}
    <div x-show="currentStep === 'upload'">
        <div
            class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 transition-colors"
            :class="dragging
                ? 'border-zinc-900 bg-zinc-50 dark:border-white dark:bg-zinc-800'
                : 'border-zinc-300 hover:border-zinc-400 dark:border-zinc-600 dark:hover:border-zinc-500'"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
            @click="$refs.fileInput.click()"
        >
            <flux:icon icon="cloud-arrow-up" variant="outline" class="mb-4 size-12 text-zinc-400" />
            <p class="mb-1 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Arraste arquivos .OFX aqui ou clique para selecionar
            </p>
            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                Apenas arquivos OFX são suportados
            </p>
            <input
                type="file"
                accept=".ofx,.ofx"
                multiple
                class="hidden"
                x-ref="fileInput"
                @change="onFileSelect"
            />
        </div>

        <template x-if="selectedFiles.length">
            <div class="mt-4 space-y-2">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    <span x-text="selectedFiles.length"></span> arquivo(s) selecionado(s):
                </p>
                <template x-for="(file, i) in selectedFiles" :key="i">
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon icon="document-text" variant="outline" class="size-5 text-zinc-400" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300" x-text="file.name"></p>
                            <p class="text-xs text-zinc-400" x-text="formatBytes(file.size)"></p>
                        </div>
                        <button @click="selectedFiles.splice(i, 1)" class="text-xs text-red-500 hover:text-red-700">
                            Remover
                        </button>
                    </div>
                </template>

                <div class="mt-6">
                    <flux:button
                        variant="primary"
                        @click="runPipeline"
                        x-bind:disabled="loading"
                    >
                        <span x-show="!loading">Iniciar Importação</span>
                        <span x-show="loading">Processando...</span>
                    </flux:button>
                </div>
            </div>
        </template>
    </div>

    {{-- Step: Processing --}}
    <div x-show="currentStep === 'processing'" class="py-12">
        <div class="mx-auto max-w-md text-center">
            <div class="mb-4">
                <svg class="mx-auto size-10 animate-spin text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300" x-text="progressLabel"></p>
            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div
                    class="h-full rounded-full bg-zinc-900 transition-all duration-500 dark:bg-white"
                    :style="'width: ' + progressPct + '%'"
                ></div>
            </div>
        </div>
    </div>

    {{-- Step: Review --}}
    <div x-show="currentStep === 'review'" class="space-y-8">
        {{-- Error --}}
        <template x-if="error">
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                <p class="font-medium">Erro</p>
                <p x-text="error"></p>
            </div>
        </template>

        {{-- Schedule Updates --}}
        <template x-if="scheduleUpdates.length">
            <div>
                <h3 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    Atualizações em Agendamentos
                    <span class="ml-1 text-xs font-normal text-zinc-400" x-text="`(${scheduleUpdates.length})`"></span>
                </h3>
                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-2 font-medium text-zinc-500">Título</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Alterações</th>
                                <th class="w-12 px-4 py-2">
                                    <input type="checkbox" @change="scheduleUpdates.forEach(u => u.selected = $el.checked)" checked />
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <template x-for="(upd, i) in scheduleUpdates" :key="i">
                                <tr class="bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300" x-text="upd.schedule.title || upd.schedule.matcher || '—'"></td>
                                    <td class="px-4 py-3">
                                        <template x-for="(change, field) in upd.changes" :key="field">
                                            <span class="mr-2 inline-block rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                                                <span x-text="field"></span>:
                                                <span class="line-through" x-text="formatVal(change.from)"></span>
                                                →
                                                <span class="font-medium" x-text="formatVal(change.to)"></span>
                                            </span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="checkbox" x-model="upd.selected" />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        {{-- Schedule Creates --}}
        <template x-if="scheduleCreates.length">
            <div>
                <h3 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    Novos Agendamentos
                    <span class="ml-1 text-xs font-normal text-zinc-400" x-text="`(${scheduleCreates.length})`"></span>
                </h3>
                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-2 font-medium text-zinc-500">Título</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Valor</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Início</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Parcelas</th>
                                <th class="w-12 px-4 py-2">
                                    <input type="checkbox" @change="scheduleCreates.forEach(c => c.selected = $el.checked)" checked />
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <template x-for="(cr, i) in scheduleCreates" :key="i">
                                <tr class="bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300" x-text="cr.schedule.title || '—'"></td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300" x-text="formatCurrency(cr.schedule.amount / 100)"></td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300" x-text="cr.schedule.start_date || '—'"></td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300" x-text="cr.schedule.installments || '—'"></td>
                                    <td class="px-4 py-3">
                                        <input type="checkbox" x-model="cr.selected" />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        {{-- Transactions table --}}
        <template x-if="transactions.length">
            <div>
                <h3 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    Transações
                    <span class="ml-1 text-xs font-normal text-zinc-400">
                        <span x-text="transactions.filter(t => !t._duplicate).length"></span> novas,
                        <span x-text="transactions.filter(t => t._duplicate).length"></span> duplicadas
                    </span>
                </h3>
                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-2 font-medium text-zinc-500">Data</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Descrição</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Valor</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Tipo</th>
                                <th class="px-4 py-2 font-medium text-zinc-500">Agendamento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <template x-for="(txn, i) in transactions" :key="i">
                                <tr
                                    class="transition-colors"
                                    :class="txn._duplicate
                                        ? 'bg-red-50/50 text-zinc-400 dark:bg-red-900/10'
                                        : 'bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800'"
                                >
                                    <td class="px-4 py-3" x-text="txn.date ? txn.date.slice(0, 10) : '—'"></td>
                                    <td class="max-w-xs truncate px-4 py-3" x-text="txn.description || '—'">
                                    </td>
                                    <td class="px-4 py-3" x-text="formatCurrency(txn.amount / 100)"></td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="txn.type === 'expense'
                                                ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'
                                                : 'bg-lime-100 text-lime-700 dark:bg-lime-900/20 dark:text-lime-400'"
                                            x-text="txn.type === 'expense' ? 'Despesa' : 'Receita'"
                                        ></span>
                                    </td>
                                    <td class="px-4 py-3 text-xs" x-text="txn.scheduleId || txn._matchedScheduleTitle || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <div class="mt-6 flex gap-3">
            <flux:button variant="primary" @click="confirmImport" x-bind:disabled="importing">
                <span x-show="!importing">Confirmar Importação</span>
                <span x-show="importing">Importando...</span>
            </flux:button>
            <flux:button variant="ghost" @click="reset">Cancelar</flux:button>
        </div>
    </div>

    {{-- Step: Done --}}
    <div x-show="currentStep === 'done'" class="py-12 text-center">
        <flux:icon icon="check-circle" variant="outline" class="mx-auto mb-4 size-16 text-lime-500" />
        <h3 class="mb-2 text-xl font-semibold text-zinc-800 dark:text-zinc-200">Importação concluída!</h3>
        <div class="mx-auto max-w-sm space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
            <p><span class="font-medium text-zinc-700 dark:text-zinc-300" x-text="importResult.transactions"></span> transações importadas</p>
            <p><span class="font-medium text-zinc-700 dark:text-zinc-300" x-text="importResult.schedules_updated"></span> agendamentos atualizados</p>
            <p><span class="font-medium text-zinc-700 dark:text-zinc-300" x-text="importResult.schedules_created"></span> agendamentos criados</p>
        </div>
        <div class="mt-6">
            <flux:button variant="primary" @click="reset">Importar Novamente</flux:button>
        </div>
    </div>
</section>


