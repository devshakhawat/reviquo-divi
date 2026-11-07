<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

/**
 * Saved review shortcode builder and storefront renderer.
 */
class Review_Shortcodes {

	const POST_TYPE = 'sktpr_review_sc';
	const META_KEY  = '_sktpr_shortcode_settings';

	/**
	 * Shortcode tag for saved displays.
	 */
	const SHORTCODE = 'reviquo';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );

		add_action( 'init', array( $this, 'migrate_legacy_palette' ), 20 );

		add_action( 'wp_ajax_sktpr_shortcode_list', array( $this, 'ajax_list' ) );
		add_action( 'wp_ajax_sktpr_shortcode_save', array( $this, 'ajax_save' ) );
		add_action( 'wp_ajax_sktpr_shortcode_duplicate', array( $this, 'ajax_duplicate' ) );
		add_action( 'wp_ajax_sktpr_shortcode_delete', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_sktpr_shortcode_bulk_delete', array( $this, 'ajax_bulk_delete' ) );
		add_action( 'wp_ajax_sktpr_shortcode_products', array( $this, 'ajax_products' ) );
		add_action( 'wp_ajax_sktpr_shortcode_preview', array( $this, 'ajax_preview' ) );
		add_action( 'wp_ajax_sktpr_shortcode_more', array( $this, 'ajax_more' ) );
		add_action( 'wp_ajax_nopriv_sktpr_shortcode_more', array( $this, 'ajax_more' ) );
	}

	/**
	 * Register private storage for named shortcode configurations.
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Review Shortcodes', 'product-reviews' ),
					'singular_name' => __( 'Review Shortcode', 'product-reviews' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Update saved shortcodes that still use the former plugin defaults.
	 *
	 * @return void
	 */
	public function migrate_legacy_palette() {
		if ( 'wcreviews-1' === get_option( 'sktpr_shortcode_palette_version' ) ) {
			return;
		}

		$shortcode_ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$legacy_colors = array(
			'text_color'   => array( '#1f2937', '#17201e' ),
			'accent_color' => array( '#4f5edc', '#ff694d' ),
			'star_color'   => array( '#f5a623', '#ffb627' ),
		);

		foreach ( $shortcode_ids as $shortcode_id ) {
			$settings = get_post_meta( $shortcode_id, self::META_KEY, true );
			$changed  = false;

			if ( ! is_array( $settings ) ) {
				continue;
			}

			foreach ( $legacy_colors as $key => $colors ) {
				if ( isset( $settings[ $key ] ) && 0 === strcasecmp( $settings[ $key ], $colors[0] ) ) {
					$settings[ $key ] = $colors[1];
					$changed          = true;
				}
			}

			if ( $changed ) {
				update_post_meta( $shortcode_id, self::META_KEY, $settings );
			}
		}

		update_option( 'sktpr_shortcode_palette_version', 'wcreviews-1' );
	}

	/**
	 * Default shortcode settings.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'source'           => 'all',
			'product_ids'      => array(),
			'layout'           => 'list',
			'per_page'         => 6,
			'order'            => 'newest',
			'show_summary'     => true,
			'show_filters'     => true,
			'show_product'     => true,
			'show_review_text' => true,
			'show_avatar'      => true,
			'show_verified'    => true,
			'show_media'       => true,
			'show_photos'      => true,
			'show_videos'      => true,
			'columns'          => 3,
			'tablet_columns'   => 2,
			'mobile_columns'   => 1,
			'slider_autoplay'  => false,
			'slider_delay'     => 5,
			'slider_arrows'    => true,
			'slider_dots'      => true,
			'gap'              => 20,
			'radius'           => 10,
			'background_color' => '#ffffff',
			'text_color'       => '#17201e',
			'accent_color'     => '#ff694d',
			'star_color'       => '#ffb627',
		);
	}

	/**
	 * Sanitize a complete shortcode settings payload.
	 *
	 * @param array $settings Raw settings.
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		$defaults = $this->get_defaults();
		$settings = is_array( $settings ) ? $settings : array();
		$clean    = $defaults;

		$clean['source'] = in_array( $settings['source'] ?? '', array( 'current', 'selected', 'all' ), true ) ? $settings['source'] : $defaults['source'];
		$clean['layout'] = in_array( $settings['layout'] ?? '', array( 'list', 'grid', 'masonry', 'slider', 'gallery', 'testimonial', 'spotlight', 'compact', 'reels', 'marquee', 'timeline', 'accordion', 'chat' ), true ) ? $settings['layout'] : $defaults['layout'];
		$clean['order']  = in_array( $settings['order'] ?? '', array( 'newest', 'oldest', 'highest', 'lowest' ), true ) ? $settings['order'] : $defaults['order'];

		$product_ids             = isset( $settings['product_ids'] ) && is_array( $settings['product_ids'] ) ? $settings['product_ids'] : array();
		$product_ids             = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
		$clean['product_ids']    = array_values(
			array_filter(
				$product_ids,
				function ( $product_id ) {
					$post = get_post( $product_id );
					return $post && 'product' === $post->post_type && 'publish' === $post->post_status && 0 === intval( $post->post_parent );
				}
			)
		);
		$clean['per_page']       = max( 1, min( 24, absint( $settings['per_page'] ?? $defaults['per_page'] ) ) );
		$clean['columns']        = max( 1, min( 4, absint( $settings['columns'] ?? $defaults['columns'] ) ) );
		$clean['tablet_columns'] = max( 1, min( 4, absint( $settings['tablet_columns'] ?? $defaults['tablet_columns'] ) ) );
		$clean['mobile_columns'] = max( 1, min( 4, absint( $settings['mobile_columns'] ?? $defaults['mobile_columns'] ) ) );
		$clean['slider_delay']   = max( 1, min( 20, absint( $settings['slider_delay'] ?? $defaults['slider_delay'] ) ) );
		$clean['gap']            = max( 0, min( 48, absint( $settings['gap'] ?? $defaults['gap'] ) ) );
		$clean['radius']         = max( 0, min( 32, absint( $settings['radius'] ?? $defaults['radius'] ) ) );

		foreach ( array( 'show_summary', 'show_filters', 'show_product', 'show_review_text', 'show_avatar', 'show_verified', 'slider_autoplay', 'slider_arrows', 'slider_dots' ) as $key ) {
			$clean[ $key ] = filter_var( $settings[ $key ] ?? $defaults[ $key ], FILTER_VALIDATE_BOOLEAN );
		}

		$legacy_show_media    = filter_var( $settings['show_media'] ?? $defaults['show_media'], FILTER_VALIDATE_BOOLEAN );
		$clean['show_photos'] = filter_var( $settings['show_photos'] ?? $legacy_show_media, FILTER_VALIDATE_BOOLEAN );
		$clean['show_videos'] = filter_var( $settings['show_videos'] ?? $legacy_show_media, FILTER_VALIDATE_BOOLEAN );
		$clean['show_media']  = $clean['show_photos'] || $clean['show_videos'];

		foreach ( array( 'background_color', 'text_color', 'accent_color', 'star_color' ) as $key ) {
			$color         = $this->sanitize_color( $settings[ $key ] ?? '' );
			$clean[ $key ] = $color ? $color : $defaults[ $key ];
		}

		return $clean;
	}

	/**
	 * Sanitize a hex or rgb()/rgba() color (page-builder color pickers can
	 * return either).
	 *
	 * @param mixed $color Raw color.
	 * @return string Sanitized color, or '' when invalid.
	 */
	private function sanitize_color( $color ) {
		if ( ! is_string( $color ) ) {
			return '';
		}

		$color = trim( $color );

		// Divi 5 global colors arrive as $variable(...)$ / var(--gcid-...) references.
		if ( ( 0 === strpos( $color, '$variable(' ) || 0 === strpos( $color, 'var(--gcid-' ) ) && method_exists( '\\ET\\Builder\\Packages\\StyleLibrary\\Utils\\Utils', 'resolve_global_color_to_value' ) ) {
			$resolved = \ET\Builder\Packages\StyleLibrary\Utils\Utils::resolve_global_color_to_value( $color ); // @phpstan-ignore-line
			$color    = is_string( $resolved ) ? trim( $resolved ) : '';
		}

		$hex = sanitize_hex_color( $color );
		if ( $hex ) {
			return $hex;
		}

		if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0|1|0?\.\d+)\s*)?\)$/i', $color, $parts ) ) {
			$channels = array_map( 'absint', array_slice( $parts, 1, 3 ) );
			if ( max( $channels ) > 255 ) {
				return '';
			}

			return isset( $parts[4] ) && '' !== $parts[4]
				? sprintf( 'rgba(%d,%d,%d,%s)', $channels[0], $channels[1], $channels[2], $parts[4] )
				: sprintf( 'rgb(%d,%d,%d)', $channels[0], $channels[1], $channels[2] );
		}

		return '';
	}

	/**
	 * Convert page-builder field values (on/off toggles, comma-separated
	 * product IDs) into a shortcode settings array. Unknown keys are ignored
	 * and missing keys fall back to the defaults in sanitize_settings().
	 *
	 * @param array $values Builder values keyed by shortcode setting name.
	 * @return array Sanitized settings.
	 */
	public function settings_from_builder( $values ) {
		$values = is_array( $values ) ? array_intersect_key( $values, $this->get_defaults() ) : array();

		if ( isset( $values['product_ids'] ) && ! is_array( $values['product_ids'] ) ) {
			$values['product_ids'] = preg_split( '/[\s,]+/', (string) $values['product_ids'], -1, PREG_SPLIT_NO_EMPTY );
		}

		return $this->sanitize_settings( $values );
	}

	/**
	 * HMAC for an inline settings payload, so the public Load More endpoint
	 * only accepts settings this site rendered.
	 *
	 * @param string $payload JSON-encoded settings.
	 * @return string
	 */
	private function sign_settings( $payload ) {
		return hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) );
	}

	/**
	 * Render a display from settings configured inline in a page builder
	 * instead of a saved shortcode.
	 *
	 * @param array    $settings Builder settings (see settings_from_builder()).
	 * @param int|null $context_product_id Product for the "current product" source; null detects it from the request.
	 * @param bool     $preview Whether this is a builder preview.
	 * @return string
	 */
	public function render_inline( $settings, $context_product_id = null, $preview = false ) {
		$settings = $this->apply_plan_limits( $this->settings_from_builder( $settings ) );

		if ( null === $context_product_id ) {
			$context = 'current' === $settings['source'] && function_exists( 'is_product' ) && is_product() ? get_the_ID() : 0;
		} else {
			$context = 'current' === $settings['source'] && 'product' === get_post_type( $context_product_id ) ? absint( $context_product_id ) : 0;
		}

		if ( ! $preview ) {
			plugin()->scripts->enqueue_public_assets();
		}

		return $this->render_view( 0, $settings, $context, $preview );
	}

	/**
	 * Apply shortcode features available for the current license.
	 *
	 * @param array $settings Sanitized shortcode settings.
	 * @return array
	 */
	private function apply_plan_limits( $settings ) {
		if ( Features::has_pro_license() ) {
			return $settings;
		}

		$settings['show_verified'] = false;

		return $settings;
	}

	/**
	 * Keep saved Pro values when a Free user edits another shortcode setting.
	 *
	 * @param array $submitted Sanitized submitted settings.
	 * @param int   $shortcode_id Existing shortcode post ID.
	 * @return array
	 */
	private function preserve_locked_settings( $submitted, $shortcode_id ) {
		if ( Features::has_pro_license() ) {
			return $submitted;
		}

		if ( ! $shortcode_id ) {
			$submitted['show_verified'] = false;

			return $submitted;
		}

		$stored = $this->sanitize_settings( get_post_meta( $shortcode_id, self::META_KEY, true ) );

		$submitted['show_verified'] = $stored['show_verified'];

		return $submitted;
	}

	/**
	 * Return the public, license-aware settings for one saved display.
	 *
	 * @param int $id Saved display ID.
	 * @return array|false
	 */
	public function get_saved_settings( $id ) {
		$post = get_post( absint( $id ) );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return false;
		}

		return $this->apply_plan_limits( $this->sanitize_settings( get_post_meta( $post->ID, self::META_KEY, true ) ) );
	}

	/**
	 * Render a saved shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, self::SHORTCODE );
		$id   = absint( $atts['id'] );
		$post = get_post( $id );

		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		$settings = $this->apply_plan_limits( $this->sanitize_settings( get_post_meta( $id, self::META_KEY, true ) ) );
		$context  = 'current' === $settings['source'] && is_product() ? get_the_ID() : 0;

		plugin()->scripts->enqueue_public_assets();

		return $this->render_view( $id, $settings, $context, false );
	}

	/**
	 * Render a saved display for the block editor.
	 *
	 * @param int $id Saved shortcode ID.
	 * @param int $context_product_id Product being edited, when applicable.
	 * @return string
	 */
	public function render_saved_preview( $id, $context_product_id = 0 ) {
		$post = get_post( absint( $id ) );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		$settings = $this->apply_plan_limits( $this->sanitize_settings( get_post_meta( $post->ID, self::META_KEY, true ) ) );
		$context  = 'current' === $settings['source'] && 'product' === get_post_type( $context_product_id ) ? absint( $context_product_id ) : 0;

		return $this->render_view( $post->ID, $settings, $context, true );
	}

	/**
	 * Render one complete shortcode instance.
	 *
	 * @param int   $id Shortcode ID.
	 * @param array $settings Settings.
	 * @param int   $context_product_id Current or preview product.
	 * @param bool  $preview Whether this is an admin preview.
	 * @return string
	 */
	public function render_view( $id, $settings, $context_product_id = 0, $preview = false ) {
		$settings    = $this->apply_layout_rules( $settings );
		$product_ids = $this->resolve_product_ids( $settings, $context_product_id );
		$instance_id = 'sktpr-shortcode-' . ( $id ? $id : 'preview' ) . '-' . wp_rand( 1000, 999999 );

		if ( false === $product_ids ) {
			return sprintf(
				'<div class="sktpr-shortcode-empty">%s</div>',
				$preview ? esc_html__( 'Choose a product to preview its reviews.', 'product-reviews' ) : esc_html__( 'No reviews found.', 'product-reviews' )
			);
		}

		$query   = $this->query_reviews( $settings, $product_ids, 1, 'all', false );
		$reviews = $query->comments;
		$total   = intval( $query->found_comments );
		$sample  = false;

		if ( $preview && empty( $reviews ) ) {
			$sample  = true;
			$reviews = $this->get_sample_reviews();
			$total   = count( $reviews );
		}

		$stats = $this->get_stats( $settings, $product_ids );
		if ( $sample ) {
			$stats = array(
				'total'   => 2,
				'rated'   => 2,
				'average' => 4.5,
				'ratings' => array(
					1 => 0,
					2 => 0,
					3 => 0,
					4 => 1,
					5 => 1,
				),
				'media'   => 0,
			);
		}
		$style = sprintf(
			'--sktpr-sc-bg:%1$s;--sktpr-sc-text:%2$s;--sktpr-sc-accent:%3$s;--sktpr-sc-star:%4$s;--sktpr-sc-accent-foreground:%5$s;--sktpr-sc-gap:%6$dpx;--sktpr-sc-radius:%7$dpx;--sktpr-sc-columns:%8$d;--sktpr-sc-tablet-columns:%9$d;--sktpr-sc-mobile-columns:%10$d;',
			esc_attr( $settings['background_color'] ),
			esc_attr( $settings['text_color'] ),
			esc_attr( $settings['accent_color'] ),
			esc_attr( $settings['star_color'] ),
			esc_attr( $this->get_contrasting_text_color( $settings['accent_color'] ) ),
			absint( $settings['gap'] ),
			absint( $settings['radius'] ),
			absint( $settings['columns'] ),
			absint( $settings['tablet_columns'] ),
			absint( $settings['mobile_columns'] )
		);

		$is_slider = 'slider' === $settings['layout'];
		if ( $is_slider ) {
			plugin()->scripts->enqueue_slider_assets();
		}

		// Inline (page-builder) displays have no saved ID, so they carry their
		// signed settings for the Load More and filter requests.
		$inline_attributes = '';
		if ( ! $id ) {
			$payload           = wp_json_encode( $settings );
			$inline_attributes = sprintf(
				' data-sktpr-settings="%1$s" data-sktpr-signature="%2$s"',
				esc_attr( $payload ),
				esc_attr( $this->sign_settings( $payload ) )
			);
		}

		$html = sprintf(
			'<section id="%1$s" class="sktpr-shortcode sktpr-shortcode--%2$s%3$s" style="%4$s" data-shortcode-id="%5$d" data-context-product="%6$d" data-page="1"%7$s%8$s>',
			esc_attr( $instance_id ),
			esc_attr( $settings['layout'] ),
			$preview ? ' is-preview' : '',
			$style,
			absint( $id ),
			absint( $context_product_id ),
			$is_slider ? $this->get_slider_attributes( $settings, ! $preview && $total > $settings['per_page'] ) : '',
			$inline_attributes
		);

		if ( $sample ) {
			$html .= '<div class="sktpr-shortcode-sample">' . ( $this->is_media_layout( $settings ) 
				? esc_html__( 'Sample preview — no approved reviews with photos or videos were found.', 'product-reviews' )
				: esc_html__( 'Sample preview — no approved reviews were found.', 'product-reviews' ) ) . '</div>';
		}

		if ( $settings['show_summary'] ) {
			$html .= $this->render_summary( $stats );
		}

		if ( $settings['show_filters'] && $total > 1 && ! $sample ) {
			$html .= $this->render_filters( $stats, $this->is_media_layout( $settings ) );
		}

		if ( $is_slider ) {
			$html .= sprintf( '<div class="sktpr-shortcode-slider-frame%s">', $settings['slider_arrows'] ? ' has-arrows' : '' );
			$html .= '<div class="sktpr-shortcode-slider">';
		}

		// Spotlight: the featured first card spans the rows of the cards beside it
		// (public.js keeps this in sync after filtering and Load More).
		$is_reels   = 'reels' === $settings['layout'];
		$is_marquee = 'marquee' === $settings['layout'];
		if ( $is_reels ) {
			$html .= '<div class="sktpr-shortcode-reels-frame">';
		}
		if ( $is_marquee ) {
			$html .= '<div class="sktpr-shortcode-marquee">';
		}

		$html .= sprintf(
			'<div class="sktpr-shortcode-reviews" aria-live="polite"%s>',
			'spotlight' === $settings['layout'] ? sprintf( ' style="--sktpr-sc-spotlight-rows:%d"', max( 1, count( $reviews ) - 1 ) ) : ''
		);
		if ( $reviews ) {
			foreach ( $reviews as $review ) {
				$html .= $this->render_card( $review, $settings, $sample );
			}
		} else {
			$html .= '<p class="sktpr-shortcode-no-results">' . esc_html__( 'No reviews found.', 'product-reviews' ) . '</p>';
		}
		$html .= '</div>';

		if ( $is_marquee ) {
			$html .= '</div>';
		}

		if ( $is_reels ) {
			$arrow = '<button type="button" class="sktpr-shortcode-reels-arrow sktpr-shortcode-reels-%1$s" data-sktpr-reels-dir="%2$d" aria-label="%3$s"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="%4$s" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
			$html .= sprintf( $arrow, 'prev', -1, esc_attr__( 'Previous reviews', 'product-reviews' ), 'M15 18l-6-6 6-6' );
			$html .= sprintf( $arrow, 'next', 1, esc_attr__( 'Next reviews', 'product-reviews' ), 'M9 18l6-6-6-6' );
			$html .= '</div>';
		}

		if ( $is_slider ) {
			$html .= '</div>' . $this->render_slider_arrows( $settings ) . '</div>';
			if ( $settings['slider_dots'] ) {
				$html .= '<div class="sktpr-shortcode-slider-dots"></div>';
			}
		}

		// The slider fetches further pages itself when it reaches its last slide;
		// the marquee loops its single page.
		if ( ! $preview && ! $is_slider && ! $is_marquee && $total > $settings['per_page'] ) {
			$html .= sprintf(
				'<button type="button" class="sktpr-shortcode-more" data-sktpr-load-more>%s</button>',
				esc_html__( 'Load More', 'product-reviews' )
			);
		}

		$html .= '</section>';
		return $html;
	}

	/**
	 * Data attributes that configure the Swiper instance for a slider display.
	 *
	 * @param array $settings Display settings.
	 * @param bool  $has_more Whether further review pages can be loaded.
	 * @return string
	 */
	private function get_slider_attributes( $settings, $has_more ) {
		$config = array(
			'columns'       => absint( $settings['columns'] ),
			'tabletColumns' => absint( $settings['tablet_columns'] ),
			'mobileColumns' => absint( $settings['mobile_columns'] ),
			'gap'           => absint( $settings['gap'] ),
			'autoplay'      => (bool) $settings['slider_autoplay'],
			'delay'         => absint( $settings['slider_delay'] ) * 1000,
		);

		return sprintf(
			' data-sktpr-slider="%1$s" data-has-more="%2$d"',
			esc_attr( wp_json_encode( $config ) ),
			$has_more ? 1 : 0
		);
	}

	/**
	 * Previous/next arrows for a slider display, placed beside the track.
	 *
	 * @param array $settings Display settings.
	 * @return string
	 */
	private function render_slider_arrows( $settings ) {
		if ( ! $settings['slider_arrows'] ) {
			return '';
		}

		$arrow = '<button type="button" class="sktpr-shortcode-slider-arrow sktpr-shortcode-slider-%1$s" aria-label="%2$s"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="%3$s" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';

		return sprintf( $arrow, 'prev', esc_attr__( 'Previous reviews', 'product-reviews' ), 'M15 18l-6-6 6-6' ) .
			sprintf( $arrow, 'next', esc_attr__( 'Next reviews', 'product-reviews' ), 'M9 18l6-6-6-6' );
	}

	/**
	 * Choose the more readable theme foreground for a configurable accent.
	 *
	 * @param string $background Hex background color.
	 * @return string
	 */
	private function get_contrasting_text_color( $background ) {
		$background_luminance = $this->get_relative_luminance( $background );
		$ink_luminance        = $this->get_relative_luminance( '#17201e' );
		$ink_contrast         = ( max( $background_luminance, $ink_luminance ) + 0.05 ) / ( min( $background_luminance, $ink_luminance ) + 0.05 );
		$white_contrast       = 1.05 / ( $background_luminance + 0.05 );

		return $ink_contrast >= $white_contrast ? '#17201e' : '#ffffff';
	}

	/**
	 * Calculate WCAG relative luminance for a hex color.
	 *
	 * @param string $hex Hex color.
	 * @return float
	 */
	private function get_relative_luminance( $hex ) {
		if ( is_string( $hex ) && preg_match( '/^rgba?\((\d{1,3}),(\d{1,3}),(\d{1,3})/', str_replace( ' ', '', $hex ), $rgb ) ) {
			$values = array_map( 'absint', array_slice( $rgb, 1, 3 ) );
		} else {
			$sanitized = sanitize_hex_color( $hex );
			$hex       = ltrim( $sanitized ? $sanitized : '#ff694d', '#' );
			if ( 3 === strlen( $hex ) ) {
				$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
			}
			$values = array_map( 'hexdec', str_split( $hex, 2 ) );
		}

		$channels = array_map(
			function ( $channel ) {
				$value = min( 255, $channel ) / 255;
				return $value <= 0.04045 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
			},
			$values
		);

		return ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );
	}

	/**
	 * Resolve product constraints. An empty array means all products.
	 *
	 * @param array $settings Settings.
	 * @param int   $context_product_id Current product.
	 * @return array|false
	 */
	private function resolve_product_ids( $settings, $context_product_id ) {
		if ( 'current' === $settings['source'] ) {
			return $context_product_id ? array( absint( $context_product_id ) ) : false;
		}

		if ( 'selected' === $settings['source'] ) {
			return $settings['product_ids'] ? $settings['product_ids'] : false;
		}

		return array();
	}

	/**
	 * Query approved WooCommerce reviews.
	 *
	 * @param array      $settings Settings.
	 * @param array      $product_ids Product constraints.
	 * @param int        $page Page number.
	 * @param string|int $rating Rating filter.
	 * @param bool       $media Media filter.
	 * @return \WP_Comment_Query
	 */
	private function query_reviews( $settings, $product_ids, $page = 1, $rating = 'all', $media = false ) {
		$args = array(
			'status'        => 'approve',
			'type'          => 'review',
			'post_type'     => 'product',
			'number'        => $settings['per_page'],
			'offset'        => ( max( 1, $page ) - 1 ) * $settings['per_page'],
			'count'         => false,
			// found_comments (used for Load More / slider paging) is only populated when this is false.
			'no_found_rows' => false,
		);

		if ( $product_ids ) {
			$args['post__in'] = $product_ids;
		}

		if ( 'oldest' === $settings['order'] ) {
			$args['orderby'] = array(
				'comment_date_gmt' => 'ASC',
				'comment_ID'       => 'ASC',
			);
		} elseif ( in_array( $settings['order'], array( 'highest', 'lowest' ), true ) ) {
			$args['meta_key'] = 'rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required to sort reviews by rating.
			$args['orderby']  = array(
				'meta_value_num' => 'highest' === $settings['order'] ? 'DESC' : 'ASC',
				'comment_ID'     => 'DESC',
			);
		} else {
			$args['orderby'] = array(
				'comment_date_gmt' => 'DESC',
				'comment_ID'       => 'DESC',
			);
		}

		$meta_query = array( 'relation' => 'AND' );
		if ( 'all' !== (string) $rating && in_array( absint( $rating ), array( 1, 2, 3, 4, 5 ), true ) ) {
			$meta_query[] = array(
				'key'     => 'rating',
				'value'   => absint( $rating ),
				'compare' => '=',
			);
		}
		// The gallery layout is built from review media, so it only lists reviews that have some.
		if ( $media || $this->is_media_layout( $settings ) ) {
			$visible_media_query = array( 'relation' => 'OR' );
			if ( $settings['show_videos'] ) {
				$visible_media_query[] = array(
					'key'     => 'uploaded_video_url',
					'compare' => 'EXISTS',
				);
			}
			if ( $settings['show_photos'] ) {
				$visible_media_query[] = array(
					'key'     => 'uploaded_image_url',
					'compare' => 'EXISTS',
				);
			}
			if ( count( $visible_media_query ) > 1 ) {
				$meta_query[] = $visible_media_query;
			}
		}
		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return new \WP_Comment_Query( $args );
	}

	/**
	 * Aggregate rating and media statistics for the full source.
	 *
	 * @param array $settings Settings.
	 * @param array $product_ids Product constraints.
	 * @return array
	 */
	private function get_stats( $settings, $product_ids ) {
		$args = array(
			'status'    => 'approve',
			'type'      => 'review',
			'post_type' => 'product',
			'number'    => 0,
			'fields'    => 'ids',
		);
		if ( $product_ids ) {
			$args['post__in'] = $product_ids;
		}

		$ids    = get_comments( $args );
		$counts       = array_fill( 1, 5, 0 );
		$media_counts = array_fill( 1, 5, 0 );
		$total        = 0;
		$sum    = 0;
		$media  = 0;

		foreach ( $ids as $comment_id ) {
			$rating = absint( get_comment_meta( $comment_id, 'rating', true ) );
			if ( $rating >= 1 && $rating <= 5 ) {
				++$counts[ $rating ];
				$sum += $rating;
				++$total;
			}
			$has_visible_video = $settings['show_videos'] && get_comment_meta( $comment_id, 'uploaded_video_url', true );
			$has_visible_photo = $settings['show_photos'] && get_comment_meta( $comment_id, 'uploaded_image_url', true );
			if ( $has_visible_video || $has_visible_photo ) {
				++$media;
				if ( $rating >= 1 && $rating <= 5 ) {
					++$media_counts[ $rating ];
				}
			}
		}

		return array(
			'total'   => count( $ids ),
			'rated'   => $total,
			'average' => $total ? round( $sum / $total, 1 ) : 0,
			'ratings'       => $counts,
			'media'         => $media,
			'media_ratings' => $media_counts,
		);
	}

	/**
	 * Render aggregate summary.
	 *
	 * @param array $stats Statistics.
	 * @return string
	 */
	private function render_summary( $stats ) {
		if ( ! $stats['rated'] ) {
			return '';
		}

		$bars = '';
		for ( $stars = 5; $stars >= 1; $stars-- ) {
			$count   = intval( $stats['ratings'][ $stars ] );
			$percent = $stats['rated'] ? round( ( $count / $stats['rated'] ) * 100 ) : 0;
			$bars   .= sprintf(
				'<div class="sktpr-shortcode-rating-row"><span>%1$d★</span><span class="sktpr-shortcode-rating-track"><i style="width:%2$d%%"></i></span><span>%3$d</span></div>',
				$stars,
				$percent,
				$count
			);
		}

		return sprintf(
			'<div class="sktpr-shortcode-summary"><div><strong>%1$s</strong>%2$s<span>%3$s</span></div><div class="sktpr-shortcode-rating-bars" role="img" aria-label="%4$s">%5$s</div></div>',
			esc_html( number_format_i18n( $stats['average'], 1 ) ),
			wc_get_rating_html( number_format( (float) $stats['average'], 2, '.', '' ) ),
			// translators: %s: number of reviews.
			esc_html( sprintf( _n( '%s review', '%s reviews', $stats['total'], 'product-reviews' ), number_format_i18n( $stats['total'] ) ) ),
			esc_attr__( 'Rating distribution', 'product-reviews' ),
			$bars
		);
	}

	/**
	 * Whether a layout is built from review media (and so lists only reviews that have some).
	 *
	 * @param array $settings Settings.
	 * @return bool
	 */
	private function is_media_layout( $settings ) {
		return in_array( $settings['layout'], array( 'gallery', 'reels' ), true );
	}

	/**
	 * Adjust settings a layout depends on at render time, without changing what
	 * is stored: Reels shows video reviews only, so photos are left out of its
	 * tiles, query, filter counts and lightbox.
	 *
	 * @param array $settings Sanitized settings.
	 * @return array
	 */
	private function apply_layout_rules( $settings ) {
		if ( 'reels' === $settings['layout'] ) {
			$settings['show_photos'] = false;
			$settings['show_media']  = $settings['show_videos'];
		}

		return $settings;
	}

	/**
	 * Render visitor filters.
	 *
	 * The gallery layout only lists reviews with media, so its rating counts
	 * come from those reviews and the "With media" toggle is omitted.
	 *
	 * @param array $stats Statistics.
	 * @param bool  $media_only Whether the display only lists reviews with media.
	 * @return string
	 */
	private function render_filters( $stats, $media_only = false ) {
		$ratings = $media_only ? $stats['media_ratings'] : $stats['ratings'];
		$html  = '<div class="sktpr-shortcode-filters" role="group" aria-label="' . esc_attr__( 'Filter reviews', 'product-reviews' ) . '">';
		$html .= '<button type="button" class="is-active" data-sktpr-sc-rating="all">' . esc_html__( 'All', 'product-reviews' ) . '</button>';
		for ( $stars = 5; $stars >= 1; $stars-- ) {
			$html .= sprintf(
				'<button type="button" data-sktpr-sc-rating="%1$d" %2$s>%1$d★ <span>(%3$d)</span></button>',
				$stars,
				disabled( $ratings[ $stars ], 0, false ),
				absint( $ratings[ $stars ] )
			);
		}
		if ( $stats['media'] && ! $media_only ) {
			$html .= sprintf(
				'<button type="button" data-sktpr-sc-media aria-pressed="false">%1$s <span>(%2$d)</span></button>',
				esc_html__( 'With media', 'product-reviews' ),
				absint( $stats['media'] )
			);
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * Render one review card.
	 *
	 * @param \WP_Comment|array $review Review.
	 * @param array             $settings Settings.
	 * @param bool              $sample Sample data flag.
	 * @return string
	 */
	private function render_card( $review, $settings, $sample = false ) {
		if ( $sample ) {
			$author      = $review['author'];
			$content     = $review['content'];
			$date        = $review['date'];
			$rating      = $review['rating'];
			$product     = $review['product'];
			$product_url = '#';
			$avatar      = get_avatar( 0, 44, '', $author );
			$verified    = $review['verified'];
			$media_html  = '';
		} else {
			$author      = get_comment_author( $review );
			$content     = get_comment_text( $review );
			$date        = get_comment_date( get_option( 'date_format' ), $review );
			$rating      = absint( get_comment_meta( $review->comment_ID, 'rating', true ) );
			$product_obj = wc_get_product( $review->comment_post_ID );
			$product     = $product_obj ? $product_obj->get_name() : '';
			$product_url = $product_obj ? $product_obj->get_permalink() : '';
			$avatar      = get_avatar( $review, 44 );
			$verified    = function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $review->comment_ID );
			$media_html  = $settings['show_media'] ? $this->render_media( $review->comment_ID, $settings['show_photos'], $settings['show_videos'] ) : '';
		}

		if ( $this->is_media_layout( $settings ) ) {
			return $this->render_gallery_tile( compact( 'author', 'content', 'date', 'rating', 'product', 'product_url', 'avatar', 'verified', 'media_html' ), $review, $settings, $sample );
		}

		if ( 'accordion' === $settings['layout'] ) {
			$media_count = ( $sample || ! $settings['show_media'] ) ? 0 : $this->count_media( $review->comment_ID, $settings['show_photos'], $settings['show_videos'] );
			return $this->render_accordion_item( compact( 'author', 'content', 'date', 'rating', 'product', 'product_url', 'avatar', 'verified', 'media_html' ), $settings, $media_count );
		}

		if ( 'chat' === $settings['layout'] ) {
			return $this->render_chat_item( compact( 'author', 'content', 'date', 'rating', 'product', 'product_url', 'avatar', 'verified', 'media_html' ), $settings );
		}

		if ( 'compact' === $settings['layout'] ) {
			return $this->render_compact_row( compact( 'author', 'content', 'date', 'rating', 'product', 'product_url', 'avatar', 'verified', 'media_html' ), $settings );
		}

		if ( 'testimonial' === $settings['layout'] ) {
			return $this->render_testimonial_card( compact( 'author', 'content', 'date', 'rating', 'product', 'product_url', 'avatar', 'verified', 'media_html' ), $settings );
		}

		$html  = '<article class="sktpr-shortcode-card">';
		$html .= '<header class="sktpr-shortcode-card-head">';
		if ( $settings['show_avatar'] ) {
			$html .= '<span class="sktpr-shortcode-avatar">' . wp_kses_post( $avatar ) . '</span>';
		}
		$html .= '<div class="sktpr-shortcode-author"><strong>' . esc_html( $author ) . '</strong><span>' . esc_html( $date ) . '</span></div>';
		if ( $settings['show_verified'] && $verified ) {
			$html .= '<span class="sktpr-shortcode-verified"><span aria-hidden="true">✓</span> ' . esc_html__( 'Verified Customer', 'product-reviews' ) . '</span>';
		}
		$html .= '</header>';
		if ( $rating ) {
			// translators: %d: star rating out of 5.
			$html .= '<div class="sktpr-shortcode-stars" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'product-reviews' ), $rating ) ) . '">' . esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ) . '</div>';
		}
		if ( $settings['show_product'] && $product ) {
			$html .= $product_url ? '<a class="sktpr-shortcode-product" href="' . esc_url( $product_url ) . '">' . esc_html( $product ) . '</a>' : '<span class="sktpr-shortcode-product">' . esc_html( $product ) . '</span>';
		}
		if ( $settings['show_review_text'] ) {
			$html .= '<div class="sktpr-shortcode-content">' . wpautop( wp_kses_post( $content ) ) . '</div>';
		}
		$html .= $media_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_media().
		$html .= '</article>';

		// Timeline: the card hangs off the line beside a date marker.
		if ( 'timeline' === $settings['layout'] ) {
			$html = sprintf(
				'<div class="sktpr-shortcode-timeline-item"><time class="sktpr-shortcode-timeline-date"%1$s>%2$s</time>%3$s</div>',
				$sample ? '' : ' datetime="' . esc_attr( get_comment_date( 'c', $review ) ) . '"',
				esc_html( $date ),
				$html
			);
		}

		return $html;
	}

	/**
	 * Render one gallery tile: the review's first photo or video fills the tile
	 * (the rest open from it in the lightbox) with the rating, an excerpt and the
	 * author overlaid. Reviews without visible media render as a text tile.
	 *
	 * @param array             $fields Prepared review fields from render_card().
	 * @param \WP_Comment|array $review Review.
	 * @param array             $settings Settings.
	 * @param bool              $sample Sample data flag.
	 * @return string
	 */
	private function render_gallery_tile( $fields, $review, $settings, $sample ) {
		$media_count = ( $sample || ! $settings['show_media'] ) ? 0 : $this->count_media( $review->comment_ID, $settings['show_photos'], $settings['show_videos'] );
		$has_media   = $media_count > 0 && '' !== $fields['media_html'];

		$html = sprintf( '<article class="sktpr-shortcode-card sktpr-shortcode-tile %s">', $has_media ? 'has-media' : 'is-text' );
		if ( $has_media ) {
			$html .= $fields['media_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_media().
			if ( $media_count > 1 ) {
				$html .= sprintf(
					'<span class="sktpr-shortcode-tile-count" title="%1$s"><span aria-hidden="true">+%2$d</span><span class="screen-reader-text">%1$s</span></span>',
					// translators: %d: number of photos and videos attached to the review.
					esc_attr( sprintf( _n( '%d media item', '%d media items', $media_count, 'product-reviews' ), $media_count ) ),
					absint( $media_count - 1 )
				);
			}
		}

		$html .= '<div class="sktpr-shortcode-tile-body">';
		if ( $fields['rating'] ) {
			// translators: %d: star rating out of 5.
			$html .= '<div class="sktpr-shortcode-stars" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'product-reviews' ), $fields['rating'] ) ) . '">' . esc_html( str_repeat( '★', $fields['rating'] ) . str_repeat( '☆', 5 - $fields['rating'] ) ) . '</div>';
		}
		if ( $settings['show_review_text'] ) {
			$excerpt = wp_trim_words( wp_strip_all_tags( $fields['content'] ), $has_media ? 18 : 45 );
			if ( '' !== $excerpt ) {
				$html .= '<p class="sktpr-shortcode-tile-text">' . esc_html( $excerpt ) . '</p>';
			}
		}
		$html .= '<div class="sktpr-shortcode-tile-author">';
		if ( $settings['show_avatar'] ) {
			$html .= '<span class="sktpr-shortcode-avatar">' . wp_kses_post( $fields['avatar'] ) . '</span>';
		}
		$html .= '<div class="sktpr-shortcode-author"><strong>' . esc_html( $fields['author'] );
		if ( $settings['show_verified'] && $fields['verified'] ) {
			$html .= ' <span class="sktpr-shortcode-tile-verified" title="' . esc_attr__( 'Verified Customer', 'product-reviews' ) . '"><span aria-hidden="true">✓</span><span class="screen-reader-text">' . esc_html__( 'Verified Customer', 'product-reviews' ) . '</span></span>';
		}
		$html .= '</strong>';
		if ( $settings['show_product'] && $fields['product'] ) {
			$html .= $fields['product_url'] ? '<a class="sktpr-shortcode-product" href="' . esc_url( $fields['product_url'] ) . '">' . esc_html( $fields['product'] ) . '</a>' : '<span class="sktpr-shortcode-product">' . esc_html( $fields['product'] ) . '</span>';
		} else {
			$html .= '<span>' . esc_html( $fields['date'] ) . '</span>';
		}
		$html .= '</div></div></div>';
		$html .= '</article>';
		return $html;
	}

	/**
	 * Render one testimonial card: a centered quote with the rating and any
	 * media, followed by the customer's avatar, name and product.
	 *
	 * @param array $fields Prepared review fields from render_card().
	 * @param array $settings Settings.
	 * @return string
	 */
	private function render_testimonial_card( $fields, $settings ) {
		$html = '<article class="sktpr-shortcode-card sktpr-shortcode-testimonial">';
		$html .= '<span class="sktpr-shortcode-testimonial-mark" aria-hidden="true">&#8220;</span>';
		if ( $fields['rating'] ) {
			// translators: %d: star rating out of 5.
			$html .= '<div class="sktpr-shortcode-stars" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'product-reviews' ), $fields['rating'] ) ) . '">' . esc_html( str_repeat( '★', $fields['rating'] ) . str_repeat( '☆', 5 - $fields['rating'] ) ) . '</div>';
		}
		if ( $settings['show_review_text'] ) {
			$html .= '<blockquote class="sktpr-shortcode-content">' . wpautop( wp_kses_post( $fields['content'] ) ) . '</blockquote>';
		}
		$html .= $fields['media_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_media().

		$html .= '<footer class="sktpr-shortcode-testimonial-author">';
		if ( $settings['show_avatar'] ) {
			$html .= '<span class="sktpr-shortcode-avatar">' . wp_kses_post( $fields['avatar'] ) . '</span>';
		}
		$html .= '<strong>' . esc_html( $fields['author'] ) . '</strong>';
		if ( $settings['show_verified'] && $fields['verified'] ) {
			$html .= '<span class="sktpr-shortcode-verified"><span aria-hidden="true">✓</span> ' . esc_html__( 'Verified Customer', 'product-reviews' ) . '</span>';
		}
		if ( $settings['show_product'] && $fields['product'] ) {
			$html .= $fields['product_url'] ? '<a class="sktpr-shortcode-product" href="' . esc_url( $fields['product_url'] ) . '">' . esc_html( $fields['product'] ) . '</a>' : '<span class="sktpr-shortcode-product">' . esc_html( $fields['product'] ) . '</span>';
		}
		$html .= '<span class="sktpr-shortcode-testimonial-date">' . esc_html( $fields['date'] ) . '</span>';
		$html .= '</footer>';
		$html .= '</article>';
		return $html;
	}

	/**
	 * Render one compact row: avatar, a single meta line (name, verified mark,
	 * stars, date), the product, a two-line excerpt and small media thumbnails.
	 *
	 * @param array $fields Prepared review fields from render_card().
	 * @param array $settings Settings.
	 * @return string
	 */
	private function render_compact_row( $fields, $settings ) {
		$html = '<article class="sktpr-shortcode-card sktpr-shortcode-row">';
		if ( $settings['show_avatar'] ) {
			$html .= '<span class="sktpr-shortcode-avatar">' . wp_kses_post( $fields['avatar'] ) . '</span>';
		}
		$html .= '<div class="sktpr-shortcode-row-main">';
		$html .= '<div class="sktpr-shortcode-row-meta"><strong>' . esc_html( $fields['author'] ) . '</strong>';
		if ( $settings['show_verified'] && $fields['verified'] ) {
			$html .= '<span class="sktpr-shortcode-row-verified" title="' . esc_attr__( 'Verified Customer', 'product-reviews' ) . '"><span aria-hidden="true">✓</span> ' . esc_html__( 'Verified', 'product-reviews' ) . '</span>';
		}
		if ( $fields['rating'] ) {
			// translators: %d: star rating out of 5.
			$html .= '<span class="sktpr-shortcode-stars" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'product-reviews' ), $fields['rating'] ) ) . '">' . esc_html( str_repeat( '★', $fields['rating'] ) . str_repeat( '☆', 5 - $fields['rating'] ) ) . '</span>';
		}
		$html .= '<span class="sktpr-shortcode-row-date">' . esc_html( $fields['date'] ) . '</span></div>';
		if ( $settings['show_product'] && $fields['product'] ) {
			$html .= $fields['product_url'] ? '<a class="sktpr-shortcode-product" href="' . esc_url( $fields['product_url'] ) . '">' . esc_html( $fields['product'] ) . '</a>' : '<span class="sktpr-shortcode-product">' . esc_html( $fields['product'] ) . '</span>';
		}
		if ( $settings['show_review_text'] ) {
			$excerpt = wp_strip_all_tags( $fields['content'] );
			if ( '' !== trim( $excerpt ) ) {
				$html .= '<p class="sktpr-shortcode-row-text" title="' . esc_attr( wp_trim_words( $excerpt, 80 ) ) . '">' . esc_html( $excerpt ) . '</p>';
			}
		}
		$html .= '</div>';
		$html .= $fields['media_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_media().
		$html .= '</article>';
		return $html;
	}

	/**
	 * Render one accordion item: a native <details> whose summary shows the
	 * customer, rating, date and a one-line excerpt, and whose body holds the
	 * product, full review and media. public.js keeps one item open at a time.
	 *
	 * @param array $fields Prepared review fields from render_card().
	 * @param array $settings Settings.
	 * @param int   $media_count Number of visible media items.
	 * @return string
	 */
	private function render_accordion_item( $fields, $settings, $media_count ) {
		$text = $settings['show_review_text'] ? wp_strip_all_tags( $fields['content'] ) : '';

		$html  = '<details class="sktpr-shortcode-card sktpr-shortcode-accordion-item">';
		$html .= '<summary>';
		if ( $settings['show_avatar'] ) {
			$html .= '<span class="sktpr-shortcode-avatar">' . wp_kses_post( $fields['avatar'] ) . '</span>';
		}
		$html .= '<span class="sktpr-shortcode-accordion-head">';
		$html .= '<span class="sktpr-shortcode-accordion-title"><strong>' . esc_html( $fields['author'] ) . '</strong>';
		if ( $settings['show_verified'] && $fields['verified'] ) {
			$html .= '<span class="sktpr-shortcode-row-verified"><span aria-hidden="true">✓</span> ' . esc_html__( 'Verified', 'product-reviews' ) . '</span>';
		}
		if ( $fields['rating'] ) {
			// translators: %d: star rating out of 5.
			$html .= '<span class="sktpr-shortcode-stars" role="img" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'product-reviews' ), $fields['rating'] ) ) . '">' . esc_html( str_repeat( '★', $fields['rating'] ) . str_repeat( '☆', 5 - $fields['rating'] ) ) . '</span>';
		}
		$html .= '</span>';
		if ( '' !== trim( $text ) ) {
			$html .= '<span class="sktpr-shortcode-accordion-excerpt">' . esc_html( wp_trim_words( $text, 30 ) ) . '</span>';
		}
		$html .= '</span>';
		$html .= '<span class="sktpr-shortcode-accordion-meta">';
		if ( $media_count ) {
			$html .= '<span class="sktpr-shortcode-accordion-media">' . esc_html(
				// translators: %d: number of photos and videos attached to the review.
				sprintf( _n( '%d media', '%d media', $media_count, 'product-reviews' ), $media_count )
			) . '</span>';
		}
		$html .= '<span class="sktpr-shortcode-row-date">' . esc_html( $fields['date'] ) . '</span>';
		$html .= '</span>';
		$html .= '<span class="sktpr-shortcode-accordion-chevron" aria-hidden="true"></span>';
		$html .= '</summary>';

		$html .= '<div class="sktpr-shortcode-accordion-body">';
		if ( $settings['show_product'] && $fields['product'] ) {
			$html .= $fields['product_url'] ? '<a class="sktpr-shortcode-product" href="' . esc_url( $fields['product_url'] ) . '">' . esc_html( $fields['product'] ) . '</a>' : '<span class="sktpr-shortcode-product">' . esc_html( $fields['product'] ) . '</span>';
		}
		if ( $settings['show_review_text'] ) {
			$html .= '<div class="sktpr-shortcode-content">' . wpautop( wp_kses_post( $fields['content'] ) ) . '</div>';
		}
		$html .= $fields['media_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_media().
		$html .= '</div>';
		$html .= '</details>';
		return $html;
	}

	/**
	 * Render one chat item: the avatar beside a speech bubble holding the name,
	 * rating, review, media and a product/date footer. Sides alternate in CSS.
	 *
	 * @param array $fields Prepared review fields from render_card().
	 * @param array $settings Settings.
	 * @return string
	 */
	private function render_chat_item( $fields, $settings ) {
		$html = '<article class="sktpr-shortcode-card sktpr-shortcode-chat">';
		if ( $settings['show_avatar'] ) {
			$html .= '<span class="sktpr-shortcode-avatar">' . wp_kses_post( $fields['avatar'] ) . '</span>';
		}
		$html .= '<div class="sktpr-shortcode-chat-bubble">';
		$html .= '<div class="sktpr-shortcode-chat-head"><strong>' . esc_html( $fields['author'] ) . '</strong>';
		if ( $settings['show_verified'] && $fields['verified'] ) {
			$html .= '<span class="sktpr-shortcode-row-verified"><span aria-hidden="true">✓</span> ' . esc_html__( 'Verified', 'product-reviews' ) . '</span>';
		}
		if ( $fields['rating'] ) {
			// translators: %d: star rating out of 5.
			$html .= '<span class="sktpr-shortcode-stars" role="img" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'product-reviews' ), $fields['rating'] ) ) . '">' . esc_html( str_repeat( '★', $fields['rating'] ) . str_repeat( '☆', 5 - $fields['rating'] ) ) . '</span>';
		}
		$html .= '</div>';
		if ( $settings['show_review_text'] ) {
			$html .= '<div class="sktpr-shortcode-content">' . wpautop( wp_kses_post( $fields['content'] ) ) . '</div>';
		}
		$html .= $fields['media_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_media().
		$html .= '<div class="sktpr-shortcode-chat-foot">';
		if ( $settings['show_product'] && $fields['product'] ) {
			$html .= $fields['product_url'] ? '<a class="sktpr-shortcode-product" href="' . esc_url( $fields['product_url'] ) . '">' . esc_html( $fields['product'] ) . '</a>' : '<span class="sktpr-shortcode-product">' . esc_html( $fields['product'] ) . '</span>';
		}
		$html .= '<span class="sktpr-shortcode-row-date">' . esc_html( $fields['date'] ) . '</span>';
		$html .= '</div></div>';
		$html .= '</article>';
		return $html;
	}

	/**
	 * Count the visible photos and videos attached to a review.
	 *
	 * @param int  $comment_id Comment ID.
	 * @param bool $show_photos Whether uploaded photos are shown.
	 * @param bool $show_videos Whether uploaded videos are shown.
	 * @return int
	 */
	private function count_media( $comment_id, $show_photos = true, $show_videos = true ) {
		$videos = $show_videos ? array_filter( flatten_array( get_comment_meta( $comment_id, 'uploaded_video_url' ) ) ) : array();
		$images = $show_photos ? array_filter( flatten_array( get_comment_meta( $comment_id, 'uploaded_image_url' ) ) ) : array();

		return count( $videos ) + count( $images );
	}

	/**
	 * Render review media compatible with the existing lightbox.
	 *
	 * @param int  $comment_id Comment ID.
	 * @param bool $show_photos Whether to include uploaded photos.
	 * @param bool $show_videos Whether to include uploaded videos.
	 * @return string
	 */
	private function render_media( $comment_id, $show_photos = true, $show_videos = true ) {
		$videos = $show_videos ? flatten_array( get_comment_meta( $comment_id, 'uploaded_video_url' ) ) : array();
		$images = $show_photos ? flatten_array( get_comment_meta( $comment_id, 'uploaded_image_url' ) ) : array();
		$items  = '';

		foreach ( $videos as $url ) {
			if ( $url ) {
				$items .= sprintf(
					'<button type="button" class="sktpr-media-thumb sktpr-media-thumb--video" data-sktpr-type="video" data-sktpr-src="%1$s" aria-label="%2$s"><video preload="metadata" muted playsinline tabindex="-1"><source src="%1$s#t=0.1"></video><span class="sktpr-media-play" aria-hidden="true"></span></button>',
					esc_url( $url ),
					esc_attr__( 'Play review video', 'product-reviews' )
				);
			}
		}
		foreach ( $images as $url ) {
			if ( $url ) {
				$items .= sprintf(
					'<button type="button" class="sktpr-media-thumb sktpr-media-thumb--image" data-sktpr-type="image" data-sktpr-src="%1$s" aria-label="%2$s"><img src="%1$s" alt="%2$s" loading="lazy"></button>',
					esc_url( $url ),
					esc_attr__( 'View review photo', 'product-reviews' )
				);
			}
		}

		return $items ? '<div class="sktpr-review-media">' . $items . '</div>' : '';
	}

	/**
	 * Sample review records for an empty admin preview.
	 *
	 * @return array
	 */
	private function get_sample_reviews() {
		return array(
			array(
				'author'   => __( 'Alex Morgan', 'product-reviews' ),
				'content'  => __( 'Excellent quality and exactly as described. Delivery was quick and the product feels built to last.', 'product-reviews' ),
				'date'     => date_i18n( get_option( 'date_format' ) ),
				'rating'   => 5,
				'product'  => __( 'Sample Product', 'product-reviews' ),
				'verified' => true,
			),
			array(
				'author'   => __( 'Jamie Lee', 'product-reviews' ),
				'content'  => __( 'Very happy with this purchase. Setup was simple and everything worked as expected.', 'product-reviews' ),
				'date'     => wp_date( get_option( 'date_format' ), time() - DAY_IN_SECONDS * 4 ),
				'rating'   => 4,
				'product'  => __( 'Sample Product', 'product-reviews' ),
				'verified' => true,
			),
		);
	}

	/**
	 * Verify an administrative builder request.
	 */
	private function verify_admin_request() {
		check_admin_referer( 'sktpr_plugin_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to manage review shortcodes.', 'product-reviews' ) ), 403 );
		}
	}

	/**
	 * Return all saved shortcodes.
	 */
	public function ajax_list() {
		$this->verify_admin_request();
		wp_send_json_success(
			array(
				'items'    => $this->get_saved_items(),
				'defaults' => $this->apply_plan_limits( $this->get_defaults() ),
			)
		);
	}

	/**
	 * Return saved review displays for admin and editor selectors.
	 *
	 * @return array
	 */
	public function get_saved_items() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		$items = array();
		foreach ( $posts as $post ) {
			$settings = $this->apply_plan_limits( $this->sanitize_settings( get_post_meta( $post->ID, self::META_KEY, true ) ) );
			$items[]  = array(
				'id'        => $post->ID,
				'name'      => $post->post_title,
				'shortcode' => sprintf( '[%s id="%d"]', self::SHORTCODE, $post->ID ),
				'source'    => $settings['source'],
				'layout'    => $settings['layout'],
				'modified'  => get_the_modified_date( get_option( 'date_format' ), $post ),
				'settings'  => $settings,
			);
		}

		return $items;
	}

	/**
	 * Save a shortcode.
	 */
	public function ajax_save() {
		$this->verify_admin_request();
		$id       = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability are verified in verify_admin_request() above.
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability are verified in verify_admin_request() above.
		$raw_json = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in verify_admin_request(); the raw JSON is decoded and sanitized below.
		$decoded  = json_decode( $raw_json, true );
		$settings = $this->sanitize_settings( $decoded );

		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => __( 'Enter a name for this shortcode.', 'product-reviews' ) ), 400 );
		}
		if ( 'selected' === $settings['source'] && empty( $settings['product_ids'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Select at least one product.', 'product-reviews' ) ), 400 );
		}
		if ( $id && ( self::POST_TYPE !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'The shortcode could not be found.', 'product-reviews' ) ), 404 );
		}

		$post_id = wp_insert_post(
			array(
				'ID'          => $id,
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $name,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
		}

		$stored_settings    = $this->preserve_locked_settings( $settings, $id );
		$effective_settings = $this->apply_plan_limits( $stored_settings );

		update_post_meta( $post_id, self::META_KEY, $stored_settings );
		wp_send_json_success(
			array(
				'id'        => $post_id,
				'name'      => $name,
				'settings'  => $effective_settings,
				'shortcode' => sprintf( '[%s id="%d"]', self::SHORTCODE, $post_id ),
				'message'   => __( 'Review shortcode saved.', 'product-reviews' ),
			)
		);
	}

	/**
	 * Duplicate a saved shortcode.
	 */
	public function ajax_duplicate() {
		$this->verify_admin_request();
		$id   = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability are verified in verify_admin_request() above.
		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error( array( 'message' => __( 'The shortcode could not be found.', 'product-reviews' ) ), 404 );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				// translators: %s: title of the shortcode being duplicated.
				'post_title'  => sprintf( __( '%s Copy', 'product-reviews' ), $post->post_title ),
			),
			true
		);
		if ( is_wp_error( $new_id ) ) {
			wp_send_json_error( array( 'message' => $new_id->get_error_message() ), 500 );
		}
		update_post_meta( $new_id, self::META_KEY, $this->sanitize_settings( get_post_meta( $id, self::META_KEY, true ) ) );
		wp_send_json_success( array( 'message' => __( 'Shortcode duplicated.', 'product-reviews' ) ) );
	}

	/**
	 * Trash a saved shortcode.
	 */
	public function ajax_delete() {
		$this->verify_admin_request();
		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability are verified in verify_admin_request() above.
		if ( ! $id || self::POST_TYPE !== get_post_type( $id ) || ! wp_trash_post( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'The shortcode could not be deleted.', 'product-reviews' ) ), 400 );
		}
		wp_send_json_success( array( 'message' => __( 'Shortcode moved to the trash.', 'product-reviews' ) ) );
	}

	/**
	 * Trash multiple saved shortcodes.
	 */
	public function ajax_bulk_delete() {
		$this->verify_admin_request();
		$raw_ids = isset( $_POST['ids'] ) && is_string( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : '[]'; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in verify_admin_request(); the raw JSON is decoded and sanitized below.
		$ids     = json_decode( $raw_ids, true );
		$ids     = is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Select at least one shortcode to delete.', 'product-reviews' ) ), 400 );
		}

		foreach ( $ids as $id ) {
			if ( self::POST_TYPE !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
				wp_send_json_error( array( 'message' => __( 'One or more selected shortcodes could not be found.', 'product-reviews' ) ), 404 );
			}
		}

		foreach ( $ids as $id ) {
			if ( ! wp_trash_post( $id ) ) {
				wp_send_json_error( array( 'message' => __( 'One or more shortcodes could not be deleted.', 'product-reviews' ) ), 400 );
			}
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of deleted shortcodes. */
					_n( '%d shortcode moved to the trash.', '%d shortcodes moved to the trash.', count( $ids ), 'product-reviews' ),
					count( $ids )
				),
			)
		);
	}

	/**
	 * Product autocomplete endpoint.
	 */
	public function ajax_products() {
		$this->verify_admin_request();
		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce and capability are verified in verify_admin_request() above.
		$ids    = isset( $_GET['ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['ids'] ) ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce and capability are verified in verify_admin_request() above.
		$args   = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'post_parent'    => 0,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		);
		if ( $ids ) {
			$args['post__in'] = $ids;
			$args['orderby']  = 'post__in';
		} elseif ( $search ) {
			$args['s'] = $search;
		}

		$items = array();
		foreach ( get_posts( $args ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$items[] = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
				);
			}
		}
		wp_send_json_success( array( 'items' => $items ) );
	}

	/**
	 * Live preview endpoint.
	 */
	public function ajax_preview() {
		$this->verify_admin_request();
		$decoded  = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in verify_admin_request(); the raw JSON is decoded and sanitized below.
		$settings = $this->apply_plan_limits( $this->sanitize_settings( $decoded ) );
		$product  = isset( $_POST['preview_product_id'] ) ? absint( wp_unslash( $_POST['preview_product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability are verified in verify_admin_request() above.
		if ( $product ) {
			$settings['source'] = 'current';
		}
		wp_send_json_success( array( 'html' => $this->render_view( 0, $settings, $product, true ) ) );
	}

	/**
	 * Public Load More and filter endpoint.
	 */
	public function ajax_more() {
		$id      = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public read-only pagination endpoint; every value is cast to an integer or boolean.
		$page    = isset( $_POST['page'] ) ? max( 1, absint( wp_unslash( $_POST['page'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public read-only pagination endpoint; every value is cast to an integer or boolean.
		$context = isset( $_POST['context_product_id'] ) ? absint( wp_unslash( $_POST['context_product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public read-only pagination endpoint; every value is cast to an integer or boolean.
		$rating  = isset( $_POST['rating'] ) && 'all' !== $_POST['rating'] ? absint( wp_unslash( $_POST['rating'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public read-only pagination endpoint; every value is cast to an integer or boolean.
		$media   = ! empty( $_POST['media'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public read-only pagination endpoint; every value is cast to an integer or boolean.

		if ( 'all' !== $rating && ! in_array( $rating, array( 1, 2, 3, 4, 5 ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid review filter.', 'product-reviews' ) ), 400 );
		}

		if ( $id ) {
			$post = get_post( $id );
			if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
				wp_send_json_error( array( 'message' => __( 'The shortcode could not be found.', 'product-reviews' ) ), 404 );
			}
			$stored = get_post_meta( $id, self::META_KEY, true );
		} else {
			// Inline page-builder display: the settings travel with the request and
			// are only trusted when their signature matches.
			$payload   = isset( $_POST['settings'] ) && is_string( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public read-only endpoint; the payload is HMAC-verified, decoded and sanitized below.
			$signature = isset( $_POST['signature'] ) ? sanitize_text_field( wp_unslash( $_POST['signature'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public read-only endpoint; the signature is compared in constant time.
			if ( '' === $payload || '' === $signature || ! hash_equals( $this->sign_settings( $payload ), $signature ) ) {
				wp_send_json_error( array( 'message' => __( 'The shortcode could not be found.', 'product-reviews' ) ), 404 );
			}
			$stored = json_decode( $payload, true );
		}

		$settings    = $this->apply_layout_rules( $this->apply_plan_limits( $this->sanitize_settings( $stored ) ) );
		$product_ids = $this->resolve_product_ids( $settings, $context );
		if ( false === $product_ids ) {
			wp_send_json_success(
				array(
					'html'     => '',
					'has_more' => false,
					'page'     => 1,
				)
			);
		}

		$query = $this->query_reviews( $settings, $product_ids, $page, $rating, $media );
		$html  = '';
		foreach ( $query->comments as $review ) {
			$html .= $this->render_card( $review, $settings );
		}
		$shown = $page * $settings['per_page'];
		wp_send_json_success(
			array(
				'html'     => $html,
				'has_more' => $shown < intval( $query->found_comments ),
				'page'     => $page,
			)
		);
	}
}
