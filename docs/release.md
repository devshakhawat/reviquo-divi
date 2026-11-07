# Releasing the WordPress.org edition

WordPress.org gets the **free** version that Freemius generates from the premium
build, never the source tree itself. One command runs the whole chain:

```
npm run release
```

```
build.sh ──► premium zip ──► Freemius (pending) ──► free zip ──► validate
                                                                   │
        git tag v<version> ◄── SVN commit ◄── Freemius released ◄──┘ (confirm)
```

1. **Preflight** – version in sync, readme changelog entry, clean git tree,
   version not yet on WordPress.org SVN, not older than the latest on Freemius.
2. **Build** – `build.sh` → `build/org/product-reviews.zip` (premium). The
   script checks it still has `'is_premium' => true` and the
   `wp_org_gatekeeper` key, which Freemius needs to generate the free version.
3. **Freemius upload** – uploaded as a *pending* deployment, so nobody gets it
   until the rest of the release has passed.
4. **Free version** – downloaded with `is_premium=false` to
   `build/org/product-reviews-free-<version>.zip` and validated: one
   `product-reviews/` folder, header and `Stable tag` match, `is_premium`
   is `false`, no gatekeeper key, no `.env` file, `php -l` clean.
5. **SVN staging** – fresh checkout in `build/release/svn`; the free version is
   synced into `trunk/` (removed files are `svn rm`ed), `.wordpress-org/` into
   `assets/` (add/update only, PNG/JPG/GIF/SVG get the right `svn:mime-type`),
   `trunk` is copied to `tags/<version>`. The changes are listed and you confirm.
6. **Publish** – the Freemius deployment is set to `released`, the SVN changes
   are committed, and `v<version>` is tagged and pushed to `origin`.

## One-time setup

```
cp .env.release.example .env.release
```

Fill in `FS_API_TOKEN` (Freemius › product › Settings › API & Keys › API Bearer
token). `SVN_USERNAME` / `SVN_PASSWORD` are optional; without them svn uses its
cached credentials or prompts at commit time. `.env.release` is git-ignored and
`.distignore` keeps every `.env*` file out of all packages.

## Cutting a release

1. Bump `Version:` in `product-reviews.php`, run `npm run version:sync`.
2. Add the `= X.Y.Z - YYYY-MM-DD =` entry to `readme.txt` (and the edition
   changelogs if you ship those too). Commit.
3. Optional rehearsal: `npm run release -- --svn-dry-run`. It uploads to
   Freemius as *pending*, stages SVN and stops. Inspect `build/release/svn`.
4. `npm run release` (add `--reuse-freemius` if you ran the dry run).

## Options

| Option | Effect |
| --- | --- |
| `--svn-dry-run` | Stop before the SVN commit; Freemius stays pending, no git tag. |
| `--release-mode=beta\|pending` | Release on Freemius only (never WordPress.org). |
| `--no-svn` | Freemius only (upload, release, free zip). |
| `--reuse-freemius` | Continue with this version's existing Freemius upload instead of refusing. |
| `--no-git-tag` | Skip creating and pushing `v<version>`. |
| `-y`, `--yes` | Commit without the confirmation prompt. |

## Recovering

* **Version already on Freemius** – from a dry run or an interrupted release:
  rerun with `--reuse-freemius`. If the code changed since that upload, delete
  the deployment in the Freemius dashboard and rerun without it.
* **SVN commit failed** – Freemius is already released. Fix the cause
  (credentials, network) and rerun with `--reuse-freemius`.
* **Git tag push failed** – `git push origin v<version>`.

## CI

`.github/workflows/deploy.yml` runs the same script on manual dispatch (secrets
`FS_API_TOKEN`, `SVN_USERNAME`, `SVN_PASSWORD`). It defaults to an SVN dry run
and never tags. `.wordpress-org/` is not in git, so a CI release leaves the
WordPress.org listing assets as they are. It deliberately does not run on `v*`
tags: `npm run release` pushes the tag after releasing.
