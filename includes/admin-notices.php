<?php // phpcs:ignore

/**
 * Admin notices displayed before the main plugin is initialized.
 *
 * @package Sktpr_Customer_Review
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether WooCommerce is active on the site or network.
 *
 * @return bool
 */
function sktpr_is_woocommerce_active() {
	$woocommerce_plugin = 'woocommerce/woocommerce.php';
	$active_plugins     = (array) get_option( 'active_plugins', array() );

	if ( in_array( $woocommerce_plugin, $active_plugins, true ) ) {
		return true;
	}

	if ( ! is_multisite() ) {
		return false;
	}

	$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

	return isset( $network_plugins[ $woocommerce_plugin ] );
}

/**
 * Register the WooCommerce dependency notice and its stylesheet.
 */
function sktpr_register_woocommerce_dependency_notice() {
	add_action( 'admin_enqueue_scripts', 'sktpr_enqueue_woocommerce_dependency_notice_styles' );
	add_action( 'admin_notices', 'sktpr_woocommerce_dependency_notice' );
	add_action( 'network_admin_notices', 'sktpr_woocommerce_dependency_notice' );
}

/**
 * Check whether the current admin screen can render the dependency notice.
 *
 * The core installer and updater screens stream their own progress markup and
 * carry no `.wp-header-end` anchor, so `common.js` relocates every `.notice`
 * into the middle of the install log. On those screens the notice also repeats
 * a call to action the screen is already performing.
 *
 * @return bool
 */
function sktpr_can_render_woocommerce_dependency_notice() {
	global $pagenow;

	$excluded_screens = array(
		'update.php',
		'update-core.php',
	);

	return ! in_array( $pagenow, $excluded_screens, true );
}

/**
 * Enqueue styles for the WooCommerce dependency notice.
 */
function sktpr_enqueue_woocommerce_dependency_notice_styles() {
	if ( ! sktpr_can_render_woocommerce_dependency_notice() || sktpr_is_woocommerce_active() ) {
		return;
	}

	$style_path = SKTPR_PLUGIN_DIR . 'assets/admin/css/admin-notices.min.css';
	$version    = file_exists( $style_path ) ? filemtime( $style_path ) : SKTPR_VERSION;

	wp_enqueue_style(
		'sktpr-admin-notices',
		SKTPR_PLUGIN_URI . 'assets/admin/css/admin-notices.min.css',
		array(),
		$version
	);
}

/**
 * Display a notice when WooCommerce is unavailable.
 */
function sktpr_woocommerce_dependency_notice() {
	if ( sktpr_is_woocommerce_active() || ! sktpr_can_render_woocommerce_dependency_notice() ) {
		return;
	}

	$woocommerce_plugin = 'woocommerce/woocommerce.php';
	$is_installed       = file_exists( WP_PLUGIN_DIR . '/' . $woocommerce_plugin );
	$action_url         = '';
	$action_label       = '';
	$description        = $is_installed
		? __( 'WooCommerce is installed but inactive. Activate it to start using ReviQuo.', 'product-reviews' )
		: __( 'Install WooCommerce to start collecting richer product reviews with ReviQuo.', 'product-reviews' );

	if ( $is_installed && current_user_can( 'activate_plugins' ) ) {
		$action_url   = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'activate',
					'plugin' => $woocommerce_plugin,
				),
				self_admin_url( 'plugins.php' )
			),
			'activate-plugin_' . $woocommerce_plugin
		);
		$action_label = __( 'Activate WooCommerce', 'product-reviews' );
	} elseif ( ! $is_installed && current_user_can( 'install_plugins' ) ) {
		$action_url   = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'install-plugin',
					'plugin' => 'woocommerce',
				),
				self_admin_url( 'update.php' )
			),
			'install-plugin_woocommerce'
		);
		$action_label = __( 'Install WooCommerce', 'product-reviews' );
	}

	?>
	<div class="notice sktpr-wc-notice" role="alert">
		<div class="sktpr-wc-notice__inner">
			<span class="sktpr-wc-notice__icon" aria-hidden="true">
				<svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M5.25 5.75H22.75C24.13 5.75 25.25 6.87 25.25 8.25V17.25C25.25 18.63 24.13 19.75 22.75 19.75H13L8.25 24V19.75H5.25C3.87 19.75 2.75 18.63 2.75 17.25V8.25C2.75 6.87 3.87 5.75 5.25 5.75Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
					<path d="M12 9.75L18 12.75L12 15.75V9.75Z" fill="currentColor"/>
				</svg>
			</span>
			<div class="sktpr-wc-notice__content">
				<strong class="sktpr-wc-notice__title"><?php esc_html_e( 'ReviQuo needs WooCommerce', 'product-reviews' ); ?></strong>
				<p class="sktpr-wc-notice__description"><?php echo esc_html( $description ); ?></p>
			</div>
			<?php if ( $action_url && $action_label ) : ?>
				<div class="sktpr-wc-notice__actions">
					<a class="sktpr-wc-notice__button" href="<?php echo esc_url( $action_url ); ?>"><?php echo esc_html( $action_label ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
