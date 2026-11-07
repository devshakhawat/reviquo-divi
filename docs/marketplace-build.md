# Divi Marketplace build

This repository holds only the Divi (Elegant Themes) Marketplace edition. The
WordPress.org / Freemius and WooCommerce Marketplace editions were removed.

| | |
| --- | --- |
| Command | `npm run build` (alias `build:marketplace`) |
| Script | `build-marketplace.sh` |
| Output | `build/divi/reviquo.zip` |
| Folder / bootstrap | `reviquo/reviquo.php` |
| Text domain | `reviquo` (rewritten at build from `product-reviews`) |
| Licensing | none - every feature unlocked |
| Updates | Divi Marketplace |
| Edition sources | `marketplace/` |
| Exclusions | `.distignore` + `.marketplace-distignore` |

The build compiles the assets first (`npm run production`), so there is no need
to run `npm run build:assets` beforehand. Use `npm run build:assets` when you
only want the compiled bundles in `assets/` without a zip.

## Bootstrap

`product-reviews.php` defines `SKTPR_MARKETPLACE_BUILD` inside the
`sktpr:edition-seam` markers. At build time
`bin/make-marketplace-bootstrap.php <source> <destination> <manifest.json>`
regenerates the bootstrap as `reviquo.php`, re-emitting that define and applying
the header overrides from `marketplace/manifest.json`. The generator aborts if
the markers are missing or the result references a licensing SDK.

## Feature policy

All gating goes through `SKTPREVIEW\Features` in PHP and the `licensing` object
the admin bundle reads from `window.sktpr_plugin`. `includes/features.php` is a
copy of `marketplace/features.php`: `has_pro_license()` returns `true` and
`get_pro_setting_keys()` returns an empty array. The build copies
`marketplace/features.php` over `includes/features.php` in the package, so keep
the two identical (`npm run check:parity`, `npm run check:features`).

## Editing

* **Bootstrap, PHP, assets** - edit the source. The package is generated.
* **Header overrides** - `marketplace/manifest.json`.
* **Feature policy** - `marketplace/features.php`, then copy it to
  `includes/features.php`.
* **Listing copy** - `marketplace/readme.txt`.
* **License and third-party credits** - `marketplace/license.txt` (GPL v2 text
  plus a credits section for every bundled third-party resource, as the Divi
  Marketplace requires). It ships as `license.txt` in place of the root
  `LICENSE.txt`; update the credits whenever a bundled dependency changes.
* **Version** - edit the `Version:` header in `product-reviews.php`, then run
  `npm run version:sync`. It updates the constant, `marketplace/readme.txt` and
  `package.json`, and warns about a missing changelog entry.

## Checks that run on every build

`build-marketplace.sh` refuses to produce a package that fails any of these:

| Check | Guards against |
| --- | --- |
| `bin/sync-version.php --check` | header, constant, readme and `package.json` disagreeing |
| `bin/check-features-parity.php` | `marketplace/features.php` missing a constant or method the source class has |
| `bin/make-marketplace-bootstrap.php` | a bootstrap that references a licensing SDK |
| `bin/check-marketplace-features.php` | a policy that does not unlock every Pro decision |
| `forbid` greps | licensing SDK calls, outbound HTTP, WordPress.org links in bundles |
| directory checks | `freemius/` or `marketplace/` present in the package |
| `bin/check-text-domain.php` | a translation call using the wrong text domain |
| `bin/set-text-domain.php` + `wp i18n make-pot` | the package keeping the `product-reviews` domain; requires WP-CLI |
| `php -l` | a syntax error in any packaged file |

## Releasing

Build locally with `npm run build` and upload `build/divi/reviquo.zip` to the
Divi Marketplace by hand.
