include mk/push.mk
include mk/hexagonal.mk
include mk/audit.mk

APP_NAME ?= glasspos-cpanel
APP_DIR_NAME ?= glasspos
PUBLIC_DIR_NAME ?= public_html
DEPLOY_DIR ?= deploy-package
SITE_URL ?= https://arbiconbengkel.my.id
ENV_FILE ?= .env.production

.PHONY: deploy smoke-r2-supplier-payment-proof
deploy:
	@APP_NAME="$(APP_NAME)" \
	APP_DIR_NAME="$(APP_DIR_NAME)" \
	PUBLIC_DIR_NAME="$(PUBLIC_DIR_NAME)" \
	DEPLOY_DIR="$(DEPLOY_DIR)" \
	SITE_URL="$(SITE_URL)" \
	ENV_FILE="$(ENV_FILE)" \
	bash scripts/build-cpanel-package.sh

smoke-r2-supplier-payment-proof:
	@test "$$RUN_REAL_R2_SUPPLIER_PROOF_SMOKE" = "1" || \
		(echo "ERROR: set RUN_REAL_R2_SUPPLIER_PROOF_SMOKE=1 for this real-R2 gate." >&2; exit 2)
	php artisan test tests/Smoke/Procurement/RealR2SupplierPaymentProofDirectUploadSmokeTest.php

push:
	@$(MAKE) git-push

pushc: push
	@clear

# >>> docs targets >>>
.PHONY: docs-help help

help:
	@echo "HyperPOS available commands:"
	@echo ""
	@echo "  Deployment:"
	@echo "    make deploy                         Build transferable cPanel package using local .env.production"
	@echo ""
	@echo "  Core verification:"
	@echo "    make verify                         Run lint, contract audits, and full test suite"
	@echo "    make ci                             Alias for make verify"
	@echo "    make test                           Run the full Pest test suite"
	@echo "    make test-unit                      Run unit tests only"
	@echo "    make test-feature                   Run feature tests only"
	@echo "    make test-report                    Run reporting feature tests"
	@echo "    make test-stock                     Run inventory feature tests"
	@echo "    make test-arch                      Run architecture dependency tests"
	@echo ""
	@echo "  Architecture and repository audits:"
	@echo "    make audit-git                      Generate Git/repository density and architecture statistics"
	@echo "    make audit-hex                      Check hexagonal architecture boundaries"
	@echo "    make audit-lines                    Check line-count guardrails"
	@echo "    make audit-blade                    Check Blade PHP boundary rules"
	@echo "    make audit-contract                 Run line-count and Blade contract audits"
	@echo ""
	@echo "  Database:"
	@echo "    make migrate                        Run Laravel migrations"
	@echo "    make rollback                       Roll back the latest migration batch"
	@echo "    make reset-db                       Rebuild local database from migrations"
	@echo ""
	@echo "  Seed datasets:"
	@echo "    make seed-help                      Show simple seed aliases for QA/load data"
	@echo "    make seed-help-full                 Show full create-only seed target list"
	@echo "    make create-all-v3                  Create recommended audited local dataset"
	@echo "    make seed-load-real                 Create realistic normal load dataset"
	@echo "    make seed-load-peak                 Create peak load dataset"
	@echo "    make seed-load-stress               Create stress load dataset"
	@echo ""
	@echo "  Documentation:"
	@echo "    make docs-help                      Show documentation entrypoint"
	@echo ""
	@echo "  Specialized verification:"
	@echo "    make smoke-r2-supplier-payment-proof Explicit opt-in prepare/PUT/finalize against configured private R2"
	@echo "    make verify-service-product-template Validate service catalog/template slice"
	@echo ""
	@echo "  Git:"
	@echo "    make push                           Run project git push wrapper"
	@echo "    make pushc                          Run push wrapper and clear terminal"

docs-help:
	@cat docs/0001_docs_help.md
# <<< docs targets <<<


include mk/seed.mk

.PHONY: verify-service-product-template
verify-service-product-template:
	php -l routes/web/admin_service_catalog.php
	php -l routes/web/admin_service_product_templates.php
	php -l app/Application/ServiceCatalog/Services/ServiceCatalogAdminPageData.php
	php -l app/Application/ServiceProductTemplate/Services/ServiceProductTemplateAdminPageData.php
	@for file in app/Adapters/In/Http/Controllers/Admin/ServiceCatalog/*.php; do php -l "$$file"; done
	@for file in app/Adapters/In/Http/Controllers/Admin/ServiceProductTemplate/*.php; do php -l "$$file"; done
	php -l tests/Feature/ServiceCatalog/AdminServiceCatalogManagementFeatureTest.php
	php -l tests/Feature/ServiceProductTemplate/AdminServiceProductTemplateNavigationFeatureTest.php
	php -l tests/Feature/ServiceProductTemplate/AdminServiceProductTemplateManagementFeatureTest.php
	php artisan route:list | rg "admin.services|admin.service-product-templates|cashier.notes.products.lookup|cashier.notes.services"
	php artisan view:clear
	php artisan test \
		tests/Feature/ServiceCatalog/AdminServiceCatalogManagementFeatureTest.php \
		tests/Feature/ServiceProductTemplate/AdminServiceProductTemplateNavigationFeatureTest.php \
		tests/Feature/ServiceProductTemplate/AdminServiceProductTemplateManagementFeatureTest.php \
		tests/Feature/Database/ServiceProductTemplateFoundationMigrationTest.php \
		tests/Feature/ServiceProductTemplate/ServiceProductTemplateLookupReaderFeatureTest.php \
		tests/Feature/Note/CashierProductLookupServiceProductTemplateFeatureTest.php \
		tests/Feature/Note/CashierWorkspaceServiceProductTemplateAutofillContractFeatureTest.php \
		tests/Feature/Note/CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest.php \
		tests/Feature/Note/ProductLookupPerformanceFeatureTest.php \
		tests/Feature/Note/CreateTransactionWorkspaceTemplateContractFeatureTest.php \
		tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php \
		tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php \
		tests/Feature/Note/ServiceCatalogEndpointFeatureTest.php
