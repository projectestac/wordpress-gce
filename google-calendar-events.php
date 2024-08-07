<?php
/**
 * Plugin Name: Simple Calendar
 * Plugin URI:  https://simplecalendar.io
 * Description: Add Google Calendar events to your WordPress site in minutes. Beautiful calendar displays. Fully responsive.
 * Author:      Simple Calendar
 * Author URI:  https://simplecalendar.io
 * Version:     3.4.0
 * Text Domain: google-calendar-events
 * Domain Path: /i18n
 *
 * @copyright   2013-2023 Xtendify Technologies. All rights reserved.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit();
}

// Plugin constants.
$this_plugin_path = trailingslashit(dirname(__FILE__));
$this_plugin_dir = plugin_dir_url(__FILE__);
$this_plugin_constants = [
	'SIMPLE_CALENDAR_VERSION' => '3.4.0',
	'SIMPLE_CALENDAR_MAIN_FILE' => __FILE__,
	'SIMPLE_CALENDAR_URL' => $this_plugin_dir,
	'SIMPLE_CALENDAR_ASSETS' => $this_plugin_dir . 'assets/',
	'SIMPLE_CALENDAR_PATH' => $this_plugin_path,
	'SIMPLE_CALENDAR_INC' => $this_plugin_path . 'includes/',
];
foreach ($this_plugin_constants as $constant => $value) {
	if (!defined($constant)) {
		define($constant, $value);
	}
}

// Plugin requirements

include_once 'includes/wp-requirements.php';

// Check plugin requirements before loading plugin.
$this_plugin_checks = new SimCal_WP_Requirements('Simple Calendar', plugin_basename(__FILE__), [
	'PHP' => '7.3',
	'WordPress' => '4.2',
	'Extensions' => ['curl', 'iconv', 'json', 'mbstring'],
]);
if ($this_plugin_checks->pass() === false) {
	$this_plugin_checks->halt();

	return;
}

include_once 'third-party/vendor/autoload.php';

// Load plugin.
include_once 'includes/main.php';

// XTEC ************ AFEGIT - Remove taxonomies associated to grouped calendar for prevent errors and show informative message
// 2016.10.03 @xaviernietosanchez

// Remove any taxonomy associated
function xtec_simple_calendar_unlink_category($post_id, $post, $update) {
    $taxonomy = 'calendar_category';
    wp_delete_object_term_relationships($post_id,$taxonomy);
    // Add filter to show information message
    add_filter( 'redirect_post_location', 'xtec_calendar_remove_taxonomy', 99 );
}

// Catch before save post
function xtec_simple_calendar_category_check( $data, $post ) {
    // Check that is a grouped calendar
    if ( isset($post['_feed_type']) and $post['_feed_type'] == 'grouped-calendars' ){
        // Add filter to remove taxonomies relationship
        add_filter( 'wp_insert_post', 'xtec_simple_calendar_unlink_category', '99', 3 );
    }

    return $data;
}

add_filter( 'wp_insert_post_data', 'xtec_simple_calendar_category_check', '99', 2 );

function xtec_calendar_remove_taxonomy( $location ) {
    remove_filter( 'redirect_post_location', __FILTER__, 99 );
    // Add argument to detect when is necessary show the message
    $location = add_query_arg ( 'remove_category_grouped_calendars', 997, $location );

    return $location;
}

// Check message to modify
function xtec_calendar_remove_taxonomy_message( $messages ) {
    if ( isset( $_GET['remove_category_grouped_calendars'] ) ) {
        // Modify success message
        $messages['post'][1] = $messages['post'][1] . ' <br />' . __('Grouped calendars can not have any taxonomy associated.', 'google-calendar-events');

        return $messages;
    }

    return $messages;
}

add_action('post_updated_messages', 'xtec_calendar_remove_taxonomy_message');
//************ FI
