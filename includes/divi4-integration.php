<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Product Reviews module with the legacy Divi 4 builder.
 */
class Divi4_Integration {

	/**
	 * Register optional Divi 4 hooks.
	 */
	public function __construct() {
		add_action( 'et_builder_ready', array( $this, 'register_module' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_visual_builder_assets' ) );
	}

	/**
	 * Whether the Divi 5 builder handles this request instead.
	 *
	 * @return bool
	 */
	private function is_d5_builder_active() {
		return function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled();
	}

	/**
	 * Register the legacy module once the Divi 4 framework is loaded.
	 *
	 * @return void
	 */
	public function register_module() {
		if ( ! class_exists( '\\ET_Builder_Module' ) ) {
			return;
		}

		require_once SKTPR_PLUGIN_DIR . 'includes/divi4-reviews-module.php';
	}

	/**
	 * Enqueue the Divi 4 Visual Builder preview component and styles.
	 *
	 * @return void
	 */
	public function enqueue_visual_builder_assets() {
		if (
			! function_exists( 'et_core_is_fb_enabled' ) ||
			! et_core_is_fb_enabled() ||
			$this->is_d5_builder_active() ||
			! class_exists( '\\ET_Builder_Module' )
		) {
			return;
		}

		$script_path = SKTPR_PLUGIN_DIR . 'assets/divi4/reviews.js';
		$script_ver  = file_exists( $script_path ) ? filemtime( $script_path ) : SKTPR_VERSION;

		// The Divi 4 builder swaps the 'react' handle for its own React copy.
		wp_enqueue_script(
			'sktpr-divi4-visual-builder',
			SKTPR_PLUGIN_URI . 'assets/divi4/reviews.js',
			array( 'jquery', 'react' ),
			$script_ver,
			true
		);
		plugin()->scripts->enqueue_public_assets();
		plugin()->scripts->enqueue_slider_assets();

		$context_id = get_queried_object_id();
		if ( 'product' !== get_post_type( $context_id ) ) {
			$context_id = 0;
		}

		wp_add_inline_script(
			'sktpr-divi4-visual-builder',
			'window.sktprDivi4 = ' . wp_json_encode(
				array(
					'restUrl'          => esc_url_raw( rest_url( 'sktpr/v1/review-shortcodes/' ) ),
					'restNonce'        => wp_create_nonce( 'wp_rest' ),
					'contextProductId' => absint( $context_id ),
					'strings'          => array(
						'configure'   => __( 'Choose a shortcode setting in the Content panel.', 'product-reviews' ),
						'loading'     => __( 'Loading review display…', 'product-reviews' ),
						'unavailable' => __( 'The selected shortcode setting is unavailable.', 'product-reviews' ),
					),
				)
			) . ';',
			'before'
		);
	}
}
