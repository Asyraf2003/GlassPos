<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class ReportArrayPaginator
{
    private const DEFAULT_PER_PAGE = 10;

    public function paginate(
        array $rows,
        Request $request,
        string $pageName,
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        $rawPage = $request->query($pageName);
        $page = is_scalar($rawPage) ? max(1, (int) $rawPage) : 1;
        $offset = ($page - 1) * $perPage;

        $paginator = new LengthAwarePaginator(
            array_slice($rows, $offset, $perPage),
            count($rows),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ],
        );

        $query = $request->query();

        return $paginator->appends(is_array($query) ? $query : []);
    }

    public function paginateTables(
        array $tables,
        Request $request,
        string $pageNamePrefix = 'table',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): array {
        $tableNumber = 0;

        foreach ($tables as $key => $table) {
            if (! is_array($table)) {
                continue;
            }

            $tableNumber++;
            $rows = is_array($table['rows'] ?? null) ? $table['rows'] : [];
            $table['rows'] = $this->paginate(
                $rows,
                $request,
                $pageNamePrefix.'_'.$tableNumber.'_page',
                $perPage,
            );
            $tables[$key] = $table;
        }

        return $tables;
    }
}
