<?php
require plugin_dir_path(__DIR__) . '../vendor/autoload.php';

use Ramsey\Uuid\Uuid;

/**
 * A class for handling the recurring events feature.
 *
 * @author Dalton McGee
 * @author Chris Miller
 * @since     0.0.1
 */
class Recurring_Event {

  function __construct() {
    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (get_post_status($post_id) != 'trash') {
      return $post_id;
    }
  }

  public function get_event_series($start_date, $end_series, $repeats) {

    $rule = (new \Recurr\Rule)
      ->setStartDate(new \DateTime($start_date))
      ->setTimezone('America/New_York')
      ->setFreq($repeats)
      // ->setByDay(['MO', 'TU'])
      ->setUntil(new \DateTime($end_series));

    echo $rule->getString();
    $transformer = new \Recurr\Transformer\ArrayTransformer();

    $event_series = $transformer->transform($rule);

    return $event_series;
  }

  public function create_series($post_id) {

    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (get_post_status($post_id) == 'trash') {
      return $post_id;
    }

    $uuid = get_post_meta($post_id, 'series_id', true);

    // Check to see if UUID is blank — if so, this will start a new event series.
    if (empty($uuid)) {

      // Checking to see if recurring events option is set.
      $repeats = get_post_meta($post_id, 'repeats', true);
      if ($repeats != "never" && $repeats != NULL) {

        // Getting all of the field data from the post.
        $post_meta = $this->get_fields($post_id);

        // Creating an array with the recurring dates.
        $event_series = $this->get_event_series($post_meta['start_date'], $post_meta['end_series'], $post_meta['repeats']);

        // Creating a UUID for recurring events — building a relationship.
        $uuid = Uuid::uuid4()->toString();

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
            update_post_meta($post_id, 'start_date', $start_date->format('Y-m-d'));
            continue;
          }

          // Creating new posts — new post ID is being returned in the function below.
          $inserted_post_id = wp_insert_post($args);

          // Updating all fields to match across the series.
          $this->update_fields($post_id, $inserted_post_id, $start_date->format('Y-m-d'), $uuid);
        }

        // Adding the hook back.
        add_action('save_post', [$this, 'create_series']);
      }
    }

    return $post_id;
  }

  public function update_series($post_id) {

    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (get_post_status($post_id) == 'trash') {
      return $post_id;
    }

    // Checking to make sure there's an existing series.
    $uuid = get_post_meta($post_id, 'series_id', true);

    if (!empty($uuid)) {

      // Getting all of the field data from the post.
      $post_meta = $this->get_fields($post_id);

      // Creating an array with the recurring dates.
      $event_series = $this->get_event_series($post_meta['start_date'], $post_meta['end_series'], $post_meta['repeats']);

      $args = array(
        'post_type' => 'events',
        'posts_per_page' => -1,
        'orderby' => 'meta_value_datetime',
        'order' => 'ASC',
        'meta_key'  => 'start_date',
        'meta_query' => array(
          'relation' => 'AND',
          array(
            'key'     => 'start_date',
            'value'   => $post_meta['start_date'],
            'compare' => '>=',
            'type' => 'DATE',
          ),
          array(
            'key'     => 'series_id',
            'value'   => $uuid,
            'compare' => '=',
          ),
        ),
      );

      $events = get_posts($args);

      // Removing the hook to prevent an infinite loop.
      remove_action('save_post', [$this, 'update_series']);

      foreach ($event_series as $key => $value) {

        wp_update_post([
          'ID' => $events[$key]->ID,
          'post_title' => get_the_title($post_id)
        ]);

        $start_date = $value->getStart();
        update_post_meta($events[$key]->ID, 'start_date', $start_date->format('Y-m-d'));
      }

      // Adding the hook back.
      add_action('save_post', [$this, 'update_series']);
    }

    return $post_id;
  }

  public function get_fields($post_id) {

    $post_meta = get_post_meta($post_id);

    $fields = [];

    foreach ($post_meta as $key => $value) {
      $fields[$key] = $value[0];
    }

    return $fields;
  }

  function update_fields($post_id, $inserted_post_id, $start_date, $uuid) {

    // Associate the series by adding the originating ID (parent ID) and the UUID (series ID).
    update_post_meta($post_id, 'parent_id', $post_id);
    update_post_meta($post_id, 'series_id', $uuid);
    update_post_meta($inserted_post_id, 'parent_id', $post_id);
    update_post_meta($inserted_post_id, 'series_id', $uuid);

    update_post_meta($inserted_post_id, 'start_date', $start_date);

    // Getting all fields from the parent event.
    $post_meta = $this->get_fields($post_id);

    // Defining fields that we want to ignore.
    $remove_fields = [
      'start_date'
    ];

    foreach ($post_meta as $key => $value) {

      // If we find a field that we want to ignore, skip it.
      if (in_array($key, $remove_fields)) {
        continue;
      }

      update_post_meta($inserted_post_id, $key, $value);
    }
  }
}
