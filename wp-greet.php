<?php
/*
Plugin Name: wp-greet
Plugin URI: http://www.tuxlog.de
Description: wp-greet is a wordpress plugin to send greeting cards from your wordpress blog.
Version: 3.1
Author: Barbara Jany, Hans Matzen <webmaster at tuxlog.de>
Author URI: http://www.tuxlog.de
*/

/*  Copyright 2008-2012  Barbara Jany, Hans Matzen  (email : webmaster at tuxlog dot de)

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


define( "WP_GREET_VERSION", "3.1" );

// global options array
$wpg_options = array();

// include setup functions
$plugin_prefix_root = plugin_dir_path( __FILE__ );
$plugin_prefix_filename = "{$plugin_prefix_root}/setup.php";
include_once $plugin_prefix_filename;
//require_once("setup.php");
// include functions
require_once("wpg-func.php");
require_once("wpg-func-mail.php");
// include admin options page
require_once("wpg-admin.php");
require_once("wpg-admin-log.php");
require_once("wpg-admin-gal.php");
require_once("wpg-admin-sec.php");

// include form page
require_once("wpg-form.php");


function wp_greet_init()
{
  // optionen laden
  global $wpg_options;
  $wpg_options=wpgreet_get_options();

  // add css in header
  wp_enqueue_style("wp-greet", plugins_url('wp-greet.css', __FILE__) );
  
 // add thickbox for frontend
  add_action('wp_print_scripts', 'wpg_add_thickbox_script');
  add_action('wp_print_styles',  'wpg_add_thickbox_style' );
  
  // add actions for future send
  add_action("wpgreet_sendcard_link","cron_sendGreetCardLink",10,7);
  add_action("wpgreet_sendcard_mail","cron_sendGreetCardMail",10,9);
  
  // add jquery extensions for datepicker if applicable
  if ($wpg_options['wp-greet-future-send']) {
  	wp_enqueue_script('jquery'); 
    wp_enqueue_script('jquery-ui-wpgcustom', plugins_url('wp-greet/dtpicker/jquery-ui-1.8.16.custom.min.js', dirname(__FILE__)),array('jquery'));  	
    
    wp_enqueue_script('jquery-ui-timepicker', plugins_url('wp-greet/dtpicker/jquery-ui-timepicker-addon.js', dirname(__FILE__)),array('jquery','jquery-ui-wpgcustom'));
	$locale=trim(substr(get_locale(),0,2)); 
	wp_enqueue_script('jquery-ui-timepicker-i18n', plugins_url("wp-greet/dtpicker/i18n/jquery-ui-timepicker-$locale.js", dirname(__FILE__)),array('jquery-ui-timepicker'));
	wp_enqueue_script('jquery-ui-datepicker-i18n', plugins_url("wp-greet/dtpicker/i18n/jquery.ui.datepicker-$locale.js", dirname(__FILE__)),array('jquery-ui-timepicker'));
    
	wp_enqueue_style('jquery-ui-wpgcustom-css', plugins_url('wp-greet/dtpicker/jquery-ui-1.8.16.custom.css'));
	      	
  }
  
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
  
  add_menu_page('wp-greet','wp-greet', 'manage_options', $PPATH."wpg-admin.php","wpg_admin_form", site_url("/wp-content/plugins/wp-greet") . '/wp-greet.png');

  add_submenu_page( $PPATH."wpg-admin.php", __('Galleries',"wp-greet"), __('Galleries', "wp-greet"), 'manage_options', $PPATH."wpg-admin-gal.php", "wpg_admin_gal") ;

  add_submenu_page( $PPATH."wpg-admin.php", __('Security',"wp-greet"), __('Security', "wp-greet"), 'manage_options', $PPATH."wpg-admin-sec.php", "wpg_admin_sec") ;

  add_submenu_page( $PPATH."wpg-admin.php", __('Logging',"wp-greet"), __('Logging', "wp-greet"), 'manage_options', $PPATH."wpg-admin-log.php", "wpg_admin_log") ;

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

// wrapper functions for wp_cron trigger
 function cron_sendGreetCardMail($sender,$sendername,$recv,$recvname,$title,
			   $msgtext,$picurl,$ccsender,$debug=false) 
{ 
	//echo "###" . $sender .$sendername.$recv.$recvname.$title.$msgtext.$picurl.$ccsender.$debug."###";
	sendGreetcardMail($sender,$sendername,$recv,$recvname,$title,$msgtext,$picurl,$ccsender,$debug);
	log_greetcard($recv,addslashes($sender),$picurl,$msgtext);
}

function cron_sendGreetCardLink($sender,$sendername,$recv, $recvname,$duration, $fetchcode, $debug=false) 
{ 
	//echo "###".$sender.$sendername,$recv. $recvname.$duration. $fetchcode. $debug."###";
	sendGreetcardLink($sender,$sendername,$recv, $recvname,$duration, $fetchcode, $debug);
	mark_sentcard($fetchcode); 
}

//
// MAIN
//
register_activation_hook(__FILE__,'wp_greet_activate');
register_deactivation_hook(__FILE__,'wp_greet_deactivate');


// add admin menu 
add_action('admin_menu', 'wpg_add_menus');

// init plugin
add_action('init', 'wp_greet_init');

?>
