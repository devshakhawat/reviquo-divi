<?php // phpcs:ignore

/**
 * Plugin Name: ReviQuo
 * Description: Let customers add webcam recordings, video files and photos to WooCommerce product reviews, with a rating summary, review filters, verified-buyer badges, rich snippets and automated review-reminder emails.
 * Version: 3.3.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 11.1.2
 * Author: Shakhawat
 * Author URI: https://profiles.wordpress.org/devshakhawat/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: product-reviews
 * Domain Path: /languages
 *
 * @package   Sktpr_Customer_Review
 */

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defining constants.
 */
if ( ! defined( 'SKTPR_VERSION' ) ) {
	define( 'SKTPR_VERSION', '3.3.0' );
}
if ( ! defined( 'SKTPR_PLUGIN_FILE' ) ) {
	define( 'SKTPR_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'SKTPR_PLUGIN_DIR' ) ) {
	define( 'SKTPR_PLUGIN_DIR', trailingslashit( plugin_dir_path( SKTPR_PLUGIN_FILE ) ) );
}
if ( ! defined( 'SKTPR_PLUGIN_URI' ) ) {
	define( 'SKTPR_PLUGIN_URI', trailingslashit( plugins_url( '', SKTPR_PLUGIN_FILE ) ) );
}

require_once SKTPR_PLUGIN_DIR . 'includes/admin-notices.php';

if ( ! sktpr_is_woocommerce_active() ) {
	sktpr_register_woocommerce_dependency_notice();
	return;
}

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// sktpr:edition-seam:start
require_once SKTPR_PLUGIN_DIR . 'includes/licensing.php';
// sktpr:edition-seam:end

/**
 * Load essential files
 */
require_once SKTPR_PLUGIN_DIR . 'includes/functions.php';
require_once SKTPR_PLUGIN_DIR . 'includes/autoloader.php';
require_once SKTPR_PLUGIN_DIR . 'includes/plugin.php';

// Register optional builder hooks early enough for Divi 5's dependency tree.
new SKTPREVIEW\Divi5_Integration();
new SKTPREVIEW\Divi4_Integration();

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, 'SKTPREVIEW\\sktpr_activation_setup' );

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( __FILE__, 'SKTPREVIEW\\sktpr_deactivation_cleanup' );
