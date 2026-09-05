# ADR-0043: Single Business Timezone Authority

Status: Accepted by owner
Date: 2026-09-05

## Context

GlassPos is operated in WITA. The repository previously split temporal authority across:

- Laravel/PHP application timezone: UTC;
- owner-facing display timezone: Asia/Makassar;
- MySQL session timezone: inherited from the database/server runtime.

That split allowed the same action to be represented by different local clock values and, more seriously, allowed `date('Y-m-d')` / business-"today" logic to remain on the previous UTC date until 08:00 WITA.

## Decision

`APP_TIMEZONE` is the single configurable timezone authority.

Default:

```dotenv
APP_TIMEZONE=Asia/Makassar
```

It governs:

- Laravel `app.timezone`;
- PHP default timezone and therefore `date()`;
- Carbon / `now()`;
- `ClockPort` system time;
- business-day defaults and "today" calculations;
- owner-facing timestamp rendering;
- MySQL/MariaDB session timezone through an offset derived from the same IANA timezone.

`app.display_timezone` remains only as a compatibility alias and is derived from the same `APP_TIMEZONE`. `APP_DISPLAY_TIMEZONE` is no longer a supported independent knob.

## Database Session

For MySQL and MariaDB, `config/database.php` derives the current numeric offset from `APP_TIMEZONE` and passes that value through the connection `timezone` setting.

For the accepted WITA timezone this is:

```text
Asia/Makassar -> +08:00
```

Using a numeric offset avoids depending on MySQL timezone tables being installed on shared hosting.

## Legacy Production Data

Owner decision: do not rewrite historical production rows as part of this change.

Consequences:

- no bulk timestamp migration;
- no blind `+8 hour` or `-8 hour` repair;
- date-only business fields are never shifted;
- existing legacy `DATETIME` values that were previously UTC-like may remain historically offset in display;
- existing `TIMESTAMP` values may be rendered by MySQL according to the new session timezone;
- all new writes after this configuration is deployed use the unified WITA runtime contract.

This intentionally supersedes the previous operational approach of storing/interpreting new application timestamps under UTC while converting only the UI.

## Invariants

For a new runtime after this ADR:

```text
APP_TIMEZONE       = Asia/Makassar
Laravel/PHP        = Asia/Makassar
Carbon / now()     = Asia/Makassar
ClockPort          = Asia/Makassar
MySQL session      = +08:00
UI timestamp       = Asia/Makassar
business today     = WITA calendar date
```

There must not be separate environment variables for application, display, and database business timezones.

## Date-only Fields

Calendar/business dates remain dates, not instants. Fields such as transaction date, shipment date, due date, expense date, payment date, and refund date must not be timezone-shifted as migration data.

## Deployment

No schema migration is required.

Deployment must clear/rebuild Laravel configuration cache so `APP_TIMEZONE` and the derived database-session offset are loaded by new PHP/database connections.

Historical rows are left untouched by explicit owner decision.

## Proof Contract

Tests must prove at least:

- Laravel and PHP default timezone are Asia/Makassar;
- `ClockPort` and Carbon use Asia/Makassar;
- the UTC instant `2026-09-05 16:30:00Z` belongs to business date `2026-09-06`;
- owner-facing new timestamp strings are not shifted a second time;
- MySQL and MariaDB connection configuration receives `+08:00` from the same `APP_TIMEZONE`;
- read-only legacy diagnostics do not mutate or guess historical rows.
