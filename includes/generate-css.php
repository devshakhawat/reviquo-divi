<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin shortcode.
 *
 * @since 1.0.0
 */
class Generate_CSS {

	use Helpers;

	/**
	 * Generate CSS
	 *
	 * @param string $selector The CSS selector.
	 * @param mixed  $targets The target elements.
	 * @param string $prop The CSS property.
	 * @param string $value The CSS value.
	 */
	public function generate_css( $selector, $targets, $prop, $value ) {

		$selectors = array();

		if ( gettype( $targets ) !== 'array' ) {
			$targets = array( $targets );
		}

		foreach ( $targets as $target ) {
			$selectors[] = $selector . $target;
		}

		$css = sprintf( '%s{%s:%s}', join( ',', $selectors ), esc_attr( $prop ), esc_attr( $value ) );

		wp_add_inline_style( 'sktpr_public', wp_kses_post( wp_strip_all_tags( $css ) ) );
	}

	/**
	 * Generate custom CSS based on settings and shortcode ID.
	 *
	 * @param array $settings The settings array.
	 * @return string The generated CSS.
	 */
	public function generate_custom_css( $settings ) {

		$buttons = array(
			'review_btn_'      => array( '.skt-input-field', ' #sktpr_modal_btn' ),
			'file_btn_'        => array( '.sktpr_file_uploader', ' .sktpr_upload_btn' ),
			'image_btn_'       => array( '.sktpr_image_uploader', ' .sktpr_upload_btn' ),
			'modal_start_btn_' => array( '.sktpr_record_video_buttons', ' #sktpr_startButton' ),
			'modal_stop_btn_'  => array( '.sktpr_record_video_buttons', ' #sktpr_stopButton' ),
			'modal_add_btn_'   => array( '.sktpr_record_video_buttons', ' #sktpr_addButton' ),
		);

		$families = $this->get_button_font_families();

		ob_start();

		foreach ( $buttons as $prefix => $selector ) {

			list( $parent, $target ) = $selector;

			if ( ! empty( $settings[ $prefix . 'color' ] ) ) {
				$this->generate_css( $parent, $target, 'background', $settings[ $prefix . 'color' ] );
			}

			if ( ! empty( $settings[ $prefix . 'txt_color' ] ) ) {
				$this->generate_css( $parent, $target, 'color', $settings[ $prefix . 'txt_color' ] );
			}

			if ( ! empty( $settings[ $prefix . 'hover_color' ] ) ) {
				$this->generate_css( $parent, $target . ':hover', 'background', $settings[ $prefix . 'hover_color' ] );
			}

			if ( ! empty( $settings[ $prefix . 'hover_txt_color' ] ) ) {
				$this->generate_css( $parent, $target . ':hover', 'color', $settings[ $prefix . 'hover_txt_color' ] );
			}

			if ( isset( $settings[ $prefix . 'border_style' ], $settings[ $prefix . 'border_width' ] ) ) {
				$border_style = $settings[ $prefix . 'border_style' ];
				$border_width = absint( $settings[ $prefix . 'border_width' ] );

				if ( 'none' === $border_style || 0 === $border_width ) {
					$this->generate_css( $parent, $target, 'border', 'none' );
				} else {
					$border_color = ! empty( $settings[ $prefix . 'border_color' ] ) ? $settings[ $prefix . 'border_color' ] : '#dfe2da';
					$this->generate_css( $parent, $target, 'border', $border_width . 'px ' . $border_style . ' ' . $border_color );

					if ( ! empty( $settings[ $prefix . 'hover_border_color' ] ) ) {
						$this->generate_css( $parent, $target . ':hover', 'border-color', $settings[ $prefix . 'hover_border_color' ] );
					}
				}
			}

			if ( isset( $settings[ $prefix . 'padding_y' ], $settings[ $prefix . 'padding_x' ] ) ) {
				$padding = absint( $settings[ $prefix . 'padding_y' ] ) . 'px ' . absint( $settings[ $prefix . 'padding_x' ] ) . 'px';
				$this->generate_css( $parent, $target, 'padding', $padding );
			}

			if ( isset( $settings[ $prefix . 'radius' ] ) ) {
				$this->generate_css( $parent, $target, 'border-radius', absint( $settings[ $prefix . 'radius' ] ) . 'px' );
			}

			if ( ! empty( $settings[ $prefix . 'font_size' ] ) ) {
				$this->generate_css( $parent, $target, 'font-size', absint( $settings[ $prefix . 'font_size' ] ) . 'px' );
			}

			if ( ! empty( $settings[ $prefix . 'icon_size' ] ) ) {
				$this->generate_css( $parent, $target . ' .fa', 'font-size', absint( $settings[ $prefix . 'icon_size' ] ) . 'px' );
			}

			if ( ! empty( $settings[ $prefix . 'font_family' ] ) && 'default' !== $settings[ $prefix . 'font_family' ] && ! empty( $families[ $settings[ $prefix . 'font_family' ] ] ) ) {
				$this->generate_css( $parent, $target, 'font-family', $families[ $settings[ $prefix . 'font_family' ] ] );
			}

			if ( ! empty( $settings[ $prefix . 'font_weight' ] ) ) {
				$this->generate_css( $parent, $target, 'font-weight', absint( $settings[ $prefix . 'font_weight' ] ) );
			}

			if ( ! empty( $settings[ $prefix . 'font_style' ] ) ) {
				$this->generate_css( $parent, $target, 'font-style', $settings[ $prefix . 'font_style' ] );
			}

			if ( ! empty( $settings[ $prefix . 'text_transform' ] ) ) {
				$this->generate_css( $parent, $target, 'text-transform', $settings[ $prefix . 'text_transform' ] );
			}
		}

		$this->generate_modal_css( $settings );

		$verified_badge_size = max( 10, min( 24, absint( $settings['verified_badge_size'] ?? 12 ) ) );
		$this->generate_css( '.sktpr-verified-badge', '', 'font-size', $verified_badge_size . 'px' );
		$badge_bg_color   = sanitize_hex_color( $settings['verified_badge_bg_color'] ?? '' );
		$badge_text_color = sanitize_hex_color( $settings['verified_badge_text_color'] ?? '' );

		$this->generate_css( '.sktpr-verified-badge', '', 'background-color', $badge_bg_color ? $badge_bg_color : '#E6F2EA' );
		$this->generate_css( '.sktpr-verified-badge', '', 'color', $badge_text_color ? $badge_text_color : '#2F7552' );

		return ob_get_clean();
	}

	/**
	 * Emits CSS for the recording modal container, backdrop, and title.
	 *
	 * @param array $settings The settings array.
	 */
	private function generate_modal_css( $settings ) {

		if ( ! empty( $settings['modal_bg_color'] ) ) {
			$this->generate_css( '.sktpr_modal-content', '', 'background-color', $settings['modal_bg_color'] );
		}

		if ( isset( $settings['modal_radius'] ) ) {
			$this->generate_css( '.sktpr_modal-content', '', 'border-radius', absint( $settings['modal_radius'] ) . 'px' );
		}

		if ( ! empty( $settings['modal_overlay_color'] ) && isset( $settings['modal_overlay_opacity'] ) ) {
			$overlay = $this->hex_to_rgba( $settings['modal_overlay_color'], absint( $settings['modal_overlay_opacity'] ) );
			if ( $overlay ) {
				$this->generate_css( '.sktpr_background', '', 'background-color', $overlay );
			}
		}

		if ( ! empty( $settings['modal_title_color'] ) ) {
			$this->generate_css( '.sktpr_modal-content-inner', ' h3', 'color', $settings['modal_title_color'] );
		}
	}

	/**
	 * Converts a hex color and 0-100 opacity into an rgba() value.
	 *
	 * @param string $hex Hex color, with or without leading '#'.
	 * @param int    $opacity Opacity percentage (0-100).
	 * @return string rgba() value, or empty string for invalid hex input.
	 */
	private function hex_to_rgba( $hex, $opacity ) {

		$hex = ltrim( trim( $hex ), '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return '';
		}

		return sprintf(
			'rgba(%d, %d, %d, %s)',
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
			rtrim( rtrim( number_format( min( 100, $opacity ) / 100, 2 ), '0' ), '.' )
		);
	}
}
