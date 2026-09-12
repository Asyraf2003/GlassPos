<?php

declare(strict_types=1);

namespace App\Adapters\Out\ServiceProductTemplate;

use Illuminate\Support\Facades\DB;

trait DatabaseServiceProductTemplateAdminServiceOptions
{
    /** @return list<array{id:string,name:string,price_rupiah:int,label:string}> */
    public function serviceOptions(?string $includeId = null): array
    {
        $trimmedIncludeId = trim((string) $includeId);

        return DB::table('service_catalog_items')
            ->where(function ($query) use ($trimmedIncludeId): void {
                $query->where('is_active', true);

                if ($trimmedIncludeId !== '') {
                    $query->orWhere('id', $trimmedIncludeId);
                }
            })
            ->select(['id', 'name', 'default_price_rupiah', 'is_active'])
            ->orderBy('name')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'price_rupiah' => (int) $row->default_price_rupiah,
                'label' => $this->serviceLabel($row),
            ])
            ->all();
    }

    private function serviceLabel(object $row): string
    {
        return sprintf(
            '%s · Default %s%s',
            (string) $row->name,
            number_format((int) $row->default_price_rupiah, 0, ',', '.'),
            (bool) $row->is_active ? '' : ' · nonaktif',
        );
    }
}
