<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\Services\ServiceProductTemplatePackageSplitCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class ShowServiceProductTemplatePageController extends Controller
{
    public function __invoke(
        ServiceProductTemplatePackageSplitCalculator $split,
        string $templateId
    ): View|RedirectResponse {
        $row = $this->templateRow($templateId);

        if ($row === null) {
            return redirect()
                ->route('admin.service-product-templates.index')
                ->with('error', 'Paket service tidak ditemukan.');
        }

        return view('admin.service_product_templates.show', [
            'template' => $this->template($row, $split),
        ]);
    }

    private function templateRow(string $templateId): ?object
    {
        return DB::table('service_product_templates')
            ->join('products', 'products.id', '=', 'service_product_templates.product_id')
            ->join('service_catalog_items', 'service_catalog_items.id', '=', 'service_product_templates.service_catalog_item_id')
            ->leftJoin('product_inventory_costing', 'product_inventory_costing.product_id', '=', 'products.id')
            ->where('service_product_templates.id', trim($templateId))
            ->select($this->columns())
            ->first();
    }

    /** @return list<string> */
    private function columns(): array
    {
        return [
            'service_product_templates.id',
            'service_product_templates.product_id',
            'service_product_templates.service_catalog_item_id',
            'service_product_templates.default_service_price_rupiah',
            'service_product_templates.default_package_total_rupiah',
            'service_product_templates.is_active',
            'service_product_templates.created_at',
            'service_product_templates.updated_at',
            'products.kode_barang',
            'products.nama_barang',
            'products.merek',
            'products.ukuran',
            'products.harga_jual',
            'product_inventory_costing.avg_cost_rupiah',
            'product_inventory_costing.inventory_value_rupiah',
            'service_catalog_items.name as service_name',
            'service_catalog_items.default_price_rupiah as current_service_price_rupiah',
            'service_catalog_items.is_active as service_is_active',
        ];
    }

    /** @return array<string, mixed> */
    private function template(object $row, ServiceProductTemplatePackageSplitCalculator $split): array
    {
        $productPrice = (int) $row->harga_jual;
        $templateServicePrice = (int) $row->default_service_price_rupiah;
        $averageCost = $row->avg_cost_rupiah !== null ? (int) $row->avg_cost_rupiah : null;

        return [
            'id' => (string) $row->id,
            'product_id' => (string) $row->product_id,
            'service_catalog_item_id' => (string) $row->service_catalog_item_id,
            'product_name' => (string) $row->nama_barang,
            'product_code' => $row->kode_barang !== null && $row->kode_barang !== '' ? (string) $row->kode_barang : '-',
            'product_brand' => $row->merek !== null && $row->merek !== '' ? (string) $row->merek : '-',
            'product_size' => $row->ukuran !== null && (string) $row->ukuran !== '' ? (string) $row->ukuran : '-',
            'product_price' => $productPrice,
            'average_cost' => $averageCost,
            'inventory_value' => $row->inventory_value_rupiah !== null ? (int) $row->inventory_value_rupiah : null,
            'product_gross_margin' => $averageCost !== null ? $productPrice - $averageCost : null,
            'service_name' => (string) $row->service_name,
            'template_service_price' => $templateServicePrice,
            'current_service_price' => (int) $row->current_service_price_rupiah,
            'service_is_active' => (bool) $row->service_is_active,
            'is_active' => (bool) $row->is_active,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ] + $split->calculate($productPrice, $templateServicePrice, $this->nullableInt($row->default_package_total_rupiah));
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }
}
