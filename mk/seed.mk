.PHONY: seed-user
seed-user:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateUserSeeder'

.PHONY: user
user: seed-user
	$(MAKE) seed-audit-baseline

.PHONY: seed-audit-baseline
seed-audit-baseline:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateAuditBaselineSeeder'

.PHONY: audit-baseline
audit-baseline: seed-audit-baseline

.PHONY: seed-create-basic
seed-create-basic:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterBasicSeeder'

.PHONY: product-1
product-1: seed-create-basic
	$(MAKE) seed-audit-baseline

.PHONY: seed-create-week
seed-create-week:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterDenseWeekSeeder'

.PHONY: product-2
product-2: seed-create-week
	$(MAKE) seed-audit-baseline

.PHONY: seed-create-year
seed-create-year:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterDenseYearSeeder'

.PHONY: product-year
product-year: seed-create-year
	$(MAKE) seed-audit-baseline

.PHONY: seed-inventory
seed-inventory:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateInventorySeeder'

.PHONY: inventory
inventory: seed-inventory
	$(MAKE) seed-audit-baseline

.PHONY: seed-create-default
seed-create-default:
	php artisan db:seed --class='Database\Seeders\DatabaseSeeder'

.PHONY: seed-procurement
seed-procurement:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateSupplierProcurementSeeder'

.PHONY: procurement
procurement: seed-procurement
	$(MAKE) seed-audit-baseline

.PHONY: seed-supplier-payment
seed-supplier-payment:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateSupplierPaymentSeeder'

.PHONY: supplier-payment
supplier-payment: seed-supplier-payment
	$(MAKE) seed-audit-baseline

.PHONY: seed-expense
seed-expense:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateOperationalExpenseSeeder'

.PHONY: expense
expense: seed-expense
	$(MAKE) seed-audit-baseline

.PHONY: seed-admin-cashier-area-access
seed-admin-cashier-area-access:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateAdminCashierAreaAccessSeeder'

.PHONY: admin-cashier-area-access
admin-cashier-area-access: seed-admin-cashier-area-access
	$(MAKE) seed-audit-baseline

.PHONY: seed-employee-debt
seed-employee-debt:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateEmployeeDebtSeeder'

.PHONY: employee-debt
employee-debt: seed-employee-debt
	$(MAKE) seed-audit-baseline

.PHONY: seed-employee-debt-payment
seed-employee-debt-payment:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateEmployeeDebtPaymentSeeder'

.PHONY: employee-debt-payment
employee-debt-payment: seed-employee-debt-payment
	$(MAKE) seed-audit-baseline

.PHONY: seed-employee-debt-adjustment
seed-employee-debt-adjustment:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateEmployeeDebtAdjustmentSeeder'

.PHONY: employee-debt-adjustment
employee-debt-adjustment: seed-employee-debt-adjustment
	$(MAKE) seed-audit-baseline

.PHONY: seed-payroll-disbursement
seed-payroll-disbursement:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreatePayrollDisbursementSeeder'

.PHONY: seed-transaction-week
seed-transaction-week:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionWeekSeeder'

.PHONY: seed-transaction-month-normal
seed-transaction-month-normal:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthNormalSeeder'

.PHONY: seed-transaction-month-normal-100m
seed-transaction-month-normal-100m:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthNormal100MSeeder'

.PHONY: seed-transaction-month-peak-500m
seed-transaction-month-peak-500m:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthPeak500MSeeder'

.PHONY: seed-transaction-month-stress-8b
seed-transaction-month-stress-8b:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthStress8BSeeder'

.PHONY: seed-transaction-month-stress-10b
seed-transaction-month-stress-10b:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateTransactionMonthStress10BSeeder'

.PHONY: payroll-disbursement
payroll-disbursement: seed-payroll-disbursement
	$(MAKE) seed-audit-baseline

.PHONY: seed-create-all-v1
seed-create-all-v1: seed-user seed-admin-cashier-area-access seed-create-basic seed-inventory seed-procurement seed-supplier-payment seed-expense seed-employee-debt seed-employee-debt-payment seed-employee-debt-adjustment seed-payroll-disbursement

.PHONY: create-all-v1
create-all-v1: seed-create-all-v1
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-create-all-v2
seed-create-all-v2: seed-user seed-admin-cashier-area-access seed-create-basic seed-create-week seed-inventory seed-procurement seed-supplier-payment seed-expense seed-employee-debt seed-employee-debt-payment seed-employee-debt-adjustment seed-payroll-disbursement

.PHONY: create-all-v2
create-all-v2: seed-create-all-v2
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-create-all-v3
seed-create-all-v3: seed-user seed-admin-cashier-area-access seed-create-basic seed-create-week seed-create-year seed-inventory seed-procurement seed-supplier-payment seed-expense seed-employee-debt seed-employee-debt-payment seed-employee-debt-adjustment seed-payroll-disbursement seed-transaction-week seed-transaction-month-normal

.PHONY: create-all-v3
create-all-v3: seed-create-all-v3
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-create-all-month-normal-100m
seed-create-all-month-normal-100m: seed-create-all-v3 seed-transaction-month-normal-100m

.PHONY: create-all-month-normal-100m
create-all-month-normal-100m: seed-create-all-month-normal-100m
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-create-all-month-peak-500m
seed-create-all-month-peak-500m: seed-create-all-v3 seed-transaction-month-peak-500m

.PHONY: create-all-month-peak-500m
create-all-month-peak-500m: seed-create-all-month-peak-500m
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-create-all-month-stress-8b
seed-create-all-month-stress-8b: seed-create-all-v3 seed-transaction-month-stress-8b

.PHONY: create-all-month-stress-8b
create-all-month-stress-8b: seed-create-all-month-stress-8b
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-create-all-month-stress-10b
seed-create-all-month-stress-10b: seed-create-all-v3 seed-transaction-month-stress-10b

.PHONY: create-all-month-stress-10b
create-all-month-stress-10b: seed-create-all-month-stress-10b
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-domain-user
seed-domain-user: user

.PHONY: seed-domain-product-basic
seed-domain-product-basic: product-1

.PHONY: seed-domain-product-week
seed-domain-product-week: product-2

.PHONY: seed-domain-product-year
seed-domain-product-year: product-year

.PHONY: seed-domain-inventory
seed-domain-inventory: inventory

.PHONY: seed-domain-procurement
seed-domain-procurement: procurement

.PHONY: seed-domain-supplier-payment
seed-domain-supplier-payment: supplier-payment

.PHONY: seed-domain-expense
seed-domain-expense: expense

.PHONY: seed-domain-employee-debt
seed-domain-employee-debt: employee-debt

.PHONY: seed-domain-employee-debt-payment
seed-domain-employee-debt-payment: employee-debt-payment

.PHONY: seed-domain-employee-debt-adjustment
seed-domain-employee-debt-adjustment: employee-debt-adjustment

.PHONY: seed-domain-payroll
seed-domain-payroll: payroll-disbursement

.PHONY: seed-weekly
seed-weekly:
	$(MAKE) seed-user
	$(MAKE) seed-admin-cashier-area-access
	$(MAKE) seed-create-basic
	$(MAKE) seed-create-week
	$(MAKE) seed-inventory
	$(MAKE) seed-procurement
	$(MAKE) seed-supplier-payment
	$(MAKE) seed-expense
	$(MAKE) seed-employee-debt
	$(MAKE) seed-employee-debt-payment
	$(MAKE) seed-employee-debt-adjustment
	$(MAKE) seed-payroll-disbursement
	$(MAKE) seed-transaction-week
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-monthly
seed-monthly:
	$(MAKE) seed-user
	$(MAKE) seed-admin-cashier-area-access
	$(MAKE) seed-create-basic
	$(MAKE) seed-create-week
	$(MAKE) seed-create-year
	$(MAKE) seed-inventory
	$(MAKE) seed-procurement
	$(MAKE) seed-supplier-payment
	$(MAKE) seed-expense
	$(MAKE) seed-employee-debt
	$(MAKE) seed-employee-debt-payment
	$(MAKE) seed-employee-debt-adjustment
	$(MAKE) seed-payroll-disbursement
	$(MAKE) seed-transaction-week
	$(MAKE) seed-transaction-month-normal
	$(MAKE) seed-audit-baseline
	php artisan projection:rebuild-indexes all

.PHONY: seed-yearly
seed-yearly: seed-monthly

.PHONY: seed-load-real
seed-load-real: create-all-month-normal-100m

.PHONY: seed-load-peak
seed-load-peak: create-all-month-peak-500m

.PHONY: seed-load-stress
seed-load-stress: create-all-month-stress-8b

.PHONY: seed-load-10x
seed-load-10x: create-all-month-stress-10b

.PHONY: seed-load
seed-load: seed-load-peak

.PHONY: seed-max
seed-max: seed-load-10x

.PHONY: seed-help
seed-help:
	@echo ""
	@echo "Simple seed aliases"
	@echo "==================="
	@echo ""
	@echo "Per domain:"
	@echo "  make seed-domain-user"
	@echo "  make seed-domain-product-basic"
	@echo "  make seed-domain-product-week"
	@echo "  make seed-domain-product-year"
	@echo "  make seed-domain-inventory"
	@echo "  make seed-domain-procurement"
	@echo "  make seed-domain-supplier-payment"
	@echo "  make seed-domain-expense"
	@echo "  make seed-domain-employee-debt"
	@echo "  make seed-domain-employee-debt-payment"
	@echo "  make seed-domain-employee-debt-adjustment"
	@echo "  make seed-domain-payroll"
	@echo ""
	@echo "Sequential full flows:"
	@echo "  make seed-weekly       Weekly create flow, audit baseline, projection rebuild"
	@echo "  make seed-monthly      Monthly create flow, audit baseline, projection rebuild"
	@echo "  make seed-yearly       Alias to monthly flow; year-dense master data is included"
	@echo ""
	@echo "Load profiles:"
	@echo "  make seed-load-real    Realistic normal load profile"
	@echo "  make seed-load-peak    Peak load profile"
	@echo "  make seed-load-stress  Highest implemented stress profile"
	@echo "  make seed-load-10x     10B upper ceiling load profile"
	@echo "  make seed-load         Default load alias, currently peak"
	@echo "  make seed-max          Alias to 10B upper ceiling load profile"
	@echo ""
	@echo "All aliases run existing create-only seed paths; transaction seeders use the application create handler."
	@echo "Run from repo root."

.PHONY: help
help:
	@echo ""
	@echo "HyperPOS create-only seed targets"
	@echo "================================="
	@echo ""
	@echo "Core/master:"
	@echo "  make user                         Create demo users, actor access, admin transaction capability, then audit baseline"
	@echo "  make admin-cashier-area-access    Create admin cashier area access state, then audit baseline"
	@echo "  make product-1                    Create basic suppliers, products, employees, expense categories, then audit baseline"
	@echo "  make product-2                    Add dense week master data, then audit baseline"
	@echo "  make product-year                 Add dense year master data, then audit baseline"
	@echo ""
	@echo "Operational source data:"
	@echo "  make inventory                    Create inventory movements, inventory, costing, then audit baseline"
	@echo "  make procurement                  Create supplier invoices, lines, receipts, receipt lines, then audit baseline"
	@echo "  make supplier-payment             Create supplier payments and proof attachments, then audit baseline"
	@echo "  make expense                      Create operational expenses, then audit baseline"
	@echo "  make employee-debt                Create employee debts, then audit baseline"
	@echo "  make employee-debt-payment        Create employee debt payment scenarios, then audit baseline"
	@echo "  make employee-debt-adjustment     Create employee debt adjustment scenarios, then audit baseline"
	@echo "  make payroll-disbursement         Create payroll disbursement scenarios, then audit baseline"
	@echo "  make seed-transaction-week        Source-only transaction notes weekly seed"
	@echo "  make seed-transaction-month-normal Source-only transaction notes monthly normal seed"
	@echo "  make seed-transaction-month-normal-100m Source-only transaction notes monthly normal 100M seed"
	@echo "  make seed-transaction-month-peak-500m Source-only transaction notes monthly peak 500M seed"
	@echo "  make seed-transaction-month-stress-8b Source-only transaction notes monthly stress 8B seed"
	@echo ""
	@echo "Audit baseline:"
	@echo "  make audit-baseline               Rebuild/create deterministic audit_events, snapshots, employee_versions, supplier_invoice_versions for existing seed rows"
	@echo "  make seed-audit-baseline          Raw audit baseline target used by human-facing targets"
	@echo ""
	@echo "Aggregate create-only datasets:"
	@echo "  make create-all-v1                Run source seed dataset v1, then audit baseline and rebuild projections once"
	@echo "  make create-all-v2                Run source seed dataset v2, then audit baseline and rebuild projections once"
	@echo "  make create-all-v3                Run source seed dataset v3, then audit baseline and rebuild projections once"
	@echo "  make create-all-month-normal-100m Run dataset v3 plus monthly normal 100M, then audit baseline and rebuild projections once"
	@echo "  make create-all-month-peak-500m Run dataset v3 plus monthly peak 500M, then audit baseline and rebuild projections once"
	@echo "  make create-all-month-stress-8b Run dataset v3 plus monthly stress 8B, then audit baseline and rebuild projections once"
	@echo "  make create-all-month-stress-10b Run dataset v3 plus monthly stress 10B, then audit baseline and rebuild projections once"
	@echo "  make seed-help                    Show the simple seed aliases for manual QA/load testing"
	@echo "  make seed-weekly                  Sequential weekly create flow"
	@echo "  make seed-monthly                 Sequential monthly create flow"
	@echo "  make seed-load-real               Realistic normal load alias"
	@echo "  make seed-load-peak               Peak load alias"
	@echo "  make seed-load-stress             Highest implemented stress alias"
	@echo "  make seed-load-10x                10B upper ceiling load alias"
	@echo ""
	@echo "Raw source-only targets:"
	@echo "  make seed-create-all-v1           Source-only aggregate v1; does not run audit baseline"
	@echo "  make seed-create-all-v2           Source-only aggregate v2; does not run audit baseline"
	@echo "  make seed-create-all-v3           Source-only aggregate v3; does not run audit baseline"
	@echo "  make seed-procurement             Source-only procurement seed; use make procurement for natural audited flow"
	@echo "  make seed-supplier-payment        Source-only supplier payment seed; use make supplier-payment for natural audited flow"
	@echo "  make seed-expense                 Source-only expense seed; use make expense for natural audited flow"
	@echo ""
	@echo "Alias explanation:"
	@echo "  make create-all-v3                Recommended human-facing command: source seed + audit baseline + projection rebuild"
	@echo "  make seed-create-all-v3           Raw dependency/debug target: source seed only"
	@echo "  Human-facing targets run audit baseline and projection rebuild automatically; raw seed-* targets are kept for debugging"
	@echo ""
	@echo "Project utility targets:"
	@echo "  make verify                       Run the project verification target defined by the repo"
	@echo "  make push                         Run the repo push helper target; review git status first"
	@echo ""
	@echo "Notes:"
	@echo "  - Seeders are create-only and idempotent."
	@echo "  - Audit baseline is deterministic and idempotent."
	@echo "  - Run from repo root."
	@echo "  - Use create-all-v1/v2/v3 for normal audited seeding."
	@echo "  - Use seed-create-all-v1/v2/v3 only when debugging source seed dependencies."
	@echo "  - Commit/push remains manual unless you intentionally run make push."
	@echo ""
