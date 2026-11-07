<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Menu
 *
 * Handles the admin menu for the SKT Customer Review plugin.
 */
class Admin_Menu {

	use Helpers;

	/**
	 * Admin_Menu constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'in_admin_header', array( $this, 'suppress_admin_notices' ), PHP_INT_MAX );
	}

	/**
	 * Suppress all admin notices on ReviQuo admin pages.
	 */
	public function suppress_admin_notices() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, $this->get_admin_screen_ids(), true ) ) {
			return;
		}

		$notice_hooks = array(
			'admin_notices',
			'all_admin_notices',
			'network_admin_notices',
			'user_admin_notices',
		);

		foreach ( $notice_hooks as $hook_name ) {
			remove_all_actions( $hook_name );
		}
	}

	/**
	 * Return the screen IDs owned by this plugin.
	 *
	 * @return string[]
	 */
	private function get_admin_screen_ids() {
		return array(
			'toplevel_page_' . SKTPR_ADMIN_PAGE,
			'reviquo_page_sktpr-review-shortcode',
			'reviquo_page_video-reviews-list',
			'reviquo_page_skt-product-reviews-reminders',
		);
	}

	/**
	 * Adds the admin menu for the SKT Customer Review plugin.
	 */
	public function add_admin_menu() {

		// The menu title is deliberately not translated: WordPress derives the
		// submenu page hook suffixes (reviquo_page_*) and the admin body class
		// from sanitize_title() of this label, and both are matched literally.
		add_menu_page(
			'ReviQuo',
			'ReviQuo',
			'manage_options',
			SKTPR_ADMIN_PAGE,
			'',
			$this->get_menu_icon(),
			50
		);

		add_submenu_page(
			SKTPR_ADMIN_PAGE,
			__( 'Review Settings', 'product-reviews' ),
			__( 'Review Settings', 'product-reviews' ),
			'manage_options',
			SKTPR_ADMIN_PAGE,
			array( $this, 'get_menu_page_data' ),
			10
		);

		add_submenu_page(
			SKTPR_ADMIN_PAGE,
			__( 'Review Shortcode', 'product-reviews' ),
			__( 'Review Shortcode', 'product-reviews' ),
			'manage_options',
			'sktpr-review-shortcode',
			array( $this, 'get_shortcode_page_data' ),
			20
		);
	}

	/**
	 * Return the admin-menu icon as a local inline SVG data URI.
	 *
	 * @return string
	 */
	private function get_menu_icon() {
		$icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160"><defs><linearGradient id="ga" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#ffffff"/></linearGradient></defs><g fill="#ffb627" stroke="#ffb627" stroke-width="3" stroke-linejoin="round"><path transform="translate(50,20) scale(0.78)" d="M0,-10 L2.47,-3.4 L9.51,-3.09 L3.99,1.3 L5.88,8.09 L0,4.2 L-5.88,8.09 L-3.99,1.3 L-9.51,-3.09 L-2.47,-3.4 Z"/><path transform="translate(80,15) scale(1.05)" d="M0,-10 L2.47,-3.4 L9.51,-3.09 L3.99,1.3 L5.88,8.09 L0,4.2 L-5.88,8.09 L-3.99,1.3 L-9.51,-3.09 L-2.47,-3.4 Z"/><path transform="translate(110,20) scale(0.78)" d="M0,-10 L2.47,-3.4 L9.51,-3.09 L3.99,1.3 L5.88,8.09 L0,4.2 L-5.88,8.09 L-3.99,1.3 L-9.51,-3.09 L-2.47,-3.4 Z"/></g><path fill="url(#ga)" d="M44 40 H116 C131.5 40 144 52.5 144 68 V98 C144 113.5 131.5 126 116 126 H74 L53 146 C49 149.8 43 147 43 141.5 V126 H44 C28.5 126 16 113.5 16 98 V68 C16 52.5 28.5 40 44 40 Z"/><path d="M72 64 L102 82 L72 100 Z" fill="#ff694d" stroke="#ff694d" stroke-width="10" stroke-linejoin="round"/></svg>';

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return 'data:image/svg+xml;base64,' . base64_encode( $icon_svg );
	}

	/**
	 * Outputs the content for the Video Reviews admin page.
	 */
	public function get_menu_page_data() {
		include_once Template_Loader::locate_template( 'admin-settings.php' );
	}

	/**
	 * Outputs the Review Shortcode builder root.
	 */
	public function get_shortcode_page_data() {
		include_once Template_Loader::locate_template( 'review-shortcodes.php' );
	}
}
