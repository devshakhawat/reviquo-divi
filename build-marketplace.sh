#!/usr/bin/env bash

# Build the Divi (Elegant Themes) Marketplace edition: build/divi/reviquo.zip,
# containing reviquo/reviquo.php. No Freemius, every feature unlocked;
# licensing and updates come from the Divi Marketplace. Like the WooCommerce
# Marketplace edition, it ships under the marketplace product slug, which is
# also its text domain.

set -euo pipefail

plugin_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
build_dir="${plugin_dir}/build/divi"
stage_dir="$(mktemp -d)"

# Divi Marketplace product slug: the zip name, the top-level folder, the main
# plugin file and the text domain all use it.
slug="reviquo"
pkg_dir="${stage_dir}/${slug}"
package_name="ReviQuo"
source_domain="product-reviews"
support_url="https://www.elegantthemes.com/marketplace/"

cleanup() {
	rm -rf "${stage_dir}"
}
trap cleanup EXIT

fail() {
	echo "Marketplace validation failed: $1" >&2
	exit 1
}

# Fail before building: both checks are cheap and both invalidate the package.
php "${plugin_dir}/bin/sync-version.php" --check \
	|| fail "the plugin version is not in sync. Run 'npm run version:sync'."

php "${plugin_dir}/bin/check-features-parity.php" \
	"${plugin_dir}/includes/features.php" \
	"${plugin_dir}/marketplace/features.php" \
	|| fail "marketplace/features.php no longer matches the source feature policy API."

command -v wp >/dev/null 2>&1 \
	|| fail "WP-CLI ('wp') is required to regenerate the translation template."

npm --prefix "${plugin_dir}" run production

mkdir -p "${build_dir}" "${pkg_dir}"
rsync -a \
	--exclude-from="${plugin_dir}/.distignore" \
	--exclude-from="${plugin_dir}/.marketplace-distignore" \
	--exclude='/build' \
	--exclude='/product-reviews.php' \
	--exclude='/LICENSE.txt' \
	"${plugin_dir}/" \
	"${pkg_dir}/"

# The bootstrap is generated from the source plugin file so the two editions
# cannot drift; only features.php and readme.txt are separate documents. The
# marketplace edition ships as reviquo/reviquo.php, so the source
# bootstrap file is excluded from the stage above.
php "${plugin_dir}/bin/make-marketplace-bootstrap.php" \
	"${plugin_dir}/product-reviews.php" \
	"${pkg_dir}/${slug}.php" \
	"${plugin_dir}/marketplace/manifest.json" \
	|| fail "the marketplace bootstrap could not be generated."

cp "${plugin_dir}/marketplace/features.php" "${pkg_dir}/includes/features.php"
cp "${plugin_dir}/marketplace/readme.txt" "${pkg_dir}/readme.txt"

# The marketplace requires a license.txt carrying the GPL text plus credits for
# all bundled third-party code and media; it replaces the plain root LICENSE.txt
# (excluded from the stage above so the zip holds exactly one license file).
cp "${plugin_dir}/marketplace/license.txt" "${pkg_dir}/license.txt"

grep -q 'GNU GENERAL PUBLIC LICENSE' "${pkg_dir}/license.txt" \
	&& grep -q 'Third Party Code Credits' "${pkg_dir}/license.txt" \
	|| fail "license.txt is missing the GPL text or the third-party credits section."

# Translation calls, the plugin header, block.json and the compiled editor
# bundle switch to the marketplace slug as their text domain.
php "${plugin_dir}/bin/set-text-domain.php" "${pkg_dir}" "${source_domain}" "${slug}" \
	|| fail "the text domain could not be rewritten."

# Regenerate the translation template for the new domain; it also stops the
# marketplace copy pointing translators at a wordpress.org support forum.
#
# JS strings come from the block's source file rather than the compiled bundle:
# the bundle aliases wp.i18n.__ to a minified name that make-pot cannot see.
# The source is staged under the bundle's shipped path so the references in
# the template match the file WordPress looks up with wp_set_script_translations().
js_stage="${stage_dir}/js-src/assets/blocks/reviews"
mkdir -p "${js_stage}"
cp "${plugin_dir}/dev/blocks/reviews/index.js" "${js_stage}/index.js"

wp i18n make-pot "${stage_dir}/js-src" "${stage_dir}/js.pot" \
	--domain="${source_domain}" --skip-php --skip-block-json --skip-audit --quiet \
	|| fail "the block editor strings could not be extracted."

rm -f "${pkg_dir}"/languages/*.pot
mkdir -p "${pkg_dir}/languages"

wp i18n make-pot "${pkg_dir}" "${pkg_dir}/languages/${slug}.pot" \
	--slug="${slug}" --domain="${slug}" --package-name="${package_name}" \
	--skip-js --merge="${stage_dir}/js.pot" \
	--headers="{\"Report-Msgid-Bugs-To\":\"${support_url}\"}" \
	|| fail "the translation template could not be generated."

# Fail when the pattern matches, and also when grep itself errors, so a broken
# check can never be mistaken for a clean package.
forbid() {
	local message="$1"
	shift
	local output status=0

	# -o keeps a minified bundle from dumping a whole line of JavaScript.
	output="$(grep -roHP --exclude-dir=fonts "$@" "${pkg_dir}")" || status=$?

	case "${status}" in
		0)
			printf '%s\n' "${output}" | sort -u | head -20 >&2
			fail "${message}"
			;;
		1) ;;
		*) fail "the '${message%%.*}' check could not run (grep exit ${status})." ;;
	esac
}

forbid "an external updater or licensing SDK remains in the package." \
	"freemius/start|fs_dynamic_init|class-fs-plugin-updater|pr_fs\("

forbid "the package makes outbound HTTP requests." --include='*.php' \
	"wp_remote_(get|post|request|head)|curl_exec"

# The compiled bundles must not carry WordPress.org listing links or Freemius
# strings, because a marketplace customer never sees either channel.
forbid "the old display name 'Reviquo' remains in the package; the brand is 'ReviQuo'." \
	"Reviquo"

forbid "a compiled bundle links to WordPress.org or references Freemius." \
	--include='*.js' --include='*.css' \
	"wordpress\.org|freemius"

if [ -e "${pkg_dir}/freemius" ] || [ -e "${pkg_dir}/marketplace" ] || [ -e "${pkg_dir}/woo-marketplace" ]; then
	fail "a directory that must not ship is present in the package."
fi

if [ -e "${pkg_dir}/includes/licensing.php" ]; then
	fail "the Freemius licensing bootstrap is present in the package."
fi

# The header version, the SKTPR_VERSION constant and the readme stable tag must agree.
header_version="$(grep -m1 -oP '^\s*\*\s*Version:\s*\K[0-9.]+' "${pkg_dir}/${slug}.php")"
constant_version="$(grep -m1 -oP "SKTPR_VERSION',\s*'\K[0-9.]+" "${pkg_dir}/${slug}.php")"
readme_version="$(grep -m1 -oP '^Stable tag:\s*\K[0-9.]+' "${pkg_dir}/readme.txt")"

if [ -z "${header_version}" ] || [ "${header_version}" != "${constant_version}" ] || [ "${header_version}" != "${readme_version}" ]; then
	fail "version mismatch (header '${header_version}', constant '${constant_version}', readme '${readme_version}')."
fi

# Every translation call must now use the marketplace text domain.
if ! php "${plugin_dir}/bin/check-text-domain.php" "${pkg_dir}" "${slug}"; then
	fail "a translation call uses the wrong text domain."
fi

if grep -rq --include='*.js' --include='*.json' --include='*.pot' "${source_domain}" "${pkg_dir}/assets" "${pkg_dir}/blocks" "${pkg_dir}/languages"; then
	fail "the WordPress.org text domain is still present in a bundle, block.json or the translation template."
fi

pot_strings="$(grep -c '^msgid "' "${pkg_dir}/languages/${slug}.pot" || true)"
if [ "${pot_strings:-0}" -lt 100 ]; then
	fail "the translation template holds only ${pot_strings} strings; extraction looks broken."
fi

# Prove the staged policy actually unlocks every Pro feature, rather than only
# proving the licensing SDK is gone.
php "${plugin_dir}/bin/check-marketplace-features.php" \
	"${pkg_dir}/includes/features.php" \
	|| fail "the staged feature policy does not unlock every Pro feature."

find "${pkg_dir}" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null

(
	cd "${stage_dir}"
	zip -qr "${slug}.zip" "${slug}"
)

mv -f "${stage_dir}/${slug}.zip" "${build_dir}/${slug}.zip"

echo "Created ${build_dir}/${slug}.zip"
