<?php // phpcs:ignore
namespace SKTPREVIEW;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Class Display_Video
 *
 * Handles the display of video in WooCommerce reviews.
 */
class Display_Video {

	use Helpers;

	/**
	 * Display_Video constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_review_comment_text', array( $this, 'display_video' ) );
		add_action( 'init', array( $this, 'replace_review_meta' ), 20 );
	}

	/**
	 * Replaces WooCommerce's metadata callback so the badge can remain part of
	 * the author/date line rather than becoming a separate layout item.
	 */
	public function replace_review_meta() {

		remove_action( 'woocommerce_review_meta', 'woocommerce_review_display_meta', 10 );
		add_action( 'woocommerce_review_meta', array( $this, 'display_review_meta' ), 10 );
	}

	/**
	 * Renders WooCommerce-compatible review metadata with the badge inline.
	 *
	 * @param \WP_Comment $comment The review comment.
	 */
	public function display_review_meta( $comment ) {

		if ( '0' === $comment->comment_approved ) {
			printf(
				'<p class="meta"><em class="woocommerce-review__awaiting-approval">%s</em></p>',
				esc_html__( 'Your review is awaiting approval', 'product-reviews' )
			);
			return;
		}

		?>
		<p class="meta">
			<strong class="woocommerce-review__author"><?php echo esc_html( get_comment_author( $comment ) ); ?> </strong>
			<span class="woocommerce-review__dash">&ndash;</span>
			<time class="woocommerce-review__published-date" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>"><?php echo esc_html( get_comment_date( wc_date_format(), $comment ) ); ?></time>
			<?php $this->display_verified_badge( $comment ); ?>
		</p>
		<?php
	}

	/**
	 * Renders a "Verified Customer" badge on reviews written by a confirmed
	 * purchaser of the product.
	 *
	 * @param \WP_Comment $comment The review comment.
	 */
	public function display_verified_badge( $comment ) {

		$settings = $this->get_settings();
		if ( empty( $settings['show_verified_badge'] ?? true ) ) {
			return;
		}

		// Pending reviews show their own "awaiting approval" notice instead.
		if ( '0' === $comment->comment_approved ) {
			return;
		}

		if ( ! function_exists( 'wc_review_is_from_verified_owner' ) || ! wc_review_is_from_verified_owner( $comment->comment_ID ) ) {
			return;
		}

		printf(
			'<span class="sktpr-verified-badge"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/></svg><span>%s</span></span>',
			esc_html__( 'Verified Customer', 'product-reviews' )
		);
	}

	/**
	 * Displays the review media as a thumbnail grid opened in a lightbox.
	 *
	 * Videos render as muted metadata-preloaded thumbnails (the #t=0.1 media
	 * fragment makes browsers paint the first frame as a poster) with a play
	 * overlay; photos as square thumbnails. Clicking any thumbnail opens the
	 * lightbox built in public.js.
	 *
	 * @param object $comments The comment object.
	 */
	public function display_video( $comments ) {

		$comment_id = $comments->comment_ID;
		$video_urls = flatten_array( get_comment_meta( $comment_id, 'uploaded_video_url' ) );
		$image_urls = flatten_array( get_comment_meta( $comment_id, 'uploaded_image_url' ) );

		$items = '';

		foreach ( $video_urls as $url ) {
			if ( empty( $url ) ) {
				continue;
			}

			$items .= sprintf(
				'<button type="button" class="sktpr-media-thumb sktpr-media-thumb--video" data-sktpr-type="video" data-sktpr-src="%1$s" aria-label="%2$s">
					<video preload="metadata" muted playsinline tabindex="-1"><source src="%1$s#t=0.1"></video>
					<span class="sktpr-media-play" aria-hidden="true"></span>
				</button>',
				esc_url( $url ),
				esc_attr__( 'Play review video', 'product-reviews' )
			);
		}

		foreach ( $image_urls as $url ) {
			if ( empty( $url ) ) {
				continue;
			}

			$items .= sprintf(
				'<button type="button" class="sktpr-media-thumb sktpr-media-thumb--image" data-sktpr-type="image" data-sktpr-src="%1$s" aria-label="%2$s">
					<img src="%1$s" alt="%2$s" loading="lazy" />
				</button>',
				esc_url( $url ),
				esc_attr__( 'View review photo', 'product-reviews' )
			);
		}

		if ( $items ) {
			printf( '<div class="sktpr-review-media">%s</div>', $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped above.
		}
	}
}
