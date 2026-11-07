<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Gutenberg block for saved review displays.
 */
class Review_Block {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ), 20 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_canvas_assets' ) );
	}

	/**
	 * Load the slider runtime into the (iframed) editor canvas so slider
	 * previews behave as they do on the storefront.
	 */
	public function enqueue_editor_canvas_assets() {
		if ( ! is_admin() ) {
			return;
		}

		plugin()->scripts->enqueue_slider_assets();
		wp_enqueue_script( 'sktpr_public' );
	}

	/**
	 * Register the dynamic review block and its editor assets.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		$editor_script_path    = SKTPR_PLUGIN_DIR . 'assets/blocks/reviews/index.js';
		$editor_style_path     = SKTPR_PLUGIN_DIR . 'assets/blocks/reviews/editor.css';
		$editor_script_version = file_exists( $editor_script_path ) ? filemtime( $editor_script_path ) : SKTPR_VERSION;
		$editor_style_version  = file_exists( $editor_style_path ) ? filemtime( $editor_style_path ) : SKTPR_VERSION;

		wp_register_script(
			'sktpr-reviews-block-editor',
			SKTPR_PLUGIN_URI . 'assets/blocks/reviews/index.js',
			array( 'wp-api-fetch', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n' ),
			$editor_script_version,
			true
		);
		wp_register_style(
			'sktpr-reviews-block-preview',
			SKTPR_PLUGIN_URI . 'assets/public/css/public.min.css',
			array(),
			SKTPR_VERSION
		);
		wp_register_style(
			'sktpr-reviews-block-editor',
			SKTPR_PLUGIN_URI . 'assets/blocks/reviews/editor.css',
			array( 'wp-edit-blocks', 'sktpr-reviews-block-preview' ),
			$editor_style_version
		);

		register_block_type(
			SKTPR_PLUGIN_DIR . 'blocks/reviews',
			array(
				'editor_script_handles' => array( 'sktpr-reviews-block-editor' ),
				'editor_style_handles'  => array( 'sktpr-reviews-block-editor' ),
				'render_callback'       => array( $this, 'render_block' ),
			)
		);

		wp_set_script_translations( 'sktpr-reviews-block-editor', 'product-reviews', SKTPR_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Supply saved display data to the block editor.
	 */
	public function enqueue_editor_assets() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_localize_script(
			'sktpr-reviews-block-editor',
			'sktprReviewsBlock',
			array(
				'items'     => plugin()->review_shortcodes->get_saved_items(),
				'canManage' => current_user_can( 'manage_options' ),
				'manageUrl' => admin_url( 'admin.php?page=sktpr-review-shortcode' ),
				'createUrl' => admin_url( 'admin.php?page=sktpr-review-shortcode&view=new' ),
			)
		);
	}

	/**
	 * Register the authenticated editor-preview endpoint.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'sktpr/v1',
			'/review-shortcodes/(?P<id>\d+)/preview',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'id'                 => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'context_product_id' => array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
				'callback'            => array( $this, 'get_preview' ),
			)
		);

		// Preview for page-builder modules configured inline (no saved display).
		register_rest_route(
			'sktpr/v1',
			'/review-shortcodes/inline-preview',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'settings'           => array(
						'type'    => 'object',
						'default' => array(),
					),
					'context_product_id' => array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
				'callback'            => array( $this, 'get_inline_preview' ),
			)
		);
	}

	/**
	 * Return preview markup for builder settings configured inline.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_inline_preview( $request ) {
		$settings = $request['settings'];

		return rest_ensure_response(
			array(
				'html' => plugin()->review_shortcodes->render_inline( is_array( $settings ) ? $settings : array(), absint( $request['context_product_id'] ), true ),
			)
		);
	}

	/**
	 * Return preview markup for one saved display.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_preview( $request ) {
		$id   = absint( $request['id'] );
		$post = get_post( $id );
		if ( ! $post || Review_Shortcodes::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return new \WP_Error( 'sktpr_review_display_missing', __( 'The selected review display could not be found.', 'product-reviews' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'html' => plugin()->review_shortcodes->render_saved_preview( $id, absint( $request['context_product_id'] ) ),
			)
		);
	}

	/**
	 * Render the selected shortcode on the storefront.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		$id = absint( $attributes['id'] ?? 0 );
		if ( ! $id ) {
			return '';
		}

		$content = plugin()->review_shortcodes->render_shortcode( array( 'id' => $id ) );
		if ( '' === $content ) {
			return '';
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'sktpr-reviews-block' ) );

		return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
	}
}
