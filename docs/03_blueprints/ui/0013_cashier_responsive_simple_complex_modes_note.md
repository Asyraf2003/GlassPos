# Cashier Responsive Modes Note

Status: Deferred / non-blocking
Date: 2026-09-03

## Intent

The cashier UI should eventually have distinct responsive experiences instead of one layout merely shrinking across all screen sizes.

- Mobile / narrow screens: prioritize a simple, fast cashier flow with minimal visible complexity.
- Desktop / wide screens: allow a denser, more complete cashier experience that takes advantage of available space.
- A more complex/advanced cashier UI may coexist with the simple flow later; complexity should be progressive, not forced on the mobile cashier path.
- Prefer responsive behavior derived from viewport/layout capability rather than requiring the cashier to manually choose "HP" or "PC", unless later UX testing proves an explicit mode selector is useful.

## Current priority

This is a future UI follow-up and is not a blocker for the current Cloudflare R2/media migration. Keep the existing functional cashier/direct-upload UI stable while the R2 migration, real-browser proof, legacy media migration, and production rollout are completed.
