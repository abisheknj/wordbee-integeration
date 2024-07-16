<?php



// Function to retrieve project list from Wordbee API
function get_project_list($client_name = '', $date_from = '', $date_to = '', $src  = '', $trg = '',  $start_index = 0, $projects_per_page = 10) {


    // Get the auth token
    $token = get_auth_token();

    // API endpoint URL
    $url = 'https://td.eu.wordbee-translator.com/api/projects/list';

    // Construct the query
    $query = array();
    if (!empty($src)) {
        $query[] = "{src} == \"" . esc_attr($src) . "\"";
    }
    if (!empty($client_name)) {
        $query[] = "{client}.StartsWith(\"" . sanitize_text_field($client_name) . "\")";
    }
    if (!empty($date_from)) {
        $query[] = "{dtreceived} >= DateTime(" . date('Y, m, d', strtotime($date_from)) . ")";
    }
    if (!empty($date_to)) {
        $query[] = "{dtreceived} <= DateTime(" . date('Y, m, d', strtotime($date_to)) . ")";
    }
    

  
    $query_string = implode(' And ', $query);

    // Request body with pagination parameters
    error_log($query_string);
    $request_body = json_encode(array(
        "query" => $query_string,
        "take" => 100 
    ));

    // Make the API call with token in header using wp_remote_post()
    $response = wp_remote_post($url, array(
        'body' => $request_body,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' =>  $token, 
            'X-Auth-AccountId' => ACCOUNT_ID
        )
    ));

    // Increment API call statistics based on response
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // Decode the JSON response body
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        // Increment success API call
        increment_api_call(true);

        // Return the rows
        return $data;
    } else {
        // Log error message if API call failed
        if (is_wp_error($response)) {
            error_log('Failed to retrieve project list. Error: ' . $response->get_error_message());
        } else {
            error_log('Failed to retrieve project list. HTTP Error Code: ' . wp_remote_retrieve_response_code($response));
        }
        
        // Increment error API call
        increment_api_call(false);

        return null; // API call failed
    }
}

// Function to retrieve jobs list from Wordbee API for a specific project
function get_jobs_list($project_id , $trg) {
    // Get the token
    $token = get_auth_token();

    // API endpoint URL for jobs list
    $url = 'https://td.eu.wordbee-translator.com/api/jobs/list';

    $query = array();
    if (!empty($project_id)) {
        $query[] = "{pid} == " . intval($project_id) . " And {task}.StartsWith(\"PSTED\")";
    }

    if (!empty($trg)) {
        $query[] = "{trg} == \"" . esc_attr($trg) . "\"";
    }

    $query_string = implode(' And ', $query);
    error_log($query_string);
    // Request body with project ID
    $request_body = json_encode(array(
        "query" => $query_string
        // "take" => 100 // Adjust as needed, or implement pagination
    
    ));

    // Make the API call with token in header using wp_remote_post()
    $response = wp_remote_post($url, array(
        'body' => $request_body,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' => $token,
            'X-Auth-AccountId' => ACCOUNT_ID
        )
    ));

    // Increment API call statistics based on response
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // Decode the JSON response body
        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Increment success API call
        increment_api_call(true);

        // Return the rows
        return $data;
    } else {
        // Log error message if API call failed
        if (is_wp_error($response)) {
            error_log('Failed to retrieve jobs list. Error: ' . $response->get_error_message());
        } else {
            error_log('Failed to retrieve jobs list. HTTP Error Code: ' . wp_remote_retrieve_response_code($response));
        }

        // Increment error API call
        increment_api_call(false);

        return null; // API call failed
    }
}



// Shortcode function to display project list table
// Shortcode function to display project list table
function display_project_list() {
    $api_key = get_option('wordbee_api_key');
    $account_id = get_option('wordbee_username');
    $language_codes = [
        "ab", "ach", "adh-UG", "aa", "af", "af-NA", "af-ZA", "ak", "ak-Fant", "ak-Fant-GH", "ak-GH", "sq", "sq-AL", "sq-MK", "sq-ALN", "sq-XK", "sq-ME", "sq-ALS", "gsw", 
        "gsw-FR", "am", "am-ET", "anu", "am-US", "apa", "ar", "ar-DZ", "ar-BH", "ar-TD", 
        "ar-EG", "ar-IQ", "ar-IL", "ar-JO", "ar-kab", "ar-KW", "ar-LB", "ar-LY", "ar-MR", 
        "ar-MA", "ar-OM", "ar-AA", "ar-PS", "ar-QA", "ar-SA", "ar-SY", "ar-TN", "ar-AE", 
        "ar-YE", "hy", "hy-AM", "hy-east", "hy-west", "as", "as-IN", "aii", "az", "az-Cyrl", 
        "az-Cyrl-AZ", "az-Latn", "az-Latn-AZ", "bal", "bm", "bxg", "ba", "ba-RU", "eu", 
        "eu-ES", "be", "be-BY", "bem", "bn", "bn-BD", "bn-IN", "ber", "bho", "bik", "xbd", 
        "bi", "bla", "bla-Latn", "bla-Cans", "byn", "brx", "bs", "bs-Cyrl", "bs-Cyrl-BA", 
        "bs-Latn", "bs-Latn-BA", "br", "br-FR", "bg", "bg-BG", "my", "my-MM", "cbv", "yue", 
        "yue-CN", "yue-MY", "kea", "hns", "ca", "ca-ES", "ceb-PH", "ceb-dav-PH", "knc", "cld", 
        "ch", "cbk", "ny-MW", "zh", "zh-Hans", "zh-CHS", "zh-Cn-HK", "zh-MY", "zh-Cn-AU", "zh-CN", 
        "zh-SG", "zh-Hant", "zh-CHT", "zh-AU", "zh-HK", "zh-MO", "zh-TW", "zh-US", "chp", 
        "chp-Latn", "chp-Cans", "chk", "cv", "co", "co-FR", "cre", "cre-Latn", "cre-Cans", 
        "cpe", "cpf", "cpp", "hr", "hr-HR", "hr-BA", "cs", "cs-CZ", "cs-SK", "dag-GH", "da", 
        "da-DK", "da-GL", "prs", "prs-AF", "luo", "did", "din", "dv", "dv-MV", "doi", "nl", 
        "nl-BE", "nl-CW", "nl-NL", "dz", "dz-BT", "cjm", "bin", "efi", "en", "en-AR", "en-AU", 
        "en-BH", "en-BD", "en-BE", "en-BZ", "en-CM", "en-CA", "en-029", "en-CY", "en-EG", 
        "en-GM", "en-GE", "en-GH", "en-HK", "en-IS", "en-IN", "en-ID", "en-IE", "en-IL", "en-JM", 
        "en-JP", "en-JO", "en-KE", "en-KW", "en-LB", "en-LR", "en-LT", "en-MY", "en-MT", "en-MEA", 
        "en-NZ", "en-NG", "en-OM", "en-CN", "en-QA", "en-PH", "en-RW", "en-SA", "en-SG", "en-ZA", 
        "en-LK", "en-CH", "en-TZ", "en-TH", "en-TG", "en-TT", "en-TV", "en-AE", "en-UG", "en-GB", 
        "en-US", "en-ZW", "eo", "et", "et-EE", "ee", "ee-GH", "cfm", "fat", "gur", "fo", "fo-FO", 
        "fj", "fil", "fil-PH", "fi", "fi-FI", "fr", "fr-DZ", "fr-BE", "fr-BJ", "fr-BF", "fr-CM", 
        "fr-CA", "fr-CF", "fr-TD", "fr-CG", "fr-CI", "fr-GQ", "fr-FR", "fr-GA", "fr-GN", "fr-GW", 
        "fr-HT", "fr-LB", "fr-LU", "fr-ML", "fr-MQ", "fr-MR", "fr-MEA", "fr-MC", "fr-MA", "fr-NC", 
        "fr-NE", "fr-PF", "fr-RE", "fr-RW", "fr-SN", "fr-SL", "fr-CH", "fr-TG", "fr-TN", "fy", 
        "fy-NL", "ff", "gaa-GH", "gl", "gl-ES", "cab", "ka", "ka-GE", "de", "de-AT", "de-BE", 
        "de-DE", "de-IT", "de-LI", "de-LU", "de-CH", "gil", "el", "el-CY", "el-GR", "kl", "kl-GL", 
        "gu", "gu-IN", "gu-GB", "gwr", "ht", "cnh", "ha", "ha-Arab", "ha-Latn", "ha-Latn-NG", "haw", 
        "haw-HI", "haw-US", "haz", "he", "he-IL", "hil-PH", "hi", "hi-CA", "hi-IN", "hmn", "blu", 
        "hnj", "nan", "hu", "hu-HU", "hu-SK", "is", "is-IS", "ig", "ig-NG", "ilo-PH", "id", "id-ID", 
        "iku", "iku-Latn", "iku-Latn-CA", "iku-Cans", "iku-Cans-CA", "ga", "ga-IE", "xh", "xh-ZA", 
        "it", "it-IT", "it-MT", "it-CH", "ium", "ja", "ja-JP", "ja-latn", "ja-US", "jv", "kac", 
        "kjb", "kn", "kn-IN", "kr", "pam", "kar", "eky", "xsm", "ks", "kk", "kk-KZ", "km", "km-KH", 
        "qut", "qut-GT", "kg", "ki", "ki-KE", "rw", "rw-RW", "rn", "kok", "kok-IN", "ko", "ko-KR", 
        "ko-US", "kri", "kun", "ku", "ku-IQ", "kmr", "ky", "ky-KG", "lah", "lah-PK", "lah-PNB", 
        "lo", "lo-LA", "la", "lv", "lv-LV", "ln", "lt", "lt-LT", "dsb", "dsb-DE", "loz-ZM", "lug", 
        "lug-UG", "luo-KE", "lb", "lb-LU", "mas", "ymm", "mk-MK", "mk", "mdh", "mai", "mg", "ms", 
        "ms-BN", "ms-MY", "ms-SG", "ml", "ml-IN", "mt", "mt-MT", "mam", "cmn-US", "cmn-HK", "mnk", 
        "mi", "mi-NZ", "arn", "arn-CL", "mr", "mr-IN", "mh", "mwr", "myx", "mni", "men", "mih", 
        "mxt", "mix", "xtj", "moh", "moh-CA", "mn", "mn-Cyrl", "mn-MN", "mn-Mong", "mn-Mong-CN", 
        "cnr", "cnr-CNR", "mos", "mos-BF", "kin-MUL", "nv", "nd", "nr", "ne", "ne-NP", "no", 
        "nb", "no-DK", "no-NO", "nn", "nb-NO", "nn-NO", "nus", "nyy", "ny", "oc", "oc-FR", "ryu", 
        "or", "or-IN", "om", "om-ET", "kua", "pau", "pag", "pap", "pap-CW", "pap-pu", "ps", "ps-AF", 
        "ps-PK", "pdc", "fa", "fa-IR", "peo", "pcm-NG", "crk", "crk-Latn", "crk-Cans", "pon", 
        "pl", "pl-PL", "pt", "pt-AO", "pt-BR", "pt-GQ", "pt-GW", "pt-MZ", "pt-PT", "pa", "pa-CA", 
        "pa-IN", "pa-PK", "quz", "quz-BO", "quz-EC", "quz-PE", "rhg", "rom", "ro", "ro-MD", 
        "ro-RO", "rm", "rm-CH", "nyn", "ru", "ru-BY", "ru-EE", "ru-GE", "ru-IL", "ru-KZ", "ru-KG", 
        "ru-LV", "ru-LT", "ru-MD", "ru-RU", "ru-UA", "sah", "sah-RU", "smn", "smj", "se", "sms", 
        "sma", "smn-FI", "smj-NO", "smj-SE", "se-FI", "se-NO", "se-SE", "sms-FI", "sma-NO", 
        "sma-SE", "sm", "sg", "sa", "sa-IN", "sat", "skr", "gd", "gd-GB", "sr", "sr-Cyrl", 
        "sr-Cyrl-BA", "sr-Cyrl-ME", "sr-Cyrl-CS", "sr-Cyrl-RS", "sr-XK", "sr-Latn", "sr-Latn-BA", 
        "sr-Latn-ME", "sr-Latn-CS", "sr-Latn-RS", "sr-RS", "sh", "st", "st-ZA", "nso", "nso-ZA", 
        "tn", "tn-ZA", "ksw-MM", "shn", "suji", "sn", "sd", "si", "si-LK", "ss", "sk", "sk-SK", 
        "sl", "sl-SI", "xog", "so", "snk", "ckb", "es", "es-AR", "es-VE", "es-BO", "es-CL", 
        "es-CO", "es-CR", "es-CU", "es-DO", "es-EC", "es-SV", "es-GQ", "es-GT", "es-HN", 
        "es-001", "es-419", "es-MX", "es-NI"
        
    ];

    
    $output = '';
    $output .= '<div class = "loading">';
    $output .= '<div class = "loading-container">';
    $output .= '<h4>Generating Results</h4>';
    $output .= '<div class="loading-wrapper">';
    $output .=  '<div class="loader"></div>';
    $output .=  '</div></div> </div>';

    
    // Check if API credentials are empty
    if (empty($api_key) || empty($account_id)) {
        $output = '<div style="padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin-top: 20px;">';
        $output .= '<p>API credentials are missing. Please configure API credentials <a href="/wordpress/wp-admin/admin.php?page=wordbee-api-plugin-settings">Settings</a>.</p>';
        $output .= '</div>';
        return $output;
    }

    $project_list = false;
    
    $start_index = 0;
    $total_pages = 0;
    $current_page = 0;
    
    // Check if the form is submitted
    if (isset($_POST['client_name']) || isset($_POST['date_from']) || isset($_POST['date_to']) || isset($_POST['src']) || isset($_POST['trg']) ) {
        $client_name = isset($_POST['client_name']) ? sanitize_text_field($_POST['client_name']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $src = isset($_POST['src']) ? sanitize_text_field($_POST['src']) : '';
        $trg = isset($_POST['trg']) ? sanitize_text_field($_POST['trg']) : '';

        $start_index = isset($_POST['skip']) ? intval($_POST['skip']) : 0;
        $projects_per_page = 10;

        $data = get_project_list($client_name, $date_from, $date_to, $src, $trg ,  $start_index, $projects_per_page);

        if (is_array($data) && isset($data['rows']) && isset($data['total'])) {
            $project_list = $data['rows'];
            $total = $data['total'];
            $total_pages = ceil($total / $projects_per_page);
            $current_page = floor($start_index / $projects_per_page) + 1;
        } else {
            $project_list = array();
            $total = 0;
        }
    }

   




    $output .= '<form id="projectForm" method="post" style="display: flex; flex-direction: row; justify-content: center; margin-top: 20px;">';
    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="client_name">Enter Client Name:</label><br>';
    $output .= '<input type="text" name="client_name" style="margin-top: 5px;" value="' . (isset($_POST['client_name']) ? esc_attr($client_name) : '') . '" required><br>';
    $output .= '</div>';

    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="date_from">Date From:</label><br>';
    $output .= '<input type="date" id="date_from" name="date_from" style="margin-top: 5px;" value="' . (isset($_POST['date_from']) ? esc_attr($date_from) : '') . '" required><br>';
    $output .= '</div>';

    $output .= '<div style="margin-right: 20px;">';
    $output .= '<label for="date_to">Date To:</label><br>';
    $output .= '<input type="date" id="date_to" name="date_to" style="margin-top: 5px;" value="' . (isset($_POST['date_to']) ? esc_attr($date_to) : '') . '" required><br>';
    $output .= '</div>';

    $output .= '<div>';
    $output .= '<label for="src">Source src:</label><br>';
    $output .= '<select id="src" name="src">';
    $output .= '<option  value=""' . selected('', isset($_POST['src']) ? $_POST['src'] : '', false) . '>All</option>';
    $output .= '<option value="en-US" selected' . selected('en-US', isset($_POST['src']) ? $_POST['src'] : '', false) . '>en-US</option>';
    $output .= '</select>';
    $output .= '</div>';

    $output .= '<div>';
    $output .= '<label for="trg">Target trg:</label><br>';
    $output .= '<select id="trg" name="trg">';
    $output .= '<option value="" ' . selected('', isset($_POST['trg']) ? $_POST['trg'] : '', false) . '>Select</option>';
    
    foreach ($language_codes as $code) {
    $selected = ($code == (isset($_POST['trg']) ? $_POST['trg'] : '')) ? 'selected="selected"' : '';
    $output .= '<option value="' . $code . '" ' . $selected . '>' . $code . '</option>';
    }
    $output .= '</select>';
    $output .= '</div>';

    $output .= '<input type="hidden" id="skip" name="skip" value="' . esc_attr($start_index) . '">';
    $output .= '<input type="submit" value="Get Results" style="margin-top: 10px;">';
    
    $output .= '</form>';

  

    if ($project_list !== false) {
        if (!empty($project_list)) {

           $output .= '<div class="export-button-container">';
           $output .=  '<button class="export-button" onclick="exportTableToExcel()">Export</button>';
           $output .= '</div>';

            
            $output .= '<table class="project-table" id="projectTable" border="1" style="margin-top: 20px; width: 100%; border-collapse: collapse; font-size: 14px;">';
            $output .= '<tr>';
            $output .= '<th>Project ID & Name</th>';
            $output .= '<th>Client</th>';
            $output .= '<th>Status</th>';
            $output .= '<th>Source Language</th>';
            $output .= '<th>Date Received</th>';
            $output .= '<th>Manager Name</th>';
            $output .= '<th>Word Count</th>';
            $output .= '<th>Char Count</th>';
            $output .= '</tr>';
    
            $total_average_edit_distance = 0;
            $project_count = 0;
           
    
            foreach ($project_list as $project) {
                $project_edit_distance = 0;
                $job_count = 0;
                $project_average_edit_distance = 0;
                $total_word_counts = get_total_word_count($project['id']);
                $total_words = 0;
                $total_chars = 0;
    
    // Check if data exists
                if (isset($total_word_counts['total_words']) && isset($total_word_counts['total_chars'])) {
                   $total_words = $total_word_counts['total_words'];
                   $total_chars = $total_word_counts['total_chars'];
                
                   // Log words and chars
                   error_log("Total Words: $total_words, Total Chars: $total_chars");
                }
                 else {
                   // Handle case where data doesn't exist or there was an error
                   error_log("Error fetching total word counts: " . ($total_word_counts['error'] ?? 'Unknown error'));
                }
                


                $output .= '<tr>';
                $output .= '<td>' . esc_html($project['id']) . ' - ' . esc_html($project['reference']) . '</td>';
                $output .= '<td>' . esc_html($project['client']) . '</td>';
                $output .= '<td>' . esc_html($project['statust']) . '</td>';
                $output .= '<td>' . esc_html($project['srct']) . '</td>';
                $output .= '<td>' . esc_html(date('Y-m-d', strtotime($project['dtreceived']))) . '</td>';
                $output .= '<td>' . esc_html($project['managernm']) . '</td>';
                $output .= '<td>' . esc_html($total_words) . '</td>';
                $output .= '<td>' . esc_html($total_chars) . '</td>';

                $output .= '</tr>';
    
                $output .= '<tr>';
                $output .= '<td colspan="8">';
                $output .= '<table class="job-table" border="1" style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 12px;">';
    
                $jobs = get_jobs_list($project['id'], $trg);
    
                if ($jobs && isset($jobs['rows']) && !empty($jobs['rows'])) {
                    $output .= '<tr>';
                    $output .= '<th>Job ID</th>';
                    $output .= '<th>Status</th>';
                    $output .= '<th>Source</th>';
                    $output .= '<th>Target</th>';
                    $output .= '<th>Reference</th>';
                    $output .= '<th>Word Count</th>';
                    $output .= '<th>Char Count</th>';
                    $output .= '<th>Edits</th>';
                    $output .= '<th>Edit distance';
                    $output .= '<div class="edit-distance-info-th" title="editDistance = editDistanceSum / editDistanceSumLengths">';
                    $output .= '<span class="info-icon-th">';
                    $output .= '<svg width="16" height="16" viewBox="0 0 16 16" fill="white" xmlns="http://www.w3.org/2000/svg">';
                    $output .= '<path d="M8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0ZM8.93 12.41H7.07V7.05H8.93V12.41ZM8.93 5.85H7.07V4.59H8.93V5.85Z"/>';
                    $output .= '</svg>';
                    $output .= '</span>';
                    $output .= '</div>';
                    $output .= '</th>';
                    $output .= '<th>Edit distance Sum</th>';
                    $output .= '<th>Edit distance Sum Lengths</th>';
                    $output .= '<th>Edit distance Normalized</th>';
                    
                    $output .= '</tr>';
    
                    foreach ($jobs['rows'] as $job) {
                        $date_from = sanitize_text_field($_POST['date_from']); // Sanitize input
                        $date_to = sanitize_text_field($_POST['date_to']); // Sanitize input
                
                        // Construct the date filter parameters
                        $date_filter = array(
                            'dateFrom' => $date_from,
                            'dateTo' => $date_to
                        );
                        $data = get_text_edit($job['jobid'], $date_filter , $job['trg']);
                        $edit_distance = 0;
                        $edits = 0;
                        $edit_distance_sum = 0;
                        $edit_distance_sum_lengths = 0;
                        $edit_distance_normalized = 0;
                        if($data == false){
                            $edit_distance = "Request Failed";
                            $edits = "Request Failed";
                        }
                        if ($data !== false) {
                            // Decode the JSON string into an array
                            $decoded_data = json_decode($data, true);
                
                            // Check if decoding was successful
                            if ($decoded_data !== null) {
                                if (isset($decoded_data['counts']) && !empty($decoded_data['counts'])) {
                                    foreach ($decoded_data['counts'] as $item) {
                                        $edit_distance = $item['editDistance'];
                                        $edits = $item['edits'];
                                        $edit_distance_sum = $item['editDistanceSum'];
                                        $edit_distance_sum_lengths = $item['editDistanceSumLengths'];
                                        $edit_distance_normalized = $item['editDistanceSumNormalized'];
                                        $project_edit_distance += $edit_distance;
                                        error_log("edit distance added to " . $project_edit_distance);
                                        $job_count++;
                                    }
                                }
                            }
                        }
                        $job_id = $job['jobid'];
                        $job_status = $job['statust'];
                        $job_source = $job['srct'];
                        $job_target = $job['trgt'];
                        $job_task = $job['taskt'];
                        $job_reference = $job['reference'];
                        $job_wordData = get_word_count_for_job($job_id);
                        $word_count = 0;
                        $char_count = 0;

                        if (is_array($job_wordData)) {
                            $word_count = $job_wordData['total_words'];
                            $char_count = $job_wordData['total_chars'];
                        }

                        
                        $output .= '<tr>';
                        $output .= '<td>' . esc_html($job_id) . '</td>';
                        $output .= '<td>' . esc_html($job_status ) . '</td>';
                        $output .= '<td>' . esc_html($job_source) . '</td>';
                        $output .= '<td>' . esc_html($job_target ) . '</td>';
                        $output .= '<td>' . esc_html($job_reference) . '</td>';
                        $output .= '<td>' . esc_html($word_count) . '</td>';
                        $output .= '<td>' . esc_html($char_count) . '</td>';
                        $output .= '<td>' . esc_html($edits) . '</td>';
                        $output .= '<td>' . esc_html($edit_distance) . '</td>';
                        $output .= '<td>' . esc_html($edit_distance_sum) . '</td>';
                        $output .= '<td>' . esc_html($edit_distance_sum_lengths) . '</td>';
                        $output .= '<td>' . esc_html($edit_distance_normalized) . '</td>';

                        $output .= '</tr>';
                    }
    
                    $project_average_edit_distance = ($job_count > 0) ? $project_edit_distance / $job_count : 0;
                    error_log("project average is" . $project_average_edit_distance);
                    $total_average_edit_distance += $project_average_edit_distance;
                    $project_count++;
                } else {
                    $output .= '<tr id="no-jobs" ><td colspan="7">No jobs found.</td></tr>';
                }
    
                $output .= '</table>';
                $output .= '</td>';
                $output .= '</tr>';
            }
    
            $output .= '</table>';
            
            $final_edit_distance = ($project_count > 0) ? $total_average_edit_distance/$project_count : 0;
            $edit_distance_percentage = $final_edit_distance * 100;

            


            $output .= '<div class="card">';
            $output .= '<div class="card-body">';
            $output .= '<div class="edit-distance-info" title="Edit distance * 100 is calculated as percentage">';
            $output .= '<span class="info-icon">ℹ️</span>';
            $output .= '</div>';
            $output .= '<h3 class="card-title">Average Edit Distance</h3>';
            $output .= '<p class="card-text">' . esc_html($final_edit_distance) . '</p>';
            $output .= '<h3 class="card-title">Edit Distance Percentage</h3>';
            $output .= '<p class="card-text">' . esc_html($edit_distance_percentage) . '%</p>';
            $output .= '</div>';
            $output .= '</div>';
            

        } else {
            $output .= '<div>No projects found.</div>';
        }
        
    }
    $output .= '<div id="loading-complete"></div>';
    return $output;
}

// Register shortcode
add_shortcode('project_list', 'display_project_list');
