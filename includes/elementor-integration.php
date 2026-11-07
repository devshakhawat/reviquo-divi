<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Product Reviews features with Elementor when it is available.
 */
class Elementor_Integration {

	/**
	 * Register optional Elementor hooks.
	 */
	public function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Add a dedicated Elementor panel category.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		if ( ! method_exists( $elements_manager, 'add_category' ) ) {
			return;
		}

		$elements_manager->add_category(
			'sktpr-product-reviews',
			array(
				'title' => __( 'Product Reviews', 'product-reviews' ),
				'icon'  => 'eicon-star',
			)
		);
	}

	/**
	 * Register assets before Elementor resolves widget dependencies.
	 *
	 * @return void
	 */
	public function register_assets() {
		plugin()->scripts->register_public_assets();
	}

	/**
	 * Register the saved-review-display widget.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		$widget = new Elementor_Reviews_Widget();
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( $widget );
		} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( $widget );
		}
	}
}
