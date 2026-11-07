<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Migration
 *
 * Handles migration of existing reminder data to new database table
 */
class Migration {

	/**
	 * Migrate existing reminders from meta fields to database table
	 */
	public static function migrate_existing_reminders() {
		// Check if migration has already been done.
		$migration_done = get_option( 'sktpr_reminders_migrated', false );
		if ( $migration_done ) {
			return;
		}

		// Get all orders with reminder meta data (HPOS-compatible).
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One-time data migration; the legacy reminder flags only exist as order meta.
					'relation' => 'OR',
					array(
						'key'     => '_sktpr_reminder_time',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_sktpr_reminder_scheduled',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_sktpr_reminder_sent',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$migrated_count = 0;
		$settings       = plugin()->email_settings->get_email_settings();
		$rule           = $settings['rules'][0] ?? array();
		$snapshot       = wp_json_encode(
			array(
				'from_name'     => $settings['from_name'],
				'from_email'    => $settings['from_email'],
				'email_subject' => $rule['email_subject'] ?? '',
				'email_content' => $rule['email_content'] ?? '',
			)
		);

		foreach ( $orders as $order ) {
			// Skip if already exists in new table.
			if ( Database::reminder_exists( $order->get_id() ) ) {
				continue;
			}

			$legacy_time = $order->get_meta( '_sktpr_reminder_time' );
			$legacy_sent = $order->get_meta( '_sktpr_reminder_sent' );

			// Determine status.
			$status = 'scheduled';
			if ( $legacy_sent ) {
				$status = 'sent';
			}

			// Get customer name.
			$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			if ( empty( $customer_name ) ) {
				$customer_name = __( 'Guest Customer', 'product-reviews' );
			}

			// Calculate scheduled time.
			$scheduled_time = current_time( 'mysql' );
			if ( $legacy_time ) {
				$scheduled_time = gmdate( 'Y-m-d H:i:s', intval( $legacy_time ) );
			}

			// Insert into new table.
			$reminder_id = Database::insert_reminder(
				array(
					'order_id'          => $order->get_id(),
					'rule_id'           => 'legacy-default',
					'trigger_status'    => $rule['trigger_order_status'] ?? 'completed',
					'customer_name'     => $customer_name,
					'customer_email'    => $order->get_billing_email(),
					'template_snapshot' => $snapshot,
					'scheduled_time'    => $scheduled_time,
					'status'            => $status,
				)
			);

			if ( $reminder_id ) {
				++$migrated_count;

				// Update sent_at if reminder was sent.
				if ( $legacy_sent ) {
					Database::update_reminder(
						$reminder_id,
						array(
							'sent_at' => $legacy_sent,
						)
					);
				}
			}
		}

		// Mark migration as complete.
		update_option( 'sktpr_reminders_migrated', true );

		self::log( sprintf( 'Migration completed. Migrated %d reminders.', $migrated_count ) );

		return $migrated_count;
	}

	/**
	 * Clean up old meta data after successful migration
	 */
	public static function cleanup_old_meta_data() {
		// Only cleanup if migration was successful.
		$migration_done = get_option( 'sktpr_reminders_migrated', false );
		if ( ! $migration_done ) {
			return;
		}

		// Delete old meta keys (HPOS-compatible).
		$legacy_keys = array( '_sktpr_reminder_time', '_sktpr_reminder_scheduled', '_sktpr_reminder_sent' );
		$orders      = wc_get_orders(
			array(
				'limit'      => -1,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One-time cleanup; the legacy reminder flags only exist as order meta.
					'relation' => 'OR',
					array(
						'key'     => '_sktpr_reminder_time',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_sktpr_reminder_scheduled',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_sktpr_reminder_sent',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$deleted = 0;
		foreach ( $orders as $order ) {
			foreach ( $legacy_keys as $key ) {
				if ( $order->meta_exists( $key ) ) {
					$order->delete_meta_data( $key );
					++$deleted;
				}
			}
			$order->save();
		}

		self::log( sprintf( 'Cleaned up %d old meta entries.', $deleted ) );

		return $deleted;
	}

	/**
	 * Record a migration audit-trail entry in the WooCommerce log.
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	private static function log( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->info( $message, array( 'source' => 'reviquo-migration' ) );
		}
	}
}
