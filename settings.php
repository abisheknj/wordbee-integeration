<?php
add_action('admin_menu', 'wordbee_api_plugin_create_menu');

function wordbee_api_plugin_create_menu() {
    // Create new top-level menu
    add_menu_page(
        'Wordbee API Plugin Settings', // Page title
        'Wordbee API', // Menu title
        'administrator', // Capability
        'wordbee-api-plugin-settings', // Menu slug
        'wordbee_api_plugin_settings_page', // Function to display the settings page
        'dashicons-admin-generic' // Icon
    );
}

add_action('admin_init', 'wordbee_api_plugin_register_settings');

function wordbee_api_plugin_register_settings() {
    // Register settings
    register_setting('wordbee-api-plugin-settings-group', 'wordbee_api_key', 'sanitize_text_field');
    register_setting('wordbee-api-plugin-settings-group', 'wordbee_username', 'sanitize_text_field');

    // Add success message
    if (isset($_GET['settings-updated'])) {
        add_settings_error('wordbee_messages', 'wordbee_message', 'Settings Saved', 'updated');
    }
}

// Handle statistics reset
add_action('admin_post_wordbee_reset_stats', 'wordbee_reset_stats');

function wordbee_reset_stats() {
    if (!current_user_can('administrator')) {
        return;
    }

    check_admin_referer('wordbee_reset_stats_nonce');
    
    delete_option('wordbee_total_calls');
    delete_option('wordbee_daily_calls');
    delete_option('wordbee_error_calls');
    delete_option('wordbee_daily_error_calls');
    delete_option('wordbee_last_call_time');

    wp_redirect(admin_url('admin.php?page=wordbee-api-plugin-settings'));
    exit;
}

function increment_api_call($success = true) {
    $today = date('Y-m-d');
    $total_calls = get_option('wordbee_total_calls', 0);
    $daily_calls = get_option('wordbee_daily_calls', []);
    $error_calls = get_option('wordbee_error_calls', 0);
    $daily_error_calls = get_option('wordbee_daily_error_calls', []);
    $last_call_time = current_time('mysql');
    
    if (!isset($daily_calls[$today])) {
        $daily_calls[$today] = 1;
    } else {
        $daily_calls[$today]++;
    }

    if (!$success) {
        $error_calls++;
        if (!isset($daily_error_calls[$today])) {
            $daily_error_calls[$today] = 1;
        } else {
            $daily_error_calls[$today]++;
        }
    }

    update_option('wordbee_total_calls', $total_calls + 1);
    update_option('wordbee_daily_calls', $daily_calls);
    update_option('wordbee_error_calls', $error_calls);
    update_option('wordbee_daily_error_calls', $daily_error_calls);
    update_option('wordbee_last_call_time', $last_call_time);
}




function wordbee_api_plugin_settings_page() {
    $total_calls = get_option('wordbee_total_calls', 0);
    $daily_calls = get_option('wordbee_daily_calls', []);
    $error_calls = get_option('wordbee_error_calls', 0);
    $daily_error_calls = get_option('wordbee_daily_error_calls', []);
    $last_call_time = get_option('wordbee_last_call_time', 'N/A');
    $today = date('Y-m-d');
    $calls_today = isset($daily_calls[$today]) ? $daily_calls[$today] : 0;
    $errors_today = isset($daily_error_calls[$today]) ? $daily_error_calls[$today] : 0;
?>

<div class="wrap">
    <h1>Wordbee API Plugin Settings</h1>
    <?php settings_errors('wordbee_messages'); ?>
    <form method="post" action="options.php">
        <?php settings_fields('wordbee-api-plugin-settings-group'); ?>
        <?php do_settings_sections('wordbee-api-plugin-settings-group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">API Key</th>
                <td><input type="text" name="wordbee_api_key" value="<?php echo esc_attr(get_option('wordbee_api_key')); ?>" required /></td>
            </tr>
             
            <tr valign="top">
                <th scope="row">Username</th>
                <td><input type="text" name="wordbee_username" value="<?php echo esc_attr(get_option('wordbee_username')); ?>" required/></td>
        </table>
        
        <?php submit_button(); ?>
    </form>

    <h2>API Statistics</h2>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Total API Calls</th>
            <td><?php echo esc_html($total_calls); ?></td>
        </tr>
        <tr valign="top">
            <th scope="row">API Calls Today</th>
            <td><?php echo esc_html($calls_today); ?></td>
        </tr>
        <tr valign="top">
            <th scope="row">Total Error Calls</th>
            <td><?php echo esc_html($error_calls); ?></td>
        </tr>
        <tr valign="top">
            <th scope="row">Error Calls Today</th>
            <td><?php echo esc_html($errors_today); ?></td>
        </tr>
        <!-- <tr valign="top">
            <th scope="row">Last API Call Time</th>
            <td><?php echo esc_html($last_call_time); ?></td>
        </tr> -->
    </table>

    <!-- <h3>Daily API Calls</h3>
    <table class="form-table">
        <?php foreach ($daily_calls as $date => $calls) : ?>
        <tr valign="top">
            <th scope="row"><?php echo esc_html($date); ?></th>
            <td><?php echo esc_html($calls); ?></td>
        </tr>
        <?php endforeach; ?>
    </table> -->

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?page=wordbee-api-plugin-settings')); ?>">
        <?php wp_nonce_field('wordbee_reset_stats_nonce'); ?>
        <input type="hidden" name="action" value="wordbee_reset_stats">
        <?php submit_button('Reset Statistics'); ?>
    </form>
</div>

<div class="card" style="padding: 20px; font-size: 0.9em; border: 1px solid #d1e7dd; background-color: #d1e7dd; border-radius: 5px;">
    <div style="display: flex; align-items: center;">
        <img src="https://img.icons8.com/ios-filled/50/000000/info.png" alt="info icon" style="width: 24px; height: 24px; margin-right: 10px;">
        <h1 style="margin: 0;">Plugin Information</h1>
    </div>
    <p>To utilize this plugin, please create two pages and add the following shortcodes to them:</p>
    <ul>
        <li><strong>Project List:</strong> Create a page and add the shortcode <code>[project_list]</code> to display the list of projects.</li>
        <li><strong>Text Edit Report:</strong> Create another page and add the shortcode <code>[display_text_edits]</code> to show the text edit report. Make sure the slug for this page is <code>text_edits</code>.</li>
    </ul>
</div>

<?php
}
?>
