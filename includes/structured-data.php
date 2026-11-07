<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Structured_Data
 *
 * Enriches WooCommerce's product JSON-LD with review media. WooCommerce core
 * already outputs Product schema with aggregateRating and review entries;
 * emitting our own block would duplicate and conflict with it, so this class
 * only extends review entries with the uploaded photos (image) and videos
 * (VideoObject) that core does not know about.
 *
 * Core builds the product schema's review list inline (5 most recent rated
 * reviews) without applying its per-review filter, so both filters are
 * hooked: woocommerce_structured_data_product covers that inline list, and
 * woocommerce_structured_data_review covers any per-review generation.
 */
class Structured_Data {

	use Helpers;

	/**
	 * Structured_Data constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_structured_data_product', array( $this, 'enrich_product_schema' ), 10, 2 );
		add_filter( 'woocommerce_structured_data_review', array( $this, 'enrich_review_schema' ), 10, 2 );
	}

	/**
	 * Whether media enrichment is enabled in settings.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		$settings = $this->get_settings();

		return ! empty( $settings['enable_rich_snippets'] ?? true );
	}

	/**
	 * Enriches the review entries inside the Product schema markup.
	 *
	 * @param array       $markup Product schema markup.
	 * @param \WC_Product $product The product.
	 * @return array
	 */
	public function enrich_product_schema( $markup, $product ) {

		if ( ! $this->is_enabled() || empty( $markup['review'] ) || ! is_array( $markup['review'] ) ) {
			return $markup;
		}

		// Mirrors the query core used to build $markup['review'] so entries
		// can be matched back to their comments by index.
		$comments = get_comments(
			array(
				'number'      => 5,
				'post_id'     => $product->get_id(),
				'status'      => 'approve',
				'post_status' => 'publish',
				'post_type'   => 'product',
				'parent'      => 0,
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'rating',
						'type'    => 'NUMERIC',
						'compare' => '>',
						'value'   => 0,
					),
				),
			)
		);

		foreach ( array_values( $markup['review'] ) as $index => $entry ) {
			if ( ! isset( $comments[ $index ] ) ) {
				break;
			}

			$comment = $comments[ $index ];

			// Skip if the replicated query drifted from what core rendered.
			$entry_author = $entry['author']['name'] ?? null;
			if ( $entry_author && get_comment_author( $comment ) !== $entry_author ) {
				continue;
			}

			$markup['review'][ $index ] = $this->add_media_to_markup( $entry, $comment );
		}

		return $markup;
	}

	/**
	 * Enriches a single review's structured data (per-review filter path).
	 *
	 * @param array       $markup Review schema markup.
	 * @param \WP_Comment $comment The review comment.
	 * @return array
	 */
	public function enrich_review_schema( $markup, $comment ) {

		if ( ! $this->is_enabled() ) {
			return $markup;
		}

		return $this->add_media_to_markup( $markup, $comment );
	}

	/**
	 * Adds image and video properties for a review's uploaded media.
	 *
	 * @param array       $markup Review schema markup.
	 * @param \WP_Comment $comment The review comment.
	 * @return array
	 */
	private function add_media_to_markup( $markup, $comment ) {

		$image_urls = array_values( array_filter( flatten_array( get_comment_meta( $comment->comment_ID, 'uploaded_image_url' ) ) ) );
		$video_urls = array_values( array_filter( flatten_array( get_comment_meta( $comment->comment_ID, 'uploaded_video_url' ) ) ) );

		if ( ! empty( $image_urls ) ) {
			$markup['image'] = array_map( 'esc_url_raw', $image_urls );
		}

		if ( ! empty( $video_urls ) ) {
			$videos = array();

			foreach ( $video_urls as $url ) {
				$video = array(
					'@type'       => 'VideoObject',
					'name'        => sprintf(
						/* translators: %s: review author name. */
						__( 'Customer review video by %s', 'product-reviews' ),
						$comment->comment_author
					),
					'description' => wp_trim_words( $comment->comment_content, 25 ),
					'contentUrl'  => esc_url_raw( $url ),
					'uploadDate'  => mysql2date( 'c', $comment->comment_date_gmt, false ),
				);

				// Google requires thumbnailUrl for video rich results; the
				// review's first photo is the best stand-in available.
				if ( ! empty( $image_urls ) ) {
					$video['thumbnailUrl'] = esc_url_raw( $image_urls[0] );
				}

				$videos[] = $video;
			}

			$markup['video'] = count( $videos ) === 1 ? $videos[0] : $videos;
		}

		return $markup;
	}
}
