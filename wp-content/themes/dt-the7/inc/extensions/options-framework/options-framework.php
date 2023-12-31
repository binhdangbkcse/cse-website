<?php
define( 'OPTIONS_FRAMEWORK_VERSION', '1.5' );
define( 'OPTIONS_FRAMEWORK_URL', trailingslashit( get_template_directory_uri() . '/inc/extensions/' . basename(dirname( __FILE__ )) ) );
define( 'OPTIONS_FRAMEWORK_DIR', trailingslashit( dirname( __FILE__ ) ) );

/* Make sure we don't expose any info if called directly */

if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a little plugin, don't mind me.";
	exit;
}

if ( ! function_exists( 'optionsframework_get_presets_list' ) ) :

	/**
	 * Add theme options presets.
	 *
	 * @return array
	 */
	function optionsframework_get_presets_list() {
		$presets_names = array(
			'skin11r',
			'skin12r',
			'skin15r',
			'skin14r',
			'skin09r',
			'skin03r',
			'skin05r',
			'skin02r',
			'skin11b',
			'skin16r',
			'skin19b',
			'skin19r',
			'skin10r',
			'skin07c',
			'skin06r',

			'wizard01',
			'wizard02',
			'wizard03',
			'wizard05',
			'wizard07',
			'wizard08',
			'wizard09',
		);

		$presets = array();
		foreach ( $presets_names as $preset_name ) {
			$presets[ $preset_name ] = array(
				'src'   => '/inc/presets/icons/' . $preset_name . '.gif',
				'title' => $preset_name,
			);
		}

		return $presets;
	}

endif;

if ( ! function_exists( 'presscore_set_first_run_skin' ) ) :

	/**
	 * Set first run skin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $skin_name
	 * @return string
	 */
	function presscore_set_first_run_skin( $skin_name = '' ) {
		return 'skin11r';
	}

	add_filter( 'options_framework_first_run_skin', 'presscore_set_first_run_skin' );

endif;

require_once OPTIONS_FRAMEWORK_DIR . 'options-custom.php';

/* If the user can't edit theme options, no use running this plugin */
add_action( 'init', 'optionsframework_rolescheck', 20 );

function optionsframework_rolescheck() {
	if ( ! current_user_can( optionsframework_read_capability() ) ) {
		return;
	}

	add_action( 'admin' . '_bar_menu', 'optionsframework_admin_bar_theme_options', 40 );

	// If the user can edit theme options, let the fun begin!
	add_action( 'admin_menu', 'optionsframework_add_page' );
	add_action( 'admin_init', 'optionsframework_init' );
    add_action( 'wp_ajax_optionsframework_search', 'optionsframework_ajax_search' );

	if ( is_admin() ) {
		add_action( 'admin_enqueue_scripts', 'of_load_global_admin_assets' );
	} else {
		add_action( 'wp_enqueue_scripts', 'of_load_global_admin_assets' );
	}

	add_action( 'wp_ajax_save_the7_options', 'optionsframework_save_options_via_ajax' );
	add_action( 'wp_ajax_get_the7_options_last_error', 'optionsframework_get_last_php_error_via_ajax' );

	$options_preview = new The7_Options_Preview();
	$options_preview->bootstrap();

    // Replace default admin bar class for visual mode.
	$plugin_page = isset( $_GET['page'] ) ? $_GET['page'] : null;
    if ( optionsframework_get_options_files( $plugin_page ) ) {
	    optionsframework_save_view_state();

	    if ( optionsframework_is_in_visual_mode() ) {
		    add_filter( 'wp_admin_bar_class', 'optionsframework_visual_admin_bar_class', 9999 );
		    add_action( 'admin' . '_bar_menu', 'optionsframework_admin_bar_visual_mode', 9999 );
			add_filter( 'admin_body_class', 'of_body_class_filter' );
			add_action( 'submenu_file', 'optionsframework_empty_main_menu' );
			add_action( 'admin_print_styles', 'optionsfamework_inline_css_for_visual_mode' );
		}
    }
}

/**
 * Return admin bar class for visual mode.
 *
 * @return string
 */
function optionsframework_visual_admin_bar_class() {
    return 'The7_Options_Visual_Admin_Bar';
}

function optionsframework_options_switch_invalid_nonce_notice() {
    echo '<p>' . __( 'Cannot switch options mode: invalid nonce. Please go to another options page and try one more time.', 'the7mk2' ) . '</p>';
}

/**
 * Add inline styles that for options visual mode.
 *
 * Hide admin menu, fix margins for wpcontent and wpfooter.
 *
 * @since 7.6.0
 */
function optionsfamework_inline_css_for_visual_mode() {
	wp_add_inline_style(
		'the7-options',
		'
/* Hide admin menu */
#adminmenumain {
	display: none !important;
}

#wpcontent,
#wpfooter {
	margin-left: 0;
}
	    '
	);
}

/**
 * Empty admin menu on theme options pages in visual mode.
 *
 * @param string $submenu_file Submenu file.
 *
 * @return mixed
 */
function optionsframework_empty_main_menu( $submenu_file ) {
	global $menu;

	// Empty admin menu for visual mode. Less DOM elements ...
	$menu = array();

	return $submenu_file;
}

/**
 * Get options id.
 *
 * @return string
 */
function optionsframework_get_options_id() {
	static $options_id = null;

	if ( $options_id === null ) {
		$options_id = preg_replace( '/\W/', '', strtolower( wp_get_theme()->Name ) );
	}

	return apply_filters( 'optionsframework_get_options_id', $options_id );
}

/**
 * A unique identifier is defined to store the options in the database and reference them from the theme.
 * By default it uses the theme name, in lowercase and without spaces, but this can be changed if needed.
 * If the identifier changes, it'll appear as if the options have been reset.
 *
 */
function optionsframework_option_name() {
	$options_id = optionsframework_get_options_id();
	$of_settings = get_option('optionsframework', []);
	$update = false;

	if ( ! isset( $of_settings['id'] ) || $options_id != $of_settings['id'] ) {
		$of_settings['id'] = $options_id;
		$update = true;
	}

	if ( ! isset( $of_settings['knownoptions'] ) ) {
		$of_settings['knownoptions'] = array( $options_id );
		$update = true;
	} elseif ( ! in_array( $options_id, $of_settings['knownoptions'] ) ) {
		$of_settings['knownoptions'][] = $options_id;
		$update                        = true;
	}

	if ( $update ) {
		update_option('optionsframework', $of_settings);
	}
}

/* Loads the file for option sanitization */

add_action( 'init', 'optionsframework_load_sanitization' );

function optionsframework_load_sanitization() {
	require_once dirname( __FILE__ ) . '/options-sanitize.php';
}

/*
 * The optionsframework_init loads all the required files and registers the settings.
 *
 * Read more about the Settings API in the WordPress codex:
 * https://codex.wordpress.org/Settings_API
 *
 * The theme options are saved using a unique option id in the database.  Developers
 * traditionally set the option id via in theme using the function
 * optionsframework_option_name, but it can also be set using a hook of the same name.
 *
 * If a theme developer doesn't explictly set the unique option id using one of those
 * functions it will be set by default to: optionsframework_[the theme name]
 *
 */

function optionsframework_init() {

	// Load settings
	$optionsframework_settings = get_option( 'optionsframework' );

	// Updates the unique option id in the database if it has changed
	if ( function_exists( 'optionsframework_option_name' ) ) {
		optionsframework_option_name();
	}
	elseif ( has_action( 'optionsframework_option_name' ) ) {
		do_action( 'optionsframework_option_name' );
	}
	// If the developer hasn't explicitly set an option id, we'll use a default
	else {
		$default_themename = get_option( 'stylesheet' );
		$default_themename = preg_replace("/\W/", "_", strtolower($default_themename) );
		$default_themename = 'optionsframework_' . $default_themename;
		if ( isset( $optionsframework_settings['id'] ) ) {
			if ( $optionsframework_settings['id'] == $default_themename ) {
				// All good, using default theme id
			} else {
				$optionsframework_settings['id'] = $default_themename;
				update_option( 'optionsframework', $optionsframework_settings );
			}
		}
		else {
			$optionsframework_settings['id'] = $default_themename;
			update_option( 'optionsframework', $optionsframework_settings );
		}
	}

	$optionsframework_settings = get_option( 'optionsframework' );

	$saved_settings = get_option( $optionsframework_settings['id'] );

	// If the option has no saved data, load the defaults
	if ( ! $saved_settings ) {
		optionsframework_setdefaults();
	}

	// Registers the settings fields and callback
	register_setting( 'optionsframework', $optionsframework_settings['id'], 'optionsframework_validate' );

	// Change the capability required to save the 'optionsframework' options group.
	add_filter( 'option_page_capability_optionsframework', 'optionsframework_page_capability' );
}

/**
 * Ensures that a user with the 'edit_theme_options' capability can actually set the options
 * See: https://core.trac.wordpress.org/ticket/14365
 *
 * @param string $capability The capability used for the page, which is manage_options by default.
 * @return string The capability to actually use.
 */

function optionsframework_page_capability( $capability = '' ) {
	return 'edit_theme_options';
}

function optionsframework_read_capability() {
	return apply_filters( 'optionsframework_read_capability', 'edit_theme_options' );
}

/*
 * Adds default options to the database if they aren't already present.
 * May update this later to load only on plugin activation, or theme
 * activation since most people won't be editing the options.php
 * on a regular basis.
 *
 * https://codex.wordpress.org/Function_Reference/add_option
 *
 */

function optionsframework_setdefaults() {

	$optionsframework_settings = get_option( 'optionsframework' );

	// Gets the unique option id
	$option_name = $optionsframework_settings['id'];

	/*
	 * Each theme will hopefully have a unique id, and all of its options saved
	 * as a separate option set.  We need to track all of these option sets so
	 * it can be easily deleted if someone wishes to remove the plugin and
	 * its associated data.  No need to clutter the database.
	 *
	 */

	if ( isset( $optionsframework_settings['knownoptions'] ) ) {
		$knownoptions =  $optionsframework_settings['knownoptions'];
		if ( !in_array($option_name, $knownoptions) ) {
			array_push( $knownoptions, $option_name );
			$optionsframework_settings['knownoptions'] = $knownoptions;
			update_option( 'optionsframework', $optionsframework_settings);
		}
	} else {
		$newoptionname = array($option_name);
		$optionsframework_settings['knownoptions'] = $newoptionname;
		update_option('optionsframework', $optionsframework_settings);
	}

	// If the options haven't been added to the database yet, they are added now
	$values = of_get_default_values();

	if ( isset($values) ) {
		add_option( $option_name, $values ); // Add option with default settings
	}
}

function optionsframework_get_main_title() {
	return _x( 'Theme Options', 'backend', 'the7mk2' );
}

/* Add a subpage called "Theme Options" to the appearance menu. */

if ( !function_exists( 'optionsframework_add_page' ) ) {

	function optionsframework_add_page() {
		$sub_pages = optionsframework_get_menu_items_list();

		if ( empty( $sub_pages ) ) {
			return false;
		}

		$main_menu_item = array_shift( $sub_pages );
		$main_menu_slug = $main_menu_item->get( 'slug' );
		$page_callback = 'optionsframework_page';
		$capability = optionsframework_read_capability();

		// Add main page
		$main_page_id = add_menu_page(
			$main_menu_item->get( 'menu_title' ),
			optionsframework_get_main_title(),
			$capability,
			$main_menu_slug,
			$page_callback,
            '',
            3
		);

		// Adds actions to hook in the required css and javascript
		add_action( 'admin_print_styles-' . $main_page_id, 'optionsframework_load_styles' );
		add_action( 'admin_print_scripts-' . $main_page_id, 'optionsframework_load_scripts' );

		// Add sub_pages
		foreach ( $sub_pages as $sub_page ) {
			$sub_page_id = add_submenu_page(
				$main_menu_slug,
				$sub_page->get( 'page_title' ),
				$sub_page->get( 'menu_title' ),
				$capability,
				$sub_page->get( 'slug' ),
				$page_callback
			);

			// Adds actions to hook in the required css and javascript
			add_action( 'admin_print_styles-' . $sub_page_id,'optionsframework_load_styles' );
			add_action( 'admin_print_scripts-' . $sub_page_id, 'optionsframework_load_scripts' );
		}

		// Change menu name for main page
		global $submenu;
		if ( isset( $submenu[ $main_menu_slug ] ) ) {
			$submenu[ $main_menu_slug ][0][0] = $main_menu_item->get( 'menu_title' );
		}

		// Hide menu items from admin menu.
        if ( ! The7_Admin_Dashboard_Settings::get( 'options-in-sidebar' ) ) {
	        remove_menu_page( $main_menu_slug );
        }
	}

}

/* Loads the CSS */

function optionsframework_load_styles() {
	presscore_register_scripts();

	if ( ! wp_style_is( 'wp-color-picker','registered' ) ) {
		wp_register_style('wp-color-picker', PRESSCORE_ADMIN_URI . '/assets/vendor/wp-color-picker/color-picker.min.css');
	}

	wp_register_style( 'the7-select-2', PRESSCORE_ADMIN_URI . '/assets/vendor/select2/css/select2.min.css', false, THE7_VERSION );

	the7_register_style( 'the7-options', PRESSCORE_ADMIN_URI . '/assets/css/options', array( 'thickbox', 'wp-color-picker', 'the7-select-2' ) );
	wp_enqueue_style( 'the7-options' );

	do_action( 'optionsframework_load_styles' );
}

/* Loads the javascript */

function optionsframework_load_scripts() {
	if ( ! wp_script_is( 'wp-color-picker','registered' ) ) {
		wp_register_script('wp-color-picker', PRESSCORE_ADMIN_URI . '/assets/vendor/wp-color-picker/color-picker.min.js');
	}

    // Fix conflict with WPML.
	wp_dequeue_script( 'wpml-select-2' );

	if ( function_exists( 'wp_enqueue_code_editor' ) ) {
		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
	}

	// Select2
	wp_register_script( 'the7-select-2', PRESSCORE_ADMIN_URI . '/assets/vendor/select2/js/select2.min.js', array( 'jquery' ), THE7_VERSION, true );

	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}

	the7_register_script( 'the7-options', PRESSCORE_ADMIN_URI . '/assets/js/options', array(
		'the7-select-2',
		'jquery',
		'wp-color-picker',
		'thickbox',
		'jquery-ui-core',
		'jquery-ui-dialog',
		'jquery-ui-slider',
		'jquery-ui-widget',
		'jquery-ui-sortable',
		'jquery-ui-draggable',
		'jquery-form',
		'jquery-ui-autocomplete',
	), false, true );
	wp_enqueue_script( 'the7-options' );

	wp_localize_script( 'the7-options', 'optionsframework_l10n', array(
		'upload' => esc_html( __( 'Upload', 'the7mk2' ) ),
		'remove' => esc_html( __( 'Remove', 'the7mk2' ) ),
	) );

	// Inline scripts from options-interface.php
	add_action( 'admin_head', 'of_admin_head' );

	add_action( 'optionsframework_after', 'of_localize_scripts' );
}

if ( ! function_exists( 'presscore_options_black_list' ) ) :

	/**
	 * List of options ids that do not included while export.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $fields
	 * @return array
	 */
	function presscore_options_black_list( $fields = array() ) {
		$fields_black_list = array(
			'general-tracking_code',
			'general-post_type_portfolio_slug',
			'general-post_type_gallery_slug',
			'general-post_type_team_slug',
			'general-contact_form_send_mail_to',

			'general-favicon',
			'general-favicon_hd',
			'general-handheld_icon-old_iphone',
			'general-handheld_icon-old_ipad',
			'general-handheld_icon-retina_iphone',
			'general-handheld_icon-retina_ipad',

			'header-menu-submenu-parent_is_clickable',

			'footer-layout',
			'bottom_bar-copyrights',
			'bottom_bar-text',

			'general-beautiful_loading',

			'general-show_author_in_blog',
			'general-next_prev_in_blog',
			'general-show_back_button_in_post',
			'general-post_back_button_target_page_id',
			'general-blog_meta_on',
			'general-blog_meta_date',
			'general-blog_meta_author',
			'general-blog_meta_categories',
			'general-blog_meta_comments',
			'general-blog_meta_tags',

			'general-next_prev_in_portfolio',
			'general-show_back_button_in_project',
			'general-project_back_button_target_page_id',

			'general-portfolio_meta_on',
			'general-portfolio_meta_date',
			'general-portfolio_meta_author',
			'general-portfolio_meta_categories',
			'general-portfolio_meta_comments',

			'general-show_rel_projects',
			'general-rel_projects_head_title',
			'general-rel_projects_title',
			'general-rel_projects_excerpt',
			'general-rel_projects_info_date',
			'general-rel_projects_info_author',
			'general-rel_projects_info_comments',
			'general-rel_projects_info_categories',
			'general-rel_projects_link',
			'general-rel_projects_zoom',
			'general-rel_projects_details',
			'general-rel_projects_max',
			'general-rel_projects_fullwidth_height',
			'general-rel_projects_fullwidth_width_style',
			'general-rel_projects_fullwidth_width',
			'general-rel_projects_height',
			'general-rel_projects_width_style',
			'general-rel_projects_width',

			'social_buttons-post-button_title',
			'social_buttons-post',
			'social_buttons-portfolio_post-button_title',
			'social_buttons-portfolio_post',
			'social_buttons-photo-button_title',
			'social_buttons-photo',
			'social_buttons-page-button_title',
			'social_buttons-page',

			'widgetareas',

			// archives
			'template_page_id_author',
			'template_page_id_date',
			'template_page_id_blog_category',
			'template_page_id_blog_tags',
			'template_page_id_search',
			'template_page_id_portfolio_category',
			'template_page_id_gallery_category',

			//wpml
			'wpml_dt-custom_style',
			'contact_form_security_token',
			'contact_form_recaptcha_site_key',
			'contact_form_recaptcha_secret_key',
		);

		return array_unique( array_merge( $fields, $fields_black_list ) );
	}

	add_filter( 'optionsframework_fields_black_list', 'presscore_options_black_list' );
	add_filter( 'optionsframework_validate_preserve_fields', 'presscore_options_black_list', 14 );

endif;

if ( ! function_exists( 'presscore_themeoption_preserved_fields' ) ) :

	/**
	 * List of theme options ids that do not change after skin switch.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $fields
	 * @return array
	 */
	function presscore_themeoption_preserved_fields( $fields = array() ) {
		$preserved_fields = array(
			// header logo
			'header-logo_regular',
			'header-logo_hd',

			// bottom logo
			'bottom_bar-logo_regular',
			'bottom_bar-logo_hd',

			// mobile logo
			'header-style-mobile-logo_regular',
			'header-style-mobile-logo_hd',
			'header-style-mobile-logo-padding-top',
			'header-style-mobile-logo-padding-bottom',

			// floating logo
			'header-style-floating-choose_logo',
			'header-style-floating-logo_regular',
			'header-style-floating-logo_hd',

			// menu icons dimentions
			'header-icons_size',
			'header-submenu_icons_size',
			'header-submenu_next_level_indicator',
			'header-next_level_indicator',

			// header layout
			'header-login_caption',
			'header-logout_caption',
			'header-search_caption',
			'header-woocommerce_cart_caption',

			// Header layout.
			'header-classic-elements',
			'header-classic-show_elements',
			'header-inline-elements',
			'header-inline-show_elements',
			'header-split-elements',
			'header-split-show_elements',
			'header-side-elements',
			'header-side-show_elements',
			'header-slide_out-elements',
			'header-slide_out-show_elements',
			'header-overlay-elements',
			'header-overlay-show_elements',

			// Microwidgets.
			'header-elements-search-caption',
			'header-elements-search-icon',
			'header-elements-search-second-header-switch',
			'header-elements-contact-address-caption',
			'header-elements-contact-address-icon',
			'header-elements-contact-address-second-header-switch',
			'header-elements-contact-phone-caption',
			'header-elements-contact-phone-icon',
			'header-elements-contact-phone-second-header-switch',
			'header-elements-contact-email-caption',
			'header-elements-contact-email-icon',
			'header-elements-contact-email-second-header-switch',
			'header-elements-contact-skype-caption',
			'header-elements-contact-skype-icon',
			'header-elements-contact-skype-second-header-switch',
			'header-elements-contact-clock-caption',
			'header-elements-contact-clock-icon',
			'header-elements-contact-clock-second-header-switch',
			'header-elements-contact-multipurpose_1-caption',
			'header-elements-contact-multipurpose_1-icon',
			'header-elements-contact-multipurpose_1-second-header-switch',
			'header-elements-contact-multipurpose_2-caption',
			'header-elements-contact-multipurpose_2-icon',
			'header-elements-contact-multipurpose_2-second-header-switch',
			'header-elements-contact-multipurpose_3-caption',
			'header-elements-contact-multipurpose_3-icon',
			'header-elements-contact-multipurpose_3-second-header-switch',
			'header-elements-contact-multipurpose_4-caption',
			'header-elements-contact-multipurpose_4-icon',
			'header-elements-contact-multipurpose_4-second-header-switch',
			'header-elements-login-caption',
			'header-elements-logout-caption',
			'header-elements-login-icon',
			'header-elements-login-second-header-switch',
			'header-elements-login-url',
			'header-elements-text-second-header-switch',
			'header-elements-text',
			'header-elements-text-2-second-header-switch',
			'header-elements-text-2',
			'header-elements-text-3-second-header-switch',
			'header-elements-text-3',
			'header-elements-menu-second-header-switch',
			'header-elements-menu-style',
			'header-elements-menu-style-first-switch',
			'header-elements-menu-style-second-switch',
			'header-elements-soc_icons-second-header-switch',
			'header-elements-soc_icons',
			'header-elements-woocommerce_cart-caption',
			'header-elements-woocommerce_cart-icon',
			'header-elements-woocommerce_cart-second-header-switch',
			'header-elements-woocommerce_cart-show_sub_cart',
			'header-elements-woocommerce_cart-show_subtotal',
			'header-elements-woocommerce_cart-show_counter',
		);

		return array_unique( array_merge( $fields, $preserved_fields ) );
	}

	add_filter( 'optionsframework_validate_preserve_fields', 'presscore_themeoption_preserved_fields', 15 );

endif;

if ( ! function_exists( 'presscore_of_localized_vars_filter' ) ) :

	/**
	 * Setup blocks dependencies for "Top Bar & Header" options page. Filter optionsframework localized vars.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $vars
	 * @return array
	 */
	function presscore_of_localized_vars_filter( $vars ) {
		if ( 'of-header-menu' != optionsframework_get_cur_page_id() ) {
			return $vars;
		}

		$vars['blockDependencies'] = array(
			//Microwidgets

			'classic-microwidgets-settings' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'classic',
					),
					array(
						'field' => 'header-classic-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'inline-microwidgets' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'inline',
					),
				),
			),
			'inline-microwidgets-settings' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'inline',
					),
					array(
						'field' => 'header-inline-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'split-microwidgets' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'split',
					),
				),
			),
			'split-microwidgets-settings' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'split',
					),
					array(
						'field' => 'header-split-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'side-microwidgets' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'side',
					),
				),
			),
			'side-microwidgets-settings' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'side',
					),
					array(
						'field' => 'header-side-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'top-line-microwidgets' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'top_line',
					),
				),
			),
			'top-line-microwidgets-settings' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'top_line',
					),
					array(
						'field' => 'header-top_line-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'side-line-microwidgets' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'side_line',
					),
				),
			),
			'side-line-microwidgets-settings' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'side_line',
					),
					array(
						'field' => 'header-side_line-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'menu-icon-microwidgets' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'menu_icon',
					),
				),
			),
			'menu-icon-microwidgets-settings' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'menu_icon',
					),
					array(
						'field' => 'header-menu_icon-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'top-bar-microwidgets' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'classic',
					),
					array(
						'field' => 'header-classic-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'inline',
					),
					array(
						'field' => 'header-inline-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'split',
					),

					array(
						'field' => 'header-split-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'side',
					),
					array(
						'field' => 'header-side-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'top_line',
					),
					array(
						'field' => 'header-top_line-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'side_line',
					),
					array(
						'field' => 'header-side_line-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'menu_icon',
					),
					array(
						'field' => 'header-menu_icon-show_elements',
						'operator' => '==',
						'value' => '1',
					),
				),
			),

			// Menu
			'menu-horizontal-decoration-block' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'classic',
					)
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'inline',
					)
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'split',
					)
				)
			),
			'menu-top-headers-indention' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'classic',
					)
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'inline',
					)
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'split',
					)
				)
			),

			'microwidgets-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				)
			),
			'topbar-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				)
			),
			'header-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				)
			),
			'menu-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				)
			),
			'submenu-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				)
			),
			'mobile-header-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				)
			),
			'mobile-menu-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				)
			),

			// Floating header
			'floating-header-tab' => array(
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'classic',
					)
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'inline',
					)
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '==',
						'value' => 'split',
					)
				),
				array(
					array(
						'field' => 'header-layout',
						'operator' => '!=',
						'value' => 'disabled',
					)
				),
			),
			// Woocommerce
			'isotope-block-settings' => array(
				array(
					array(
						'field' => 'wc_view_mode',
						'operator' => '==',
						'value' => 'masonry_grid',
					),
				),
				array(
					array(
						'field' => 'wc_view_mode',
						'operator' => '==',
						'value' => 'view_mode',
					)
				),
			),
			'list-block-settings' => array(
				array(
					array(
						'field' => 'wc_view_mode',
						'operator' => '==',
						'value' => 'list',
					),
				),
				array(
					array(
						'field' => 'wc_view_mode',
						'operator' => '==',
						'value' => 'view_mode',
					)
				),
			),


		);

		return $vars;
	}

	add_filter( 'of_localized_vars', 'presscore_of_localized_vars_filter' );

endif;

function of_localize_scripts() {
    $edit_page_url_tpl = presscore_get_post_type_edit_link_template( 'page' );
    $edit_link = sprintf( '<a href="%s" target="_blank">%s</a>', $edit_page_url_tpl, esc_html_x( 'Edit page', 'back-end', 'the7mk2' ) );

	$localized_vars = array(
		'ajaxurl'              => admin_url( 'admin-ajax.php' ),
		'optionsNonce'         => wp_create_nonce( 'options-framework-nonce' ),
		'ajaxFontsNonce'       => wp_create_nonce( 'options-framework-ajax-fonts-nonce' ),
		'previewNonce'         => wp_create_nonce( The7_Options_Preview::SAVE_OPTIONS_NONCE_ACTION ),
		'dependencies'         => optionsframework_fields_dependency()->get_all(),
		'resetMsg'             => _x( 'Click OK to restore default settings on this page!', 'theme-options', 'the7mk2' ),
		'serverErrorMsg'       => _x( 'The application has encountered an unknown error.', 'theme-options', 'the7mk2' ),
		'editPageLinkTemplate' => $edit_link,
	);
	$localized_vars = apply_filters( 'of_localized_vars', $localized_vars );

	wp_localize_script( 'the7-options', 'optionsframework', $localized_vars );
}

if ( ! function_exists( 'presscore_get_post_type_edit_link_template' ) ) {

	/**
	 * Return post type edit link template or empty string if it's not possible.
	 *
	 * Replace %#% placeholder with actual post id.
	 *
	 * @sine 7.2.0
	 *
	 * @param string $post_type
	 *
	 * @return string
	 */
	function presscore_get_post_type_edit_link_template( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object || ! $post_type_object->_edit_link ) {
			return '';
		}
		$action = '&amp;action=edit';

		return admin_url( str_replace( '99999', '%#%', sprintf( $post_type_object->_edit_link . $action, 99999 ) ) );
	}

}

/**
 * Add classes to admin body.
 *
 * @param string $classes
 *
 * @return string
 */
function of_body_class_filter( $classes ) {
	$classes .= ' the7-customizer ';

    return $classes;
}

function of_admin_head() {
	// Hook to add custom scripts
	do_action( 'optionsframework_custom_scripts' );
}

function of_load_global_admin_assets() {
	the7_register_style( 'the7-admin-bar', PRESSCORE_ADMIN_URI . '/assets/css/admin-bar' );
	wp_enqueue_style( 'the7-admin-bar' );
	wp_add_inline_style( 'admin-bar', '#wpadminbar #wp-admin-bar-options-framework-parent > .ab-item:before{content: "\f111";}' );
}

if ( !function_exists( 'optionsframework_page' ) ) :

	function optionsframework_page() {
		if ( presscore_options_debug() ) {
			$wrap_class = ' of-debug';
		} else {
			$wrap_class = '';
		}

		$cur_page_id = optionsframework_get_cur_page_id();
	?>
		<div id="optionsframework-wrap" class="wrap<?php echo esc_attr( $wrap_class ); ?>">

			<?php if ( ! in_array( $cur_page_id, array( 'of-options-wizard', 'of-skins-menu' ), true ) ): ?>
			<div class="optionsframework-search">
				<input id="optionsframework-search" type="search" name="search" placeholder="<?php echo esc_attr_x( 'Search for an option ...', 'backend', 'the7mk2' ); ?>" data-nonce="<?php echo wp_create_nonce( 'the7-options-search' ); ?>"><span id="optionsframework-search-spinner" class="spinner"></span>
			</div>
			<?php endif; ?>

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'options-framework' ); ?>

			<?php
			$active_tab = '';
			if ( isset( $_GET['tab'] ) ) {
				$active_tab = sanitize_key( $_GET['tab'] );
			}

			$options_to_display = apply_filters( 'the7_replace_theme_options_to_display', [], $cur_page_id );
			if ( ! $options_to_display ) {
				$options_to_display = optionsframework_get_page_options( $cur_page_id );
			}

			$of_interface = new The7_Options( $options_to_display );
			?>

			<?php do_action( 'optionsframework_before_tabs' ); ?>

			<h2 class="nav-tab-wrapper hide-if-js"><?php $of_interface->render_tabs_html( $cur_page_id, $active_tab ); ?></h2>

			<?php do_action( 'optionsframework_before' ); ?>

			<div id="optionsframework-metabox" class="metabox-holder">
				<div id="optionsframework">
					<form id="optionsframework-form" action="options.php" method="post">
						<input type="hidden" name="action" value="save_the7_options">
						<?php
						wp_nonce_field( 'optionsframework-options' );

						$optionsframework_settings = get_option( 'optionsframework' );
						// Gets the unique option id
						if ( isset( $optionsframework_settings['id'] ) ) {
							$option_name = $optionsframework_settings['id'];
						} else {
							$option_name = 'optionsframework';
						}
						$settings = apply_filters( "optionsframework_fields_saved_settings-{$cur_page_id}", get_option( $option_name ) );
						$of_interface->render_options_html( $option_name, $settings );
						optionsframework_page_buttons();

						do_action( 'optionsframework_after_options' );
						?>
					</form>
				</div> <!-- / #container -->
			</div>

			<?php do_action( 'optionsframework_after' ); ?>

		</div> <!-- / .wrap -->

		<?php
		if ( optionsframework_is_in_visual_mode() ) {
			$base_url = home_url();
			if ( isset( $_COOKIE['the7-options-preview-url'] ) ) {
				$cookie_url = urldecode( $_COOKIE['the7-options-preview-url'] );
				if ( strpos( $cookie_url, $base_url ) === 0 && strpos( $cookie_url, get_admin_url() ) === false ) {
					$base_url = $cookie_url;
				}
			}
			$iframe_src = The7_Options_Preview::get_preview_url( The7_Options_Preview::PREVIEW_SITE_MODE, $base_url );

			printf(
				'<iframe id="the7-customizer-preview" src="%s" frameborder="0"></iframe>',
				esc_url( $iframe_src )
			);
		} else {
			?>
			<div id="the7-preview-modal" class="modal">
				<div class="modal-content desktop-view">
					<div class="modal-header">
						<a href="#" class="modal-view view-desktop"><?php esc_html_e( 'Desktop', 'the7mk2' ); ?></a>
						<a href="#" class="modal-view view-tablet"><?php esc_html_e( 'Tablet', 'the7mk2' ); ?></a>
						<a href="#" class="modal-view view-mobile"><?php esc_html_e( 'Mobile', 'the7mk2' ); ?></a>
						<span class="dashicons dashicons-no close"></span>
					</div>
					<div class="modal-body"></div>
				</div>
			</div>
			<?php
		}
	}

endif;

function prepare_options_data( $file, $slug ) {
	$prepared_options = array();
	$options = array();

	include PRESSCORE_ADMIN_OPTIONS_DIR  . '/options.php';
	include $file;

	$heading = '';
	$heading_id = '';
	$block = '';
	$exclude_block = false;

	$js_hide_stack = array();
    $current_js_hide = null;
    $js_hide = false;

	foreach ( $options as $option ) {
		if ( ! isset( $option['type'] ) ) {
			continue;
		}

		if ( 'js_hide_begin' === $option['type'] ) {
		    // Save state.
		    if ( ! is_null( $current_js_hide ) ) {
			    array_push( $js_hide_stack, array( $current_js_hide, $js_hide ) );
		    }

			if ( false === $current_js_hide ) {
	            // Case array( '1' => true )
	            $js_hide = true;
            } elseif( is_array( $current_js_hide ) && isset( $option['class'] ) ) {
				// Case array( '1' => array( 'one', 'two' ), '2' => array( 'tree', 'four' ) )
                // Search through classes.
	            foreach( explode( ' ', $option['class'] ) as $js_hide_class ) {
	                if ( in_array( $js_hide_class, $current_js_hide ) ) {
		                $js_hide = true;
		                break;
                    }
                }
            }
            continue;
        }

        if ( 'js_hide_end' === $option['type'] ) {
		    if ( count( $js_hide_stack ) ) {
			    list( $current_js_hide, $js_hide ) = array_pop( $js_hide_stack );
		    } else {
			    $js_hide = false;
            }
		    continue;
        }

        // Sanitize option name
		$option['name'] = ( isset( $option['name'] ) ? $option['name'] : '' );
		$option['name'] = trim( $option['name'] );
		$option['name'] = str_replace( '&nbsp;', '', $option['name'] );

		if ( empty( $option['name'] ) ) {
		    continue;
        }

		if ( 'heading' === $option['type'] ) {
			$heading = $option['name'];
			$heading_id = isset( $option['id'] ) ? $option['id'] : $option['name'];
			continue;
		}

		if ( in_array( $option['type'], array( 'block', 'block_begin' ) ) ) {
			$block = $option['name'];
			$exclude_block = ( ! empty( $option['exclude_from_search'] ) );
			continue;
		}

		if ( empty( $option['id'] ) || ! empty( $option['exclude_from_search'] ) || $exclude_block || $js_hide ) {
		    continue;
        }

        $option_id = $option['id'];
        $prepared_options[ $option_id ] = array(
            'id' => $option_id,
            'name' => $option['name'],
            'page_slug' => $slug,
            'heading' => $heading,
            'heading_id' => $heading_id,
            'block' => $block,
        );

        if ( isset( $option['show_hide'] ) ) {
            // Save state.
            if ( $current_js_hide ) {
                array_push( $js_hide_stack, array( $current_js_hide, false ) );
            }

	        $cur_option_value = of_get_option( $option['id'] );

	        if ( 1 === count( $option['show_hide'] ) ) {
		        // Case array( 'value' => true )
		        $current_js_hide = key_exists( $cur_option_value, $option['show_hide'] );
            } else {
	            // Case array( '1' => array( 'one', 'two' ), '2' => array( 'tree', 'four' ) )
                // Exclude currently active blocks.
		        unset( $option['show_hide'][ $cur_option_value ] );

		        // Compact blocks classes.
		        $current_js_hide = array();
		        foreach ( $option['show_hide'] as $trigger_val => $hide_blocks ) {
			        $hide_blocks = (array) $hide_blocks;
			        $current_js_hide = array_merge( $current_js_hide, $hide_blocks );
		        }
		        $current_js_hide = array_unique( $current_js_hide );
	        }
        }
	}

	return $prepared_options;
}

function optionsframework_ajax_search() {
    $user_capability = optionsframework_page_capability();
    if ( ! check_ajax_referer( 'the7-options-search', false, false ) || ! current_user_can( $user_capability ) ) {
	    wp_send_json( array() );
    }

	$search_query = isset( $_REQUEST['search'] ) ? preg_quote( urldecode( trim( $_REQUEST['search'] ) ), '/' ) : '';
	if ( empty( $search_query ) ) {
		wp_send_json( array() );
	}

    $options = array();
    foreach ( optionsframework_get_options_files() as $slug => $file ) {
        $options = array_merge( $options, prepare_options_data( $file, $slug ) );
    }

	$found_options = array();
    $reqexp = join( '.*', explode( ' ', $search_query ) );
    foreach ( $options as $option ) {
        if ( preg_match( "/{$reqexp}/i", "{$option['heading']} {$option['block']} {$option['name']}" ) ) {
            $found_options[] = $option;
        }
    }

    $search_results = array();
    foreach ( $found_options as $option ) {
        $tab_id = $option['heading_id'];
        $tab_id = preg_replace( '/[^a-zA-Z0-9._\-]/', '', strtolower( $tab_id ) ) . '-tab';
        $option_href = add_query_arg( array( 'page' => $option['page_slug'], 'tab' => $tab_id, 'mark' => $option['id'] ), admin_url( 'admin.php' ) ) . "#section-{$option['id']}";
        $search_results[] = array(
            'label' => "{$option['heading']}->{$option['block']}->{$option['name']}",
            'value' => $option_href,
        );
    }

    if ( empty( $search_results ) ) {
	    $search_results[] = array(
		    'label' => _x( 'Nothing found', 'backend', 'the7mk2' ),
		    'value' => 'none',
        );
    }

    wp_send_json( $search_results );
}

/**
 * Print submit buttons.
 */
function optionsframework_page_buttons() {
	$buttons = array(
		'update' => '<input type="submit" class="button button-primary" name="update" value="' . esc_attr( __( 'Save Options', 'the7mk2' ) ) . '" data-busy-value="' . esc_attr( __( 'Saving Options ...', 'the7mk2' ) ) . '"/>',

		'preview' => '',

		'reset' => '<input type="submit" class="button reset-button button-secondary of-reload-page-on-click" name="reset" value="' . esc_attr( __( 'Restore Defaults', 'the7mk2' ) ) .'"  data-busy-value="' . esc_attr( __( 'Restoring Defaults ...', 'the7mk2' ) ) . '"/>',
	);

	if ( defined( 'THE7_FEATURE_FLAG_THEME_OPTIONS_PREVIEW_BUTTON' ) ) {
		$preview_title = __( 'Preview', 'the7mk2' );
		$buttons['preview'] = '<a id="the7-show-preview" href="#" class="button preview" target="_blank" title="' . esc_attr( $preview_title ) . '" style="float: left; margin-right: 10px;"><span class="spinner"></span>' . esc_html( $preview_title ) . '</a>';
	}

	if ( optionsframework_is_in_visual_mode() ) {
		unset( $buttons['preview'] );
	}

	$current_page_id = optionsframework_get_cur_page_id();
	$buttons = apply_filters( 'optionsframework_page_buttons', $buttons, $current_page_id );

	if ( $buttons ) {
		echo '<div id="submit-wrap"><div id="optionsframework-submit">' . join( '', $buttons ) . '<div class="clear"></div></div></div>';
	}
}

function optionsframework_sanitize_options_values( $options, $values, $defaults_override = array() ) {
    $clean = array();

	foreach ( $options as $option ) {
		if ( isset( $option['save'] ) && $option['save'] === false ) {
			continue;
		}

		if ( ! isset( $option['id'] ) ) {
			continue;
		}

		if ( ! isset( $option['type'] ) ) {
			continue;
		}

		// Do not save those options types.
		if ( in_array( $option['type'], array( 'heading', 'block' ) ) ) {
			continue;
		}

		$id = preg_replace( '/(\W!-)/', '', strtolower( $option['id'] ) );

		// Set checkbox to false if it wasn't sent in the $_POST
		if ( ! isset( $values[ $id ] ) && in_array( $option['type'], array( 'checkbox', 'switch', 'multi_select' ), true ) ) {
			$values[ $id ] = false;
		}

		// Set each item in the multicheck to false if it wasn't sent in the $_POST
		if ( 'multicheck' === $option['type'] && ! isset( $values[ $id ] ) ) {
			foreach ( $option['options'] as $key => $value ) {
				$values[ $id ][ $key ] = false;
			}
		}

		// Use preset value instead native std
		if ( isset( $defaults_override[ $id ] ) ) {
			$option['std'] = $defaults_override[ $id ];
		}

		if ( ! isset( $values[ $id ] ) ) {
			continue;
		}

		$sanitizers = ( empty( $option['sanitize'] ) ? array() : $option['sanitize'] );
		if ( is_string( $sanitizers ) ) {
			$sanitizers = array_map( 'trim', explode( ',', $sanitizers ) );
		}

		$was_sanitized = false;
		if ( $sanitizers ) {
			$option_val = $values[ $id ];
			foreach ( $sanitizers as $sanitizer_name ) {
				if ( has_filter( "of_sanitize_{$sanitizer_name}" ) ) {
					$option_val = apply_filters( "of_sanitize_{$sanitizer_name}", $option_val, $option );
					$was_sanitized = true;
				}
			}

			if ( $was_sanitized ) {
				$clean[ $id ] = $option_val;
			}
		}

		if ( ! $was_sanitized && has_filter( 'of_sanitize_' . $option['type'] ) ) {
			$clean[ $id ] = apply_filters( 'of_sanitize_' . $option['type'], $values[ $id ], $option );
		}
	}

	return $clean;
}

/**
 * Validate Options.
 *
 * This runs after the submit/reset button has been clicked and
 * validates the inputs.
 *
 * @uses $_POST['reset'] to restore default options
 */
function optionsframework_validate( $input ) {

	/*
	 * Restore Defaults.
	 *
	 * In the event that the user clicked the "Restore Defaults"
	 * button, the options defined in the theme's options.php
	 * file will be added to the option for the active theme.
	 */

	if ( isset( $_POST['reset'] ) ) {
		add_settings_error( 'options-framework', 'restore_defaults', __( 'Default options restored.', 'the7mk2' ), 'updated fade' );
		$current = null;
		if ( isset( $_POST['_wp_http_referer'] ) ) {
			$arr = array();
			wp_parse_str( $_POST['_wp_http_referer'], $arr );
			$current = current($arr);
		}
		$options = of_get_default_values( $current );
		do_action( 'optionsframework_after_options_reset' );

		return $options;
	}

	/*
	 * Update Settings
	 *
	 * This used to check for $_POST['update'], but has been updated
	 * to be compatible with the theme customizer introduced in WordPress 3.4
	 */

	$input = apply_filters( 'optionsframework_validate_input', $input );

	/**
	 * Highjack saved options validation.
	 *
	 * @since 3.0.0
	 */
	if ( $clean = apply_filters( 'optionsframework_get_validated_options', array(), $input ) ) {
		$clean = apply_filters( 'optionsframework_validated_options', $clean );

		// Hook to run after validation
		do_action( 'optionsframework_after_validate', $clean, $input );

		return $clean;
	}

	// Get all saved options
	$known_options = get_option( 'optionsframework', array() );
	$saved_options = $used_options = get_option( $known_options['id'], array() );
	if ( ! is_array( $saved_options ) ) {
		$saved_options = $used_options = array();
	}
	if ( empty( $saved_options['preset'] ) ) {
		$saved_options['preset'] = apply_filters( 'options_framework_first_run_skin', '' );
    }
	$presets_list = optionsframework_get_presets_list();

	$apply_preset = ! empty( $_POST['optionsframework_apply_preset'] );

	// If there are preset option on this page - use this options instead saved
	if ( $apply_preset && isset( $input['preset'] ) && in_array( $input['preset'], array_keys( $presets_list ) ) ) {

		// Get preset options
		$preset_options = optionsframework_presets_data( $input['preset'] );

		$preserve = apply_filters( 'optionsframework_validate_preserve_fields', array() );

		// Ignore preserved options
		foreach ( $preserve as $option ) {
			if ( isset( $preset_options[ $option ] ) ) {
				unset( $preset_options[ $option ] );
			}
		}

		if ( !isset( $preset_options['preset'] ) ) {
			$preset_options['preset'] = $input['preset'];
		}

		// Use all options for sanitazing
		$options =& _optionsframework_options();

		// Merge options, use preset options
		$used_options = array_merge( (array) $saved_options, $preset_options );

		$is_preset = true;

	// if import / export
	} elseif ( ! empty( $input['import_export'] ) ) {

		// Use all options for sanitazing
		$options =& _optionsframework_options();

		$import_options = json_decode( trim( $input['import_export'] ), true );

		if ( is_array( $import_options ) ) {
			$used_options = array_merge( (array) $saved_options, $import_options );
		}

		$is_preset      = true;
		$preset_options = [];
		// If regular page
	} else {

		// Get current preset options
		$preset_options = optionsframework_presets_data( $saved_options['preset'] );

		// Options only for current page
		$page_id = optionsframework_get_cur_page_id();
		$options = optionsframework_get_page_options( $page_id );

		// Define options data with which we will work
		$used_options = $input;

		$is_preset = false;
	}

	if ( $is_preset ) {
	    foreach ( $options as $option ) {
	        if ( ! isset( $option['id'], $option['type'] ) ) {
	            continue;
            }
	        $id = $option['id'];
		    if ( isset( $used_options[ $id ] ) && 'upload' === $option['type'] && is_array( $used_options[ $id ] ) ) {
			    $used_options[ $id ] = array_reverse( $used_options[ $id ] );
		    }
        }
    }

	// Sanitize options
	$clean = optionsframework_sanitize_options_values( $options, $used_options, $preset_options );

	// Merge current options and saved ones
	$clean = array_merge( $saved_options, $clean );
	$clean = apply_filters( 'optionsframework_validated_options', $clean );

	// Hook to run after validation
	do_action( 'optionsframework_after_validate', $clean, $input );

	ksort( $clean );

	return $clean;
}

function optionsframework_save_options_via_ajax() {
    try {
	    check_ajax_referer('optionsframework-options');

	    $optionsframework_settings = get_option( 'optionsframework' );
	    $options_id = $optionsframework_settings['id'];
		$options_to_save = array();
	    if ( array_key_exists( $options_id, $_POST ) ) {
	    	$options_to_save = wp_unslash( $_POST[ $options_id ] );
	    }
		update_option( $options_id, $options_to_save );

	    wp_raise_memory_limit( 'admin' );
	    register_shutdown_function( 'optionsframework_catch_last_php_error' );

	    $dynamic_css = presscore_get_dynamic_stylesheets_list();
        $admin_dynamic_css = presscore_get_admin_dynamic_stylesheets_list();

	    presscore_regenerate_dynamic_css( array_merge( $dynamic_css, $admin_dynamic_css ) );
	    do_action( 'optionsframework_options_saved' );
    } catch ( Exception $e ) {
        wp_send_json_error( array( 'msg' => $e->getMessage() ) );
    }
	wp_send_json_success( array( 'msg' => 'Options saved!' ) );
}

/**
 * Catch last php error and store related message.
 */
function optionsframework_catch_last_php_error() {
	$last_php_error = error_get_last();
	if ( isset( $last_php_error['message'], $last_php_error['type'] ) && $last_php_error['type'] === E_ERROR ) {
	    delete_transient( 'the7_options_errors' );

		if ( strpos( $last_php_error['message'], 'Allowed memory size' ) !== false ) {
			$error = _x( 'Theme options cannot be saved. Not enough memory available. Please try to increase <a href="https://support.dream-theme.com/knowledgebase/allowed-memory-size-error/" title="memory limit">memory limit</a>', 'theme-options', 'the7mk2' );
			set_transient( 'the7_options_errors', $error, 30 );
		}
	}
}

/**
 * Ajax callback that output last php error message that was stored by 'optionsframework_catch_last_php_error'.
 */
function optionsframework_get_last_php_error_via_ajax() {
	try {
		$error = get_transient( 'the7_options_errors' );
		if ( $error ) {
			wp_send_json_success( array( 'msg' => $error ) );
		}
	} catch ( Exception $e ) {
		wp_send_json_error();
	}

	wp_send_json_error();
}

function optionsframework_options_saved( $state = true ) {
	update_option( 'the7_options_saved', $state, false );
}

function optionsframework_options_is_saved() {
	return get_option( 'the7_options_saved' );
}

/**
 * Display message when options have been saved
 */

function optionsframework_save_options_notice() {
	add_settings_error( 'options-framework', 'save_options', _x( 'Options saved.', 'backend', 'the7mk2' ), 'updated fade' );
	optionsframework_options_saved();
}

add_action( 'optionsframework_after_validate', 'optionsframework_save_options_notice' );

/**
 * Format Configuration Array.
 *
 * Get an array of all default values as set in
 * options.php. The 'id','std' and 'type' keys need
 * to be defined in the configuration array. In the
 * event that these keys are not present the option
 * will not be included in this function's output.
 *
 * @return    array     Rey-keyed options configuration array.
 *
 * @access    private
 */

function of_get_default_values( $page = null ) {
	$output = $preset = $saved_options = array();
	$known_options = get_option( 'optionsframework', array() );
	$tmp_options = get_option( $known_options['id'], array() );

	// If this is first run - use one of preset
	if ( empty( $tmp_options ) ) {
		$tmp_options['preset'] = apply_filters('options_framework_first_run_skin', '');
	}

	// If this is preset page - restore it's defaults
	if ( isset( $tmp_options['preset'] ) ) {
		// Get preset options
		$preset = optionsframework_presets_data( $tmp_options['preset'] );

		// if preset not set - set it
		if ( !isset( $preset['preset'] ) ) {
			$preset['preset'] = $tmp_options['preset'];
		}
	}

	// Current page defaults
	if ( $page ) {
		$config = optionsframework_get_page_options( $page );
		$saved_options = $tmp_options;
	} else {
		$config =& _optionsframework_options();
	}

	foreach ( (array) $config as $option ) {
		if ( ! isset( $option['id'] ) ) {
			continue;
		}
		if ( ! isset( $option['std'] ) ) {
			continue;
		}
		if ( ! isset( $option['type'] ) ) {
			continue;
		}
		if ( has_filter( 'of_sanitize_' . $option['type'] ) ) {
			$value = $option['std'];

			// Use defaults from preset if it's present
			if ( isset( $preset[ $option['id'] ] ) ) {
				$preset_value = $preset[ $option['id'] ];

				if ( 'upload' == $option['type'] && isset($option['mode']) && 'full' == $option['mode'] ) {
					$preset_value = array_reverse($preset_value);
				}

				$value = $preset_value;
			}

			$sanitizers = ( empty( $option['sanitize'] ) ? array() : $option['sanitize'] );
			if ( is_string( $sanitizers ) ) {
				$sanitizers = array_map( 'trim', explode( ',', $sanitizers ) );
			}

			$was_sanitized = false;
			if ( $sanitizers ) {
				$option_val = $value;
				foreach ( $sanitizers as $sanitizer_name ) {
					if ( has_filter( "of_sanitize_{$sanitizer_name}" ) ) {
						$option_val = apply_filters( "of_sanitize_{$sanitizer_name}", $option_val, $option );
						$was_sanitized = true;
					}
				}

				if ( $was_sanitized ) {
					$output[ $option['id'] ] = $option_val;
				}
			}

			if ( ! $was_sanitized && has_filter( 'of_sanitize_' . $option['type'] ) ) {
				$output[ $option['id'] ] = apply_filters( 'of_sanitize_' . $option['type'], $value, $option );
			}
		}
	}
	$output = array_merge($saved_options, $output);

	return apply_filters( 'of_get_default_values', $output );
}

/**
 * Add "Theme Options" menu to the Toolbar.
 *
 * @since 6.0.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 *
 * @return void
 */
function optionsframework_admin_bar_theme_options( $wp_admin_bar ) {
	global $plugin_page;

	$menu_items = optionsframework_get_menu_items_list();

	// Bail if there is no menu items to show.
	if ( empty( $menu_items ) ) {
		return;
	}

	$parent_menu_item = current( $menu_items );
	$parent_menu_id = 'options-framework-parent';
	$wp_admin_bar->add_menu( array(
		'id'    => $parent_menu_id,
		'title' => optionsframework_get_main_title(),
		'href'  => admin_url( 'admin.php?page=options-framework' ),
	));

	foreach( $menu_items as $menu_item ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $parent_menu_id,
			'id'     => $menu_item->get( 'slug' ),
			'title'  => $menu_item->get( 'menu_title' ),
			'href'   => admin_url( 'admin.php?page=' . urlencode( $menu_item->get( 'slug' ) ) ),
		));
	}

	// Only for theme options pages.
	if ( optionsframework_get_options_files( $plugin_page ) ) {
		// Add "Edit in Back-end" switcher.
		$wp_admin_bar->add_group( array(
			'parent' => $parent_menu_id,
			'id'     => 'the7-options-view-controls',
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );
		$switcher_title = _x( 'Edit in Back-end', 'admin-bar', 'the7mk2' );
		$switcher_class = 'edit-back';
		$next_view = 'backend';
		if ( optionsframework_is_in_backend_mode() ) {
			$switcher_title = _x( 'Edit in Front-end', 'admin-bar','the7mk2' );
			$switcher_class = 'edit-front';
			$next_view = 'frontend';
		}
		$wp_admin_bar->add_menu( array(
			'parent' => 'the7-options-view-controls',
			'id'     => 'the7-options-preview-switcher',
			'title'  => $switcher_title,
			'href'   => add_query_arg( array( 'page' => $plugin_page, 'view' => $next_view ), admin_url( 'admin.php' ) ),
			'meta'   => array(
				'class'   => $switcher_class,
			),
		) );
	}
}

/**
 * Change admin bar in visual mode.
 *
 * @param object $wp_admin_bar
 *
 * @return void
 */
function optionsframework_admin_bar_visual_mode( $wp_admin_bar ) {
    // Whitelist admin bar items.
    if ( is_a( $wp_admin_bar, 'The7_Options_Visual_Admin_Bar' ) && method_exists( $wp_admin_bar, 'set_white_list' ) ) {
        $wp_admin_bar->set_white_list( array(
            array(
                'menu-toggle',
                'wp-logo',
                'site-name',
                'options-framework-parent',
                'the7-options-preview-switcher',
                'view-controls',
                'view',
            ),
            array(
                'my-account',
            )
        ) );
    }

    // Options panel controls.
    $wp_admin_bar->add_menu( array(
        'id'    => 'view-controls',
        'title' => sprintf(
	        '<span class="options-show">%s</span><span class="options-hide">%s</span> %s',
	        _x( 'Show', 'admin-bar', 'the7mk2' ),
	        _x( 'Hide', 'admin-bar', 'the7mk2'  ),
	        _x( 'Options Panel', 'admin-bar', 'the7mk2' )
        ),
        'href'  => '#',
        'meta'  => array(
            'onclick' => 'return false',
            'class' => 'panel-shown',
        ),
    ) );

    // Device controls.
    switch ( optionsframework_get_view_device() ) {
        case 'mobile':
            $view_class = 'view-mobile';
            break;
        case 'tablet':
            $view_class = 'view-tablet';
            break;
        default:
            $view_class = 'view-desktop';
    }
    $wp_admin_bar->add_menu( array(
        'id'    => 'view',
        'title' => _x( 'View', 'admin-bar', 'the7mk2' ),
        'meta' => array(
            'class' => $view_class,
        ),
    ) );
    $wp_admin_bar->add_menu( array(
        'parent' => 'view',
        'id'     => 'view-desktop',
        'title'  => _x( 'Desktop', 'admin-bar', 'the7mk2' ),
        'href'   => '#',
        'meta'   => array(
            'onclick' => 'return false',
        ),
    ) );
    $wp_admin_bar->add_menu( array(
        'parent' => 'view',
        'id'     => 'view-tablet',
        'title'  => _x( 'Tablet', 'admin-bar', 'the7mk2' ),
        'href'   => '#',
        'meta'   => array(
            'onclick' => 'return false',
        ),
    ) );
    $wp_admin_bar->add_menu( array(
        'parent' => 'view',
        'id'     => 'view-mobile',
        'title'  => _x( 'Mobile', 'admin-bar', 'the7mk2' ),
        'href'   => '#',
        'meta'   => array(
            'onclick' => 'return false',
        ),
    ) );

    // Connect 'site-name' with admin dashboard.
    $site_name_node = $wp_admin_bar->get_node( 'site-name' );
    $site_name_node->href = admin_url( '/' );
    $wp_admin_bar->add_menu( $site_name_node );

    // Add "Dashboard" menu item.
    $wp_admin_bar->add_menu( array(
        'parent' => 'site-name',
        'id'     => 'view-dashboard',
        'title'  => _x( 'Dashboard', 'admin-bar', 'the7mk2' ),
        'href'   => admin_url( '/' ),
    ) );
}

/**
 * Save options view state in db if it is changed.
 */
function optionsframework_save_view_state() {
	$new_view  = ( isset( $_GET['view'] ) ? $_GET['view'] : '' );

	if ( ! $new_view ) {
	    return;
    }

	if (
		( $new_view === 'frontend' && optionsframework_is_in_backend_mode() ) ||
		( $new_view === 'backend' && optionsframework_is_in_visual_mode() )
    ) {
		update_option( 'the7_options_view_mode', $new_view );
    }
}

/**
 * Return true if options are in backend mode.
 *
 * @return bool
 */
function optionsframework_is_in_backend_mode() {
	$mode = get_option( 'the7_options_view_mode' );

	return ( $mode === 'backend' );
}

/**
 * Return true if options are in visual mode.
 *
 * @return bool
 */
function optionsframework_is_in_visual_mode() {
	return ! optionsframework_is_in_backend_mode();
}

/**
 * Return current view device. By default it's desktop.
 *
 * @return string
 */
function optionsframework_get_view_device() {
	return ( isset( $_COOKIE['the7-options-preview-device'] ) ? $_COOKIE['the7-options-preview-device'] : 'desktop' );
}

/**
 * Retrieve theme options array.
 *
 * @return array
 */
function optionsframework_get_options() {
	$options = get_option( optionsframework_get_options_id() );

	return is_array( $options ) ? $options : [];
}

if ( ! function_exists( 'of_get_option' ) ) :

	/**
	 * Get Option.
	 *
	 * Helper function to return the theme option value.
	 * If no value has been saved, it returns $default.
	 * Needed because options are saved as serialized strings.
	 *
	 * @param string $name    Theme options id.
	 * @param mixed  $default Default is false.
	 *
	 * @return mixed Theme option value.
	 */
	function of_get_option( $name, $default = false ) {
		$options = apply_filters( 'dt_of_get_option_static', optionsframework_get_options() );
		$options = apply_filters( 'dt_of_get_option', $options, $name );

		if ( isset( $options[ $name ] ) ) {
			return apply_filters( "dt_of_get_option-{$name}", $options[ $name ] ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		if ( false === $default ) {
			$def_val = _optionsframework_get_option_default_value( $name );

			if ( $def_val !== null ) {
				return $def_val;
			}
		}

		return $default;
	}

endif;

/**
 * Wrapper for optionsframework_options()
 *
 * Allows for manipulating or setting options via 'of_options' filter
 * For example:
 *
 * <code>
 * add_filter('of_options', function($options) {
 *     $options[] = array(
 *         'name' => 'Input Text Mini',
 *         'desc' => 'A mini text input field.',
 *         'id' => 'example_text_mini',
 *         'std' => 'Default',
 *         'class' => 'mini',
 *         'type' => 'text'
 *     );
 *
 *     return $options;
 * });
 * </code>
 *
 * Also allows for setting options via a return statement in the
 * options.php file.  For example (in options.php):
 *
 * <code>
 * return array(...);
 * </code>
 *
 * @return array (by reference)
 */
function &_optionsframework_options() {
	$options = optionsframework_load_options( optionsframework_get_options_files() );

	// Allow setting/manipulating options via filters
	$options = apply_filters( 'of_options', $options );

	return $options;
}


/**
 * Return array with actual theme options.
 *
 * @return mixed
 */
function _optionsframework_get_clean_options() {
	if ( false === ( $clean_options = wp_cache_get( 'optionsframework_clean_options', 'optionsframework' ) ) ) {
		$clean_options =& _optionsframework_options();

		wp_cache_set( 'optionsframework_clean_options', $clean_options, 'optionsframework', MINUTE_IN_SECONDS );
	}

	return $clean_options;
}

/**
 * Delete options cache.
 */
function _optionsframework_delete_defaults_cache() {
	wp_cache_delete( 'optionsframework_clean_options', 'optionsframework' );
}

/**
 * Return option default value.
 *
 * @param  string $id
 * @return mixed
 */
function _optionsframework_get_option_default_value( $id ) {
	$defaults = _optionsframework_get_clean_options();
	return ( isset( $defaults[ $id ]['std'] ) ? $defaults[ $id ]['std'] : null );
}

/**
 * Turn off autolod of unused theme options db entries.
 *
 * @since 6.8.1
 */
function optionsframework_db_autoload_options() {
	global $wpdb;

	$of_settings = get_option( 'optionsframework' );

	if ( ! isset( $of_settings['id'], $of_settings['knownoptions'] ) ) {
		return;
	}

	$current_options_id = optionsframework_get_options_id();
	$unused_options     = array_diff( $of_settings['knownoptions'], array( $current_options_id ) );
	if ( count( $unused_options ) === 0 ) {
	    return;
    }

	$placeholder = implode( ', ', array_fill( 0, count( $unused_options ), '%s' ) );
	$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->options SET autoload = 'no' WHERE option_name IN ({$placeholder})", $unused_options ) );
	$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->options SET autoload = 'yes' WHERE option_name = %s", $current_options_id ) );
}
add_action( 'after_switch_theme', 'optionsframework_db_autoload_options' );

if ( ! function_exists( 'optionsframework_fields_dependency' ) ) :

	/**
	 * Returns object with stored options dependencies.
	 *
	 * @since 3.0.0
	 * @return The7_Options_Dependency_Handler
	 */
	function optionsframework_fields_dependency() {
		static $dep_obj = null;

		if ( null === $dep_obj ) {
			$dep_obj = new The7_Options_Dependency_Handler();
		}

		return $dep_obj;
	}

endif;

if ( ! function_exists( 'presscore_options_apply_template' ) ) :

	/**
	 * Apply options template.
	 *
	 * @param  array &$options
	 * @param  string $tpl
	 * @param  string $prefix
	 * @param  array  $fields
	 */
	function presscore_options_apply_template( &$options, $tpl, $prefix, $fields = array(), $dependency = array() ) {
		require_once PRESSCORE_ADMIN_DIR . '/theme-options-parts.php';

		$class_name = 'Presscore_Lib_Options_' . implode( '', array_map( 'ucfirst', explode( '-',  strtolower( $tpl ) ) ) ) . 'Template';

		if ( class_exists( $class_name ) ) {
			$template = new $class_name();
			$template->execute( $options, $prefix, $fields, $dependency );
		}
	}

endif;

function optionsframework_get_fonts_options( $group = 'all' ) {
	switch ( $group ) {
		case 'safe':
			return presscore_options_get_safe_fonts();
		case 'web':
			return presscore_options_get_web_fonts();
		case 'all':
		default:
			return presscore_options_get_all_fonts();
	}
}

function optionsframework_fonts_ajax_response() {
	if ( ! check_ajax_referer( 'options-framework-ajax-fonts-nonce', false, false ) || ! current_user_can( optionsframework_read_capability() ) ) {
		wp_send_json_error();
	}

	$fonts = optionsframework_get_fonts_options( isset( $_POST['fontsGroup'] ) ? $_POST['fontsGroup'] : '' );
	$html  = '';
	foreach ( $fonts as $key => $option ) {
		$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
	}
	wp_send_json_success( $html );
}

add_action( 'wp_ajax_of_get_fonts', 'optionsframework_fonts_ajax_response' );

/**
 * @return void
 */
function optionsframework_icons_picker_ajax_response() {
    if ( ! current_user_can( optionsframework_read_capability() ) ) {
		wp_send_json_error();
    }

	$icon_manager_icons = apply_filters( 'the7_icons_in_settings', [] );

	wp_send_json( $icon_manager_icons );
}

add_action( 'wp_ajax_the7_get_icons_for_icons_picker', 'optionsframework_icons_picker_ajax_response' );

function of_save_unsanitized_options( $options ) {
	add_filter( 'optionsframework_get_validated_options', 'the7_skip_options_sanitizing', 10, 2 );
	update_option( optionsframework_get_options_id(), $options );
	remove_filter( 'optionsframework_get_validated_options', 'the7_skip_options_sanitizing', 10 );
}
