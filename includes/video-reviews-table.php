<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Video_Reviews_Table
 *
 * Extends WP_List_Table to display video reviews in admin area.
 */
class Video_Reviews_Table extends \WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'video-review',
				'plural'   => 'video-reviews',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get table columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'author'  => __( 'Author', 'product-reviews' ),
			'comment' => __( 'Review', 'product-reviews' ),
			'product' => __( 'Product', 'product-reviews' ),
			'rating'  => __( 'Rating', 'product-reviews' ),
			'media'   => __( 'Media', 'product-reviews' ),
		);

		if ( Features::has_pro_license() ) {
			$columns['reward'] = __( 'Reward', 'product-reviews' );
		}

		$columns['date'] = __( 'Date', 'product-reviews' );

		return $columns;
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'author'  => array( 'comment_author', false ),
			'product' => array( 'post_title', false ),
			'date'    => array( 'comment_date', true ),
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'trash'     => __( 'Move to Trash', 'product-reviews' ),
			'untrash'   => __( 'Restore', 'product-reviews' ),
			'spam'      => __( 'Mark as Spam', 'product-reviews' ),
			'unspam'    => __( 'Not Spam', 'product-reviews' ),
			'approve'   => __( 'Approve', 'product-reviews' ),
			'unapprove' => __( 'Unapprove', 'product-reviews' ),
		);
	}

	/**
	 * Prepare table items
	 */
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$search         = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table filter; the value is sanitized and changes no state.
		$orderby        = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'comment_date'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table filter; the value is sanitized and changes no state.
		$order          = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table filter; the value is sanitized and changes no state.
		$comment_status = isset( $_REQUEST['comment_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['comment_status'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table filter; the value is sanitized and changes no state.

		// Map comment status to database values.
		$status_map = array(
			'all'      => 'approve',
			'approved' => '1',
			'pending'  => '0',
			'spam'     => 'spam',
			'trash'    => 'trash',
		);

		$status = isset( $status_map[ $comment_status ] ) ? $status_map[ $comment_status ] : 'approve';
		if ( 'all' === $comment_status ) {
			$status = array( '1', '0' ); // Show approved and pending by default.
		}

		$args = array(
			'number'  => $per_page,
			'offset'  => $offset,
			'search'  => $search,
			'orderby' => $orderby,
			'order'   => $order,
			'status'  => $status,
		);

		$this->items = Video_Reviews_List::get_video_reviews( $args );
		$total_items = Video_Reviews_List::get_video_reviews_count(
			array(
				'search' => $search,
				'status' => $status,
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Default column display
	 *
	 * @param object $item Review row object.
	 * @param string $column_name Column slug.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'author':
				return $this->column_author( $item );
			case 'comment':
				return $this->column_comment( $item );
			case 'product':
				return $this->column_product( $item );
			case 'rating':
				return $this->column_rating( $item );
			case 'media':
				return $this->column_media( $item );
			case 'reward':
				return $this->column_reward( $item );
			case 'date':
				return $this->column_date( $item );
			default:
				return '';
		}
	}

	/**
	 * Checkbox column
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="comment[]" value="%s" />',
			$item->comment_ID
		);
	}

	/**
	 * Author column
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_author( $item ) {
		$avatar      = get_avatar( $item->comment_author_email, 40 );
		$author_name = ! empty( $item->comment_author ) ? $item->comment_author : __( 'Anonymous', 'product-reviews' );

		$actions  = array();
		$base_url = admin_url( 'admin.php?page=video-reviews-list' );

		// Edit action - use WordPress comment edit page.
		$edit_url        = admin_url( 'comment.php?action=editcomment&c=' . $item->comment_ID );
		$actions['edit'] = sprintf(
			'<a href="%s">%s</a>',
			$edit_url,
			__( 'Edit', 'product-reviews' )
		);

		// View action.
		$comment_link    = get_comment_link( $item->comment_ID );
		$actions['view'] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			$comment_link,
			__( 'View', 'product-reviews' )
		);

		// Status actions - use our custom handlers.
		if ( '1' === $item->comment_approved ) {
			$actions['unapprove'] = sprintf(
				'<a href="%s">%s</a>',
				wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'unapprove',
							'comment_id' => $item->comment_ID,
						),
						$base_url
					),
					'video-review-action_' . $item->comment_ID
				),
				__( 'Unapprove', 'product-reviews' )
			);
		} elseif ( '0' === $item->comment_approved ) {
			$actions['approve'] = sprintf(
				'<a href="%s">%s</a>',
				wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'approve',
							'comment_id' => $item->comment_ID,
						),
						$base_url
					),
					'video-review-action_' . $item->comment_ID
				),
				__( 'Approve', 'product-reviews' )
			);
		}

		// Spam/Unspam actions.
		if ( 'spam' === $item->comment_approved ) {
			$actions['unspam'] = sprintf(
				'<a href="%s">%s</a>',
				wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'unspam',
							'comment_id' => $item->comment_ID,
						),
						$base_url
					),
					'video-review-action_' . $item->comment_ID
				),
				__( 'Not Spam', 'product-reviews' )
			);
		} else {
			$actions['spam'] = sprintf(
				'<a href="%s">%s</a>',
				wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'spam',
							'comment_id' => $item->comment_ID,
						),
						$base_url
					),
					'video-review-action_' . $item->comment_ID
				),
				__( 'Spam', 'product-reviews' )
			);
		}

		// Trash/Untrash actions.
		if ( 'trash' === $item->comment_approved ) {
			$actions['untrash'] = sprintf(
				'<a href="%s">%s</a>',
				wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'untrash',
							'comment_id' => $item->comment_ID,
						),
						$base_url
					),
					'video-review-action_' . $item->comment_ID
				),
				__( 'Restore', 'product-reviews' )
			);
		} else {
			$actions['trash'] = sprintf(
				'<a href="%s" class="submitdelete">%s</a>',
				wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'trash',
							'comment_id' => $item->comment_ID,
						),
						$base_url
					),
					'video-review-action_' . $item->comment_ID
				),
				__( 'Trash', 'product-reviews' )
			);
		}

		return sprintf(
			'<div class="sktpr-author">
				<div class="sktpr-author__avatar">%s</div>
				<div class="sktpr-author__details">
					<strong class="sktpr-author__name">%s</strong>
					<span class="sktpr-author__email">%s</span>
					%s
				</div>
			</div>',
			$avatar,
			esc_html( $author_name ),
			esc_html( $item->comment_author_email ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Comment column
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_comment( $item ) {
		$comment_text = wp_trim_words( $item->comment_content, 24, '…' );

		$states = array(
			'0'     => array( 'pending', __( 'Pending', 'product-reviews' ) ),
			'spam'  => array( 'spam', __( 'Spam', 'product-reviews' ) ),
			'trash' => array( 'trash', __( 'Trash', 'product-reviews' ) ),
		);

		$status_label = '';
		if ( isset( $states[ $item->comment_approved ] ) ) {
			list( $slug, $label ) = $states[ $item->comment_approved ];
			$status_label         = sprintf(
				'<span class="sktpr-post-state sktpr-post-state--%s">%s</span>',
				esc_attr( $slug ),
				esc_html( $label )
			);
		}

		return sprintf(
			'<div class="sktpr-comment">%s<p class="sktpr-comment-text">%s</p></div>',
			$status_label,
			esc_html( $comment_text )
		);
	}

	/**
	 * Product column
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_product( $item ) {
		$product_link      = get_edit_post_link( $item->product_id );
		$product_view_link = get_permalink( $item->product_id );

		return sprintf(
			'<div class="sktpr-product">
				<a class="sktpr-product__title" href="%s">%s</a>
				<a class="sktpr-product__view" href="%s" target="_blank" rel="noopener">%s</a>
			</div>',
			esc_url( $product_link ),
			esc_html( $item->post_title ),
			esc_url( $product_view_link ),
			esc_html__( 'View product', 'product-reviews' )
		);
	}

	/**
	 * Rating column
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_rating( $item ) {
		$rating = get_comment_meta( $item->comment_ID, 'rating', true );

		if ( empty( $rating ) ) {
			return '<span class="sktpr-empty">—</span>';
		}

		$rating = (int) $rating;
		$stars  = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			if ( $i <= $rating ) {
				$stars .= '<span class="dashicons dashicons-star-filled"></span>';
			} else {
				$stars .= '<span class="dashicons dashicons-star-empty"></span>';
			}
		}

		return sprintf(
			'<div class="sktpr-star-rating" title="%3$s"><span class="sktpr-star-rating__stars">%1$s</span><span class="sktpr-rating-text">%2$d/5</span></div>',
			$stars,
			$rating,
			// translators: %d: star rating out of 5.
			esc_attr( sprintf( __( 'Rated %d out of 5', 'product-reviews' ), $rating ) )
		);
	}

	/**
	 * Normalizes a serialized media-URL meta value into a flat array.
	 *
	 * @param mixed $raw Raw meta value from the query.
	 * @return array
	 */
	private function get_media_urls( $raw ) {
		$urls = maybe_unserialize( $raw );

		if ( empty( $urls ) ) {
			return array();
		}

		if ( is_array( $urls ) && isset( $urls[0] ) && is_array( $urls[0] ) ) {
			$urls = $urls[0];
		}

		if ( ! is_array( $urls ) ) {
			$urls = array( $urls );
		}

		return array_values( array_filter( $urls ) );
	}

	/**
	 * Media column - thumbnails of the review's videos and photos.
	 *
	 * Uses the same markup as the storefront so the public lightbox
	 * (public.js) opens them; the bundle is enqueued for this screen in
	 * Video_Reviews_List::enqueue_admin_scripts().
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_media( $item ) {
		$video_urls = $this->get_media_urls( $item->video_urls ?? '' );
		$image_urls = $this->get_media_urls( $item->image_urls ?? '' );

		if ( empty( $video_urls ) && empty( $image_urls ) ) {
			return '<span class="sktpr-empty">—</span>';
		}

		$thumbs = '';

		foreach ( $video_urls as $url ) {
			$thumbs .= sprintf(
				'<button type="button" class="sktpr-media-thumb sktpr-media-thumb--video" data-sktpr-type="video" data-sktpr-src="%1$s" aria-label="%2$s">
					<video preload="metadata" muted playsinline tabindex="-1"><source src="%1$s#t=0.1"></video>
					<span class="sktpr-media-play" aria-hidden="true"></span>
				</button>',
				esc_url( $url ),
				esc_attr__( 'Play review video', 'product-reviews' )
			);
		}

		foreach ( $image_urls as $url ) {
			$thumbs .= sprintf(
				'<button type="button" class="sktpr-media-thumb sktpr-media-thumb--image" data-sktpr-type="image" data-sktpr-src="%1$s" aria-label="%2$s">
					<img src="%1$s" alt="" loading="lazy" />
				</button>',
				esc_url( $url ),
				esc_attr__( 'View review photo', 'product-reviews' )
			);
		}

		$counts = array();
		if ( ! empty( $video_urls ) ) {
			// translators: %d: number of videos attached to the review.
			$counts[] = sprintf( _n( '%d video', '%d videos', count( $video_urls ), 'product-reviews' ), count( $video_urls ) );
		}
		if ( ! empty( $image_urls ) ) {
			// translators: %d: number of photos attached to the review.
			$counts[] = sprintf( _n( '%d photo', '%d photos', count( $image_urls ), 'product-reviews' ), count( $image_urls ) );
		}

		return sprintf(
			'<div class="sktpr-media"><div class="sktpr-review-media">%s</div><span class="sktpr-media__count">%s</span></div>',
			$thumbs,
			esc_html( implode( ' · ', $counts ) )
		);
	}

	/**
	 * Date column
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_date( $item ) {
		$date = mysql2date( get_option( 'date_format' ), $item->comment_date );
		$time = mysql2date( get_option( 'time_format' ), $item->comment_date );

		return sprintf(
			'<div class="sktpr-date">
				<span class="sktpr-date__main">%s</span>
				<span class="sktpr-date__time">%s</span>
			</div>',
			esc_html( $date ),
			esc_html( $time )
		);
	}

	/**
	 * Reward coupon column
	 *
	 * @param object $item Review row object.
	 * @return string
	 */
	public function column_reward( $item ) {
		$coupon = plugin()->review_rewards->get_comment_coupon( $item->comment_ID );

		if ( ! $coupon ) {
			return '<span aria-hidden="true">&mdash;</span>';
		}

		if ( $coupon->get_usage_count() > 0 ) {
			$state = __( 'Used', 'product-reviews' );
		} elseif ( 'publish' !== get_post_status( $coupon->get_id() ) ) {
			$state = __( 'Suspended', 'product-reviews' );
		} else {
			$state = __( 'Unused', 'product-reviews' );
		}

		return sprintf(
			'<a href="%s"><code>%s</code></a><br><small>%s &middot; %s</small>',
			esc_url( get_edit_post_link( $coupon->get_id() ) ),
			esc_html( $coupon->get_code() ),
			esc_html( plugin()->review_rewards->format_discount( $coupon->get_discount_type(), $coupon->get_amount() ) ),
			esc_html( $state )
		);
	}

	/**
	 * Display when no items found
	 */
	public function no_items() {
		esc_html_e( 'No video or photo reviews found.', 'product-reviews' );
	}

	/**
	 * Get views for different comment statuses
	 *
	 * @return array
	 */
	public function get_views() {
		global $wpdb;

		$views   = array();
		$current = isset( $_REQUEST['comment_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['comment_status'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table filter; the value is sanitized and changes no state.

		// Get counts for each status.
		$counts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Moderation counts are aggregated with a JOIN no core API exposes and must reflect live data.
			"
			SELECT c.comment_approved, COUNT(DISTINCT c.comment_ID) as count
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
			INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
			WHERE cm.meta_key IN ( 'uploaded_video_url', 'uploaded_image_url' )
			AND p.post_type = 'product'
			GROUP BY c.comment_approved
		"
		);

		$status_counts = array(
			'all'      => 0,
			'approved' => 0,
			'pending'  => 0,
			'spam'     => 0,
			'trash'    => 0,
		);

		foreach ( $counts as $count ) {
			$status_counts['all'] += $count->count;

			switch ( $count->comment_approved ) {
				case '1':
					$status_counts['approved'] = $count->count;
					break;
				case '0':
					$status_counts['pending'] = $count->count;
					break;
				case 'spam':
					$status_counts['spam'] = $count->count;
					break;
				case 'trash':
					$status_counts['trash'] = $count->count;
					break;
			}
		}

		$views['all'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			admin_url( 'admin.php?page=video-reviews-list' ),
			'all' === $current ? ' class="current"' : '',
			__( 'All', 'product-reviews' ),
			$status_counts['all']
		);

		if ( $status_counts['approved'] > 0 ) {
			$views['approved'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				admin_url( 'admin.php?page=video-reviews-list&comment_status=approved' ),
				'approved' === $current ? ' class="current"' : '',
				__( 'Approved', 'product-reviews' ),
				$status_counts['approved']
			);
		}

		if ( $status_counts['pending'] > 0 ) {
			$views['pending'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				admin_url( 'admin.php?page=video-reviews-list&comment_status=pending' ),
				'pending' === $current ? ' class="current"' : '',
				__( 'Pending', 'product-reviews' ),
				$status_counts['pending']
			);
		}

		if ( $status_counts['spam'] > 0 ) {
			$views['spam'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				admin_url( 'admin.php?page=video-reviews-list&comment_status=spam' ),
				'spam' === $current ? ' class="current"' : '',
				__( 'Spam', 'product-reviews' ),
				$status_counts['spam']
			);
		}

		if ( $status_counts['trash'] > 0 ) {
			$views['trash'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				admin_url( 'admin.php?page=video-reviews-list&comment_status=trash' ),
				'trash' === $current ? ' class="current"' : '',
				__( 'Trash', 'product-reviews' ),
				$status_counts['trash']
			);
		}

		return $views;
	}
}
