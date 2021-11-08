<?php
/**
 * @author Dalton McGee
 */

use Firebase\JWT\JWT;

class Zoom {
  protected $api_key;
  protected $api_secret;
  protected $jwt;
  protected $zoom_user_id;

  function __construct() {
    $settings_options = get_option('wp_events_settings_option_name');
    $this->api_key = $settings_options['zoom_api_key_0'] ? $settings_options['zoom_api_key_0'] : "";
    $this->api_secret = $settings_options['zoom_api_secret_1'] ? $settings_options['zoom_api_secret_1'] : "";
    $this->zoom_user_id = $settings_options['zoom_user_id_2'] ? $settings_options['zoom_user_id_2'] : "";
    $payload = [
      "iss" => $this->api_key,
      "exp" => time() + 60
    ];
    $this->jwt = JWT::encode($payload, $this->api_secret);
  }

  /**
   * The above properties should be used to generate a JWT on every call.
   *
   * For Development purposes I've created a temp JWT with an expiration
   * that serves no purpose. We will need to include a recommended
   * library from jwt.io to programatiically generate JWTs on every call.
   *
   * https://jwt.io/#libraries-io
   *
   * */

  protected function useApi($url, $method = "GET", $json = null) {
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.zoom.us/v2/$url",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_POSTFIELDS => $json,
      CURLOPT_HTTPHEADER => [
        "authorization: Bearer $this->jwt",
        'content-type: application/json',
      ],
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      return 'cURL Error #:' . $err;
    } else {
      return $response;
    }
  }

  public function register_attendee($meeting_id, $json) {
    $api_call = $this->useApi("/meetings/$meeting_id/registrants", "POST", json_encode($json, JSON_UNESCAPED_SLASHES));
    if (strpos($api_call, "Registration has not been enabled for this meeting")) {
      $api_call = $this->useApi("/webinars/$meeting_id/registrants", "POST", json_encode($json, JSON_UNESCAPED_SLASHES));
    }
    return $api_call;
  }

  protected function getDateTime($start_time, $timezone, $duration) {
    $start_datetime = new DateTime($start_time);
    $start_datetime->setTimezone(new DateTimeZone($timezone));
    $end_datetime = clone $start_datetime;
    $end_datetime->add(new DateInterval('PT' . $duration . 'M'));
    return [$start_datetime, $end_datetime];
  }

  public function getUsers() {
    return $this->useApi('users');
  }

  public function getZoomEvents() {
    $events = [];
    $eventIds = [];
    $response = json_decode($this->useApi("users/$this->zoom_user_id/meetings"), true);
    $meetings = array_key_exists('meetings', $response) ? $response['meetings'] : false;
    $webinars = array_key_exists('webinars', $response) ? $response['webinars'] : false;
    if ($meetings) {
      foreach ($meetings as $meeting) {
        // Add every meeting to $events array
        array_push($eventIds, $meeting["id"]);
      }
    }
    if ($webinars) {
      foreach ($webinars as $webinar) {
        // Add every webinar to $events array
        array_push($eventIds, $webinar["id"]);
      }
    }
    if (count($eventIds) > 0) {
      foreach ($eventIds as $id) {
        $arr = [];
        $data = $this->getMeeting($id);
        // Check to see if meeting is webinar
        if (property_exists($data, "message")  && strpos($data->message, "webinar")) {
          $data = json_decode($this->useApi("webinars/$id"));
        }
        if (property_exists($data, 'occurrences')) {
          foreach ($data->occurrences as $occurrence) {
            $occurenceArr = [];
            $occurenceArr['start_time'] =  property_exists($occurrence, 'start_time') ? $occurrence->start_time : "";
            $occurenceArr['duration'] =  property_exists($occurrence, 'duration') ? $occurrence->duration : "";
            $occurenceArr['id'] =  property_exists($occurrence, 'occurrence_id') ? $occurrence->occurrence_id : "";
            $occurenceArr['topic'] =  property_exists($data, 'topic') ? $data->topic : "";
            $occurenceArr['join_url'] =  property_exists($data, 'join_url') ? $data->join_url : "";
            $occurenceArr['timezone'] =  property_exists($data, 'timezone') ? $data->timezone : "";
            $occurenceArr['agenda'] =  property_exists($data, 'agenda') ? $data->agenda : "";
            array_push($events, $occurenceArr);
          }
        } else {
          $arr['start_time'] =  property_exists($data, 'start_time') ? $data->start_time : "";
          $arr['duration'] =  property_exists($data, 'duration') ? $data->duration : "";
          $arr['id'] =  property_exists($data, 'id') ? $data->id : "";
          $arr['topic'] =  property_exists($data, 'topic') ? $data->topic : "";
          $arr['join_url'] =  property_exists($data, 'join_url') ? $data->join_url : "";
          $arr['timezone'] =  property_exists($data, 'timezone') ? $data->timezone : "";
          $arr['agenda'] =  property_exists($data, 'agenda') ? $data->agenda : "";
          array_push($events, $arr);
        }
      }
    }
    return $events;
  }

  public function getDatabaseEvents() {
    /**
     * Get all events in one query.
     * Could be paginated, but I figured the total number of posts
     * won't ever be in the millions, or even thousands,
     * so this should be fine from a computational POV.
     */
    $query = new WP_Query(
      [
        'post_type' => 'events',
        'posts_per_page' => -1,
      ]
    );

    return $query->posts;
  }

  public function insertNewEvents() {
    $zoom_meetings = $this->getZoomEvents();
    $wp_events = $this->getDatabaseEvents();
    // Loop over every meeting from the Zoom API
    foreach ($zoom_meetings as $meeting) {
      $exists = false;
      $zoom_id = $meeting['id'];
      /**
       * Loop over every event in the database then
       * compare it to the Zoom ID. If it exists, flag it,
       * check for any changes, update, then end the loop.
       */
      if (count($wp_events) > 0) {
        foreach ($wp_events as $event) {
          if (($zoom_id == $event->zoom_id)) {
            $exists = true;
            if ($meeting['start_time'] && $meeting['timezone'] && $meeting['duration']) {
              $date_time = $this->getDateTime($meeting['start_time'], $meeting['timezone'], $meeting['duration']);
            } else {
              $date_time = [false, false];
            }
            $zoom = [
              'end_time' => !!$date_time[1] ? $date_time[1]->format('H:i:s') : "",
              'start_time' => !!$date_time[0] ? $date_time[0]->format('H:i:s') : "",
              'start_date' => !!$date_time[0] ? $date_time[0]->format(get_option('date_format')) : ""
            ];
            if ($event->post_title != $meeting["topic"]) {
              wp_update_post(['ID' => $event->ID, 'post_title' => $meeting['topic']]);
            }
            if (get_post_field('description', $event->ID) != $meeting['agenda']) {
              update_post_meta($event->ID, 'description', $meeting['agenda']);
            }
            if (get_post_field('zoom_url', $event->ID) != $meeting['join_url']) {
              update_post_meta($event->ID, 'registration_link', $meeting['join_url']);
            }
            if (get_post_field('start_date', $event->ID) != $zoom['start_date']) {
              update_post_meta($event->ID, 'start_date', $zoom['start_date']);
            }
            if (get_post_field('start_time', $event->ID) != $zoom['start_time']) {
              update_post_meta($event->ID, 'start_time', $zoom['start_time']);
            }
            if (get_post_field('end_time', $event->ID) != $zoom['end_time']) {
              update_post_meta($event->ID, 'end_time', $zoom['end_time']);
            }
            break;
          }
        }
      }
      // If the Meeting doesn't exist, create Event in DB.
      if (!$exists) {
        if ($meeting['start_time'] && $meeting['timezone'] && $meeting['duration']) {
          $date_time = $this->getDateTime($meeting['start_time'], $meeting['timezone'], $meeting['duration']);
        } else {
          $date_time = [false, false];
        }
        $args = [
          'post_type' => 'events',
          'post_title' => $meeting['topic'],
          'post_status' => 'publish',
        ];
        $meta_args = [
          'zoom_id' => $zoom_id,
          'description' => $meeting['agenda'],
          'zoom_url' => $meeting['join_url'],
          'end_time' => !!$date_time[1] ? $date_time[1]->format('H:i:s') : "",
          'start_time' => !!$date_time[0] ? $date_time[0]->format('H:i:s') : "",
          'start_date' => !!$date_time[0] ? $date_time[0]->format(get_option('date_format')) : ""
        ];
        $result_id = wp_insert_post($args);
        $this->setPostMeta($result_id, $meta_args);
      }
    }
    // $this->deleteNonExistentMeetings();
    return true;
  }

  protected function setPostMeta($post_id, $postarr) {
    foreach ($postarr as $key => $value) {
      add_post_meta($post_id, $key, $value);
    }
    return;
  }

  public function getMeeting($meeting_id) {
    return json_decode($this->useApi("meetings/$meeting_id"));
  }

  public function getMeetingRegistrationQuestions($meeting_id) {
    $questions = json_decode($this->useApi("meetings/$meeting_id/registrants/questions"));
    if (strpos($questions->message, "webinar")) {
      $questions = json_decode($this->useApi("webinars/$meeting_id/registrants/questions"));
    }
    return $questions;
  }


  protected function deleteNonExistentMeetings() {
    $zoom_meetings = $this->getZoomEvents();
    $wp_events = $this->getDatabaseEvents();
    foreach ($wp_events as $event) {
      $zoom_id = $event->zoom_id;
      if ($zoom_id) {
        $exists = false;
        foreach ($zoom_meetings as $meeting) {
          if ($zoom_id == $meeting["id"]) {
            $exists = true;
            break;
          }
        }
        if (!$exists) {
          wp_delete_post($event->ID);
        }
      }
    }
  }
}
