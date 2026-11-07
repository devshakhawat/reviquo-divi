<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

trait Helpers {

	/**
	 * Generates the HTML for the video modal.
	 *
	 * @return string The HTML content for the video modal.
	 */
	public function modal_html() {

		$settings       = $this->get_settings();
		$recording_time = intval( $settings['video_duration'] ?? 2 );
		$modal_title    = $settings['modal_title_text'] ?? __( 'Record Review', 'product-reviews' );
		$start_text     = $settings['modal_start_btn_text'] ?? __( 'Start Recording', 'product-reviews' );
		$stop_text      = $settings['modal_stop_btn_text'] ?? __( 'Stop Recording', 'product-reviews' );
		$add_text       = $settings['modal_add_btn_text'] ?? __( 'Add this video', 'product-reviews' );
		ob_start();
		wp_nonce_field( 'sktpr_review_media', 'sktpr_review_media_nonce' );
		?>
		<div id="sktpr_video_modal" class="sktpr_video_modal">
			<div class="sktpr_background"></div>
			<!-- Modal content -->
			<div class="sktpr_modal-content-wrapper">
				<div class="sktpr_modal-content">
					<span class="sktpr_modal_close">&times;</span>
					<div class="sktpr_modal-content-inner">
						<h3 class="sktpr-text-center"><?php echo $this->button_icon_html( $settings, 'modal_title_icon', 'video-camera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in button_icon_html(). ?><?php echo esc_html( $modal_title ); ?></h3>
						<div class="sktpr_preview-recording">
							<div id="sktpr_timer"><span id="sktpr_timer-text" class="sktpr-initially-hidden" data-maxtime="<?php echo esc_attr( $recording_time ); ?>">05:00</span></div>
							<video playsinline id="sktpr_preview" class="sktpr-initially-hidden" width="450" height="337" autoplay="" muted=""></video>
							<video playsinline id="sktpr_recording" class="sktpr-initially-hidden" width="450" height="337" controls></video>
							<div class="sktpr_no_camera sktpr-text-center sktpr-initially-hidden">
								<div class="camera_inner">
									<img src="<?php echo esc_url( SKTPR_PLUGIN_URI . 'assets/img/video-icon.svg' ); ?>" alt="">
									<div><?php esc_html_e( 'No camera available', 'product-reviews' ); ?></div>
								</div>
							</div>
						</div>
						<div class="sktpr_record_video_buttons">
							<div id="sktpr_startButton" class="sktpr_video_button sktpr-initially-hidden">
							<?php echo $this->button_icon_html( $settings, 'modal_start_btn_icon', 'video-camera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in button_icon_html(). ?><span id="sktpr_startButton_text">
									<?php echo esc_html( $start_text ); ?>
								</span>
							</div>
							<div id="sktpr_stopButton" class="sktpr_video_button stop_recording_btn sktpr-initially-hidden">
							<?php echo $this->button_icon_html( $settings, 'modal_stop_btn_icon', 'stop-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in button_icon_html(). ?>
								<?php echo esc_html( $stop_text ); ?>
							</div>
							<a id="sktpr_addButton" class="sktpr_video_button add_video_btn sktpr-initially-hidden">
							<?php echo $this->button_icon_html( $settings, 'modal_add_btn_icon', 'plus-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in button_icon_html(). ?>
								<?php echo esc_html( $add_text ); ?>
							</a>
						</div>
					</div>
					<p class="sktpr_modal-content-bottom sktpr-text-center">
						<?php
							$sktpr_video_duration_unit = $recording_time >= 2 ? __( 'minutes', 'product-reviews' ) : __( 'minute', 'product-reviews' );
						?>
						<span><?php echo esc_html__( 'Maximum recording duration', 'product-reviews' ) . ' ' . esc_html( $recording_time ) . ' ' . esc_html( apply_filters( 'sktpr_video_duration_unit', $sktpr_video_duration_unit ) ); ?></span>
					</p>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieves the default settings for the review.
	 *
	 * @return array The default settings.
	 */
	public function get_defaults() {

		return array(
			'enable_video_btn'                   => true,
			'show_file_uploader'                 => true,
			'show_image_uploader'                => true,
			'required_video_recording'           => false,
			'required_file_upload'               => false,
			'required_image_upload'              => false,
			'auto_approve_video_reviews'         => false,
			'enable_gdpr_consent'                => false,
			'show_rating_summary'                => true,
			'show_review_filters'                => true,
			'enable_rich_snippets'               => true,
			'show_verified_badge'                => true,
			'verified_badge_size'                => 12,
			'verified_badge_bg_color'            => '#E6F2EA',
			'verified_badge_text_color'          => '#2F7552',
			'gdpr_consent_text'                  => __( 'I consent to my name, review, and any uploaded photos or videos being stored and displayed publicly on this site.', 'product-reviews' ),
			'video_duration'                     => 2,
			'review_btn_color'                   => '#FF694D',
			'review_btn_txt_color'               => '#FFFFFF',
			'review_btn_hover_color'             => '#EF4F31',
			'review_btn_hover_txt_color'         => '#FFFFFF',
			'review_btn_border_style'            => 'none',
			'review_btn_border_width'            => 0,
			'review_btn_border_color'            => '#FF694D',
			'review_btn_hover_border_color'      => '#EF4F31',
			'review_btn_text'                    => __( 'Record Video', 'product-reviews' ),
			'review_btn_icon'                    => 'video-camera',
			'review_btn_padding_y'               => 12,
			'review_btn_padding_x'               => 20,
			'review_btn_radius'                  => 3,
			'review_btn_font_size'               => 14,
			'review_btn_icon_size'               => 15,
			'review_btn_font_family'             => 'default',
			'review_btn_font_weight'             => '500',
			'review_btn_font_style'              => 'normal',
			'review_btn_text_transform'          => 'uppercase',
			'file_btn_color'                     => '#F7F5EF',
			'file_btn_txt_color'                 => '#17201E',
			'file_btn_hover_color'               => '#EEF4EF',
			'file_btn_hover_txt_color'           => '#17201E',
			'file_btn_border_style'              => 'dashed',
			'file_btn_border_width'              => 1,
			'file_btn_border_color'              => '#DFE2DA',
			'file_btn_hover_border_color'        => '#FF694D',
			'file_btn_text'                      => __( 'Upload video', 'product-reviews' ),
			'file_btn_icon'                      => 'file-video-o',
			'file_btn_padding_y'                 => 10,
			'file_btn_padding_x'                 => 18,
			'file_btn_radius'                    => 6,
			'file_btn_font_size'                 => 14,
			'file_btn_icon_size'                 => 16,
			'file_btn_font_family'               => 'default',
			'file_btn_font_weight'               => '500',
			'file_btn_font_style'                => 'normal',
			'file_btn_text_transform'            => 'none',
			'image_btn_color'                    => '#F7F5EF',
			'image_btn_txt_color'                => '#17201E',
			'image_btn_hover_color'              => '#EEF4EF',
			'image_btn_hover_txt_color'          => '#17201E',
			'image_btn_border_style'             => 'dashed',
			'image_btn_border_width'             => 1,
			'image_btn_border_color'             => '#DFE2DA',
			'image_btn_hover_border_color'       => '#FF694D',
			'image_btn_text'                     => __( 'Add photos', 'product-reviews' ),
			'image_btn_icon'                     => 'camera',
			'image_btn_padding_y'                => 10,
			'image_btn_padding_x'                => 18,
			'image_btn_radius'                   => 6,
			'image_btn_font_size'                => 14,
			'image_btn_icon_size'                => 16,
			'image_btn_font_family'              => 'default',
			'image_btn_font_weight'              => '500',
			'image_btn_font_style'               => 'normal',
			'image_btn_text_transform'           => 'none',
			'modal_bg_color'                     => '#FFFFFF',
			'modal_radius'                       => 7,
			'modal_overlay_color'                => '#000000',
			'modal_overlay_opacity'              => 40,
			'modal_title_text'                   => __( 'Record Review', 'product-reviews' ),
			'modal_title_color'                  => '#17201E',
			'modal_title_icon'                   => 'video-camera',
			'modal_start_btn_color'              => '#FF694D',
			'modal_start_btn_txt_color'          => '#FFFFFF',
			'modal_start_btn_hover_color'        => '#EF4F31',
			'modal_start_btn_hover_txt_color'    => '#FFFFFF',
			'modal_start_btn_border_style'       => 'none',
			'modal_start_btn_border_width'       => 0,
			'modal_start_btn_border_color'       => '#FF694D',
			'modal_start_btn_hover_border_color' => '#EF4F31',
			'modal_start_btn_text'               => __( 'Start Recording', 'product-reviews' ),
			'modal_start_btn_icon'               => 'video-camera',
			'modal_start_btn_padding_y'          => 10,
			'modal_start_btn_padding_x'          => 20,
			'modal_start_btn_radius'             => 3,
			'modal_start_btn_font_size'          => 12,
			'modal_start_btn_icon_size'          => 12,
			'modal_start_btn_font_family'        => 'default',
			'modal_start_btn_font_weight'        => '400',
			'modal_start_btn_font_style'         => 'normal',
			'modal_start_btn_text_transform'     => 'uppercase',
			'modal_stop_btn_color'               => '#C6272A',
			'modal_stop_btn_txt_color'           => '#FFFFFF',
			'modal_stop_btn_hover_color'         => '#A81E21',
			'modal_stop_btn_hover_txt_color'     => '#FFFFFF',
			'modal_stop_btn_border_style'        => 'none',
			'modal_stop_btn_border_width'        => 0,
			'modal_stop_btn_border_color'        => '#C6272A',
			'modal_stop_btn_hover_border_color'  => '#C6272A',
			'modal_stop_btn_text'                => __( 'Stop Recording', 'product-reviews' ),
			'modal_stop_btn_icon'                => 'stop-circle',
			'modal_stop_btn_padding_y'           => 10,
			'modal_stop_btn_padding_x'           => 20,
			'modal_stop_btn_radius'              => 3,
			'modal_stop_btn_font_size'           => 12,
			'modal_stop_btn_icon_size'           => 12,
			'modal_stop_btn_font_family'         => 'default',
			'modal_stop_btn_font_weight'         => '400',
			'modal_stop_btn_font_style'          => 'normal',
			'modal_stop_btn_text_transform'      => 'uppercase',
			'modal_add_btn_color'                => '#80C6A6',
			'modal_add_btn_txt_color'            => '#17201E',
			'modal_add_btn_hover_color'          => '#2F7552',
			'modal_add_btn_hover_txt_color'      => '#FFFFFF',
			'modal_add_btn_border_style'         => 'none',
			'modal_add_btn_border_width'         => 0,
			'modal_add_btn_border_color'         => '#80C6A6',
			'modal_add_btn_hover_border_color'   => '#2F7552',
			'modal_add_btn_text'                 => __( 'Add this video', 'product-reviews' ),
			'modal_add_btn_icon'                 => 'plus-circle',
			'modal_add_btn_padding_y'            => 10,
			'modal_add_btn_padding_x'            => 20,
			'modal_add_btn_radius'               => 3,
			'modal_add_btn_font_size'            => 12,
			'modal_add_btn_icon_size'            => 12,
			'modal_add_btn_font_family'          => 'default',
			'modal_add_btn_font_weight'          => '400',
			'modal_add_btn_font_style'           => 'normal',
			'modal_add_btn_text_transform'       => 'none',
			'button_position'                    => 'after_review_form',
		);
	}

	/**
	 * Retrieves the style setting prefixes for the storefront buttons.
	 *
	 * @return array The button setting prefixes.
	 */
	public function get_button_style_prefixes() {

		return array( 'review_btn_', 'file_btn_', 'image_btn_', 'modal_start_btn_', 'modal_stop_btn_', 'modal_add_btn_' );
	}

	/**
	 * Retrieves the list of allowed button icons.
	 *
	 * @return array The allowed Font Awesome icon slugs.
	 */
	public function get_allowed_button_icons() {

		return array( 'video-camera', 'camera', 'microphone', 'play-circle', 'star', 'comment', 'pencil', 'file-video-o', 'picture-o', 'upload', 'stop-circle', 'plus-circle', 'none' );
	}

	/**
	 * Builds the Font Awesome icon markup for a storefront button.
	 *
	 * @param array  $settings The plugin settings.
	 * @param string $setting_key The icon setting key, e.g. 'review_btn_icon'.
	 * @param string $fallback The icon slug used when the setting is missing or invalid.
	 * @return string The icon markup, or an empty string for 'none'.
	 */
	public function button_icon_html( $settings, $setting_key, $fallback ) {

		$icon = isset( $settings[ $setting_key ] ) && in_array( $settings[ $setting_key ], $this->get_allowed_button_icons(), true ) ? $settings[ $setting_key ] : $fallback;

		if ( 'none' === $icon ) {
			return '';
		}

		return sprintf( '<i class="fa fa-%s" aria-hidden="true"></i>', esc_attr( $icon ) );
	}

	/**
	 * Retrieves the available button font families keyed by slug.
	 *
	 * The 'default' slug maps to an empty stack, meaning the theme font is inherited.
	 *
	 * @return array Font family slugs mapped to their CSS font stacks.
	 */
	public function get_button_font_families() {

		return array(
			'default'   => '',
			'system'    => 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
			'arial'     => 'Arial, Helvetica, sans-serif',
			'helvetica' => 'Helvetica Neue, Helvetica, Arial, sans-serif',
			'verdana'   => 'Verdana, Geneva, sans-serif',
			'tahoma'    => 'Tahoma, Geneva, sans-serif',
			'trebuchet' => 'Trebuchet MS, sans-serif',
			'georgia'   => 'Georgia, Times New Roman, serif',
			'times'     => 'Times New Roman, Times, serif',
			'courier'   => 'Courier New, monospace',
		);
	}

	/**
	 * Validates and sanitizes the form data.
	 *
	 * @param array $form_data The form data to validate.
	 * @return array The validated and sanitized form data.
	 */
	public function validate_form_data( $form_data ) {

		$form_data['enable_video_btn']           = ( 'on' === $form_data['enable_video_btn'] );
		$form_data['show_file_uploader']         = ( 'on' === $form_data['show_file_uploader'] );
		$form_data['show_image_uploader']        = ( 'on' === $form_data['show_image_uploader'] );
		$form_data['required_video_recording']   = ( 'on' === $form_data['required_video_recording'] );
		$form_data['required_file_upload']       = ( 'on' === $form_data['required_file_upload'] );
		$form_data['required_image_upload']      = ( 'on' === $form_data['required_image_upload'] );
		$form_data['auto_approve_video_reviews'] = ( 'on' === $form_data['auto_approve_video_reviews'] );
		$form_data['enable_gdpr_consent']        = ( ( $form_data['enable_gdpr_consent'] ?? 'off' ) === 'on' );
		$form_data['show_rating_summary']        = ( ( $form_data['show_rating_summary'] ?? 'off' ) === 'on' );
		$form_data['show_review_filters']        = ( ( $form_data['show_review_filters'] ?? 'off' ) === 'on' );
		$form_data['enable_rich_snippets']       = ( ( $form_data['enable_rich_snippets'] ?? 'off' ) === 'on' );
		$form_data['show_verified_badge']        = ( ( $form_data['show_verified_badge'] ?? 'off' ) === 'on' );
		$form_data['verified_badge_size']        = max( 10, min( 24, absint( $form_data['verified_badge_size'] ?? 12 ) ) );
		$badge_bg_color                          = sanitize_hex_color( $form_data['verified_badge_bg_color'] ?? '' );
		$badge_text_color                        = sanitize_hex_color( $form_data['verified_badge_text_color'] ?? '' );

		$form_data['verified_badge_bg_color']   = $badge_bg_color ? $badge_bg_color : '#E6F2EA';
		$form_data['verified_badge_text_color'] = $badge_text_color ? $badge_text_color : '#2F7552';
		$form_data['gdpr_consent_text']         = sanitize_text_field( $form_data['gdpr_consent_text'] ?? '' );

		if ( '' === $form_data['gdpr_consent_text'] ) {
			$form_data['gdpr_consent_text'] = $this->get_defaults()['gdpr_consent_text'];
		}

		$form_data['video_duration']  = max( 1, min( Features::MAX_VIDEO_DURATION, intval( $form_data['video_duration'] ) ) );
		$form_data['button_position'] = sanitize_text_field( $form_data['button_position'] );

		$modal_defaults = $this->get_defaults();

		$form_data['modal_bg_color']        = sanitize_text_field( $form_data['modal_bg_color'] ?? $modal_defaults['modal_bg_color'] );
		$form_data['modal_radius']          = min( 30, absint( $form_data['modal_radius'] ?? $modal_defaults['modal_radius'] ) );
		$form_data['modal_overlay_color']   = sanitize_text_field( $form_data['modal_overlay_color'] ?? $modal_defaults['modal_overlay_color'] );
		$form_data['modal_overlay_opacity'] = min( 95, absint( $form_data['modal_overlay_opacity'] ?? $modal_defaults['modal_overlay_opacity'] ) );
		$form_data['modal_title_text']      = sanitize_text_field( $form_data['modal_title_text'] ?? $modal_defaults['modal_title_text'] );
		$form_data['modal_title_color']     = sanitize_text_field( $form_data['modal_title_color'] ?? '' );
		$form_data['modal_title_icon']      = in_array( $form_data['modal_title_icon'] ?? '', $this->get_allowed_button_icons(), true ) ? $form_data['modal_title_icon'] : $modal_defaults['modal_title_icon'];

		if ( '' === $form_data['modal_title_text'] ) {
			$form_data['modal_title_text'] = $modal_defaults['modal_title_text'];
		}

		foreach ( $this->get_button_style_prefixes() as $prefix ) {
			$form_data = $this->validate_button_style_data( $form_data, $prefix );
		}

		return $form_data;
	}

	/**
	 * Validates and sanitizes the style settings for one storefront button.
	 *
	 * @param array  $form_data The form data to validate.
	 * @param string $prefix The button setting prefix, e.g. 'review_btn_'.
	 * @return array The validated form data.
	 */
	public function validate_button_style_data( $form_data, $prefix ) {

		$defaults = $this->get_defaults();

		$form_data[ $prefix . 'color' ]              = sanitize_text_field( $form_data[ $prefix . 'color' ] );
		$form_data[ $prefix . 'txt_color' ]          = sanitize_text_field( $form_data[ $prefix . 'txt_color' ] );
		$form_data[ $prefix . 'hover_color' ]        = sanitize_text_field( $form_data[ $prefix . 'hover_color' ] );
		$form_data[ $prefix . 'hover_txt_color' ]    = sanitize_text_field( $form_data[ $prefix . 'hover_txt_color' ] );
		$form_data[ $prefix . 'border_style' ]       = in_array( $form_data[ $prefix . 'border_style' ], array( 'none', 'solid', 'dashed', 'dotted' ), true ) ? $form_data[ $prefix . 'border_style' ] : $defaults[ $prefix . 'border_style' ];
		$form_data[ $prefix . 'border_width' ]       = min( 10, absint( $form_data[ $prefix . 'border_width' ] ) );
		$form_data[ $prefix . 'border_color' ]       = sanitize_text_field( $form_data[ $prefix . 'border_color' ] );
		$form_data[ $prefix . 'hover_border_color' ] = sanitize_text_field( $form_data[ $prefix . 'hover_border_color' ] );
		$form_data[ $prefix . 'text' ]               = sanitize_text_field( $form_data[ $prefix . 'text' ] );
		$form_data[ $prefix . 'icon' ]               = in_array( $form_data[ $prefix . 'icon' ], $this->get_allowed_button_icons(), true ) ? $form_data[ $prefix . 'icon' ] : $defaults[ $prefix . 'icon' ];
		$form_data[ $prefix . 'padding_y' ]          = min( 40, absint( $form_data[ $prefix . 'padding_y' ] ) );
		$form_data[ $prefix . 'padding_x' ]          = min( 80, absint( $form_data[ $prefix . 'padding_x' ] ) );
		$form_data[ $prefix . 'radius' ]             = min( 50, absint( $form_data[ $prefix . 'radius' ] ) );
		$form_data[ $prefix . 'font_size' ]          = max( 10, min( 24, absint( $form_data[ $prefix . 'font_size' ] ) ) );
		$form_data[ $prefix . 'icon_size' ]          = max( 10, min( 32, absint( $form_data[ $prefix . 'icon_size' ] ) ) );
		$form_data[ $prefix . 'font_family' ]        = array_key_exists( $form_data[ $prefix . 'font_family' ], $this->get_button_font_families() ) ? $form_data[ $prefix . 'font_family' ] : 'default';
		$form_data[ $prefix . 'font_weight' ]        = in_array( $form_data[ $prefix . 'font_weight' ], array( '300', '400', '500', '600', '700', '800' ), true ) ? $form_data[ $prefix . 'font_weight' ] : '500';
		$form_data[ $prefix . 'font_style' ]         = in_array( $form_data[ $prefix . 'font_style' ], array( 'normal', 'italic' ), true ) ? $form_data[ $prefix . 'font_style' ] : 'normal';
		$form_data[ $prefix . 'text_transform' ]     = in_array( $form_data[ $prefix . 'text_transform' ], array( 'uppercase', 'capitalize', 'lowercase', 'none' ), true ) ? $form_data[ $prefix . 'text_transform' ] : $defaults[ $prefix . 'text_transform' ];

		return $form_data;
	}

	/**
	 * Updates the review settings.
	 *
	 * @param array $settings The new settings to update.
	 * @return bool True if the settings were updated, false otherwise.
	 */
	public function update_settings( $settings ) {

		if ( empty( $settings ) ) {
			return;
		}

		$prev_data = get_option( 'sktpr_review_settings' );

		if ( $prev_data === $settings ) {
			return true;
		}

		$is_updated = update_option( 'sktpr_review_settings', $settings );

		return $is_updated;
	}

	/**
	 * Retrieves the review settings.
	 *
	 * @return array The review settings.
	 */
	public function get_stored_settings() {
		$defaults = $this->get_defaults();
		$settings = get_option( 'sktpr_review_settings', $defaults );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		if ( 'wcreviews-1' !== get_option( 'sktpr_brand_palette_version' ) ) {
			$legacy_colors = array(
				'verified_badge_bg_color'            => array( '#E7F7EE', '#E6F2EA' ),
				'verified_badge_text_color'          => array( '#1A7F47', '#2F7552' ),
				'review_btn_color'                   => array( '#481FEA', '#FF694D' ),
				'review_btn_txt_color'               => array( '#17201E', '#FFFFFF' ),
				'review_btn_hover_color'             => array( '#3916C0', '#EF4F31' ),
				'review_btn_hover_txt_color'         => array( '#17201E', '#FFFFFF' ),
				'review_btn_border_color'            => array( '#481FEA', '#FF694D' ),
				'review_btn_hover_border_color'      => array( '#481FEA', '#EF4F31' ),
				'file_btn_color'                     => array( '#F7F8FB', '#F7F5EF' ),
				'file_btn_txt_color'                 => array( '#384D6A', '#17201E' ),
				'file_btn_hover_color'               => array( '#E9EFF9', '#EEF4EF' ),
				'file_btn_hover_txt_color'           => array( '#005BDF', '#17201E' ),
				'file_btn_border_color'              => array( '#B3B9C9', '#DFE2DA' ),
				'file_btn_hover_border_color'        => array( '#005BDF', '#FF694D' ),
				'image_btn_color'                    => array( '#F7F8FB', '#F7F5EF' ),
				'image_btn_txt_color'                => array( '#384D6A', '#17201E' ),
				'image_btn_hover_color'              => array( '#E9EFF9', '#EEF4EF' ),
				'image_btn_hover_txt_color'          => array( '#005BDF', '#17201E' ),
				'image_btn_border_color'             => array( '#B3B9C9', '#DFE2DA' ),
				'image_btn_hover_border_color'       => array( '#005BDF', '#FF694D' ),
				'modal_bg_color'                     => array( '#FEFEFE', '#FFFFFF' ),
				'modal_start_btn_color'              => array( '#005BDF', '#FF694D' ),
				'modal_start_btn_txt_color'          => array( '#17201E', '#FFFFFF' ),
				'modal_start_btn_hover_color'        => array( '#004AB8', '#EF4F31' ),
				'modal_start_btn_hover_txt_color'    => array( '#17201E', '#FFFFFF' ),
				'modal_start_btn_border_color'       => array( '#005BDF', '#FF694D' ),
				'modal_start_btn_hover_border_color' => array( '#005BDF', '#EF4F31' ),
				'modal_add_btn_color'                => array( '#07B86B', '#80C6A6' ),
				'modal_add_btn_txt_color'            => array( '#FFFFFF', '#17201E' ),
				'modal_add_btn_hover_color'          => array( '#069457', '#2F7552' ),
				'modal_add_btn_border_color'         => array( '#07B86B', '#80C6A6' ),
				'modal_add_btn_hover_border_color'   => array( '#07B86B', '#2F7552' ),
			);
			$changed       = false;

			foreach ( $legacy_colors as $key => $colors ) {
				if ( isset( $settings[ $key ] ) && 0 === strcasecmp( $settings[ $key ], $colors[0] ) ) {
					$settings[ $key ] = $colors[1];
					$changed          = true;
				}
			}

			if ( $changed ) {
				update_option( 'sktpr_review_settings', $settings );
			}

			update_option( 'sktpr_brand_palette_version', 'wcreviews-1' );
		}

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Retrieve settings with the current Freemius license policy applied.
	 *
	 * @return array
	 */
	public function get_settings() {
		return Features::apply_settings( $this->get_stored_settings(), $this->get_defaults() );
	}

	/**
	 * Get email reminder settings
	 *
	 * @return array
	 */
	public function get_stored_email_settings() {
		$defaults = $this->get_email_defaults();
		$settings = get_option( 'sktpr_email_reminder_settings', $defaults );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		// Normalize the pre-3.1 single-template option into one stable rule.
		if ( ! isset( $settings['rules'] ) || ! is_array( $settings['rules'] ) ) {
			$default_rule      = $defaults['rules'][0];
			$settings['rules'] = array(
				array(
					'id'                   => 'legacy-default',
					'enabled'              => true,
					'trigger_order_status' => sanitize_key( $settings['trigger_order_status'] ?? $default_rule['trigger_order_status'] ),
					'email_delay_days'     => min( 365, absint( $settings['email_delay_days'] ?? $default_rule['email_delay_days'] ) ),
					'email_subject'        => $settings['email_subject'] ?? $default_rule['email_subject'],
					'email_content'        => $settings['email_content'] ?? $default_rule['email_content'],
				),
			);
		}

		$settings = wp_parse_args( $settings, $defaults );

		return array(
			'enable_email_reminders' => ! empty( $settings['enable_email_reminders'] ),
			'from_name'              => $settings['from_name'],
			'from_email'             => $settings['from_email'],
			'rules'                  => array_values( $settings['rules'] ),
		);
	}

	/**
	 * Retrieve email settings with the current license policy applied.
	 *
	 * @return array
	 */
	public function get_email_settings() {
		return Features::apply_email_settings( $this->get_stored_email_settings() );
	}

	/**
	 * Get default email reminder settings.
	 *
	 * @return array The default email reminder settings.
	 */
	public function get_email_defaults() {
		$default_subject = __( 'How was your recent purchase, {customer_name}?', 'product-reviews' );
		$default_content = $this->get_default_email_content();

		return array(
			'enable_email_reminders' => false,
			'from_name'              => get_bloginfo( 'name' ),
			'from_email'             => get_option( 'admin_email' ),
			'rules'                  => array(
				array(
					'id'                   => 'default-completed',
					'enabled'              => true,
					'trigger_order_status' => 'completed',
					'email_delay_days'     => 7,
					'email_subject'        => $default_subject,
					'email_content'        => $default_content,
				),
			),
		);
	}

	/**
	 * Update email settings
	 *
	 * @param array $settings Settings array.
	 * @return bool
	 */
	public function update_email_settings( $settings ) {
		return update_option( 'sktpr_email_reminder_settings', $settings );
	}

	/**
	 * Review reward tiers, from least to most valuable.
	 *
	 * @return string[]
	 */
	public function get_reward_tier_keys() {
		return array( 'text', 'photo', 'video' );
	}

	/**
	 * Get default review reward settings.
	 *
	 * @return array
	 */
	public function get_reward_defaults() {
		return array(
			'enable_review_rewards'  => false,
			'tiers'                  => array(
				'text'  => array(
					'enabled'       => false,
					'discount_type' => 'percent',
					'amount'        => 5,
				),
				'photo' => array(
					'enabled'       => true,
					'discount_type' => 'percent',
					'amount'        => 10,
				),
				'video' => array(
					'enabled'       => true,
					'discount_type' => 'percent',
					'amount'        => 15,
				),
			),
			'coupon_prefix'          => 'REVIEW-',
			'expiry_days'            => 30,
			'minimum_amount'         => '',
			'individual_use'         => true,
			'exclude_sale_items'     => false,
			'require_verified_owner' => true,
			'min_review_length'      => 0,
			'reward_limit'           => 'product',
			'revoke_on_unapprove'    => true,
			'email_subject'          => __( 'Thanks for your review, {customer_name}! Here is your reward', 'product-reviews' ),
			'email_content'          => $this->get_default_reward_email_content(),
			'show_form_notice'       => true,
			'form_notice_text'       => __( 'Share a photo or video review and get {review_reward} off your next order!', 'product-reviews' ),
		);
	}

	/**
	 * Get stored review reward settings merged over the defaults.
	 *
	 * @return array
	 */
	public function get_stored_reward_settings() {
		$defaults = $this->get_reward_defaults();
		$settings = get_option( 'sktpr_review_reward_settings', array() );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		$stored_tiers = isset( $settings['tiers'] ) && is_array( $settings['tiers'] ) ? $settings['tiers'] : array();
		$settings     = wp_parse_args( $settings, $defaults );
		$tiers        = array();

		foreach ( $this->get_reward_tier_keys() as $tier ) {
			$tiers[ $tier ] = wp_parse_args(
				isset( $stored_tiers[ $tier ] ) && is_array( $stored_tiers[ $tier ] ) ? $stored_tiers[ $tier ] : array(),
				$defaults['tiers'][ $tier ]
			);
		}

		$settings['tiers'] = $tiers;

		return $settings;
	}

	/**
	 * Retrieve review reward settings with the current license policy applied.
	 *
	 * @return array
	 */
	public function get_reward_settings() {
		return Features::apply_reward_settings( $this->get_stored_reward_settings() );
	}

	/**
	 * Update review reward settings.
	 *
	 * @param array $settings Settings array.
	 * @return bool
	 */
	public function update_reward_settings( $settings ) {
		return update_option( 'sktpr_review_reward_settings', $settings );
	}

	/**
	 * Get the default reward email content template.
	 *
	 * @return string
	 */
	private function get_default_reward_email_content() {
		return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #17201e;">
			<h2 style="color: #17201e;">' . esc_html__( 'Hi {customer_name},', 'product-reviews' ) . '</h2>

			<p>' . esc_html__( 'Thank you for reviewing {product_name}! Your feedback helps other shoppers and helps us improve.', 'product-reviews' ) . '</p>

			<p>' . esc_html__( 'As a thank-you, here is {coupon_amount} off your next order:', 'product-reviews' ) . '</p>

			<p style="margin: 24px 0; text-align: center;">
				<span style="display: inline-block; padding: 14px 28px; border: 2px dashed #ff694d; border-radius: 6px; font-size: 22px; font-weight: 700; letter-spacing: 2px;">{coupon_code}</span>
			</p>

			<p style="text-align: center;">' . esc_html__( 'Valid until: {coupon_expiry}', 'product-reviews' ) . '</p>

			<p style="text-align: center;">
				<a href="{shop_url}" style="background: #ff694d; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; font-weight: 700;">' . esc_html__( 'Shop now', 'product-reviews' ) . '</a>
			</p>

			<p>' . esc_html__( 'Best regards,', 'product-reviews' ) . '<br>
			' . esc_html__( 'The {site_name} Team', 'product-reviews' ) . '</p>
		</div>';
	}

	/**
	 * Get default email content template
	 *
	 * @return string
	 */
	private function get_default_email_content() {
		$admin_email = get_option( 'admin_email' );

		return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #17201e;">
			<h2 style="color: #17201e;">' . esc_html__( 'Hi {customer_name},', 'product-reviews' ) . '</h2>

			<p>' . esc_html__( 'Thank you for your recent purchase from {site_name}! We hope you are enjoying your new products.', 'product-reviews' ) . '</p>

			<p>' . esc_html__( 'We would love to hear about your experience. Your feedback helps us improve and helps other customers make informed decisions.', 'product-reviews' ) . '</p>

			<p><strong>' . esc_html__( 'Order #{order_number}', 'product-reviews' ) . '</strong> ' . esc_html__( 'placed on {order_date}', 'product-reviews' ) . '</p>

			{products_list}

			<p>' . esc_html__( 'Thank you for taking the time to share your thoughts!', 'product-reviews' ) . '</p>

			<p>' . esc_html__( 'Best regards,', 'product-reviews' ) . '<br>
			' . esc_html__( 'The {site_name} Team', 'product-reviews' ) . '</p>

			<hr style="border: none; border-top: 1px solid #dfe2da; margin: 30px 0;">
			<p style="font-size: 12px; color: #69716c;">
				' . esc_html__( 'This email was sent because you recently made a purchase from {site_name}.', 'product-reviews' ) . '
				' . sprintf(
					/* translators: %s: site administrator email address, rendered as a mailto link. */
					esc_html__( 'If you have any questions, please contact us at %s', 'product-reviews' ),
					'<a href="mailto:' . esc_attr( $admin_email ) . '">' . esc_html( $admin_email ) . '</a>'
				) . '
			</p>
		</div>';
	}
}
