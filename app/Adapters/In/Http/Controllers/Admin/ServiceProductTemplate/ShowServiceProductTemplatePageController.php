<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Application\ServiceProductTemplate\Services\ServiceProductTemplateShowTemplateMapper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class ShowServiceProductTemplatePageController extends Controller
{
    public function __invoke(
        ServiceProductTemplateShowTemplateMapper $mapper,
        string $templateId
    ): View|RedirectResponse {
        $row = $this->templateRow($templateId);

        if ($row === null) {
            return redirect()
                ->route('admin.service-product-templates.index')
                ->with('error', 'Template jasa + produk tidak ditemukan.');
        }

        return view('admin.service_product_templates.show', [
            'template' => $mapper->map($row),
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
}
