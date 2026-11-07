<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Database
 *
 * Handles database operations for email reminders
 */
class Database {

	/** Current reminder table schema version. */
	const DB_VERSION = '2.0';

	/**
	 * Table name for email reminders
	 */
	const TABLE_NAME = 'sktpr_email_reminders';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'check_database_version' ), 5 );
	}

	/**
	 * Get the full table name with WordPress prefix
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create the email reminders table (called on plugin activation)
	 */
	public static function create_table_on_activation() {
		self::create_table();

		if ( ! get_option( 'sktpr_reminders_migrated', false ) ) {
			wp_schedule_single_event( time() + 10, 'sktpr_migrate_reminders' );
		}
	}

	/**
	 * Create the email reminders table
	 */
	private static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			rule_id varchar(64) NOT NULL DEFAULT 'legacy-default',
			trigger_status varchar(100) NOT NULL DEFAULT '',
			customer_name varchar(255) NOT NULL DEFAULT '',
			customer_email varchar(255) NOT NULL DEFAULT '',
			template_snapshot longtext NULL,
			scheduled_time datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'scheduled',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			sent_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY order_rule (order_id,rule_id),
			KEY order_id (order_id),
			KEY status (status),
			KEY scheduled_time (scheduled_time),
			KEY customer_email (customer_email)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a new email reminder
	 *
	 * @param array $data Reminder data.
	 * @return int|false Insert ID on success, false on failure
	 */
	public static function insert_reminder( $data ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'order_id'          => 0,
			'rule_id'           => 'legacy-default',
			'trigger_status'    => '',
			'customer_name'     => '',
			'customer_email'    => '',
			'template_snapshot' => '',
			'scheduled_time'    => current_time( 'mysql' ),
			'status'            => 'scheduled',
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$table_name,
			array(
				'order_id'          => intval( $data['order_id'] ),
				'rule_id'           => substr( sanitize_key( $data['rule_id'] ), 0, 64 ),
				'trigger_status'    => sanitize_key( $data['trigger_status'] ),
				'customer_name'     => sanitize_text_field( $data['customer_name'] ),
				'customer_email'    => sanitize_email( $data['customer_email'] ),
				'template_snapshot' => $data['template_snapshot'],
				'scheduled_time'    => $data['scheduled_time'],
				'status'            => sanitize_text_field( $data['status'] ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update a reminder
	 *
	 * @param int   $id Reminder ID.
	 * @param array $data Update data.
	 * @return bool
	 */
	public static function update_reminder( $id, $data ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$allowed_fields = array( 'status', 'sent_at', 'scheduled_time', 'rule_id', 'trigger_status', 'template_snapshot' );
		$update_data    = array();
		$format         = array();

		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $allowed_fields, true ) ) {
				$update_data[ $key ] = $value;
				$format[]            = '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$table_name,
			$update_data,
			array( 'id' => intval( $id ) ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Update reminder by order ID
	 *
	 * @param int   $order_id Order ID.
	 * @param array $data Update data.
	 * @return bool
	 */
	public static function update_reminder_by_order( $order_id, $data ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$allowed_fields = array( 'status', 'sent_at', 'scheduled_time' );
		$update_data    = array();
		$format         = array();

		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $allowed_fields, true ) ) {
				$update_data[ $key ] = $value;
				$format[]            = '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$table_name,
			$update_data,
			array( 'order_id' => intval( $order_id ) ),
			$format,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a reminder
	 *
	 * @param int $id Reminder ID.
	 * @return bool
	 */
	public static function delete_reminder( $id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$table_name,
			array( 'id' => intval( $id ) ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete reminder by order ID
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public static function delete_reminder_by_order( $order_id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$table_name,
			array( 'order_id' => intval( $order_id ) ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get a reminder by ID
	 *
	 * @param int $id Reminder ID.
	 * @return object|null
	 */
	public static function get_reminder( $id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$table_name,
				intval( $id )
			)
		);
	}

	/**
	 * Get reminder by order ID
	 *
	 * @param int $order_id Order ID.
	 * @return object|null
	 */
	public static function get_reminder_by_order( $order_id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE order_id = %d',
				$table_name,
				intval( $order_id )
			)
		);
	}

	/**
	 * Get a reminder for an order and rule.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $rule_id Rule ID.
	 * @return object|null
	 */
	public static function get_reminder_by_order_rule( $order_id, $rule_id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE order_id = %d AND rule_id = %s',
				$table_name,
				intval( $order_id ),
				sanitize_key( $rule_id )
			)
		);
	}

	/**
	 * Get all reminder rows for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public static function get_reminders_by_order( $order_id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE order_id = %d ORDER BY id ASC',
				$table_name,
				intval( $order_id )
			)
		);
	}

	/**
	 * Get all reminders with optional filters
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_reminders( $args = array() ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'status'  => '',
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'scheduled_time',
			'order'   => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Only a validated column/direction pair can reach the ORDER BY clause.
		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		if ( ! $orderby ) {
			$orderby = sanitize_sql_orderby( 'scheduled_time ASC' );
		}

		$status = ! empty( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
		$limit  = intval( $args['limit'] );
		$offset = max( 0, intval( $args['offset'] ) );

		if ( '' !== $status ) {
			if ( $limit > 0 ) {
				return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
					$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and validated ORDER BY cannot be placeholders.
						"SELECT * FROM %i WHERE status = %s ORDER BY {$orderby} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated ORDER BY cannot be a placeholder.
						$table_name,
						$status,
						$limit,
						$offset
					)
				);
			}

			return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and validated ORDER BY cannot be placeholders.
					"SELECT * FROM %i WHERE status = %s ORDER BY {$orderby}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated ORDER BY cannot be a placeholder.
					$table_name,
					$status
				)
			);
		}

		if ( $limit > 0 ) {
			return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and validated ORDER BY cannot be placeholders.
					"SELECT * FROM %i ORDER BY {$orderby} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated ORDER BY cannot be a placeholder.
					$table_name,
					$limit,
					$offset
				)
			);
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY {$orderby}", $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated ORDER BY cannot be a placeholder; no user input reaches this query.
	}

	/**
	 * Get reminders due for sending
	 *
	 * @return array
	 */
	public static function get_due_reminders() {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				"SELECT * FROM %i WHERE status = 'scheduled' AND scheduled_time <= %s ORDER BY scheduled_time ASC",
				$table_name,
				current_time( 'mysql' )
			)
		);
	}

	/**
	 * Get reminder statistics
	 *
	 * @return array
	 */
	public static function get_stats() {
		global $wpdb;

		$table_name = self::get_table_name();

		$stats = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare( 'SELECT status, COUNT(*) as count FROM %i GROUP BY status', $table_name )
		);

		$result = array(
			'scheduled' => 0,
			'sent'      => 0,
			'cancelled' => 0,
			'failed'    => 0,
			'total'     => 0,
		);

		foreach ( $stats as $stat ) {
			$result[ $stat->status ] = intval( $stat->count );
			$result['total']        += intval( $stat->count );
		}

		return $result;
	}

	/**
	 * Get reminders for a customer email address.
	 *
	 * @param string $email Customer email.
	 * @param int    $limit Max rows to return.
	 * @param int    $offset Row offset for paging.
	 * @return array
	 */
	public static function get_reminders_by_email( $email, $limit = 100, $offset = 0 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE customer_email = %s ORDER BY id ASC LIMIT %d OFFSET %d',
				$table_name,
				sanitize_email( $email ),
				intval( $limit ),
				intval( $offset )
			)
		);
	}

	/**
	 * Cancel all scheduled reminders for a customer email address.
	 *
	 * Clears the pending cron event for each affected order and marks the
	 * database rows as cancelled.
	 *
	 * @param string $email Customer email.
	 * @return int Number of reminders cancelled.
	 */
	public static function cancel_by_email( $email ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$scheduled = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				"SELECT id, order_id FROM %i WHERE customer_email = %s AND status = 'scheduled'",
				$table_name,
				sanitize_email( $email )
			)
		);

		foreach ( $scheduled as $reminder ) {
			self::clear_scheduled_event( $reminder );
			self::update_reminder( $reminder->id, array( 'status' => 'cancelled' ) );
		}

		return count( $scheduled );
	}

	/**
	 * Delete all reminders for a customer email address.
	 *
	 * Used by the privacy data eraser. Pending cron events are cleared first.
	 *
	 * @param string $email Customer email.
	 * @return int Number of rows deleted.
	 */
	public static function delete_by_email( $email ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
			$wpdb->prepare(
				'SELECT id, order_id, status FROM %i WHERE customer_email = %s',
				$table_name,
				sanitize_email( $email )
			)
		);

		foreach ( $rows as $reminder ) {
			if ( 'scheduled' === $reminder->status ) {
				self::clear_scheduled_event( $reminder );
			}
			self::delete_reminder( $reminder->id );
		}

		return count( $rows );
	}

	/**
	 * Check if reminder exists for order
	 *
	 * @param int    $order_id Order ID.
	 * @param string $rule_id  Optional rule ID to narrow the lookup.
	 * @return bool
	 */
	public static function reminder_exists( $order_id, $rule_id = '' ) {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( '' !== $rule_id ) {
			$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE order_id = %d AND rule_id = %s',
					$table_name,
					intval( $order_id ),
					sanitize_key( $rule_id )
				)
			);
		} else {
			$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE order_id = %d',
					$table_name,
					intval( $order_id )
				)
			);
		}

		return intval( $count ) > 0;
	}

	/**
	 * Cancel all scheduled reminder rows for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return int Number cancelled.
	 */
	public static function cancel_by_order( $order_id ) {
		$scheduled = array_filter(
			self::get_reminders_by_order( $order_id ),
			function ( $reminder ) {
				return 'scheduled' === $reminder->status;
			}
		);

		foreach ( $scheduled as $reminder ) {
			self::clear_scheduled_event( $reminder );
			self::update_reminder( $reminder->id, array( 'status' => 'cancelled' ) );
		}

		return count( $scheduled );
	}

	/**
	 * Cancel every pending reminder when the master setting is disabled.
	 *
	 * @return int Number cancelled.
	 */
	public static function cancel_all_scheduled() {
		$scheduled = self::get_reminders(
			array(
				'status' => 'scheduled',
				'limit'  => 0,
			)
		);

		foreach ( $scheduled as $reminder ) {
			self::clear_scheduled_event( $reminder );
			self::update_reminder( $reminder->id, array( 'status' => 'cancelled' ) );
		}

		return count( $scheduled );
	}

	/**
	 * Cancel the cron events associated with a reminder row.
	 *
	 * @param object $reminder Reminder row.
	 * @return void
	 */
	public static function clear_scheduled_event( $reminder ) {
		wp_clear_scheduled_hook( 'sktpr_send_review_reminder_item', array( intval( $reminder->id ) ) );
		wp_clear_scheduled_hook( 'sktpr_send_review_reminder', array( intval( $reminder->order_id ) ) );
	}

	/**
	 * Check database version and create table if needed (for updates)
	 */
	public function check_database_version() {
		$db_version = get_option( 'sktpr_db_version', '0' );

		if ( version_compare( $db_version, self::DB_VERSION, '<' ) ) {
			if ( version_compare( $db_version, '2.0', '<' ) ) {
				$this->drop_legacy_order_index();
			}

			self::create_table_on_activation();
			$this->migrate_multi_rule_rows();
			update_option( 'sktpr_db_version', self::DB_VERSION );

			// Show admin notice.
			add_action( 'admin_notices', array( $this, 'show_database_update_notice' ) );
		}
	}

	/**
	 * Remove the old unique order index before dbDelta adds the composite key.
	 *
	 * @return void
	 */
	private function drop_legacy_order_index() {
		global $wpdb;

		$table_name   = self::get_table_name();
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table; table name comes from \$wpdb->prefix so it cannot be a placeholder, and reminder rows are transactional so they are not cached.
		if ( $table_name !== $table_exists ) {
			return;
		}

		$indexes = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM %i', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time schema upgrade on the plugin's own table.

		foreach ( (array) $indexes as $index ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Column names returned by SHOW INDEX.
			if ( 'order_id' === $index->Key_name && 0 === intval( $index->Non_unique ) ) {
				$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP INDEX order_id', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- One-time schema upgrade on the plugin's own table.
				break;
			}
		}
	}

	/**
	 * Attach the legacy rule/template to existing rows and move their events to
	 * the reminder-ID cron hook.
	 *
	 * @return void
	 */
	private function migrate_multi_rule_rows() {
		$settings = plugin()->email_settings->get_email_settings();
		$rule     = $settings['rules'][0] ?? array();
		$snapshot = wp_json_encode(
			array(
				'from_name'     => $settings['from_name'],
				'from_email'    => $settings['from_email'],
				'email_subject' => $rule['email_subject'] ?? '',
				'email_content' => $rule['email_content'] ?? '',
			)
		);

		foreach ( self::get_reminders( array( 'limit' => 0 ) ) as $reminder ) {
			self::update_reminder(
				$reminder->id,
				array(
					'rule_id'           => 'legacy-default',
					'trigger_status'    => $rule['trigger_order_status'] ?? 'completed',
					'template_snapshot' => $snapshot,
				)
			);

			if ( 'scheduled' !== $reminder->status ) {
				continue;
			}

			wp_clear_scheduled_hook( 'sktpr_send_review_reminder', array( intval( $reminder->order_id ) ) );
			$date      = date_create_from_format( 'Y-m-d H:i:s', $reminder->scheduled_time, wp_timezone() );
			$timestamp = max( time() + 1, $date ? $date->getTimestamp() : time() + 1 );
			if ( ! wp_next_scheduled( 'sktpr_send_review_reminder_item', array( intval( $reminder->id ) ) ) ) {
				wp_schedule_single_event( $timestamp, 'sktpr_send_review_reminder_item', array( intval( $reminder->id ) ) );
			}
		}
	}

	/**
	 * Show admin notice after database update
	 */
	public function show_database_update_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'ReviQuo:', 'product-reviews' ); ?></strong>
				<?php esc_html_e( 'Email reminders database table has been created/updated successfully.', 'product-reviews' ); ?>
			</p>
		</div>
		<?php
	}
}
