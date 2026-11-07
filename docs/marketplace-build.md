# Edition builds

The plugin ships in three editions from one source tree (the `unified` branch).
The source tree itself is the WordPress.org / Freemius edition; the two
marketplace editions are generated from it at build time.

| | WordPress.org edition | Divi Marketplace edition | WooCommerce Marketplace edition |
| --- | --- | --- | --- |
| Command | `npm run build` (alias `build:org`, `zip`) | `npm run build:marketplace` (alias `build:divi`) | `npm run build:woo` |
| Script | `build.sh` | `build-marketplace.sh` | `build-woo-marketplace.sh` |
| Output | `build/org/product-reviews.zip` | `build/divi/reviquo.zip` | `build/woo/reviquo.zip` |
| Folder / bootstrap | `product-reviews/product-reviews.php` | `reviquo/reviquo.php` | `reviquo/reviquo.php` |
| Text domain | `product-reviews` | `reviquo` (rewritten at build) | `reviquo` (rewritten at build) |
| Licensing | Freemius SDK | none | none |
| Pro features | gated by license | all unlocked | all unlocked |
| Updates | WordPress.org / Freemius | Divi Marketplace | WooCommerce.com |
| Edition sources | - | `marketplace/` | `woo-marketplace/` |
| Extra exclusions | - | `.marketplace-distignore` | `.woo-marketplace-distignore` |

Every build compiles the assets first (`npm run production`), so there is no
need to run `npm run build:assets` beforehand. Use `npm run build:assets` when
you only want the compiled bundles in `assets/` without a zip.

`.distignore` applies to all three packages, so every edition's source folder
and build script is excluded from every zip.

## How the marketplace editions drop Freemius

Everything Freemius-related lives in `includes/licensing.php`, loaded from one
place in `product-reviews.php`:

```php
// sktpr:edition-seam:start
require_once SKTPR_PLUGIN_DIR . 'includes/licensing.php';
// sktpr:edition-seam:end
```

`bin/make-marketplace-bootstrap.php <source> <destination> <manifest.json>`
regenerates the bootstrap for the package, replacing that region with
`define( 'SKTPR_MARKETPLACE_BUILD', true );` and applying the header overrides
from the edition's `manifest.json`. The generator aborts if the markers are
missing, so a refactor of the licensing code cannot silently ship licensing code
to a marketplace.

The edition distignore then keeps `freemius/` and `includes/licensing.php` out
of the package entirely.

## How every Pro feature is unlocked

All gating goes through `SKTPREVIEW\Features` in PHP and the `licensing` object
the admin bundle reads from `window.sktpr_plugin`. The marketplace builds swap
`includes/features.php` for the edition's `features.php`, whose
`has_pro_license()` returns `true` and whose `get_pro_setting_keys()` returns an
empty array. `includes/features.php` also short-circuits on
`SKTPR_MARKETPLACE_BUILD` and reports `is_marketplace`, which hides the
Free/Pro badge in the admin header. The JavaScript needs no separate build
because it reads `licensing.*` at runtime.

## Editing an edition

* **Bootstrap, PHP, assets** - edit the source. Packages are generated.
* **Header overrides** - `marketplace/manifest.json` (Divi) or
  `woo-marketplace/manifest.json` (WooCommerce).
* **Feature policy** - `marketplace/features.php` / `woo-marketplace/features.php`,
  keeping their public API identical to `includes/features.php`
  (`npm run check:parity` verifies both).
* **Listing copy** - `marketplace/readme.txt` / `woo-marketplace/readme.txt`.
* **Divi license and third-party credits** - `marketplace/license.txt` (GPL v2
  text plus a credits section for every bundled third-party resource, as the
  Divi Marketplace requires). It ships as `license.txt` in place of the root
  `LICENSE.txt`; update the credits whenever a bundled dependency changes.
* **WooCommerce changelog** - `woo-marketplace/changelog.txt`, in
  WooCommerce.com's `YYYY-MM-DD - version X.Y.Z` format.
* **Version** - edit the `Version:` header in `product-reviews.php`, then run
  `npm run version:sync`. It updates the constant, all three readmes and
  `package.json`, and warns about a missing changelog entry in any edition.

## Checks that run on every build

Each build script refuses to produce a package that fails its checks.

| Check | Org | Divi | Woo | Guards against |
| --- | :-: | :-: | :-: | --- |
| `bin/sync-version.php --check` | ✓ | ✓ | ✓ | header, constant, readmes and `package.json` disagreeing |
| Freemius present | ✓ | | | shipping an org package without `freemius/` or `includes/licensing.php`, or with `SKTPR_MARKETPLACE_BUILD` |
| `bin/check-features-parity.php` | | ✓ | ✓ | the edition's `features.php` missing a constant or method the source class gained, which fatals at runtime |
| `bin/make-marketplace-bootstrap.php` | | ✓ | ✓ | a bootstrap that still references the licensing SDK |
| `bin/check-marketplace-features.php` | | ✓ | ✓ | shipping the gated policy by mistake - asserts every Pro decision is unlocked |
| `forbid` greps | | ✓ | ✓ | `freemius/start`, `fs_dynamic_init`, `pr_fs(`, outbound HTTP, WordPress.org links in bundles |
| directory checks | ✓ | ✓ | ✓ | another edition's source folder (or `freemius/` in a marketplace zip) present in the package |
| `bin/check-text-domain.php` | | ✓ | ✓ | a translation call using the wrong text domain |
| `bin/set-text-domain.php` + `wp i18n make-pot` | | ✓ | ✓ | a marketplace package keeping the `product-reviews` domain; requires WP-CLI |
| `php -l` | ✓ | ✓ | ✓ | a syntax error in any packaged file |

## Releasing

The marketplace packages are built locally (`npm run build:marketplace`,
`npm run build:woo`) and uploaded by hand from `build/divi/` and `build/woo/`;
the build scripts run every check above, so there is no CI for them.
WordPress.org releases go through Freemius (`npm run release`, see
[release.md](release.md)): the source tree is the premium build, and only the
free version Freemius generates from it is committed to SVN.
`.github/workflows/deploy.yml` runs the same script on manual dispatch.
