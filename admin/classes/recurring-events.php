<?php
require plugin_dir_path(__DIR__) . '../vendor/autoload.php';

use Ramsey\Uuid\Uuid;

/**
 * A class for handling the recurring events feature.
 *
 * @author Chris Miller
 * @since     0.0.1
 * @author Dalton McGee
 * @since     0.0.2
 */
class Recurring_Event {

  /**
   * Creates a recurring set of dates based on the recurr library.
   *
   * @author Chris Miller
   * @since     0.0.1
   * @author Dalton McGee
   * @since     0.0.2
   *
   * @param integer $post_id The current post that's being edited.
   *
   * @return array $event_series An array of recurring dates.
   */

  // Variable to track various if various fields have changed
  private $previous_end_series;
  private $previous_repeats_on;
  private $previous_series_repeat;

  public function get_event_series($post_id) {

    $post_meta = get_fields($post_id);

    $end_date = (new DateTime($post_meta['end_series']))->modify('+1 day')->format("Ymd");

    $args = array(
      'FREQ' => $post_meta['series_repeat'],
      'UNTIL' => (string)$end_date,
    );

    $weekdays = ["SU", "MO", "TU", "WE", "TH", "FR", "SA"];

    if (array_key_exists('repeats_on_weekly', $post_meta) && $post_meta['repeats_on_weekly']) {
      $days = implode(',', $post_meta['repeats_on_weekly']);
      $args['BYDAY'] = $days;
    }
    if (array_key_exists('repeats_on_monthly', $post_meta) && $post_meta['repeats_on_monthly']) {
      if ($post_meta['repeats_on_monthly'] === "CUSTOM") {
        $days = $post_meta['repeats_on_monthly_custom'];
        $formatted_days = "";
        $day_of_week = $weekdays[date('w', strtotime($post_meta['start_date']))];
        foreach ($days as $key => $day) {
          $arg = $day . $day_of_week . ',';
          if ($key === array_key_last($days)) $arg = $day . $day_of_week;
          $formatted_days .= $arg;
        }
        $args['BYDAY'] = $formatted_days;
      }
    }

    $timezone    = 'America/New_York';
    $startDate   = (new \DateTime($post_meta['start_date'], new \DateTimeZone($timezone)));
    $rule        = new \Recurr\Rule($args, $startDate, $timezone);

    $transformer = new \Recurr\Transformer\ArrayTransformer();

    $event_series = $transformer->transform($rule);

    return $event_series;
  }

  /**
   * Creates event posts based on a set of recurring rules.
   *
   * @author Chris Miller
   * @since     0.0.1
   * @author Dalton McGee
   * @since     0.0.2
   *
   * @param integer $post_id The current post that's being edited.
   *
   * @return integer $post_id Returns the edited post ID.
   */
  public function create_series($post_id) {

    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (in_array(get_post_status($post_id), ['trash', 'draft'])) {
      return $post_id;
    }

    // Getting all of the field data from the post.
    $post_meta = get_fields($post_id);

    if (empty($post_meta['series_id']) && $post_meta['series_repeat'] && $post_meta['series_repeat'] !== 'none') {

      // Creating an array with the recurring dates.
      $event_series = $this->get_event_series($post_id);

      // Creating a UUID for recurring events — building a relationship.
      $uuid = Uuid::uuid4()->toString();

      update_field('parent_id', $post_id, $post_id);
      update_field('series_id', $uuid, $post_id);

      // Getting all fields from the parent event.
      $post_meta = get_fields($post_id);

      $args = [
        'post_type' => 'events',
        'post_status' => 'publish',
        'post_title' => get_the_title($post_id)
      ];

      // Removing the hook temporarily to prevent an infinite loop.
      remove_action('save_post', [$this, 'create_series']);

      foreach ($event_series as $key => $value) {

        $start_date = $value->getStart();

        // Assigning the first date in the series to the current post that's being edited.
        if ($key == 0) {
          update_field('start_date', $start_date->format('Y-m-d'), $post_id);
          continue;
        }

        // Creating new posts — new post ID is being returned in the function below.
        $inserted_post_id = wp_insert_post($args);

        // Updating all fields to match across the series.
        $this->update_metadata_fields($post_id, $inserted_post_id);
        $this->update_fields($inserted_post_id, $post_meta, $start_date->format('Y-m-d'));
      }

      // Adding the hook back.
      add_action('save_post', [$this, 'create_series']);
    }

    return $post_id;
  }

  public function extend_series($post_id) {

    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (in_array(get_post_status($post_id), ['trash', 'draft'])) {
      return $post_id;
    }

    // Getting all of the field data from the post.
    $post_meta = get_fields($post_id);

    $the_query = new WP_Query([
      'post_type' => 'events',
      'posts_per_page' => -1,
      'meta_key' => 'series_id',
      'meta_value' => get_field('series_id', $post_id),
    ]);

    // Update all the previously existing events
    foreach ($the_query->get_posts() as $event) {
      foreach ($post_meta as $field => $value) {
        if (!in_array($field, ['registrants', 'start_date', 'series_repeat', 'repeats_on', 'notify_registrants'])) {
          update_field($field, $value, $event->ID);
          wp_update_post(['post_title' => get_the_title($post_id), 'ID' => $event->ID]);
        }
      }
    }

    // Create the new events based on the date of the last event
    $event_series = $this->get_event_series(end($the_query->get_posts())->ID);
    $new_event_args = [
      'post_type' => 'events',
      'post_status' => 'publish',
      'post_title' => get_the_title($post_id)
    ];
    foreach ($event_series as $key => $value) {
      $start_date = $value->getStart();

      // Ignore the first key as it is functioning as the parent;
      if ($key == 0) {
        continue;
      }

      // Creating new posts — new post ID is being returned in the function below.
      $inserted_post_id = wp_insert_post($new_event_args);
      // Updating all relevant meta fields in the new posts.
      update_field('start_date', $start_date->format('Y-m-d'), $inserted_post_id);
      $this->update_metadata_fields($post_id, $inserted_post_id);
      foreach ($post_meta as $field => $value) {
        if (!in_array($field, ['registrants', 'start_date'])) {
          update_field($field, $value, $inserted_post_id);
        }
      }
    }

    return $post_id;
  }

  /**
   * Updates the event when its end series date is extended.
   *
   * @author Dalton McGee
   * @since     0.0.2
   *
   * @param integer $post_id The current post that's being edited.
   *
   * @return integer $post_id Returns the edited post ID.
   */
  public function update_series($post_id) {

    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (in_array(get_post_status($post_id), ['trash', 'draft'])) {
      return $post_id;
    }
    // Getting all of the field data from the post.
    $post_meta = get_fields($post_id);

    if (!empty($post_meta['series_id'])) {

      // Creating an array with the recurring dates.
      // $event_series = $this->get_event_series($post_id);
      $args = array(
        'post_type' => 'events',
        'posts_per_page' => -1,
        'orderby' => 'meta_value_datetime',
        'order' => 'ASC',
        'meta_key'  => 'start_date',
        'meta_query' => array(
          array(
            'key'     => 'series_id',
            'value'   => $post_meta['series_id'],
            'compare' => '=',
          ),
        ),
      );

      $events = get_posts($args);
      $new_end_series = strtotime($post_meta['end_series']);
      $previous_end_series = strtotime($this->previous_end_series);

      // Removing the hook to prevent an infinite loop.
      remove_action('save_post', [$this, 'update_series']);

      if ($previous_end_series != null && $new_end_series > $previous_end_series) {
        $this->extend_series($post_id);
      } else {
        // Updates all events to match changes on all fields except:
        // [registrants, start_date, series_repeat, repeats_on]
        foreach ($events as $event) {
          if (!get_field('zoom_id', $event->ID) && $new_end_series <= strtotime(get_field('start_date', $event->ID))) {
            wp_delete_post($event->ID);
          } else {
            $this->update_metadata_fields($post_id, $event->ID);
            foreach ($post_meta as $field => $value) {
              if (!in_array($field, ['registrants', 'start_date', 'series_repeat', 'repeats_on', 'notify_registrants', 'zoom_id'])) {
                update_field($field, $value, $event->ID);
                wp_update_post(['post_title' => get_the_title($post_id), 'ID' => $event->ID]);
              }
            }
          }
        }
      }

      // Adding the hook back.
      add_action('save_post', [$this, 'update_series']);
    }

    return $post_id;
  }

  /**
   * Updating all fields on newly created recurring events in the series.
   *
   * @author Chris Miller
   * @since     0.0.1
   * @author Dalton McGee
   * @since     0.0.2
   *
   * @param integer $post_id The post ID of the newly inserted post.
   * @param array $post_meta The parent post meta data.
   * @param string $start_date The newly created start date from the recurring series.
   *
   * @return void
   */
  protected function update_fields($post_id, $post_meta, $start_date) {

    update_field('start_date', $start_date, $post_id);

    // Defining fields that we want to ignore.
    $remove_fields = [
      'start_date'
    ];

    foreach ($post_meta as $key => $value) {

      // If we find a field that we want to ignore, skip it.
      if (in_array($key, $remove_fields)) {
        continue;
      }

      update_field($key, $value, $post_id);
    }
  }

  protected function update_metadata_fields($post_id, $event_id) {
    $old_categories = get_the_terms($post_id, 'topic');
    $new_categories = [];
    $old_tags = get_the_tags($post_id);
    $new_tags = [];
    if ($old_categories) {
      foreach ($old_categories as $category) {
        array_push($new_categories, $category->term_id);
      }
      wp_set_post_terms($event_id, $new_categories, 'topic');
    }
    if ($old_tags) {
      foreach ($old_tags as $tag) {
        array_push($new_tags, $tag->name);
      }
    }
    if (get_post_thumbnail_id($post_id)) {
      set_post_thumbnail($event_id, get_post_thumbnail_id($post_id));
    } else {
      delete_post_thumbnail($event_id);
    }
    wp_set_post_categories($event_id, $new_categories);
    wp_set_post_tags($event_id, $new_tags);
    return;
  }

  public function add_registrants_middleware($form_data) {
    // Initialize variables;
    $email_id;
    $post_id;

    // Look through the form data for appropriate fields.
    // If fields exist, set respective variables to the values.
    foreach ($form_data['fields'] as $datum) {
      if ($datum['label'] === "Email") {
        $email_id = $datum['id'];
      }
      if ($datum['label'] === "post_id") {
        $post_id = $datum['id'];
      }
    }
    $email = $_POST["input_$email_id"];

    $this->insert_registrants($email, $_POST["input_$post_id"]);
    return;
  }

  static function insert_registrants($email, $post_id) {
    // If there are not current registrants, set the variable to email.
    // If there are registrants, append email to the end.
    $current_registrants = get_field('registrants', $post_id);
    $updated_registrants_csv =  empty($current_registrants) ? $email : "$current_registrants, $email";
    update_field('registrants', $updated_registrants_csv, $post_id);
    return;
  }

  function get_previous_statuses($post_id) {
    $this->previous_end_series = get_field("end_series", $post_id);
    $this->previous_repeats_on = get_field("repeats_on", $post_id);
    $this->previous_series_repeat = get_field("series_repeat", $post_id);
    return;
  }
}
