<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Save_Video
 *
 * Handles the saving of video files uploaded with comments.
 */
class Save_Video {

	use Helpers;

	/**
	 * Save_Video constructor.
	 * Adds the action to save video on comment post.
	 */
	public function __construct() {
		add_action( 'comment_post', array( $this, 'save_video' ) );
		add_filter( 'preprocess_comment', array( $this, 'enforce_required_media' ) );
		add_filter( 'pre_comment_approved', array( $this, 'set_video_review_status' ), 10, 2 );
	}

	/**
	 * Video MIME types accepted from the public review form.
	 *
	 * @return array
	 */
	private function get_video_mimes() {
		return array(
			'mp4|m4v' => 'video/mp4',
			'webm'    => 'video/webm',
			'mkv'     => 'video/x-matroska',
		);
	}

	/**
	 * Image MIME types accepted from the public review form.
	 *
	 * @return array
	 */
	private function get_image_mimes() {
		return array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		);
	}

	/**
	 * Verify the nonce printed in the WooCommerce review form.
	 *
	 * @return bool
	 */
	private function has_valid_upload_nonce() {
		return isset( $_POST['sktpr_review_media_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sktpr_review_media_nonce'] ) ), 'sktpr_review_media' );
	}

	/**
	 * Check whether at least one upload has a valid name, extension, and size.
	 *
	 * @param array $file  Uploaded file array.
	 * @param array $mimes Allowed extension-to-MIME map.
	 * @return bool
	 */
	private function has_valid_upload( $file, $mimes ) {
		$names  = (array) ( $file['name'] ?? array() );
		$errors = (array) ( $file['error'] ?? array() );
		$sizes  = (array) ( $file['size'] ?? array() );
		$limit  = wp_max_upload_size();

		foreach ( $names as $index => $name ) {
			$name  = sanitize_file_name( $name );
			$error = absint( $errors[ $index ] ?? UPLOAD_ERR_NO_FILE );
			$size  = absint( $sizes[ $index ] ?? 0 );
			$type  = wp_check_filetype( $name, $mimes );

			if ( UPLOAD_ERR_OK === $error && $size > 0 && $size <= $limit && ! empty( $type['type'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Blocks frontend product reviews that are missing required media.
	 *
	 * Mirrors the client-side checks in public.js so the "required" settings
	 * cannot be bypassed by disabling JavaScript. A requirement only applies
	 * while its uploader is enabled, otherwise submission would be impossible.
	 *
	 * @param array $commentdata Comment data.
	 * @return array Unmodified comment data.
	 */
	public function enforce_required_media( $commentdata ) {

		if ( is_admin() || ! empty( $commentdata['comment_parent'] ) ) {
			return $commentdata;
		}

		if ( 'product' !== get_post_type( $commentdata['comment_post_ID'] ?? 0 ) ) {
			return $commentdata;
		}

		$settings             = $this->get_settings();
		$requires_plugin_data = ! empty( $settings['enable_gdpr_consent'] ) ||
			( ! empty( $settings['enable_video_btn'] ) && ! empty( $settings['required_video_recording'] ) ) ||
			( ! empty( $settings['show_file_uploader'] ) && ! empty( $settings['required_file_upload'] ) ) ||
			( ! empty( $settings['show_image_uploader'] ) && ! empty( $settings['required_image_upload'] ) );

		if ( $requires_plugin_data && ! $this->has_valid_upload_nonce() ) {
			wp_die(
				esc_html__( 'The review form expired. Please reload the page and try again.', 'product-reviews' ),
				esc_html__( 'Review submission error', 'product-reviews' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above whenever consent is enabled.
		if ( ! empty( $settings['enable_gdpr_consent'] ) && empty( $_POST['sktpr_gdpr_consent'] ) ) {
			wp_die(
				esc_html__( 'Please check the consent box to submit your review.', 'product-reviews' ),
				esc_html__( 'Review submission error', 'product-reviews' ),
				array(
					'response'  => 400,
					'back_link' => true,
				)
			);
		}

		$rules = array(
			'sktpr_client_video_upload' => array(
				'required' => ( $settings['enable_video_btn'] ?? false ) && ( $settings['required_video_recording'] ?? false ),
				'message'  => __( 'Please record a video testimonial before submitting your review.', 'product-reviews' ),
				'mimes'    => $this->get_video_mimes(),
			),
			'sktpr_file_upload'         => array(
				'required' => ( $settings['show_file_uploader'] ?? false ) && ( $settings['required_file_upload'] ?? false ),
				'message'  => __( 'Please upload a video file before submitting your review.', 'product-reviews' ),
				'mimes'    => $this->get_video_mimes(),
			),
			'sktpr_image_upload'        => array(
				'required' => ( $settings['show_image_uploader'] ?? false ) && ( $settings['required_image_upload'] ?? false ),
				'message'  => __( 'Please upload at least one photo before submitting your review.', 'product-reviews' ),
				'mimes'    => $this->get_image_mimes(),
			),
		);

		foreach ( $rules as $file_key => $rule ) {
			if ( ! $rule['required'] ) {
				continue;
			}

			$file = isset( $_FILES[ $file_key ] ) && is_array( $_FILES[ $file_key ] ) ? $_FILES[ $file_key ] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above; names, extensions and sizes are validated by has_valid_upload().

			if ( ! $this->has_valid_upload( $file, $rule['mimes'] ) ) {
				wp_die(
					esc_html( $rule['message'] ),
					esc_html__( 'Review submission error', 'product-reviews' ),
					array(
						'response'  => 400,
						'back_link' => true,
					)
				);
			}
		}

		return $commentdata;
	}

	/**
	 * Saves the media uploaded with a product review.
	 *
	 * Uploads are restricted to the review form's video and image MIME types,
	 * attached to the reviewed product, and tagged with the review ID so
	 * Privacy::delete_comment_media() can remove them with the review.
	 *
	 * @param int $comment_id The ID of the comment.
	 */
	public function save_video( $comment_id ) {

		if ( ! $this->has_valid_upload_nonce() ) {
			return;
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment || 'product' !== get_post_type( $comment->comment_post_ID ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_urls = array();
		foreach ( array( 'sktpr_client_video_upload', 'sktpr_file_upload' ) as $file_key ) {
			$file = isset( $_FILES[ $file_key ] ) && is_array( $_FILES[ $file_key ] ) ? $_FILES[ $file_key ] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WordPress validates the temporary upload against the restricted MIME map below.
			if ( ! $this->has_valid_upload( $file, $this->get_video_mimes() ) ) {
				continue;
			}

			$attachment_id = media_handle_upload(
				$file_key,
				$comment->comment_post_ID,
				array(),
				array(
					'test_form' => false,
					'mimes'     => $this->get_video_mimes(),
				)
			);

			if ( ! is_wp_error( $attachment_id ) ) {
				update_post_meta( $attachment_id, '_sktpr_review_comment_id', absint( $comment_id ) );
				$attachment_urls[] = wp_get_attachment_url( $attachment_id );
			}
		}

		if ( $attachment_urls ) {
			update_comment_meta( $comment_id, 'uploaded_video_url', array_filter( $attachment_urls ) );
		}

		$image_urls = $this->handle_image_uploads( $comment_id, $comment->comment_post_ID );
		if ( $image_urls ) {
			update_comment_meta( $comment_id, 'uploaded_image_url', $image_urls );
		}

		if ( ! empty( $_POST['sktpr_gdpr_consent'] ) ) {
			update_comment_meta( $comment_id, 'sktpr_gdpr_consent', current_time( 'mysql' ) );
		}
	}

	/**
	 * Saves the images uploaded with a comment.
	 *
	 * The image field is a multi-file input, so each file is re-shaped into its
	 * own $_FILES entry before being passed to media_handle_upload(). At most 5
	 * images are processed per review.
	 *
	 * @param int $comment_id The ID of the comment.
	 * @param int $product_id The reviewed product ID.
	 * @return array Attachment URLs of the saved images.
	 */
	private function handle_image_uploads( $comment_id, $product_id ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in save_video().
		if ( ! isset( $_FILES['sktpr_image_upload'] ) || ! is_array( $_FILES['sktpr_image_upload']['name'] ?? null ) ) {
			return array();
		}

		$files               = $_FILES['sktpr_image_upload']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- handled per file below.
		$allowed_image_mimes = $this->get_image_mimes();
		$max_images          = 5;
		$urls                = array();
		$total               = min( count( $files['name'] ), $max_images );

		for ( $i = 0; $i < $total; $i++ ) {

			if ( empty( $files['name'][ $i ] ) || ! empty( $files['error'][ $i ] ) ) {
				continue;
			}

			$file = array(
				'name'  => $files['name'][ $i ],
				'error' => $files['error'][ $i ],
				'size'  => $files['size'][ $i ],
			);
			if ( ! $this->has_valid_upload( $file, $allowed_image_mimes ) ) {
				continue;
			}

			$_FILES['sktpr_single_image'] = array(
				'name'     => sanitize_file_name( $files['name'][ $i ] ),
				'type'     => sanitize_text_field( $files['type'][ $i ] ),
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => absint( $files['size'][ $i ] ),
			);

			$attachment_id = media_handle_upload(
				'sktpr_single_image',
				$product_id,
				array(),
				array(
					'test_form' => false,
					'mimes'     => $allowed_image_mimes,
				)
			);

			if ( ! is_wp_error( $attachment_id ) && $attachment_id > 0 ) {
				update_post_meta( $attachment_id, '_sktpr_review_comment_id', absint( $comment_id ) );
				$urls[] = wp_get_attachment_url( $attachment_id );
			}
		}

		unset( $_FILES['sktpr_single_image'] );

		return $urls;
	}

	/**
	 * Set video reviews to pending status by default
	 *
	 * @param int|string|\WP_Error $approved The approval status.
	 * @param array                $commentdata Comment data.
	 * @return int|string|\WP_Error Modified approval status
	 */
	public function set_video_review_status( $approved, $commentdata ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Required by the pre_comment_approved filter signature.
		// Check if this is a video review submission.
		$has_video = false;

		// Check for video files in the submission.
		if ( isset( $_FILES['sktpr_client_video_upload'] ) && ! empty( $_FILES['sktpr_client_video_upload']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Presence check only; the upload itself is nonce-verified in save_video().
			$has_video = true;
		}

		if ( isset( $_FILES['sktpr_file_upload'] ) && ! empty( $_FILES['sktpr_file_upload']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Presence check only; the upload itself is nonce-verified in save_video().
			$has_video = true;
		}

		// Image reviews go through the same moderation gate as video reviews.
		if ( isset( $_FILES['sktpr_image_upload']['name'] ) && is_array( $_FILES['sktpr_image_upload']['name'] ) && ! empty( array_filter( $_FILES['sktpr_image_upload']['name'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Presence check only; the upload itself is nonce-verified and sanitized in save_video().
			$has_video = true;
		}

		// If this is a video review, set it to pending for admin approval.
		if ( $has_video ) {
			// Check if admin wants video reviews to be auto-approved.
			$settings            = $this->get_settings();
			$auto_approve_videos = isset( $settings['auto_approve_video_reviews'] ) ? $settings['auto_approve_video_reviews'] : false;

			if ( ! $auto_approve_videos ) {
				// Add action to notify admin about pending video review.
				add_action( 'comment_post', array( $this, 'notify_admin_pending_video_review' ), 20 );

				// Set to pending (0) for admin approval.
				return 0;
			}
		}

		// Return original approval status for non-video reviews.
		return $approved;
	}

	/**
	 * Notify admin about pending video review
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function notify_admin_pending_video_review( $comment_id ) {
		// Photo-only reviews are held for moderation too, so notify for any saved media.
		$video_urls = get_comment_meta( $comment_id, 'uploaded_video_url', true );
		$image_urls = get_comment_meta( $comment_id, 'uploaded_image_url', true );
		if ( empty( $video_urls ) && empty( $image_urls ) ) {
			return;
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment || '0' !== $comment->comment_approved ) {
			return;
		}

		// Get admin email.
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );
		$product     = get_post( $comment->comment_post_ID );

		// Email subject.
		$subject = sprintf(
			/* translators: %s: Site name. */
			__( '[%s] New Media Review Awaiting Approval', 'product-reviews' ),
			$site_name
		);

		// Email message.
		$message = sprintf(
			/* translators: 1: Product name. 2: Review author name. 3: Review author email. 4: Excerpt of the review. 5: URL to edit the comment. 6: URL to the media reviews list. */
			__( "A new review with customer media has been submitted and is awaiting your approval.\n\nProduct: %1\$s\nAuthor: %2\$s (%3\$s)\nReview: %4\$s\n\nPlease review and approve it here:\n%5\$s\n\nYou can manage all media reviews here:\n%6\$s", 'product-reviews' ),
			$product ? $product->post_title : __( 'Unknown Product', 'product-reviews' ),
			$comment->comment_author,
			$comment->comment_author_email,
			wp_trim_words( $comment->comment_content, 20 ),
			admin_url( 'comment.php?action=editcomment&c=' . $comment_id ),
			admin_url( 'admin.php?page=video-reviews-list&comment_status=pending' )
		);

		// Send notification email.
		wp_mail( $admin_email, $subject, $message );

		// Add admin notice for next admin page load.
		$this->add_admin_notice_for_pending_review( $comment_id );
	}

	/**
	 * Add admin notice for pending video review
	 *
	 * @param int $comment_id Comment ID.
	 */
	private function add_admin_notice_for_pending_review( $comment_id ) {
		$pending_notices = get_transient( 'sktpr_pending_video_reviews' );
		if ( ! is_array( $pending_notices ) ) {
			$pending_notices = array();
		}

		$pending_notices[] = $comment_id;
		set_transient( 'sktpr_pending_video_reviews', $pending_notices, DAY_IN_SECONDS );
	}
}
