<?php
/*
Plugin Name: WikiProTip
Plugin URI: http://phil.lavin.me.uk/
Description: Displays a tooltip pulling content from wiki
Version: 1.2
Author: Phil lavin
Author URI: http://phil.lavin.me.uk/
*/

/*
* Inclusion of administration,  and general functions.
*/
include( 'wikiprotip_functions.php' );

/*
* Addition functions to hooks.
*/
if (!is_admin()) {
	// include headers
	add_action('wp_head', 'wikiprotip_wp_head');
}

// Shortcode for [wikiprotip  ....]
add_shortcode('wiki', 'wikiprotip_wp_tags' );

// Activation of plugin
register_activation_hook( __FILE__, 'wikiprotip_wp_activate' );

// Deactivation of plugin
register_deactivation_hook( __FILE__, 'wikiprotip_wp_deactivate' );
