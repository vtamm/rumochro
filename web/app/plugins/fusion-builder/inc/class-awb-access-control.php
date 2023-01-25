<?php
/**
 * The AWB_Access_Control class.
 *
 * @package fusion-builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The AWB_Access_Control class.
 *
 * @since 3.9
 */
class AWB_Access_Control {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 3.9
	 * @var object
	 */
	private static $instance;

	/**
	 * Disabled post types.
	 *
	 * @access private
	 * @since 3.9
	 * @var array
	 */
	private static $allowed_post_types = [
		'fusion_tb_layout',
		'fusion_tb_section',
		'awb_off_canvas',
		'fusion_icons',
		'fusion_form',
		'slide',
		'fusion_template',
		'fusion_element',
		'avada_library',
		'avada_portfolio',
		'avada_faq',
		'post',
		'page',
	];

	/**
	 * Capabilities data.
	 *
	 * @access private
	 * @since 3.9
	 * @var array
	 */
	private static $capabilities;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->set_capabilities();
		$this->add_hooks();
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 3.9
	 * @return object
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new AWB_Access_Control();
		}
		return self::$instance;
	}

	/**
	 * Sets capabilities from fusion settings.
	 *
	 * @since 3.9
	 * @access private
	 * @return void
	 */
	private function set_capabilities() {
		$option = get_option( 'fusion_builder_settings', [] );

		if ( null === self::$capabilities && isset( $option['capabilities'] ) ) {
			self::$capabilities = $option['capabilities'];
		}

	}

	/**
	 * Adds necessary hooks for access control.
	 *
	 * @since 3.9
	 * @access private
	 * @return void
	 */
	private function add_hooks() {

		// Live editor access.
		add_filter( 'fusion_load_live_editor', [ $this, 'load_live_editor' ], PHP_INT_MAX, 1 );

		// Backend builder access.
		add_filter( 'awb_load_builder', [ $this, 'load_builder' ], PHP_INT_MAX, 1 );

		// Page Options.
		add_filter( 'awb_add_po_metabox', [ $this, 'page_options' ], PHP_INT_MAX, 2 );

		// Dashboard Menu CPTs.
		add_filter( 'awb_dashboard_menu_cpt', [ $this, 'dashboard_menu_cpt' ], PHP_INT_MAX, 2 );

		// Form submissions filter.
		add_filter( 'awb_view_forms_submissions', [ $this, 'forms_submissions' ], PHP_INT_MAX, 1 );

		// Live editor new post menu.
		add_filter( 'awb_live_editor_cpt', [ $this, 'live_editor_cpt' ], PHP_INT_MAX, 2 );

		// Dashboard Menu Options.
		add_filter( 'awb_dashboard_options_menu', [ $this, 'dashboard_menu_options' ], PHP_INT_MAX, 1 );

		// Global elements.
		add_filter( 'awb_global_elements_access', [ $this, 'global_element_access' ], PHP_INT_MAX, 1 );

		// Global elements hook.
		add_action( 'load-post.php', [ $this, 'direct_edit_access' ] );

	}

	/**
	 * Should user be able to access the dashboard options menu?
	 *
	 * @since 3.9
	 * @access public
	 * @param bool $default Current default value.
	 * @return bool
	 */
	public function dashboard_menu_options( $default ) {
		return $this->request_general_filter( $default, 'global_options' );
	}

	/**
	 * Should user be able to access the global elements?
	 *
	 * @since 3.9
	 * @access public
	 * @param bool $default Current default value.
	 * @return bool
	 */
	public function global_element_access( $default ) {
		return $this->request_general_filter( $default, 'global_elements' );
	}

	/**
	 * Should user be able to access the forms submissions?
	 *
	 * @since 3.9.2
	 * @access public
	 * @param bool $default Current default value.
	 * @return bool
	 */
	public function forms_submissions( $default ) {
		return $this->request_general_filter( $default, 'fusion_form_submissions' );
	}

	/**
	 * Should user be able to edit the post directly?
	 *
	 * @since 3.9
	 * @access public
	 * @return void
	 */
	public function direct_edit_access() {
		$post_id   = isset( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$post_type = get_post_type( $post_id );

		if ( $post_type ) {
			// Skip built in posts.
			if ( in_array( $post_type, [ 'post', 'page' ], true ) ) {
				return;
			}

			// Global elements access.
			if ( isset( $post_id ) && 'yes' === get_post_meta( $post_id, '_fusion_is_global', true ) && ! apply_filters( 'awb_global_elements_access', true ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 ); // phpcs:ignore WordPress.Security.EscapeOutput
			}

			// CPTs access.
			if ( in_array( $post_type, self::get_allowed_post_types(), true ) && ! apply_filters( 'awb_dashboard_menu_cpt', true, $post_type ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	}

	/**
	 * Should user be able to access the dashboard CPT menu?
	 *
	 * @since 3.9
	 * @access public
	 * @param bool   $default    Current default value.
	 * @param string $post_type  Current post type.
	 * @return bool
	 */
	public function dashboard_menu_cpt( $default, $post_type ) {
		$post_type = empty( $post_type ) ? $this->get_current_post_type() : $post_type;

		if ( 'post' === $post_type || 'page' === $post_type ) {
			return true;
		}

		return $this->request_cpt_filter( $default, $post_type, 'dashboard_menu' );
	}

	/**
	 * Should user be able to access the Live editor CPT menu?
	 *
	 * @since 3.9
	 * @access public
	 * @param bool   $default    Current default value.
	 * @param string $post_type  Current post type.
	 * @return bool
	 */
	public function live_editor_cpt( $default, $post_type ) {
		$post_type = empty( $post_type ) ? $this->get_current_post_type() : $post_type;

		return $this->request_cpt_filter( $default, $post_type, 'avada_live' );
	}

	/**
	 * Should user be able to access page options?
	 *
	 * @since 3.9
	 * @access public
	 * @param bool   $default    Current default value.
	 * @param string $post_type  Current post type.
	 * @return bool
	 */
	public function page_options( $default, $post_type ) {
		return $this->request_cpt_filter( $default, $post_type, 'page_options' );
	}

	/**
	 * Live editor filter.
	 *
	 * @since 3.9
	 * @access public
	 * @param bool $default  The default value.
	 * @return bool
	 */
	public function load_live_editor( $default ) {
		$post_type = $this->get_current_post_type();

		return $this->request_cpt_filter( $default, $post_type, 'avada_live' );
	}

	/**
	 * Live builder.
	 *
	 * @since 3.9
	 * @access public
	 * @param bool $default  The default value.
	 * @return bool
	 */
	public function load_builder( $default ) {
		$post_type = $this->get_current_post_type();

		return $this->request_cpt_filter( $default, $post_type, 'avada_builder' );
	}

	/**
	 * Filters general request.
	 *
	 * @since 3.9
	 * @access public
	 * @param bool   $default Current default value.
	 * @param string $request request type.
	 * @return bool
	 */
	public function request_general_filter( $default, $request ) {

		if ( $this->is_administrator() ) {
			return true;
		}

		if ( ! $this->role_can( $this->get_current_user_highest_role(), $request ) ) {
			$default = false;
		}

		return $default;
	}

	/**
	 * Filters CTP request.
	 *
	 * @since 3.9
	 * @access public
	 * @param bool   $default    Current default value.
	 * @param string $post_type  Current post type.
	 * @param string $request    request type.
	 * @return bool
	 */
	public function request_cpt_filter( $default, $post_type, $request ) {

		if ( $this->is_administrator() || ! in_array( $post_type, self::get_allowed_post_types(), true ) ) {
			return true;
		}

		if ( ! $this->role_can_for_cpt( $this->get_current_user_highest_role(), $request, $post_type ) ) {
			$default = false;
		}

		return $default;
	}

	/**
	 * Checks if role has specified access for CPT.
	 *
	 * @since 3.9
	 * @access private
	 * @param string $role      The role.
	 * @param string $type      The access type.
	 * @param string $post_type The post type.
	 * @return bool
	 */
	private function role_can_for_cpt( $role, $type, $post_type ) {
		$post_type = in_array( $post_type, [ 'fusion_template', 'fusion_element' ], true ) ? 'avada_library' : $post_type;
		$role      = $this->get_role_id( $role );

		if ( ( in_array( $post_type, self::get_allowed_post_types(), true ) && is_array( self::$capabilities ) && isset( self::$capabilities[ $role ] ) && in_array( $post_type . '_' . $type, self::$capabilities[ $role ], true ) ) || null === self::$capabilities ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if role has specified access.
	 *
	 * @since 3.9
	 * @access private
	 * @param string $role      The role.
	 * @param string $type      The access type.
	 * @return bool
	 */
	private function role_can( $role, $type ) {
		$role = $this->get_role_id( $role );

		if ( ( is_array( self::$capabilities ) && isset( self::$capabilities[ $role ] ) && in_array( $type, self::$capabilities[ $role ], true ) ) || null === self::$capabilities ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets role ID.
	 *
	 * @since 3.9
	 * @access private
	 * @param string $role The role.
	 * @return string
	 */
	private function get_role_id( $role ) {
		return strtolower( str_replace( [ ' ', '-' ], '_', $role ) );
	}

	/**
	 * Gets current post type.
	 *
	 * @since 3.9
	 * @access private
	 * @return string
	 */
	private function get_current_post_type() {
		global $typenow, $pagenow;

		$post_type = $typenow;

		if ( is_admin() ) {
			if ( 'edit.php' === $pagenow && '' === $typenow ) {
				$post_type = 'post';
			}

			if ( '' === $post_type ) {
				$post_type = $this->get_custom_screen_post_type();
			}
		} elseif ( ! is_admin() && isset( $_SERVER['REQUEST_URI'] ) && false === strpos( $_SERVER['REQUEST_URI'], 'fb-edit' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$post_type = get_post_type();
		} elseif ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'fb-edit' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$post_type = $this->get_live_editor_post_type();
		}

		$post_type = in_array( $post_type, [ 'fusion_template', 'fusion_element' ], true ) ? 'avada_library' : $post_type;

		return apply_filters( 'awb_access_control_current_screen_post_type', $post_type );
	}

	/**
	 * Gets current page post type in live editor.
	 *
	 * @since 3.9
	 * @access private
	 * @return string
	 */
	private function get_live_editor_post_type() {
		global $wp_rewrite;

		$fusion_settings = class_exists( 'Fusion_Settings' ) ? awb_get_fusion_settings() : false;
		$url             = $this->get_current_page_url();
		$post_type       = '';
		$portfolio_slug  = $fusion_settings && '' !== $fusion_settings->get( 'portfolio_slug' ) ? $fusion_settings->get( 'portfolio_slug' ) : 'portfolio-items';
		$faqs_slug       = $fusion_settings && '' !== $fusion_settings->get( 'faq_slug' ) ? $fusion_settings->get( 'faq_slug' ) : 'faq-items';
		$post_types      = [
			'awb_off_canvas'    => 'awb_off_canvas',
			'fusion_tb_section' => 'fusion_tb_section',
			'fusion_form'       => 'fusion_form',
			'fusion_element'    => 'fusion_element',
			'fusion_template'   => 'fusion_template',
			'avada_portfolio'   => $portfolio_slug,
			'avada_faq'         => $faqs_slug,
		];

		foreach ( $post_types as $item ) {
			if ( false !== strpos( $url, $item ) ) {
				$post_type = $item;
				break;
			}
		}

		if ( empty( $post_type ) && ! is_null( $wp_rewrite ) ) {
			$post_type = get_post_type( url_to_postid( $url ) );
		}

		if ( false === $post_type && get_home_url() === $url && 'page' === get_option( 'show_on_front' ) ) {
			$post_type = 'page';
		}

		return $post_type;
	}

	/**
	 * Gets current page URL.
	 *
	 * @since 3.9.2
	 * @access private
	 * @return string
	 */
	private function get_current_page_url() {
		$url = '';
		if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
			$url = 'https://';
		} else {
			$url = 'http://';
		}

		$url .= isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$url .= isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		return $url;
	}

	/**
	 * Gets post type for custom screens.
	 *
	 * @since 3.9
	 * @access private
	 * @return string
	 */
	private function get_custom_screen_post_type() {
		$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$screens = [
			'avada-layout-sections' => 'fusion_tb_section',
			'avada-off-canvas'      => 'awb_off_canvas',
			'avada-icons'           => 'fusion_icons',
			'avada-forms'           => 'fusion_form',
			'avada-form-entries'    => 'fusion_form',
			'avada-library'         => 'avada_library',
		];

		return isset( $screens[ $page ] ) ? $screens[ $page ] : '';
	}

	/**
	 * Checks if current user is administrator.
	 *
	 * @since 3.9
	 * @access private
	 * @return bool
	 */
	private function is_administrator() {
		return current_user_can( 'administrator' );
	}

	/**
	 * Gets current logged in user highest role.
	 *
	 * @since 3.9
	 * @access private
	 * @return string
	 */
	private function get_current_user_highest_role() {
		$role = [];

		if ( is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_roles = (array) $user->roles;

			foreach ( $user_roles as $user_role ) {
				$role_data          = get_role( $user_role );
				$capabilities_count = isset( $role_data->capabilities ) ? count( $role_data->capabilities ) : 0;
				if ( empty( $role ) ) {
					$role['role']         = $user_role;
					$role['capabilities'] = $capabilities_count;
				} elseif ( $capabilities_count > $role['capabilities'] ) {
					$role['role']         = $user_role;
					$role['capabilities'] = $capabilities_count;
				}
			}
		}

		return isset( $role['role'] ) ? $role['role'] : '';
	}

	/**
	 * Checks if current capability is available for role and echos/returns checked.
	 *
	 * @since 3.9
	 * @access public
	 * @param string $role_id    The role ID.
	 * @param string $capability The capability to check for.
	 * @param bool   $echo       Should echo or return value.
	 * @return mixed
	 */
	public static function maybe_checked( $role_id, $capability, $echo = true ) {
		if ( ( is_array( self::$capabilities ) && isset( self::$capabilities[ $role_id ] ) && in_array( $capability, self::$capabilities[ $role_id ], true ) ) || null === self::$capabilities ) {
			if ( $echo ) {
				echo 'checked';
			} else {
				return 'checked';
			}
		}
	}

	/**
	 * Creates dummy library object.
	 *
	 * @since 3.9
	 * @access public
	 * @return object
	 */
	public static function get_library_object() {
		$obj = new stdClass();

		$obj->label = __( 'Avada Library', 'fusion-builder' );
		$obj->name  = 'avada_library';

		return $obj;

	}

	/**
	 * Prefixes Avada in post label if does not exist.
	 *
	 * @since 3.9
	 * @param array $post The post object.
	 * @access public
	 * @return string
	 */
	public static function get_post_type_label( $post ) {
		$label = '';
		if ( ( is_object( $post ) && property_exists( $post, '_builtin' ) && $post->_builtin ) || false !== strpos( $post->label, 'Avada' ) ) {
			$label = $post->label;
		} else {
			$label = __( 'Avada', 'fusion-builder' ) . ' ' . $post->label;
		}

		return apply_filters( 'awb_access_control_post_type_label', $label );
	}

	/**
	 * Method to display options.
	 *
	 * @since 3.9
	 * @access public
	 * @return void
	 */
	public static function display_options() {
		$roles      = self::get_user_roles();
		$post_types = self::get_allowed_post_types();

		// Remove templates and elements. So that library option can be used for both.
		$post_types = array_diff( $post_types, [ 'fusion_template', 'fusion_element' ] );

		foreach ( $roles as $role ) {
			self::display_options_for_a_role( $role, $post_types );
		}
	}

	/**
	 * Displays options for a role.
	 *
	 * @since 3.9
	 * @param array $role       Object of user role.
	 * @param array $post_types Object of post types.
	 * @access private
	 * @return void
	 */
	private static function display_options_for_a_role( $role, $post_types ) {
		$template = locate_template( [ 'template-user-roles.php' ] );
		if ( ! empty( $template ) ) {
			include $template;
		} else {
			include FUSION_BUILDER_PLUGIN_DIR . 'templates/template-user-roles.php';
		}
	}

	/**
	 * Gets user roles.
	 *
	 * @access public
	 * @return array
	 */
	public static function get_user_roles() {
		$roles = get_editable_roles();

		// Remove admin role.
		unset( $roles['administrator'] );

		return $roles;
	}

	/**
	 * Checks if role has got specific WP core capabilities.
	 *
	 * @since 3.9
	 * @param string $role         The WP user role.
	 * @param array  $capabilities Array of capabilities.
	 * @param string $operator     The and/or operator.
	 * @access private
	 * @return boolean
	 */
	public static function wp_role_has_core_capability( $role, $capabilities, $operator = 'and' ) {
		$role_can = false;
		foreach ( $capabilities as $capability ) {
			if ( isset( $role['capabilities'][ $capability ] ) && true === $role['capabilities'][ $capability ] ) {
				$role_can = true;
			} elseif ( 'and' === $operator ) {
				$role_can = false;
				break;
			}
		}

		return $role_can;
	}

	/**
	 * Checks if user has certain WP core capability for a post.
	 *
	 * @since 3.9
	 * @param string $post_type  The post type.
	 * @param string $capability The capability.
	 * @access private
	 * @return boolean
	 */
	public static function wp_user_can_for_post( $post_type, $capability ) {
		$post_type_object = get_post_type_object( $post_type );
		return current_user_can( $post_type_object->cap->{$capability} );
	}

	/**
	 * Gets allowed post types.
	 *
	 * @access public
	 * @return array
	 */
	public static function get_allowed_post_types() {
		return apply_filters( 'awb_access_control_post_types', self::$allowed_post_types );
	}
}

/**
 * Instantiates the AWB_Access_Control class.
 * Make sure the class is properly set-up.
 *
 * @since object 3.9
 * @return object AWB_Access_Control
 */
function AWB_Access_Control() { // phpcs:ignore WordPress.NamingConventions
	return AWB_Access_Control::get_instance();
}
AWB_Access_Control();
