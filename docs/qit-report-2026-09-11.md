# QIT Test Report — ReviewFlow 3.0.6

**Date:** 2026-09-11
**Package tested:** `build/reviewflow.zip` (784 KB, 86 files), built from branch `wc-compatibility` @ `7bb551c`
**Extension:** ReviewFlow (WooCommerce.com ID 18734006854602, slug `reviewflow`)
**Environment:** WordPress 7.1 · WooCommerce 11.1.0 · PHP 7.4 (static scans) / PHP 8.2 (runtime suites)

## Verdict

**Ready to resubmit.** All four previously failing test types now pass. No blocking
errors remain in any of the eight test types on the submission report.

## Results vs. previous submission

| Test type | Previous | Now | Detail | Run ID |
|---|---|---|---|---|
| Activation | ❌ Failed | ✅ Success | 18 passed, 0 failed | 5162007 |
| Activation (canonical compatibility check) | ✅ Success | ✅ Success | No errors detected | 5162020 |
| Security | ❌ Failed | ⚠️ Warning | **0 errors**, 6 informational warnings | 5162003 |
| Code Compatibility | ✅ Success | ✅ Success | 0 errors, 0 warnings (PHP 7.4 → 8.5) | 5162005 |
| Malware | ✅ Success | ✅ Success | 0 issues | 5162004 |
| Woo API | ✅ Success | ✅ Success | 265 passed, 0 failed, 4 skipped | 5162019 |
| Woo E2E | ❌ Failed | ✅ Success | 123 passed, 0 failed, 3 skipped | 5162009 |
| Validation | ❌ Failed | ✅ Success | 0 errors, 0 warnings | 5162002 |

## The one non-green row: Security = warning

Six findings, all from semgrep, all severity **INFO**, all the same rule:
`audit.php.wp.security.rest-route.permission-callback.return-true`.

| File | Lines |
|---|---|
| `includes/headless-api.php` | 27, 38, 54, 70, 84, 111 |

All six are `WP_REST_Server::READABLE` (GET-only) routes that expose published review
data — the same data WordPress core already exposes publicly at `/wp/v2/comments`.
There are no write routes with an open permission callback. Every ID argument carries a
`sanitize_callback` (`absint`).

Per the QIT security docs: *failures* block listing; *warnings* are "non-critical" and do
not. PHPCS, composer audit, npm audit, WPScan and gitleaks all returned zero findings.

**Optional hardening** (not required to submit): add a `// nosemgrep` comment with a short
justification above each `register_rest_route` call in `headless-api.php` to silence the
rule and pre-empt a reviewer question. Leaving it as-is is also defensible.

## Notes on flakiness

The first Woo API run reported 1 failure: `orders.test.ts › orderby › include`. The stack
trace showed the failure was inside the suite's **teardown** helper
(`productsTestSetupDeleteSampleData`), on `POST /wp-json/wc/v3/products/batch` hitting
Playwright's 10s timeout — not an assertion about plugin behaviour. ReviewFlow registers no
`delete_post` / `before_delete_post` / `woocommerce_delete_product` hooks, so it cannot
slow a product batch delete. A clean re-run confirmed it: 265 passed, 0 failed.

Three Woo E2E tests and four Woo API tests are skipped by the core suites themselves
(`install-wc` fixture, plus two tagged core tests); this is normal and not attributable to
the plugin.

## Package sanity checks

- `qit woo:validate-zip build/reviewflow.zip` → ZIP file content is valid
- Zip root folder, main file, and text domain all use the marketplace slug: `reviewflow/reviewflow.php`, `Text Domain: reviewflow`
- Headers present: `Requires at least: 6.8`, `Requires PHP: 7.4`, `WC requires at least: 8.0`, `WC tested up to: 11.1`, `Requires Plugins: woocommerce`, `License: GPL v2 or later`
- HPOS declared compatible via `FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true )`
- `readme.txt` and `changelog.txt` present, `Stable tag: 3.0.6` matches the plugin header
- No Freemius SDK, no `licensing.php`, no `node_modules`, no `dev/` sources in the package

## How to reproduce

```bash
npm run build:woo

qit run:validation      reviewflow --zip build/reviewflow.zip
qit run:security        reviewflow --zip build/reviewflow.zip
qit run:malware         reviewflow --zip build/reviewflow.zip
qit run:phpcompatibility reviewflow --zip build/reviewflow.zip
qit run:activation      reviewflow --zip build/reviewflow.zip
qit run:activation      reviewflow --zip build/reviewflow.zip --extension_set compatibility
qit run:woo-api         reviewflow --zip build/reviewflow.zip
qit run:woo-e2e         reviewflow --zip build/reviewflow.zip
```
