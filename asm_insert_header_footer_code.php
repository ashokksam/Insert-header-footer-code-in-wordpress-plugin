<?php
/**
* Plugin Name: Insert Code in Header and Footer
* Plugin URI: http://www.Ashokkumawat.com/
* Version: 1.0.1
* Requires at least: 4.6
* Requires PHP: 5.2
* Tested up to: 5.7
* Author: Ashok kumawat
* Author URI: http://www.Ashokkumawat.com/
* Description: Easy to add code in header footer script, HTML, CSS code.
* License: GPLv2 or later
*/

/**
* Insert Code In Headers and Footers Starts
*/
class ASMInsertCODEHeadersAndFooters {
	
	public function __construct() {
		$asm_file_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );

		// Plugin Details
		$this->plugin                           = new stdClass;
		$this->plugin->name                     = 'asm-insert-codein-headers-and-footers'; // Plugin Folder
		$this->plugin->displayName              = 'ASM Insert CODE IN Headers and Footers'; // Plugin Name
		$this->plugin->version                  = $asm_file_data['Version'];
		$this->plugin->folder                   = plugin_dir_path( __FILE__ );
		$this->plugin->url                      = plugin_dir_url( __FILE__ );
		$this->plugin->db_welcome_dismissed_key = $this->plugin->name . '_welcome_dismissed_key';
		$this->body_open_supported              = function_exists( 'wp_body_open' ) && version_compare( get_bloginfo( 'version' ), '5.2', '>=' );

		// Hooks
		add_action( 'admin_init', array( &$this, 'asm_registerSettings' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'asm_initCodeMirror' ) );
		add_action( 'admin_menu', array( &$this, 'asm_adminPanelsAndMetaBoxes' ) );
		add_action( 'admin_notices', array( &$this, 'asm_dashboardNotices' ) );
		add_action( 'wp_ajax_' . $this->plugin->name . '_dismiss_dashboard_notices', array( &$this, 'asm_dismissDashboardNotices' ) );

		// Frontend Hooks
		add_action( 'wp_head', array( &$this, 'asm_frontendHeader' ) );
		add_action( 'wp_footer', array( &$this, 'asm_frontendFooter' ) );
		if ( $this->body_open_supported ) {
			add_action( 'wp_body_open', array( &$this, 'asm_frontendBody' ), 1 );
		}
	}

	/**
	 * Show relevant notices for the plugin
	 */
	function asm_dashboardNotices() {
		global $pagenow;

		if (
			! get_option( $this->plugin->db_welcome_dismissed_key )
			&& current_user_can( 'manage_options' )
		) {
			if ( ! ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'asm-insert-codein-headers-and-footers' === $_GET['page'] ) ) {
				$setting_page = admin_url( 'options-general.php?page=' . $this->plugin->name );
				// load the notices view
				include_once( $this->plugin->folder . '/views/dashboard-notices.php' );
			}
		}
	}

	/**
	 * Dismiss the welcome notice for the plugin
	 */
	function asm_dismissDashboardNotices() {
		check_ajax_referer( $this->plugin->name . '-nonce', 'nonce' );
		// user has dismissed the welcome notice
		update_option( $this->plugin->db_welcome_dismissed_key, 1 );
		exit;
	}

	/**
	* Register Settings
	*/
	function asm_registerSettings() {
		register_setting( $this->plugin->name, 'asm_insert_header', 'trim' );
		register_setting( $this->plugin->name, 'asm_insert_footer', 'trim' );
		register_setting( $this->plugin->name, 'asm_insert_body', 'trim' );
	}

	/**
	* Register the plugin settings panel
	*/
	function asm_adminPanelsAndMetaBoxes() {
		add_submenu_page( 'options-general.php', $this->plugin->displayName, $this->plugin->displayName, 'manage_options', $this->plugin->name, array( &$this, 'adminPanel' ) );
	}

	/**
	* Output the Administration Panel
	* Save POSTed data from the Administration Panel into a WordPress option
	*/
	function adminPanel() {
		/*
		 * Only users with manage_options can access this page.
		 *
		 * The capability included in add_settings_page() means WP should deal
		 * with this automatically but it never hurts to double check.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'asm-insert-codein-headers-and-footers' ) );
		}

		// only users with `unfiltered_html` can edit scripts.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->errorMessage = '<p>' . __( 'Sorry, only have read-only access to this page. Ask your administrator for assistance editing.', 'asm-insert-codein-headers-and-footers' ) . '</p>';
		}

		// Save Settings
		if ( isset( $_REQUEST['submit'] ) ) {
			// Check permissions and nonce.
			if ( ! current_user_can( 'unfiltered_html' ) ) {
				// Can not edit scripts.
				wp_die( __( 'Sorry, you are not allowed to edit this page.', 'asm-insert-codein-headers-and-footers' ) );
			} elseif ( ! isset( $_REQUEST[ $this->plugin->name . '_nonce' ] ) ) {
				// Missing nonce
				$this->errorMessage = __( 'nonce field is missing. Settings NOT saved.', 'asm-insert-codein-headers-and-footers' );
			} elseif ( ! wp_verify_nonce( $_REQUEST[ $this->plugin->name . '_nonce' ], $this->plugin->name ) ) {
				// Invalid nonce
				$this->errorMessage = __( 'Invalid nonce specified. Settings NOT saved.', 'asm-insert-codein-headers-and-footers' );
			} else {
				// Save
				// $_REQUEST has already been slashed by wp_magic_quotes in wp-settings
				// so do nothing before saving
				update_option( 'asm_insert_header', $_REQUEST['asm_insert_header'] );
				update_option( 'asm_insert_footer', $_REQUEST['asm_insert_footer'] );
				update_option( 'asm_insert_body', isset( $_REQUEST['asm_insert_body'] ) ? $_REQUEST['asm_insert_body'] : '' );
				update_option( $this->plugin->db_welcome_dismissed_key, 1 );
				$this->message = __( 'Settings Saved.', 'asm-insert-codein-headers-and-footers' );
			}
		}

		// Get latest settings
		$this->settings = array(
			'asm_insert_header' => esc_html( wp_unslash( get_option( 'asm_insert_header' ) ) ),
			'asm_insert_footer' => esc_html( wp_unslash( get_option( 'asm_insert_footer' ) ) ),
			'asm_insert_body'   => esc_html( wp_unslash( get_option( 'asm_insert_body' ) ) ),
		);

		// Load Settings Form
		include_once( $this->plugin->folder . '/views/settings.php' );
	}

	/**
	 * Enqueue and initialize CodeMirror for the form fields.
	 */
	function asm_initCodeMirror() {
		// Make sure that we don't fatal error on WP versions before 4.9.
		if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
			return;
		}

		global $pagenow;

		if ( ! ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'asm-insert-codein-headers-and-footers' === $_GET['page'] ) ) {
			return;
		}

		$editor_args = array( 'type' => 'text/html' );

		if ( ! current_user_can( 'unfiltered_html' ) || ! current_user_can( 'manage_options' ) ) {
			$editor_args['codemirror']['readOnly'] = true;
		}

		// Enqueue code editor and settings for manipulating HTML.
		$settings = wp_enqueue_code_editor( $editor_args );

		// Bail if user disabled CodeMirror.
		if ( false === $settings ) {
			return;
		}

		// Custom styles for the form fields.
		$styles = '.CodeMirror{ border: 1px solid #ccd0d4; }';

		wp_add_inline_style( 'code-editor', $styles );

		wp_add_inline_script( 'code-editor', sprintf( 'jQuery( function() { wp.codeEditor.initialize( "asm_insert_header", %s ); } );', wp_json_encode( $settings ) ) );
		wp_add_inline_script( 'code-editor', sprintf( 'jQuery( function() { wp.codeEditor.initialize( "asm_insert_body", %s ); } );', wp_json_encode( $settings ) ) );
		wp_add_inline_script( 'code-editor', sprintf( 'jQuery( function() { wp.codeEditor.initialize( "asm_insert_footer", %s ); } );', wp_json_encode( $settings ) ) );
	}

	/**
	* Outputs script / CSS to the frontend header
	*/
	function asm_frontendHeader() {
		$this->output( 'asm_insert_header' );
	}

	/**
	* Outputs script / CSS to the frontend footer
	*/
	function asm_frontendFooter() {
		$this->output( 'asm_insert_footer' );
	}

	/**
	* Outputs script / CSS to the frontend below opening body
	*/
	function asm_frontendBody() {
		$this->output( 'asm_insert_body' );
	}

	/**
	* Outputs the given setting, if conditions are met
	*
	* @param string $setting Setting Name
	* @return output
	*/
	function output( $setting ) {
		// Ignore admin, feed, robots or trackbacks
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		// provide the opportunity to Ignore asm - both headers and footers via filters
		if ( apply_filters( 'disable_asm', false ) ) {
			return;
		}

		// provide the opportunity to Ignore asm - footer only via filters
		if ( 'asm_insert_footer' === $setting && apply_filters( 'disable_asm_footer', false ) ) {
			return;
		}

		// provide the opportunity to Ignore asm - header only via filters
		if ( 'asm_insert_header' === $setting && apply_filters( 'disable_asm_header', false ) ) {
			return;
		}

		// provide the opportunity to Ignore asm - below opening body only via filters
		if ( 'asm_insert_body' === $setting && apply_filters( 'disable_asm_body', false ) ) {
			return;
		}

		// Get meta
		$meta = get_option( $setting );
		if ( empty( $meta ) ) {
			return;
		}
		if ( trim( $meta ) === '' ) {
			return;
		}

		// Output
		echo wp_unslash( $meta );
	}
}

$asm = new ASMInsertCODEHeadersAndFooters();
