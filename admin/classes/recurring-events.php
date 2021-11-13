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

      $start_date = get_post_meta($post_id, 'start_date', true);
      $end_series = get_post_meta($post_id, 'end_series', true);
      $repeats = get_post_meta($post_id, 'repeats', true);
      $event_series = $this->get_event_series($start_date, $end_series, $repeats);

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
            'value'   => $start_date,
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

    $parent_id = get_post_meta($inserted_post_id, 'parent_id');

    // $end_recurring = get_post_meta($post->ID, 'ends', true);

    update_post_meta($inserted_post_id, "start_date", $start_date);

    update_post_meta($inserted_post_id, 'series_id', $uuid);
    update_post_meta($post_id, 'series_id', $uuid);

    update_post_meta($inserted_post_id, 'parent_id', $post_id);
    update_post_meta($post_id, 'parent_id', $post_id);

    // update_post_meta($post->ID, "start_date", $start_date, $new_event_id);
    // update_post_meta($post->ID, 'start_time', get_field('start_time', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'end_time', get_field('end_time', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'recurring', get_field('recurring', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'which_days', get_field('which_days', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'ends', get_field('ends', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'description', get_field('description', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'registration_link', get_field('registration_link', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'zoom_id', get_field('zoom_id', $old_event_id), $new_event_id);
    // update_post_meta($post->ID, 'series_id', get_field('series_id', $old_event_id), $new_event_id);
  }
}
