<?php // phpcs:ignore
/**
 * Assert that the packaged marketplace feature policy really does unlock everything.
 *
 * The other build checks are negative: they prove the package contains no
 * Freemius SDK, no updater and no upgrade prompts. This one is positive - it
 * loads the feature policy that is about to ship and exercises the decisions
 * that separate Free from Pro, so a package that quietly ships the gated policy
 * fails the build instead of reaching a customer.
 *
 * Usage: php bin/check-marketplace-features.php <staged-features.php>
 *
 * @package Sktpr_Customer_Review
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

$features_file = $argv[1] ?? '';

if ( '' === $features_file || ! is_readable( $features_file ) ) {
	fwrite( STDERR, "check-marketplace-features: usage: check-marketplace-features.php <staged-features.php>\n" );
	exit( 1 );
}

// Enough of WordPress to exercise the policy in isolation.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'SKTPR_MARKETPLACE_BUILD' ) ) {
	define( 'SKTPR_MARKETPLACE_BUILD', true );
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * WordPress absint().
	 *
	 * @param mixed $value Value to convert.
	 * @return int
	 */
	function absint( $value ) {
		return abs( intval( $value ) );
	}
}

require_once $features_file;

if ( ! class_exists( '\\SKTPREVIEW\\Features' ) ) {
	fwrite( STDERR, "check-marketplace-features: the staged file does not declare SKTPREVIEW\\Features.\n" );
	exit( 1 );
}

$features = '\\SKTPREVIEW\\Features';
$defaults = array(
	'enable_rich_snippets' => false,
	'show_verified_badge'  => false,
	'video_duration'       => 2,
);

// A site that had a Pro license: every one of these must survive the policy.
$stored = array(
	'enable_rich_snippets' => true,
	'show_verified_badge'  => true,
	'video_duration'       => 10,
);

$applied = call_user_func( array( $features, 'apply_settings' ), $stored, $defaults );
$license = call_user_func( array( $features, 'get_admin_license_data' ) );
$rules   = call_user_func( array( $features, 'apply_email_settings' ), array( 'rules' => array( 'a', 'b', 'c' ) ) );

$checks = array(
	'Pro features are licensed'              => true === call_user_func( array( $features, 'has_pro_license' ) ),
	'full webcam recording length is allowed' => constant( $features . '::MAX_VIDEO_DURATION' ) === $applied['video_duration'],
	'rich snippets are not gated'            => true === $applied['enable_rich_snippets'],
	'verified badges are not gated'          => true === $applied['show_verified_badge'],
	'no setting is treated as Pro-only'      => array() === call_user_func( array( $features, 'get_pro_setting_keys' ) ),
	'reminder rules are not capped'          => 3 === count( $rules['rules'] ),
	'saved settings are never rewritten'     => $stored === call_user_func( array( $features, 'preserve_locked_settings' ), $stored, $defaults ),
	'reminder rules are never rewritten'     => $rules === call_user_func( array( $features, 'preserve_locked_email_rules' ), $rules, array() ),
	'admin UI reports a Pro install'         => true === ( $license['is_pro'] ?? null ),
	'admin UI reports a marketplace install' => true === ( $license['is_marketplace'] ?? null ),
	'no trial is advertised'                 => false === ( $license['is_trial'] ?? null ),
	'no upgrade URL is advertised'           => '' === ( $license['upgrade_url'] ?? null ),
	'no account URL is advertised'           => '' === ( $license['account_url'] ?? null ),
);

$failed = array();

foreach ( $checks as $label => $passed ) {
	if ( ! $passed ) {
		$failed[] = $label;
	}
}

if ( $failed ) {
	foreach ( $failed as $label ) {
		fwrite( STDERR, "check-marketplace-features: FAILED - {$label}.\n" );
	}
	exit( 1 );
}

echo 'Marketplace feature policy unlocks everything (' . count( $checks ) . " checks).\n";
