<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Review_Filters
 *
 * Renders a filter bar (star rating + with-media toggle) above the review
 * list and tags each review with filterable CSS classes. The actual
 * show/hide filtering happens client-side in public.js.
 */
class Review_Filters {

	use Helpers;

	/**
	 * Review_Filters constructor.
	 */
	public function __construct() {
		// Priority 20 places the bar after the rating summary box, which echoes at 10.
		add_filter( 'woocommerce_product_review_list_args', array( $this, 'output_filter_bar' ), 20 );
		add_filter( 'comment_class', array( $this, 'add_filter_classes' ), 10, 4 );
	}

	/**
	 * Tags product review list items with rating and media classes so the
	 * frontend filter can match them without extra lookups.
	 *
	 * @param array       $classes Comment classes.
	 * @param array       $css_classes Additional classes (unused).
	 * @param int         $comment_id Comment ID.
	 * @param \WP_Comment $comment Comment object.
	 * @return array
	 */
	public function add_filter_classes( $classes, $css_classes, $comment_id, $comment ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Required by the comment_class filter signature.

		if ( is_admin() || 'product' !== get_post_type( $comment->comment_post_ID ) ) {
			return $classes;
		}

		$rating = get_comment_meta( $comment_id, 'rating', true );
		if ( $rating ) {
			$classes[] = 'sktpr-rating-' . intval( $rating );
		}

		if ( get_comment_meta( $comment_id, 'uploaded_video_url', true ) || get_comment_meta( $comment_id, 'uploaded_image_url', true ) ) {
			$classes[] = 'sktpr-has-media';
		}

		return $classes;
	}

	/**
	 * Echoes the filter bar above the review list (see Rating_Summary for why
	 * this filter is used positionally). Args pass through untouched.
	 *
	 * @param array $args wp_list_comments() arguments.
	 * @return array Unmodified arguments.
	 */
	public function output_filter_bar( $args ) {

		if ( is_admin() || ! is_product() ) {
			return $args;
		}

		$settings = $this->get_settings();
		if ( empty( $settings['show_review_filters'] ?? true ) ) {
			return $args;
		}

		echo $this->get_filter_bar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in get_filter_bar_html().

		return $args;
	}

	/**
	 * Builds the filter bar markup.
	 *
	 * @return string Escaped HTML, or empty string when there is nothing to filter.
	 */
	private function get_filter_bar_html() {

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return '';
		}

		$count = $product->get_review_count();
		if ( $count < 2 ) {
			return '';
		}

		$buttons = sprintf(
			'<button type="button" class="sktpr-filter-btn is-active" data-sktpr-filter-rating="all">%s</button>',
			esc_html__( 'All', 'product-reviews' )
		);

		if ( wc_review_ratings_enabled() ) {
			$rating_counts = $product->get_rating_counts();

			for ( $stars = 5; $stars >= 1; $stars-- ) {
				$star_count = intval( $rating_counts[ $stars ] ?? 0 );

				$buttons .= sprintf(
					'<button type="button" class="sktpr-filter-btn" data-sktpr-filter-rating="%1$d" %2$s>%1$d<span class="sktpr-filter-star" aria-hidden="true">&#9733;</span> <span class="sktpr-filter-count">(%3$d)</span></button>',
					$stars,
					disabled( $star_count, 0, false ),
					$star_count
				);
			}
		}

		$media_count = $this->get_media_review_count( $product->get_id() );
		if ( $media_count > 0 ) {
			$buttons .= sprintf(
				'<button type="button" class="sktpr-filter-btn sktpr-filter-media" data-sktpr-filter-media aria-pressed="false">%s <span class="sktpr-filter-count">(%d)</span></button>',
				esc_html__( 'With photos / videos', 'product-reviews' ),
				$media_count
			);
		}

		return sprintf(
			'<div class="sktpr-review-filters" role="group" aria-label="%s">%s</div>
			<p class="sktpr-review-filters-empty" hidden>%s</p>',
			esc_attr__( 'Filter reviews', 'product-reviews' ),
			$buttons,
			esc_html__( 'No reviews match the selected filters.', 'product-reviews' )
		);
	}

	/**
	 * Counts approved reviews on a product that carry video or photo media.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	private function get_media_review_count( $product_id ) {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Media review count needs a JOIN on comment meta that no core API exposes and must reflect live data.
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT c.comment_ID)
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID
			WHERE c.comment_post_ID = %d
			AND c.comment_approved = '1'
			AND cm.meta_key IN ( 'uploaded_video_url', 'uploaded_image_url' )",
				$product_id
			)
		);
	}
}
