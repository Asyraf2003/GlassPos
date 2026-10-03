<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\UseCases;

use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Procurement\Context\SupplierInvoiceChangeContext;
use App\Application\Procurement\Services\CanonicalizeSupplierInvoiceProduct;
use App\Application\ProductCatalog\DTO\ProductIdentityMerge;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\ProductCatalog\ProductIdentityMergePort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Throwable;

/** Explicit adoption of an already transferred duplicate identity, not a new physical merge. */
final class AdoptTransferredProductMergeHandler
{
    public function __construct(
        private readonly ProductIdentityMergePort $merges,
        private readonly TransactionManagerPort $transactions,
        private readonly CanonicalizeSupplierInvoiceProduct $invoices,
        private readonly SupplierInvoiceChangeContext $context,
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
    ) {}

    public function handle(string $id, string $source, string $canonical, string $actor, string $reason, string $priorTransfer, bool $dryRun = false): int
    {
        $merge = new ProductIdentityMerge($id, $source, $canonical, $actor, $reason, $priorTransfer);
        $this->transactions->begin();
        try {
            $created = $this->merges->recordTransferredMerge($merge);
            $this->context->set($actor, null, 'cli_product_identity_merge', $reason, $id);
            $changed = $this->invoices->apply($this->merges->findBySource($source) ?? $merge);
            $this->merges->assertSourceDrained($merge);
            if ($created) {
                $this->audit->write(new AuditEventWrite(
                    $this->uuid->generate(), 'product_catalog', 'product_identity_merge', $id,
                    'product_identity_merge_adopted', $actor, null, $reason, 'cli_product_identity_merge',
                    null, $id, $this->clock->now(), [
                        'source_product_id' => $source, 'canonical_product_id' => $canonical,
                        'prior_stock_transfer_source_id' => $priorTransfer, 'revised_invoice_count' => $changed,
                    ],
                ));
            }
            if ($dryRun) {
                $this->transactions->rollBack();
            } else {
                $this->transactions->commit();
            }
            return $changed;
        } catch (Throwable $exception) {
            $this->transactions->rollBack();
            throw $exception;
        } finally {
            $this->context->clear();
        }
    }
}
