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

  function create_event($post_id) {

    $this->create_series($post_id);

    /**
     * Work in progress — need to find out why it changes 
     * the date to the date when updating the post.
     */
    // $this->update_series($post_id);

    return $post_id;
  }

  public function get_recurring_dates($start_date) {
    $timezone    = 'America/New_York';
    $startDate   = new \DateTime($start_date, new \DateTimeZone($timezone));
    $endDate     = new \DateTime('2021-12-30 20:00:00', new \DateTimeZone($timezone)); // Optional
    // $rule        = new \Recurr\Rule('FREQ=MONTHLY;COUNT=5', $startDate, $endDate, $timezone);

    $rule = (new \Recurr\Rule)
      ->setStartDate($startDate)
      ->setTimezone($timezone)
      ->setFreq('DAILY')
      ->setByDay(['MO', 'TU'])
      ->setUntil(new \DateTime('2021-11-31'));

    echo $rule->getString(); //FREQ=DAILY;UNTIL=20171231T000000;BYDAY=MO,TU
    $transformer = new \Recurr\Transformer\ArrayTransformer();

    $collection = $transformer->transform($rule);

    return $collection;
  }

  public function create_series($post_id) {
    // The save_post action is triggered when deleting event — this prevents anything from happening.
    if (get_post_status($post_id) != 'trash') {

      $uuid = get_post_meta($post_id, 'series_id', true);

      // Check to see if UUID is blank — if so, this will start a new event series.
      if (empty($uuid)) {

        // Checking to see if recurring events option is set.
        $recurring = get_post_meta($post_id, 'repeats', true);
        if ($recurring != "never" && $recurring != NULL) {

          $start_date = get_post_meta($post_id, 'start_date', true);

          $collection = $this->get_recurring_dates($start_date);

          // Creating a UUID for repeating events that require a relationship.
          $uuid = Uuid::uuid4()->toString();

          // Removing the hook to prevent an infinite loop.
          remove_action('save_post', [$this, 'create_event']);

          $args = [
            'post_type' => 'events',
            'post_status' => 'publish',
            'post_title' => get_the_title($post_id)
          ];

          foreach ($collection as $key => $value) {

            $inserted_post_id = wp_insert_post($args);

            $start_date = $value->getStart();
            $this->update_fields($post_id, $inserted_post_id, $start_date->format('Y-m-d'), $uuid);
          }

          // Adding the hook back.
          add_action('save_post', [$this, 'create_event']);
        }
      }
    }
  }


  function create_event_args($title) {
    $args = [
      'post_type' => 'events',
      'post_status' => 'publish',
      'post_title' => $title
    ];
    return $args;
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


  public function update_series($post_id) {

    // Checking to make sure there's an existing series.
    $uuid = get_post_meta($post_id, 'series_id', true);

    if (!empty($uuid)) {

      $start_date = get_post_meta($post_id, 'start_date', true);

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

      $collection = $this->get_recurring_dates($start_date);

      // Removing the hook to prevent an infinite loop.
      remove_action('edit_post', [$this, 'update_series']);

      foreach ($collection as $key => $value) {

        wp_update_post([
          'ID' => $events[$key]->ID,
          'post_title' => get_the_title($post_id)
        ]);

        $start_date = $value->getStart();
        update_post_meta($events[$key]->ID, 'start_date', $start_date->format('Y-m-d'));
      }

      // Adding the hook back.
      add_action('edit_post', [$this, 'update_series']);
    }

    return;
  }
}
