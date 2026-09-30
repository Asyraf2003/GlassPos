# README Snippets

Copy-paste snippets for the GlassPos portfolio assets.

All paths assume the SVGs are saved in:

```text
.github/assets/readme/portfolio/
```

GitHub sanitizes inline HTML, so these snippets use only `<p>`, `<img>`, `<table>`, `<tr>`, `<td>` and `<details>` with `align`, `width`, `alt` and `src` attributes.

---

## 1. Hero: verification snapshot (full width)

Place near the top of the README.

```html
<p align="center">
  <img
    src=".github/assets/readme/portfolio/06-engineering-proof.svg"
    alt="GlassPos verification snapshot: 1,849 automated tests passing, 14,308 assertions, 0 PHPStan errors, 100% strict_types coverage"
    width="100%"
  />
</p>
```

---

## 2. Repository snapshot (full width)

```html
<p align="center">
  <img
    src=".github/assets/readme/portfolio/10-repository-composition.svg"
    alt="GlassPos repository engineering snapshot"
    width="100%"
  />
</p>
```

---

## 3. Architecture section

Full-width system diagram:

```html
<p align="center">
  <img
    src=".github/assets/readme/portfolio/07-system-architecture.svg"
    alt="GlassPos hexagonal architecture: inbound adapters, application use cases, core domain rules, ports, outbound infrastructure"
    width="100%"
  />
</p>
```

Composition donut beside the LOC donut:

```html
<table>
  <tr>
    <td width="50%" valign="top">
      <img
        src=".github/assets/readme/portfolio/01-architecture-donut.svg"
        alt="Architecture composition: 1,585 tracked PHP files across hexagonal groups"
        width="100%"
      />
    </td>
    <td width="50%" valign="top">
      <img
        src=".github/assets/readme/portfolio/02-loc-donut-3d.svg"
        alt="Tracked repository LOC by area: documentation, tests, application, database, Blade"
        width="100%"
      />
    </td>
  </tr>
</table>
```

---

## 4. Testing section

```html
<p align="center">
  <img
    src=".github/assets/readme/portfolio/03-test-domain-bars.svg"
    alt="Feature test density by business domain, with Note, Reporting and Procurement emphasized"
    width="100%"
  />
</p>
```

---

## 5. Engineering activity: trend and candlestick side by side

```html
<table>
  <tr>
    <td width="50%" valign="top">
      <img
        src=".github/assets/readme/portfolio/04-commit-trend.svg"
        alt="Git commits by month, March to September 2026"
        width="100%"
      />
    </td>
    <td width="50%" valign="top">
      <img
        src=".github/assets/readme/portfolio/05-git-activity-candlestick.svg"
        alt="Candlestick-style view of daily Git commit intensity per month, not financial data"
        width="100%"
      />
    </td>
  </tr>
</table>
```

Stacked version, better for readability of the candlestick table:

```html
<p align="center">
  <img
    src=".github/assets/readme/portfolio/04-commit-trend.svg"
    alt="Git commits by month, March to September 2026"
    width="100%"
  />
</p>

<p align="center">
  <img
    src=".github/assets/readme/portfolio/05-git-activity-candlestick.svg"
    alt="Candlestick-style view of daily Git commit intensity per month, not financial data"
    width="100%"
  />
</p>
```

---

## 6. Business-flow section: lifecycle and integrity

Stacked, full width (recommended, both diagrams contain small text):

```html
<p align="center">
  <img
    src=".github/assets/readme/portfolio/08-transaction-lifecycle.svg"
    alt="Transaction lifecycle: draft, created, payment, paid, then revision, cancellation and refund as separate operations"
    width="100%"
  />
</p>

<p align="center">
  <img
    src=".github/assets/readme/portfolio/09-integrity-flow.svg"
    alt="Transaction integrity boundary: one business event reconnects UI, guards, database, payments, stock, history, audit and reports into an explainable consistent state"
    width="100%"
  />
</p>
```

Side by side (smaller text on narrow screens):

```html
<table>
  <tr>
    <td width="50%" valign="top">
      <img
        src=".github/assets/readme/portfolio/08-transaction-lifecycle.svg"
        alt="Transaction lifecycle diagram"
        width="100%"
      />
    </td>
    <td width="50%" valign="top">
      <img
        src=".github/assets/readme/portfolio/09-integrity-flow.svg"
        alt="Transaction integrity boundary diagram"
        width="100%"
      />
    </td>
  </tr>
</table>
```

---

## 7. Collapsible gallery

Keeps the README short while making every chart available.

```html
<details>
  <summary><strong>Engineering metrics gallery</strong></summary>
  <br />

  <p align="center">
    <img src=".github/assets/readme/portfolio/01-architecture-donut.svg" alt="Architecture composition" width="100%" />
  </p>
  <p align="center">
    <img src=".github/assets/readme/portfolio/02-loc-donut-3d.svg" alt="Tracked repository LOC" width="100%" />
  </p>
  <p align="center">
    <img src=".github/assets/readme/portfolio/03-test-domain-bars.svg" alt="Feature test density" width="100%" />
  </p>
  <p align="center">
    <img src=".github/assets/readme/portfolio/04-commit-trend.svg" alt="Engineering activity by month" width="100%" />
  </p>
  <p align="center">
    <img src=".github/assets/readme/portfolio/05-git-activity-candlestick.svg" alt="Git activity range, not financial data" width="100%" />
  </p>
</details>
```

---

## 8. Markdown-only fallback

If HTML attributes are ever stripped, plain Markdown images still work:

```markdown
![GlassPos verification snapshot](.github/assets/readme/portfolio/06-engineering-proof.svg)
![GlassPos architecture](.github/assets/readme/portfolio/07-system-architecture.svg)
```

---

## Placement suggestion

| README area | Asset |
| --- | --- |
| Top, under the project intro | `06-engineering-proof.svg` |
| Overview / scale | `10-repository-composition.svg` |
| Architecture | `07-system-architecture.svg`, `01-architecture-donut.svg`, `02-loc-donut-3d.svg` |
| Testing | `03-test-domain-bars.svg` |
| Business rules | `08-transaction-lifecycle.svg`, `09-integrity-flow.svg` |
| Development history | `04-commit-trend.svg`, `05-git-activity-candlestick.svg` |

## Notes

- Use `width="100%"` for full-width images. GitHub scales SVGs with the `viewBox`.
- Each SVG includes its own dark card background, so it looks the same in GitHub light and dark themes.
- Regenerate the affected SVG whenever the underlying audit numbers change. The charts are point-in-time.
