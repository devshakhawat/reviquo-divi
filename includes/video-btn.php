<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Video_Btn
 *
 * Handles the addition of a video button to the WooCommerce product review form.
 */
class Video_Btn {

	use Helpers;

	/**
	 * Video_Btn constructor.
	 * Adds actions and filters for the video button functionality.
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'add_enctype_to_review_form' ) );
		add_filter( 'woocommerce_product_review_comment_form_args', array( $this, 'add_custom_review_field' ) );
	}

	/**
	 * Adds enctype attribute to the review form.
	 */
	public function add_enctype_to_review_form() {

		if ( is_product() ) {
			wp_enqueue_script( 'sktpr_public' );
		}
	}

	/**
	 * Adds a custom video upload field to the WooCommerce product review form.
	 *
	 * @param array $args The arguments for the comment form.
	 * @return array Modified comment form arguments.
	 */
	public function add_custom_review_field( $args ) {

		$settings = $this->get_settings();

		$button = '';
		if ( $settings['enable_video_btn'] ) {

			$icon_html = $this->button_icon_html( $settings, 'review_btn_icon', 'video-camera' );

			$button = sprintf(
				'<div class="skt-input-field">
							<div class="skt-video-wrapper sktpr-initially-hidden">
								<video playsinline controls src="" type="video/mp4"></video>
							</div>
							<div class="sktpr_video_uploader"><a href="#" id="sktpr_modal_btn">%s%s</a></div>
							<input type="file" name="sktpr_client_video_upload" id="sktpr_client_video_upload" accept="video/mp4, video/x-m4v,video/webm,video/*" />
						</div>',
				$icon_html,
				esc_html( $settings['review_btn_text'] )
			);
		}

		$rating = '';
		if ( wc_review_ratings_enabled() ) {
			$rating = '<div class="comment-form-rating">
			<label for="rating">' . esc_html__( 'Your rating', 'product-reviews' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label>
						<select name="rating" id="rating" required>
							<option value="">' . esc_html__( 'Rate…', 'product-reviews' ) . '</option>
							<option value="5">' . esc_html__( 'Perfect', 'product-reviews' ) . '</option>
							<option value="4">' . esc_html__( 'Good', 'product-reviews' ) . '</option>
							<option value="3">' . esc_html__( 'Average', 'product-reviews' ) . '</option>
							<option value="2">' . esc_html__( 'Not that bad', 'product-reviews' ) . '</option>
							<option value="1">' . esc_html__( 'Very poor', 'product-reviews' ) . '</option>
						</select>
			</div>';
		}

		$modal = $this->modal_html();

		$text_comment = '<p class="comment-form-comment">
                <label for="comment">' . esc_html__( 'Your review', 'product-reviews' ) . '&nbsp;<span class="required">*</span></label>
                <textarea id="comment" name="comment" cols="45" rows="8" required></textarea>
            </p>';

		$file_input = '';
		if ( $settings['show_file_uploader'] ) {
			$file_input = sprintf(
				'<div class="sktpr_file_uploader">
							<label for="sktpr_file_upload" class="sktpr_upload_btn">
								%s%s<span class="sktpr_video_upload_hint">%s</span>
							</label>
							<button type="button" class="sktpr_video_file_clear sktpr-initially-hidden" aria-label="%s">&times;</button>
							<input type="file" name="sktpr_file_upload" id="sktpr_file_upload" accept="video/mp4, video/x-m4v,video/webm,video/*">
						</div>
						<video playsinline controls class="inp_file_video sktpr-initially-hidden" type="video/mp4"></video>',
				$this->button_icon_html( $settings, 'file_btn_icon', 'file-video-o' ),
				esc_html( $settings['file_btn_text'] ?? __( 'Upload video', 'product-reviews' ) ),
				esc_html__( 'MP4 or WebM', 'product-reviews' ),
				esc_attr__( 'Remove video', 'product-reviews' )
			);
		}

		$image_input = '';
		if ( ! empty( $settings['show_image_uploader'] ) ) {
			$image_input = sprintf(
				'<div class="sktpr_image_uploader">
							<label for="sktpr_image_upload" class="sktpr_upload_btn">
								%s%s<span class="sktpr_image_upload_hint">%s</span>
							</label>
							<input type="file" name="sktpr_image_upload[]" id="sktpr_image_upload" accept="image/jpeg,image/png,image/gif,image/webp" multiple />
							<div class="sktpr_image_previews"></div>
						</div>',
				$this->button_icon_html( $settings, 'image_btn_icon', 'camera' ),
				esc_html( $settings['image_btn_text'] ?? __( 'Add photos', 'product-reviews' ) ),
				esc_html__( 'up to 5', 'product-reviews' )
			);
		}

		$consent = '';
		if ( ! empty( $settings['enable_gdpr_consent'] ) ) {
			$consent = sprintf(
				'<p class="comment-form-sktpr-consent">
							<label for="sktpr_gdpr_consent">
								<input type="checkbox" name="sktpr_gdpr_consent" id="sktpr_gdpr_consent" value="1" required />
								%s&nbsp;<span class="required">*</span>
							</label>
						</p>',
				esc_html( $settings['gdpr_consent_text'] ?? __( 'I consent to my name, review, and any uploaded photos or videos being stored and displayed publicly on this site.', 'product-reviews' ) )
			);
		}

		$reward_notice = plugin()->review_rewards->get_form_notice_html();

		$args['comment_field']     = sprintf( '<div class="comment-form-rating">%s %s</div>%s %s %s %s %s %s', $rating, $modal, $text_comment, $reward_notice, $button, $file_input, $image_input, $consent );
		$args['fields']['comment'] = false;

		return $args;
	}
}
