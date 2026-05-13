# SCENARIO MATRIX — SeederNew Finance Correctness

Repo: Asyraf2003/bengkelnativejs  
Tanggal: 2026-04-26  
Area: seedernew / finance correctness / deterministic scenario matrix  
Status: BASELINE TRACEABILITY MATRIX  
Related ADR: docs/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md  
Related proof: docs/handoff/v2/seedernew/2026-04-26-seedernew-make2-idempotency-proof.md

---

## 1. Purpose

Dokumen ini mendefinisikan scenario matrix minimum untuk SeederNew Finance Correctness Strategy.

Tujuan matrix ini:

~~~text
- Menjadi daftar scenario wajib untuk make 2 dan make 3.
- Menghubungkan scenario ke domain, table, expected effect, invariant, dan manual review path.
- Menjadi basis untuk audit command dan finance invariant tests.
- Menghindari kondisi seeder "banyak data" tetapi tidak bisa diaudit.
~~~

Dokumen ini bukan bukti bahwa semua scenario sudah fully seeded.
Status setiap scenario harus dibuktikan melalui:

~~~text
- local seeder trace audit
- audit command
- invariant tests
- make verify
~~~

---

## 2. Status Legend

~~~text
DEFINED
Scenario sudah didefinisikan dalam matrix.

SEEDED-PROVEN
Scenario sudah terbukti ada lewat audit command / query / test.

PARTIAL-PROVEN
Sebagian data scenario sudah ada, tetapi belum full invariant proof.

PENDING SEED TRACE AUDIT
Belum ada bukti lokal yang cukup untuk mengunci seeder class dan row trace.

DEFERRED MAKE 3
Scenario khusus load / annual / extreme, tidak boleh diklaim sebelum make 3 diuji.
~~~

---

## 3. Matrix Columns

Kolom matrix:

~~~text
Domain
Scenario ID
Scenario Name
Purpose
make level
Seeder class
Tables involved
Expected status
Expected money effect
Expected stock effect
Expected report effect
Invariant checks
Manual review path
Status
~~~

---

## 4. Customer Transaction Scenarios

| Domain | Scenario ID | Scenario Name | Purpose | make level | Seeder class | Tables involved | Expected status | Expected money effect | Expected stock effect | Expected report effect | Invariant checks | Manual review path | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Customer | CUST-001 | note unpaid | Nota dibuat tanpa pembayaran | make 2 | CustomerTransactionBaselineSeeder | notes, work_items, customer_transactions | unpaid / Belum Lunas | outstanding = note total | stock follows item policy | muncul di laporan piutang/outstanding | note total > 0, allocated = 0 | Nota pelanggan / laporan transaksi per nota | PENDING SEED TRACE AUDIT |
| Customer | CUST-002 | note full paid single payment | Nota lunas dengan satu pembayaran | make 2 | CustomerTransactionBaselineSeeder | notes, customer_payments, customer_payment_allocations | paid / Lunas | allocated = note total | stock follows item policy | muncul sebagai lunas | allocation per note <= note total | Nota pelanggan / detail pembayaran | PENDING SEED TRACE AUDIT |
| Customer | CUST-003 | note partial payment | Nota dibayar sebagian | make 2 | CustomerTransactionBaselineSeeder | notes, customer_payments, customer_payment_allocations | unpaid / partial | 0 < allocated < note total | stock follows item policy | outstanding tetap muncul | outstanding = total - allocated | laporan transaksi per nota | PENDING SEED TRACE AUDIT |
| Customer | CUST-004 | note multi-payment full paid | Nota lunas via beberapa pembayaran | make 2 | CustomerTransactionBaselineSeeder | notes, customer_payments, customer_payment_allocations | paid / Lunas | sum allocations = note total | stock follows item policy | payment history muncul | allocation total per note <= note total | detail pembayaran pelanggan | PENDING SEED TRACE AUDIT |
| Customer | CUST-005 | note service only | Nota hanya jasa | make 2 | CustomerTransactionBaselineSeeder | notes, work_item_services | active | revenue service only | no stock movement expected | service revenue visible | note total = service total | detail nota | PENDING SEED TRACE AUDIT |
| Customer | CUST-006 | note store stock only | Nota hanya barang stok toko | make 2 | CustomerTransactionBaselineSeeder | notes, work_item_store_stock_lines, inventory_movements | active | goods revenue only | stock out expected | stock keluar terlapor | stock out source exists | laporan stok / detail nota | PENDING SEED TRACE AUDIT |
| Customer | CUST-007 | note service + store stock | Nota jasa + stok toko | make 2 | CustomerTransactionBaselineSeeder | notes, work_item_services, work_item_store_stock_lines | active | service + goods total | stock out for product lines | mixed revenue visible | note total = service + goods | detail nota | PENDING SEED TRACE AUDIT |
| Customer | CUST-008 | note service + external purchase | Nota jasa + pembelian luar | make 2 | CustomerTransactionBaselineSeeder | notes, work_item_services, external purchase related rows | active | service + external cost/revenue | no internal stock unless policy says | external purchase visible | total components reconcile | detail nota | PENDING SEED TRACE AUDIT |
| Customer | CUST-009 | mixed service + store stock + external purchase | Nota campuran lengkap | make 2 | CustomerTransactionBaselineSeeder | notes, services, stock lines, external purchase rows | active | all components reconcile | stock out only for store stock | full detail report visible | note total = all components | detail nota / breakdown customer | PENDING SEED TRACE AUDIT |
| Customer | CUST-010 | partial refund | Refund sebagian dari pembayaran | make 2 | CustomerTransactionBaselineSeeder | notes, customer_payments, payment_allocations, refunds | refunded partial | refund < allocated | stock restore only if policy says | refund visible | refund total <= allocated | detail refund | PENDING SEED TRACE AUDIT |
| Customer | CUST-011 | full refund | Refund penuh | make 2 | CustomerTransactionBaselineSeeder | notes, customer_payments, payment_allocations, refunds | refunded | refund = allocated or policy total | stock restore only if policy says | refund report visible | refund <= allocated | detail refund | PENDING SEED TRACE AUDIT |
| Customer | CUST-012 | paid note status correction | Koreksi status nota lunas | make 2 | CustomerTransactionBaselineSeeder | notes, note_mutation_events, note_mutation_snapshots | corrected | money must remain reconciled | no stock corruption | correction audit visible | no destructive finalized mutation leak | mutation timeline | PENDING SEED TRACE AUDIT |
| Customer | CUST-013 | paid service nominal correction | Koreksi nominal jasa pada nota paid | make 2 | CustomerTransactionBaselineSeeder | notes, work_item_services, note_mutation_events | corrected | corrected total reconciles | no stock effect | correction visible | corrected total = final components | mutation timeline | PENDING SEED TRACE AUDIT |
| Customer | CUST-014 | unpaid note remains outstanding | Nota belum lunas tetap outstanding | make 2 | CustomerTransactionBaselineSeeder | notes, payment_allocations | unpaid | outstanding > 0 | stock follows item policy | piutang/outstanding visible | total > allocated | laporan piutang / nota | PENDING SEED TRACE AUDIT |
| Customer | CUST-015 | refund does not exceed allocated amount | Guard refund overflow | make 2 | CustomerTransactionBaselineSeeder | refunds, customer_payment_allocations | valid | refund <= allocated | policy-dependent | no refund overflow report issue | refund overflow = 0 | refund report / audit command | PENDING SEED TRACE AUDIT |

---

## 5. Supplier / Procurement Scenarios

| Domain | Scenario ID | Scenario Name | Purpose | make level | Seeder class | Tables involved | Expected status | Expected money effect | Expected stock effect | Expected report effect | Invariant checks | Manual review path | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Supplier | SUP-001 | invoice draft/editable | Invoice supplier draft | make 2 | SupplierInvoiceScenarioSeeder | supplier_invoices, supplier_invoice_lines | draft | payable not finalized unless policy says | no stock effect | draft visible | line total = invoice total | supplier invoice detail | PENDING SEED TRACE AUDIT |
| Supplier | SUP-002 | invoice received unpaid | Barang diterima belum dibayar | make 2 | SupplierInvoiceBaselineSeeder / SupplierInvoiceScenarioSeeder | supplier_invoices, receipt rows, inventory_movements | received unpaid | payable exists | stock in expected | hutang supplier visible | received != paid | hutang supplier / stok | PENDING SEED TRACE AUDIT |
| Supplier | SUP-003 | invoice paid pending proof | Invoice dibayar tanpa proof uploaded | make 2 | SupplierInvoiceScenarioSeeder | supplier_invoices, supplier_payments | paid pending proof | payable reduced | no stock implication from payment | proof pending visible | paid does not imply received | supplier payment detail | PENDING SEED TRACE AUDIT |
| Supplier | SUP-004 | invoice paid uploaded proof | Payment dengan proof | make 2 | SupplierInvoiceScenarioSeeder | supplier_payments, supplier_payment_proofs | paid proof uploaded | payable reduced | no stock implication from payment | proof status visible | proof state matches attachment | supplier payment proof | PENDING SEED TRACE AUDIT |
| Supplier | SUP-005 | invoice received + paid full cycle | Full procurement cycle | make 2 | SupplierInvoiceBaselineSeeder / SupplierInvoiceScenarioSeeder | supplier_invoices, lines, receipts, payments, inventory | received and paid | payable = 0 | stock in expected | full cycle visible | invoice total = lines; payments <= total | supplier invoice detail | PENDING SEED TRACE AUDIT |
| Supplier | SUP-006 | invoice voided before domain effect | Void sebelum efek domain final | make 2 | SupplierInvoiceVoidedScenarioSeeder | supplier_invoices, supplier_invoice_versions | voided | no active payable leak | no stock effect | void visible | voided active payable leaks = 0 | supplier invoice void scenario | PARTIAL-PROVEN |
| Supplier | SUP-007 | invoice number reused after void | Nomor invoice dipakai ulang setelah void | make 2 | SupplierInvoiceVoidedScenarioSeeder | supplier_invoices, supplier_invoice_versions, projections | one voided + one active | active payable only from active invoice | active invoice policy only | reuse visible | duplicate active normalized no = 0 | supplier invoice list | PARTIAL-PROVEN |
| Supplier | SUP-008 | payment without receipt does not affect stock | Payment tidak otomatis stock in | make 2 | SupplierInvoiceScenarioSeeder | supplier_payments, inventory_movements | paid / not received | payable reduced | no stock movement from payment alone | paid without receipt visible | paid does not imply received | supplier invoice + stock report | PENDING SEED TRACE AUDIT |
| Supplier | SUP-009 | receipt without payment affects stock but not paid status | Receipt memengaruhi stok, bukan paid | make 2 | SupplierInvoiceScenarioSeeder | receipts, inventory_movements, supplier_invoices | received unpaid | payable remains | stock in expected | stock and payable both visible | received stock != paid | stok + hutang supplier | PENDING SEED TRACE AUDIT |
| Supplier | SUP-010 | supplier payable remains correct | Rekonsiliasi hutang supplier | make 2 | SupplierInvoiceBaselineSeeder / SupplierInvoiceScenarioSeeder | supplier_invoices, supplier_payments | mixed | payable = invoice total - payment | no direct stock check | hutang supplier accurate | payable mismatch = 0 | rekap hutang supplier | PENDING SEED TRACE AUDIT |
| Supplier | SUP-011 | annual dense invoice load | Load invoice 1 tahun | make 3 | SeedLevel3Seeder / procurement load seeder TBD | supplier_invoices, payments, receipts | mixed annual | annual payable reconciles | annual stock in | stress report visible | annual totals stable after rerun | supplier report annual | DEFERRED MAKE 3 |
| Supplier | SUP-012 | proof attachment scenario | Bukti pembayaran supplier | make 2 | SupplierInvoiceScenarioSeeder | supplier_payment_proofs, supplier_payments | proof state present | no mismatch | none | proof status visible | proof mismatch = 0 | payment proof detail | PENDING SEED TRACE AUDIT |

---

## 6. Inventory / Costing Scenarios

| Domain | Scenario ID | Scenario Name | Purpose | make level | Seeder class | Tables involved | Expected status | Expected money effect | Expected stock effect | Expected report effect | Invariant checks | Manual review path | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Inventory | INV-001 | stock in from supplier receipt | Stock bertambah dari receipt | make 2 | SupplierInvoiceScenarioSeeder / inventory seeder TBD | inventory_movements, product_inventory, receipts | active | inventory value changes | qty increases | stock report reflects in | stored qty = computed qty | stok dan nilai persediaan | PENDING SEED TRACE AUDIT |
| Inventory | INV-002 | stock out from customer note | Stock keluar dari nota | make 2 | CustomerTransactionBaselineSeeder | inventory_movements, product_inventory, work_item_store_stock_lines | active | COGS/value policy applies | qty decreases | stock out visible | stock out source exists | detail nota + stok | PENDING SEED TRACE AUDIT |
| Inventory | INV-003 | average cost changes after procurement | Avg cost berubah setelah pembelian | make 2 | SupplierInvoiceScenarioSeeder / costing seeder TBD | inventory_movements, product_inventory_costing | active | avg cost recalculated | qty/value updated | inventory value visible | avg_cost >= 0 | stok dan nilai persediaan | PENDING SEED TRACE AUDIT |
| Inventory | INV-004 | inventory value reconciles with qty and avg cost | Nilai inventory valid | make 2 | inventory seeder TBD | product_inventory, product_inventory_costing | active | value = qty * costing policy | qty stable | report value correct | value mismatch = 0 | stok dan nilai persediaan | PENDING SEED TRACE AUDIT |
| Inventory | INV-005 | low stock threshold | Scenario stok rendah | make 2 | Product threshold seeder TBD | products, product_inventory | active | none | qty <= low threshold | low stock visible | threshold active exists | snapshot stok saat ini | PENDING SEED TRACE AUDIT |
| Inventory | INV-006 | critical stock threshold | Scenario stok kritis | make 2 | Product threshold seeder TBD | products, product_inventory | active | none | qty <= critical threshold | critical stock visible | threshold active exists | snapshot stok saat ini | PENDING SEED TRACE AUDIT |
| Inventory | INV-007 | no negative stock unless explicitly allowed | Guard stok negatif | make 2 | audit command / invariant test | product_inventory, inventory_movements | valid | none | no negative unless policy allows | no invalid stock report | negative stock count = 0 unless allowed | audit finance | PENDING SEED TRACE AUDIT |
| Inventory | INV-008 | refund does not incorrectly restore stock unless policy says so | Refund tidak merusak stok | make 2 | CustomerTransactionBaselineSeeder | refunds, inventory_movements | policy-bound | refund money only unless policy says | stock restore policy explicit | refund report consistent | refund stock policy clear | refund detail + stock | PENDING SEED TRACE AUDIT |
| Inventory | INV-009 | void supplier invoice does not create inventory movement | Void tidak membuat stock in | make 2 | SupplierInvoiceVoidedScenarioSeeder | supplier_invoices, inventory_movements | voided | no payable leak | no stock movement | void clean in report | void stock movement count = 0 | supplier void + stock report | PARTIAL-PROVEN |
| Inventory | INV-010 | correction does not corrupt stock movement history | Koreksi tidak korup movement | make 2 | CustomerTransactionBaselineSeeder / correction seeder TBD | note_mutation_events, inventory_movements | corrected | money reconciles | stock history valid | correction visible | source movement exists | mutation timeline + stock | PENDING SEED TRACE AUDIT |

---

## 7. Expense Scenarios

| Domain | Scenario ID | Scenario Name | Purpose | make level | Seeder class | Tables involved | Expected status | Expected money effect | Expected stock effect | Expected report effect | Invariant checks | Manual review path | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Expense | EXP-001 | cash expense | Biaya operasional tunai | make 2 | Expense baseline seeder TBD | expenses, expense_categories | active | cash out | none | expense report visible | expense amount > 0 | rekap biaya operasional | PENDING SEED TRACE AUDIT |
| Expense | EXP-002 | transfer expense | Biaya operasional transfer | make 2 | Expense baseline seeder TBD | expenses, expense_categories | active | cash/bank out | none | payment method visible | amount > 0 | detail biaya operasional | PENDING SEED TRACE AUDIT |
| Expense | EXP-003 | category snapshot preserved | Snapshot kategori biaya | make 2 | Expense baseline seeder TBD | expenses, expense_categories | active | categorized expense | none | category report stable | category exists / snapshot valid | rekap biaya operasional | PENDING SEED TRACE AUDIT |
| Expense | EXP-004 | deleted expense excluded from report | Expense deleted tidak masuk report | make 2 | Expense scenario seeder TBD | expenses | deleted/excluded | excluded from totals | none | report excludes deleted | deleted rows not counted | rekap biaya operasional | PENDING SEED TRACE AUDIT |
| Expense | EXP-005 | 1 month daily expense distribution | Distribusi expense 1 bulan | make 2 | Expense baseline seeder TBD | expenses | active month | monthly cash out | none | monthly report stable | expected count stable | rekap biaya operasional | PARTIAL-PROVEN |
| Expense | EXP-006 | 1 year heavy expense distribution | Distribusi expense 1 tahun | make 3 | SeedLevel3Seeder / expense load seeder TBD | expenses | active annual | annual cash out | none | annual stress report | annual count stable after rerun | annual expense report | DEFERRED MAKE 3 |

---

## 8. Employee Finance Scenarios

| Domain | Scenario ID | Scenario Name | Purpose | make level | Seeder class | Tables involved | Expected status | Expected money effect | Expected stock effect | Expected report effect | Invariant checks | Manual review path | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Employee | EMP-001 | active weekly employee | Karyawan aktif mingguan | make 2 | Employee finance seeder TBD | employees, payroll related tables | active | salary liability/disbursement policy | none | payroll report visible | active employee counted | laporan rekap gaji | PENDING SEED TRACE AUDIT |
| Employee | EMP-002 | active monthly employee | Karyawan aktif bulanan | make 2 | Employee finance seeder TBD | employees, payroll related tables | active | monthly payroll policy | none | payroll report visible | active employee counted | laporan rekap gaji | PENDING SEED TRACE AUDIT |
| Employee | EMP-003 | active daily employee | Karyawan aktif harian | make 2 | Employee finance seeder TBD | employees, payroll related tables | active | daily payroll policy | none | payroll report visible | active employee counted | laporan rekap gaji | PENDING SEED TRACE AUDIT |
| Employee | EMP-004 | manual salary employee | Karyawan gaji manual | make 2 | Employee finance seeder TBD | employees, payroll related tables | active | manual salary amount | none | payroll detail visible | non-negative salary | laporan rekap gaji | PENDING SEED TRACE AUDIT |
| Employee | EMP-005 | inactive employee | Karyawan nonaktif | make 2 | Employee finance seeder TBD | employees | inactive | no active payroll unless policy says | none | inactive excluded from active-only report | inactive not counted as active | employee report | PENDING SEED TRACE AUDIT |
| Employee | EMP-006 | employee debt unpaid | Hutang karyawan belum dibayar | make 2 | Employee finance seeder TBD | employee_debts, employee_debt_payments | unpaid | remaining = total | none | hutang visible | remaining > 0 | rekap hutang karyawan | PENDING SEED TRACE AUDIT |
| Employee | EMP-007 | employee debt partially paid | Hutang karyawan dibayar sebagian | make 2 | Employee finance seeder TBD | employee_debts, employee_debt_payments | partial | 0 < paid < total | none | remaining visible | remaining = total - paid | detail hutang karyawan | PENDING SEED TRACE AUDIT |
| Employee | EMP-008 | employee debt fully paid | Hutang karyawan lunas | make 2 | Employee finance seeder TBD | employee_debts, employee_debt_payments | paid | remaining = 0 | none | paid debt visible/excluded by filter policy | remaining = 0 | detail hutang karyawan | PENDING SEED TRACE AUDIT |
| Employee | EMP-009 | payroll disbursement report | Laporan pencairan gaji | make 2 | Employee finance seeder TBD | payroll related tables | posted | salary cash out | none | payroll disbursement visible | amount non-negative | laporan rekap gaji | PENDING SEED TRACE AUDIT |
| Employee | EMP-010 | debt remaining balance reconciliation | Rekonsiliasi sisa hutang | make 2 | Employee finance seeder TBD | employee_debts, debt payments | valid | remaining accurate | none | debt report accurate | remaining mismatch = 0 | rekap hutang karyawan | PENDING SEED TRACE AUDIT |

---

## 9. Cash / Ledger Scenarios

| Domain | Scenario ID | Scenario Name | Purpose | make level | Seeder class | Tables involved | Expected status | Expected money effect | Expected stock effect | Expected report effect | Invariant checks | Manual review path | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Cash | CASH-001 | customer payment cash-in | Pembayaran pelanggan masuk kas | make 2 | CustomerTransactionBaselineSeeder | customer_payments, customer_payment_allocations | active | cash in | none | ledger cash in visible | cash in = customer payments | ledger kas transaksi | PENDING SEED TRACE AUDIT |
| Cash | CASH-002 | refund cash-out | Refund pelanggan keluar kas | make 2 | CustomerTransactionBaselineSeeder | refunds | active | cash out | policy-dependent | refund cash out visible | refund <= allocated | ledger kas transaksi | PENDING SEED TRACE AUDIT |
| Cash | CASH-003 | operational expense cash-out | Biaya operasional keluar kas | make 2 | Expense baseline seeder TBD | expenses | active | cash out | none | expense cash out visible | expense amount positive | ledger kas transaksi | PENDING SEED TRACE AUDIT |
| Cash | CASH-004 | payroll cash-out | Gaji keluar kas | make 2 | Employee finance seeder TBD | payroll related tables | active | cash out | none | payroll cash out visible | payroll non-negative | ledger kas transaksi | PENDING SEED TRACE AUDIT |
| Cash | CASH-005 | supplier payment cash-out | Pembayaran supplier keluar kas | make 2 | SupplierInvoiceScenarioSeeder | supplier_payments | active | cash out | none | supplier payment cash out visible | payment <= invoice total unless policy says | ledger kas transaksi | PENDING SEED TRACE AUDIT |
| Cash | CASH-006 | correction event audit | Koreksi event kas | make 2 | correction seeder TBD | note_mutation_events, finance event related tables | corrected | correction reconciles | policy-dependent | audit trail visible | no destructive finalized leak | detail event kas | PENDING SEED TRACE AUDIT |
| Cash | CASH-007 | cash ledger balance reconciles | Rekonsiliasi saldo kas | make 2 | audit command / finance invariant test | customer_payments, refunds, supplier_payments, expenses, payroll | valid | net = in - out | none | ledger balance correct | ledger mismatch = 0 | ledger kas transaksi | PENDING SEED TRACE AUDIT |

---

## 10. Current Proof Links

Current known proof from make 2 stabilization:

~~~text
docs/handoff/v2/seedernew/2026-04-26-seedernew-make2-idempotency-proof.md
~~~

Known level 2 count proof:

~~~text
users total: 2
products total: 382
active products: 338
products missing threshold active: 0
suppliers total: 100
employees total: 12

SI-BL invoices: 69
SI-BL versions: 69
SI-BL projections: 69
scenario invoices active: 5
void scenario invoices total: 3
SI-VOID-001 voided: 1
SI-VOID-REUSE-001 voided: 1
SI-VOID-REUSE-001 active: 1

baseline notes: 240
baseline customer payments: 216
baseline payment allocations: 216
baseline refunds: 12

baseline expenses: 120
expense categories: 6

orphan supplier invoice lines: 0
orphan supplier receipt lines: 0
orphan payment allocations: 0
duplicate active supplier invoice normalized no: 0
~~~

---

## 11. Required Next Audit

Before creating audit command or make 3, run local seeder trace audit for exact seeder class mapping.

Minimum questions:

~~~text
- Which seeder owns each scenario?
- Which scenario IDs are explicitly encoded?
- Which scenarios are only implied by aggregate seeded data?
- Which scenarios are make 2 only?
- Which scenarios are make 3 only?
- Which scenarios have manual review route/page?
- Which scenarios have invariant test coverage?
~~~

Recommended next technical step:

~~~text
Audit Laravel command structure before adding:
app/Console/Commands/AuditSeedLevelCommand.php
~~~

Do not implement audit command before checking current command registration style.

---

## 12. Completion Criteria For This Matrix

This matrix becomes complete only when:

~~~text
- every row has a verified seeder class or explicit "audit command only" owner
- every row has query proof or test proof
- audit command can report missing scenarios
- finance invariant tests cover money/stock/debt/cash correctness
- make 2 and make 3 rerun idempotency are proven
- make verify passes after all changes
~~~

