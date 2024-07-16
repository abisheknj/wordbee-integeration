<?php

require_once(plugin_dir_path(__FILE__) .'auth.php');
require_once(plugin_dir_path(__FILE__) .'encryption.php');

function get_text_edit($job_id, $date_filter , $trg) {
    $token = get_auth_token();
    error_log("Get Text edit running");
    error_log("Job ID: $job_id");
    
    $filetoken = make_api_call($token, $job_id, $date_filter ,  $trg);

    if ($filetoken) {
        error_log("Async API Operation Response with file token: $filetoken");

        $second_response = call_second_api($filetoken, $token);

        if ($second_response) {
            error_log('Second API Call Response: ' . json_encode($second_response));
            increment_api_call(true);
            return $second_response;
        } else {
            error_log('Failed to perform second API call.');
            increment_api_call(false);
            return 'No Data Available';
        }
    } else {
        error_log('Failed to perform async API operation.');
        increment_api_call(false);
        return 'No Data Available';
    }
}

function make_api_call($token, $job_id, $date_filter , $trg) {
    error_log('Starting async API operation.');

    $date_from = $date_filter['dateFrom'];
    $date_to = $date_filter['dateTo'];

    error_log("Date From: $date_from");
    error_log("Date To: $date_to");

    if ($token) {
        $url = 'https://td.eu.wordbee-translator.com/api/resources/segments/textedits';
        $data = array(
            'scope' => array(
                'type' => 'Job',
                'jobid' => $job_id,
                'jobcdyt' => true
            ),
            'trgs' => array($trg)
        );
        error_log('API Request Data: ' . json_encode($data));

        $response = wp_remote_post($url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Auth-Token' =>  $token,
                'X-Auth-AccountId' => get_option('wordbee_username')
            )
        ));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $response_data = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_data['trm']['requestid'])) {
                $result = poll_operation_completion($response_data['trm']['requestid'], $token);
                return $result;
            } else {
                error_log('Failed to start async API operation. Response Data: ' . json_encode($response_data));
                increment_api_call(false);
                return false;
            }
        } else {
            error_log('Failed to perform async API operation. Response Code: ' . wp_remote_retrieve_response_code($response));
            error_log('Response Message: ' . wp_remote_retrieve_response_message($response));
            increment_api_call(false);
            return false;
        }
    } else {
        error_log('Failed to obtain token for async API operation.');
        increment_api_call(false);
        return false;
    }
}

function poll_operation_completion($request_id, $token) {
    $url = 'https://td.eu.wordbee-translator.com/api/trm/status?requestid=' . $request_id;

    error_log("Polling for operation completion. Request ID: $request_id");

    $max_attempts = 5; // Maximum number of polling attempts
    $retry_delay = 1; // Initial retry delay in seconds
    $attempt = 1;

    do {
        sleep($retry_delay);

        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Auth-Token' =>  $token,
                'X-Auth-AccountId' => get_option('wordbee_username')
            )
        ));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $response_data = json_decode(wp_remote_retrieve_body($response), true);

            error_log('Poll Response Data: ' . json_encode($response_data));

            if (isset($response_data['trm']['status'])) {
                if ($response_data['trm']['status'] === 'Finished') {
                    if (isset($response_data['custom']['filetoken'])) {
                        error_log("Operation finished. File Token: " . $response_data['custom']['filetoken']);
                        return $response_data['custom']['filetoken'];
                    } else {
                        error_log('File token not found in poll response.');
                        return false;
                    }
                } elseif ($response_data['trm']['status'] === 'Waiting') {
                    // Increment retry delay up to a maximum of 10 seconds
                    $retry_delay = min($retry_delay * 2, 10);
                } else {
                    // Handle other statuses if needed
                    error_log('Operation status: ' . $response_data['trm']['status']);
                }
            } else {
                error_log('Status field not found in poll response.');
                return false;
            }
        } else {
            error_log('Failed to poll async API operation status. Response Code: ' . wp_remote_retrieve_response_code($response));
            error_log('Response Message: ' . wp_remote_retrieve_response_message($response));
            increment_api_call(false);
            return false;
        }

        $attempt++;
    } while ($attempt <= $max_attempts);

    // If reached maximum attempts, mark as failed
    error_log("Polling for request ID $request_id exceeded maximum attempts. Marking operation as failed.");
    return false;
}

function call_second_api($filetoken, $token) {
    error_log('Calling second API with File Token: ' . $filetoken);

    $url = 'https://td.eu.wordbee-translator.com/api/media/get/' . $filetoken;

    $response = wp_remote_get($url, array(
        'headers' => array(
            'X-Auth-Token' =>  $token,
            'X-Auth-AccountId' => get_option('wordbee_username')
        )
    ));

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        increment_api_call(true);
        return wp_remote_retrieve_body($response);
    } else {
        error_log('Failed to perform second API call. Response Code: ' . wp_remote_retrieve_response_code($response));
        error_log('Response Message: ' . wp_remote_retrieve_response_message($response));
        increment_api_call(false);
        return false;
    }
}
?>
