<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

return array(
	'Hooks'                    => 'includes/hooks.php',
	'Scripts'                  => 'includes/scripts.php',
	'Template_Loader'          => 'includes/template-loader.php',
	'Video_Btn'                => 'includes/video-btn.php',
	'Admin_Menu'               => 'includes/admin-menu.php',
	'Save_Video'               => 'includes/save-video.php',
	'Display_Video'            => 'includes/display-video.php',
	'Rating_Summary'           => 'includes/rating-summary.php',
	'Review_Filters'           => 'includes/review-filters.php',
	'Structured_Data'          => 'includes/structured-data.php',
	'Helpers'                  => 'includes/helpers.php',
	'Features'                 => 'includes/features.php',
	'Generate_CSS'             => 'includes/generate-css.php',
	'Email_Reminders'          => 'includes/email-reminders.php',
	'Email_Settings'           => 'includes/email-settings.php',
	'Review_Rewards'           => 'includes/review-rewards.php',
	'Reminders_List'           => 'includes/reminders-list.php',
	'Database'                 => 'includes/database.php',
	'Privacy'                  => 'includes/privacy.php',
	'Migration'                => 'includes/migration.php',
	'Video_Reviews_List'       => 'includes/video-reviews-list.php',
	'Video_Reviews_Table'      => 'includes/video-reviews-table.php',
	'Review_Shortcodes'        => 'includes/review-shortcodes.php',
	'Review_Block'             => 'includes/review-block.php',
	'Headless_API'             => 'includes/headless-api.php',
	'Elementor_Integration'    => 'includes/elementor-integration.php',
	'Elementor_Reviews_Widget' => 'includes/elementor-reviews-widget.php',
	'Divi5_Integration'        => 'includes/divi5-integration.php',
	'Divi4_Integration'        => 'includes/divi4-integration.php',
);
