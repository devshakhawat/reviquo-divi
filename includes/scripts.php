<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin shortcode.
 *
 * @since 1.0.0
 */
class Scripts {

	use Helpers;

	/**
	 * Whether the public assets have been configured for this request.
	 *
	 * @var bool
	 */
	private $public_assets_configured = false;

	/** Bundled Swiper version (assets/libs/swiper). */
	const SWIPER_VERSION = '11.2.10';

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'public_enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page.
	 */
	public function admin_enqueue_scripts( $hook ) {

		$admin_hooks = array(
			'reviquo_page_video-reviews-list',
			'toplevel_page_' . SKTPR_ADMIN_PAGE,
			'reviquo_page_sktpr-review-shortcode',
			'reviquo_page_skt-product-reviews-reminders',
		);

		if ( ! in_array( $hook, $admin_hooks, true ) ) {
			return;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Styles.
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'sktpr_admin', SKTPR_PLUGIN_URI . 'assets/admin/css/admin.min.css', array(), SKTPR_VERSION );

		if ( 'reviquo_page_skt-product-reviews-reminders' === $hook ) {
			return;
		}

		if ( 'reviquo_page_sktpr-review-shortcode' === $hook ) {
			$this->register_public_assets();
			wp_enqueue_style( 'sktpr_public' );
			wp_enqueue_script( 'sktpr_public' );
			$this->enqueue_slider_assets();
		}

		// Scripts.
		wp_enqueue_script( 'sktpr_admin', SKTPR_PLUGIN_URI . 'assets/admin/js/admin.min.js', array( 'wp-element' ), SKTPR_VERSION, true );

		wp_localize_script(
			'sktpr_admin',
			'sktpr_plugin',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'sktpr_plugin_nonce' ),
				'settings'        => $this->get_settings(),
				'email_settings'  => $this->get_email_settings(),
				'reward_settings' => $this->get_stored_reward_settings(),
				'currency_symbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'licensing'       => Features::get_admin_license_data(),
				'order_statuses'  => plugin()->email_settings->get_order_statuses(),
				'email_preview'   => array(
					'customer_name' => __( 'Alex Johnson', 'product-reviews' ),
					'order_number'  => '1042',
					'order_date'    => date_i18n( get_option( 'date_format' ) ),
					'site_name'     => get_bloginfo( 'name' ),
					'site_url'      => home_url( '/' ),
					'product_name'  => __( 'Sample product', 'product-reviews' ),
					'product_image' => function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'thumbnail' ) : '',
				),
				'version'         => SKTPR_VERSION,
				'admin_page'      => 'reviquo_page_sktpr-review-shortcode' === $hook ? 'shortcodes' : 'settings',
				'navigation'      => array(
					'shortcodes' => admin_url( 'admin.php?page=sktpr-review-shortcode' ),
					'create'     => admin_url( 'admin.php?page=sktpr-review-shortcode&view=new' ),
					'settings'   => admin_url( 'admin.php?page=' . SKTPR_ADMIN_PAGE ),
					// Marketplace editions supply their own listing channels; an empty
					// URL hides the corresponding link in the admin UI.
					'docs'       => defined( 'SKTPR_MARKETPLACE_BUILD' ) ? '' : 'https://wordpress.org/plugins/product-reviews/',
					'help'       => defined( 'SKTPR_MARKETPLACE_BUILD' ) ? '' : 'https://wordpress.org/support/plugin/product-reviews/',
				),
			)
		);
	}

	/**
	 * Enqueue public-facing scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function public_enqueue_scripts() {
		$this->register_public_assets();

		if ( ! $this->should_enqueue_public_assets() ) {
			return;
		}

		$this->enqueue_public_assets();
	}

	/**
	 * Register public assets for WordPress and visual-builder dependency APIs.
	 *
	 * @return void
	 */
	public function register_public_assets() {
		$style_path     = SKTPR_PLUGIN_DIR . 'assets/public/css/public.min.css';
		$script_path    = SKTPR_PLUGIN_DIR . 'assets/public/js/public.min.js';
		$style_version  = file_exists( $style_path ) ? filemtime( $style_path ) : SKTPR_VERSION;
		$script_version = file_exists( $script_path ) ? filemtime( $script_path ) : SKTPR_VERSION;

		wp_register_style( 'sktpr_public', SKTPR_PLUGIN_URI . 'assets/public/css/public.min.css', array(), $style_version );
		wp_register_script( 'sktpr_public', SKTPR_PLUGIN_URI . 'assets/public/js/public.min.js', array( 'jquery' ), $script_version, true );

		// Swiper powers the slider layout of saved review displays. It is only
		// enqueued where a slider renders; public.js initializes it when present.
		wp_register_style( 'sktpr-swiper', SKTPR_PLUGIN_URI . 'assets/libs/swiper/swiper-bundle.min.css', array(), self::SWIPER_VERSION );
		wp_register_script( 'sktpr-swiper', SKTPR_PLUGIN_URI . 'assets/libs/swiper/swiper-bundle.min.js', array(), self::SWIPER_VERSION, true );
	}

	/**
	 * Enqueue the Swiper library used by the slider layout.
	 *
	 * @return void
	 */
	public function enqueue_slider_assets() {
		$this->register_public_assets();

		wp_enqueue_style( 'sktpr-swiper' );
		wp_enqueue_script( 'sktpr-swiper' );
	}

	/**
	 * Determine whether the current page uses the public review interface.
	 *
	 * @return bool
	 */
	private function should_enqueue_public_assets() {
		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		$post = get_queried_object();

		return $post instanceof \WP_Post && (
			has_shortcode( $post->post_content, Review_Shortcodes::SHORTCODE ) ||
			has_block( 'sktpr/reviews', $post ) ||
			has_block( 'sktpr/divi5-reviews', $post ) ||
			// Divi 4 module shortcode; matched as a substring because the tag
			// only exists once the Divi builder has registered its modules.
			false !== strpos( $post->post_content, '[sktpr_divi4_reviews' )
		);
	}

	/**
	 * Enqueue and configure the public review assets.
	 *
	 * This is public so shortcode rendering can provide a fallback for shortcodes
	 * added outside the main post content, such as by a widget or page builder.
	 */
	public function enqueue_public_assets() {
		$this->register_public_assets();

		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_style( 'font-awesome', SKTPR_PLUGIN_URI . 'assets/libs/font-awesome/css/font-awesome.min.css', array(), SKTPR_VERSION );
		}

		wp_enqueue_style( 'sktpr_public' );
		wp_enqueue_script( 'sktpr_public' );

		if ( $this->public_assets_configured ) {
			return;
		}

		$settings = $this->get_settings();
		plugin()->generate_css->generate_custom_css( $settings );

		// Our styled badge replaces WooCommerce's plain "(verified owner)" text.
		if ( ! empty( $settings['show_verified_badge'] ?? true ) ) {
			wp_add_inline_style( 'sktpr_public', '.woocommerce-review__verified{display:none;}' );
		}

		// Pass settings to JavaScript.
		wp_localize_script(
			'sktpr_public',
			'sktpr_settings',
			array(
				// A requirement only applies while its uploader is shown, matching Save_Video::enforce_required_media().
				'required_video_recording' => ! empty( $settings['enable_video_btn'] ) && ! empty( $settings['required_video_recording'] ),
				'required_file_upload'     => ! empty( $settings['show_file_uploader'] ) && ! empty( $settings['required_file_upload'] ),
				'required_image_upload'    => ! empty( $settings['show_image_uploader'] ) && ! empty( $settings['required_image_upload'] ),
				'ajax_url'                 => admin_url( 'admin-ajax.php' ),
				// Keys match the sktprString() calls in dev/public/js/public.js; its English fallbacks mirror these.
				'strings'                  => array(
					'camera_blocked'       => __( 'Camera access was blocked. Please allow camera and microphone permissions for this site in your browser, then try again.', 'product-reviews' ),
					'camera_missing'       => __( 'No camera or microphone was found on this device.', 'product-reviews' ),
					'camera_busy'          => __( 'Your camera appears to be in use by another application. Close it and try again.', 'product-reviews' ),
					'camera_error'         => __( 'Could not access your camera or microphone.', 'product-reviews' ),
					'https_required'       => __( 'Camera recording requires a secure connection. Please open this page over HTTPS and try again.', 'product-reviews' ),
					'camera_unsupported'   => __( 'Camera recording is not supported in this browser.', 'product-reviews' ),
					'recorder_unsupported' => __( 'Video recording is not supported in this browser. Please upload a video file instead.', 'product-reviews' ),
					'camera_not_ready'     => __( 'The camera is not ready. Please allow camera access when prompted, or reopen this window to try again.', 'product-reviews' ),
					'start_recording'      => __( 'Start Recording', 'product-reviews' ),
					'record_again'         => __( 'Record again', 'product-reviews' ),
					'remove_photo'         => __( 'Remove photo', 'product-reviews' ),
					/* translators: 1: number of photos added, 2: maximum number of photos. */
					'images_added'         => __( '%1$d of %2$d added', 'product-reviews' ),
					/* translators: %d: maximum number of photos. */
					'images_up_to'         => __( 'up to %d', 'product-reviews' ),
					/* translators: %d: maximum number of photos. */
					'images_limit'         => __( 'You can upload up to %d photos.', 'product-reviews' ),
					'review_media'         => __( 'Review media', 'product-reviews' ),
					'close'                => __( 'Close', 'product-reviews' ),
					'previous'             => __( 'Previous', 'product-reviews' ),
					'next'                 => __( 'Next', 'product-reviews' ),
					'no_reviews'           => __( 'No reviews match the selected filters.', 'product-reviews' ),
					'load_more'            => __( 'Load More', 'product-reviews' ),
					'slider_prev'          => __( 'Previous reviews', 'product-reviews' ),
					'slider_next'          => __( 'Next reviews', 'product-reviews' ),
					'record_required'      => __( 'Please record a video testimonial before submitting your review.', 'product-reviews' ),
					'video_required'       => __( 'Please upload a video file before submitting your review.', 'product-reviews' ),
					'image_required'       => __( 'Please upload at least one photo before submitting your review.', 'product-reviews' ),
				),
			)
		);

		$this->public_assets_configured = true;
	}
}
