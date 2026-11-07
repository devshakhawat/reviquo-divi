#!/usr/bin/env bash

# Release the WordPress.org edition through Freemius.
#
#   1. build.sh builds the premium package (build/org/product-reviews.zip).
#   2. The package is uploaded to Freemius as a pending deployment. Freemius
#      strips the premium-only code and generates the free version.
#   3. The free version is downloaded from Freemius and validated.
#   4. The free version is staged in a WordPress.org SVN checkout (trunk,
#      tags/<version>, assets) and the changes are shown for confirmation.
#   5. The Freemius deployment is released and the SVN changes are committed.
#   6. The release is tagged in git (v<version>) and the tag is pushed.
#
# The version comes from the Version: header in product-reviews.php; bump it
# and run 'npm run version:sync' first. Credentials are read from the
# environment or from .env.release (see .env.release.example).
#
# Usage: npm run release -- [options]    (or: bash bin/release.sh [options])
# Run with --help for the options.

set -euo pipefail

plugin_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
slug="product-reviews"
api="https://api.freemius.com/v1"
svn_url="https://plugins.svn.wordpress.org/${slug}"
work_dir="${plugin_dir}/build/release"
premium_zip="${plugin_dir}/build/org/${slug}.zip"

release_mode="released"
deploy_svn=1
svn_dry_run=0
assume_yes=0
git_tag=1
reuse_freemius=0

usage() {
	cat <<'EOF'
Release the WordPress.org edition: build -> Freemius -> free version -> WordPress.org SVN.

Usage: npm run release -- [options]

Options:
  --release-mode=MODE   Freemius release mode: released (default), beta or pending.
                        Only 'released' continues to WordPress.org.
  --svn-dry-run         Do everything up to the SVN commit, show the staged
                        changes, then stop. The Freemius deployment stays pending.
  --no-svn              Stop after Freemius (upload, release, free zip download).
  --reuse-freemius      Continue with a version that is already on Freemius
                        (e.g. to resume after a failed SVN commit) instead of
                        refusing. The existing upload is used, not a new build.
  --no-git-tag          Do not create and push the v<version> git tag.
  -y, --yes             Commit to SVN without asking for confirmation.
  -h, --help            Show this help.

Environment (or .env.release in the plugin root, or the file in RELEASE_ENV_FILE):
  FS_API_TOKEN          Freemius product API bearer token (required).
  FS_PRODUCT_ID         Freemius product ID (default: the id in includes/licensing.php).
  SVN_USERNAME          WordPress.org username (optional; falls back to svn's
  SVN_PASSWORD          cached credentials or an interactive prompt).
EOF
}

step() {
	printf '\n\033[1;34m==> %s\033[0m\n' "$1"
}

info() {
	printf '    %s\n' "$1"
}

warn() {
	printf '\033[1;33mwarning:\033[0m %s\n' "$1" >&2
}

fail() {
	printf '\033[1;31mrelease failed:\033[0m %s\n' "$1" >&2
	exit 1
}

for arg in "$@"; do
	case "${arg}" in
		--release-mode=*) release_mode="${arg#*=}" ;;
		--svn-dry-run) svn_dry_run=1 ;;
		--no-svn) deploy_svn=0 ;;
		--reuse-freemius) reuse_freemius=1 ;;
		--no-git-tag) git_tag=0 ;;
		-y | --yes) assume_yes=1 ;;
		-h | --help) usage; exit 0 ;;
		*) usage >&2; fail "unknown option '${arg}'." ;;
	esac
done

case "${release_mode}" in
	released) ;;
	beta | pending)
		# A beta or pending version is not public, so it must not reach WordPress.org.
		deploy_svn=0
		;;
	*) fail "--release-mode must be released, beta or pending (got '${release_mode}')." ;;
esac

if [ "${svn_dry_run}" -eq 1 ] && [ "${deploy_svn}" -eq 0 ]; then
	fail "--svn-dry-run needs the WordPress.org step; drop --no-svn or use --release-mode=released."
fi

# A dry run publishes nothing, so there is nothing to tag.
if [ "${svn_dry_run}" -eq 1 ]; then
	git_tag=0
fi

env_file="${RELEASE_ENV_FILE:-${plugin_dir}/.env.release}"
if [ -f "${env_file}" ]; then
	set -a
	# shellcheck disable=SC1090
	. "${env_file}"
	set +a
fi

# ---------------------------------------------------------------------------
# Freemius API
# ---------------------------------------------------------------------------

# fs_request METHOD PATH OUTPUT_FILE [extra curl args...]
# Prints the HTTP status code. The response body is written to OUTPUT_FILE.
fs_request() {
	local method="$1" path="$2" output="$3"
	shift 3

	curl -sS -L -X "${method}" \
		-H "Authorization: Bearer ${FS_API_TOKEN}" \
		-o "${output}" \
		-w '%{http_code}' \
		"$@" \
		"${api}/products/${FS_PRODUCT_ID}/${path}"
}

# fs_error OUTPUT_FILE - the error message from a Freemius JSON error response.
fs_error() {
	jq -r '.error.message // .message // empty' "$1" 2>/dev/null || true
}

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------

step "Preflight"

for tool in curl jq php rsync unzip zip git npm; do
	command -v "${tool}" >/dev/null || fail "'${tool}' is required but not installed."
done
if [ "${deploy_svn}" -eq 1 ]; then
	command -v svn >/dev/null || fail "'svn' (Subversion) is required for the WordPress.org step."
fi

[ -n "${FS_API_TOKEN:-}" ] \
	|| fail "FS_API_TOKEN is not set. Copy .env.release.example to .env.release and add the token from Freemius > Settings > API & Keys."

if [ -z "${FS_PRODUCT_ID:-}" ]; then
	FS_PRODUCT_ID="$(sed -n "s/^[[:space:]]*'id'[[:space:]]*=>[[:space:]]*'\([0-9]*\)'.*/\1/p" "${plugin_dir}/includes/licensing.php" | head -n1)"
fi
[ -n "${FS_PRODUCT_ID}" ] || fail "FS_PRODUCT_ID is not set and could not be read from includes/licensing.php."

version="$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*\([^[:space:]]*\).*/\1/p' "${plugin_dir}/product-reviews.php" | head -n1)"
[ -n "${version}" ] || fail "could not read the Version: header from product-reviews.php."

php "${plugin_dir}/bin/sync-version.php" --check \
	|| fail "the plugin version is not in sync. Run 'npm run version:sync'."

grep -q "^= ${version//./\\.}[ =]" "${plugin_dir}/readme.txt" \
	|| fail "readme.txt has no '= ${version} - YYYY-MM-DD =' changelog entry."

if [ -n "$(git -C "${plugin_dir}" status --porcelain)" ]; then
	fail "the git working tree has uncommitted changes. Commit or stash them so the release matches a commit."
fi

branch="$(git -C "${plugin_dir}" rev-parse --abbrev-ref HEAD)"
head_commit="$(git -C "${plugin_dir}" rev-parse HEAD)"
tag_name="v${version}"

if tag_commit="$(git -C "${plugin_dir}" rev-parse -q --verify "refs/tags/${tag_name}^{commit}")"; then
	[ "${tag_commit}" = "${head_commit}" ] \
		|| fail "git tag ${tag_name} already exists on another commit. Bump the version for a new release."
fi

info "Version:        ${version}"
info "Git:            ${branch} @ $(git -C "${plugin_dir}" rev-parse --short HEAD)"
info "Freemius:       product ${FS_PRODUCT_ID}, release mode '${release_mode}'"
if [ "${deploy_svn}" -eq 1 ]; then
	info "WordPress.org:  ${svn_url}$([ "${svn_dry_run}" -eq 1 ] && echo ' (dry run, no commit)')"
else
	info "WordPress.org:  skipped"
fi

rm -rf "${work_dir}"
mkdir -p "${work_dir}"

if [ "${deploy_svn}" -eq 1 ]; then
	svn_tags="$(svn ls "${svn_url}/tags/")" || fail "could not reach ${svn_url}."
	if printf '%s\n' "${svn_tags}" | grep -qxF "${version}/"; then
		fail "${version} is already released on WordPress.org (tags/${version} exists). Bump the version."
	fi
fi

# Find this version on Freemius. The list is newest first, so the first entry
# is also the latest version, which this release must not be older than.
status="$(fs_request GET 'tags.json?count=50' "${work_dir}/tags.json")"
[ "${status}" = "200" ] \
	|| fail "could not list Freemius deployments (HTTP ${status}): $(fs_error "${work_dir}/tags.json")"
jq -e '.tags | type == "array"' "${work_dir}/tags.json" >/dev/null 2>&1 \
	|| fail "unexpected response from Freemius when listing deployments: $(head -c 300 "${work_dir}/tags.json")"

tag_id="$(jq -r --arg v "${version}" '[.tags[]? | select(.version == $v)][0].id // empty' "${work_dir}/tags.json")"
tag_mode="$(jq -r --arg v "${version}" '[.tags[]? | select(.version == $v)][0].release_mode // empty' "${work_dir}/tags.json")"
latest="$(jq -r '.tags[0]?.version // empty' "${work_dir}/tags.json")"

if [ -n "${tag_id}" ]; then
	[ "${reuse_freemius}" -eq 1 ] \
		|| fail "${version} is already on Freemius (deployment ${tag_id}, ${tag_mode}). Pass --reuse-freemius to continue with that upload, or delete it in the Freemius dashboard to upload this build again."
	info "Reusing Freemius deployment ${tag_id} (${tag_mode})."
elif [ -n "${latest}" ] && php -r 'exit( version_compare( $argv[1], $argv[2], ">" ) ? 0 : 1 );' "${latest}" "${version}"; then
	fail "Freemius already has a newer version (${latest}) than ${version}."
fi

# ---------------------------------------------------------------------------
# Build and upload the premium package
# ---------------------------------------------------------------------------

if [ -z "${tag_id}" ]; then
	step "Building the premium package"
	bash "${plugin_dir}/build.sh"

	[ -f "${premium_zip}" ] || fail "build.sh did not produce ${premium_zip}."
	if [ -n "$(git -C "${plugin_dir}" status --porcelain)" ]; then
		warn "the build changed tracked files (usually the compiled assets/). The release uses the fresh build; commit the changes afterwards."
	fi
	if unzip -Z1 "${premium_zip}" | grep -Eq '(^|/)\.env'; then
		fail "the package contains a .env file. It must never ship; check .distignore."
	fi

	# Freemius derives the free version from the premium one by flipping is_premium
	# and dropping the gatekeeper key. A package that already has them flipped
	# would give paying customers a build that can never unlock Pro.
	premium_licensing="$(unzip -p "${premium_zip}" "${slug}/includes/licensing.php")"
	grep -Eq "'is_premium'[[:space:]]*=>[[:space:]]*true" <<<"${premium_licensing}" \
		|| fail "includes/licensing.php must set 'is_premium' => true for the Freemius upload (Freemius generates the free version from it)."
	grep -q 'wp_org_gatekeeper' <<<"${premium_licensing}" \
		|| fail "includes/licensing.php has no 'wp_org_gatekeeper' key. Restore it; Freemius removes it from the free version."

	step "Uploading ${version} to Freemius"
	status="$(fs_request POST tags.json "${work_dir}/upload.json" -F "file=@\"${premium_zip}\";type=application/zip")"
	[ "${status}" = "200" ] || [ "${status}" = "201" ] \
		|| fail "the Freemius upload failed (HTTP ${status}): $(fs_error "${work_dir}/upload.json")"

	tag_id="$(jq -r '.id // empty' "${work_dir}/upload.json")"
	tag_mode="$(jq -r '.release_mode // "pending"' "${work_dir}/upload.json")"
	uploaded_version="$(jq -r '.version // empty' "${work_dir}/upload.json")"

	[ -n "${tag_id}" ] || fail "the Freemius upload returned no deployment id: $(cat "${work_dir}/upload.json")"
	[ "${uploaded_version}" = "${version}" ] \
		|| fail "Freemius read the version as '${uploaded_version}', expected '${version}'. Deployment ${tag_id} was left ${tag_mode}; delete it in the Freemius dashboard."

	info "Deployment ${tag_id} created (${tag_mode})."
fi

# release_on_freemius - set the deployment's release mode unless it already has it.
release_on_freemius() {
	if [ "${tag_mode}" = "${release_mode}" ]; then
		info "Freemius deployment ${tag_id} is already ${release_mode}."
		return
	fi

	local status
	status="$(fs_request PUT "tags/${tag_id}.json" "${work_dir}/release.json" \
		-H 'Content-Type: application/json' \
		--data "{\"release_mode\":\"${release_mode}\"}")"
	[ "${status}" = "200" ] \
		|| fail "could not set deployment ${tag_id} to ${release_mode} (HTTP ${status}): $(fs_error "${work_dir}/release.json")"

	tag_mode="${release_mode}"
	info "Freemius deployment ${tag_id} is now ${release_mode}."
}

# ---------------------------------------------------------------------------
# Download and validate the free version
# ---------------------------------------------------------------------------

step "Downloading the free version from Freemius"

free_zip="${plugin_dir}/build/org/${slug}-free-${version}.zip"
downloaded=0
for attempt in 1 2 3 4 5 6; do
	status="$(fs_request GET "tags/${tag_id}.zip?is_premium=false" "${free_zip}")" || true
	if [ "${status}" = "200" ] && unzip -tq "${free_zip}" >/dev/null 2>&1; then
		downloaded=1
		break
	fi
	# Freemius may still be generating the free version right after the upload.
	info "Not ready yet (HTTP ${status}), retrying in 10s (${attempt}/6)..."
	sleep 10
done
[ "${downloaded}" -eq 1 ] \
	|| fail "could not download the free version of deployment ${tag_id}: $(fs_error "${free_zip}")"
info "Saved ${free_zip}"

step "Validating the free version"

free_dir="${work_dir}/free"
mkdir -p "${free_dir}"
unzip -q "${free_zip}" -d "${free_dir}"
free_pkg="${free_dir}/${slug}"

roots="$(find "${free_dir}" -mindepth 1 -maxdepth 1 -printf '%f\n')"
[ "${roots}" = "${slug}" ] \
	|| fail "the free zip must contain exactly one '${slug}/' folder, found: $(echo "${roots}" | tr '\n' ' ')"

free_version="$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*\([^[:space:]]*\).*/\1/p' "${free_pkg}/${slug}.php" | head -n1)"
[ "${free_version}" = "${version}" ] \
	|| fail "the free version's plugin header says '${free_version}', expected '${version}'."

stable_tag="$(sed -n 's/^Stable tag:[[:space:]]*\([^[:space:]]*\).*/\1/p' "${free_pkg}/readme.txt" | head -n1)"
[ "${stable_tag}" = "${version}" ] \
	|| fail "the free version's readme.txt Stable tag is '${stable_tag}', expected '${version}'."

# Freemius flips is_premium and removes the gatekeeper key in the free version.
# If either survived, this is the premium code and must not reach WordPress.org.
if grep -rq 'wp_org_gatekeeper' "${free_pkg}"; then
	fail "the free version still contains 'wp_org_gatekeeper'. Freemius returned the premium code."
fi
grep -Eq "'is_premium'[[:space:]]*=>[[:space:]]*false" "${free_pkg}/includes/licensing.php" \
	|| fail "includes/licensing.php in the free version does not set 'is_premium' => false."

if find "${free_pkg}" -name '.env*' | grep -q .; then
	fail "the free version contains a .env file."
fi

find "${free_pkg}" -name '*.php' -not -path '*/freemius/*' -print0 | xargs -0 -n1 php -l >/dev/null \
	|| fail "the free version has a PHP syntax error."

info "Plugin header and Stable tag: ${version}"
info "Premium code removed (is_premium => false, no gatekeeper key)"
info "$(find "${free_pkg}" -type f | wc -l) files, PHP syntax OK"

# ---------------------------------------------------------------------------
# Freemius only
# ---------------------------------------------------------------------------

if [ "${deploy_svn}" -eq 0 ]; then
	step "Releasing on Freemius"
	release_on_freemius

	if [ "${git_tag}" -eq 1 ] && [ "${release_mode}" = "released" ]; then
		step "Tagging ${tag_name}"
		git -C "${plugin_dir}" rev-parse -q --verify "refs/tags/${tag_name}" >/dev/null \
			|| git -C "${plugin_dir}" tag -a "${tag_name}" -m "Version ${version}"
		git -C "${plugin_dir}" push origin "${tag_name}"
	fi

	step "Done"
	info "Freemius deployment ${tag_id} is ${tag_mode}. WordPress.org was not updated."
	info "Free version: ${free_zip}"
	exit 0
fi

# ---------------------------------------------------------------------------
# Stage the WordPress.org SVN commit
# ---------------------------------------------------------------------------

step "Staging the WordPress.org SVN commit"

svn_dir="${work_dir}/svn"
# Without SVN_USERNAME, svn uses its cached credentials or prompts for them.
svn_auth=()
if [ -n "${SVN_USERNAME:-}" ]; then
	svn_auth+=(--username "${SVN_USERNAME}" --no-auth-cache)
	if [ -n "${SVN_PASSWORD:-}" ]; then
		svn_auth+=(--password "${SVN_PASSWORD}" --non-interactive)
	fi
fi

svn checkout -q --depth immediates "${svn_url}" "${svn_dir}"
(
	cd "${svn_dir}"
	svn update -q --set-depth infinity trunk
	svn update -q --set-depth infinity assets
)

rsync -rc --delete --exclude='.svn' "${free_pkg}/" "${svn_dir}/trunk/"

assets_src="${plugin_dir}/.wordpress-org"
if [ -d "${assets_src}" ]; then
	# Add and update only: an asset that is missing locally is left on WordPress.org.
	rsync -rc --prune-empty-dirs --exclude='.DS_Store' "${assets_src}/" "${svn_dir}/assets/"
else
	warn ".wordpress-org/ not found; the WordPress.org assets (banners, icons, screenshots) are left unchanged."
fi

(
	cd "${svn_dir}"

	svn add -q --force trunk assets --auto-props --parents --depth infinity

	svn status trunk assets | sed -n 's/^![[:space:]]*//p' | while IFS= read -r missing; do
		svn rm -q "${missing}@"
	done

	# Without an explicit mime type WordPress.org serves the images as downloads.
	find assets -maxdepth 1 -type f \( -name '*.png' -o -name '*.jpg' -o -name '*.jpeg' -o -name '*.gif' -o -name '*.svg' \) | while IFS= read -r image; do
		case "${image}" in
			*.png) mime="image/png" ;;
			*.jpg | *.jpeg) mime="image/jpeg" ;;
			*.gif) mime="image/gif" ;;
			*.svg) mime="image/svg+xml" ;;
		esac
		if [ "$(svn propget svn:mime-type "${image}@" 2>/dev/null)" != "${mime}" ]; then
			svn propset -q svn:mime-type "${mime}" "${image}@"
		fi
	done

	svn cp -q trunk "tags/${version}"
)

changes="$(svn status "${svn_dir}/trunk" "${svn_dir}/assets" | grep -v '^[[:space:]]*$' || true)"
info "trunk/assets: $(echo "${changes}" | grep -c '^A' || true) added, $(echo "${changes}" | grep -c '^M' || true) modified, $(echo "${changes}" | grep -c '^D' || true) deleted"
info "tags/${version}: copied from trunk"
echo "${changes}" | sed "s|${svn_dir}/||" | head -n 40 | sed 's/^/    /'
if [ "$(echo "${changes}" | wc -l)" -gt 40 ]; then
	info "... (full list: svn status ${svn_dir})"
fi

if [ "${svn_dry_run}" -eq 1 ]; then
	step "Dry run complete"
	info "Nothing was committed. Freemius deployment ${tag_id} is ${tag_mode}."
	info "Inspect the working copy: ${svn_dir}"
	info "Release for real with: npm run release -- --reuse-freemius"
	exit 0
fi

if [ "${assume_yes}" -eq 0 ]; then
	[ -t 0 ] || fail "not running in a terminal; pass --yes to commit without confirmation."
	printf '\nRelease %s to Freemius (%s) and commit it to WordPress.org? [y/N] ' "${version}" "${release_mode}"
	read -r answer
	case "${answer}" in
		y | Y | yes | YES) ;;
		*)
			info "Aborted. Nothing was committed; Freemius deployment ${tag_id} is ${tag_mode}."
			info "Resume with: npm run release -- --reuse-freemius"
			exit 1
			;;
	esac
fi

# ---------------------------------------------------------------------------
# Publish
# ---------------------------------------------------------------------------

step "Releasing on Freemius"
release_on_freemius

step "Committing to WordPress.org"
svn commit ${svn_auth[@]+"${svn_auth[@]}"} -m "Release ${version}" "${svn_dir}" \
	|| fail "the SVN commit failed. Freemius ${version} is already ${tag_mode}; fix the problem and resume with: npm run release -- --reuse-freemius"

if [ "${git_tag}" -eq 1 ]; then
	step "Tagging ${tag_name}"
	git -C "${plugin_dir}" rev-parse -q --verify "refs/tags/${tag_name}" >/dev/null \
		|| git -C "${plugin_dir}" tag -a "${tag_name}" -m "Version ${version}"
	git -C "${plugin_dir}" push origin "${tag_name}" \
		|| warn "could not push ${tag_name}; push it with: git push origin ${tag_name}"
fi

step "Released ${version}"
info "Freemius:       deployment ${tag_id} (${tag_mode})"
info "WordPress.org:  https://wordpress.org/plugins/${slug}/ (can take a few minutes to update)"
info "Free version:   ${free_zip}"
