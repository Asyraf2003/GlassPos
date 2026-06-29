include mk/push.mk
include mk/hexagonal.mk
include mk/audit.mk

push:
	@$(MAKE) git-push

pushc: push
	@clear

# >>> docs targets >>>
.PHONY: docs-help help

help:
	@echo "Tersedia Perintah Kerja:"
	@echo "  make audit-git                      Menjalankan audit laporan arsitektur dan statistik Git"
	@echo "  make verify-service-product-template  Validasi sintaks rute, controller, views, dan test"
	@echo "  make docs-help                      Melihat dokumentasi bantuan proyek"

docs-help:
	@cat docs/DOCS_HELP.md
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
