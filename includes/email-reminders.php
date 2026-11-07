<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Email_Reminders
 *
 * Handles email reminders for product reviews based on order status
 */
class Email_Reminders {

	use Helpers;

	/**
	 * Option name holding hashed opt-out email addresses.
	 *
	 * Emails are stored as HMAC hashes so the suppression list itself holds
	 * no personal data and survives privacy-erasure requests.
	 */
	const OPTOUT_OPTION = 'sktpr_email_optouts';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_unsubscribe_request' ), 5 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 20, 4 );
		add_action( 'sktpr_send_review_reminder_item', array( $this, 'send_review_reminder' ), 10, 1 );
		add_action( 'sktpr_send_review_reminder', array( $this, 'send_legacy_review_reminder' ), 10, 1 );
		add_action( 'sktpr_process_due_reminders', array( $this, 'process_due_reminders' ) );
		add_action( 'sktpr_migrate_reminders', array( 'SKTPREVIEW\Migration', 'migrate_existing_reminders' ) );

		// Schedule recurring cron job to process due reminders.
		if ( ! wp_next_scheduled( 'sktpr_process_due_reminders' ) ) {
			wp_schedule_event( time(), 'hourly', 'sktpr_process_due_reminders' );
		}
	}

	/**
	 * Schedule matching rules or cancel pending reminders after a status change.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from_status Previous status.
	 * @param string   $to_status New status.
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public function handle_order_status_change( $order_id, $from_status, $to_status, $order ) {
		if ( in_array( $to_status, array( 'cancelled', 'refunded' ), true ) ) {
			if ( Database::cancel_by_order( $order_id ) ) {
				$order->add_order_note( __( 'SKTPR: Pending review reminders cancelled due to order status change.', 'product-reviews' ) );
			}
			return;
		}

		$settings = $this->get_email_settings();
		if ( empty( $settings['enable_email_reminders'] ) ) {
			return;
		}

		foreach ( $settings['rules'] as $rule ) {
			if ( empty( $rule['enabled'] ) || $to_status !== $rule['trigger_order_status'] ) {
				continue;
			}

			$this->schedule_reminder( $order, $rule, $settings );
		}
	}

	/**
	 * Schedule one rule for an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array    $rule Reminder rule.
	 * @param array    $settings Shared reminder settings.
	 * @return void
	 */
	private function schedule_reminder( $order, $rule, $settings ) {
		$order_id = $order->get_id();
		$rule_id  = substr( sanitize_key( $rule['id'] ), 0, 64 );

		if ( Database::reminder_exists( $order_id, $rule_id ) ) {
			return;
		}

		$customer_email = $order->get_billing_email();
		if ( ! is_email( $customer_email ) ) {
			$order->add_order_note( __( 'SKTPR: Review reminder not scheduled - invalid email address.', 'product-reviews' ) );
			return;
		}

		if ( self::is_opted_out( $customer_email ) ) {
			$order->add_order_note( __( 'SKTPR: Review reminder not scheduled - customer has unsubscribed from review reminders.', 'product-reviews' ) );
			return;
		}

		if ( ! $this->has_reviewable_products( $order ) ) {
			$order->add_order_note( __( 'SKTPR: Review reminder not scheduled - no reviewable products in order.', 'product-reviews' ) );
			return;
		}

		$delay_days          = min( 365, absint( $rule['email_delay_days'] ) );
		$scheduled_timestamp = time() + ( $delay_days * DAY_IN_SECONDS );
		$scheduled_time      = wp_date( 'Y-m-d H:i:s', $scheduled_timestamp, wp_timezone() );
		$customer_name       = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

		if ( empty( $customer_name ) ) {
			$customer_name = __( 'Guest Customer', 'product-reviews' );
		}

		$snapshot = wp_json_encode(
			array(
				'from_name'     => $settings['from_name'],
				'from_email'    => $settings['from_email'],
				'email_subject' => $rule['email_subject'],
				'email_content' => $rule['email_content'],
			)
		);

		$reminder_id = Database::insert_reminder(
			array(
				'order_id'          => $order_id,
				'rule_id'           => $rule_id,
				'trigger_status'    => $rule['trigger_order_status'],
				'customer_name'     => $customer_name,
				'customer_email'    => $customer_email,
				'template_snapshot' => $snapshot,
				'scheduled_time'    => $scheduled_time,
				'status'            => 'scheduled',
			)
		);

		if ( ! $reminder_id ) {
			$order->add_order_note( __( 'SKTPR: Failed to schedule review reminder - database error.', 'product-reviews' ) );
			return;
		}

		if ( 0 === $delay_days ) {
			$this->send_review_reminder( $reminder_id );
			return;
		}

		$scheduled = wp_schedule_single_event( $scheduled_timestamp, 'sktpr_send_review_reminder_item', array( $reminder_id ) );
		if ( false === $scheduled ) {
			Database::delete_reminder( $reminder_id );
			$order->add_order_note( __( 'SKTPR: Failed to schedule review reminder - cron error.', 'product-reviews' ) );
			return;
		}

		$scheduled_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $scheduled_timestamp );
		$order->add_order_note(
			sprintf(
				/* translators: 1: order status, 2: scheduled date. */
				__( 'SKTPR: %1$s review reminder scheduled for %2$s.', 'product-reviews' ),
				wc_get_order_status_name( $rule['trigger_order_status'] ),
				$scheduled_date
			)
		);
	}

	/**
	 * Send review reminder email
	 *
	 * @param int $reminder_id Reminder row ID.
	 */
	public function send_review_reminder( $reminder_id ) {
		$reminder = Database::get_reminder( $reminder_id );
		if ( ! $reminder || 'scheduled' !== $reminder->status ) {
			return;
		}

		$order = wc_get_order( $reminder->order_id );
		if ( ! $order ) {
			Database::update_reminder( $reminder_id, array( 'status' => 'failed' ) );
			return;
		}

		$current_settings = $this->get_email_settings();
		if ( empty( $current_settings['enable_email_reminders'] ) ) {
			Database::update_reminder( $reminder_id, array( 'status' => 'cancelled' ) );
			return;
		}

		if ( in_array( $order->get_status(), array( 'cancelled', 'refunded' ), true ) ) {
			Database::update_reminder( $reminder_id, array( 'status' => 'cancelled' ) );
			return;
		}

		$customer_email = $reminder->customer_email;
		$customer_name  = $order->get_billing_first_name();
		$trigger_name   = wc_get_order_status_name( $reminder->trigger_status );
		$snapshot       = json_decode( $reminder->template_snapshot, true );

		if ( ! is_array( $snapshot ) ) {
			$rule     = $current_settings['rules'][0] ?? array();
			$snapshot = array(
				'from_name'     => $current_settings['from_name'],
				'from_email'    => $current_settings['from_email'],
				'email_subject' => $rule['email_subject'] ?? '',
				'email_content' => $rule['email_content'] ?? '',
			);
		}

		// Respect unsubscribe requests made after scheduling.
		if ( self::is_opted_out( $customer_email ) ) {
			$order->add_order_note( __( 'SKTPR: Review reminder not sent - customer has unsubscribed from review reminders.', 'product-reviews' ) );
			Database::update_reminder(
				$reminder_id,
				array(
					'status' => 'cancelled',
				)
			);
			return;
		}

		// Get order items for review.
		$items = $this->get_reviewable_items( $order );
		if ( empty( $items ) ) {
			$order->add_order_note( __( 'SKTPR: Review reminder not sent - no reviewable products found.', 'product-reviews' ) );
			Database::update_reminder(
				$reminder_id,
				array(
					'status' => 'failed',
				)
			);
			return;
		}

		// Prepare email content.
		$subject = $this->parse_email_template( $snapshot['email_subject'], $order, $customer_name );
		$message = $this->parse_email_template( $snapshot['email_content'], $order, $customer_name );

		// Add products list to email.
		$products_html = $this->generate_products_html( $items );
		$message       = str_replace( '{products_list}', $products_html, $message );

		// Add unsubscribe link; append a footer when the template has no placeholder.
		$unsubscribe_url = self::get_unsubscribe_url( $customer_email );
		if ( false !== strpos( $message, '{unsubscribe_link}' ) ) {
			$message = str_replace( '{unsubscribe_link}', esc_url( $unsubscribe_url ), $message );
		} else {
			$message .= sprintf(
				'<p style="margin-top: 30px; font-size: 12px; color: #69716c;">%s <a href="%s">%s</a></p>',
				esc_html__( 'No longer want review reminder emails?', 'product-reviews' ),
				esc_url( $unsubscribe_url ),
				esc_html__( 'Unsubscribe', 'product-reviews' )
			);
		}

		// Email headers.
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $snapshot['from_name'] . ' <' . $snapshot['from_email'] . '>',
		);

		// Send email.
		$sent = wp_mail( $customer_email, $subject, $message, $headers );

		if ( $sent ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: triggering order status. */
					__( 'SKTPR: %s review reminder email sent successfully.', 'product-reviews' ),
					$trigger_name
				)
			);

			// Update reminder status to sent.
			Database::update_reminder(
				$reminder_id,
				array(
					'status'  => 'sent',
					'sent_at' => current_time( 'mysql' ),
				)
			);
		} else {
			$order->add_order_note(
				sprintf(
					/* translators: %s: triggering order status. */
					__( 'SKTPR: Failed to send the %s review reminder email.', 'product-reviews' ),
					$trigger_name
				)
			);

			// Update reminder status to failed.
			Database::update_reminder(
				$reminder_id,
				array(
					'status' => 'failed',
				)
			);
		}
	}

	/**
	 * Compatibility handler for cron events created by the single-rule version.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function send_legacy_review_reminder( $order_id ) {
		$reminder = Database::get_reminder_by_order_rule( $order_id, 'legacy-default' );
		if ( ! $reminder ) {
			$reminder = Database::get_reminder_by_order( $order_id );
		}

		if ( $reminder ) {
			$this->send_review_reminder( $reminder->id );
		}
	}

	/**
	 * Check if order has reviewable products
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function has_reviewable_products( $order ) {
		$items = $order->get_items();

		foreach ( $items as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product_id = $item->get_product_id();
			$product    = wc_get_product( $product_id );

			if ( $product && $product->is_type( array( 'simple', 'variable' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get reviewable items from order
	 *
	 * @param \WC_Order $order Order object.
	 * @return array
	 */
	private function get_reviewable_items( $order ) {
		$items       = array();
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product_id = $item->get_product_id();
			$product    = wc_get_product( $product_id );

			if ( $product && $product->is_type( array( 'simple', 'variable' ) ) ) {
				$items[] = array(
					'product_id'    => $product_id,
					'product_name'  => $product->get_name(),
					'product_url'   => get_permalink( $product_id ),
					'product_image' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
				);
			}
		}

		return $items;
	}

	/**
	 * Generate HTML for products list in email
	 *
	 * @param array $items Product items.
	 * @return string
	 */
	private function generate_products_html( $items ) {
		$html = '<div style="margin: 20px 0;">';

		foreach ( $items as $item ) {
			$html .= '<div style="border: 1px solid #dfe2da; padding: 15px; margin: 10px 0; border-radius: 5px;">';

			if ( $item['product_image'] ) {
				$html .= '<img src="' . esc_url( $item['product_image'] ) . '" alt="' . esc_attr( $item['product_name'] ) . '" style="width: 60px; height: 60px; float: left; margin-right: 15px; border-radius: 3px;">';
			}

			$html .= '<h3 style="margin: 0 0 10px 0; font-size: 16px;">' . esc_html( $item['product_name'] ) . '</h3>';
			$html .= '<a href="' . esc_url( $item['product_url'] ) . '#reviews" style="background: #ff694d; color: #ffffff; padding: 8px 16px; text-decoration: none; border-radius: 3px; display: inline-block; font-weight: 700;">' . __( 'Write Review', 'product-reviews' ) . '</a>';
			$html .= '<div style="clear: both;"></div>';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Parse email template variables
	 *
	 * @param string   $template Template string.
	 * @param \WC_Order $order Order object.
	 * @param string   $customer_name Customer name.
	 * @return string
	 */
	private function parse_email_template( $template, $order, $customer_name ) {
		$variables = array(
			'{customer_name}' => $customer_name,
			'{order_number}'  => $order->get_order_number(),
			'{order_date}'    => $order->get_date_created()->date_i18n( get_option( 'date_format' ) ),
			'{site_name}'     => get_bloginfo( 'name' ),
			'{site_url}'      => home_url(),
			'{review_reward}' => esc_html( plugin()->review_rewards->get_reward_teaser() ),
		);

		return str_replace( array_keys( $variables ), array_values( $variables ), $template );
	}

	/**
	 * Builds the keyed hash used for unsubscribe tokens and the opt-out list.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	public static function get_email_hash( $email ) {
		return hash_hmac( 'sha256', strtolower( trim( $email ) ), wp_salt( 'auth' ) );
	}

	/**
	 * Builds the tokenized unsubscribe URL embedded in reminder emails.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	public static function get_unsubscribe_url( $email ) {
		return add_query_arg(
			array(
				'sktpr_unsubscribe' => rawurlencode( $email ),
				'sktpr_token'       => self::get_email_hash( $email ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Whether an email address has unsubscribed from review reminders.
	 *
	 * @param string $email Email address.
	 * @return bool
	 */
	public static function is_opted_out( $email ) {
		$optouts = get_option( self::OPTOUT_OPTION, array() );

		return is_array( $optouts ) && in_array( self::get_email_hash( $email ), $optouts, true );
	}

	/**
	 * Handles tokenized unsubscribe links clicked from reminder emails.
	 *
	 * Records the opt-out, cancels any scheduled reminders for the address,
	 * and shows a confirmation page. The HMAC token means no login or nonce
	 * is needed while still preventing third parties from unsubscribing
	 * arbitrary addresses.
	 */
	public function handle_unsubscribe_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- authenticated by the HMAC token below.
		if ( ! isset( $_GET['sktpr_unsubscribe'], $_GET['sktpr_token'] ) ) {
			return;
		}

		$email = sanitize_email( rawurldecode( wp_unslash( $_GET['sktpr_unsubscribe'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unsubscribe link is a public one-click endpoint; the decoded value is sanitized with sanitize_email() on this same line.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['sktpr_token'] ) );

		if ( ! is_email( $email ) || ! hash_equals( self::get_email_hash( $email ), $token ) ) {
			wp_die(
				esc_html__( 'This unsubscribe link is invalid or has expired.', 'product-reviews' ),
				esc_html__( 'Unsubscribe', 'product-reviews' ),
				array( 'response' => 403 )
			);
		}

		$optouts = get_option( self::OPTOUT_OPTION, array() );
		if ( ! is_array( $optouts ) ) {
			$optouts = array();
		}

		$hash = self::get_email_hash( $email );
		if ( ! in_array( $hash, $optouts, true ) ) {
			$optouts[] = $hash;
			update_option( self::OPTOUT_OPTION, $optouts, false );
		}

		Database::cancel_by_email( $email );

		wp_die(
			esc_html__( 'You have been unsubscribed and will no longer receive review reminder emails from us.', 'product-reviews' ),
			esc_html__( 'Unsubscribed', 'product-reviews' ),
			array(
				'response'  => 200,
				'link_url'  => esc_url( home_url( '/' ) ),
				'link_text' => esc_html__( 'Return to store', 'product-reviews' ),
			)
		);
	}

	/**
	 * Process due reminders (fallback cron job)
	 */
	public function process_due_reminders() {
		$due_reminders = Database::get_due_reminders();

		if ( empty( $due_reminders ) ) {
			return;
		}

		foreach ( $due_reminders as $reminder ) {
			$this->send_review_reminder( $reminder->id );
		}
	}
}
