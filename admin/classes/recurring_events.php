<?php

/**
 * @author Dalton McGee
 */

use Ramsey\Uuid\Uuid;

class Recurring_Event {
  private $id;
  private $previous_recurring_status;
  private $previous_end_recurring;

  function generate_uuid($post_id, $post, $update) {
    if ($post->post_type === "event" && !$update) {
      update_field('sibling_shared_id', Uuid::uuid4()->toString(), $post_id);
    }
    return;
  }

  function set_fields($post_id) {
    $this->id = $post_id;
    $end_recurring = get_field("ends", $post_id);
    if ($end_recurring === '') {
      $end_recurring = date("Ymd", strtotime(get_field("start_date", $post_id)) + 31560000);
      update_field("ends", $end_recurring);
    }
    if ($this->previous_recurring_status !== null && $this->previous_recurring_status !== get_field("recurring", $post_id)) {
      update_field("recurring", $this->previous_recurring_status);
    }
  }

  function get_previous_statuses($post_id) {
    $this->previous_recurring_status = get_field("recurring", $post_id);
    $this->previous_end_recurring = get_field("ends", $post_id);
    return;
  }

  function create_recurring_event() {
    if ($this->previous_recurring_status === null) {
      $id = $this->id;
      $start_date = get_field("start_date", $id);
      $end_recurring = get_field("ends", $id);
      $recurring = get_field("recurring", $id);

      if ($recurring === "daily") {
        $this->create_events($id, "day", $start_date, $end_recurring);
      }

      if ($recurring === "weekly") {
        $this->create_events($id, "week", $start_date, $end_recurring);
      }

      if ($recurring === "monthly") {
        $this->create_events($id, "month", $start_date, $end_recurring);
      }

      if ($recurring === "yearly") {
        $this->create_events($id, "year", $start_date, $end_recurring);
      }
    }
    return;
  }

  function update_recurring_events() {
    $new_end_recurring = get_field("ends", $this->id);
    if ($this->previous_end_recurring !== null && $this->previous_end_recurring !== $new_end_recurring) {
      $extend_end_date = intval(get_field("ends", $this->id)) > intval($this->previous_end_recurring);
      $the_query = new WP_Query([
        'post_type' => 'event',
        'posts_per_page' => -1,
        'meta_key' => 'sibling_shared_id',
        'meta_value' => get_field('sibling_shared_id', $this->id),
      ]);
      if ($extend_end_date) {
        while ($the_query->have_posts()) {
          $the_query->the_post();
          update_field("ends", $new_end_recurring, get_the_ID());
        }

        $id = $the_query->posts[0]->ID;
        $start_date = get_field("start_date", $id);
        $recurring = get_field("recurring", $id);

        if ($recurring === "daily") {
          $this->create_events($id, "day", $start_date, $new_end_recurring);
        }

        if ($recurring === "weekly") {
          $this->create_events($id, "week", $start_date, $new_end_recurring);
        }

        if ($recurring === "monthly") {
          $this->create_events($id, "month", $start_date, $new_end_recurring);
        }

        if ($recurring === "yearly") {
          $this->create_events($id, "year", $start_date, $end_recurring);
        }
      } else {
        while ($the_query->have_posts()) {
          $the_query->the_post();
          if (intval(get_field("start_date", get_the_ID())) > intval($new_end_recurring)) {
            wp_delete_post(get_the_ID(), true);
          } else {
            update_field("ends", $new_end_recurring, get_the_ID());
          }
        }
      }
      wp_reset_postdata();
    }
    return;
  }

  function create_event_args($title) {
    $args = [
      'post_type' => 'event',
      'post_status' => 'publish',
      'post_title' => $title
    ];
    return $args;
  }

  function update_acf_fields($old_event_id, $new_event_id, $start_date) {
    update_field("start_date", $start_date, $new_event_id);
    update_field('start_time', get_field('start_time', $old_event_id), $new_event_id);
    update_field('end_time', get_field('end_time', $old_event_id), $new_event_id);
    update_field('recurring', get_field('recurring', $old_event_id), $new_event_id);
    update_field('which_days', get_field('which_days', $old_event_id), $new_event_id);
    update_field('ends', get_field('ends', $old_event_id), $new_event_id);
    update_field('description', get_field('description', $old_event_id), $new_event_id);
    update_field('registration_link', get_field('registration_link', $old_event_id), $new_event_id);
    update_field('zoom_id', get_field('zoom_id', $old_event_id), $new_event_id);
    update_field('sibling_shared_id', get_field('sibling_shared_id', $old_event_id), $new_event_id);
  }

  function create_events($id, $increment, $start_date, $end_recurring) {
    $init_date = new DateTime();
    $init_date->setTimestamp(strtotime($start_date))->modify("+1 $increment");
    while ($init_date->getTimeStamp() <= strtotime($end_recurring)) {
      $args = $this->create_event_args(get_the_title(get_post($id)));
      $new_id = wp_insert_post($args);
      $this->update_acf_fields($id, $new_id, $init_date->format("Ymd"));
      $init_date->modify("+1 $increment");
    }
    return;
  }
}
