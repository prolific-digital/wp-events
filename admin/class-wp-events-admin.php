<?php

// Define path and URL to the ACF plugin.
define('MY_ACF_PATH', plugin_dir_path(__DIR__) .  '/includes/acf/');
define('MY_ACF_URL', plugin_dir_url(__DIR__) .  '/includes/acf/');

// Include the ACF plugin.
if (!class_exists('acf')) {
	include_once(MY_ACF_PATH . 'acf.php');
}
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
class Wp_Events_Admin
{

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
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->define_hooks();
	}

	function my_acf_settings_url($url)
	{
		return MY_ACF_URL;
	}

	function my_acf_settings_show_admin($show_admin)
	{
		return true;
	}

	protected function define_hooks()
	{
		add_action('acf/input/admin_enqueue_scripts', function () {
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-events-admin.js', array('jquery'), $this->version, false);
		});
		add_filter('acf/load_field/key=field_6191ae91bb5b0', function ($field) {
			$post_info = get_fields();
			if ($post_info) {
				$start_day = (new DateTime($post_info['start_date']))->format('w');
				$series_type = $post_info['series_repeat'];
				$parent_id = $post_info['parent_id'];
				$is_parent = (int)$parent_id == get_the_ID();
				$message_init = "<p>This is a $series_type event";
				$weekdays = ['SU'=>"Sunday", 'MO'=>"Monday", 'TU'=>"Tuesday", 'WE'=>"Wednesday", 'TH'=>"Thursday", 'FR'=>"Friday", 'SA'=>"Saturday"];
				if (array_key_exists('repeats_on_monthly_custom', $post_info) && $post_info['repeats_on_monthly_custom']) {
					$monthly = $post_info['repeats_on_monthly_custom'];
					$ordinals = ['1st', '2nd', '3rd', '4th', '5th'];
					$message_append = ' that repeats on the ';
					foreach ($monthly as $key => $week) {
						$ordinal = $ordinals[$week - 1];
						if ($key === 0) {
							$message_append .= $ordinal;
						} else if (array_key_last($monthly) === $key) {
							$message_append .= " and $ordinal";
						} else {
							$message_append .= ", $ordinal";
						}
					};
					$message_append .= " " . array_values($weekdays)[$start_day] . " of the month";
					$message_init .= $message_append;
				}
				if (array_key_exists('repeats_on_weekly', $post_info) && $post_info['repeats_on_weekly']) {
					$weekly = $post_info['repeats_on_weekly'];
					$message_append = ' that repeats on ';
					foreach ($weekly as $key => $day) {
						$weekday = $weekdays[$day];
						if ($key === 0) {
							$message_append .= $weekday;
						} else if (array_key_last($weekly) === $key) {
							$message_append .= " and $weekday";
						} else {
							$message_append .= ", $weekday";
						}
					};
					$message_append .= ' each week';
					$message_init .= $message_append;
				}
				if ($is_parent) {
					$field['message'] = "$message_init. This is the initial event.";
				} else {
					$field['message'] = "$message_init. The initial event can be found <a href='http://demosite.local/wp-admin/post.php?post=$parent_id&action=edit'>here</a>.</p>";
				}
				return $field;
			}
		});
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
	public function events()
	{
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
			'has_archive'           => false,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
			'show_in_rest'          => true,
		);
		register_post_type('events', $args);
	}
}
