<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Review_Rewards
 *
 * Issues a single-use WooCommerce coupon when an eligible product review is
 * approved, and emails it to the reviewer (Pro feature).
 *
 * The reward never depends on the star rating: incentives conditioned on
 * positive sentiment violate FTC and Google review policies.
 */
class Review_Rewards {

	use Helpers;

	/**
	 * Comment meta holding the issued coupon ID.
	 */
	const COUPON_META = '_sktpr_reward_coupon_id';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_sktpr_save_reward_settings', array( $this, 'handle_ajax_settings_save' ) );
		add_action( 'comment_post', array( $this, 'handle_new_comment' ), 30, 2 );
		add_action( 'transition_comment_status', array( $this, 'handle_status_transition' ), 10, 3 );
		add_action( 'delete_comment', array( $this, 'handle_deleted_comment' ) );
	}

	/**
	 * Saves review reward settings from the React admin screen.
	 *
	 * @return void
	 */
	public function handle_ajax_settings_save() {
		check_admin_referer( 'sktpr_plugin_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'status' => __( 'You do not have permission to update these settings.', 'product-reviews' ) ), 403 );
		}

		$current  = $this->get_stored_reward_settings();
		$settings = $this->sanitize_reward_settings( wp_unslash( $_POST ) );
		$error    = $this->validate_reward_settings( $settings );

		if ( $error ) {
			wp_send_json_error( array( 'status' => $error ), 400 );
		}

		$settings_to_store = Features::preserve_locked_reward_settings( $settings, $current );
		$this->update_reward_settings( $settings_to_store );

		wp_send_json_success(
			array(
				'status'   => __( 'Review reward settings saved successfully!', 'product-reviews' ),
				'settings' => Features::apply_reward_settings( $settings_to_store ),
			)
		);
	}

	/**
	 * Sanitize submitted reward settings.
	 *
	 * @param array $post_data POST data.
	 * @return array
	 */
	private function sanitize_reward_settings( $post_data ) {
		$defaults = $this->get_reward_defaults();
		$tiers    = $post_data['tiers'] ?? array();

		if ( is_string( $tiers ) ) {
			$tiers = json_decode( $tiers, true );
		}

		if ( ! is_array( $tiers ) ) {
			$tiers = array();
		}

		$clean_tiers = array();
		foreach ( $this->get_reward_tier_keys() as $tier ) {
			$submitted = isset( $tiers[ $tier ] ) && is_array( $tiers[ $tier ] ) ? $tiers[ $tier ] : array();
			$type      = in_array( $submitted['discount_type'] ?? '', array( 'percent', 'fixed_cart' ), true ) ? $submitted['discount_type'] : 'percent';
			$amount    = max( 0, (float) wc_format_decimal( $submitted['amount'] ?? 0 ) );

			$clean_tiers[ $tier ] = array(
				'enabled'       => ! empty( $submitted['enabled'] ),
				'discount_type' => $type,
				'amount'        => 'percent' === $type ? min( 100, $amount ) : $amount,
			);
		}

		$minimum_amount = wc_format_decimal( $post_data['minimum_amount'] ?? '' );
		$reward_limit   = sanitize_key( $post_data['reward_limit'] ?? $defaults['reward_limit'] );
		$coupon_prefix  = strtoupper( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $post_data['coupon_prefix'] ?? '' ) ) );

		return array(
			'enable_review_rewards'  => isset( $post_data['enable_review_rewards'] ),
			'tiers'                  => $clean_tiers,
			'coupon_prefix'          => substr( $coupon_prefix, 0, 20 ),
			'expiry_days'            => max( 0, min( 3650, absint( $post_data['expiry_days'] ?? $defaults['expiry_days'] ) ) ),
			'minimum_amount'         => '' === $minimum_amount ? '' : max( 0, (float) $minimum_amount ),
			'individual_use'         => isset( $post_data['individual_use'] ),
			'exclude_sale_items'     => isset( $post_data['exclude_sale_items'] ),
			'require_verified_owner' => isset( $post_data['require_verified_owner'] ),
			'min_review_length'      => max( 0, min( 5000, absint( $post_data['min_review_length'] ?? 0 ) ) ),
			'reward_limit'           => in_array( $reward_limit, array( 'product', 'order', 'none' ), true ) ? $reward_limit : $defaults['reward_limit'],
			'revoke_on_unapprove'    => isset( $post_data['revoke_on_unapprove'] ),
			'email_subject'          => sanitize_text_field( $post_data['email_subject'] ?? '' ),
			'email_content'          => wp_kses_post( $post_data['email_content'] ?? '' ),
			'show_form_notice'       => isset( $post_data['show_form_notice'] ),
			'form_notice_text'       => sanitize_text_field( $post_data['form_notice_text'] ?? '' ),
		);
	}

	/**
	 * Validate normalized reward settings.
	 *
	 * @param array $settings Normalized settings.
	 * @return string Validation message, or an empty string.
	 */
	private function validate_reward_settings( $settings ) {
		if ( empty( $settings['enable_review_rewards'] ) ) {
			return '';
		}

		$enabled_tiers = array_filter(
			$settings['tiers'],
			function ( $tier ) {
				return ! empty( $tier['enabled'] );
			}
		);

		if ( empty( $enabled_tiers ) ) {
			return __( 'Enable at least one reward tier.', 'product-reviews' );
		}

		foreach ( $enabled_tiers as $tier ) {
			if ( $tier['amount'] <= 0 ) {
				return __( 'Every enabled reward tier needs a discount amount greater than zero.', 'product-reviews' );
			}
		}

		if ( '' === $settings['email_subject'] || '' === trim( wp_strip_all_tags( $settings['email_content'] ) ) ) {
			return __( 'The reward email needs a subject and content.', 'product-reviews' );
		}

		if ( false === strpos( $settings['email_content'], '{coupon_code}' ) ) {
			return __( 'The reward email content must include the {coupon_code} placeholder.', 'product-reviews' );
		}

		return '';
	}

	/**
	 * Issue a reward for reviews that are approved on submission.
	 *
	 * Runs after Save_Video::save_video() so the media meta is in place.
	 *
	 * @param int        $comment_id Comment ID.
	 * @param int|string $approved   1, 0 or 'spam'.
	 * @return void
	 */
	public function handle_new_comment( $comment_id, $approved ) {
		if ( 1 === (int) $approved ) {
			$this->maybe_issue_reward( get_comment( $comment_id ) );
		}
	}

	/**
	 * Issue, suspend or restore a reward when a review changes status.
	 *
	 * @param string      $new_status New status.
	 * @param string      $old_status Old status.
	 * @param \WP_Comment $comment    Comment.
	 * @return void
	 */
	public function handle_status_transition( $new_status, $old_status, $comment ) {
		if ( 'approved' === $new_status ) {
			if ( ! $this->restore_coupon( $comment ) ) {
				$this->maybe_issue_reward( $comment );
			}
			return;
		}

		if ( 'approved' === $old_status ) {
			$settings = $this->get_stored_reward_settings();
			if ( ! empty( $settings['revoke_on_unapprove'] ) ) {
				$this->suspend_coupon( $comment );
			}
		}
	}

	/**
	 * Remove an unused reward coupon when its review is deleted permanently.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	public function handle_deleted_comment( $comment_id ) {
		$coupon = $this->get_comment_coupon( $comment_id );

		if ( $coupon && 0 === $coupon->get_usage_count() ) {
			$coupon->delete( true );
		}
	}

	/**
	 * Create and email a reward coupon when the review is eligible.
	 *
	 * @param \WP_Comment|null $comment Comment.
	 * @return int Coupon ID, or 0 when no reward was issued.
	 */
	public function maybe_issue_reward( $comment ) {
		if ( ! $comment instanceof \WP_Comment || ! class_exists( 'WC_Coupon' ) ) {
			return 0;
		}

		$settings = $this->get_reward_settings();
		if ( empty( $settings['enable_review_rewards'] ) ) {
			return 0;
		}

		$email = sanitize_email( $comment->comment_author_email );
		if (
			'product' !== get_post_type( $comment->comment_post_ID ) ||
			! in_array( $comment->comment_type, array( 'review', 'comment', '' ), true ) ||
			0 !== (int) $comment->comment_parent ||
			! is_email( $email ) ||
			get_comment_meta( $comment->comment_ID, self::COUPON_META, true )
		) {
			return 0;
		}

		$tier     = $this->resolve_tier( $comment, $settings );
		$order_id = 'order' === $settings['reward_limit'] ? $this->find_order_id( $email, (int) $comment->comment_post_ID ) : 0;
		$eligible = $tier && $this->passes_eligibility_rules( $comment, $email, $order_id, $settings );

		/**
		 * Filters whether a review earns a reward coupon.
		 *
		 * @param bool        $eligible Whether the review is eligible.
		 * @param \WP_Comment $comment  Review comment.
		 * @param string      $tier     Resolved tier (text, photo, video) or empty.
		 */
		if ( ! apply_filters( 'sktpr_review_reward_is_eligible', $eligible, $comment, $tier ) || ! $tier ) {
			return 0;
		}

		$coupon_id = $this->create_coupon( $comment, $email, $tier, $order_id, $settings );
		if ( ! $coupon_id ) {
			return 0;
		}

		update_comment_meta( $comment->comment_ID, self::COUPON_META, $coupon_id );
		update_comment_meta( $comment->comment_ID, '_sktpr_reward_tier', $tier );

		$this->send_reward_email( $comment, new \WC_Coupon( $coupon_id ), $settings );

		/**
		 * Fires after a review reward coupon has been issued.
		 *
		 * @param int         $coupon_id Coupon ID.
		 * @param \WP_Comment $comment   Review comment.
		 * @param string      $tier      Reward tier.
		 */
		do_action( 'sktpr_review_reward_issued', $coupon_id, $comment, $tier );

		return $coupon_id;
	}

	/**
	 * Resolve the most valuable enabled tier the review qualifies for.
	 *
	 * Uses the saved media meta rather than $_FILES, so only uploads that were
	 * actually stored count.
	 *
	 * @param \WP_Comment $comment  Comment.
	 * @param array       $settings Reward settings.
	 * @return string Tier key, or an empty string.
	 */
	private function resolve_tier( $comment, $settings ) {
		$qualifies = array(
			'video' => ! empty( get_comment_meta( $comment->comment_ID, 'uploaded_video_url', true ) ),
			'photo' => ! empty( get_comment_meta( $comment->comment_ID, 'uploaded_image_url', true ) ),
			'text'  => true,
		);

		foreach ( $qualifies as $tier => $qualified ) {
			if ( $qualified && ! empty( $settings['tiers'][ $tier ]['enabled'] ) ) {
				return $tier;
			}
		}

		return '';
	}

	/**
	 * Apply the admin-configured eligibility rules.
	 *
	 * @param \WP_Comment $comment  Comment.
	 * @param string      $email    Reviewer email.
	 * @param int         $order_id Matching order ID (order limit only).
	 * @param array       $settings Reward settings.
	 * @return bool
	 */
	private function passes_eligibility_rules( $comment, $email, $order_id, $settings ) {
		if ( ! empty( $settings['require_verified_owner'] ) && ! wc_review_is_from_verified_owner( $comment->comment_ID ) ) {
			return false;
		}

		$length = function_exists( 'mb_strlen' ) ? mb_strlen( trim( wp_strip_all_tags( $comment->comment_content ) ) ) : strlen( trim( wp_strip_all_tags( $comment->comment_content ) ) );
		if ( $length < absint( $settings['min_review_length'] ) ) {
			return false;
		}

		$meta_query = array(
			array(
				'key'   => '_sktpr_reward_email',
				'value' => strtolower( $email ),
			),
		);

		if ( 'order' === $settings['reward_limit'] && $order_id ) {
			$meta_query[] = array(
				'key'   => '_sktpr_reward_order_id',
				'value' => $order_id,
			);
		} elseif ( 'none' !== $settings['reward_limit'] ) {
			// The product limit is also the fallback when no matching order exists.
			$meta_query[] = array(
				'key'   => '_sktpr_reward_product_id',
				'value' => (int) $comment->comment_post_ID,
			);
		} else {
			return true;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		return empty( $existing );
	}

	/**
	 * Find the reviewer's most recent paid order containing the product.
	 *
	 * @param string $email      Reviewer email.
	 * @param int    $product_id Product ID.
	 * @return int Order ID, or 0.
	 */
	private function find_order_id( $email, $product_id ) {
		$orders = wc_get_orders(
			array(
				'billing_email' => $email,
				'status'        => wc_get_is_paid_statuses(),
				'limit'         => 20,
				'orderby'       => 'date',
				'order'         => 'DESC',
			)
		);

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( in_array( $product_id, array( (int) $item->get_product_id(), (int) $item->get_variation_id() ), true ) ) {
					return $order->get_id();
				}
			}
		}

		return 0;
	}

	/**
	 * Create the reward coupon.
	 *
	 * @param \WP_Comment $comment  Comment.
	 * @param string      $email    Reviewer email.
	 * @param string      $tier     Reward tier.
	 * @param int         $order_id Matching order ID, or 0.
	 * @param array       $settings Reward settings.
	 * @return int Coupon ID, or 0 on failure.
	 */
	private function create_coupon( $comment, $email, $tier, $order_id, $settings ) {
		$tier_settings = $settings['tiers'][ $tier ];

		try {
			$coupon = new \WC_Coupon();
			$coupon->set_code( $this->generate_coupon_code( $settings['coupon_prefix'] ) );
			$coupon->set_discount_type( $tier_settings['discount_type'] );
			$coupon->set_amount( $tier_settings['amount'] );
			$coupon->set_usage_limit( 1 );
			$coupon->set_usage_limit_per_user( 1 );
			$coupon->set_email_restrictions( array( $email ) );
			$coupon->set_individual_use( ! empty( $settings['individual_use'] ) );
			$coupon->set_exclude_sale_items( ! empty( $settings['exclude_sale_items'] ) );
			$coupon->set_description(
				sprintf(
					/* translators: %d: review comment ID. */
					__( 'ReviQuo reward for review #%d', 'product-reviews' ),
					$comment->comment_ID
				)
			);

			if ( '' !== $settings['minimum_amount'] ) {
				$coupon->set_minimum_amount( $settings['minimum_amount'] );
			}

			if ( absint( $settings['expiry_days'] ) > 0 ) {
				$coupon->set_date_expires( time() + absint( $settings['expiry_days'] ) * DAY_IN_SECONDS );
			}

			$coupon->update_meta_data( '_sktpr_reward_comment_id', (int) $comment->comment_ID );
			$coupon->update_meta_data( '_sktpr_reward_email', strtolower( $email ) );
			$coupon->update_meta_data( '_sktpr_reward_product_id', (int) $comment->comment_post_ID );
			$coupon->update_meta_data( '_sktpr_reward_tier', $tier );
			if ( $order_id ) {
				$coupon->update_meta_data( '_sktpr_reward_order_id', $order_id );
			}

			/**
			 * Fires before a review reward coupon is saved, to customize it.
			 *
			 * @param \WC_Coupon  $coupon  Unsaved coupon.
			 * @param \WP_Comment $comment Review comment.
			 * @param string      $tier    Reward tier.
			 */
			do_action( 'sktpr_review_reward_before_coupon_save', $coupon, $comment, $tier );

			return (int) $coupon->save();
		} catch ( \Exception $e ) {
			return 0;
		}
	}

	/**
	 * Generate a unique coupon code.
	 *
	 * @param string $prefix Code prefix.
	 * @return string
	 */
	private function generate_coupon_code( $prefix ) {
		do {
			$code = strtoupper( $prefix . wp_generate_password( 8, false ) );
		} while ( wc_get_coupon_id_by_code( $code ) );

		return $code;
	}

	/**
	 * Email the reward coupon to the reviewer.
	 *
	 * @param \WP_Comment $comment  Comment.
	 * @param \WC_Coupon  $coupon   Coupon.
	 * @param array       $settings Reward settings.
	 * @return bool
	 */
	private function send_reward_email( $comment, $coupon, $settings ) {
		$email_settings = $this->get_stored_email_settings();
		$product        = wc_get_product( $comment->comment_post_ID );
		$expires        = $coupon->get_date_expires();
		$amount         = $this->format_discount( $coupon->get_discount_type(), $coupon->get_amount() );

		$variables = array(
			'{customer_name}' => esc_html( $comment->comment_author ),
			'{coupon_code}'   => esc_html( $coupon->get_code() ),
			'{coupon_amount}' => esc_html( $amount ),
			'{coupon_expiry}' => esc_html( $expires ? $expires->date_i18n( get_option( 'date_format' ) ) : __( 'No expiry', 'product-reviews' ) ),
			'{product_name}'  => esc_html( $product ? $product->get_name() : '' ),
			'{product_url}'   => esc_url( $product ? $product->get_permalink() : home_url( '/' ) ),
			'{shop_url}'      => esc_url( wc_get_page_permalink( 'shop' ) ),
			'{site_name}'     => esc_html( get_bloginfo( 'name' ) ),
			'{site_url}'      => esc_url( home_url() ),
		);

		$subject = wp_specialchars_decode( str_replace( array_keys( $variables ), array_values( $variables ), $settings['email_subject'] ), ENT_QUOTES );
		$message = str_replace( array_keys( $variables ), array_values( $variables ), $settings['email_content'] );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $email_settings['from_name'] . ' <' . $email_settings['from_email'] . '>',
		);

		return wp_mail( $comment->comment_author_email, $subject, $message, $headers );
	}

	/**
	 * Unpublish an unused reward coupon while its review is not approved.
	 * Unpublished coupons cannot be applied at checkout.
	 *
	 * @param \WP_Comment $comment Comment.
	 * @return void
	 */
	private function suspend_coupon( $comment ) {
		$coupon = $this->get_comment_coupon( $comment->comment_ID );

		if ( $coupon && 0 === $coupon->get_usage_count() && 'publish' === get_post_status( $coupon->get_id() ) ) {
			wp_update_post(
				array(
					'ID'          => $coupon->get_id(),
					'post_status' => 'draft',
				)
			);
		}
	}

	/**
	 * Republish a suspended reward coupon when its review is approved again.
	 *
	 * @param \WP_Comment $comment Comment.
	 * @return bool True when the review already has a reward coupon.
	 */
	private function restore_coupon( $comment ) {
		$coupon = $this->get_comment_coupon( $comment->comment_ID );

		if ( ! $coupon ) {
			return false;
		}

		if ( 'draft' === get_post_status( $coupon->get_id() ) ) {
			wp_update_post(
				array(
					'ID'          => $coupon->get_id(),
					'post_status' => 'publish',
				)
			);
		}

		return true;
	}

	/**
	 * Get the reward coupon issued for a review.
	 *
	 * @param int $comment_id Comment ID.
	 * @return \WC_Coupon|null
	 */
	public function get_comment_coupon( $comment_id ) {
		$coupon_id = absint( get_comment_meta( $comment_id, self::COUPON_META, true ) );

		if ( ! $coupon_id || ! class_exists( 'WC_Coupon' ) || 'shop_coupon' !== get_post_type( $coupon_id ) ) {
			return null;
		}

		return new \WC_Coupon( $coupon_id );
	}

	/**
	 * Format a discount for display, e.g. "15%" or "$10.00".
	 *
	 * @param string $type   Discount type.
	 * @param float  $amount Discount amount.
	 * @return string Plain text.
	 */
	public function format_discount( $type, $amount ) {
		if ( 'percent' === $type ) {
			return wc_format_localized_decimal( wc_format_decimal( $amount, false, true ) ) . '%';
		}

		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	}

	/**
	 * Short description of the best available reward, e.g. "up to 15%".
	 *
	 * Empty when rewards are disabled or unlicensed, so placeholders collapse.
	 *
	 * @return string Plain text.
	 */
	public function get_reward_teaser() {
		$settings = $this->get_reward_settings();

		if ( empty( $settings['enable_review_rewards'] ) ) {
			return '';
		}

		$enabled = array_filter(
			$settings['tiers'],
			function ( $tier ) {
				return ! empty( $tier['enabled'] ) && $tier['amount'] > 0;
			}
		);

		if ( empty( $enabled ) ) {
			return '';
		}

		// Tiers are ordered text → photo → video, so the last is the most valuable.
		$best   = end( $enabled );
		$amount = $this->format_discount( $best['discount_type'], $best['amount'] );

		return count( $enabled ) > 1 ?
			/* translators: %s: discount, e.g. 15% or $10. */
			sprintf( __( 'up to %s', 'product-reviews' ), $amount ) :
			$amount;
	}

	/**
	 * Storefront notice shown above the review media buttons.
	 *
	 * @return string HTML.
	 */
	public function get_form_notice_html() {
		$settings = $this->get_reward_settings();
		$teaser   = $this->get_reward_teaser();

		if ( '' === $teaser || empty( $settings['show_form_notice'] ) || '' === trim( $settings['form_notice_text'] ) ) {
			return '';
		}

		return sprintf(
			'<p class="sktpr-reward-notice"><i class="fa fa-gift" aria-hidden="true"></i> %s</p>',
			esc_html( str_replace( '{review_reward}', $teaser, $settings['form_notice_text'] ) )
		);
	}
}
