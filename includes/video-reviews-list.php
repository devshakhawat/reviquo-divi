<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Video_Reviews_List
 *
 * Handles the display of video reviews in admin area.
 */
class Video_Reviews_List {

	/**
	 * Admin notices array
	 *
	 * @var array
	 */
	private $admin_notices = array();

	/**
	 * Video_Reviews_List constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'show_pending_video_review_notices' ) );
	}

	/**
	 * Add submenu for video reviews
	 */
	public function add_submenu() {
		add_submenu_page(
			SKTPR_ADMIN_PAGE,
			__( 'Reviews', 'product-reviews' ),
			__( 'Reviews', 'product-reviews' ),
			'manage_options',
			'video-reviews-list',
			array( $this, 'display_video_reviews_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'reviquo_page_video-reviews-list' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-list-table' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'dashicons' );

		// The media column reuses the storefront thumbnail markup and lightbox.
		plugin()->scripts->register_public_assets();
		wp_enqueue_style( 'sktpr_public' );
		wp_enqueue_script( 'sktpr_public' );
	}

	/**
	 * Display video reviews page
	 */
	public function display_video_reviews_page() {
		// Handle individual actions.
		$this->process_individual_actions();

		// Handle bulk actions.
		$this->process_bulk_actions();

		$list_table = new Video_Reviews_Table();
		$list_table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Reviews', 'product-reviews' ); ?></h1>
			<hr class="wp-header-end">

			<?php $this->display_admin_notices(); ?>
			<?php $list_table->views(); ?>

			<form method="post">
				<input type="hidden" name="page" value="video-reviews-list" />
				<?php wp_nonce_field( 'bulk-video-reviews' ); ?>
				<?php $list_table->search_box( __( 'Search reviews', 'product-reviews' ), 'video-review' ); ?>
				<?php $list_table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Process individual actions
	 */
	private function process_individual_actions() {
		// Check if we have an individual action.
		if ( empty( $_GET['action'] ) || empty( $_GET['comment_id'] ) ) {
			return;
		}

		$comment_id = intval( $_GET['comment_id'] );
		$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'video-review-action_' . $comment_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'product-reviews' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		// Process the action.
		if ( $this->process_single_action( $action, $comment_id ) ) {
			$message = $this->get_single_action_message( $action );
			$this->add_admin_notice( $message, 'success' );
		} else {
			$this->add_admin_notice( __( 'Action failed. Please try again.', 'product-reviews' ), 'error' );
		}

		// Redirect to clean URL.
		$redirect_url = remove_query_arg( array( 'action', 'comment_id', '_wpnonce' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Process bulk actions
	 */
	private function process_bulk_actions() {
		// Check if we have a bulk action.
		$action = '';
		if ( isset( $_POST['action'] ) && '-1' !== $_POST['action'] ) {
			$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
		} elseif ( isset( $_POST['action2'] ) && '-1' !== $_POST['action2'] ) {
			$action = sanitize_text_field( wp_unslash( $_POST['action2'] ) );
		}

		if ( empty( $action ) ) {
			return;
		}

		// Verify nonce.
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-video-reviews' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'product-reviews' ) );
		}

		// Check if we have comments selected.
		if ( empty( $_POST['comment'] ) || ! is_array( $_POST['comment'] ) ) {
			$this->add_admin_notice( __( 'No reviews selected.', 'product-reviews' ), 'error' );
			return;
		}

		$comment_ids = array_map( 'absint', wp_unslash( $_POST['comment'] ) );
		$processed   = 0;

		foreach ( $comment_ids as $comment_id ) {
			if ( $this->process_single_action( $action, $comment_id ) ) {
				++$processed;
			}
		}

		// Add success message.
		if ( $processed > 0 ) {
			$message = $this->get_bulk_action_message( $action, $processed );
			$this->add_admin_notice( $message, 'success' );
		}

		// Redirect to avoid resubmission.
		$redirect_url = remove_query_arg( array( 'action', 'action2', 'comment', '_wpnonce' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Process single action on a comment
	 *
	 * @param string $action Action to perform.
	 * @param int    $comment_id Comment ID.
	 * @return bool Success status
	 */
	private function process_single_action( $action, $comment_id ) {
		if ( ! current_user_can( 'moderate_comments' ) ) {
			return false;
		}

		switch ( $action ) {
			case 'approve':
				return wp_set_comment_status( $comment_id, 'approve' );

			case 'unapprove':
				return wp_set_comment_status( $comment_id, 'hold' );

			case 'spam':
				return wp_spam_comment( $comment_id );

			case 'unspam':
				return wp_unspam_comment( $comment_id );

			case 'trash':
				return wp_trash_comment( $comment_id );

			case 'untrash':
				return wp_untrash_comment( $comment_id );

			default:
				return false;
		}
	}

	/**
	 * Get single action success message
	 *
	 * @param string $action Action performed.
	 * @return string Message
	 */
	private function get_single_action_message( $action ) {
		switch ( $action ) {
			case 'approve':
				return __( 'Review approved.', 'product-reviews' );

			case 'unapprove':
				return __( 'Review unapproved.', 'product-reviews' );

			case 'spam':
				return __( 'Review marked as spam.', 'product-reviews' );

			case 'unspam':
				return __( 'Review restored from spam.', 'product-reviews' );

			case 'trash':
				return __( 'Review moved to trash.', 'product-reviews' );

			case 'untrash':
				return __( 'Review restored from trash.', 'product-reviews' );

			default:
				return __( 'Review updated.', 'product-reviews' );
		}
	}

	/**
	 * Get bulk action success message
	 *
	 * @param string $action Action performed.
	 * @param int    $count Number of items processed.
	 * @return string Message
	 */
	private function get_bulk_action_message( $action, $count ) {
		switch ( $action ) {
			case 'approve':
				// translators: %d: number of reviews approved.
				return sprintf( _n( '%d review approved.', '%d reviews approved.', $count, 'product-reviews' ), $count );

			case 'unapprove':
				// translators: %d: number of reviews unapproved.
				return sprintf( _n( '%d review unapproved.', '%d reviews unapproved.', $count, 'product-reviews' ), $count );

			case 'spam':
				// translators: %d: number of reviews marked as spam.
				return sprintf( _n( '%d review marked as spam.', '%d reviews marked as spam.', $count, 'product-reviews' ), $count );

			case 'unspam':
				// translators: %d: number of reviews restored from spam.
				return sprintf( _n( '%d review restored from spam.', '%d reviews restored from spam.', $count, 'product-reviews' ), $count );

			case 'trash':
				// translators: %d: number of reviews moved to trash.
				return sprintf( _n( '%d review moved to trash.', '%d reviews moved to trash.', $count, 'product-reviews' ), $count );

			case 'untrash':
				// translators: %d: number of reviews restored from trash.
				return sprintf( _n( '%d review restored from trash.', '%d reviews restored from trash.', $count, 'product-reviews' ), $count );

			default:
				// translators: %d: number of reviews processed.
				return sprintf( _n( '%d review processed.', '%d reviews processed.', $count, 'product-reviews' ), $count );
		}
	}

	/**
	 * Add admin notice
	 *
	 * @param string $message Notice message.
	 * @param string $type Notice type (success, error, warning, info).
	 */
	private function add_admin_notice( $message, $type = 'success' ) {
		if ( ! isset( $this->admin_notices ) ) {
			$this->admin_notices = array();
		}

		$this->admin_notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
	}

	/**
	 * Display admin notices
	 */
	private function display_admin_notices() {
		if ( empty( $this->admin_notices ) ) {
			return;
		}

		foreach ( $this->admin_notices as $notice ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}
	}

	/**
	 * Get video reviews data
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_video_reviews( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'number'  => 20,
			'offset'  => 0,
			'orderby' => 'comment_date',
			'order'   => 'DESC',
			'search'  => '',
			'status'  => 'approve',
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array();

		// Base query to get comments carrying video and/or photo meta.
		// Separate LEFT JOINs per meta key keep one row per review even when
		// both media types are attached.
		$query = "
			SELECT c.*, vm.meta_value as video_urls, im.meta_value as image_urls, p.post_title, p.ID as product_id
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
			LEFT JOIN {$wpdb->commentmeta} vm ON vm.comment_id = c.comment_ID AND vm.meta_key = 'uploaded_video_url'
			LEFT JOIN {$wpdb->commentmeta} im ON im.comment_id = c.comment_ID AND im.meta_key = 'uploaded_image_url'
			WHERE p.post_type = 'product'
			AND ( vm.meta_id IS NOT NULL OR im.meta_id IS NOT NULL )
		";

		$query .= self::build_status_clause( $args['status'], $query_args );
		$query .= self::build_search_clause( $args['search'], $query_args );
		$query .= self::build_orderby_clause( $args['orderby'], $args['order'] );

		// Add pagination.
		if ( intval( $args['number'] ) > 0 ) {
			$query       .= ' LIMIT %d OFFSET %d';
			$query_args[] = intval( $args['number'] );
			$query_args[] = max( 0, intval( $args['offset'] ) );
		}

		// Every fragment appended above contributes only literal SQL and %s/%d
		// placeholders, and $query_args always holds at least one status value.
		return $wpdb->get_results( $wpdb->prepare( $query, $query_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query is assembled from literal fragments returned by the build_*_clause() helpers above; every value is bound through a %s/%d placeholder, which static analysis cannot follow across the helper calls.
	}

	/**
	 * Build the comment_approved clause and push its values onto $query_args.
	 *
	 * Accepts a single status or a list; an empty list falls back to the
	 * default so the clause never degenerates into `IN ()`.
	 *
	 * @param string|array $status     Status or list of statuses.
	 * @param array        $query_args Prepared-statement values, appended by reference.
	 * @return string SQL fragment containing only literals and placeholders.
	 */
	private static function build_status_clause( $status, array &$query_args ) {
		$statuses = array_values( array_filter( array_map( 'strval', (array) $status ), 'strlen' ) );

		if ( empty( $statuses ) ) {
			$statuses = array( 'approve' );
		}

		$query_args = array_merge( $query_args, $statuses );

		// array_fill() emits only '%s' tokens, so this interpolation is literal SQL.
		return ' AND c.comment_approved IN ( ' . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ' )';
	}

	/**
	 * Build the search clause and push its values onto $query_args.
	 *
	 * @param string $search     Search term.
	 * @param array  $query_args Prepared-statement values, appended by reference.
	 * @return string SQL fragment containing only literals and placeholders.
	 */
	private static function build_search_clause( $search, array &$query_args ) {
		global $wpdb;

		if ( '' === trim( (string) $search ) ) {
			return '';
		}

		$search_term  = '%' . $wpdb->esc_like( $search ) . '%';
		$query_args[] = $search_term;
		$query_args[] = $search_term;
		$query_args[] = $search_term;

		return ' AND ( c.comment_content LIKE %s OR c.comment_author LIKE %s OR p.post_title LIKE %s )';
	}

	/**
	 * Map a requested sort onto a literal ORDER BY fragment.
	 *
	 * The column and direction never reach SQL as variables: the request only
	 * selects which hard-coded clause is returned.
	 *
	 * @param string $orderby Requested column.
	 * @param string $order   Requested direction.
	 * @return string Literal SQL fragment.
	 */
	private static function build_orderby_clause( $orderby, $order ) {
		$direction = ( 'ASC' === strtoupper( (string) $order ) ) ? 'ASC' : 'DESC';

		$clauses = array(
			'comment_date_ASC'    => ' ORDER BY c.comment_date ASC',
			'comment_date_DESC'   => ' ORDER BY c.comment_date DESC',
			'comment_author_ASC'  => ' ORDER BY c.comment_author ASC',
			'comment_author_DESC' => ' ORDER BY c.comment_author DESC',
			'comment_ID_ASC'      => ' ORDER BY c.comment_ID ASC',
			'comment_ID_DESC'     => ' ORDER BY c.comment_ID DESC',
			'post_title_ASC'      => ' ORDER BY p.post_title ASC',
			'post_title_DESC'     => ' ORDER BY p.post_title DESC',
		);

		$key = $orderby . '_' . $direction;

		return isset( $clauses[ $key ] ) ? $clauses[ $key ] : $clauses[ 'comment_date_' . $direction ];
	}

	/**
	 * Get total count of video reviews
	 *
	 * @param array $args Query arguments.
	 * @return int
	 */
	public static function get_video_reviews_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search' => '',
			'status' => 'approve',
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array();

		$query = "
			SELECT COUNT(DISTINCT c.comment_ID)
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
			INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
			WHERE cm.meta_key IN ( 'uploaded_video_url', 'uploaded_image_url' )
			AND p.post_type = 'product'
		";

		$query .= self::build_status_clause( $args['status'], $query_args );
		$query .= self::build_search_clause( $args['search'], $query_args );

		// Every fragment appended above contributes only literal SQL and %s
		// placeholders, and $query_args always holds at least one status value.
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $query_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query is assembled from literal fragments returned by the build_*_clause() helpers above; every value is bound through a %s/%d placeholder, which static analysis cannot follow across the helper calls.
	}

	/**
	 * Show admin notices for pending video reviews
	 */
	public function show_pending_video_review_notices() {
		// Only show on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Get pending notices.
		$pending_notices = get_transient( 'sktpr_pending_video_reviews' );
		if ( empty( $pending_notices ) || ! is_array( $pending_notices ) ) {
			return;
		}

		// Count pending video reviews.
		$pending_count = count( $pending_notices );

		if ( $pending_count > 0 ) {
			$message = sprintf(
				// translators: 1: number of pending media reviews, 2: URL of the media reviews screen.
				_n(
					'%1$d new media review is awaiting approval. <a href="%2$s">Review it now</a>',
					'%1$d new media reviews are awaiting approval. <a href="%2$s">Review them now</a>',
					$pending_count,
					'product-reviews'
				),
				$pending_count,
				admin_url( 'admin.php?page=video-reviews-list&comment_status=pending' )
			);

			printf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				wp_kses_post( $message )
			);
		}

		// Clear the notices after showing them.
		delete_transient( 'sktpr_pending_video_reviews' );
	}
}
