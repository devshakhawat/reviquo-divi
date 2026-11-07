<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Public, read-only REST API for headless review storefronts.
 */
class Headless_API {

	const REST_NAMESPACE = 'sktpr/v1';

	/**
	 * Register REST hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register public read-only endpoints.
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/reviews',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'get_reviews' ),
				'args'                => $this->get_collection_args(),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/reviews/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'get_review' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/products/(?P<product_id>\d+)/review-summary',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'get_product_summary' ),
				'args'                => array(
					'product_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/review-displays',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'get_review_displays' ),
				'args'                => array(
					'page'     => $this->get_page_arg(),
					'per_page' => $this->get_per_page_arg( 20 ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/review-displays/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'get_review_display' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		$display_review_args                       = $this->get_collection_args();
		$display_review_args['id']                 = array(
			'required'          => true,
			'sanitize_callback' => 'absint',
		);
		$display_review_args['context_product_id'] = array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		);
		unset( $display_review_args['product_id'], $display_review_args['product_ids'], $display_review_args['order'], $display_review_args['per_page'] );

		register_rest_route(
			self::REST_NAMESPACE,
			'/review-displays/(?P<id>\d+)/reviews',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'get_review_display_reviews' ),
				'args'                => $display_review_args,
			)
		);
	}

	/**
	 * Return public approved reviews.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_reviews( $request ) {
		$product_ids = $this->get_requested_product_ids( $request );
		if ( is_wp_error( $product_ids ) ) {
			return $product_ids;
		}

		return $this->build_reviews_response( $request, $product_ids );
	}

	/**
	 * Return one public approved review.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_review( $request ) {
		$comment = $this->get_public_review( absint( $request['id'] ) );
		if ( ! $comment ) {
			return new \WP_Error( 'sktpr_review_not_found', __( 'Review not found.', 'product-reviews' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $this->prepare_review( $comment ) );
	}

	/**
	 * Return aggregate data for one published product.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_product_summary( $request ) {
		$product_id = absint( $request['product_id'] );
		$product    = $this->get_public_product( $product_id );
		if ( ! $product ) {
			return new \WP_Error( 'sktpr_product_not_found', __( 'Product not found.', 'product-reviews' ), array( 'status' => 404 ) );
		}

		$data               = $this->get_summary( array( $product_id ) );
		$data['product_id'] = $product_id;
		$data['_links']     = array(
			'product' => array( array( 'href' => $product->get_permalink() ) ),
			'reviews' => array( array( 'href' => add_query_arg( 'product_id', $product_id, rest_url( self::REST_NAMESPACE . '/reviews' ) ) ) ),
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Return published saved review displays.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_review_displays( $request ) {
		$page     = max( 1, absint( $request['page'] ) );
		$per_page = max( 1, min( 100, absint( $request['per_page'] ) ) );
		$query    = new \WP_Query(
			array(
				'post_type'      => Review_Shortcodes::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$displays = array_map( array( $this, 'prepare_review_display' ), $query->posts );
		$response = rest_ensure_response(
			array(
				'displays'   => $displays,
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total'       => intval( $query->found_posts ),
					'total_pages' => intval( $query->max_num_pages ),
				),
			)
		);

		return $this->add_pagination_headers( $response, intval( $query->found_posts ), intval( $query->max_num_pages ) );
	}

	/**
	 * Return one published saved review display.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_review_display( $request ) {
		$post = $this->get_public_display( absint( $request['id'] ) );
		if ( ! $post ) {
			return new \WP_Error( 'sktpr_review_display_not_found', __( 'Review display not found.', 'product-reviews' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $this->prepare_review_display( $post ) );
	}

	/**
	 * Return reviews using a saved display's source, ordering and page size.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_review_display_reviews( $request ) {
		$post = $this->get_public_display( absint( $request['id'] ) );
		if ( ! $post ) {
			return new \WP_Error( 'sktpr_review_display_not_found', __( 'Review display not found.', 'product-reviews' ), array( 'status' => 404 ) );
		}

		$settings    = plugin()->review_shortcodes->get_saved_settings( $post->ID );
		$product_ids = array();

		if ( 'current' === $settings['source'] ) {
			$context_product_id = absint( $request['context_product_id'] );
			if ( ! $context_product_id ) {
				return new \WP_Error(
					'sktpr_context_product_required',
					__( 'context_product_id is required for a current-product review display.', 'product-reviews' ),
					array( 'status' => 400 )
				);
			}
			$product_ids = array( $context_product_id );
		} elseif ( 'selected' === $settings['source'] ) {
			$product_ids = $settings['product_ids'];
		}

		$product_ids = $this->validate_product_ids( $product_ids );
		if ( is_wp_error( $product_ids ) ) {
			return $product_ids;
		}

		$response = $this->build_reviews_response(
			$request,
			$product_ids,
			array(
				'order'    => $settings['order'],
				'per_page' => $settings['per_page'],
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data            = $response->get_data();
		$data['display'] = $this->prepare_review_display( $post );
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Build a paginated reviews response.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @param array            $product_ids Product scope, or empty for all.
	 * @param array            $overrides Query overrides from a saved display.
	 * @return \WP_REST_Response
	 */
	private function build_reviews_response( $request, $product_ids, $overrides = array() ) {
		$page     = max( 1, absint( $request['page'] ) );
		$per_page = isset( $overrides['per_page'] ) ? absint( $overrides['per_page'] ) : absint( $request['per_page'] );
		$per_page = max( 1, min( 100, $per_page ) );
		$order    = isset( $overrides['order'] ) ? $overrides['order'] : sanitize_key( $request['order'] );
		$rating   = $request->has_param( 'rating' ) ? absint( $request['rating'] ) : 0;
		$media    = $request->has_param( 'media' ) && rest_sanitize_boolean( $request['media'] );

		$args = array(
			'status'      => 'approve',
			'type'        => 'review',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'parent'      => 0,
			'number'      => $per_page,
			'offset'      => ( $page - 1 ) * $per_page,
			'count'       => false,
		);

		if ( $product_ids ) {
			$args['post__in'] = $product_ids;
		}

		if ( 'oldest' === $order ) {
			$args['orderby'] = array(
				'comment_date_gmt' => 'ASC',
				'comment_ID'       => 'ASC',
			);
		} elseif ( in_array( $order, array( 'highest', 'lowest' ), true ) ) {
			$args['meta_key'] = 'rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = array(
				'meta_value_num' => 'highest' === $order ? 'DESC' : 'ASC',
				'comment_ID'     => 'DESC',
			);
		} else {
			$args['orderby'] = array(
				'comment_date_gmt' => 'DESC',
				'comment_ID'       => 'DESC',
			);
		}

		$meta_query = array( 'relation' => 'AND' );
		if ( $rating ) {
			$meta_query[] = array(
				'key'     => 'rating',
				'value'   => $rating,
				'compare' => '=',
			);
		}
		if ( $media ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'uploaded_video_url',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'uploaded_image_url',
					'compare' => 'EXISTS',
				),
			);
		}
		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query       = new \WP_Comment_Query( $args );
		$reviews     = array_map( array( $this, 'prepare_review' ), $query->comments );
		$total       = intval( $query->found_comments );
		$total_pages = $total ? (int) ceil( $total / $per_page ) : 0;
		$response    = rest_ensure_response(
			array(
				'reviews'    => array_values( $reviews ),
				'summary'    => $this->get_summary( $product_ids ),
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total'       => $total,
					'total_pages' => $total_pages,
				),
				'filters'    => array(
					'product_ids' => array_values( $product_ids ),
					'rating'      => $rating ? $rating : null,
					'media'       => $media,
					'order'       => $order,
				),
			)
		);

		return $this->add_pagination_headers( $response, $total, $total_pages );
	}

	/**
	 * Normalize one review without exposing private comment fields.
	 *
	 * @param \WP_Comment $comment Review comment.
	 * @return array
	 */
	private function prepare_review( $comment ) {
		$product = wc_get_product( $comment->comment_post_ID );
		$content = wp_kses_post( get_comment_text( $comment ) );
		$images  = array_values( array_unique( array_filter( array_map( 'esc_url_raw', flatten_array( get_comment_meta( $comment->comment_ID, 'uploaded_image_url' ) ) ) ) ) );
		$videos  = array_values( array_unique( array_filter( array_map( 'esc_url_raw', flatten_array( get_comment_meta( $comment->comment_ID, 'uploaded_video_url' ) ) ) ) ) );

		return array(
			'id'       => intval( $comment->comment_ID ),
			'date'     => mysql2date( 'c', $comment->comment_date, false ),
			'date_gmt' => mysql2date( 'c', $comment->comment_date_gmt, false ),
			'rating'   => absint( get_comment_meta( $comment->comment_ID, 'rating', true ) ),
			'content'  => array(
				'rendered' => wpautop( $content ),
				'plain'    => wp_strip_all_tags( $content ),
			),
			'author'   => array(
				'name'       => get_comment_author( $comment ),
				'avatar_url' => get_avatar_url( $comment, array( 'size' => 96 ) ),
				'verified'   => function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $comment->comment_ID ),
			),
			'product'  => array(
				'id'        => $product ? $product->get_id() : absint( $comment->comment_post_ID ),
				'name'      => $product ? $product->get_name() : get_the_title( $comment->comment_post_ID ),
				'slug'      => $product ? $product->get_slug() : get_post_field( 'post_name', $comment->comment_post_ID ),
				'permalink' => $product ? $product->get_permalink() : get_permalink( $comment->comment_post_ID ),
			),
			'media'    => array(
				'images' => $images,
				'videos' => $videos,
			),
			'_links'   => array(
				'self'    => array( array( 'href' => rest_url( self::REST_NAMESPACE . '/reviews/' . $comment->comment_ID ) ) ),
				'product' => array( array( 'href' => get_permalink( $comment->comment_post_ID ) ) ),
			),
		);
	}

	/**
	 * Aggregate public review statistics for a product scope.
	 *
	 * @param array $product_ids Product scope, or empty for all products.
	 * @return array
	 */
	private function get_summary( $product_ids ) {
		$args = array(
			'status'      => 'approve',
			'type'        => 'review',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'parent'      => 0,
			'number'      => 0,
			'fields'      => 'ids',
		);
		if ( $product_ids ) {
			$args['post__in'] = $product_ids;
		}

		$ids          = get_comments( $args );
		$ratings      = array_fill( 1, 5, 0 );
		$rated        = 0;
		$rating_total = 0;
		$with_media   = 0;

		foreach ( $ids as $comment_id ) {
			$rating = absint( get_comment_meta( $comment_id, 'rating', true ) );
			if ( $rating >= 1 && $rating <= 5 ) {
				++$ratings[ $rating ];
				++$rated;
				$rating_total += $rating;
			}
			if ( get_comment_meta( $comment_id, 'uploaded_video_url', true ) || get_comment_meta( $comment_id, 'uploaded_image_url', true ) ) {
				++$with_media;
			}
		}

		return array(
			'total'      => count( $ids ),
			'rated'      => $rated,
			'average'    => $rated ? round( $rating_total / $rated, 2 ) : 0,
			'ratings'    => $ratings,
			'with_media' => $with_media,
		);
	}

	/**
	 * Format a saved display and its public configuration.
	 *
	 * @param \WP_Post $post Saved display post.
	 * @return array
	 */
	private function prepare_review_display( $post ) {
		return array(
			'id'       => intval( $post->ID ),
			'name'     => get_the_title( $post ),
			'modified' => get_post_modified_time( 'c', true, $post ),
			'settings' => plugin()->review_shortcodes->get_saved_settings( $post->ID ),
			'_links'   => array(
				'self'    => array( array( 'href' => rest_url( self::REST_NAMESPACE . '/review-displays/' . $post->ID ) ) ),
				'reviews' => array( array( 'href' => rest_url( self::REST_NAMESPACE . '/review-displays/' . $post->ID . '/reviews' ) ) ),
			),
		);
	}

	/**
	 * Return a published saved display post.
	 *
	 * @param int $id Post ID.
	 * @return \WP_Post|false
	 */
	private function get_public_display( $id ) {
		$post = get_post( absint( $id ) );

		return $post && Review_Shortcodes::POST_TYPE === $post->post_type && 'publish' === $post->post_status ? $post : false;
	}

	/**
	 * Return an approved, top-level review for a published product.
	 *
	 * @param int $id Comment ID.
	 * @return \WP_Comment|false
	 */
	private function get_public_review( $id ) {
		$comment = get_comment( absint( $id ) );

		if ( ! $comment || '1' !== (string) $comment->comment_approved || 'review' !== $comment->comment_type || 0 !== intval( $comment->comment_parent ) ) {
			return false;
		}
		if ( 'product' !== get_post_type( $comment->comment_post_ID ) || 'publish' !== get_post_status( $comment->comment_post_ID ) ) {
			return false;
		}

		return $comment;
	}

	/**
	 * Return a published, top-level WooCommerce product.
	 *
	 * @param int $id Product ID.
	 * @return \WC_Product|false
	 */
	private function get_public_product( $id ) {
		$product = wc_get_product( absint( $id ) );

		return $product && 'publish' === get_post_status( $product->get_id() ) && 0 === intval( wp_get_post_parent_id( $product->get_id() ) ) ? $product : false;
	}

	/**
	 * Resolve and validate product filters from a collection request.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array|\WP_Error
	 */
	private function get_requested_product_ids( $request ) {
		$product_ids = self::sanitize_product_ids( $request['product_ids'] );
		$product_id  = absint( $request['product_id'] );
		if ( $product_id ) {
			$product_ids[] = $product_id;
		}

		return $this->validate_product_ids( array_values( array_unique( $product_ids ) ) );
	}

	/**
	 * Ensure every requested product is public.
	 *
	 * @param array $product_ids Product IDs.
	 * @return array|\WP_Error
	 */
	private function validate_product_ids( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			if ( ! $this->get_public_product( $product_id ) ) {
				return new \WP_Error( 'sktpr_product_not_found', __( 'One or more products could not be found.', 'product-reviews' ), array( 'status' => 404 ) );
			}
		}

		return array_values( array_unique( array_map( 'absint', $product_ids ) ) );
	}

	/**
	 * Sanitize a comma-separated or array product ID filter.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	public static function sanitize_product_ids( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Shared review collection arguments.
	 *
	 * @return array
	 */
	private function get_collection_args() {
		return array(
			'product_id'  => array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'product_ids' => array(
				'default'           => array(),
				'sanitize_callback' => array( __CLASS__, 'sanitize_product_ids' ),
			),
			'page'        => $this->get_page_arg(),
			'per_page'    => $this->get_per_page_arg( 10 ),
			'rating'      => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 5,
			),
			'media'       => array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'order'       => array(
				'type'              => 'string',
				'default'           => 'newest',
				'enum'              => array( 'newest', 'oldest', 'highest', 'lowest' ),
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/**
	 * Page route argument.
	 *
	 * @return array
	 */
	private function get_page_arg() {
		return array(
			'type'              => 'integer',
			'default'           => 1,
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
		);
	}

	/**
	 * Per-page route argument.
	 *
	 * @param int $default_value Default page size.
	 * @return array
	 */
	private function get_per_page_arg( $default_value ) {
		return array(
			'type'              => 'integer',
			'default'           => $default_value,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
		);
	}

	/**
	 * Add standard WordPress pagination headers.
	 *
	 * @param \WP_REST_Response $response REST response.
	 * @param int               $total Total results.
	 * @param int               $total_pages Total pages.
	 * @return \WP_REST_Response
	 */
	private function add_pagination_headers( $response, $total, $total_pages ) {
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}
}
