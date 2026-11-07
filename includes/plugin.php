<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * @return void
 */
class Plugin {

	/**
	 * Define Instance.
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Returns an instance of the Plugin class.
	 *
	 * @return self
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public $hooks; // phpcs:ignore
	public $scripts; // phpcs:ignore
	public $generate_css; // phpcs:ignore
	public $video_btn; // phpcs:ignore
	public $admin_menu; // phpcs:ignore
	public $save_video; // phpcs:ignore
	public $display_video; // phpcs:ignore
	public $email_reminders; // phpcs:ignore
	public $email_settings; // phpcs:ignore
	public $review_rewards; // phpcs:ignore
	public $database; // phpcs:ignore
	public $reminders_list; // phpcs:ignore
	public $video_reviews_list; // phpcs:ignore
	public $privacy; // phpcs:ignore
	public $rating_summary; // phpcs:ignore
	public $review_filters; // phpcs:ignore
	public $structured_data; // phpcs:ignore
	public $review_shortcodes; // phpcs:ignore
	public $review_block; // phpcs:ignore
	public $headless_api; // phpcs:ignore
	public $elementor; // phpcs:ignore

	/**
	 * Constructor for the class.
	 */
	public function __construct() {

		$this->hooks              = new Hooks();
		$this->scripts            = new Scripts();
		$this->video_btn          = new Video_Btn();
		$this->admin_menu         = new Admin_Menu();
		$this->save_video         = new Save_Video();
		$this->display_video      = new Display_Video();
		$this->generate_css       = new Generate_CSS();
		$this->email_reminders    = new Email_Reminders();
		$this->email_settings     = new Email_Settings();
		$this->review_rewards     = new Review_Rewards();
		$this->database           = new Database();
		$this->reminders_list     = new Reminders_List();
		$this->video_reviews_list = new Video_Reviews_List();
		$this->privacy            = new Privacy();
		$this->review_shortcodes  = new Review_Shortcodes();
		$this->review_block       = new Review_Block();
		$this->headless_api       = new Headless_API();
		$this->elementor          = new Elementor_Integration();
		$this->rating_summary     = new Rating_Summary();
		$this->review_filters     = new Review_Filters();
		if ( Features::has_pro_license() ) {
			$this->structured_data = new Structured_Data();
		}

		new Template_Loader();
	}
}

/**
 * Returns an instance of the Plugin class.
 *
 * @return Plugin
 */
function plugin() { // phpcs:ignore
	return Plugin::get_instance();
}

add_action(
	'init',
	function () {
		plugin();
	},
	0
);
