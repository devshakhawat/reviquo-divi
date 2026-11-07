<?php // phpcs:ignore
namespace SKTPREVIEW;

defined( 'ABSPATH' ) || exit;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

// Divi5_Integration only instantiates this module once the builder's
// dependency tree exists; the guard keeps the declaration conditional for
// static analysis too.
if ( ! interface_exists( '\\ET\\Builder\\Framework\\DependencyManagement\\Interfaces\\DependencyInterface' ) ) {
	return;
}

/**
 * Divi 5 module for a Product Reviews display: a saved display, or every
 * shortcode-builder option configured on the module ("custom" mode), with
 * Divi design options (fonts, card spacing/border/shadow, custom CSS) on top.
 *
 * The ET\Builder classes called here only exist when Divi 5 is installed, so
 * those calls are excluded from static analysis.
 */
class Divi5_Reviews_Module implements DependencyInterface {

	/**
	 * Module attributes holding Design-tab options for review elements
	 * (see divi5/reviews/module.json).
	 */
	const DESIGN_ELEMENTS = array( 'card', 'author', 'reviewText', 'productName', 'date', 'stars', 'summary', 'filters', 'loadMore' );

	/**
	 * Register the module on init.
	 *
	 * @return void
	 */
	public function load() {
		add_action( 'init', array( __CLASS__, 'register_module' ) );
	}

	/**
	 * Register shared module metadata and its frontend renderer.
	 *
	 * @return void
	 */
	public static function register_module() {
		// @phpstan-ignore-next-line
		ModuleRegistration::register_module(
			SKTPR_PLUGIN_DIR . 'divi5/reviews',
			array(
				'render_callback' => array( __CLASS__, 'render_callback' ),
			)
		);
	}

	/**
	 * Add module design-option styles.
	 *
	 * @param array $args Divi style arguments.
	 * @return void
	 */
	public static function module_styles( $args ) {
		$elements = $args['elements'];

		$styles = array(
			$elements->style(
				array(
					'attrName'   => 'module',
					'styleProps' => array(
						'disabledOn' => array(
							'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
						),
					),
				)
			),
		);

		foreach ( self::DESIGN_ELEMENTS as $attr_name ) {
			$styles[] = $elements->style( array( 'attrName' => $attr_name ) );
		}

		if ( class_exists( '\\ET\\Builder\\Packages\\Module\\Options\\Css\\CssStyle' ) && ! empty( $args['orderClass'] ) ) {
			$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'sktpr/divi5-reviews' );
			// @phpstan-ignore-next-line
			$styles[] = CssStyle::style(
				array(
					'selector'  => $args['orderClass'],
					'attr'      => $args['attrs']['css'] ?? array(),
					'cssFields' => $block_type && isset( $block_type->customCssFields ) ? $block_type->customCssFields : array(),
				)
			);
		}

		// @phpstan-ignore-next-line
		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => $styles,
			)
		);
	}

	/**
	 * Shortcode settings configured on the module (custom mode), keyed by
	 * shortcode setting name.
	 *
	 * @param array $attrs Module attributes.
	 * @return array
	 */
	private static function get_inline_settings( $attrs ) {
		$values   = array();
		$advanced = isset( $attrs['reviews']['advanced'] ) && is_array( $attrs['reviews']['advanced'] ) ? $attrs['reviews']['advanced'] : array();

		foreach ( $advanced as $sub_name => $attr ) {
			$value = $attr['desktop']['value'] ?? null;
			if ( 'mode' === $sub_name || null === $value || '' === $value || is_array( $value ) ) {
				continue;
			}

			// sliderAutoplay -> slider_autoplay.
			$key            = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', (string) $sub_name ) );
			$values[ $key ] = $value;
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
	 * Add Divi's standard element class names.
	 *
	 * @param array $args Divi classname arguments.
	 * @return void
	 */
	public static function module_classnames( $args ) {
		$args['classnamesInstance']->add(
			// @phpstan-ignore-next-line
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Add standard module script data.
	 *
	 * @param array $args Divi script-data arguments.
	 * @return void
	 */
	public static function module_script_data( $args ) {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Render the selected saved display on the frontend.
	 *
	 * @param array  $attrs    Saved module attributes.
	 * @param string $content  Parsed child content.
	 * @param object $block    Divi block object.
	 * @param object $elements Divi module elements.
	 * @return string
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		if ( 'custom' === ( $attrs['reviews']['advanced']['mode']['desktop']['value'] ?? 'saved' ) ) {
			$reviews = plugin()->review_shortcodes->render_inline( self::get_inline_settings( $attrs ) );
		} else {
			$display_id = absint( $attrs['display']['innerContent']['desktop']['value'] ?? 0 );
			if ( ! $display_id ) {
				return '';
			}
			$reviews = plugin()->review_shortcodes->render_shortcode( array( 'id' => $display_id ) );
		}

		if ( '' === $reviews ) {
			return '';
		}

		// @phpstan-ignore-next-line
		$module_inner    = HTMLUtility::render(
			array(
				'tag'               => 'div',
				'attributes'        => array( 'class' => 'sktpr-divi5-reviews__inner' ),
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $reviews,
			)
		);
		$module_children = $elements->style_components( array( 'attrName' => 'module' ) ) . $module_inner;

		// The parsed block comes from post content, so normalise the values
		// Divi needs to identify this module instance before they are output.
		$parsed         = is_array( $block->parsed_block ) ? $block->parsed_block : array();
		$order_index    = absint( $parsed['orderIndex'] ?? 0 );
		$store_instance = isset( $parsed['storeInstance'] ) && is_numeric( $parsed['storeInstance'] ) ? (int) $parsed['storeInstance'] : null;
		$block_id       = sanitize_text_field( (string) ( $parsed['id'] ?? '' ) );

		$block_type_name     = sanitize_text_field( (string) ( $block->block_type->name ?? '' ) );
		$block_type_category = sanitize_text_field( (string) ( $block->block_type->category ?? '' ) );

		$module_args = array(
			'orderIndex'          => $order_index,
			'storeInstance'       => $store_instance,
			'attrs'               => $attrs,
			'elements'            => $elements,
			'id'                  => $block_id,
			'moduleClassName'     => 'sktpr_divi5_reviews',
			'name'                => $block_type_name,
			'moduleCategory'      => $block_type_category,
			'classnamesFunction'  => array( __CLASS__, 'module_classnames' ),
			'stylesComponent'     => array( __CLASS__, 'module_styles' ),
			'scriptDataComponent' => array( __CLASS__, 'module_script_data' ),
			'children'            => $module_children,
		);

		// Divi's Module::render() escapes the attributes it prints itself; the
		// attributes this module reads on its own are cast to an integer
		// ($display_id) or sanitized by Review_Shortcodes::settings_from_builder().
		// @phpstan-ignore-next-line
		return Module::render( $module_args ); // nosemgrep: audit.php.wp.security.xss.block-attr
	}
}
