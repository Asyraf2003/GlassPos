# Asset Manifest

Target directory: `.github/assets/readme/portfolio/`

All SVGs are standalone: no JavaScript, no external CSS, no remote images or fonts, no network requests. Font stack is `Inter, 'Segoe UI', Arial, sans-serif` (fallbacks only, nothing embedded). All use a responsive `viewBox`.

Data snapshot: commit activity in `daily-commits.csv` runs from 2026-03-09 to 2026-09-30.

| # | Filename | Visualization type | Source data | Factual / conceptual | Intended README section | Point-in-time status | SVG viewBox | Recommended alt text |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 01 | `01-architecture-donut.svg` | Full-color donut chart with count and share legend | `architecture.csv` (Application 663, Adapters Out 365, Adapters In 309, Ports 153, Core 95; total 1,585) | Factual | Architecture | Point-in-time audit; shares cover only these five groups, not every PHP file | `0 0 1200 640` | Architecture composition: 1,585 tracked PHP files across Application, Adapters Out, Adapters In, Ports and Core. |
| 02 | `02-loc-donut-3d.svg` | Pseudo-3D extruded donut with legend | `loc.csv` (Documentation 139,981; Tests 97,481; Application 82,449; Database 16,000; Blade 15,176; total 351,087) | Factual (total is the sum of the five areas) | Architecture / repository scale | Point-in-time audit; tracked LOC, not code coverage | `0 0 1200 680` | Tracked repository LOC by area: documentation, tests, application, database and Blade, 351,087 lines represented. |
| 03 | `03-test-domain-bars.svg` | Horizontal bar chart, top 12 domains, top 3 emphasized | `test-domains.csv` (top 12 of 23 domains: 478 of 508 listed files) | Factual | Testing | Point-in-time audit; not a total test count (repository tracks 618 test files) | `0 0 1200 830` | Feature test density by business domain, led by Note with 190 test files, then Reporting and Procurement. |
| 04 | `04-commit-trend.svg` | Line chart with subtle area fill and exact point labels | `monthly-commits.csv` (Mar 397, Apr 1,096, May 947, Jun 1,057, Jul 66, Sep 690) plus Aug = 0 confirmed by `daily-commits.csv` having no August days | Factual | Development history | Point-in-time; September counted through Sep 30 | `0 0 1200 660` | Git commits by month from March to September 2026, with none recorded in August. |
| 05 | `05-git-activity-candlestick.svg` | Candlestick-style range chart with O/H/L/C table and key | `daily-commits.csv`, aggregated per month (open = first active day, close = last active day, high = busiest day, low = quietest active day) | Factual, derived from daily counts; explicitly not financial data | Development history | Point-in-time; Aug shown as no-activity state | `0 0 1200 820` | Candlestick-style view of daily Git commit intensity per month, not financial data. |
| 06 | `06-engineering-proof.svg` | KPI verification infographic with PASS states | `verification.csv` (Pest tests 1,849; assertions 14,308; PHPStan errors 0; strict_types 1,621/1,621; three guardrail audits PASS) | Factual | Top of README | Point-in-time; latest full local verification run (no date in the source data) | `0 0 1200 700` | GlassPos verification snapshot: 1,849 tests passing, 14,308 assertions, 0 PHPStan errors, 100% strict_types coverage. |
| 07 | `07-system-architecture.svg` | Layered architecture diagram (Ports and Adapters) | Flow specified in the brief; file counts per group from `architecture.csv` | Conceptual structure with factual file-count tags | Architecture | Not time-bound; file counts are point-in-time. The "MySQL / Persistence" label comes from the brief and is not confirmed by the data files | `0 0 1200 960` | GlassPos hexagonal architecture from cashier or admin through inbound adapters, use cases, core and ports, to infrastructure and owner-facing reports. |
| 08 | `08-transaction-lifecycle.svg` | State-flow diagram with branching paths and effects row | Lifecycle specified in the brief | Conceptual | Business rules | Not time-bound | `0 0 1200 900` | Transaction lifecycle: draft, created, payment, paid, with revision, cancellation and refund as separate operations affecting money, stock, history and reports. |
| 09 | `09-integrity-flow.svg` | Radial hub-and-spoke boundary diagram | Boundaries specified in the brief | Conceptual | Business rules / integrity | Not time-bound | `0 0 1200 940` | Transaction integrity boundary: one business event reconnects UI, guards, database, allocation, stock, history, audit and reports into an explainable consistent state. |
| 10 | `10-repository-composition.svg` | Dashboard: KPI cards, raw-count bars, strict_types ring, signals table, daily commit sparkline | `repository.csv`, `engineering-signals.csv`, `daily-commits.csv` | Factual | Overview / repository scale | Point-in-time audit; bars compare raw counts only, no cross-category percentages | `0 0 1200 1040` | Repository engineering snapshot: 9,546 tracked files, 858 directories, 4,253 commits over 127 unique days. |

## Data integrity notes

- Source numbers were read from the uploaded CSV files and asserted in the generator before writing.
- The 127 unique commit days and 4,253 commits match both the daily sums and the monthly totals.
- Percentages appear only where the parts form a defined whole: assets 01 and 02 (shares of the represented total) and the strict_types ring in asset 10 (1,621 of 1,621).
- Asset 10 does not compute percentages between unrelated categories such as tests versus migrations.
- Cancellation and refund are shown as separate operations in asset 08.
- No private information (names, emails, customer data, screenshots) is embedded in any asset.

## Regeneration

These charts are point-in-time. If the audit numbers change, regenerate the affected SVGs from fresh data files rather than editing values by hand.
