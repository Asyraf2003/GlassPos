<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ViewDateFormatter;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

final class ViewDateFormatterTest extends TestCase
{
    public function test_it_displays_date_with_indonesian_month_name(): void
    {
        self::assertSame('01 Juni 2026', ViewDateFormatter::display('2026-06-01'));
    }

    public function test_it_displays_business_timezone_timestamp_without_a_second_shift(): void
    {
        self::assertSame('29 Juni 2026 10:07', ViewDateFormatter::display('2026-06-29 10:07:45', true));
    }

    public function test_it_converts_an_explicit_utc_datetime_to_business_timezone(): void
    {
        $utc = Carbon::create(2026, 6, 29, 2, 7, 45, 'UTC');

        self::assertSame('29 Juni 2026 10:07', ViewDateFormatter::display($utc, true));
    }

    public function test_it_converts_legacy_slash_date_text(): void
    {
        self::assertSame('01 Juni 2026', ViewDateFormatter::display('01/06/2026'));
        self::assertSame('01 Juni 2026 14:30', ViewDateFormatter::display('01/06/2026 14:30', true));
    }

    public function test_it_does_not_shift_date_only_business_values(): void
    {
        self::assertSame('29 Juni 2026', ViewDateFormatter::display('2026-06-29'));
    }

    public function test_it_displays_range_with_indonesian_month_names(): void
    {
        self::assertSame(
            '01 Juni 2026 s/d 03 Juni 2026',
            ViewDateFormatter::range('2026-06-01', '2026-06-03'),
        );
    }

    public function test_it_returns_dash_for_empty_values(): void
    {
        self::assertSame('-', ViewDateFormatter::display(null));
        self::assertSame('-', ViewDateFormatter::display(''));
    }

    public function test_it_returns_original_text_for_unparseable_values(): void
    {
        self::assertSame('periode berjalan', ViewDateFormatter::display('periode berjalan'));
    }

    public function test_it_accepts_carbon_instances(): void
    {
        self::assertSame('01 Juni 2026', ViewDateFormatter::display(Carbon::parse('2026-06-01')));
    }
}
