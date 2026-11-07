<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

// Divi4_Integration only requires this file once the builder is loaded; the
// guard keeps the class declaration conditional for static analysis too.
if ( ! class_exists( '\\ET_Builder_Module' ) ) {
	return;
}

/**
 * Divi 4 (legacy builder) module for a Product Reviews display.
 *
 * The module either renders a saved display (ReviQuo > Review Shortcode) or,
 * in "custom" mode, every shortcode-builder option configured on the module
 * itself. Divi's own design options (fonts, card border and shadow, spacing,
 * custom CSS) layer on top in both modes.
 */
class Divi4_Reviews_Module extends \ET_Builder_Module {

	/**
	 * Layouts that always render a single column.
	 */
	const SINGLE_COLUMN_LAYOUTS = array( 'list', 'spotlight', 'compact', 'marquee', 'timeline', 'accordion', 'chat' );

	/**
	 * Review elements styled by the Design tab, across every layout's markup.
	 * Keep in sync with the selectors in divi5/reviews/module.json.
	 */
	const SELECTORS = array(
		'author'    => array( '.sktpr-shortcode-author strong', '.sktpr-shortcode-testimonial-author strong', '.sktpr-shortcode-row-meta strong', '.sktpr-shortcode-accordion-title strong', '.sktpr-shortcode-chat-head strong' ),
		'content'   => array( '.sktpr-shortcode-content', '.sktpr-shortcode-content p', '.sktpr-shortcode-tile-text', '.sktpr-shortcode-row-text', '.sktpr-shortcode-accordion-excerpt' ),
		'product'   => array( '.sktpr-shortcode-product' ),
		'date'      => array( '.sktpr-shortcode-author > span:not(.sktpr-shortcode-product)', '.sktpr-shortcode-row-date', '.sktpr-shortcode-testimonial-date', '.sktpr-shortcode-timeline-date' ),
		'stars'     => array( '.sktpr-shortcode-stars' ),
		'summary'   => array( '.sktpr-shortcode-summary strong' ),
		'filters'   => array( '.sktpr-shortcode-filters button' ),
		'load_more' => array( '.sktpr-shortcode-more' ),
	);

	/**
	 * Module slug, also the shortcode tag stored in post content.
	 *
	 * @var string
	 */
	public $slug = 'sktpr_divi4_reviews';

	/**
	 * Visual Builder support level.
	 *
	 * @var string
	 */
	public $vb_support = 'on';

	/**
	 * Module title shown in the builder (set in init()).
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Selector Divi prefixes generated CSS with (set in init()).
	 *
	 * @var string
	 */
	public $main_css_element;

	/**
	 * Settings-modal tab and toggle layout (set in init()).
	 *
	 * @var array<string, mixed>
	 */
	public $settings_modal_toggles;

	/**
	 * Divi design-tab option groups (set in init()).
	 *
	 * @var array<string, mixed>
	 */
	public $advanced_fields;

	/**
	 * Custom CSS targets (set in init()).
	 *
	 * @var array<string, mixed>
	 */
	public $custom_css_fields;

	/**
	 * Absolute path to the builder's module icon (set in init()).
	 *
	 * @var string
	 */
	public $icon_path;

	/**
	 * Configure module identity, settings-modal layout and design options.
	 *
	 * @return void
	 */
	public function init() {
		$this->name             = esc_html__( 'Product Reviews', 'product-reviews' );
		$this->main_css_element = '%%order_class%%';
		$this->icon_path        = SKTPR_PLUGIN_DIR . 'assets/img/divi-module-icon.svg';

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Review Display', 'product-reviews' ),
					'layout'       => esc_html__( 'Layout', 'product-reviews' ),
					'source'       => esc_html__( 'Reviews Source', 'product-reviews' ),
					'elements'     => esc_html__( 'Elements', 'product-reviews' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'colors'      => esc_html__( 'Review Colors', 'product-reviews' ),
					'card'        => esc_html__( 'Review Card', 'product-reviews' ),
					'author_text' => esc_html__( 'Author Text', 'product-reviews' ),
					'review_text' => esc_html__( 'Review Text', 'product-reviews' ),
					'product'     => esc_html__( 'Product Name', 'product-reviews' ),
					'date'        => esc_html__( 'Date Text', 'product-reviews' ),
					'stars'       => esc_html__( 'Star Rating', 'product-reviews' ),
					'summary'     => esc_html__( 'Rating Summary', 'product-reviews' ),
					'filters'     => esc_html__( 'Filter Buttons', 'product-reviews' ),
					'load_more'   => esc_html__( 'Load More Button', 'product-reviews' ),
				),
			),
		);

		$card = '%%order_class%% .sktpr-shortcode-card';

		$this->advanced_fields = array(
			'fonts'          => array(
				'author_text' => array(
					'label'           => esc_html__( 'Author', 'product-reviews' ),
					'css'             => array( 'main' => $this->selectors( self::SELECTORS['author'] ) ),
					'hide_text_align' => true,
					'toggle_slug'     => 'author_text',
				),
				'review_text' => array(
					'label'       => esc_html__( 'Review', 'product-reviews' ),
					'css'         => array( 'main' => $this->selectors( self::SELECTORS['content'] ) ),
					'toggle_slug' => 'review_text',
				),
				'product'     => array(
					'label'           => esc_html__( 'Product', 'product-reviews' ),
					'css'             => array( 'main' => $this->selectors( self::SELECTORS['product'] ) ),
					'hide_text_align' => true,
					'toggle_slug'     => 'product',
				),
				'date'        => array(
					'label'           => esc_html__( 'Date', 'product-reviews' ),
					'css'             => array( 'main' => $this->selectors( self::SELECTORS['date'] ) ),
					'hide_text_align' => true,
					'toggle_slug'     => 'date',
				),
				'stars'       => array(
					'label'            => esc_html__( 'Stars', 'product-reviews' ),
					'css'              => array( 'main' => $this->selectors( self::SELECTORS['stars'] ) ),
					'hide_font'        => true,
					'hide_text_align'  => true,
					'hide_line_height' => true,
					'toggle_slug'      => 'stars',
				),
				'summary'     => array(
					'label'           => esc_html__( 'Average Rating', 'product-reviews' ),
					'css'             => array( 'main' => $this->selectors( self::SELECTORS['summary'] ) ),
					'hide_text_align' => true,
					'toggle_slug'     => 'summary',
				),
				'filters'     => array(
					'label'           => esc_html__( 'Filter', 'product-reviews' ),
					'css'             => array( 'main' => $this->selectors( self::SELECTORS['filters'] ) ),
					'hide_text_align' => true,
					'toggle_slug'     => 'filters',
				),
				'load_more'   => array(
					'label'           => esc_html__( 'Load More', 'product-reviews' ),
					'css'             => array( 'main' => $this->selectors( self::SELECTORS['load_more'] ) ),
					'hide_text_align' => true,
					'toggle_slug'     => 'load_more',
				),
			),
			'borders'        => array(
				'default' => array(),
				'card'    => array(
					'css'          => array(
						'main' => array(
							'border_radii'  => $card,
							'border_styles' => $card,
						),
					),
					'label_prefix' => esc_html__( 'Card', 'product-reviews' ),
					'tab_slug'     => 'advanced',
					'toggle_slug'  => 'card',
				),
			),
			'box_shadow'     => array(
				'default' => array(),
				'card'    => array(
					'label'       => esc_html__( 'Card Box Shadow', 'product-reviews' ),
					'css'         => array( 'main' => $card ),
					'tab_slug'    => 'advanced',
					'toggle_slug' => 'card',
				),
			),
			'margin_padding' => array(
				'css' => array( 'important' => 'all' ),
			),
			'text'           => false,
			'button'         => false,
			'link_options'   => false,
		);

		$this->custom_css_fields = array(
			'review_card'    => array(
				'label'    => esc_html__( 'Review Card', 'product-reviews' ),
				'selector' => '.sktpr-shortcode-card',
			),
			'rating_summary' => array(
				'label'    => esc_html__( 'Rating Summary', 'product-reviews' ),
				'selector' => '.sktpr-shortcode-summary',
			),
			'filters'        => array(
				'label'    => esc_html__( 'Filter Buttons', 'product-reviews' ),
				'selector' => '.sktpr-shortcode-filters button',
			),
			'stars'          => array(
				'label'    => esc_html__( 'Star Rating', 'product-reviews' ),
				'selector' => '.sktpr-shortcode-stars',
			),
			'avatar'         => array(
				'label'    => esc_html__( 'Avatar', 'product-reviews' ),
				'selector' => '.sktpr-shortcode-avatar img',
			),
			'load_more'      => array(
				'label'    => esc_html__( 'Load More Button', 'product-reviews' ),
				'selector' => '.sktpr-shortcode-more',
			),
		);
	}

	/**
	 * Prefix each selector in a list with the module order class.
	 *
	 * @param string[] $selectors Element selectors.
	 * @return string
	 */
	private function selectors( $selectors ) {
		return implode(
			', ',
			array_map(
				function ( $selector ) {
					return '%%order_class%% ' . $selector;
				},
				$selectors
			)
		);
	}

	/**
	 * Settings fields: the saved-display picker plus every shortcode-builder
	 * option (shown in "custom" mode).
	 *
	 * @return array
	 */
	public function get_fields() {
		$defaults = plugin()->review_shortcodes->get_defaults();
		$custom   = array( 'settings_mode' => 'custom' );
		$on_off   = array(
			'on'  => esc_html__( 'Yes', 'product-reviews' ),
			'off' => esc_html__( 'No', 'product-reviews' ),
		);

		// Keyed '0', not '': the Divi 4 select pushes an empty key to the end
		// of the list and shows the first real option as selected instead.
		$saved = array( '0' => esc_html__( 'Choose a shortcode setting', 'product-reviews' ) );
		foreach ( plugin()->review_shortcodes->get_saved_items() as $item ) {
			$saved[ (string) absint( $item['id'] ) ] = sprintf( '%1$s (#%2$d)', $item['name'], absint( $item['id'] ) );
		}

		$fields = array(
			'settings_mode' => array(
				'label'           => esc_html__( 'Settings Source', 'product-reviews' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'saved'  => esc_html__( 'Saved shortcode setting', 'product-reviews' ),
					'custom' => esc_html__( 'Customize in this module', 'product-reviews' ),
				),
				'default'         => 'saved',
				'description'     => esc_html__( 'Reuse a display saved under ReviQuo > Review Shortcode, or configure every option right here.', 'product-reviews' ),
				'toggle_slug'     => 'main_content',
			),
			'display_id'    => array(
				'label'           => esc_html__( 'Shortcode setting', 'product-reviews' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => $saved,
				'default'         => '0',
				'description'     => esc_html__( 'Select a saved display created under ReviQuo > Review Shortcode.', 'product-reviews' ),
				'toggle_slug'     => 'main_content',
				'show_if'         => array( 'settings_mode' => 'saved' ),
			),

			// Layout.
			'layout'          => array(
				'label'           => esc_html__( 'Layout', 'product-reviews' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => array(
					'list'        => esc_html__( 'List', 'product-reviews' ),
					'grid'        => esc_html__( 'Grid', 'product-reviews' ),
					'masonry'     => esc_html__( 'Masonry', 'product-reviews' ),
					'slider'      => esc_html__( 'Slider', 'product-reviews' ),
					'gallery'     => esc_html__( 'Media Gallery', 'product-reviews' ),
					'testimonial' => esc_html__( 'Testimonial', 'product-reviews' ),
					'spotlight'   => esc_html__( 'Spotlight', 'product-reviews' ),
					'compact'     => esc_html__( 'Compact List', 'product-reviews' ),
					'reels'       => esc_html__( 'Reels', 'product-reviews' ),
					'marquee'     => esc_html__( 'Marquee', 'product-reviews' ),
					'timeline'    => esc_html__( 'Timeline', 'product-reviews' ),
					'accordion'   => esc_html__( 'Accordion', 'product-reviews' ),
					'chat'        => esc_html__( 'Chat Bubbles', 'product-reviews' ),
				),
				'default'         => $defaults['layout'],
				'description'     => esc_html__( 'Media Gallery lists reviews with photos or videos; Reels lists video reviews only.', 'product-reviews' ),
				'toggle_slug'     => 'layout',
				'show_if'         => $custom,
			),
			'slider_autoplay' => $this->toggle( esc_html__( 'Autoplay', 'product-reviews' ), $defaults['slider_autoplay'], 'layout', $on_off, array_merge( $custom, array( 'layout' => 'slider' ) ) ),
			'slider_delay'    => $this->range( esc_html__( 'Autoplay Delay (s)', 'product-reviews' ), $defaults['slider_delay'], 1, 20, 'layout', array_merge( $custom, array( 'layout' => 'slider', 'slider_autoplay' => 'on' ) ) ),
			'slider_arrows'   => $this->toggle( esc_html__( 'Arrows', 'product-reviews' ), $defaults['slider_arrows'], 'layout', $on_off, array_merge( $custom, array( 'layout' => 'slider' ) ) ),
			'slider_dots'     => $this->toggle( esc_html__( 'Dots', 'product-reviews' ), $defaults['slider_dots'], 'layout', $on_off, array_merge( $custom, array( 'layout' => 'slider' ) ) ),
		);

		foreach (
			array(
				'columns'        => esc_html__( 'Desktop Columns', 'product-reviews' ),
				'tablet_columns' => esc_html__( 'Tablet Columns', 'product-reviews' ),
				'mobile_columns' => esc_html__( 'Mobile Columns', 'product-reviews' ),
			) as $key => $label
		) {
			$fields[ $key ]                = $this->range( $label, $defaults[ $key ], 1, 4, 'layout', $custom );
			$fields[ $key ]['show_if_not'] = array( 'layout' => self::SINGLE_COLUMN_LAYOUTS );
		}

		$fields['per_page'] = $this->range( esc_html__( 'Reviews Per Load', 'product-reviews' ), $defaults['per_page'], 1, 24, 'layout', $custom );
		$fields['order']    = array(
			'label'           => esc_html__( 'Order', 'product-reviews' ),
			'type'            => 'select',
			'option_category' => 'configuration',
			'options'         => array(
				'newest'  => esc_html__( 'Newest first', 'product-reviews' ),
				'oldest'  => esc_html__( 'Oldest first', 'product-reviews' ),
				'highest' => esc_html__( 'Highest rated', 'product-reviews' ),
				'lowest'  => esc_html__( 'Lowest rated', 'product-reviews' ),
			),
			'default'         => $defaults['order'],
			'toggle_slug'     => 'layout',
			'show_if'         => $custom,
		);

		// Source.
		$fields['source']      = array(
			'label'           => esc_html__( 'Review Source', 'product-reviews' ),
			'type'            => 'select',
			'option_category' => 'configuration',
			'options'         => array(
				'current'  => esc_html__( 'Current product', 'product-reviews' ),
				'selected' => esc_html__( 'Selected products', 'product-reviews' ),
				'all'      => esc_html__( 'All products', 'product-reviews' ),
			),
			'default'         => $defaults['source'],
			'description'     => esc_html__( 'Current product shows reviews of the product page the module is placed on.', 'product-reviews' ),
			'toggle_slug'     => 'source',
			'show_if'         => $custom,
		);
		$fields['product_ids'] = array(
			'label'           => esc_html__( 'Product IDs', 'product-reviews' ),
			'type'            => 'text',
			'option_category' => 'configuration',
			'default'         => '',
			'description'     => esc_html__( 'Comma-separated IDs of the products whose reviews to show, e.g. 12, 34.', 'product-reviews' ),
			'toggle_slug'     => 'source',
			'show_if'         => array_merge( $custom, array( 'source' => 'selected' ) ),
		);

		// Elements.
		$verified_label = esc_html__( 'Verified Badge', 'product-reviews' );
		if ( ! Features::has_pro_license() ) {
			$verified_label .= ' ' . esc_html__( '(Pro)', 'product-reviews' );
		}
		foreach (
			array(
				'show_summary'     => esc_html__( 'Rating Summary', 'product-reviews' ),
				'show_filters'     => esc_html__( 'Review Filters', 'product-reviews' ),
				'show_product'     => esc_html__( 'Product Name', 'product-reviews' ),
				'show_review_text' => esc_html__( 'Review Text', 'product-reviews' ),
				'show_avatar'      => esc_html__( 'Customer Avatar', 'product-reviews' ),
				'show_verified'    => $verified_label,
				'show_photos'      => esc_html__( 'Photos', 'product-reviews' ),
				'show_videos'      => esc_html__( 'Videos', 'product-reviews' ),
			) as $key => $label
		) {
			$fields[ $key ] = $this->toggle( $label, $defaults[ $key ], 'elements', $on_off, $custom );
		}

		// Design: colors and card geometry.
		foreach (
			array(
				'background_color' => esc_html__( 'Card Background', 'product-reviews' ),
				'text_color'       => esc_html__( 'Text Color', 'product-reviews' ),
				'accent_color'     => esc_html__( 'Accent Color', 'product-reviews' ),
				'star_color'       => esc_html__( 'Star Color', 'product-reviews' ),
			) as $key => $label
		) {
			$fields[ $key ] = array(
				'label'           => $label,
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'option_category' => 'basic_option',
				'default'         => $defaults[ $key ],
				'tab_slug'        => 'advanced',
				'toggle_slug'     => 'colors',
				'show_if'         => $custom,
			);
		}
		$fields['gap']    = array_merge( $this->range( esc_html__( 'Card Gap', 'product-reviews' ), $defaults['gap'], 0, 48, 'card', $custom, 'px' ), array( 'tab_slug' => 'advanced' ) );
		$fields['radius'] = array_merge( $this->range( esc_html__( 'Card Radius', 'product-reviews' ), $defaults['radius'], 0, 32, 'card', $custom, 'px' ), array( 'tab_slug' => 'advanced' ) );

		$fields['card_padding'] = array(
			'label'           => esc_html__( 'Card Padding', 'product-reviews' ),
			'type'            => 'custom_padding',
			'option_category' => 'layout',
			'mobile_options'  => true,
			'tab_slug'        => 'advanced',
			'toggle_slug'     => 'card',
		);

		return $fields;
	}

	/**
	 * An on/off field.
	 *
	 * @param string $label       Label.
	 * @param bool   $default     Default state.
	 * @param string $toggle_slug Settings toggle.
	 * @param array  $options     On/off labels.
	 * @param array  $show_if     Visibility conditions.
	 * @return array
	 */
	private function toggle( $label, $default, $toggle_slug, $options, $show_if ) {
		return array(
			'label'           => $label,
			'type'            => 'yes_no_button',
			'option_category' => 'configuration',
			'options'         => $options,
			'default'         => $default ? 'on' : 'off',
			'toggle_slug'     => $toggle_slug,
			'show_if'         => $show_if,
		);
	}

	/**
	 * A numeric range field.
	 *
	 * @param string $label       Label.
	 * @param int    $default     Default value.
	 * @param int    $min         Minimum.
	 * @param int    $max         Maximum.
	 * @param string $toggle_slug Settings toggle.
	 * @param array  $show_if     Visibility conditions.
	 * @param string $unit        CSS unit, '' for a plain number.
	 * @return array
	 */
	private function range( $label, $default, $min, $max, $toggle_slug, $show_if, $unit = '' ) {
		$field = array(
			'label'           => $label,
			'type'            => 'range',
			'option_category' => 'layout',
			'range_settings'  => array(
				'min'       => $min,
				'max'       => $max,
				'step'      => 1,
				'min_limit' => $min,
				'max_limit' => $max,
			),
			'default'         => $default . $unit,
			'toggle_slug'     => $toggle_slug,
			'show_if'         => $show_if,
		);

		if ( '' === $unit ) {
			$field['unitless']      = true;
			$field['validate_unit'] = false;
		} else {
			$field['default_unit']  = $unit;
			$field['allowed_units'] = array( $unit );
			$field['validate_unit'] = true;
		}

		return $field;
	}

	/**
	 * Shortcode settings configured on this module (custom mode).
	 *
	 * @return array
	 */
	private function get_inline_settings() {
		$values = array();
		foreach ( array_keys( plugin()->review_shortcodes->get_defaults() ) as $key ) {
			if ( isset( $this->props[ $key ] ) && '' !== $this->props[ $key ] ) {
				$values[ $key ] = $this->props[ $key ];
			}
		}

		// Range values may carry a unit ("20px"); the settings are plain integers.
		foreach ( array( 'per_page', 'columns', 'tablet_columns', 'mobile_columns', 'slider_delay', 'gap', 'radius' ) as $key ) {
			if ( isset( $values[ $key ] ) ) {
				$values[ $key ] = intval( $values[ $key ] );
			}
		}

		return $values;
	}

	/**
	 * Render the display on the frontend.
	 *
	 * @param array       $attrs       Saved module attributes.
	 * @param string|null $content     Parsed child content.
	 * @param string      $render_slug Render slug.
	 * @return string
	 */
	public function render( $attrs, $content = null, $render_slug = '' ) {
		if ( 'custom' === ( $this->props['settings_mode'] ?? 'saved' ) ) {
			$reviews = plugin()->review_shortcodes->render_inline( $this->get_inline_settings() );
		} else {
			$display_id = absint( $this->props['display_id'] ?? 0 );
			if ( ! $display_id ) {
				return '';
			}
			$reviews = plugin()->review_shortcodes->render_shortcode( array( 'id' => $display_id ) );
		}

		if ( '' === $reviews ) {
			return '';
		}

		$this->generate_card_padding_styles( $render_slug );

		return sprintf( '<div class="sktpr-divi4-reviews__inner">%s</div>', $reviews );
	}

	/**
	 * Output the responsive card padding.
	 *
	 * @param string $render_slug Render slug.
	 * @return void
	 */
	private function generate_card_padding_styles( $render_slug ) {
		if ( ! function_exists( 'et_pb_responsive_options' ) ) {
			return;
		}

		$selector = '%%order_class%% .sktpr-shortcode-card, %%order_class%% .sktpr-shortcode-chat-bubble';
		$values   = et_pb_responsive_options()->get_property_values( $this->props, 'card_padding' );

		foreach ( array( 'desktop', 'tablet', 'phone' ) as $device ) {
			$value = $values[ $device ] ?? '';
			if ( '' === $value || ! is_string( $value ) ) {
				continue;
			}

			$sides = array_pad( explode( '|', $value ), 4, '' );
			$rules = array();
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $index => $side ) {
				$side_value = trim( $sides[ $index ] );
				if ( '' !== $side_value && preg_match( '/^-?\d*\.?\d+(px|em|rem|%|vw|vh)?$/', $side_value ) ) {
					$rules[] = sprintf( 'padding-%1$s: %2$s', $side, $side_value );
				}
			}
			if ( ! $rules ) {
				continue;
			}

			$style = array(
				'selector'    => $selector,
				'declaration' => implode( '; ', $rules ) . ';',
			);
			if ( 'tablet' === $device ) {
				$style['media_query'] = \ET_Builder_Element::get_media_query( 'max_width_980' );
			} elseif ( 'phone' === $device ) {
				$style['media_query'] = \ET_Builder_Element::get_media_query( 'max_width_767' );
			}

			\ET_Builder_Element::set_style( $render_slug, $style );
		}
	}
}

new Divi4_Reviews_Module();
