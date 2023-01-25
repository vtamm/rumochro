<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_form' ) ) {

	if ( ! class_exists( 'FusionSC_FusionForm' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_FusionForm extends Fusion_Element {

			/**
			 * An array of the shortcode arguments.
			 *
			 * @access protected
			 * @since 1.0
			 * @var array
			 */
			protected $args;

			/**
			 * An array of rendered form's IDs.
			 *
			 * @access protected
			 * @since 3.7
			 * @var array
			 */
			protected $rendered_forms = [];

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 1.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_fusion-form-wrapper', [ $this, 'wrapper_attr' ] );
				add_shortcode( 'fusion_form', [ $this, 'render' ] );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 3.1
			 * @return array
			 */
			public static function get_element_defaults() {
				return [
					'form_post_id'   => '',
					'margin_bottom'  => '',
					'margin_left'    => '',
					'margin_right'   => '',
					'margin_top'     => '',
					'class'          => '',
					'hide_on_mobile' => fusion_builder_default_visibility( 'string' ),
					'id'             => '',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {

				// Early return if form_post_id is invalid.
				if ( ! isset( $args['form_post_id'] ) || '' === $args['form_post_id'] ) {

					// Editor user, display message.
					if ( current_user_can( 'publish_posts' ) ) {
						return apply_filters( 'fusion_element_form_content', '<div class="fusion-builder-placeholder">' . esc_html__( 'No form selected. Please select a form to display it here.', 'fusion-builder' ) . '</div>', $args );
					}

					// Non editor, display nothing.
					return apply_filters( 'fusion_element_form_content', '', $args );
				}

				// Set data.
				$this->params = Fusion_Builder_Form_Helper::fusion_form_set_form_data( $args['form_post_id'] );
				$this->args   = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_form' );
				$form_data    = Fusion_Builder_Form_Helper::fusion_get_form_post_content( $this->args['form_post_id'] );

				// No form found.
				if ( false === $form_data ) {

					// Editor user, display message.
					if ( current_user_can( 'publish_posts' ) ) {
						return apply_filters(
							'fusion_element_form_content',
							'<div class="fusion-builder-placeholder">' . esc_html__( 'This form no longer exists. It has been deleted or moved. Please create a new form to assign here.', 'fusion-builder' ) . '</div>',
							$this->args
						);
					}

					// Non editor, display nothing.
					return apply_filters( 'fusion_element_form_content', '', $args );
				}

				$this->params['is_upload'] = false !== strpos( $form_data['content'], 'fusion_form_upload' );

				// Make forms translateable.
				$this->args['form_post_id'] = apply_filters( 'wpml_object_id', $this->args['form_post_id'], 'fusion_form', true );

				$this->args['margin_bottom'] = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_bottom'], 'px' );
				$this->args['margin_left']   = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_left'], 'px' );
				$this->args['margin_right']  = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_right'], 'px' );
				$this->args['margin_top']    = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_top'], 'px' );

				// We have a valid form.
				$content                  = isset( $args['use_content'] ) ? $content : $form_data['content'];
				$this->args['custom_css'] = $form_data['css'];

				// Member only checks.
				if ( 'yes' === $this->params['form_meta']['member_only_form'] ) {
					if ( ! is_user_logged_in() ) {
						return apply_filters( 'fusion_element_form_content', '', $args );
					}

					$user_roles = [];
					if ( $this->params['form_meta']['user_roles'] ) {
						$user_roles = is_array( $this->params['form_meta']['user_roles'] )
							? $this->params['form_meta']['user_roles']
							: explode( ',', $this->params['form_meta']['user_roles'] );
					}
					if ( ! Fusion_Builder_Form_Helper::user_can_see_fusion_form( $user_roles ) ) {
						return apply_filters( 'fusion_element_form_content', '', $args );
					}
				}

				// Add Off Canvas to stack, so it's markup is added to the page.
				if ( class_exists( 'AWB_Off_Canvas' ) && false !== AWB_Off_Canvas::is_enabled() && in_array( 'off-canvas', $this->params['form_meta']['form_actions'], true ) && $this->params['form_meta']['off_canvas'] ) {
					AWB_Off_Canvas_Front_End::add_off_canvas_to_stack( $this->params['form_meta']['off_canvas'] );
				}

				$html = ! empty( $this->args['custom_css'] ) ? '<style>' . $this->args['custom_css'] . '</style>' : '';

				$form  = $this->open_form();
				$form .= do_shortcode( $content );
				$form .= '<input type="hidden" name="fusion_privacy_store_ip_ua" value="' . ( 'yes' === $this->params['form_meta']['privacy_store_ip_ua'] ? 'true' : 'false' ) . '">';
				$form .= '<input type="hidden" name="fusion_privacy_expiration_interval" value="' . absint( $this->params['form_meta']['privacy_expiration_interval'] ) . '">';
				$form .= '<input type="hidden" name="privacy_expiration_action" value="' . esc_attr( $this->params['form_meta']['privacy_expiration_action'] ) . '">';

				if ( isset( $this->params['form_meta']['nonce_method'] ) && 'localized' === $this->params['form_meta']['nonce_method'] ) {
					$form .= wp_nonce_field( 'fusion_form_nonce', 'fusion-form-nonce-' . absint( $this->args['form_post_id'] ), false, false );
				}

				$form .= $this->close_form();

				$html .= '<div ' . FusionBuilder::attributes( 'fusion-form-wrapper' ) . '>';
				$html .= $form;
				$html .= '</div>';

				$this->on_render();

				return apply_filters( 'fusion_element_form_content', $html, $args );
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

				if ( ! in_array( (int) $this->args['form_post_id'], $this->rendered_forms, true ) && isset( $this->params['form_meta']['nonce_method'] ) && ( 'none' === $this->params['form_meta']['nonce_method'] || 'localized' === $this->params['form_meta']['nonce_method'] ) ) {
					Fusion_Form_Builder()->increase_view_count( $this->args['form_post_id'] );
					$this->rendered_forms[] = (int) $this->args['form_post_id'];
				}
			}

			/**
			 * Check if a param is default.
			 *
			 * @access public
			 * @since 3.0
			 * @param string $param Param name.
			 * @param mixed  $subset Subset name.
			 * @return string
			 */
			public function is_default_form_meta( $param, $subset = false ) {

				// If we have a subset value.
				if ( $subset ) {
					if ( isset( $this->params['form_meta'][ $param ] ) && isset( $this->params['form_meta'][ $param ][ $subset ] ) && '' !== $this->params['form_meta'][ $param ][ $subset ] ) {
						return false;
					} elseif ( ! isset( $this->params['form_meta'][ $param ][ $subset ] ) || '' === $this->params['form_meta'][ $param ][ $subset ] ) {
						return true;
					}
				}

				// No arg, means we are using default.
				if ( ! isset( $this->params['form_meta'][ $param ] ) || '' === $this->params['form_meta'][ $param ] ) {
					return true;
				}

				return false;
			}

			/**
			 * Get the style variables.
			 *
			 * @access protected
			 * @since 3.9
			 * @return string
			 */
			public function get_style_variables() {
				$custom_vars     = [];
				$fusion_settings = awb_get_fusion_settings();

				$custom_vars['tooltip_text_color']       = fusion_library()->sanitize->color( $this->params['form_meta']['tooltip_text_color'] );
				$custom_vars['tooltip_background_color'] = fusion_library()->sanitize->color( $this->params['form_meta']['tooltip_background_color'] );

				if ( isset( $this->params['form_meta']['field_margin']['top'] ) && '' !== $this->params['form_meta']['field_margin']['top'] ) {
					$custom_vars['field_margin_top'] = fusion_library()->sanitize->get_value_with_unit( $this->params['form_meta']['field_margin']['top'] );
				}

				if ( isset( $this->params['form_meta']['field_margin']['bottom'] ) && '' !== $this->params['form_meta']['field_margin']['bottom'] ) {
					$custom_vars['field_margin_bottom'] = fusion_library()->sanitize->get_value_with_unit( $this->params['form_meta']['field_margin']['bottom'] );
				}

				if ( ! $this->is_default_form_meta( 'form_input_height' ) ) {
					$custom_vars['form_input_height'] = fusion_library()->sanitize->get_value_with_unit( $this->params['form_meta']['form_input_height'] );
				}

				if ( ! $this->is_default_form_meta( 'form_bg_color' ) ) {
					$custom_vars['form_bg_color'] = fusion_library()->sanitize->color( $this->params['form_meta']['form_bg_color'] );
					$bg_color                     = Fusion_Color::new_color( $custom_vars['form_bg_color'] );

					// Special case, transparent input background, calculate likely best background for text.
					if ( 0 === $bg_color->alpha ) {
						if ( ! $this->is_default_form_meta( 'form_text_color' ) ) {
							$text_color = Fusion_Color::new_color( fusion_library()->sanitize->color( $this->params['form_meta']['form_label_color'] ) );
						} else {
							$text_color = Fusion_Color::new_color( $fusion_settings->get( 'form_text_color' ) );
						}
						if ( 50 > $text_color->lightness ) {
							$custom_vars['form_select_bg'] = 'var(--awb-color1)';
						} else {
							$custom_vars['form_select_bg'] = 'var(--awb-color8)';
						}
					}
				}

				if ( ! $this->is_default_form_meta( 'label_font_size' ) ) {
					$custom_vars['label_font_size'] = fusion_library()->sanitize->get_value_with_unit( $this->params['form_meta']['label_font_size'] );
				}

				if ( ! $this->is_default_form_meta( 'form_font_size' ) ) {
					$custom_vars['form_font_size'] = fusion_library()->sanitize->get_value_with_unit( $this->params['form_meta']['form_font_size'] );
				}

				if ( isset( $this->params['form_meta']['form_placeholder_color'] ) && '' !== $this->params['form_meta']['form_placeholder_color'] ) {
					$custom_vars['form_placeholder_color'] = fusion_library()->sanitize->color( $this->params['form_meta']['form_placeholder_color'] );
				} elseif ( ! $this->is_default_form_meta( 'form_text_color' ) ) {
					$custom_vars['form_placeholder_color'] = Fusion_Color::new_color( fusion_library()->sanitize->color( $this->params['form_meta']['form_text_color'] ) )->get_new( 'alpha', '0.5' )->to_css_var_or_rgba();
				}

				if ( ! $this->is_default_form_meta( 'form_text_color' ) ) {
					$custom_vars['form_text_color'] = fusion_library()->sanitize->color( $this->params['form_meta']['form_text_color'] );
				}

				if ( ! $this->is_default_form_meta( 'form_label_color' ) ) {
					$custom_vars['form_label_color'] = fusion_library()->sanitize->color( $this->params['form_meta']['form_label_color'] );
				}

				if ( ! $this->is_default_form_meta( 'form_border_width', 'top' ) ) {
					$custom_vars['form_border_width_top'] = FusionBuilder::validate_shortcode_attr_value( $this->params['form_meta']['form_border_width']['top'], 'px' );
				}

				if ( ! $this->is_default_form_meta( 'form_border_width', 'bottom' ) ) {
					$custom_vars['form_border_width_bottom'] = FusionBuilder::validate_shortcode_attr_value( $this->params['form_meta']['form_border_width']['bottom'], 'px' );
				}

				if ( ! $this->is_default_form_meta( 'form_border_width', 'right' ) ) {
					$custom_vars['form_border_width_right'] = FusionBuilder::validate_shortcode_attr_value( $this->params['form_meta']['form_border_width']['right'], 'px' );
				}

				if ( ! $this->is_default_form_meta( 'form_border_width', 'left' ) ) {
					$custom_vars['form_border_width_left'] = FusionBuilder::validate_shortcode_attr_value( $this->params['form_meta']['form_border_width']['left'], 'px' );
				}

				if ( ! $this->is_default_form_meta( 'form_border_color' ) ) {
					$custom_vars['form_border_color'] = fusion_library()->sanitize->color( $this->params['form_meta']['form_border_color'] );
				}

				if ( ! $this->is_default_form_meta( 'form_focus_border_color' ) ) {
					$custom_vars['form_focus_border_color']       = fusion_library()->sanitize->color( $this->params['form_meta']['form_focus_border_color'] );
					$custom_vars['form_focus_border_hover_color'] = Fusion_Color::new_color( fusion_library()->sanitize->color( $this->params['form_meta']['form_focus_border_color'] ) )->get_new( 'alpha', '0.5' )->to_css_var_or_rgba();
				}

				if ( ! $this->is_default_form_meta( 'form_border_radius' ) ) {
					$custom_vars['form_border_radius'] = FusionBuilder::validate_shortcode_attr_value( $this->params['form_meta']['form_border_radius'], 'px' );
				}

				// Vertical icon alignment.
				if ( ! $this->is_default_form_meta( 'form_border_width', 'bottom' ) || ! $this->is_default_form_meta( 'form_border_width', 'top' ) ) {
					$border_top    = $this->is_default_form_meta( 'form_border_width', 'top' ) ? $fusion_settings->get( 'form_border_width', 'top' ) : FusionBuilder::validate_shortcode_attr_value( $this->params['form_meta']['form_border_width']['top'], 'px' );
					$border_bottom = $this->is_default_form_meta( 'form_border_width', 'bottom' ) ? $fusion_settings->get( 'form_border_width', 'bottom' ) : FusionBuilder::validate_shortcode_attr_value( $this->params['form_meta']['form_border_width']['bottom'], 'px' );

					$custom_vars['icon_alignment_top']       = empty( $border_top ) ? '1px' : $border_top;
					$custom_vars['icon_alignment_bottom']    = empty( $border_bottom ) ? '1px' : $border_bottom;
					$custom_vars['icon_alignment_font_size'] = $this->is_default_form_meta( 'form_font_size' ) ? '1em' : $this->params['form_meta']['form_font_size'];
				}

				$css_vars_options = [
					'margin_top'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_right'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_bottom' => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_left'   => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
				];

				$styles = $this->get_css_vars_for_options( $css_vars_options ) . $this->get_custom_css_vars( $custom_vars );

				return $styles;
			}

			/**
			 * Renders the opening form tag.
			 *
			 * @since 3.1
			 * @access private
			 * @return string The form tag.
			 */
			private function open_form() {
				global $fusion_form;

				$data_attributes = '';
				$id              = '';
				$html            = '';
				$enctype         = '';
				$class           = 'fusion-form';

				if ( 'url' === $this->params['form_meta']['form_type'] ) {
					$class .= ' fusion-form-post';
				}

				if ( ! empty( $this->params['data_attributes'] ) ) {
					foreach ( $this->params['data_attributes'] as $key => $value ) {
						$data_attributes .= ' data-' . $key . '="' . $value . '"';
					}
				}

				if ( $this->params['is_upload'] ) {
					$enctype = ' enctype="multipart/form-data"';
				}

				$class .= ' fusion-form-' . $this->params['form_number'];

				$action = get_permalink();

				if ( 'post' === $this->params['form_meta']['form_type'] && ( isset( $this->params['form_meta']['post_method_url'] ) && '' !== $this->params['form_meta']['post_method_url'] ) ) {
					$action = $this->params['form_meta']['post_method_url'];
				}
				$html .= '<form action="' . $action . '" method="' . $this->params['form_meta']['method'] . '"' . $data_attributes . ' class="' . $class . '"' . $id . $enctype . '>';

				/**
				 * The fusion_form_after_open hook.
				 */
				ob_start();
				do_action( 'fusion_form_after_open', $this->args, $this->params );
				$html .= ob_get_clean();

				return $html;

			}

			/**
			 * Closes the form and adds an action.
			 *
			 * @since 3.1
			 * @access public
			 * @return string Form closing plus action output.
			 */
			private function close_form() {

				/**
				 * The fusion_form_before_close hook.
				 */
				ob_start();
				do_action( 'fusion_form_before_close', $this->args, $this->params );
				$html = ob_get_clean();

				$html .= '</form>';

				return $html;
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.5
			 * @return array
			 */
			public function wrapper_attr() {

				$attr = [
					'class' => 'fusion-form fusion-form-builder fusion-form-form-wrapper fusion-form-' . $this->args['form_post_id'],
					'style' => '',
				];

				$attr['data-form-id'] = $this->args['form_post_id'];

				$attr = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( ! $this->is_default_form_meta( 'form_border_width', 'bottom' ) || ! $this->is_default_form_meta( 'form_border_width', 'top' ) ) {
					$attr['class'] .= ' has-icon-alignment';
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				$attr['style'] .= $this->get_style_variables();

				// Studio Preview.
				if ( isset( $this->params['form_meta']['preview_width'] ) && isset( $_GET['awb-studio-form'] ) && ! isset( $_GET['fb-edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$attr['style'] .= 'width: ' . $this->params['form_meta']['preview_width'] . '%;';
				}
				if ( isset( $this->params['form_meta']['preview_background_color'] ) && isset( $_GET['awb-studio-form'] ) && ! isset( $_GET['fb-edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$attr['style'] .= 'background-color: ' . $this->params['form_meta']['preview_background_color'];
				}

				$attr['data-config'] = $this->localize_form_data();

				// Add Off Canvas data attr.
				if ( class_exists( 'AWB_Off_Canvas' ) && false !== AWB_Off_Canvas::is_enabled() && in_array( 'off-canvas', $this->params['form_meta']['form_actions'], true ) && $this->params['form_meta']['off_canvas'] ) {
					AWB_Off_Canvas_Front_End::add_off_canvas_to_stack( $this->params['form_meta']['off_canvas'] );
					$attr['data-off-canvas'] = $this->params['form_meta']['off_canvas'];
				}

				return $attr;
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 3.2
			 * @return void
			 */
			public function on_first_render() {
				Fusion_Dynamic_JS::enqueue_script(
					'fusion-form-js',
					FusionBuilder::$js_folder_url . '/general/fusion-form.js',
					FusionBuilder::$js_folder_path . '/general/fusion-form.js',
					[ 'jquery' ],
					FUSION_BUILDER_VERSION,
					true
				);

				Fusion_Dynamic_JS::enqueue_script(
					'fusion-form-logics',
					FusionBuilder::$js_folder_url . '/general/fusion-form-logics.js',
					FusionBuilder::$js_folder_path . '/general/fusion-form-logics.js',
					[ 'jquery', 'fusion-form-js' ],
					FUSION_BUILDER_VERSION,
					true
				);

				Fusion_Dynamic_JS::localize_script(
					'fusion-form-js',
					'formCreatorConfig',
					[
						'ajaxurl'             => admin_url( 'admin-ajax.php' ),
						'invalid_email'       => esc_attr__( 'The supplied email address is invalid.', 'fusion-builder' ),
						'max_value_error'     => esc_attr__( 'Max allowed value is: 2.', 'fusion-builder' ),
						'min_value_error'     => esc_attr__( 'Min allowed value is: 1.', 'fusion-builder' ),
						'max_min_value_error' => esc_attr__( 'Value out of bounds, limits are: 1-2.', 'fusion-builder' ),
						'file_size_error'     => esc_attr__( 'Your file size exceeds max allowed limit of ', 'fusion-builder' ),
						'file_ext_error'      => esc_attr__( 'This file extension is not allowed. Please upload file having these extensions: ', 'fusion-builder' ),

						/* translators: Input label for field that must match. */
						'must_match'          => esc_attr__( 'The value entered does not match the value for %s.', 'fusion-builder' ),
					]
				);
			}

			/**
			 * Localize form data.
			 *
			 * @since 3.1
			 * @access public
			 * @return string
			 */
			private function localize_form_data() {
				global $fusion_form;

				$form_data = [
					'form_id'           => isset( $this->params['form_number'] ) ? $this->params['form_number'] : '',
					'form_post_id'      => isset( $this->args['form_post_id'] ) ? $this->args['form_post_id'] : '',
					'post_id'           => get_the_ID(),
					'form_type'         => isset( $this->params['form_meta']['form_type'] ) ? $this->params['form_meta']['form_type'] : '',
					'confirmation_type' => isset( $this->params['form_meta']['form_confirmation_type'] ) ? $this->params['form_meta']['form_confirmation_type'] : '',
					'redirect_url'      => isset( $this->params['form_meta']['redirect_url'] ) ? $this->params['form_meta']['redirect_url'] : '',
					'field_labels'      => $fusion_form['field_labels'],
					'field_logics'      => $fusion_form['field_logics'],
					'field_types'       => $fusion_form['field_types'],
					'nonce_method'      => isset( $this->params['form_meta']['nonce_method'] ) ? $this->params['form_meta']['nonce_method'] : 'ajax',
				];

				return wp_json_encode( $form_data );
			}
		}
	}

	new FusionSC_FusionForm();
}

/**
 * Map shortcode to Fusion Builder.
 *
 * @since 1.0
 */
function fusion_element_form() {
	$fusion_settings = awb_get_fusion_settings();
	$forms_link      = '<a href="' . esc_url_raw( admin_url( 'admin.php?page=avada-forms' ) ) . '" target="_blank">' . esc_attr__( 'Forms Dashboard', 'fusion-builder' ) . '</a>';

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_FusionForm',
			[
				'name'            => esc_attr__( 'Avada Form', 'fusion-builder' ),
				'shortcode'       => 'fusion_form',
				'icon'            => 'fusiona-avada-form-element',
				'allow_generator' => true,
				'inline_editor'   => true,
				'preview'         => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-form-preview.php',
				'preview_id'      => 'fusion-builder-block-module-form-preview-template',
				'help_url'        => 'https://theme-fusion.com/documentation/avada/elements/avada-form-element/',
				'params'          => [
					[
						'type'        => 'select',
						'heading'     => esc_html__( 'Form', 'fusion-builder' ),
						'description' => sprintf(
							/* translators: link to forms-dashboard */
							esc_html__( 'Select the form from list. NOTE: You can create, edit and find forms on the %s page.', 'fusion-builder' ),
							$forms_link
						),
						'param_name'  => 'form_post_id',
						'value'       => Fusion_Builder_Form_Helper::fusion_form_creator_form_list(),
						'quick_edit'  => [
							'label' => esc_html__( 'Edit Form', 'fusion-builder' ),
							'type'  => 'form',
							'items' => Fusion_Builder_Form_Helper::fusion_form_creator_form_list( 'permalinks' ),
						],
					],
					'fusion_margin_placeholder' => [
						'group'      => esc_attr__( 'General', 'fusion-builder' ),
						'param_name' => 'margin',
						'value'      => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_form' );
