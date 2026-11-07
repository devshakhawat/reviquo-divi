<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Feature policy for the full WooCommerce Marketplace edition.
 */
final class Features {

	const MAX_VIDEO_DURATION = 10;
	const FREE_VIDEO_DURATION = 2;
	const FREE_REMINDER_RULES = 1;

	/**
	 * Marketplace customers receive every local plugin feature.
	 *
	 * @return bool
	 */
	public static function has_pro_license() {
		return true;
	}

	/**
	 * Compatibility method shared with the WordPress.org edition.
	 *
	 * @return string[]
	 */
	public static function get_pro_setting_keys() {
		return array();
	}

	/**
	 * Normalize local feature settings for the full marketplace edition.
	 *
	 * @param array $settings Saved settings.
	 * @param array $defaults Default settings.
	 * @return array
	 */
	public static function apply_settings( $settings, $defaults ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$settings['video_duration'] = min( self::MAX_VIDEO_DURATION, max( 1, absint( $settings['video_duration'] ?? self::MAX_VIDEO_DURATION ) ) );
		return $settings;
	}

	/**
	 * Preserve all submitted settings because no marketplace feature is locked.
	 *
	 * @param array $submitted Submitted settings.
	 * @param array $stored Stored settings.
	 * @return array
	 */
	public static function preserve_locked_settings( $submitted, $stored ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return $submitted;
	}

	/**
	 * Return all reminder settings unchanged.
	 *
	 * @param array $settings Reminder settings.
	 * @return array
	 */
	public static function apply_email_settings( $settings ) {
		return $settings;
	}

	/**
	 * Preserve every submitted reminder rule.
	 *
	 * @param array $submitted Submitted reminder settings.
	 * @param array $stored Stored reminder settings.
	 * @return array
	 */
	public static function preserve_locked_email_rules( $submitted, $stored ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return $submitted;
	}

	/**
	 * Return all review reward settings unchanged.
	 *
	 * @param array $settings Review reward settings.
	 * @return array
	 */
	public static function apply_reward_settings( $settings ) {
		return $settings;
	}

	/**
	 * Preserve every submitted review reward setting.
	 *
	 * @param array $submitted Submitted reward settings.
	 * @param array $stored Stored reward settings.
	 * @return array
	 */
	public static function preserve_locked_reward_settings( $submitted, $stored ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return $submitted;
	}

	/**
	 * Admin UI state with no external licensing or upgrade actions.
	 *
	 * @return array
	 */
	public static function get_admin_license_data() {
		return array(
			'is_pro'         => true,
			'is_trial'       => false,
			'is_marketplace' => true,
			'upgrade_url'    => '',
			'account_url'    => '',
		);
	}
}
