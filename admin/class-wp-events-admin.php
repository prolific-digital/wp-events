<?php

// Define path and URL to the ACF plugin.
define('MY_ACF_PATH', plugin_dir_path(__DIR__) .  '/vendor/acf/');
define('MY_ACF_URL', plugin_dir_url(__DIR__) .  '/vendor/acf/');

// Include the ACF plugin.
include_once(MY_ACF_PATH . 'acf.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://prolificdigital.com
 * @since      1.0.0
 *
 * @package    Wp_Events
 * @subpackage Wp_Events/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Events
 * @subpackage Wp_Events/admin
 * @author     Prolific Digital <support@prolificdigital.com>
 */
class Wp_Events_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	function my_acf_settings_url($url) {
		return MY_ACF_URL;
	}

	function my_acf_settings_show_admin($show_admin) {
		return true;
	}

	public function acf_read_only($field) {
		$field['readonly'] = 1;
		$field['disabled'] = true;
		return $field;
	}

	/**
	 * Register the events custom post type.
	 * 
	 * @author  Chris Miller <chris@prolificdigital.com>
	 *
	 * @since    0.0.1
	 * 
	 * @return void
	 */
	public function events() {
		$labels = array(
			'name'                  => _x('Events', 'Post Type General Name', 'text_domain'),
			'singular_name'         => _x('Event', 'Post Type Singular Name', 'text_domain'),
			'menu_name'             => __('Events', 'text_domain'),
			'name_admin_bar'        => __('Event', 'text_domain'),
			'archives'              => __('Item Archives', 'text_domain'),
			'attributes'            => __('Item Attributes', 'text_domain'),
			'parent_item_colon'     => __('Parent Item:', 'text_domain'),
			'all_items'             => __('All Items', 'text_domain'),
			'add_new_item'          => __('Add New Item', 'text_domain'),
			'add_new'               => __('Add New', 'text_domain'),
			'new_item'              => __('New Item', 'text_domain'),
			'edit_item'             => __('Edit Item', 'text_domain'),
			'update_item'           => __('Update Item', 'text_domain'),
			'view_item'             => __('View Item', 'text_domain'),
			'view_items'            => __('View Items', 'text_domain'),
			'search_items'          => __('Search Item', 'text_domain'),
			'not_found'             => __('Not found', 'text_domain'),
			'not_found_in_trash'    => __('Not found in Trash', 'text_domain'),
			'featured_image'        => __('Featured Image', 'text_domain'),
			'set_featured_image'    => __('Set featured image', 'text_domain'),
			'remove_featured_image' => __('Remove featured image', 'text_domain'),
			'use_featured_image'    => __('Use as featured image', 'text_domain'),
			'insert_into_item'      => __('Insert into item', 'text_domain'),
			'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
			'items_list'            => __('Items list', 'text_domain'),
			'items_list_navigation' => __('Items list navigation', 'text_domain'),
			'filter_items_list'     => __('Filter items list', 'text_domain'),
		);
		$args = array(
			'label'                 => __('Event', 'text_domain'),
			'description'           => __('Create and manage events.', 'text_domain'),
			'labels'                => $labels,
			'supports'              => array('title', 'thumbnail'),
			'taxonomies'            => array('category', 'post_tag'),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-calendar-alt',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
			'show_in_rest'          => true,
		);
		register_post_type('events', $args);
	}

	/**
	 * Sends mail to all event registrants.
	 * 
	 * @author  Chris Miller <chris@prolificdigital.com>
	 *
	 * @since    0.0.1
	 * 
	 * @param array $events The array of events to loop over.
	 * @param string $subject This subject line of the email.
	 * 
	 * @return void
	 */
	public function send_mail($events, $subject) {
		if ($events) {
			foreach ($events as $event) {
				setup_postdata($post);

				$post_id = $event->ID;

				$to = get_post_meta($post_id, 'registrants', true);
				$body = '<h1>' . get_the_title($post_id) . '</h1>' . '<p class="start_date">Start Date: ' . get_post_meta($post_id, 'start_date', true) . '</p>' . '<p class="start_time">Start Time:' . get_post_meta($post_id, 'start_time', true) . '</p>' . get_post_meta($post_id, 'description', true) . '<a href="#">View Event</a>';
				$headers = array('Content-Type: text/html; charset=UTF-8');

				wp_mail($to, $subject, $body, $headers);
			}
			wp_reset_postdata();
		}
	}

	/**
	 * Retrieves all events from a specified date.
	 * 
	 * @author  Chris Miller <chris@prolificdigital.com>
	 * 
	 * @since    0.0.1
	 * 
	 * @param string $time Expects the +2 day format.
	 * 
	 * @return array
	 */
	public function get_events($time) {
		global $post;

		$args = array(
			'post_type' => 'events',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'start_date',
					'type' => 'DATE',
					'value' => date('Y-m-d', strtotime($time)),
					'compare' => '=',
				),
			),
		);

		return $events = get_posts($args);
	}

	/**
	 * Notifies registrants of future events.
	 * 
	 * @author  Chris Miller <chris@prolificdigital.com>
	 * 
	 * @since    0.0.1
	 */
	public function notify_registrants() {
		$events = $this->get_events('+7 day');
		$this->send_mail($events, '1 Week Away!');

		$events = $this->get_events('+2 day');
		$this->send_mail($events, '2 Days Away!');
	}

	/**
	 * Creates a custom cron job for sending event notifications.
	 * 
	 * @author  Chris Miller <chris@prolificdigital.com>
	 * 
	 * @since    0.0.1
	 */
	public function custom_cron_job() {
		if (!wp_next_scheduled('event_notification')) {
			wp_schedule_event(current_time('timestamp'), 'daily', 'event_notification');
		}
	}
}


// Meta Box Class: Event Details
// Get the field value: $metavalue = get_post_meta( $post_id, $field_id, true );
// class eventdetailsMetabox {


	
// }

// if (class_exists('eventdetailsMetabox')) {
// 	new eventdetailsMetabox;
// };
