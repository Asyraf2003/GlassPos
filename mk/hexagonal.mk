.PHONY: dev lint fmt test test-compact test-unit test-domain test-feature test-report test-audit test-integration test-money test-stock test-arch audit-hex migrate rollback reset-db coverage ci check verify audit-lines audit-blade audit-contract

dev:
	php artisan serve

lint:
	./vendor/bin/phpstan analyze --memory-limit=-1

fmt:
	./vendor/bin/pint

test:
	php -d memory_limit=-1 vendor/bin/pest

test-compact:
	php -d memory_limit=-1 vendor/bin/pest --compact

test-unit:
	php artisan test tests/Unit

test-domain:
	php artisan test tests/Unit/Core

test-feature:
	php artisan test tests/Feature

test-report:
	php artisan test tests/Feature/Reporting

test-audit:
	php artisan test tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php

test-integration:
	php artisan test tests/Feature

test-money:
	php artisan test tests/Unit/Core/Shared/ValueObjects/MoneyTest.php

test-stock:
	php artisan test tests/Feature/Inventory

test-arch:
	php artisan test tests/Arch

audit-hex:
	php scripts/audit-hex.php

migrate:
	php artisan migrate

rollback:
	php artisan migrate:rollback

reset-db:
	php artisan migrate:fresh

coverage:
	php artisan test --coverage

audit-lines:
	@php scripts/audit-line-count.php

audit-blade:
	@php scripts/audit-blade-no-php.php

audit-contract: audit-lines audit-blade
	@echo "Contract audit passed."

check: audit-hex test

# Gerbang Verifikasi Utama (Test + Lint + Contract Audit)
verify: lint audit-contract test-compact

# Alias untuk CI sesuai DoD 3.3
ci: verify
