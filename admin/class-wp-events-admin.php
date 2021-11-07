<?php

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

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Events_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Events_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-events-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Events_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Events_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-events-admin.js', array('jquery'), $this->version, false);
	}

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

	// public function send_notification() {

	// 	$post_id = get_the_ID();

	// 	$to = get_post_meta($post_id, 'registrants', true);
	// 	$subject = get_the_title($post_id);
	// 	$body = '<h1>' . get_the_title($post_id) . '</h1>' . '<p class="start_date">Start Date: ' . get_post_meta($post_id, 'start_date', true) . '</p>' . '<p class="start_time">Start Time:' . get_post_meta($post_id, 'start_time', true) . '</p>' . get_post_meta($post_id, 'description', true) . '<a href="#">View Event</a>';
	// 	$headers = array('Content-Type: text/html; charset=UTF-8');

	// 	wp_mail($to, $subject, $body, $headers);
	// }

	public function send_mail($events, $subject) {
		if ($events) {
			foreach ($events as $event) {
				setup_postdata($post);

				$post_id = $event->ID;

				$to = get_post_meta($post_id, 'registrants', true);
				// $subject = get_the_title($post_id);
				// $subject = 'Event is one week away!';
				$body = '<h1>' . get_the_title($post_id) . '</h1>' . '<p class="start_date">Start Date: ' . get_post_meta($post_id, 'start_date', true) . '</p>' . '<p class="start_time">Start Time:' . get_post_meta($post_id, 'start_time', true) . '</p>' . get_post_meta($post_id, 'description', true) . '<a href="#">View Event</a>';
				$headers = array('Content-Type: text/html; charset=UTF-8');

				wp_mail($to, $subject, $body, $headers);
			}
			wp_reset_postdata();
		}
	}

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

	public function notify_registrants() {
		$events = $this->get_events('+7 day');
		$this->send_mail($events, '1 Week Away!');

		$events = $this->get_events('+2 day');
		$this->send_mail($events, '2 Days Away!');
	}

	public function custom_cron_job() {
		if (!wp_next_scheduled('event_notification')) {
			wp_schedule_event(current_time('timestamp'), 'daily', 'event_notification');
		}
	}
}





















// Meta Box Class: Event Details
// Get the field value: $metavalue = get_post_meta( $post_id, $field_id, true );
class eventdetailsMetabox {

	private $screen = array(
		'events',
	);

	private $meta_fields = array(
		array(
			'label' => 'Start Date',
			'id' => 'start_date',
			'type' => 'date',
		),
		array(
			'label' => 'Start time',
			'id' => 'start_time',
			'type' => 'time',
		),
		array(
			'label' => 'End Time',
			'id' => 'end_time',
			'type' => 'time',
		),
		array(
			'label' => 'Repeats',
			'id' => 'repeats',
			'type' => 'select',
			'options' => [
				'Never',
				'Day',
				'Week',
				'Month',
				'Year'
			]
		),
		array(
			'label' => 'Description',
			'id' => 'description',
			'type' => 'wysiwyg',
		),
		array(
			'label' => 'Registration Link',
			'id' => 'registration_link',
			'type' => 'url',
		),
		array(
			'label' => 'Zoom ID',
			'id' => 'zoom_id',
			'type' => 'text',
		),
		array(
			'label' => 'Sibling Shared ID',
			'id' => 'end_date',
			'type' => 'text',
		),
		array(
			'label' => 'Zoom URL',
			'id' => 'zoom_url',
			'type' => 'url',
		),
		array(
			'label' => 'Registrants',
			'id' => 'registrants',
			'type' => 'textarea',
		),
	);

	public function __construct() {
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('save_post', array($this, 'save_fields'));
	}

	public function add_meta_boxes() {
		foreach ($this->screen as $single_screen) {
			add_meta_box(
				'eventdetails',
				__('Event Details', 'textdomain'),
				array($this, 'meta_box_callback'),
				$single_screen,
				'normal',
				'core'
			);
		}
	}

	public function meta_box_callback($post) {
		wp_nonce_field('eventdetails_data', 'eventdetails_nonce');
		$this->field_generator($post);
	}

	public function field_generator($post) {
		$output = '';
		$attr = '';
		// Setting field to readonly
		if ($meta_field['id'] = "registrants") {
			$attr = ' readonly';
		}

		foreach ($this->meta_fields as $meta_field) {
			$label = '<label for="' . $meta_field['id'] . '">' . $meta_field['label'] . '</label>';
			$meta_value = get_post_meta($post->ID, $meta_field['id'], true);
			if (empty($meta_value)) {
				if (isset($meta_field['default'])) {
					$meta_value = $meta_field['default'];
				}
			}
			switch ($meta_field['type']) {
				case 'textarea':
					$input = sprintf(
						'<textarea style="width: 100%%" id="%s" name="%s" rows="5"' . $attr . '>%s</textarea>',
						$meta_field['id'],
						$meta_field['id'],
						$meta_value
					);
					break;
				case 'wysiwyg':
					ob_start();
					wp_editor($meta_value, $meta_field['id']);
					$input = ob_get_contents();
					ob_end_clean();
					break;
				case 'select':
					$input = sprintf(
						'<select id="%s" name="%s">',
						$meta_field['id'],
						$meta_field['id']
					);
					foreach ($meta_field['options'] as $key => $value) {
						$meta_field_value = !is_numeric($key) ? $key : $value;
						$input .= sprintf(
							'<option %s value="%s">%s</option>',
							$meta_value === $meta_field_value ? 'selected' : '',
							$meta_field_value,
							$value
						);
					}
					$input .= '</select>';
					break;
				default:
					$input = sprintf(
						'<input %s id="%s" name="%s" type="%s" value="%s">',
						$meta_field['type'] !== 'color' ? 'style="width: 100%"' : '',
						$meta_field['id'],
						$meta_field['id'],
						$meta_field['type'],
						$meta_value
					);
			}
			$output .= $this->format_rows($label, $input);
		}
		echo '<table class="form-table"><tbody>' . $output . '</tbody></table>';
	}

	public function format_rows($label, $input) {
		return '<tr><th>' . $label . '</th><td>' . $input . '</td></tr>';
	}

	public function save_fields($post_id) {
		if (!isset($_POST['eventdetails_nonce']))
			return $post_id;
		$nonce = $_POST['eventdetails_nonce'];
		if (!wp_verify_nonce($nonce, 'eventdetails_data'))
			return $post_id;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return $post_id;
		foreach ($this->meta_fields as $meta_field) {
			if (isset($_POST[$meta_field['id']])) {
				switch ($meta_field['type']) {
					case 'email':
						$_POST[$meta_field['id']] = sanitize_email($_POST[$meta_field['id']]);
						break;
					case 'text':
						$_POST[$meta_field['id']] = sanitize_text_field($_POST[$meta_field['id']]);
						break;
				}
				update_post_meta($post_id, $meta_field['id'], $_POST[$meta_field['id']]);
			} else if ($meta_field['type'] === 'checkbox') {
				update_post_meta($post_id, $meta_field['id'], '0');
			}
		}
	}
}

if (class_exists('eventdetailsMetabox')) {
	new eventdetailsMetabox;
};
