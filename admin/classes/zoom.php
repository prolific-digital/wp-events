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
                $data = json_decode($this->useApi("meetings/$id"));
                // Check to see if meeting is webinar
                if (property_exists($data, "message")  && strpos($data->message, "webinar")) {
                    $data = json_decode($this->useApi("webinars/$id"));
                }

                $arr['id'] =  property_exists($data, 'id') ? $data->id : "";
                $arr['start_time'] =  property_exists($data, 'start_time') ? $data->start_time : "";
                $arr['topic'] =  property_exists($data, 'topic') ? $data->topic : "";
                $arr['join_url'] =  property_exists($data, 'join_url') ? $data->join_url : "";
                $arr['timezone'] =  property_exists($data, 'timezone') ? $data->timezone : "";
                $arr['agenda'] =  property_exists($data, 'agenda') ? $data->agenda : "";
                $arr['duration'] =  property_exists($data, 'duration') ? $data->duration : "";
                array_push($events, $arr);
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
        return get_posts('post_type=events');
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
            foreach ($wp_events as $event) {
                if (($zoom_id == $event->zoom_id)) {
                    $exists = true;
                    $date_time = $this->getDateTime($meeting['start_time'], $meeting['timezone'], $meeting['duration']);
                    $zoom = [
                        'start_date' => $date_time[0]->format('Ymd'),
                        'start_time' => $date_time[0]->format('H:i:s'),
                        'end_time' => $date_time[1]->format('H:i:s'),
                    ];
                    if ($event->post_title != $meeting["topic"]) {
                        wp_update_post(['ID' => $event->ID, 'post_title' => $meeting['topic']]);
                    }
                    if (get_field('description', $event->ID) != $meeting['agenda']) {
                        update_field('description', $meeting['agenda'], $event->ID);
                    }
                    if (get_field('registration_link', $event->ID) != $meeting['join_url']) {
                        update_field('registration_link', $meeting['join_url'], $event->ID);
                    }
                    if (get_field('start_date', $event->ID) != $zoom['start_date']) {
                        update_field('start_date', $zoom['start_date'], $event->ID);
                    }
                    if (get_field('start_time', $event->ID) != $zoom['start_time']) {
                        update_field('start_time', $zoom['start_time'], $event->ID);
                    }
                    if (get_field('end_time', $event->ID) != $zoom['end_time']) {
                        update_field('end_time', $zoom['end_time'], $event->ID);
                    }

                    break;
                }
            }
            // If the Meeting doesn't exist, create Event in DB.
            if (!$exists) {
                $args = [
                    'post_type' => 'event',
                    'post_title' => $meeting['topic'],
                    'post_status' => 'publish',
                ];
                $wp_rewrite = new wp_rewrite;
                $result = wp_insert_post($args);
                /**
                 * Check that Event added successfully, then
                 * add corresponding ACF Fields to Event by ID.
                 */
                if ($result && !is_wp_error($result)) {
                    $post_id = $result;
                    $date_time = $this->getDateTime($meeting['start_time'], $meeting['timezone'], $meeting['duration']);
                    update_field("zoom_id", $zoom_id, $post_id);
                    update_field("description", $meeting['agenda'], $post_id);
                    update_field("registration_link", $meeting['join_url'], $post_id);
                    update_field("start_date", $date_time[0]->format('Ymd'), $post_id);
                    update_field("start_time", $date_time[0]->format('H:i:s'), $post_id);
                    update_field("end_time", $date_time[1]->format('H:i:s'), $post_id);
                }
            }
        }
        $this->deleteNonExistentMeetings();
        return true;
    }

    // public function getMeeting($meeting_id) {
    //     return json_decode($this->useApi("meetings/$meeting_id"));
    // }

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
