<?php // phpcs:ignore
/**
 * Freemius licensing bootstrap for the WordPress.org edition.
 *
 * This file is the only place the plugin touches the Freemius SDK. Marketplace
 * builds never load it: bin/make-marketplace-bootstrap.php replaces the require
 * in product-reviews.php with the SKTPR_MARKETPLACE_BUILD define, and the
 * edition distignore keeps this file out of those packages.
 *
 * @package Sktpr_Customer_Review
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pr_fs' ) ) {
	/**
	 * Initialize Freemius licensing.
	 *
	 * The product ID and public key belong to the original Product Reviews
	 * Freemius integration, so existing licenses continue to work.
	 *
	 * @return Freemius
	 */
	function pr_fs() {
		global $pr_fs;

		if ( ! isset( $pr_fs ) ) {
			// Include Freemius SDK.
			require_once SKTPR_PLUGIN_DIR . 'freemius/start.php';

			$pr_fs = fs_dynamic_init(
				array(
					'id'                  => '18762',
					'slug'                => 'product-reviews',
					'type'                => 'plugin',
					'public_key'          => 'pk_adc800eab5e7d982ec39068219938',
					'is_premium'          => true,
					// If your plugin is a serviceware, set this option to false.
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => true,
					// Automatically removed in the free version. If you're not using the
					// auto-generated free version, delete this line before uploading to wp.org.
					'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
					'trial'               => array(
						'days'               => 7,
						'is_require_payment' => true,
					),
					'has_affiliation'     => 'selected',
					'menu'                => array(
						'slug' => 'reviquo',
					),
				)
			);
		}

		return $pr_fs;
	}

	// Init Freemius.
	pr_fs();
	// Signal that SDK was initiated.
	do_action( 'pr_fs_loaded' );
}
