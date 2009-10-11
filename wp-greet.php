<?php
/*
Plugin Name: wp-greet
Plugin URI: http://www.tuxlog.de
Description: wp-greet is a wordpress plugin to send greeting cards from your wordpress blog.
Version: 1.7
Author: Barbara Jany, Hans Matzen <webmaster at tuxlog.de>
Author URI: http://www.tuxlog.de
*/

/*  Copyright 2008,2009  Barbara Jany, Hans Matzen  (email : webmaster at tuxlog dot de)

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


define( "WP_GREET_VERSION", "1.7" );

// global options array
$wpg_options = array();

// include setup functions
require_once("setup.php");
// include functions
require_once("wpg-func.php");
// include admin options page
require_once("wpg-admin.php");
require_once("wpg-admin-log.php");
require_once("wpg-admin-gal.php");
require_once("wpg-admin-sec.php");

// include form page
require_once("wpg-form.php");

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
  // optionen laden
  global $wpg_options;
  $wpg_options=wpgreet_get_options();

  // add css in header
  add_action('wp_head', 'wp_greet_css');

  // add thickbox for frontend
  add_action('wp_print_scripts', 'wpg_add_thickbox_script');
  add_action('wp_print_styles', 'wpg_add_thickbox_style' );

  // Action calls for all functions 
  add_filter('the_content', 'searchwpgreet');

  // filter for ngg integration
  if ( $wpg_options['wp-greet-gallery']=="ngg") {
    add_filter('ngg_create_gallery_link', 'ngg_connect',1,2);
    // next line up to ngg-version 0.99 
    //add_filter('ngg_create_gallery_thumbcode', 'ngg_remove_thumbcode',2,2); 
    // next line from ngg-version 1.0 on 
    add_filter('ngg_get_thumbcode', 'ngg_remove_thumbcode',2,2); 
  }
}

function wpg_add_menus()
{
  $PPATH=ABSPATH.PLUGINDIR."/wp-greet/";

  // get translation 
  $locale = get_locale();
  if ( empty($locale) )
    $locale = 'en_US';
  if(function_exists('load_textdomain')) 
    load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
  
  add_menu_page('wp-greet','wp-greet', 8, $PPATH."wpg-admin.php","wpg_admin_form", site_url("/wp-content/plugins/wp-greet") . '/wp-greet.png');

  add_submenu_page( $PPATH."wpg-admin.php", __('Galleries',"wp-greet"), __('Galleries', "wp-greet"), 8, $PPATH."wpg-admin-gal.php", "wpg_admin_gal") ;

  add_submenu_page( $PPATH."wpg-admin.php", __('Security',"wp-greet"), __('Security', "wp-greet"), 8, $PPATH."wpg-admin-sec.php", "wpg_admin_sec") ;

  add_submenu_page( $PPATH."wpg-admin.php", __('Logging',"wp-greet"), __('Logging', "wp-greet"), 8, $PPATH."wpg-admin-log.php", "wpg_admin_log") ;

}

// add thickbox to page headers
function wpg_add_thickbox_script()
{
    wp_enqueue_script( 'thickbox' );
}

// add thickbox to page headers
function wpg_add_thickbox_style()
{
    wp_enqueue_style( 'thickbox');
}


//
// MAIN
//
// activating deactivating the plugin
register_activation_hook(__FILE__,'wp_greet_activate');
register_deactivation_hook(__FILE__,'wp_greet_deactivate');

// add admin menu 
add_action('admin_menu', 'wpg_add_menus');

// init plugin
add_action('init', 'wp_greet_init');


?>
