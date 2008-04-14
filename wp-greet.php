<?php
/*
Plugin Name: wp-greet
Plugin URI: http://www.tuxlog.de
Description: wp-greet is a wordpress plugin to send greeting cards from your wordpress blog.
Version: 1.0
Author: Barbara Jany, Hans Matzen <webmaster at tuxlog.de>
Author URI: http://www.tuxlog.de
*/

/*  Copyright 2008  Barbara Jany, Hans Matzen  (email : webmaster at tuxlog.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You 
are not allowed to call this page directly.'); }

static $wp_greet_version = "0.6";
// global options array
$wpg_options = array();

// include setup functions
require_once("setup.php");
// include functions
require_once("funclib.php");
// include admin options page
require_once("wp-greet-admin.php");
// include form page
require_once("wp-greet-form.php");

//
// just return the css link
// this function is called via the wp_head hook
//
function wp_greet_css() {

  $plugin_path = get_settings('siteurl') . '/wp-content/plugins/wp-greet';
  echo "<link rel=\"stylesheet\" href=\"". $plugin_path. "/wp-greet.css\" type=\"text/css\" media=\"screen\" />\n";
}

function wp_greet_init()
{
  // add css in header
  add_action('wp_head', 'wp_greet_css');

  // Action calls for all functions 
  add_filter('the_content', 'searchwpgreet');
  add_filter('the_excerpt', 'searchwpgreet');
}


// MAIN

// activating deactivating the plugin
register_activation_hook(__FILE__,'wp_greet_activate');
register_deactivation_hook(__FILE__,'wp_greet_deactivate');

// add option page 
add_action('admin_menu', 'wp_greet_admin');

// init plugin
add_action('init', 'wp_greet_init');


?>
