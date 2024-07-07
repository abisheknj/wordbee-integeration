<?php
/*
Plugin Name: Worbee Api Integeration
Plugin URI: http://experiencepixels.com/wordbee-api
Description: Plugin helps to connect with wordbee api
Version: 1.0
Author: Experience Pixels 
Author URI: http://experiencepixels.com
License: GPL2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}



// Define plugin constants
define('WORDBEE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WORDBEE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once(WORDBEE_PLUGIN_DIR . 'includes/auth.php');
require_once(WORDBEE_PLUGIN_DIR . 'includes/encryption.php');
require_once(WORDBEE_PLUGIN_DIR . 'settings.php');
require_once(WORDBEE_PLUGIN_DIR . 'includes/generate-text-edit.php');
require_once(WORDBEE_PLUGIN_DIR . 'includes/documents.php');

// Enqueue styles and scripts
function wordbee_enqueue_scripts() {
    wp_enqueue_style('wordbee-css', WORDBEE_PLUGIN_URL . 'assets/css/style.css');
    wp_enqueue_script('wordbee-js', WORDBEE_PLUGIN_URL . 'assets/js/script.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'wordbee_enqueue_scripts');

function enqueue_custom_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

// Add settings link on plugin page
function wordbee_plugin_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wordbee-api-plugin-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wordbee_plugin_settings_link');

// Register shortcodes
function wordbee_register_shortcodes() {
    add_shortcode('project_list', 'project_list_shortcode');
    add_shortcode('display_text_edits', 'display_text_edits_shortcode');
}
add_action('init', 'wordbee_register_shortcodes');

// Shortcode function to display project list table
function project_list_shortcode() {

    include_once(WORDBEE_PLUGIN_DIR . 'pages/projects.php');
    return display_project_list();
}

// Shortcode function to display text edits
function display_text_edits_shortcode() {
    // Your text edit logic here
    include_once(WORDBEE_PLUGIN_DIR . 'pages/text-edits.php');
    return display_text_edits();

}


?>