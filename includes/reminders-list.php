<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Reminders_List
 *
 * Handles display of scheduled email reminders
 */
class Reminders_List {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Add submenu for reminders
	 */
	public function add_submenu() {
		add_submenu_page(
			SKTPR_ADMIN_PAGE,
			__( 'Email Reminders', 'product-reviews' ),
			__( 'Email Reminders', 'product-reviews' ),
			'manage_options',
			'skt-product-reviews-reminders',
			array( $this, 'display_reminders_page' )
		);
	}

	/**
	 * Handle reminder actions
	 */
	public function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		if ( 'cancel' === $action && isset( $_GET['reminder_id'], $_GET['_wpnonce'] ) ) {
			$reminder_id = intval( $_GET['reminder_id'] );
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'cancel_reminder_' . $reminder_id ) ) {
				$this->cancel_reminder( $reminder_id );
				wp_safe_redirect( admin_url( 'admin.php?page=skt-product-reviews-reminders&cancelled=1' ) );
				exit;
			}
		}
	}

	/**
	 * Display reminders page
	 */
	public function display_reminders_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Email Reminders', 'product-reviews' ); ?></h1>
			<p><?php esc_html_e( 'View and manage scheduled email reminders for product reviews.', 'product-reviews' ); ?></p>
			
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag set by the nonce-verified redirect in handle_actions().
			if ( isset( $_GET['cancelled'] ) ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Reminder cancelled successfully.', 'product-reviews' ); ?></p>
				</div>
			<?php endif; ?>
			
			<?php $this->display_reminders_table(); ?>
		</div>
		<?php
	}

	/**
	 * Display reminders table
	 */
	private function display_reminders_table() {
		$reminders = $this->get_scheduled_reminders();
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order ID', 'product-reviews' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'product-reviews' ); ?></th>
					<th><?php esc_html_e( 'Email', 'product-reviews' ); ?></th>
					<th><?php esc_html_e( 'Trigger', 'product-reviews' ); ?></th>
					<th><?php esc_html_e( 'Scheduled Time', 'product-reviews' ); ?></th>
					<th><?php esc_html_e( 'Status', 'product-reviews' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'product-reviews' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $reminders ) ) : ?>
					<tr>
						<td class="sktpr-reminders-empty" colspan="7">
							<?php esc_html_e( 'No scheduled reminders found.', 'product-reviews' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $reminders as $reminder ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $reminder['order_id'] . '&action=edit' ) ); ?>">
									#<?php echo esc_html( $reminder['order_number'] ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $reminder['customer_name'] ); ?></td>
							<td><?php echo esc_html( $reminder['customer_email'] ); ?></td>
							<td><?php echo esc_html( wc_get_order_status_name( $reminder['trigger_status'] ) ); ?></td>
							<td>
								<?php
								echo esc_html(
									date_i18n(
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
										$reminder['scheduled_time']
									)
								);
								?>
							</td>
							<td>
								<?php
								$status_classes = array(
									'scheduled' => 'scheduled',
									'sent'      => 'sent',
									'cancelled' => 'error',
									'failed'    => 'error',
								);
								$status_class   = $status_classes[ $reminder['status'] ] ?? 'default';
								$status_text    = ucfirst( $reminder['status'] );

								if ( 'scheduled' === $reminder['status'] && $reminder['scheduled_time'] < time() ) {
									$status_text  = __( 'Overdue', 'product-reviews' );
									$status_class = 'error';
								}
								?>
								<span class="sktpr-reminder-status sktpr-reminder-status--<?php echo esc_attr( $status_class ); ?>">
									<?php echo esc_html( $status_text ); ?>
								</span>
								<?php if ( $reminder['sent_at'] ) : ?>
									<br><small><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reminder['sent_at'] ) ) ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( 'scheduled' === $reminder['status'] ) : ?>
									<?php
									$cancel_url = wp_nonce_url(
										admin_url( 'admin.php?page=skt-product-reviews-reminders&action=cancel&reminder_id=' . $reminder['id'] ),
										'cancel_reminder_' . $reminder['id']
									);
									?>
									<a href="<?php echo esc_url( $cancel_url ); ?>"
										class="button button-small"
										onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this reminder?', 'product-reviews' ); ?>')">
										<?php esc_html_e( 'Cancel', 'product-reviews' ); ?>
									</a>
								<?php elseif ( 'sent' === $reminder['status'] ) : ?>
									<span class="sktpr-reminder-status sktpr-reminder-status--sent">✓ <?php esc_html_e( 'Sent', 'product-reviews' ); ?></span>
								<?php elseif ( 'cancelled' === $reminder['status'] ) : ?>
									<span class="sktpr-reminder-status sktpr-reminder-status--error">✗ <?php esc_html_e( 'Cancelled', 'product-reviews' ); ?></span>
								<?php elseif ( 'failed' === $reminder['status'] ) : ?>
									<span class="sktpr-reminder-status sktpr-reminder-status--error">⚠ <?php esc_html_e( 'Failed', 'product-reviews' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get scheduled reminders
	 *
	 * @return array
	 */
	private function get_scheduled_reminders() {
		$reminders_data = Database::get_reminders(
			array(
				'status'  => '', // Get all statuses.
				'limit'   => 100,
				'orderby' => 'scheduled_time',
				'order'   => 'ASC',
			)
		);

		$reminders = array();
		if ( $reminders_data ) {
			foreach ( $reminders_data as $reminder ) {
				$order = wc_get_order( $reminder->order_id );
				if ( $order ) {
					$scheduled_date = date_create_from_format( 'Y-m-d H:i:s', $reminder->scheduled_time, wp_timezone() );
					$reminders[]    = array(
						'id'             => $reminder->id,
						'order_id'       => $reminder->order_id,
						'order_number'   => $order->get_order_number(),
						'customer_name'  => $reminder->customer_name,
						'customer_email' => $reminder->customer_email,
						'trigger_status' => $reminder->trigger_status,
						'scheduled_time' => $scheduled_date ? $scheduled_date->getTimestamp() : strtotime( $reminder->scheduled_time ),
						'status'         => $reminder->status,
						'sent_at'        => $reminder->sent_at,
						'created_at'     => $reminder->created_at,
					);
				}
			}
		}

		return $reminders;
	}

	/**
	 * Cancel a reminder
	 *
	 * @param int $reminder_id Reminder ID.
	 */
	private function cancel_reminder( $reminder_id ) {
		$reminder = Database::get_reminder( $reminder_id );
		if ( ! $reminder || 'scheduled' !== $reminder->status ) {
			return;
		}

		Database::clear_scheduled_event( $reminder );
		Database::update_reminder( $reminder_id, array( 'status' => 'cancelled' ) );

		$order = wc_get_order( $reminder->order_id );
		if ( $order ) {
			$order->add_order_note( __( 'SKTPR: Review reminder cancelled manually from admin.', 'product-reviews' ) );
		}
	}
}
