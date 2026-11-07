<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Product Reviews module with Divi 5 when its APIs are available.
 */
class Divi5_Integration {

	/**
	 * Register optional Divi hooks.
	 */
	public function __construct() {
		add_action( 'divi_module_library_modules_dependency_tree', array( $this, 'register_dependency' ) );
		add_action( 'divi_visual_builder_assets_before_enqueue_scripts', array( $this, 'register_visual_builder_assets' ) );
	}

	/**
	 * Add the server-rendered module to Divi's dependency tree.
	 *
	 * @param object $dependency_tree Divi module dependency tree.
	 * @return void
	 */
	public function register_dependency( $dependency_tree ) {
		if ( ! interface_exists( '\\ET\\Builder\\Framework\\DependencyManagement\\Interfaces\\DependencyInterface' ) || ! method_exists( $dependency_tree, 'add_dependency' ) ) {
			return;
		}

		require_once SKTPR_PLUGIN_DIR . 'includes/divi5-reviews-module.php';
		$dependency_tree->add_dependency( new Divi5_Reviews_Module() );
	}

	/**
	 * Register the Visual Builder module and preview stylesheet.
	 *
	 * @return void
	 */
	public function register_visual_builder_assets() {
		if (
			! function_exists( 'et_core_is_fb_enabled' ) ||
			! function_exists( 'et_builder_d5_enabled' ) ||
			! et_core_is_fb_enabled() ||
			! et_builder_d5_enabled() ||
			! class_exists( '\\ET\\Builder\\VisualBuilder\\Assets\\PackageBuildManager' )
		) {
			return;
		}

		$script_path = SKTPR_PLUGIN_DIR . 'assets/divi5/reviews.js';
		$style_path  = SKTPR_PLUGIN_DIR . 'assets/public/css/public.min.css';
		$script_ver  = file_exists( $script_path ) ? filemtime( $script_path ) : SKTPR_VERSION;
		$style_ver   = file_exists( $style_path ) ? filemtime( $style_path ) : SKTPR_VERSION;

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			array(
				'name'    => 'sktpr-divi5-preview-style',
				'version' => $style_ver,
				'style'   => array(
					'src'                => SKTPR_PLUGIN_URI . 'assets/public/css/public.min.css',
					'deps'               => array(),
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				),
			)
		);

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			array(
				'name'    => 'sktpr-divi5-visual-builder',
				'version' => $script_ver,
				'script'  => array(
					'src'                => SKTPR_PLUGIN_URI . 'assets/divi5/reviews.js',
					'deps'               => array( 'divi-vendor-react', 'divi-module-library', 'divi-vendor-wp-hooks' ),
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				),
			)
		);

		// Slider runtime, so slider previews behave as they do on the storefront.
		// Package build names double as the WordPress script/style handles.
		$swiper_uri = SKTPR_PLUGIN_URI . 'assets/libs/swiper/';
		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			array(
				'name'    => 'sktpr-divi5-swiper',
				'version' => Scripts::SWIPER_VERSION,
				'script'  => array(
					'src'                => $swiper_uri . 'swiper-bundle.min.js',
					'deps'               => array(),
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				),
				'style'   => array(
					'src'                => $swiper_uri . 'swiper-bundle.min.css',
					'deps'               => array(),
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				),
			)
		);

		$public_path = SKTPR_PLUGIN_DIR . 'assets/public/js/public.min.js';
		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			array(
				'name'    => 'sktpr-divi5-public',
				'version' => file_exists( $public_path ) ? filemtime( $public_path ) : SKTPR_VERSION,
				'script'  => array(
					'src'                => SKTPR_PLUGIN_URI . 'assets/public/js/public.min.js',
					'deps'               => array( 'jquery', 'sktpr-divi5-swiper' ),
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				),
			)
		);

		// The Divi package script is registered by PackageBuildManager after this
		// hook, so inline data cannot attach to its handle yet. Print the config
		// just before head scripts are output instead.
		add_action( 'wp_print_scripts', array( $this, 'print_visual_builder_config' ), 1 );
	}

	/**
	 * Print the Visual Builder config before the module bundle loads.
	 *
	 * @return void
	 */
	public function print_visual_builder_config() {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;

		$context_id = get_queried_object_id();
		if ( 'product' !== get_post_type( $context_id ) ) {
			$context_id = 0;
		}
		$items = array_map(
			function ( $item ) {
				return array(
					'id'   => absint( $item['id'] ),
					'name' => $item['name'],
				);
			},
			plugin()->review_shortcodes->get_saved_items()
		);

		$config = array(
			'items'            => $items,
			'restUrl'          => esc_url_raw( rest_url( 'sktpr/v1/review-shortcodes/' ) ),
			'restNonce'        => wp_create_nonce( 'wp_rest' ),
			'contextProductId' => absint( $context_id ),
			'isPro'            => Features::has_pro_license(),
			'strings'          => array(
				'choose'      => __( 'Choose a shortcode setting', 'product-reviews' ),
				'configure'   => __( 'Choose a shortcode setting in the Content panel.', 'product-reviews' ),
				'loading'     => __( 'Loading review display…', 'product-reviews' ),
				'unavailable' => __( 'The selected shortcode setting is unavailable.', 'product-reviews' ),
			),
		);

		wp_print_inline_script_tag(
			'window.sktprDivi5 = ' . wp_json_encode( $config ) . ';',
			array( 'id' => 'sktpr-divi5-config' )
		);
	}
}
