<?php

class WPEventsSettings {
	private $wp_events_settings_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wp_events_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'wp_events_settings_page_init' ) );
	}

	public function wp_events_settings_add_plugin_page() {
		add_options_page(
			'WP Events Settings', // page_title
			'WP Events Settings', // menu_title
			'manage_options', // capability
			'wp-events-settings', // menu_slug
			array( $this, 'wp_events_settings_create_admin_page' ) // function
		);
	}

	public function wp_events_settings_create_admin_page() {
		$this->wp_events_settings_options = get_option( 'wp_events_settings_option_name' ); ?>

		<div class="wrap">
			<h2>WP Events Settings</h2>
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'wp_events_settings_option_group' );
					do_settings_sections( 'wp-events-settings-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function wp_events_settings_page_init() {
		register_setting(
			'wp_events_settings_option_group', // option_group
			'wp_events_settings_option_name', // option_name
			array( $this, 'wp_events_settings_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'wp_events_settings_setting_section', // id
			'Settings', // title
			array( $this, 'wp_events_settings_section_info' ), // callback
			'wp-events-settings-admin' // page
		);

		add_settings_field(
			'zoom_api_key_0', // id
			'Zoom API Key', // title
			array( $this, 'zoom_api_key_0_callback' ), // callback
			'wp-events-settings-admin', // page
			'wp_events_settings_setting_section' // section
		);

		add_settings_field(
			'zoom_api_secret_1', // id
			'Zoom API Secret', // title
			array( $this, 'zoom_api_secret_1_callback' ), // callback
			'wp-events-settings-admin', // page
			'wp_events_settings_setting_section' // section
		);

		add_settings_field(
			'zoom_user_email_2', // id
			'Zoom User Email', // title
			array( $this, 'zoom_user_email_2_callback' ), // callback
			'wp-events-settings-admin', // page
			'wp_events_settings_setting_section' // section
		);
	}

	public function wp_events_settings_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['zoom_api_key_0'] ) ) {
			$sanitary_values['zoom_api_key_0'] = sanitize_text_field( $input['zoom_api_key_0'] );
		}

		if ( isset( $input['zoom_api_secret_1'] ) ) {
			$sanitary_values['zoom_api_secret_1'] = sanitize_text_field( $input['zoom_api_secret_1'] );
		}

		if ( isset( $input['zoom_user_email_2'] ) ) {
			$sanitary_values['zoom_user_email_2'] = sanitize_text_field( $input['zoom_user_email_2'] );
		}

		return $sanitary_values;
	}

	public function wp_events_settings_section_info() {

	}

	public function zoom_api_key_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="wp_events_settings_option_name[zoom_api_key_0]" id="zoom_api_key_0" value="%s">',
			isset( $this->wp_events_settings_options['zoom_api_key_0'] ) ? esc_attr( $this->wp_events_settings_options['zoom_api_key_0']) : ''
		);
	}

	public function zoom_api_secret_1_callback() {
		printf(
			'<input class="regular-text" type="text" name="wp_events_settings_option_name[zoom_api_secret_1]" id="zoom_api_secret_1" value="%s">',
			isset( $this->wp_events_settings_options['zoom_api_secret_1'] ) ? esc_attr( $this->wp_events_settings_options['zoom_api_secret_1']) : ''
		);
	}

	public function zoom_user_email_2_callback() {
		printf(
			'<input class="regular-text" type="text" name="wp_events_settings_option_name[zoom_user_email_2]" id="zoom_user_email_2" value="%s">',
			isset( $this->wp_events_settings_options['zoom_user_email_2'] ) ? esc_attr( $this->wp_events_settings_options['zoom_user_email_2']) : ''
		);
	}

}

if ( is_admin() )
	$wp_events_settings = new WPEventsSettings();

/*
 * Retrieve this value with:
 * $wp_events_settings_options = get_option( 'wp_events_settings_option_name' ); // Array of All Options
 * $zoom_api_key_0 = $wp_events_settings_options['zoom_api_key_0']; // Zoom API Key
 * $zoom_api_secret_1 = $wp_events_settings_options['zoom_api_secret_1']; // Zoom API Secret
 * $zoom_user_email_2 = $wp_events_settings_options['zoom_user_email_2']; // Zoom User ID
 */
