<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://prolificdigital.com
 * @since      1.0.0
 *
 * @package    Wp_Events
 * @subpackage Wp_Events/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Events
 * @subpackage Wp_Events/includes
 * @author     Prolific Digital <support@prolificdigital.com>
 */
class Wp_Events {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Events_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if (defined('WP_EVENTS_VERSION')) {
			$this->version = WP_EVENTS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-events';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Events_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Events_i18n. Defines internationalization functionality.
	 * - Wp_Events_Admin. Defines all hooks for the admin area.
	 * - Wp_Events_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-events-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-events-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-events-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-events-public.php';

		/**
		 * The fields that are responsible for the events interface.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/fields/event-fields.php';

		/**
		 * The class responsible for recurring events.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/classes/recurring-events.php';

		/**
		 * The class responsible for incorporating zoom api.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/classes/zoom.php';

		/**
		 * The class responsible for sending event notifications.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/classes/event-notifications.php';

		$this->loader = new Wp_Events_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Events_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Events_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		// $zoom = new Zoom();
		// $this->loader->add_action('create_events', $zoom, 'insertNewEvents');

		$plugin_admin = new Wp_Events_Admin($this->get_plugin_name(), $this->get_version());
		$event_notification = new EventNotifications();

		$this->loader->add_action('init', $plugin_admin, 'events', 0);
		$this->loader->add_action('event_notification', $event_notification, 'notify_registrants');

		$recurring_events = new Recurring_Event();

		// It's important that updating the event series comes before create them or an infinite loop will start.
		$this->loader->add_filter('post_updated', $recurring_events, 'get_previous_statuses');
		$this->loader->add_action('save_post', $recurring_events, 'update_series');
		$this->loader->add_action('save_post', $recurring_events, 'create_series');

		// Customize the url setting to fix incorrect asset URLs.
		$this->loader->add_filter('acf/settings/url', $plugin_admin, 'my_acf_settings_url');

		// (Optional) Hide the ACF admin menu item.
		$this->loader->add_filter('acf/settings/show_admin', $plugin_admin, 'my_acf_settings_show_admin');

		$this->loader->add_filter('acf/load_field/name=series_id', $plugin_admin, 'acf_read_only');
		$this->loader->add_filter('acf/load_field/name=parent_id', $plugin_admin, 'acf_read_only');
		// $this->loader->add_filter('acf/load_field/name=registrants', $plugin_admin, 'acf_read_only');
	}

		/**
	 * Creates cron job for
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_cron_jobs() {
		// Creates new cron scheduler for fifteen minute intervals.
		add_filter('cron_schedules', function ($schedules) {
			$schedules['fifteen_minutes'] = array(
				'interval' => 900,
				'display'  => esc_html__('Every Fifteen Minutes'),
			);
			return $schedules;
		});

		// Set up CronJob for Creating new Zoom Events.
		if (!wp_next_scheduled('create_events')) {
			wp_schedule_event(time(), 'fifteen_minutes', 'create_events');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Events_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		$this->loader->add_filter('the_content', $plugin_public, 'display_event_details', 1);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Events_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
