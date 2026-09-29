<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\In\Http\Support;

use App\Adapters\In\Http\Support\ReportArrayPaginator;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class ReportArrayPaginatorTest extends TestCase
{
    public function test_default_page_size_is_ten(): void
    {
        $request = Request::create('/admin/reports/example', 'GET', ['period_mode' => 'monthly']);

        $paginator = (new ReportArrayPaginator())->paginate(
            range(1, 11),
            $request,
            'detail_page',
        );

        self::assertCount(10, $paginator->items());
        self::assertSame(11, $paginator->total());
        self::assertSame(2, $paginator->lastPage());
        self::assertStringContainsString('period_mode=monthly', $paginator->url(2));
    }

    public function test_ten_rows_stay_on_one_page(): void
    {
        $paginator = (new ReportArrayPaginator())->paginate(
            range(1, 10),
            Request::create('/admin/reports/example'),
            'detail_page',
        );

        self::assertCount(10, $paginator->items());
        self::assertSame(1, $paginator->lastPage());
        self::assertFalse($paginator->hasMorePages());
    }

    public function test_detail_tables_have_independent_page_names_and_ten_row_limit(): void
    {
        $tables = [
            [
                'title' => 'Table A',
                'columns' => ['id' => 'ID'],
                'rows' => array_map(static fn (int $id): array => ['id' => $id], range(1, 12)),
            ],
            [
                'title' => 'Table B',
                'columns' => ['id' => 'ID'],
                'rows' => array_map(static fn (int $id): array => ['id' => $id], range(1, 5)),
            ],
        ];
        $request = Request::create('/admin/reports/example', 'GET', [
            'detail_table_1_page' => 2,
            'period_mode' => 'monthly',
        ]);

        $paginated = (new ReportArrayPaginator())->paginateTables(
            $tables,
            $request,
            'detail_table',
        );

        self::assertSame(2, $paginated[0]['rows']->currentPage());
        self::assertCount(2, $paginated[0]['rows']->items());
        self::assertSame('detail_table_1_page', $paginated[0]['rows']->getPageName());
        self::assertSame(1, $paginated[1]['rows']->currentPage());
        self::assertCount(5, $paginated[1]['rows']->items());
        self::assertSame('detail_table_2_page', $paginated[1]['rows']->getPageName());
        self::assertSame(1, $paginated[1]['rows']->lastPage());
    }
}
