<script>
    // ─── OFX Parser ──────────────────────────────────────────────────────────
    window.OFX = (() => {
        function splitHeaderAndBody(raw) {
            const firstTag = raw.indexOf('<');
            if (firstTag === -1) throw new Error('OFX sem tags.');
            const headerText = raw.slice(0, firstTag);
            const body = raw.slice(firstTag).trim();
            const header = {};
            for (const line of headerText.split(/\r?\n/)) {
                const sep = line.indexOf(':');
                if (sep !== -1) { const k = line.slice(0, sep).trim(); if (k) header[k] = line.slice(sep + 1).trim(); }
            }
            return { header, body };
        }

        function tokenize(body) {
            const tokens = [];
            let i = 0;
            while (i < body.length) {
                if (body[i] === '<') {
                    const end = body.indexOf('>', i);
                    if (end === -1) break;
                    const inner = body.slice(i + 1, end).trim();
                    if (inner.startsWith('/')) tokens.push({ type: 'close', name: inner.slice(1).trim().toUpperCase() });
                    else if (inner.endsWith('/')) {
                        tokens.push({ type: 'open', name: inner.slice(0, -1).trim().toUpperCase() });
                        tokens.push({ type: 'close', name: inner.slice(0, -1).trim().toUpperCase() });
                    } else tokens.push({ type: 'open', name: inner.toUpperCase() });
                    i = end + 1;
                } else {
                    const next = body.indexOf('<', i);
                    const text = (next === -1 ? body.slice(i) : body.slice(i, next)).trim();
                    if (text) tokens.push({ type: 'text', value: text });
                    i = next === -1 ? body.length : next;
                }
            }
            return tokens;
        }

        function appendValue(obj, key, value) {
            if (obj[key] === undefined) obj[key] = value;
            else if (Array.isArray(obj[key])) obj[key].push(value);
            else obj[key] = [obj[key], value];
        }

        function buildTree(tokens, pos = 0) {
            const result = {};
            while (pos < tokens.length) {
                const tok = tokens[pos];
                if (tok.type === 'close') return { node: result, nextPos: pos + 1 };
                if (tok.type === 'open') {
                    const name = tok.name, next = tokens[pos + 1];
                    if (next && next.type === 'text') {
                        const after = tokens[pos + 2];
                        appendValue(result, name, next.value);
                        pos += (after && after.type === 'close' && after.name === name) ? 3 : 2;
                    } else {
                        const sub = buildTree(tokens, pos + 1);
                        appendValue(result, name, sub.node);
                        pos = sub.nextPos;
                    }
                    continue;
                }
                pos++;
            }
            return { node: result, nextPos: pos };
        }

        function dig(obj, ...keys) { let c = obj; for (const k of keys) { if (c == null) return undefined; c = c[k]; } return c; }

        function toArray(v) { return v == null ? [] : Array.isArray(v) ? v : [v]; }

        function parseOfxDate(raw) {
            if (!raw) return null;
            const clean = raw.replace(/\[.*\]/, '').trim();
            const m = clean.match(/^(\d{4})(\d{2})(\d{2})(\d{2})?(\d{2})?(\d{2})?/);
            if (!m) return raw;
            const [, y, mo, d, hh = '00', mm = '00', ss = '00'] = m;
            return `${y}-${mo}-${d}T${hh}:${mm}:${ss}`;
        }

        const TYPE_MAP = { DEBIT: 'expense', CREDIT: 'income' };

        function parse(text) {
            const { header, body } = splitHeaderAndBody(text);
            const tokens = tokenize(body);
            const { node: tree } = buildTree(tokens);
            const root = tree.OFX || tree;
            const stmtrs = dig(root, 'BANKMSGSRSV1', 'STMTTRNRS', 'STMTRS') ||
                dig(root, 'CREDITCARDMSGSRSV1', 'CCSTMTTRNRS', 'CCSTMTRS') || {};
            const acctFrom = dig(stmtrs, 'BANKACCTFROM') || dig(stmtrs, 'CCACCTFROM') || {};
            const banktranlist = dig(stmtrs, 'BANKTRANLIST') || {};
            const accountId = acctFrom.ACCTID || null;
            const rawTxns = toArray(banktranlist.STMTTRN || []);
            return rawTxns.map(t => ({
                date: t.DTPOSTED ? parseOfxDate(t.DTPOSTED) : null,
                description: t.MEMO || t.NAME || null,
                amount: t.TRNAMT != null ? Math.round(Math.abs(parseFloat(t.TRNAMT)) * 100) : null,
                type: TYPE_MAP[t.TRNTYPE] || t.TRNTYPE || null,
                fitid: t.FITID || null,
                accountId,
                _duplicate: false,
                _scheduleId: null,
                _categoryId: null,
                _tempScheduleIndex: null,
                _matchedScheduleTitle: null,
            }));
        }

        return { parse };
    })();

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function lastDayOfMonth(dateStr) {
        const d = new Date(dateStr);
        return new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().slice(0, 10);
    }

    function addMonths(dateStr, n) {
        const d = new Date(dateStr);
        d.setMonth(d.getMonth() + n);
        return d.toISOString().slice(0, 10);
    }

    function dateStr(d) { return d ? (typeof d === 'string' ? d.slice(0, 10) : new Date(d).toISOString().slice(0, 10)) : null; }

    function inDateRange(txnDate, startDate, endDate) {
        const t = dateStr(txnDate);
        if (!t) return true;
        if (startDate && t < dateStr(startDate)) return false;
        if (endDate && t > dateStr(endDate)) return false;
        return true;
    }

    function tryRegex(pattern) {
        if (!pattern) return null;
        try { return new RegExp(pattern, 'i'); } catch { return null; }
    }

    function extractInstallment(desc) {
        if (!desc) return null;
        const m = desc.match(/[Pp]arcela\s+(\d{1,2})\s*[\/de]+\s*(\d{1,3})/);
        if (!m) return null;
        return { current: parseInt(m[1]), total: parseInt(m[2]) };
    }

    function findCategoryForDesc(desc, categories) {
        if (!desc) return null;
        for (const cat of categories) {
            const re = tryRegex(cat.matcher);
            if (re && re.test(desc)) return cat;
        }
        return null;
    }

    // ─── Alpine Component ────────────────────────────────────────────────────
    window.importer = () => ({
        // UI state
            steps: [
                { id: 'upload', label: 'Arquivos' },
                { id: 'processing', label: 'Processando' },
                { id: 'review', label: 'Revisão' },
                { id: 'done', label: 'Concluído' },
            ],
            currentStep: 'upload',
            loading: false,
            importing: false,
            error: null,
            dragging: false,
            progressLabel: '',
            progressPct: 0,
            selectedFiles: [],

            // Data
            transactions: [],
            schedules: [],
            categories: [],
            accounts: [],
            existingTxns: [],

            scheduleUpdates: [],
            scheduleCreates: [],
            importResult: { transactions: 0, schedules_updated: 0, schedules_created: 0 },

            async init() {},

            stepDone(id) {
                const order = ['upload', 'processing', 'review', 'done'];
                const cur = order.indexOf(this.currentStep);
                const tgt = order.indexOf(id);
                return cur > tgt || this.currentStep === 'done';
            },

            // File handling
            onFileSelect(e) { this.selectedFiles = Array.from(e.target.files); },

            onDrop(e) {
                this.dragging = false;
                this.selectedFiles = Array.from(e.dataTransfer.files).filter(f => f.name.toLowerCase().endsWith('.ofx'));
            },

            formatBytes(b) {
                if (b < 1024) return b + ' B';
                if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
                return (b / 1048576).toFixed(1) + ' MB';
            },

            formatCurrency(v) {
                if (v == null) return '—';
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
            },

            formatVal(v) {
                if (v == null || v === '') return '—';
                if (typeof v === 'number') return v.toString();
                return String(v);
            },

            // Main pipeline
            async runPipeline() {
                this.loading = true;
                this.error = null;
                this.currentStep = 'processing';
                this.progressPct = 0;

                try {
                    this.setProgress('Convertendo arquivos OFX…', 5);
                    const rawTxns = await this.parseOFXFiles();

                    this.setProgress('Carregando dados…', 15);
                    await this.loadReferenceData();

                    this.setProgress('Verificando duplicatas…', 30);
                    this.deduplicate(rawTxns);

                    this.setProgress('Associando agendamentos…', 45);
                    this.matchSchedules();

                    this.setProgress('Enriquecendo agendamentos…', 60);
                    this.enrichSchedules();

                    this.setProgress('Inferindo categorias…', 70);
                    this.inferCategories();

                    this.setProgress('Sugerindo novos agendamentos…', 80);
                    this.suggestNewSchedules();

                    this.setProgress('Classificando transações restantes…', 90);
                    this.matchRemainingCategories();

                    this.setProgress('Pronto!', 100);
                    await this.sleep(300);
                    this.currentStep = 'review';

                } catch (e) {
                    this.error = e.message;
                    this.currentStep = 'upload';
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },

            setProgress(label, pct) {
                this.progressLabel = label;
                this.progressPct = pct;
            },

            sleep(ms) { return new Promise(r => setTimeout(r, ms)); },

            // Parse OFX
            async parseOFXFiles() {
                const results = [];
                for (const file of this.selectedFiles) {
                    const text = await file.text();
                    const txns = OFX.parse(text);
                    results.push(...txns);
                }
                return results;
            },

            // Load reference data from Livewire
            async loadReferenceData() {
                const data = await this.$wire.loadReferenceData();
                this.accounts = data.accounts || [];
                this.categories = data.categories || [];
                this.schedules = data.schedules || [];
                this.existingTxns = data.transactions || [];
            },

            // Dedup
            deduplicate(rawTxns) {
                const existingSet = new Set(
                    this.existingTxns.map(t => `${t.fitid}|${t.amount}|${t.date ? t.date.slice(0, 10) : ''}`)
                );

                this.transactions = rawTxns.map(t => ({
                    ...t,
                    _duplicate: t.fitid && existingSet.has(`${t.fitid}|${t.amount}|${dateStr(t.date)}`),
                }));
            },

            // Match schedules
            matchSchedules() {
                const newTxns = this.transactions.filter(t => !t._duplicate);

                for (const txn of newTxns) {
                    for (const sched of this.schedules) {
                        if (sched.account_id) {
                            const schedAccount = this.accounts.find(a => a.id === sched.account_id);
                            if (schedAccount?.acct_id && schedAccount.acct_id !== txn.accountId) continue;
                        }

                        const schedStart = sched.start_date ? dateStr(sched.start_date) : null;
                        const schedEnd = sched.end_date ? dateStr(sched.end_date) : null;
                        if (!inDateRange(txn.date, schedStart, schedEnd)) continue;

                        const re = tryRegex(sched.matcher);
                        if (!re || !re.test(txn.description)) continue;

                        txn._scheduleId = sched.id;
                        txn._categoryId = txn._categoryId || sched.category_id || null;
                        txn._matchedScheduleTitle = sched.title || sched.matcher;

                        const schedAmt = Math.abs(sched.amount || 0);
                        const txnAmt = Math.abs(txn.amount);
                        const amtMissing = schedAmt === 0;
                        const amtChanged = !amtMissing && schedAmt !== txnAmt;

                        if (!amtChanged) {
                            if (amtMissing) {
                                this.queueAmountUpdate(sched, txnAmt);
                            }
                        } else {
                            this.queueAmountUpdate(sched, txnAmt);
                        }
                        break;
                    }
                }
            },

            queueAmountUpdate(sched, newAmt) {
                const existing = this.scheduleUpdates.find(u => u.id === sched.id);
                if (existing) {
                    existing.changes.amount = { from: sched.amount || null, to: newAmt };
                } else {
                    this.scheduleUpdates.push({
                        id: sched.id,
                        schedule: sched,
                        changes: { amount: { from: sched.amount || null, to: newAmt } },
                        selected: true,
                    });
                }
            },

            // Enrich schedules
            enrichSchedules() {
                const matched = this.transactions.filter(t => !t._duplicate && t._scheduleId);

                for (const sched of this.schedules) {
                    const txns = matched
                        .filter(t => t._scheduleId === sched.id)
                        .sort((a, b) => (a.date || '') < (b.date || '') ? -1 : 1);

                    if (!txns.length) continue;

                    const changes = {};
                    const firstTxn = txns[0];
                    const installInfo = extractInstallment(firstTxn.description);
                    const schedStart = sched.start_date ? dateStr(sched.start_date) : null;
                    const schedEnd = sched.end_date ? dateStr(sched.end_date) : null;

                    if (!schedStart && !sched.title) {
                        changes.title = { from: null, to: (firstTxn.description || '')
                            .replace(/[\s|-]*[Pp]arcela\s+\d{1,2}\s*[\/de]+\s*\d{1,3}/g, '')
                            .trim() || firstTxn.description };
                    }

                    if (!schedStart) {
                        if (!installInfo) {
                            changes.start_date = { from: null, to: dateStr(firstTxn.date) };
                        } else if (installInfo.current === 1) {
                            changes.start_date = { from: null, to: dateStr(firstTxn.date) };
                        } else {
                            const monthsBack = installInfo.current - 1;
                            const approxStart = addMonths(firstTxn.date, -monthsBack);
                            changes.start_date = { from: null, to: approxStart.slice(0, 8) + '01' };
                        }
                    }

                    if (installInfo && installInfo.current === 1) {
                        if (!sched.first_amount) {
                            changes.first_amount = { from: sched.first_amount, to: Math.abs(firstTxn.amount) };
                        }
                        if (!sched.amount) {
                            changes.amount = { from: sched.amount, to: Math.abs(firstTxn.amount) };
                        }
                    } else if (installInfo && installInfo.current > 1) {
                        const absAmt = Math.abs(firstTxn.amount);
                        if (sched.amount && sched.amount !== absAmt) {
                            changes.amount = { from: sched.amount, to: absAmt };
                        } else if (!sched.amount) {
                            changes.amount = { from: null, to: absAmt };
                        }
                    }

                    if (installInfo?.total && !sched.installments) {
                        changes.installments = { from: null, to: installInfo.total };
                    }

                    if (!schedEnd) {
                        const totalInst = changes.installments?.to || sched.installments;
                        const knownStart = changes.start_date?.to || schedStart;
                        if (totalInst && knownStart) {
                            const lastDate = addMonths(knownStart, totalInst - 1);
                            changes.end_date = { from: null, to: lastDayOfMonth(lastDate) };
                        }
                    }

                    if (Object.keys(changes).length) {
                        const existing = this.scheduleUpdates.find(u => u.id === sched.id);
                        if (existing) {
                            Object.assign(existing.changes, changes);
                        } else {
                            this.scheduleUpdates.push({ id: sched.id, schedule: sched, changes, selected: true });
                        }
                    }
                }
            },

            // Infer categories for schedules
            inferCategories() {
                for (const upd of this.scheduleUpdates) {
                    const sched = upd.schedule;
                    if (sched.category_id) continue;

                    const txn = this.transactions.find(t => t._scheduleId === sched.id);
                    if (!txn) continue;

                    const cat = findCategoryForDesc(txn.description, this.categories);
                    if (cat) {
                        upd.changes.category_id = { from: null, to: cat.id };
                        this.transactions.filter(t => t._scheduleId === sched.id).forEach(t => {
                            t._categoryId = cat.id;
                        });
                    }
                }
            },

            // Suggest new schedules
            suggestNewSchedules() {
                this.scheduleCreates = [];
                const unmatched = this.transactions.filter(t => !t._duplicate && !t._scheduleId);

                const groups = {};
                for (const txn of unmatched) {
                    const info = extractInstallment(txn.description);
                    if (!info) continue;
                    const key = txn.fitid || txn.description;
                    if (!groups[key]) groups[key] = { txns: [], installInfo: info };
                    groups[key].txns.push(txn);
                }

                for (const [, { txns, installInfo }] of Object.entries(groups)) {
                    txns.sort((a, b) => (a.date || '') < (b.date || '') ? -1 : 1);
                    const first = txns[0];
                    const last = txns[txns.length - 1];
                    const absAmt = Math.abs(last.amount);

                    const suggestedTitle = (first.description || '')
                        .replace(/[\s|-]*[Pp]arcela\s+\d{1,2}\s*[\/de]+\s*\d{1,3}/g, '')
                        .trim();

                    const suggestedMatcher = (first.description || '')
                        .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
                        .replace(/([\s|-]*[Pp]arcela\s+)(\d{1,2})(\s*[\/de]+\s*)(\d{1,3})/g, '$1(\\d{1,2})$3\\d{1,2}');

                    const firstInfo = extractInstallment(first.description);
                    let startDate;
                    if (firstInfo) {
                        if (firstInfo.current === 1) {
                            startDate = dateStr(first.date);
                        } else {
                            const monthsBack = firstInfo.current - 1;
                            const approxStart = addMonths(first.date, -monthsBack);
                            startDate = approxStart.slice(0, 8) + '01';
                        }
                    } else {
                        startDate = null;
                    }

                    const totalFromDesc = txns.reduce((t, txn) => {
                        const info = extractInstallment(txn.description);
                        return info?.total || t;
                    }, null);

                    let endDate = null;
                    if (startDate && totalFromDesc) {
                        endDate = lastDayOfMonth(addMonths(startDate, totalFromDesc - 1));
                    }

                    const cat = findCategoryForDesc(first.description, this.categories);

                    const suggested = {
                        title: suggestedTitle,
                        matcher: suggestedMatcher,
                        fitid: first.fitid,
                        variant: 'installment',
                        amount: absAmt,
                        first_amount: firstInfo?.current === 1 ? Math.abs(first.amount) : null,
                        installments: totalFromDesc || null,
                        start_date: startDate,
                        end_date: endDate,
                        category_id: cat?.id || null,
                        type: first.type,
                        accountId: first.accountId,
                    };

                    const createIndex = this.scheduleCreates.length;

                    this.scheduleCreates.push({
                        schedule: suggested,
                        selected: true,
                    });

                    txns.forEach(t => {
                        t._tempScheduleIndex = createIndex;
                        t._matchedScheduleTitle = suggestedTitle;
                    });
                }
            },

            // Category matching for remaining transactions
            matchRemainingCategories() {
                const remaining = this.transactions.filter(t => !t._duplicate && !t._scheduleId && !t._categoryId);
                for (const txn of remaining) {
                    const cat = findCategoryForDesc(txn.description, this.categories);
                    if (cat) txn._categoryId = cat.id;
                }
            },

            // Confirm import
            async confirmImport() {
                this.importing = true;
                try {
                    const accountIds = [...new Set(this.transactions.map(t => t.accountId).filter(Boolean))];
                    const existingAcctIds = new Set(this.accounts.map(a => a.acct_id));
                    const newAccounts = accountIds
                        .filter(id => !existingAcctIds.has(id))
                        .map(acctId => ({ acct_id: acctId, title: acctId }));

                    const selectedUpdates = this.scheduleUpdates.filter(u => u.selected).map(u => ({
                        id: u.id,
                        changes: u.changes,
                    }));

                    const selectedCreates = this.scheduleCreates.filter(c => c.selected).map(c => c.schedule);

                    const payload = {
                        accounts: newAccounts,
                        scheduleUpdates: selectedUpdates,
                        scheduleCreates: selectedCreates,
                        transactions: this.transactions.map(t => ({
                            fitid: t.fitid,
                            accountId: t.accountId,
                            description: t.description,
                            amount: t.amount,
                            date: t.date ? t.date.slice(0, 10) : null,
                            type: t.type,
                            category_id: t._categoryId || null,
                            schedule_id: t._scheduleId || null,
                            _duplicate: t._duplicate,
                            _tempScheduleIndex: t._tempScheduleIndex ?? null,
                        })),
                    };

                    const result = await this.$wire.confirmImport(payload);

                    if (!result.success) {
                        throw new Error(result.error || 'Erro desconhecido');
                    }

                    this.importResult = result;
                    this.currentStep = 'done';

                } catch (e) {
                    this.error = 'Erro ao importar: ' + e.message;
                    console.error(e);
                } finally {
                    this.importing = false;
                }
            },

            reset() {
                this.selectedFiles = [];
                this.transactions = [];
                this.schedules = [];
                this.categories = [];
                this.accounts = [];
                this.existingTxns = [];
                this.scheduleUpdates = [];
                this.scheduleCreates = [];
                this.error = null;
                this.currentStep = 'upload';
                this.progressPct = 0;
                this.importResult = { transactions: 0, schedules_updated: 0, schedules_created: 0 };
            },
        });
</script>
