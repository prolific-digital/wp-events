<?php
require plugin_dir_path(__DIR__) . '../vendor/autoload.php';

use Ramsey\Uuid\Uuid;

/**
 * A class for handling the recurring events feature.
 *
 * @author Chris Miller
 * @since     0.0.1
 */
class Recurring_Event {

  /**
   * Creates a recurring set of dates based on the recurr library.
   *
   * @author Chris Miller
   * @since     0.0.1
   * 
   * @param integer $post_id The current post that's being edited.
   * 
   * @return array $event_series An array of recurring dates.
   */
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

    $transformer = new \Recurr\Transformer\ArrayTransformer();

    $event_series = $transformer->transform($rule);

    return $event_series;
  }

  /**
   * Creates event posts based on a set of recurring rules.
   *
   * @author Chris Miller
   * @since     0.0.1
   * 
   * @param integer $post_id The current post that's being edited.
   * 
   * @return integer $post_id Returns the edited post ID.
   */
  public function create_series($post_id) {

    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (get_post_status($post_id) == 'trash') {
      return $post_id;
    }

    // Getting all of the field data from the post.
    $post_meta = get_fields($post_id);

    // Check to see if UUID is blank — if so, this will start a new event series.
    if (empty($post_meta['series_id'])) {

      if ($post_meta['series_repeat']) {

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
          $this->update_fields($inserted_post_id, $post_meta, $start_date->format('Y-m-d'));
        }

        // Adding the hook back.
        add_action('save_post', [$this, 'create_series']);
      }
    }

    return $post_id;
  }

  /**
   * Updates the event series when an edit occurrs, all events in the
   * event series will be deleted and a new series will take its place.
   *
   * @author Chris Miller
   * @since     0.0.1
   * 
   * @param integer $post_id The current post that's being edited.
   * 
   * @return integer $post_id Returns the edited post ID.
   */
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
          $this->update_fields($inserted_post_id, $post_meta, $start_date->format('Y-m-d'));
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
   * 
   * @param integer $post_id The post ID of the newly inserted post.
   * @param array $post_meta The parent post meta data.
   * @param string $start_date The newly created start date from the recurring series.
   * 
   * @return void
   */
  function update_fields($post_id, $post_meta, $start_date) {

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
}
