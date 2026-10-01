<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Adapters\Out\Procurement\Concerns\ProcurementInvoiceTablePayload;
use App\Application\Procurement\DTO\ProcurementInvoiceTableQuery;
use App\Ports\Out\Procurement\ProcurementInvoiceTableReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseProcurementInvoiceTableReaderAdapter implements ProcurementInvoiceTableReaderPort
{
    use ProcurementInvoiceTablePayload;

    public function __construct(
        private readonly ProcurementInvoiceProjectionTableFilters $filters,
        private readonly ProcurementInvoiceProjectionTableSorting $sorting,
    ) {
    }

    public function search(ProcurementInvoiceTableQuery $query): array
    {
        $builder = DB::table('supplier_invoice_list_projection')
            ->leftJoin('suppliers as supplier_master', 'supplier_master.id', '=', 'supplier_invoice_list_projection.supplier_id')
            ->select([
                'supplier_master.bank_name',
                'supplier_master.bank_account_number',
                'supplier_invoice_id',
                'nomor_faktur',
                'supplier_nama_pt_pengirim_snapshot',
                'shipment_date',
                'due_date',
                'grand_total_rupiah',
                'total_paid_rupiah',
                'outstanding_rupiah',
                'payment_count',
                'receipt_count',
                'total_received_qty',
                'proof_attachment_count',
                'voided_at',
                'supplier_master.nama_pt_pengirim as supplier_nama_pt_pengirim_current',
            ]);

        $builder = $this->filters->apply($builder, $query);
        $builder = $this->sorting->apply($builder, $query);

        $paginator = $builder->paginate($query->perPage(), ['*'], 'page', $query->page());

        return $this->toTablePayload($paginator, $query);
    }
}
