#!/usr/bin/env bash

# Build the WordPress.org / Freemius edition: build/org/product-reviews.zip,
# containing product-reviews/product-reviews.php with the Freemius SDK and
# includes/licensing.php. This is the source tree as-is; the two marketplace
# editions are generated from it by build-marketplace.sh (Divi) and
# build-woo-marketplace.sh (WooCommerce).

set -euo pipefail

plugin_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
build_dir="${plugin_dir}/build/org"
stage_dir="$(mktemp -d)"
pkg_dir="${stage_dir}/product-reviews"

cleanup() {
	rm -rf "${stage_dir}"
}
trap cleanup EXIT

fail() {
	echo "WordPress.org build validation failed: $1" >&2
	exit 1
}

php "${plugin_dir}/bin/sync-version.php" --check \
	|| fail "the plugin version is not in sync. Run 'npm run version:sync'."

npm --prefix "${plugin_dir}" run production

mkdir -p "${build_dir}" "${pkg_dir}"
rsync -a \
	--exclude-from="${plugin_dir}/.distignore" \
	--exclude='/build' \
	"${plugin_dir}/" \
	"${pkg_dir}/"

# This edition is the one that must carry licensing: fail rather than ship a
# package whose Pro features can never be unlocked.
[ -f "${pkg_dir}/freemius/start.php" ] \
	|| fail "the Freemius SDK (freemius/start.php) is missing from the package."

[ -f "${pkg_dir}/includes/licensing.php" ] \
	|| fail "includes/licensing.php is missing from the package."

grep -q "require_once SKTPR_PLUGIN_DIR . 'includes/licensing.php';" "${pkg_dir}/product-reviews.php" \
	|| fail "product-reviews.php no longer loads includes/licensing.php."

if grep -q 'SKTPR_MARKETPLACE_BUILD' "${pkg_dir}/product-reviews.php"; then
	fail "product-reviews.php defines SKTPR_MARKETPLACE_BUILD, which unlocks every Pro feature."
fi

for dir in marketplace marketplace-assets woo-marketplace; do
	[ ! -e "${pkg_dir}/${dir}" ] || fail "the '${dir}' directory must not ship in the WordPress.org package."
done

find "${pkg_dir}" -name '*.php' -not -path '*/freemius/*' -print0 | xargs -0 -n1 php -l >/dev/null

(
	cd "${stage_dir}"
	zip -qr product-reviews.zip product-reviews
)

mv -f "${stage_dir}/product-reviews.zip" "${build_dir}/product-reviews.zip"

echo "Created ${build_dir}/product-reviews.zip"
