<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 2.2.0
 */

if ( fusion_is_element_enabled( 'fusion_search' ) ) {

	if ( ! class_exists( 'FusionSC_Search' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 2.2.0
		 */
		class FusionSC_Search extends Fusion_Element {

			/**
			 * An array of the shortcode arguments.
			 *
			 * @access protected
			 * @since 2.2.0
			 * @var array
			 */
			protected $args;

			/**
			 * The internal container counter.
			 *
			 * @access private
			 * @since 3.0
			 * @var int
			 */
			private $counter = 1;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 2.2.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_search-element', [ $this, 'attr' ] );

				add_shortcode( 'fusion_search', [ $this, 'render' ] );

				if ( ! is_admin() ) {
					add_filter( 'pre_get_posts', [ $this, 'modify_search_filter' ] );
				}
			}

			/**
			 * Modifies the search filter.
			 *
			 * @access public
			 * @since 2.2.0
			 * @param object $query The search query.
			 * @return object $query The modified search query.
			 */
			public function modify_search_filter( $query ) {
				if ( is_search() && $query->is_search ) {

					if ( isset( $_GET ) && isset( $_GET['fs'] ) && isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$query->set( 'post_type', wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
					}
				}

				return $query;
			}

			/**
			 * Maps settings to param variables.
			 *
			 * @static
			 * @access public
			 * @since 2.2.0
			 * @return array
			 */
			public static function settings_to_params() {
				return [
					'search_form_design' => 'design',
				];
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.2.0
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = awb_get_fusion_settings();
				return [
					'animation_type'              => '',
					'animation_direction'         => 'down',
					'animation_speed'             => '',
					'animation_delay'             => '',
					'animation_offset'            => $fusion_settings->get( 'animation_offset' ),
					'animation_color'             => '',
					'class'                       => '',
					'search_content'              => '',
					'placeholder'                 => 'Search...',
					'design'                      => $fusion_settings->get( 'search_form_design' ),
					'live_search'                 => $fusion_settings->get( 'live_search' ) ? 'yes' : 'no',
					'search_limit_to_post_titles' => $fusion_settings->get( 'search_limit_to_post_titles' ) ? 'yes' : 'no',
					'input_height'                => '',
					'bg_color'                    => '',
					'text_size'                   => '',
					'text_color'                  => '',
					'border_width'                => false,
					'border_size_top'             => '',
					'border_size_right'           => '',
					'border_size_bottom'          => '',
					'border_size_left'            => '',
					'border_color'                => '',
					'focus_border_color'          => '',
					'border_radius'               => '',
					'hide_on_mobile'              => fusion_builder_default_visibility( 'string' ),
					'sticky_display'              => '',
					'id'                          => '',
					'margin_bottom'               => '',
					'margin_left'                 => '',
					'margin_right'                => '',
					'margin_top'                  => '',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 2.2.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				$this->defaults = self::get_element_defaults();
				$this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_search' );

				// Old value check.
				if ( $this->args['border_width'] ) {
					$this->args['border_size_top']    = '' !== $this->args['border_size_top'] ? $this->args['border_width'] : $this->args['border_size_top'];
					$this->args['border_size_right']  = '' !== $this->args['border_size_right'] ? $this->args['border_width'] : $this->args['border_size_right'];
					$this->args['border_size_bottom'] = '' !== $this->args['border_size_bottom'] ? $this->args['border_width'] : $this->args['border_size_bottom'];
					$this->args['border_size_left']   = '' !== $this->args['border_size_left'] ? $this->args['border_width'] : $this->args['border_size_left'];
				}

				$html  = '<div ' . FusionBuilder::attributes( 'search-element' ) . '>';
				$html .= $this->get_search_form();
				$html .= '</div>';

				$this->counter++;

				$this->on_render();

				return apply_filters( 'fusion_element_search_content', $html, $args );
			}

			/**
			 * Get the searchform
			 *
			 * @access public
			 * @since 2.1
			 * @return array
			 */
			public function get_search_form() {
				$extra_fields = '';

				if ( ! $this->args['search_content'] ) {
					$this->args['search_content'] = 'any';
				}

				$search_content = explode( ',', $this->args['search_content'] );
				$search_content = apply_filters( 'avada_search_results_post_types', $search_content );

				if ( $search_content ) {
					if ( 1 === count( $search_content ) && 'product' === $search_content[0] ) {
						$extra_fields .= '<input type="hidden" name="post_type" value="' . $search_content[0] . '" />';
					} else {
						foreach ( $search_content as $value ) {
							$extra_fields .= '<input type="hidden" name="post_type[]" value="' . $value . '" />';
						}
					}
				}

				$extra_fields .= '<input type="hidden" name="search_limit_to_post_titles" value="' . ( 'yes' === $this->args['search_limit_to_post_titles'] ? '1' : '0' ) . '" />';

				// Activate the search filter.
				$extra_fields .= '<input type="hidden" name="fs" value="1" />';

				$args = [
					'live_search'  => 'yes' === $this->args['live_search'] ? 1 : 0,
					'design'       => $this->args['design'],
					'after_fields' => $extra_fields,
				];

				if ( $this->args['placeholder'] ) {
					$args['placeholder'] = $this->args['placeholder'];
				}

				ob_start();
				Fusion_Searchform::get_form( $args );
				$form = ob_get_clean();

				return apply_filters( 'get_search_form', $form, $args );
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 2.2.0
			 * @return array
			 */
			public function attr() {

				$css_vars = [
					'margin_top'         => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_right'       => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_bottom'      => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_left'        => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'input_height'       => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius'      => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_size_top'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_size_right'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_size_bottom' => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_size_left'   => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'text_color',
					'border_color',
					'focus_border_color',
					'text_size',
					'bg_color',
				];

				$attr = [
					'class' => 'fusion-search-element fusion-search-element-' . $this->counter,
					'style' => $this->get_css_vars_for_options( $css_vars ),
				];

				// Visibility.
				$attr = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );

				$attr['class'] .= Fusion_Builder_Sticky_Visibility_Helper::get_sticky_class( $this->args['sticky_display'] );

				// Animation class.
				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['design'] ) {
					$attr['class'] .= ' fusion-search-form-' . $this->args['design'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.0
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/search.min.css' );
			}
		}
	}

	new FusionSC_Search();

}

/**
 * Map shortcode to Avada Builder.
 *
 * @since 2.2.0
 */
function fusion_element_search() {
	$fusion_settings = awb_get_fusion_settings();

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_Search',
			[
				'name'       => esc_attr__( 'Search', 'fusion-builder' ),
				'shortcode'  => 'fusion_search',
				'icon'       => 'fusiona-search',
				'preview'    => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-search-preview.php',
				'preview_id' => 'fusion-builder-block-module-search-preview-template',
				'help_url'   => 'https://theme-fusion.com/documentation/avada/elements/search-element/',
				'params'     => [
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Enable Live Search', 'fusion-builder' ),
						'description' => esc_attr__( 'Turn on to enable live search results on menu search field and other fitting search forms.', 'fusion-builder' ),
						'param_name'  => 'live_search',
						'default'     => '',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'multiple_select',
						'heading'     => esc_attr__( 'Search Results Content', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the type of content that displays in search results. Leave empty for all.', 'fusion-builder' ),
						'param_name'  => 'search_content',
						'default'     => '',
						'choices'     => [
							'post'            => esc_attr__( 'Posts', 'fusion-builder' ),
							'page'            => esc_attr__( 'Pages', 'fusion-builder' ),
							'avada_portfolio' => esc_attr__( 'Portfolio Items', 'fusion-builder' ),
							'avada_faq'       => esc_attr__( 'FAQ Items', 'fusion-builder' ),
							'product'         => esc_attr__( 'WooCommerce Products', 'fusion-builder' ),
							'tribe_events'    => esc_attr__( 'Events Calendar Posts', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Limit Search to Post Titles', 'fusion-builder' ),
						'description' => esc_attr__( 'Turn on to limit the search to post titles only.', 'fusion-builder' ),
						'param_name'  => 'search_limit_to_post_titles',
						'default'     => '',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Placeholder', 'fusion-builder' ),
						'description' => esc_attr__( 'Search placeholder', 'fusion-builder' ),
						'param_name'  => 'placeholder',
						'value'       => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Search Form Design', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the design of the search form.', 'fusion-builder' ),
						'param_name'  => 'design',
						'default'     => '',
						'value'       => [
							''        => esc_attr__( 'Default', 'fusion-builder' ),
							'classic' => esc_attr__( 'Classic', 'fusion-builder' ),
							'clean'   => esc_attr__( 'Clean', 'fusion-builder' ),
						],
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Height', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the height of form input field. Enter value including CSS unit (px, em, rem), ex: 50px.', 'fusion-builder' ),
						'param_name'  => 'input_height',
						'value'       => '',
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Field Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of search field.', 'fusion-builder' ),
						'param_name'  => 'bg_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_bg_color' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Field Font Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the size of the search field text. Enter value including any valid CSS unit, ex: 16px.', 'fusion-builder' ),
						'param_name'  => 'text_size',
						'value'       => '',
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Field Text Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the search text in field.', 'fusion-builder' ),
						'param_name'  => 'text_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_text_color' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Field Border Size', 'fusion-builder' ),
						'description'      => esc_attr__( 'Controls the border size of the search field.', 'fusion-builder' ),
						'param_name'       => 'border_size',
						'group'            => esc_html__( 'Design', 'fusion-builder' ),
						'value'            => [
							'border_size_top'    => '',
							'border_size_right'  => '',
							'border_size_bottom' => '',
							'border_size_left'   => '',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Field Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the search field.', 'fusion-builder' ),
						'param_name'  => 'border_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_border_color' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Field Border Color On Focus', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the search input field when it is focused.', 'fusion-builder' ),
						'param_name'  => 'focus_border_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_focus_border_color' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Field Border Radius', 'fusion-builder' ),
						'param_name'  => 'border_radius',
						'description' => esc_attr__( 'Controls the border radius of the search input field. Also works, if border size is set to 0. In pixels.', 'fusion-builder' ),
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_border_radius' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
					],
					'fusion_margin_placeholder'            => [
						'param_name' => 'margin',
						'value'      => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
					],
					'fusion_animation_placeholder'         => [
						'preview_selector' => '.fusion-search-element',
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					'fusion_sticky_visibility_placeholder' => [],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_search' );
