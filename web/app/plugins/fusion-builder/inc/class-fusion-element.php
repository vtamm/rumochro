<?php
/**
 * Builder Elements Class.
 *
 * @package fusion-builder
 * @since 1.1.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Builder Elements Class.
 *
 * @since 1.1.0
 */
abstract class Fusion_Element {

	/**
	 * FB options class object.
	 *
	 * @static
	 * @access protected
	 * @since 1.1.0
	 * @var object Fusion_Builder_Options
	 */
	protected static $fb_options;

	/**
	 * First add on or not.
	 *
	 * @static
	 * @access protected
	 * @since 1.1.0
	 * @var boolean
	 */
	protected static $first_addon = true;

	/**
	 * Dynamic CSS class object.
	 *
	 * @static
	 * @access protected
	 * @since 1.1.0
	 * @var bool
	 */
	protected static $dynamic_css_helpers;

	/**
	 * Options array.
	 * THis holds ALL OPTIONS from ALL ELEMENTS.
	 *
	 * @static
	 * @access protected
	 * @since 1.1.0
	 * @var array
	 */
	protected static $global_options = [];

	/**
	 * Element ID.
	 *
	 * @access protected
	 * @since 2.0
	 * @var string|int
	 */
	protected $element_id;

	/**
	 * An array of the shortcode defaults.
	 *
	 * @access protected
	 * @since 3.0
	 * @var array
	 */
	protected $defaults;

	/**
	 * Whether it has rendered already or not.
	 *
	 * @access protected
	 * @since 3.0
	 * @var array
	 */
	protected $has_rendered = false;

	/**
	 * The class constructor
	 *
	 * @access private
	 */
	public function __construct() {
		// Options class to add to.
		if ( ! self::$fb_options ) {
			self::$fb_options = Fusion_Builder_Options::get_instance();
		}

		// Check if class is in FB or FC.
		$is_core = ( false !== strpos( $this->get_dir(), wp_normalize_path( FUSION_BUILDER_PLUGIN_DIR ) ) || ( ( defined( 'FUSION_CORE_PATH' ) && false !== strpos( $this->get_dir(), wp_normalize_path( FUSION_CORE_PATH ) ) ) ) );
		if ( $is_core ) {
			$element_options = [
				'shortcode_styling' => [
					'fields' => $this->add_options(),
				],
			];
		} else {
			$fields = $this->add_options();
			foreach ( $fields as $field_id => $field ) {
				$fields[ $field_id ]['highlight'] = esc_attr__( '3rd Party Element', 'fusion-builder' );
			}
			if ( self::$first_addon ) {
				self::$first_addon = false;
				$element_options   = [
					'fusion_builder_addons' => [
						'label'    => esc_html__( 'Add-on Elements', 'fusion-builder' ),
						'id'       => 'fusion_builder_addons',
						'is_panel' => true,
						'priority' => 14,
						'icon'     => 'el-icon-cog',
						'fields'   => $fields,
					],
				];
			} else {
				$element_options = [
					'fusion_builder_addons' => [
						'fields' => $fields,
					],
				];
			}
		}
		self::$global_options = array_merge_recursive( self::$global_options, $element_options );
		self::$fb_options->add_options( $element_options );

		if ( ! is_admin() ) {
			add_action( 'wp_loaded', [ $this, 'load_css' ], 30 );
			add_action( 'wp_loaded', [ $this, 'add_css_files' ] );
		}

		// Dynamic JS script.
		$this->add_scripts();

		// Live editor, do on first render.
		if ( apply_filters( 'avada_force_enqueue', ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'live_editor_scripts' ] );
		}
	}

	/**
	 * Add CSS to dynamic CSS.
	 *
	 * @access protected
	 * @since 2.0
	 */
	public function load_css() {
		Fusion_Elements_Dynamic_CSS::add_styles_to_array( $this->add_styling() );
	}

	/**
	 * Add CSS to dynamic CSS.
	 *
	 * @access protected
	 * @since 3.0
	 */
	public function add_css_files() {
	}

	/**
	 * Adds settings to element options panel.
	 *
	 * @access protected
	 * @since 1.1
	 */
	protected function add_options() {
		return [];
	}

	/**
	 * Checks location of child class.
	 *
	 * @access protected
	 * @since 1.1
	 */
	protected function get_dir() {
		$rc = new ReflectionClass( get_class( $this ) );
		return wp_normalize_path( dirname( $rc->getFileName() ) );
	}

	/**
	 * Adds scripts to the dynamic JS.
	 *
	 * @access protected
	 * @since 1.1.0
	 */
	protected function add_scripts() {
	}

	/**
	 * Adds dynamic stying to dynamic CSS.
	 *
	 * @access protected
	 * @since 1.1
	 */
	protected function add_styling() {
		return [];
	}

	/**
	 * Fires on render.
	 *
	 * @access protected
	 * @since 3.2
	 */
	protected function on_render() {
		if ( ! $this->has_rendered ) {
			$this->on_first_render();
			$this->has_rendered = true;
		}
	}

	/**
	 * Fires on first render only.
	 *
	 * @access protected
	 * @since 3.2
	 */
	protected function on_first_render() {
	}

	/**
	 * Ensure scripts are loaded for live editor.
	 *
	 * @access public
	 * @since 3.2
	 */
	public function live_editor_scripts() {
		$this->on_first_render();
	}

	/**
	 * Sets the ID for the element.
	 *
	 * @access protected
	 * @param int $count Count of element or ID.
	 * @since 2.0
	 */
	protected function set_element_id( $count ) {
		$parent_id        = FusionBuilder()->get_global_shortcode_parent();
		$this->element_id = $parent_id ? $parent_id . '-' . $count : $count;
	}

	/**
	 * Gets the ID for the element.
	 *
	 * @access protected
	 * @since 2.0
	 * @return string
	 */
	protected function get_element_id() {
		return $this->element_id;
	}

	/**
	 * Returns the $global_options property.
	 *
	 * @static
	 * @access public
	 * @since 1.1.0
	 * @return array
	 */
	public static function get_all_options() {
		return self::$global_options;
	}

	/**
	 * Check if a param is default.
	 *
	 * @access public
	 * @since 3.0
	 * @param string                  $param Param name.
	 * @param args Array element args.
	 * @return bool
	 */
	public function is_default( $param, $args = false ) {
		$args = $args ? $args : $this->args;

		// No arg, means we are using default.
		if ( ! isset( $args[ $param ] ) ) {
			return true;
		}

		// If default is set and its the same, we are using default.
		if ( isset( $this->defaults[ $param ] ) && $this->defaults[ $param ] === $args[ $param ] ) {
			return true;
		}
		return false;
	}

	/**
	 * Add CSS property to overall array.
	 *
	 * @access protected
	 * @since 3.0
	 * @param mixed  $selectors CSS selector, array or string.
	 * @param string $property CSS property.
	 * @param string $value CSS value.
	 * @param bool   $important Whether it is important or not.
	 * @return void
	 */
	protected function add_css_property( $selectors = [], $property = '', $value = '', $important = false ) {
		if ( $important ) {
			$value .= ' !important';
		}
		if ( empty( $selectors ) ) {
			return;
		}
		if ( is_array( $selectors ) ) {
			foreach ( $selectors as $selector ) {
				if ( ! isset( $this->dynamic_css[ $selector ][ $property ] ) || $important || false === strpos( $this->dynamic_css[ $selector ][ $property ], '!important' ) ) {
						$this->dynamic_css[ $selector ][ $property ] = $value;
				}
			}
			return;
		}

		if ( ! isset( $this->dynamic_css[ $selectors ][ $property ] ) || $important || false === strpos( $this->dynamic_css[ $selectors ][ $property ], '!important' ) ) {
			$this->dynamic_css[ $selectors ][ $property ] = $value;
		}
	}

	/**
	 * Get a string with each of the option as a CSS variable, if the option is not default.
	 *
	 * @since 3.9
	 * @param array $options  The array with the options ids.
	 * @param array $args  The array with the element args if needed eg. tabs.
	 * @return string
	 */
	protected function get_css_vars_for_options( $options, $args = false ) {
		$css = '';

		$args = $args ? $args : $this->args;
		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) ) { // If the value is an array, then the CSS var name is the key.
				if ( ! $this->is_default( $key, $args ) && '' !== $args[ $key ] ) {
					$var_name      = '--awb-' . str_replace( '_', '-', $key );
					$callback_args = isset( $value['args'] ) && is_array( $value['args'] ) ? $value['args'] : [ $args[ $key ] ];
					$css          .= $var_name . ':' . call_user_func_array( $value['callback'], $callback_args ) . ';';
				}
			} else {
				if ( ! $this->is_default( $value, $args ) && '' !== $args[ $value ] ) {
					$var_name = '--awb-' . str_replace( '_', '-', $value );
					$css     .= $var_name . ':' . $args[ $value ] . ';';
				}
			}
		}

		return $css;
	}

	/**
	 * Get a string with custom CSS variables, created from array key => value pairs.
	 *
	 * @since 3.9
	 * @param array   $options The array with the custom css vars. The key
	 *   represents the option name, while the value represents the custom value.
	 * @param boolean $prefix Whether to alter the variable name or not.
	 * @return string
	 */
	protected function get_custom_css_vars( $options, $prefix = true ) {
		$css = '';

		foreach ( $options as $option_name => $value ) {
			$var_name = $prefix ? '--awb-' . str_replace( '_', '-', $option_name ) : '--' . $option_name;
			$css     .= $var_name . ':' . $value . ';';
		}

		return $css;
	}

	/**
	 * Get font styling vars, created from get_font_styling helper.
	 *
	 * @since 3.9
	 * @param string $key typography options key.
	 * @return string
	 */
	protected function get_font_styling_vars( $key, $args = false ) {
		$css         = '';
		$args        = $args ? $args : $this->args;
		$font_styles = Fusion_Builder_Element_Helper::get_font_styling( $args, $key, 'array' );

		foreach ( $font_styles as $name => $value ) {
			$key      = str_replace( '_font', '', $key );
			$var_name = '--awb-' . str_replace( '_', '-', $key . '-' . $name );
			$css     .= $var_name . ':' . $value . ';';
		}

		return $css;
	}

	/**
	 * Get declaration for typography vars with the given values.
	 *
	 * @since 3.9
	 * @param string $title_tag An HTML tag, Ex: 'h2', 'h3', 'div'.. etc.
	 * @param array  $name_value_map The key is a css property, the array value is the CSS value.
	 * @return string
	 */
	protected function get_heading_font_vars( $title_tag, $name_value_map ) {
		$var_prefix = '';
		$style      = '';

		if ( in_array( $title_tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], true ) ) {
			$var_prefix = '--' . $title_tag . '_typography-';
		} elseif ( 'div' === $title_tag || 'p' === $title_tag ) {
			$var_prefix = '--body_typography-';
		} else {
			return $style;
		}

		foreach ( $name_value_map as $css_prop => $css_value ) {
			if ( '' !== $css_value ) {
				$style .= $var_prefix . $css_prop . ':' . $css_value . ';';
			}
		}

		return $style;
	}

	/**
	 * Get aspect ratio vars.
	 *
	 * @since 3.9
	 * @return string
	 */
	protected function get_aspect_ratio_vars() {
		if ( '' === $this->args['aspect_ratio'] ) {
			return '';
		}

		$css = '';

		// Calc Ratio.
		if ( 'custom' === $this->args['aspect_ratio'] && '' !== $this->args['custom_aspect_ratio'] ) {
			$css .= '--awb-aspect-ratio: 100 / ' . $this->args['custom_aspect_ratio'] . ';';
		} else {
			$aspect_ratio = explode( '-', $this->args['aspect_ratio'] );
			$width        = isset( $aspect_ratio[0] ) ? $aspect_ratio[0] : '';
			$height       = isset( $aspect_ratio[1] ) ? $aspect_ratio[1] : '';

			$css .= '--awb-aspect-ratio:' . $width . ' / ' . $height . ';';

		}

		// Set Image Position.
		if ( '' !== $this->args['aspect_ratio_position'] ) {
			$css .= '--awb-object-position:' . $this->args['aspect_ratio_position'] . ';';
		}

		return $css;
	}

	/**
	 * Add CSS property to overall array.
	 *
	 * @access protected
	 * @since 3.0
	 * @return string
	 */
	protected function parse_css() {
		$css    = '';
		$result = [];

		foreach ( $this->dynamic_css as $selector => $values ) {
			$element     = '';
			$match_found = false;

			// Create CSS string.
			foreach ( $values as $property => $value ) {
				$element .= $property . ':' . $value . ';';
			}

			// Check if we already have CSS string.
			foreach ( $result as $result_selector => $result_values ) {
				if ( $result_values === $element ) {

					// Make sure :: selectors are listed separately because of browser compatibility.
					if ( false === strpos( $selector, ':focus-within' ) && false === strpos( $result_selector, ':focus-within' ) && false === strpos( $selector, '::' ) && false === strpos( $result_selector, '::' ) ) {
						// And remove the old one.
						unset( $result[ $result_selector ] );

						// It is the same, we create new entry with combined selector.
						$result[ $result_selector . ',' . $selector ] = $result_values;
					} else {
						// No match, add new.
						$result[ $selector ] = $element;
					}

					$match_found = true;
					break;
				}
			}

			if ( $match_found ) {
				continue;
			}

			// No match, add new.
			$result[ $selector ] = $element;
		}

		foreach ( $result as $selector => $result_css ) {
			$css .= $selector . '{' . $result_css . '}';
		}

		return $css;
	}

	/**
	 * Filter the post ID for use as dynamic data source.
	 *
	 * @access public
	 * @since 1.0
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function post_dynamic_data( $post_id ) {
		return get_the_ID();
	}
}
