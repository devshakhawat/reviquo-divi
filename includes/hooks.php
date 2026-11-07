<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;


/**
 * Class Hooks
 *
 * Handles AJAX requests for review settings.
 */
class Hooks {

	use Helpers;

	/**
	 * Constructor for the Hooks class.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( SKTPR_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		add_action( 'wp_ajax_sktpr_get_review_settings', array( $this, 'handle_get_review_settings' ) );
		add_action( 'wp_ajax_sktpr_reset_settings', array( $this, 'handle_reset_settings' ) );
	}

	/**
	 * Handles the AJAX request to get review settings.
	 */
	public function handle_get_review_settings() {

		check_admin_referer( 'sktpr_plugin_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'status' => 'error' ) );
		}

		// Get current settings.
		$current_settings = $this->get_stored_settings();

		// Merge submitted data with current settings
		// Note: Checkboxes only send data when checked, so we need to handle unchecked state.
		$data = array(
			'enable_video_btn'           => isset( $_POST['enable_video_btn'] ) ? 'on' : 'off',
			'show_file_uploader'         => isset( $_POST['show_file_uploader'] ) ? 'on' : 'off',
			'show_image_uploader'        => isset( $_POST['show_image_uploader'] ) ? 'on' : 'off',
			'required_video_recording'   => isset( $_POST['required_video_recording'] ) ? 'on' : 'off',
			'required_file_upload'       => isset( $_POST['required_file_upload'] ) ? 'on' : 'off',
			'required_image_upload'      => isset( $_POST['required_image_upload'] ) ? 'on' : 'off',
			'auto_approve_video_reviews' => isset( $_POST['auto_approve_video_reviews'] ) ? 'on' : 'off',
			'enable_gdpr_consent'        => isset( $_POST['enable_gdpr_consent'] ) ? 'on' : 'off',
			'show_rating_summary'        => isset( $_POST['show_rating_summary'] ) ? 'on' : 'off',
			'show_review_filters'        => isset( $_POST['show_review_filters'] ) ? 'on' : 'off',
			'enable_rich_snippets'       => isset( $_POST['enable_rich_snippets'] ) ? 'on' : 'off',
			'show_verified_badge'        => isset( $_POST['show_verified_badge'] ) ? 'on' : 'off',
			'verified_badge_size'        => isset( $_POST['verified_badge_size'] ) ? absint( wp_unslash( $_POST['verified_badge_size'] ) ) : ( $current_settings['verified_badge_size'] ?? 12 ),
			'verified_badge_bg_color'    => isset( $_POST['verified_badge_bg_color'] ) ? sanitize_text_field( wp_unslash( $_POST['verified_badge_bg_color'] ) ) : ( $current_settings['verified_badge_bg_color'] ?? '#E6F2EA' ),
			'verified_badge_text_color'  => isset( $_POST['verified_badge_text_color'] ) ? sanitize_text_field( wp_unslash( $_POST['verified_badge_text_color'] ) ) : ( $current_settings['verified_badge_text_color'] ?? '#2F7552' ),
			'gdpr_consent_text'          => isset( $_POST['gdpr_consent_text'] ) ? sanitize_text_field( wp_unslash( $_POST['gdpr_consent_text'] ) ) : ( $current_settings['gdpr_consent_text'] ?? '' ),
			'video_duration'             => isset( $_POST['video_duration'] ) ? absint( wp_unslash( $_POST['video_duration'] ) ) : $current_settings['video_duration'],
			'modal_bg_color'             => isset( $_POST['modal_bg_color'] ) ? sanitize_text_field( wp_unslash( $_POST['modal_bg_color'] ) ) : ( $current_settings['modal_bg_color'] ?? '#FFFFFF' ),
			'modal_radius'               => isset( $_POST['modal_radius'] ) ? absint( wp_unslash( $_POST['modal_radius'] ) ) : ( $current_settings['modal_radius'] ?? 7 ),
			'modal_overlay_color'        => isset( $_POST['modal_overlay_color'] ) ? sanitize_text_field( wp_unslash( $_POST['modal_overlay_color'] ) ) : ( $current_settings['modal_overlay_color'] ?? '#000000' ),
			'modal_overlay_opacity'      => isset( $_POST['modal_overlay_opacity'] ) ? absint( wp_unslash( $_POST['modal_overlay_opacity'] ) ) : ( $current_settings['modal_overlay_opacity'] ?? 40 ),
			'modal_title_text'           => isset( $_POST['modal_title_text'] ) ? sanitize_text_field( wp_unslash( $_POST['modal_title_text'] ) ) : ( $current_settings['modal_title_text'] ?? '' ),
			'modal_title_color'          => isset( $_POST['modal_title_color'] ) ? sanitize_text_field( wp_unslash( $_POST['modal_title_color'] ) ) : ( $current_settings['modal_title_color'] ?? '' ),
			'modal_title_icon'           => isset( $_POST['modal_title_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['modal_title_icon'] ) ) : ( $current_settings['modal_title_icon'] ?? 'video-camera' ),
			'button_position'            => isset( $_POST['button_position'] ) ? sanitize_text_field( wp_unslash( $_POST['button_position'] ) ) : $current_settings['button_position'],
		);

		$defaults  = $this->get_defaults();
		$text_keys = array( 'color', 'txt_color', 'hover_color', 'hover_txt_color', 'border_style', 'border_color', 'hover_border_color', 'text', 'icon', 'font_family', 'font_weight', 'font_style', 'text_transform' );
		$int_keys  = array( 'padding_y', 'padding_x', 'radius', 'font_size', 'icon_size', 'border_width' );

		foreach ( $this->get_button_style_prefixes() as $prefix ) {
			foreach ( $text_keys as $key ) {
				$full_key = $prefix . $key;

				$data[ $full_key ] = isset( $_POST[ $full_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $full_key ] ) ) : ( $current_settings[ $full_key ] ?? $defaults[ $full_key ] );
			}

			foreach ( $int_keys as $key ) {
				$full_key = $prefix . $key;

				$data[ $full_key ] = isset( $_POST[ $full_key ] ) ? absint( wp_unslash( $_POST[ $full_key ] ) ) : ( $current_settings[ $full_key ] ?? $defaults[ $full_key ] );
			}
		}

		$form_data = $this->validate_form_data( $data );
		$form_data = Features::preserve_locked_settings( $form_data, $current_settings );

		$is_updated = $this->update_settings( $form_data );

		if ( ! $is_updated ) {
			wp_send_json_error( array( 'status' => __( 'Something went wrong.', 'product-reviews' ) ) );
		}

		wp_send_json_success( array( 'status' => __( 'Successfully saved.', 'product-reviews' ) ) );
	}

	/**
	 * Restores review and email reminder settings to plugin defaults.
	 *
	 * @return void
	 */
	public function handle_reset_settings() {

		check_admin_referer( 'sktpr_plugin_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'status' => 'error' ), 403 );
		}

		$this->update_settings( $this->get_defaults() );
		$this->update_email_settings( $this->get_email_defaults() );
		$this->update_reward_settings( Features::preserve_locked_reward_settings( $this->get_reward_defaults(), $this->get_stored_reward_settings() ) );
		Database::cancel_all_scheduled();

		wp_send_json_success(
			array(
				'status' => __( 'All ReviQuo settings have been reset to their defaults.', 'product-reviews' ),
			)
		);
	}

	/**
	 * Adds a settings link to the plugin action links.
	 *
	 * @param array $links An array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=' . SKTPR_ADMIN_PAGE ),
			__( 'Settings', 'product-reviews' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}
