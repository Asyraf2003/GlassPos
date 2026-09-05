# 0016 - Admin Product Search Relevance Hardening

## Status

Active implementation contract for the admin Product interactive table search.

## Target

Keep the existing two-character, server-side Product search fast and predictable as the catalog grows beyond hundreds of rows, without introducing a search engine or moving filtering into the browser.

## Current State

- Product index is a thin page shell backed by `GET /admin/products/table`.
- Search/filter/sort/pagination are server-side and page size remains 10.
- Search starts at two characters.
- Existing search matched code, name, and brand but default ordering remained alphabetical.
- The browser used a stale-response counter but did not cancel obsolete requests, and reducing an active query back below two characters could leave an old search in flight.

## Constraints

- Preserve the existing ProductCatalog read-side boundary: Request -> DTO -> Use Case -> Port -> DB adapter.
- Keep inventory quantity sourced from `product_inventory` projection.
- Keep explicit column sorting authoritative when the user chooses a sortable header.
- Do not add Elasticsearch/Meilisearch/Scout/full-text infrastructure for a catalog in the current scale class.
- Keep two characters as the minimum live-search threshold.

## Scope In

- Relevance ordering for the default search mode.
- Exact/prefix/contains ranking across product code, product name, and brand.
- Explicit column sort overriding relevance.
- Browser request cancellation for superseded searches.
- Correct reset behavior when input falls below two characters.
- Shorter live-search debounce while retaining server-side pagination.
- Regression tests for relevance and explicit-sort override.

## Scope Out

- Product CRUD mutations.
- Inventory source-of-truth changes.
- New database schema/indexes.
- Dedicated search services.
- Fuzzy typo correction or phonetic search.
- Changes to page size.

## Search Contract

Default search relevance is:

1. exact product code;
2. product-code prefix;
3. product-code contains;
4. exact product name;
5. product-name prefix;
6. exact brand;
7. brand prefix;
8. product-name contains;
9. brand contains;
10. product name and ID as deterministic fallbacks.

When a user explicitly sorts a column, that requested server-side sort replaces relevance ordering.

## Browser Contract

- Two or more characters trigger relevance search after a short debounce.
- Zero or one character returns to the normal product list; one character may remain visible in the input as a typing hint but is not sent as a search query.
- Every new input state aborts an obsolete in-flight request and retains a stale-response guard.
- Relevance mode is represented by an omitted `sort_by` query parameter; explicit table sorting remains URL-addressable.

## Risk Boundary

The current SQL search still uses contains matching. At hundreds or low-thousands of products this is intentionally simpler than adding search infrastructure. Revisit database-specific full-text/trigram strategy only after measured production evidence shows the query is a real bottleneck.

## Required Proof

- `ProductTableDataQueryFeatureTest` proves default relevance and explicit-sort override.
- ProductCatalog feature tests remain green.
- JavaScript syntax check passes for `admin-products-table.js`.
- Manual browser proof covers rapid typing, backspace from 2+ to 1 character, URL state, pagination, and explicit sort after search.
