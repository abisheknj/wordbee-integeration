<?php

require_once(plugin_dir_path(__FILE__) .'auth.php');
require_once(plugin_dir_path(__FILE__) .'encryption.php');


// Shortcode callback function

// Shortcode callback function
function get_document_list($project_id) {
    $token = get_auth_token();


    // Validate the project ID
    if (empty($project_id) || !is_numeric($project_id)) {
        error_log('Invalid project ID: ' . $project_id);
        return 'Invalid project ID';
    }

    // API URL
    $api_url = 'https://td.eu.wordbee-translator.com/api/resources/documents/list';

    // Prepare the data to be sent in the body of the request
    $data = array(
        'scope' => array(
            'type' => 'Project',
            'projectid' => intval($project_id)
        )
    );

    // Make the API call with token in header using wp_remote_post()
    $response = wp_remote_post($api_url, array(
        'body' => json_encode($data),
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' => $token, // Add token to Authorization header
            'X-Auth-AccountId' => get_option('wordbee_username')
        )
    ));

    // Increment API call statistics based on response
    if (is_wp_error($response)) {
        increment_api_call(false); // Increment error call
        return 'Request Error: ' . $response->get_error_message();
    } else {
        increment_api_call(true); // Increment success call
    }

    // Decode the response
    $decoded_response = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the response is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'JSON Decode Error: ' . json_last_error_msg();
    }

    return $decoded_response;
}

function get_total_word_count($project_id) {
    // Get the document list
    $document_list = get_document_list($project_id);

    // Validate the document list
    if (!is_array($document_list) || !isset($document_list['items'])) {
        return 'Invalid document list';
    }

    // Initialize total word count
    $total_word_count = 0;
    $total_char_count = 0;

    // Iterate through each document and get the word counts
    foreach ($document_list['items'] as $document) {
        $document_id = $document['did'];
        $count_arr = get_word_count_for_document($project_id, $document_id);
    
            if (isset($count_arr['total_words']) && isset($count_arr['total_chars'])) {
                $total_word_count += $count_arr['total_words'];
                $total_char_count += $count_arr['total_chars'];
            }
        
    }
    return [
        'total_words' => $total_word_count,
        'total_chars' => $total_char_count,
    ];
}

// Function to retrieve word count for a specific document
function get_word_count_for_document($project_id, $document_id) {
    $token = get_auth_token();

    // Validate parameters
    if (empty($project_id) || !is_numeric($project_id) || empty($document_id) || !is_numeric($document_id)) {
        error_log('Invalid project ID or document ID');
        return 'Invalid project ID or document ID';
    }
    
    $api_url = "https://td.eu.wordbee-translator.com/api/projects/{$project_id}/wordcounts/{$document_id}";

    // Make the API call with token in header using wp_remote_get()
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' => $token,
            'X-Auth-AccountId' => get_option('wordbee_username')
        )
    ));

    // Increment API call statistics based on response
    if (is_wp_error($response)) {
        increment_api_call(false); // Increment error call
        $error_message = 'Request Error: ' . $response->get_error_message();
        error_log($error_message);
        return $error_message;
    } else {
        increment_api_call(true); // Increment success call
    }

    // Decode the response
    $decoded_response = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the response is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        $json_error_message = 'JSON Decode Error: ' . json_last_error_msg();
        error_log($json_error_message);
        return $json_error_message;
    }

    // Find and return the word count for the document
    $total_word_count = 0;
    $total_char_count = 0;

    // Check if the first entry exists and has 'words' and 'chars'
    if (!empty($decoded_response) && isset($decoded_response[0]['words']) && isset($decoded_response[0]['chars'])) {
        $total_word_count = $decoded_response[0]['words'];
        $total_char_count = $decoded_response[0]['chars'];
    }

    // Log word count retrieved
    $log_message = "Word count retrieved: {$total_word_count} words, {$total_char_count} chars.";
    error_log($log_message);

    return [
        'total_words' => $total_word_count,
        'total_chars' => $total_char_count,
    ];
}


function get_word_count_for_job($job_id) {
    $token = get_auth_token();

    // Validate parameters
    if (empty($job_id) || !is_numeric($job_id)) {
        error_log('Invalid job ID');
        return 'Invalid job ID';
    }

    $api_url = "https://td.eu.wordbee-translator.com/api/jobs/wordcounts/list";

    // Make the API call with token in header using wp_remote_post() to send the job ID in the body
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' => $token,
            'X-Auth-AccountId' => get_option('wordbee_username')
        ),
        'body' => json_encode(array('query' => '{jobid} == ' . $job_id))
    ));

    // Increment API call statistics based on response
    if (is_wp_error($response)) {
        increment_api_call(false); // Increment error call
        $error_message = 'Request Error: ' . $response->get_error_message();
        error_log($error_message);
        return $error_message;
    } else {
        increment_api_call(true); // Increment success call
    }

    // Decode the response
    $decoded_response = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the response is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        $json_error_message = 'JSON Decode Error: ' . json_last_error_msg();
        error_log($json_error_message);
        return $json_error_message;
    }

    // Find and return the word count for the job
    $total_word_count = 0;
    $total_char_count = 0;

    // Check if the response contains the required data
    if (!empty($decoded_response['rows']) && isset($decoded_response['rows'][0]['words']) && isset($decoded_response['rows'][0]['chars'])) {
        $total_word_count = $decoded_response['rows'][0]['words'];
        $total_char_count = $decoded_response['rows'][0]['chars'];
    }

    // Log word count retrieved
    $log_message = "Word count retrieved for job {$job_id}: {$total_word_count} words, {$total_char_count} chars.";
    error_log($log_message);

    return [
        'total_words' => $total_word_count,
        'total_chars' => $total_char_count,
    ];
}





?>
