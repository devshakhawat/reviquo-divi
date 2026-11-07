<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Elementor widget for a saved Product Reviews shortcode setting.
 *
 * The parent class is only available when Elementor is installed, so the
 * inherited methods this widget calls are declared for static analysis.
 *
 * @method void  start_controls_section( string $section_id, array $args = array() )
 * @method bool  add_control( string $id, array $args, array $options = array() )
 * @method void  end_controls_section()
 * @method mixed get_settings_for_display( ?string $setting_key = null )
 */
class Elementor_Reviews_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget identifier.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'sktpr-product-reviews';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Product Reviews', 'product-reviews' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-shortcode';
	}

	/**
	 * Elementor panel category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'sktpr-product-reviews' );
	}

	/**
	 * Widget search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'reviews', 'product reviews', 'woocommerce', 'shortcode', 'rating' );
	}

	/**
	 * Refresh the server preview after a setting changes.
	 *
	 * @return bool
	 */
	public function is_reload_preview_required() {
		return true;
	}

	/**
	 * Public stylesheet dependency.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'sktpr_public', 'sktpr-swiper' );
	}

	/**
	 * Public script dependencies for filters, Load More and the slider layout.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'sktpr-swiper', 'sktpr_public' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'sktpr_content',
			array(
				'label' => __( 'Review Display', 'product-reviews' ),
			)
		);

		$options = array( '' => __( 'Choose a shortcode setting', 'product-reviews' ) );
		foreach ( plugin()->review_shortcodes->get_saved_items() as $item ) {
			$options[ (string) absint( $item['id'] ) ] = sprintf(
				/* translators: 1: saved review display name, 2: shortcode ID. */
				__( '%1$s (#%2$d)', 'product-reviews' ),
				$item['name'],
				absint( $item['id'] )
			);
		}

		$this->add_control(
			'shortcode_id',
			array(
				'label'       => __( 'Shortcode setting', 'product-reviews' ),
				// @phpstan-ignore-next-line
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $options,
				'default'     => '',
				'label_block' => true,
				'description' => __( 'Select a saved display created under ReviQuo > Review Shortcode.', 'product-reviews' ),
			)
		);

		if ( current_user_can( 'manage_options' ) ) {
			$this->add_control(
				'manage_shortcodes',
				array(
					// @phpstan-ignore-next-line
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw'  => sprintf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( admin_url( 'admin.php?page=sktpr-review-shortcode' ) ),
						esc_html__( 'Manage shortcode settings', 'product-reviews' )
					),
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Render the selected review display.
	 *
	 * @return void
	 */
	protected function render() {
		$id = absint( $this->get_settings_for_display( 'shortcode_id' ) );
		if ( ! $id ) {
			$this->render_editor_notice( __( 'Choose a shortcode setting to display product reviews.', 'product-reviews' ) );
			return;
		}

		$content = plugin()->review_shortcodes->render_shortcode( array( 'id' => $id ) );
		if ( '' === $content ) {
			$this->render_editor_notice( __( 'The selected shortcode setting is unavailable.', 'product-reviews' ) );
			return;
		}

		echo '<div class="sktpr-elementor-reviews">' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Return the equivalent shortcode in Elementor plain-content exports.
	 *
	 * @return void
	 */
	public function render_plain_content() {
		$id = absint( $this->get_settings_for_display( 'shortcode_id' ) );
		if ( $id ) {
			echo esc_html( sprintf( '[%s id="%d"]', Review_Shortcodes::SHORTCODE, $id ) );
		}
	}

	/**
	 * Show configuration feedback only inside the Elementor editor.
	 *
	 * @param string $message Notice text.
	 * @return void
	 */
	private function render_editor_notice( $message ) {
		// @phpstan-ignore-next-line
		if ( ! isset( \Elementor\Plugin::$instance->editor ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		printf(
			'<div class="sktpr-elementor-placeholder" role="status">%s</div>',
			esc_html( $message )
		);
	}
}
