<?php

require_once(plugin_dir_path(__FILE__) .'auth.php');
require_once(plugin_dir_path(__FILE__) .'encryption.php');

function get_text_edit($job_id, $date_filter) {
    $token = get_auth_token();
    error_log("Get Text edit running");
    error_log($job_id);
    
    $filetoken = make_api_call($token, $job_id, $date_filter);

    if ($filetoken) {
        // Log the API response
        error_log('Async API Operation Response with file token: ' . $filetoken);

        $second_response = call_second_api($filetoken, $token);

        if ($second_response) {
            // Log the second API response
            error_log('Second API Call Response: ' . json_encode($second_response));

            // Increment API call statistics for successful call
            increment_api_call(true);

            return $second_response;
        } else {
            error_log('Failed to perform second API call.');

          // Increment API call statistics for failed call
            increment_api_call(false);
            return 'No Data Available';
        }
    } else {
        error_log('Failed to perform async API operation.');

        // Increment API call statistics for failed call
        increment_api_call(false);

        return 'No Data Available';
    }
}

// Function: make_api_call()
function make_api_call($token, $job_id, $date_filter) {
    error_log('Starting async API operation.');

    $date_from = $date_filter['dateFrom'];
    $date_to = $date_filter['dateTo'];

    error_log($date_from);
    error_log($date_to);


    if ($token) {
        // API endpoint URL and data
        $url = 'https://td.eu.wordbee-translator.com/api/resources/segments/textedits';
        $data = array(
            'scope' => array('type' => 'Job', 'jobid' => $job_id , 'jobcdyt' => true),
            // 'dateFrom' => $date_from,
            // 'dateTo' => $date_to
        );

        // Make the API call with token in header using wp_remote_post()
        $response = wp_remote_post($url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Auth-Token' =>  $token,
                'X-Auth-AccountId' => get_option('wordbee_username')
            )
        ));

        // Check if the request was successful
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            // Decode the JSON response body
            $response_data = json_decode(wp_remote_retrieve_body($response), true);

            // Check if operation started successfully
            if (isset($response_data['trm']['requestid'])) {
                // Poll for operation completion
                $result = poll_operation_completion($response_data['trm']['requestid'], $token);
                return $result;
            } else {
                error_log('Failed to start async API operation.');
                increment_api_call(false); // Increment API call statistics for failed call
                return false; // Operation failed to start
            }
        } else {
            error_log('Failed to perform async API operation. Error: ' . wp_remote_retrieve_response_message($response));
            increment_api_call(false); // Increment API call statistics for failed call
            return false; // API call failed
        }
    } else {
        error_log('Failed to obtain token for async API operation.');
        increment_api_call(false); // Increment API call statistics for failed call
        return false; // Failed to obtain token
    }
}

// Function: poll_operation_completion()
function poll_operation_completion($request_id, $token) {
    $url = 'https://td.eu.wordbee-translator.com/api/trm/status?requestid=' . $request_id;

    // Poll until the operation is finished
    do {
        // Wait for a brief moment before polling again
        sleep(1);

        // Make the API call to check operation status
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Auth-Token' =>  $token,
                'X-Auth-AccountId' => get_option('wordbee_username')
            )
        ));

        // Check if the request was successful
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            // Decode the JSON response body
            $response_data = json_decode(wp_remote_retrieve_body($response), true);

            // Check if operation status is 'Finished'
            if (isset($response_data['trm']['status']) && $response_data['trm']['status'] === 'Finished') {
                // Return the file token if available
                if (isset($response_data['custom']['filetoken'])) {
                    return $response_data['custom']['filetoken'];
                } else {
                    return false; // File token not found
                }
            }
        } else {
            // Log error message if API call failed
            error_log('Failed to poll async API operation status. Error: ' . wp_remote_retrieve_response_message($response));
            increment_api_call(false); // Increment API call statistics for failed call
            return false; // API call failed
        }
    } while (true);
}

// Function: call_second_api()
function call_second_api($filetoken, $token) {
    error_log('Calling second API');

    // API endpoint URL with filetoken parameter
    $url = 'https://td.eu.wordbee-translator.com/api/media/get/' . $filetoken;

    // Make the API call with token in header using wp_remote_get()
    $response = wp_remote_get($url, array(
        'headers' => array(
            'X-Auth-Token' =>  $token,
            'X-Auth-AccountId' => get_option('wordbee_username')
        )
    ));

    // Check if the request was successful
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // Increment API call statistics for successful call
        increment_api_call(true);

        // Return the response body
        return wp_remote_retrieve_body($response);
    } else {
        error_log('Failed to perform second API call. Error: ' . wp_remote_retrieve_response_message($response));
        increment_api_call(false); // Increment API call statistics for failed call
        return false; // API call failed
    }
}
?>