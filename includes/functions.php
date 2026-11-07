<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

// Slug of the top-level settings page (admin.php?page=...).
if ( ! defined( 'SKTPR_ADMIN_PAGE' ) ) {
	define( 'SKTPR_ADMIN_PAGE', 'reviquo' );
}


/**
 * Flattens a multidimensional array into a single-dimensional array.
 *
 * @param array $array The array to flatten.
 * @return array The flattened array.
 */
function flatten_array( array $array ): array { // phpcs:ignore
	$result = array();

	array_walk_recursive(
		$array,
		function ( $value ) use ( &$result ) {
			$result[] = $value;
		}
	);

	return $result;
}

/**
 * Create the plugin database table during activation.
 */
function sktpr_activation_setup() {
	// Create database table.
	Database::create_table_on_activation();

	// Set database version.
	update_option( 'sktpr_db_version', Database::DB_VERSION );
}

/**
 * Clear plugin cron events during deactivation.
 */
function sktpr_deactivation_cleanup() {
	// Clear all scheduled email reminders.
	wp_clear_scheduled_hook( 'sktpr_send_review_reminder' );
	wp_clear_scheduled_hook( 'sktpr_send_review_reminder_item' );
	wp_clear_scheduled_hook( 'sktpr_process_due_reminders' );
	wp_clear_scheduled_hook( 'sktpr_migrate_reminders' );
}
