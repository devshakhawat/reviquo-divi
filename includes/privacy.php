<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Privacy
 *
 * Integrates with the WordPress privacy tools (Tools > Export/Erase Personal
 * Data). Exports and erases the personal data this plugin stores beyond core
 * comments: review media attachments (comment meta uploaded_video_url /
 * uploaded_image_url), GDPR consent timestamps, and email reminder rows in
 * the custom reminders table.
 */
class Privacy {

	/**
	 * Rows/comments processed per exporter or eraser page.
	 */
	const PAGE_SIZE = 50;

	/**
	 * Privacy constructor. Registers the exporters and erasers.
	 */
	public function __construct() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_action( 'delete_comment', array( $this, 'delete_comment_media' ) );
	}

	/**
	 * Add suggested disclosure text to WordPress's Privacy Policy Guide.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>' . esc_html__( 'When visitors submit product reviews, this site may collect the review text, rating, reviewer name and email address, consent timestamp, and any photos or videos attached to the review. Review media is stored in this site’s WordPress media library and may be displayed publicly with an approved review.', 'product-reviews' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If review reminder emails are enabled, the site stores the customer name and email address, order identifier, reminder status, schedule, and a snapshot of the email template. Customers can opt out using the unsubscribe link in a reminder. Review media and reminder records are included in WordPress personal-data export and erasure tools.', 'product-reviews' ) . '</p>';
		$content .= '<p>' . esc_html__( 'The site owner determines how long approved reviews, uploaded media, and reminder delivery records are retained. Deleting a review permanently also deletes media uploaded through the review form.', 'product-reviews' ) . '</p>';

		wp_add_privacy_policy_content( __( 'ReviQuo', 'product-reviews' ), wp_kses_post( $content ) );
	}

	/**
	 * Delete plugin-owned uploads when their review is deleted forever.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	public function delete_comment_media( $comment_id ) {
		foreach ( array( 'uploaded_video_url', 'uploaded_image_url' ) as $meta_key ) {
			$urls = get_comment_meta( $comment_id, $meta_key, true );

			foreach ( array_filter( (array) $urls ) as $url ) {
				$attachment_id = attachment_url_to_postid( $url );
				if ( $attachment_id && absint( get_post_meta( $attachment_id, '_sktpr_review_comment_id', true ) ) === absint( $comment_id ) ) {
					wp_delete_attachment( $attachment_id, true );
				}
			}
		}
	}

	/**
	 * Registers the plugin's personal data exporters.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public function register_exporters( $exporters ) {
		$exporters['sktpr-review-media'] = array(
			'exporter_friendly_name' => __( 'Product Review Media', 'product-reviews' ),
			'callback'               => array( $this, 'export_review_media' ),
		);

		$exporters['sktpr-email-reminders'] = array(
			'exporter_friendly_name' => __( 'Review Reminder Emails', 'product-reviews' ),
			'callback'               => array( $this, 'export_email_reminders' ),
		);

		$exporters['sktpr-review-rewards'] = array(
			'exporter_friendly_name' => __( 'Review Reward Coupons', 'product-reviews' ),
			'callback'               => array( $this, 'export_review_rewards' ),
		);

		return $exporters;
	}

	/**
	 * Registers the plugin's personal data erasers.
	 *
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public function register_erasers( $erasers ) {
		$erasers['sktpr-review-media'] = array(
			'eraser_friendly_name' => __( 'Product Review Media', 'product-reviews' ),
			'callback'             => array( $this, 'erase_review_media' ),
		);

		$erasers['sktpr-email-reminders'] = array(
			'eraser_friendly_name' => __( 'Review Reminder Emails', 'product-reviews' ),
			'callback'             => array( $this, 'erase_email_reminders' ),
		);

		$erasers['sktpr-review-rewards'] = array(
			'eraser_friendly_name' => __( 'Review Reward Coupons', 'product-reviews' ),
			'callback'             => array( $this, 'erase_review_rewards' ),
		);

		return $erasers;
	}

	/**
	 * Finds review comments by author email that carry plugin meta.
	 *
	 * Queried via $wpdb directly so results are unaffected by comment-query
	 * filters other plugins (including WooCommerce) may add.
	 *
	 * @param string $email Email address.
	 * @param int    $page 1-based page number.
	 * @return array Comment rows (comment_ID, comment_post_ID).
	 */
	private function get_media_comments( $email, $page ) {
		global $wpdb;

		$offset = ( max( 1, intval( $page ) ) - 1 ) * self::PAGE_SIZE;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- GDPR exporter needs a JOIN on comment meta that no core API exposes; results must be live, not cached.
			$wpdb->prepare(
				"SELECT DISTINCT c.comment_ID, c.comment_post_ID
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID
			WHERE c.comment_author_email = %s
			AND cm.meta_key IN ( 'uploaded_video_url', 'uploaded_image_url', 'sktpr_gdpr_consent' )
			ORDER BY c.comment_ID ASC
			LIMIT %d OFFSET %d",
				sanitize_email( $email ),
				self::PAGE_SIZE,
				$offset
			)
		);
	}

	/**
	 * Exporter callback: review media URLs and consent timestamps.
	 *
	 * @param string $email Email address being exported.
	 * @param int    $page Page number.
	 * @return array Export response.
	 */
	public function export_review_media( $email, $page = 1 ) {
		$comments = $this->get_media_comments( $email, $page );
		$items    = array();

		foreach ( $comments as $comment ) {
			$data = array();

			$video_urls = get_comment_meta( $comment->comment_ID, 'uploaded_video_url', true );
			foreach ( array_filter( (array) $video_urls ) as $url ) {
				$data[] = array(
					'name'  => __( 'Review video', 'product-reviews' ),
					'value' => $url,
				);
			}

			$image_urls = get_comment_meta( $comment->comment_ID, 'uploaded_image_url', true );
			foreach ( array_filter( (array) $image_urls ) as $url ) {
				$data[] = array(
					'name'  => __( 'Review photo', 'product-reviews' ),
					'value' => $url,
				);
			}

			$consent = get_comment_meta( $comment->comment_ID, 'sktpr_gdpr_consent', true );
			if ( $consent ) {
				$data[] = array(
					'name'  => __( 'Media consent given', 'product-reviews' ),
					'value' => $consent,
				);
			}

			if ( empty( $data ) ) {
				continue;
			}

			$data[] = array(
				'name'  => __( 'Reviewed product', 'product-reviews' ),
				'value' => get_the_title( $comment->comment_post_ID ),
			);

			$items[] = array(
				'group_id'    => 'sktpr-review-media',
				'group_label' => __( 'Product Review Media', 'product-reviews' ),
				'item_id'     => 'sktpr-comment-' . $comment->comment_ID,
				'data'        => $data,
			);
		}

		return array(
			'data' => $items,
			'done' => count( $comments ) < self::PAGE_SIZE,
		);
	}

	/**
	 * Exporter callback: email reminder rows and opt-out status.
	 *
	 * @param string $email Email address being exported.
	 * @param int    $page Page number.
	 * @return array Export response.
	 */
	public function export_email_reminders( $email, $page = 1 ) {
		$offset    = ( max( 1, intval( $page ) ) - 1 ) * self::PAGE_SIZE;
		$reminders = Database::get_reminders_by_email( $email, self::PAGE_SIZE, $offset );
		$items     = array();

		foreach ( $reminders as $reminder ) {
			$items[] = array(
				'group_id'    => 'sktpr-email-reminders',
				'group_label' => __( 'Review Reminder Emails', 'product-reviews' ),
				'item_id'     => 'sktpr-reminder-' . $reminder->id,
				'data'        => array(
					array(
						'name'  => __( 'Customer name', 'product-reviews' ),
						'value' => $reminder->customer_name,
					),
					array(
						'name'  => __( 'Customer email', 'product-reviews' ),
						'value' => $reminder->customer_email,
					),
					array(
						'name'  => __( 'Order ID', 'product-reviews' ),
						'value' => $reminder->order_id,
					),
					array(
						'name'  => __( 'Trigger status', 'product-reviews' ),
						'value' => $reminder->trigger_status,
					),
					array(
						'name'  => __( 'Status', 'product-reviews' ),
						'value' => $reminder->status,
					),
					array(
						'name'  => __( 'Scheduled for', 'product-reviews' ),
						'value' => $reminder->scheduled_time,
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => count( $reminders ) < self::PAGE_SIZE,
		);
	}

	/**
	 * Eraser callback: deletes review media attachments and plugin comment meta.
	 *
	 * The review comment itself is handled by WordPress core's comment eraser;
	 * this removes what core does not know about - the uploaded attachment
	 * files and the plugin's comment meta.
	 *
	 * @param string $email Email address being erased.
	 * @param int    $page Page number.
	 * @return array Eraser response.
	 */
	public function erase_review_media( $email, $page = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Required by the wp_privacy_personal_data_erasers signature.
		// Always erase page 1: rows disappear as they are erased, so advancing the offset would skip records.
		$comments = $this->get_media_comments( $email, 1 );
		$removed  = 0;

		foreach ( $comments as $comment ) {
			foreach ( array( 'uploaded_video_url', 'uploaded_image_url' ) as $meta_key ) {
				$urls = get_comment_meta( $comment->comment_ID, $meta_key, true );

				foreach ( array_filter( (array) $urls ) as $url ) {
					$attachment_id = attachment_url_to_postid( $url );
					if ( $attachment_id ) {
						wp_delete_attachment( $attachment_id, true );
					}
				}

				delete_comment_meta( $comment->comment_ID, $meta_key );
			}

			delete_comment_meta( $comment->comment_ID, 'sktpr_gdpr_consent' );
			++$removed;
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => count( $comments ) < self::PAGE_SIZE,
		);
	}

	/**
	 * Eraser callback: deletes reminder rows for the email address.
	 *
	 * The hashed opt-out list is intentionally retained - it contains no
	 * readable personal data and preserves the person's unsubscribe choice.
	 *
	 * @param string $email Email address being erased.
	 * @param int    $page Page number.
	 * @return array Eraser response.
	 */
	public function erase_email_reminders( $email, $page = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Required by the wp_privacy_personal_data_erasers signature.
		$removed = Database::delete_by_email( $email );

		return array(
			'items_removed'  => $removed,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Finds reward coupons issued to an email address.
	 *
	 * @param string $email Email address.
	 * @return int[] Coupon IDs.
	 */
	private function get_reward_coupon_ids( $email ) {
		return get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_sktpr_reward_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => strtolower( $email ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}

	/**
	 * Exporter callback: review reward coupons issued to the email address.
	 *
	 * @param string $email Email address being exported.
	 * @param int    $page Page number.
	 * @return array Export response.
	 */
	public function export_review_rewards( $email, $page = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Required by the wp_privacy_personal_data_exporters signature.
		$items = array();

		foreach ( $this->get_reward_coupon_ids( $email ) as $coupon_id ) {
			$coupon  = new \WC_Coupon( $coupon_id );
			$items[] = array(
				'group_id'    => 'sktpr-review-rewards',
				'group_label' => __( 'Review Reward Coupons', 'product-reviews' ),
				'item_id'     => 'sktpr-reward-' . $coupon_id,
				'data'        => array(
					array(
						'name'  => __( 'Coupon code', 'product-reviews' ),
						'value' => $coupon->get_code(),
					),
					array(
						'name'  => __( 'Review ID', 'product-reviews' ),
						'value' => $coupon->get_meta( '_sktpr_reward_comment_id' ),
					),
					array(
						'name'  => __( 'Times used', 'product-reviews' ),
						'value' => $coupon->get_usage_count(),
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/**
	 * Eraser callback: deletes unused reward coupons and detaches the email
	 * address from used ones (used coupons stay referenced by orders).
	 *
	 * @param string $email Email address being erased.
	 * @param int    $page Page number.
	 * @return array Eraser response.
	 */
	public function erase_review_rewards( $email, $page = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Required by the wp_privacy_personal_data_erasers signature.
		$removed = 0;

		foreach ( $this->get_reward_coupon_ids( $email ) as $coupon_id ) {
			$coupon = new \WC_Coupon( $coupon_id );

			if ( 0 === $coupon->get_usage_count() ) {
				$coupon->delete( true );
			} else {
				$coupon->set_email_restrictions( array() );
				$coupon->delete_meta_data( '_sktpr_reward_email' );
				$coupon->save();
			}

			++$removed;
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
