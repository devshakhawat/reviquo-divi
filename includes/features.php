<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Central Free/Pro feature and license policy.
 */
final class Features {

	/** Maximum supported webcam recording length. */
	const MAX_VIDEO_DURATION = 10;

	/** Maximum webcam recording length available without Pro. */
	const FREE_VIDEO_DURATION = 2;

	/** Maximum reminder rules available without Pro. */
	const FREE_REMINDER_RULES = 1;

	/**
	 * Whether this installation can execute Pro features.
	 *
	 * Freemius returns true for an active paid license or active trial while
	 * running the premium build.
	 *
	 * @return bool
	 */
	public static function has_pro_license() {
		if ( defined( 'SKTPR_MARKETPLACE_BUILD' ) && SKTPR_MARKETPLACE_BUILD ) {
			return true;
		}

		return function_exists( 'pr_fs' ) && pr_fs()->is_premium() && pr_fs()->can_use_premium_code();
	}

	/**
	 * Settings which must retain their stored values while a license is inactive.
	 *
	 * @return string[]
	 */
	public static function get_pro_setting_keys() {
		return array(
			'enable_rich_snippets',
			'show_verified_badge',
		);
	}

	/**
	 * Return settings as they are allowed to execute for the current license.
	 * Stored Pro values are not destroyed when a license expires.
	 *
	 * @param array $settings Stored settings.
	 * @param array $defaults Default settings.
	 * @return array
	 */
	public static function apply_settings( $settings, $defaults ) {
		$max_duration               = self::has_pro_license() ? self::MAX_VIDEO_DURATION : self::FREE_VIDEO_DURATION;
		$settings['video_duration'] = min( $max_duration, max( 1, absint( $settings['video_duration'] ?? self::FREE_VIDEO_DURATION ) ) );

		if ( self::has_pro_license() ) {
			return $settings;
		}

		foreach ( self::get_pro_setting_keys() as $key ) {
			if ( array_key_exists( $key, $defaults ) ) {
				$settings[ $key ] = $defaults[ $key ];
			}
		}

		$settings['enable_rich_snippets'] = false;
		$settings['show_verified_badge']  = false;

		return $settings;
	}

	/**
	 * Prevent a Free-plan AJAX request from overwriting stored Pro settings.
	 *
	 * @param array $submitted Submitted settings.
	 * @param array $stored Existing stored settings.
	 * @return array
	 */
	public static function preserve_locked_settings( $submitted, $stored ) {
		if ( self::has_pro_license() ) {
			return $submitted;
		}

		foreach ( self::get_pro_setting_keys() as $key ) {
			if ( array_key_exists( $key, $stored ) ) {
				$submitted[ $key ] = $stored[ $key ];
			}
		}

		$stored_duration = absint( $stored['video_duration'] ?? self::FREE_VIDEO_DURATION );
		if ( $stored_duration > self::FREE_VIDEO_DURATION ) {
			$submitted['video_duration'] = min( self::MAX_VIDEO_DURATION, $stored_duration );
		} else {
			$submitted['video_duration'] = min( self::FREE_VIDEO_DURATION, max( 1, absint( $submitted['video_duration'] ?? self::FREE_VIDEO_DURATION ) ) );
		}

		return $submitted;
	}

	/**
	 * Return email settings as allowed by the current license.
	 *
	 * @param array $settings Stored email settings.
	 * @return array
	 */
	public static function apply_email_settings( $settings ) {
		if ( ! self::has_pro_license() && isset( $settings['rules'] ) && is_array( $settings['rules'] ) ) {
			$settings['rules'] = array_slice( $settings['rules'], 0, self::FREE_REMINDER_RULES );
		}

		return $settings;
	}

	/**
	 * Preserve hidden Pro reminder rules while a Free user edits their first rule.
	 *
	 * @param array $submitted License-limited submitted settings.
	 * @param array $stored Existing stored settings.
	 * @return array
	 */
	public static function preserve_locked_email_rules( $submitted, $stored ) {
		if ( self::has_pro_license() || empty( $submitted['rules'] ) ) {
			return $submitted;
		}

		$stored_rules       = isset( $stored['rules'] ) && is_array( $stored['rules'] ) ? $stored['rules'] : array();
		$pro_rules          = array_slice( $stored_rules, self::FREE_REMINDER_RULES );
		$submitted['rules'] = array_merge( array_slice( $submitted['rules'], 0, self::FREE_REMINDER_RULES ), $pro_rules );

		return $submitted;
	}

	/**
	 * Return review reward settings as allowed by the current license.
	 * Review rewards are a Pro feature; stored values survive license expiry.
	 *
	 * @param array $settings Stored reward settings.
	 * @return array
	 */
	public static function apply_reward_settings( $settings ) {
		if ( ! self::has_pro_license() ) {
			$settings['enable_review_rewards'] = false;
		}

		return $settings;
	}

	/**
	 * Prevent a Free-plan AJAX request from overwriting stored reward settings.
	 *
	 * @param array $submitted Submitted settings.
	 * @param array $stored Existing stored settings.
	 * @return array
	 */
	public static function preserve_locked_reward_settings( $submitted, $stored ) {
		return self::has_pro_license() ? $submitted : $stored;
	}

	/**
	 * Data exposed to the admin UI for licensing actions.
	 *
	 * @return array
	 */
	public static function get_admin_license_data() {
		if ( defined( 'SKTPR_MARKETPLACE_BUILD' ) && SKTPR_MARKETPLACE_BUILD ) {
			return array(
				'is_pro'         => true,
				'is_trial'       => false,
				'is_marketplace' => true,
				'upgrade_url'    => '',
				'account_url'    => '',
			);
		}

		$freemius    = function_exists( 'pr_fs' ) ? pr_fs() : null;
		$is_pro      = $freemius ? self::has_pro_license() : false;
		$license_url = '';

		if ( $freemius && $freemius->is_premium() ) {
			$license_url = $freemius->is_registered() ?
				$freemius->get_account_url( false, array( 'activate_license' => 'true' ) ) :
				$freemius->get_activation_url( array( 'require_license' => 'true' ) );

			if ( $is_pro ) {
				$license_url = $freemius->get_account_url();
			}
		}

		return array(
			'is_pro'         => $is_pro,
			'is_trial'       => $freemius ? $freemius->is_trial() : false,
			'is_marketplace' => false,
			'upgrade_url'    => $freemius ? $freemius->get_upgrade_url() : '',
			'account_url'    => $license_url,
		);
	}
}
