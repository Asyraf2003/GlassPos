.PHONY: seed-user
seed-user:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateUserSeeder'

.PHONY: user
user: seed-user

.PHONY: seed-create-basic
seed-create-basic:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterBasicSeeder'

.PHONY: product-1
product-1: seed-create-basic

.PHONY: seed-create-week
seed-create-week:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterDenseWeekSeeder'

.PHONY: product-2
product-2: seed-create-week

.PHONY: seed-create-year
seed-create-year:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterDenseYearSeeder'

.PHONY: product-year
product-year: seed-create-year

.PHONY: seed-inventory
seed-inventory:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateInventorySeeder'

.PHONY: inventory
inventory: seed-inventory

.PHONY: seed-create-default
seed-create-default:
	php artisan db:seed --class='Database\Seeders\DatabaseSeeder'

procurement:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateSupplierProcurementSeeder"

supplier-payment:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateSupplierPaymentSeeder"

expense:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateOperationalExpenseSeeder"

.PHONY: seed-admin-cashier-area-access
seed-admin-cashier-area-access:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateAdminCashierAreaAccessSeeder"

.PHONY: admin-cashier-area-access
admin-cashier-area-access: seed-admin-cashier-area-access

.PHONY: seed-employee-debt
seed-employee-debt:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateEmployeeDebtSeeder"

.PHONY: employee-debt
employee-debt: seed-employee-debt

.PHONY: seed-employee-debt-payment
seed-employee-debt-payment:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateEmployeeDebtPaymentSeeder"

.PHONY: employee-debt-payment
employee-debt-payment: seed-employee-debt-payment

.PHONY: seed-employee-debt-adjustment
seed-employee-debt-adjustment:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateEmployeeDebtAdjustmentSeeder"

.PHONY: employee-debt-adjustment
employee-debt-adjustment: seed-employee-debt-adjustment

.PHONY: seed-payroll-disbursement
seed-payroll-disbursement:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreatePayrollDisbursementSeeder"

.PHONY: payroll-disbursement
payroll-disbursement: seed-payroll-disbursement

.PHONY: seed-create-all-v1
seed-create-all-v1: user admin-cashier-area-access product-1 inventory procurement supplier-payment expense employee-debt employee-debt-payment employee-debt-adjustment payroll-disbursement

.PHONY: create-all-v1
create-all-v1: seed-create-all-v1

.PHONY: seed-create-all-v2
seed-create-all-v2: user admin-cashier-area-access product-1 product-2 inventory procurement supplier-payment expense employee-debt employee-debt-payment employee-debt-adjustment payroll-disbursement

.PHONY: create-all-v2
create-all-v2: seed-create-all-v2

.PHONY: seed-create-all-v3
seed-create-all-v3: user admin-cashier-area-access product-1 product-2 product-year inventory procurement supplier-payment expense employee-debt employee-debt-payment employee-debt-adjustment payroll-disbursement

.PHONY: create-all-v3
create-all-v3: seed-create-all-v3

.PHONY: help
help:
	@echo ""
	@echo "HyperPOS create-only seed targets"
	@echo "================================="
	@echo ""
	@echo "Core/master:"
	@echo "  make user                         Create demo users, actor access, admin transaction capability"
	@echo "  make admin-cashier-area-access    Create admin cashier area access state"
	@echo "  make product-1                    Create basic suppliers, products, employees, expense categories"
	@echo "  make product-2                    Add dense week master data"
	@echo "  make product-year                 Add dense year master data"
	@echo ""
	@echo "Operational source data:"
	@echo "  make inventory                    Create inventory movements, inventory, and costing"
	@echo "  make procurement                  Create supplier invoices, lines, receipts, and receipt lines"
	@echo "  make supplier-payment             Create supplier payments and proof attachments"
	@echo "  make expense                      Create operational expenses"
	@echo "  make employee-debt                Create employee debts"
	@echo "  make employee-debt-payment        Create employee debt payment scenarios"
	@echo "  make employee-debt-adjustment     Create employee debt adjustment scenarios"
	@echo "  make payroll-disbursement         Create payroll disbursement scenarios"
	@echo ""
	@echo "Aggregate create-only datasets:"
	@echo "  make create-all-v1                Run create-only dataset v1: basic master + operations"
	@echo "  make create-all-v2                Run create-only dataset v2: v1 + dense week master"
	@echo "  make create-all-v3                Run create-only dataset v3: v2 + dense year master"
	@echo ""
	@echo "Raw seed aggregate aliases:"
	@echo "  make seed-create-all-v1           Internal/raw target used by create-all-v1"
	@echo "  make seed-create-all-v2           Internal/raw target used by create-all-v2"
	@echo "  make seed-create-all-v3           Internal/raw target used by create-all-v3"
	@echo ""
	@echo "Alias explanation:"
	@echo "  make create-all-v3                Recommended human-facing command"
	@echo "  make seed-create-all-v3           Raw dependency target; same seed chain, less friendly name"
	@echo "  create-all-v3 depends on seed-create-all-v3, so both run the same v3 seed chain"
	@echo ""
	@echo "Project utility targets:"
	@echo "  make verify                       Run the project verification target defined by the repo"
	@echo "  make push                         Run the repo push helper target; review git status first"
	@echo ""
	@echo "Notes:"
	@echo "  - Seeders are create-only and idempotent."
	@echo "  - Run from repo root."
	@echo "  - Use create-all-v1/v2/v3 for normal seeding."
	@echo "  - Use seed-create-all-v1/v2/v3 only when debugging Make dependencies."
	@echo "  - Commit/push remains manual unless you intentionally run make push."
	@echo ""
