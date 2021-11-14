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

  public function get_event_series($post_id) {

    $post_meta = get_fields($post_id);

    $args = array(
      'FREQ' => $post_meta['series_repeat'],
      'UNTIL' => $post_meta['end_series'],
    );

    if ($post_meta['repeats_on']) {
      $days = implode(',', $post_meta['repeats_on']);
      $args['BYDAY'] = $days;
    }

    $timezone    = 'America/New_York';
    $startDate   = new \DateTime($post_meta['start_date'], new \DateTimeZone($timezone));
    $rule        = new \Recurr\Rule($args, $startDate, $timezone);

    // echo $rule->getString();
    $transformer = new \Recurr\Transformer\ArrayTransformer();

    $event_series = $transformer->transform($rule);

    return $event_series;
  }

  public function create_series($post_id) {

    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (get_post_status($post_id) == 'trash') {
      return $post_id;
    }

    // Getting all of the field data from the post.
    $post_meta = get_fields($post_id);

    // Check to see if UUID is blank — if so, this will start a new event series.
    if (empty($post_meta['series_id'])) {

      // Checking to see if recurring events option is set.
      // $repeats = get_field('series_repeat', $post_id);
      // $repeats_on = get_field('repeats_on', $repeats);

      if ($post_meta['series_repeat']) {

        // Creating an array with the recurring dates.
        $event_series = $this->get_event_series($post_id);

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
            update_field('start_date', $start_date->format('Y-m-d'), $post_id);
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

    // Getting all of the field data from the post.
    $post_meta = get_fields($post_id);

    if (!empty($post_meta['series_id'])) {

      // Creating an array with the recurring dates.
      $event_series = $this->get_event_series($post_id);

      // die(var_dump($event_series));

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
            'value'   => $post_meta['series_id'],
            'compare' => '=',
          ),
        ),
      );

      $events = get_posts($args);

      // die(var_dump($events));
      // die(var_dump($event_series));

      // Removing the hook to prevent an infinite loop.
      remove_action('save_post', [$this, 'update_series']);

      foreach ($events as $key => $value) {
        if ($value->ID != $post_meta['parent_id']) {
          wp_delete_post($value->ID, true);
        }
      }

      foreach ($event_series as $key => $value) {

        $start_date = $value->getStart();

        if ($events[$key]->ID == $post_meta['parent_id']) {
          wp_update_post([
            'ID' => $events[$key]->ID,
            'post_title' => get_the_title($post_id)
          ]);
          update_field('start_date', $start_date->format('Y-m-d'), $events[$key]->ID);
        } else {
          $args = [
            'post_type' => 'events',
            'post_status' => 'publish',
            'post_title' => get_the_title($post_id)
          ];
          // Creating new posts — new post ID is being returned in the function below.
          $inserted_post_id = wp_insert_post($args);

          // Updating all fields to match across the series.
          $this->update_fields($post_id, $inserted_post_id, $start_date->format('Y-m-d'), $post_meta['series_id']);
        }
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
    update_field('parent_id', $post_id, $post_id);
    update_field('series_id', $uuid, $post_id);
    update_field('parent_id', $post_id, $inserted_post_id);
    update_field('series_id', $uuid, $inserted_post_id);

    update_field('start_date', $start_date, $inserted_post_id);

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
