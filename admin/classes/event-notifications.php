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
  static public function send_mail($events, $topic, $registrants=null) {
    if ($events) {
      foreach ($events as $event) {
        $post_id = $event->ID;
        $registrants = $registrants ?: get_field('registrants', $event->ID);
        $event_url = get_field('zoom_url', $post_id) ? get_field('zoom_url', $post_id) : get_field('registration_link', $post_id);
        // If there are any registrants, send an email to them.
        if ($registrants) {
          $date = new DateTime(get_field('start_date', $post_id, true));
          $subject = $topic;
          $body = '<h1>' . get_the_title($post_id) . '</h1>' .
          '<h2 class="start_date">' . $date->format("l F d, Y") . (get_field('start_time', $post_id, false) ? ' @ ' . get_field('start_time', $post_id, false) : "")  . '</h2>' .
          '<p>' . get_field('description', $post_id, true) . '</p>' .
          ($event_url ? "<p><a href='$event_url'>Click here to join event</a></p>" : "" ).
          '<p><a href="' . get_permalink($post_id) . '">View Event</a></p>';

          $headers = array(
            'Content-Type: text/html; charset=UTF-8',
          );
          foreach (explode(',', $registrants) as $recipient) {
            wp_mail($recipient, $subject, $body, $headers);
          }
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
      if (
        get_field('start_date', $event->ID) == date('Ymd', strtotime($time)) &&
        get_field('notify_registrants', $event->ID)
      ) {
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
