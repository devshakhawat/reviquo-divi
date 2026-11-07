<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Rating_Summary
 *
 * Renders an average-rating summary box with a star histogram above the
 * review list on product pages.
 */
class Rating_Summary {

	use Helpers;

	/**
	 * Rating_Summary constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_review_list_args', array( $this, 'output_summary_before_list' ) );
	}

	/**
	 * Echoes the summary box directly above the review list.
	 *
	 * WooCommerce's single-product-reviews.php template has no action hook
	 * between the reviews title and the comment list; this filter is applied
	 * exactly there, so echoing from it places the box in the right spot.
	 * The args pass through untouched.
	 *
	 * @param array $args wp_list_comments() arguments.
	 * @return array Unmodified arguments.
	 */
	public function output_summary_before_list( $args ) {

		if ( is_admin() || ! is_product() ) {
			return $args;
		}

		$settings = $this->get_settings();
		if ( empty( $settings['show_rating_summary'] ?? true ) ) {
			return $args;
		}

		echo $this->get_summary_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in get_summary_html().

		return $args;
	}

	/**
	 * Builds the summary box markup.
	 *
	 * @return string Escaped HTML, or empty string when there is nothing to show.
	 */
	private function get_summary_html() {

		$product = wc_get_product( get_the_ID() );
		if ( ! $product || ! wc_review_ratings_enabled() ) {
			return '';
		}

		$count = $product->get_review_count();
		if ( ! $count ) {
			return '';
		}

		// Kept as WooCommerce's "2.00" string so the star label reads exactly like
		// the product header's ("Rated 2.00 out of 5") instead of "Rated 2 out of 5".
		$average       = $product->get_average_rating();
		$rating_counts = $product->get_rating_counts();

		$bars = '';
		for ( $stars = 5; $stars >= 1; $stars-- ) {
			$star_count = intval( $rating_counts[ $stars ] ?? 0 );
			$percent    = $count > 0 ? round( ( $star_count / $count ) * 100 ) : 0;

			$bars .= sprintf(
				'<div class="sktpr-rating-bar">
					<span class="sktpr-rating-bar-label">%1$d<span class="sktpr-rating-bar-star" aria-hidden="true">&#9733;</span></span>
					<span class="sktpr-rating-bar-track"><span class="sktpr-rating-bar-fill" style="width: %2$d%%;"></span></span>
					<span class="sktpr-rating-bar-count">%3$d</span>
				</div>',
				$stars,
				$percent,
				$star_count
			);
		}

		return sprintf(
			'<div class="sktpr-rating-summary">
				<div class="sktpr-rating-summary-score">
					<span class="sktpr-rating-average">%1$s</span>
					%2$s
					<span class="sktpr-rating-count">%3$s</span>
				</div>
				<div class="sktpr-rating-summary-bars" role="img" aria-label="%4$s">%5$s</div>
			</div>',
			esc_html( number_format_i18n( (float) $average, 1 ) ),
			wc_get_rating_html( $average ),
			// translators: %s: number of reviews.
			esc_html( sprintf( _n( 'Based on %s review', 'Based on %s reviews', $count, 'product-reviews' ), number_format_i18n( $count ) ) ),
			esc_attr__( 'Rating distribution', 'product-reviews' ),
			$bars
		);
	}
}
