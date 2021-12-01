<?php

class EventNotifications {
  /**
   * Sends mail to all event registrants.
   *
   * @author  Chris Miller <chris@prolificdigital.com>
   *
   * @since    0.0.1
   *
   * @param array 	$events 		The array of events to loop over.
   * @param string 	$subject 		This subject line of the email.
   *
   * @return void
   */
  public function send_mail($events, $topic) {
    if ($events) {
      foreach ($events as $event) {
        $post_id = $event->ID;
        $registrants = get_field('registrants', $event->ID);

        // If there are any registrants, send an email to them.
        if ($registrants) {
          $to = explode(',',$registrants);
          $subject = $topic;
          $body = '<h1>' . get_the_title($post_id) . '</h1>' . '<p class="start_date">Start Date: ' . get_post_meta($post_id, 'start_date', true) . '</p>' . '<p class="start_time">Start Time:' . get_post_meta($post_id, 'start_time', true) . '</p>' . get_post_meta($post_id, 'description', true) . '<a href="#">View Event</a>';
          $headers = array('Content-Type: text/html; charset=UTF-8');

          $sent = wp_mail($to, $subject, $body, $headers);
        }
      }
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
    date_default_timezone_set('US/Central');
    $args = array(
      'post_type' => 'events',
      'posts_per_page' => -1,
    );

    $events = (new WP_Query($args))->get_posts();

    $ret = [];
    foreach ($events as $event) {
      if (get_field('start_date', $event->ID) == date('Ymd', strtotime($time)) &&
          get_field('notify_registrants', $event->ID)) {
        array_push($ret, $event);
      }
    }
    return $ret;
  }

  /**
   * Notifies registrants of future events.
   *
   * @author  Chris Miller <chris@prolificdigital.com>
   *
   * @since    0.0.1
   */
  public function notify_registrants() {
    $seven_day = $this->get_events('+7 day');
    $this->send_mail($seven_day, '1 Week Away!');

    $two_day = $this->get_events('+2 day');
    $this->send_mail($two_day, '2 Days Away!');
  }
}
