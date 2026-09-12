// Run from the repository root: node scripts/test-admin-entity-pickers.mjs
// Requires Chromium; fixtures load production scripts and stub only lookup responses.
import assert from "node:assert/strict";
import { execFileSync } from "node:child_process";
import { mkdtempSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

const scenarios = [
  "admin-package-picker.html",
  "admin-package-table.html",
  "cashier-product-display.html",
  "admin-employee-debt-picker.html",
  "admin-payroll-picker.html",
  "admin-expense-picker.html",
  ...["create", "edit"].flatMap((mode) =>
    ["draft", "server", "old"].map((hydration) =>
      `admin-procurement-picker.html?mode=${mode}&hydration=${hydration}`)),
];
const profile = mkdtempSync(join(tmpdir(), "glasspos-picker-browser-"));
try {
  for (const scenario of scenarios) {
    const url = new URL(`../tests/Browser/${scenario}`, import.meta.url);
    const html = execFileSync(process.env.CHROMIUM_BIN || "chromium", [
      "--headless=new", "--no-sandbox", "--disable-gpu",
      `--user-data-dir=${profile}`, "--allow-file-access-from-files",
      "--virtual-time-budget=8000", "--dump-dom", url.href,
    ], { encoding: "utf8", timeout: 20000, stdio: ["ignore", "pipe", "pipe"] });
    const result = html.match(/<body[^>]*data-test-result="([^"]+)"/)?.[1];
    assert.ok(result?.startsWith("PASS"), `${scenario}: ${result || "fixture did not complete"}`);
    console.log(`PASS ${scenario}`);
  }
} finally {
  rmSync(profile, { recursive: true, force: true });
}
