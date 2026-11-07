<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Email_Settings
 *
 * Handles email reminder settings and admin interface
 */
class Email_Settings {

	use Helpers;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_sktpr_save_email_settings', array( $this, 'handle_ajax_settings_save' ) );
	}

	/**
	 * Saves email reminder settings from the React admin screen.
	 *
	 * @return void
	 */
	public function handle_ajax_settings_save() {
		check_admin_referer( 'sktpr_plugin_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'status' => __( 'You do not have permission to update these settings.', 'product-reviews' ) ), 403 );
		}

		$current  = $this->get_stored_email_settings();
		$settings = Features::apply_email_settings( $this->sanitize_email_settings( wp_unslash( $_POST ) ) );
		$error    = $this->validate_email_settings( $settings );

		if ( $error ) {
			wp_send_json_error( array( 'status' => $error ), 400 );
		}

		$settings_to_store = Features::preserve_locked_email_rules( $settings, $current );
		$this->update_email_settings( $settings_to_store );

		if ( ! empty( $current['enable_email_reminders'] ) && empty( $settings['enable_email_reminders'] ) ) {
			Database::cancel_all_scheduled();
		}

		wp_send_json_success(
			array(
				'status'   => __( 'Email reminder settings saved successfully!', 'product-reviews' ),
				'settings' => Features::apply_email_settings( $settings_to_store ),
			)
		);
	}

	/**
	 * Sanitize email settings
	 *
	 * @param array $post_data POST data.
	 * @return array
	 */
	private function sanitize_email_settings( $post_data ) {
		$defaults = $this->get_email_defaults();
		$rules    = $post_data['rules'] ?? array();

		if ( is_string( $rules ) ) {
			$rules = json_decode( $rules, true );
		}

		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		$statuses        = $this->get_order_statuses();
		$status_keys     = array_keys( $statuses );
		$first_status    = reset( $status_keys );
		$fallback_status = isset( $statuses['completed'] ) ? 'completed' : ( $first_status ? $first_status : 'completed' );
		$clean_rules     = array();
		$seen_ids        = array();

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			$id = substr( sanitize_key( $rule['id'] ?? '' ), 0, 64 );
			if ( '' === $id || isset( $seen_ids[ $id ] ) ) {
				$id = wp_generate_uuid4();
			}
			$seen_ids[ $id ] = true;

			$status = sanitize_key( $rule['trigger_order_status'] ?? $fallback_status );
			if ( ! isset( $statuses[ $status ] ) ) {
				$status = $fallback_status;
			}

			$clean_rules[] = array(
				'id'                   => $id,
				'enabled'              => ! empty( $rule['enabled'] ),
				'trigger_order_status' => $status,
				'email_delay_days'     => max( 0, min( 365, intval( $rule['email_delay_days'] ?? 0 ) ) ),
				'email_subject'        => sanitize_text_field( $rule['email_subject'] ?? '' ),
				'email_content'        => wp_kses_post( $rule['email_content'] ?? '' ),
			);
		}

		return array(
			'enable_email_reminders' => isset( $post_data['enable_email_reminders'] ) ? true : false,
			'from_name'              => sanitize_text_field( $post_data['from_name'] ?? $defaults['from_name'] ),
			'from_email'             => sanitize_email( $post_data['from_email'] ?? $defaults['from_email'] ),
			'rules'                  => $clean_rules,
		);
	}

	/**
	 * Validate normalized reminder settings.
	 *
	 * @param array $settings Normalized settings.
	 * @return string Validation message, or an empty string.
	 */
	private function validate_email_settings( $settings ) {
		if ( empty( $settings['enable_email_reminders'] ) ) {
			return '';
		}

		if ( '' === $settings['from_name'] || ! is_email( $settings['from_email'] ) ) {
			return __( 'Enter a valid sender name and email address.', 'product-reviews' );
		}

		$enabled_rules = array_filter(
			$settings['rules'],
			function ( $rule ) {
				return ! empty( $rule['enabled'] );
			}
		);

		if ( empty( $enabled_rules ) ) {
			return __( 'Enable at least one reminder rule.', 'product-reviews' );
		}

		foreach ( $enabled_rules as $rule ) {
			if ( '' === $rule['email_subject'] || '' === trim( wp_strip_all_tags( $rule['email_content'] ) ) ) {
				return __( 'Every enabled reminder needs a subject and email content.', 'product-reviews' );
			}
		}

		return '';
	}

	/**
	 * Get available order statuses
	 *
	 * @return array
	 */
	public function get_order_statuses() {
		$statuses = wc_get_order_statuses();

		// Remove wc- prefix for internal use.
		$clean_statuses = array();
		foreach ( $statuses as $key => $label ) {
			$clean_key                    = 'wc-' === substr( $key, 0, 3 ) ? substr( $key, 3 ) : $key;
			$clean_statuses[ $clean_key ] = $label;
		}

		return $clean_statuses;
	}
}
