#!/usr/bin/env bash

# Run the WooCommerce.com QIT test types against the WooCommerce Marketplace
# package (build/woo/reviquo.zip) from this machine.
#
# Static scans (validation, security, malware, phpcompat) upload the zip and
# run on QIT's servers; runtime suites (activation, woo-api, woo-e2e) run in a
# local Docker environment. Every selected test runs even when an earlier one
# fails, then a summary is printed.
#
# Exit status: 0 when every test passed or only warned, 1 when any test failed.
# Warnings do not block a WooCommerce.com listing; failures do.

set -uo pipefail

plugin_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
slug="reviquo"
zip="${plugin_dir}/build/woo/${slug}.zip"
build=0
runtime_opts=()

static_tests=(zip validation security malware phpcompat)
runtime_tests=(activation activation-compat woo-api woo-e2e)

usage() {
	cat <<EOF
Usage: bin/qit-tests.sh [options] [test|group ...]

Tests:
  zip                Validate the zip contents (local, no upload)
  validation         Validation
  security           Security
  malware            Malware
  phpcompat          Code Compatibility (PHP 7.4 to 8.5)
  activation         Activation (local Docker)
  activation-compat  Activation with the compatibility extension set
  woo-api            WooCommerce core API tests (local Docker)
  woo-e2e            WooCommerce core E2E tests (local Docker)
  phpstan            PHPStan (not part of the submission report)
  plugin-check       Plugin Check (not part of the submission report)

Groups:
  static             ${static_tests[*]}
  runtime            ${runtime_tests[*]}
  all                static + runtime, the submission report set (default)

Options:
  --build            Build the package first (npm run build:woo)
  --zip PATH         Package to test (default: build/woo/${slug}.zip)
  --php VERSION      PHP version for the runtime tests (QIT default: 8.2)
  --wp VERSION       WordPress version for the runtime tests (default: stable)
  --woo VERSION      WooCommerce version for the runtime tests
  -h, --help         Show this help

Examples:
  npm run qit                          # full submission set
  npm run qit -- --build security      # rebuild, then security only
  npm run qit:runtime -- --php 7.4
EOF
}

selected=()

while [ $# -gt 0 ]; do
	case "$1" in
		--build) build=1 ;;
		--zip) zip="$(realpath -m "${2:?--zip needs a path}")"; shift ;;
		--php) runtime_opts+=(--php_version="${2:?--php needs a version}"); shift ;;
		--wp) runtime_opts+=(--wordpress_version="${2:?--wp needs a version}"); shift ;;
		--woo) runtime_opts+=(--woocommerce_version="${2:?--woo needs a version}"); shift ;;
		-h|--help) usage; exit 0 ;;
		static) selected+=("${static_tests[@]}") ;;
		runtime) selected+=("${runtime_tests[@]}") ;;
		all) selected+=("${static_tests[@]}" "${runtime_tests[@]}") ;;
		zip|validation|security|malware|phpcompat|activation|activation-compat|woo-api|woo-e2e|phpstan|plugin-check)
			selected+=("$1") ;;
		*) echo "Unknown test or option: $1" >&2; echo >&2; usage >&2; exit 2 ;;
	esac
	shift
done

if [ ${#selected[@]} -eq 0 ]; then
	selected=("${static_tests[@]}" "${runtime_tests[@]}")
fi

# Prefer the project's composer copy so everyone runs the same CLI version.
if [ -x "${plugin_dir}/vendor/bin/qit" ]; then
	qit="${plugin_dir}/vendor/bin/qit"
elif command -v qit >/dev/null 2>&1; then
	qit="$(command -v qit)"
else
	echo "QIT CLI not found. Run 'composer install' or install woocommerce/qit-cli globally." >&2
	exit 1
fi

needs_docker=0
for test in "${selected[@]}"; do
	case "${test}" in
		activation|activation-compat|woo-api|woo-e2e) needs_docker=1 ;;
	esac
done

if [ "${needs_docker}" -eq 1 ] && ! docker info >/dev/null 2>&1; then
	echo "Docker is not running; the runtime tests need it." >&2
	exit 1
fi

cd "${plugin_dir}" || exit 1

if [ "${build}" -eq 1 ]; then
	npm run build:woo || { echo "Build failed; no tests were run." >&2; exit 1; }
fi

if [ ! -f "${zip}" ]; then
	echo "Package not found: ${zip}" >&2
	echo "Build it with 'npm run build:woo' or pass --build." >&2
	exit 1
fi

# Runs one test with QIT's own live output. Returns QIT's exit status:
# 0 success, 3 warning, anything else failure.
run_test() {
	local common=(-n "${slug}" --zip="${zip}")

	case "$1" in
		zip) "${qit}" woo:validate-zip "${zip}" ;;
		validation) "${qit}" run:validation "${common[@]}" ;;
		security) "${qit}" run:security "${common[@]}" ;;
		malware) "${qit}" run:malware "${common[@]}" ;;
		phpcompat) "${qit}" run:phpcompatibility "${common[@]}" ;;
		phpstan) "${qit}" run:phpstan "${common[@]}" ;;
		plugin-check) "${qit}" run:plugin-check "${common[@]}" ;;
		activation) "${qit}" run:activation "${common[@]}" "${runtime_opts[@]}" ;;
		activation-compat) "${qit}" run:activation "${common[@]}" "${runtime_opts[@]}" --extension_set=compatibility ;;
		woo-api) "${qit}" run:woo-api "${common[@]}" "${runtime_opts[@]}" ;;
		woo-e2e) "${qit}" run:woo-e2e "${common[@]}" "${runtime_opts[@]}" ;;
	esac
}

results=()
failed=0

echo "Package: ${zip#"${plugin_dir}/"}"
echo "Tests:   ${selected[*]}"

for test in "${selected[@]}"; do
	echo
	echo "=== ${test} ==="
	started=${SECONDS}

	run_test "${test}"
	status=$?

	case "${status}" in
		0) label="passed" ;;
		3) label="warning" ;;
		*) label="FAILED (exit ${status})"; failed=1 ;;
	esac

	results+=("$(printf '%-18s %-18s %ss' "${test}" "${label}" "$(( SECONDS - started ))")")
done

echo
echo "=== Summary ==="
printf '%s\n' "${results[@]}"
echo
echo "Remote results: ${qit##*/} list-tests    Last local run: $("${qit}" qit:dir 2>/dev/null)last-run.json"

exit "${failed}"
