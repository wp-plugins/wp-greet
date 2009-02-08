<?php
/* This file is part of the wp-greet plugin for wordpress */

/*  Copyright 2008,2009  Hans Matzen  (email : webmaster at tuxlog.de)

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

//
// setting up the default options in table wp-options during 
// plugin activation from the plugins page
//
function wp_greet_activate()
{
  global $wpdb, $wp_roles, $wp_version,$wpg_options;
  
  // upgrade function changed in WordPress 2.3	
  if (version_compare($wp_version, '2.3', '>='))		
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  else
    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

  $wpg_options = wpgreet_get_options();

  debug("<pre>###".WP_GREET_VERSION."###".$wpg_options['wp-greet-version']."###</pre>");
  debug(version_compare(WP_GREET_VERSION,$wpg_options['wp-greet-version'], '>'));
  if ($wpg_options['wp-greet-version'] == "") {
    $wpg_options['wp-greet-version'] = WP_GREET_VERSION;
    add_option("wp-greet-version",$wpg_options['wp-greet-version'],
		 "versionnumber of the installed wp-greet","yes");
    // create table	
    $sql = "CREATE TABLE " . $wpdb->prefix . 'wpgreet_stats' . " (
	    mid BIGINT NOT NULL AUTO_INCREMENT ,
            senttime DATETIME NOT NULL,
	    frommail VARCHAR(80) NOT NULL,
            tomail VARCHAR(80) NOT NULL,
            picture VARCHAR(255) NOT NULL,
            mailbody MEDIUMTEXT NOT NULL,
            remote_ip VARCHAR(15) NOT NULL,
	    PRIMARY KEY mid (mid)
	    );";
	
      dbDelta($sql);

  } else if (version_compare(WP_GREET_VERSION, 
			     $wpg_options['wp-greet-version'], '>') ) {

    // add column for ip logging (since v1.2)
    $sql="alter table " . $wpdb->prefix . "wpgreet_stats add column remote_ip varchar(15) NOT NULL after mailbody;";
    $wpdb->query($sql);

    // neue version wegschreiben
    $wpg_options['wp-greet-version'] = WP_GREET_VERSION;
    wpgreet_set_options();
  }
    
    if ($wpg_options['wp-greet-minseclevel'] == "") {
      $wpg_options['wp-greet-minseclevel'] = 'Registered';
      add_option("wp-greet-minseclevel",$wpg_options['wp-greet-minseclevel'],
		 "the minimum security level to send greeting cards","yes");
    };
    
    
    if ($wpg_options['wp-greet-captcha'] == "") {
      $wpg_options['wp-greet-captcha'] = 0;
      add_option("wp-greet-captcha",$wpg_options['wp-greet-captcha'],
		 "wanna use a captcha to prevent spamming?","yes");
    }; 
    
    if ($wpg_options['wp-greet-mailreturnpath'] == "") {
      $wpg_options['wp-greet-mailreturnpath'] = "";
      add_option("wp-greet-mailreturnpath",$wpg_options['wp-greet-mailreturnpath'],"the standard mail return path","yes");
    }; 

    if ($wpg_options['wp-greet-autofillform'] == "") {
      $wpg_options['wp-greet-autofillform'] = 1;
      add_option("wp-greet-autofillform",$wpg_options['wp-greet-autofillform'],
		 "try to fill form with user data","yes");
    };
   
    if ($wpg_options['wp-greet-imagewidth'] == "") {
      $wpg_options['wp-greet-imagewidth'] = 400;
      add_option("wp-greet-imagewidth",$wpg_options['wp-greet-imagewidth'],
		 "gives the fixe image width","yes");
    };
    if ($wpg_options['wp-greet-logging'] == "") {
      $wpg_options['wp-greet-logging'] = 1;
      add_option("wp-greet-logging",$wpg_options['wp-greet-logging'],
		 "enable logging","yes");
    };
    if ($wpg_options['wp-greet-gallery'] == "") {
      $wpg_options['wp-greet-gallery'] = "ngg";
      add_option("wp-greet-gallery",$wpg_options['wp-greet-gallery'],
		 "which gallery to use","yes");
    };

    if ($wpg_options['wp-greet-linesperpage'] == "") {
      $wpg_options['wp-greet-linesperpage'] = "10";
      add_option("wp-greet-linesperpage",$wpg_options['wp-greet-linesperpage'],
		 "lines on each page on log page","yes");
    }; 

    if ($wpg_options['wp-greet-usesmtp'] == "") {
      $wpg_options['wp-greet-usesmtp'] = "1";
      add_option("wp-greet-usesmtp",$wpg_options['wp-greet-usesmtp'],
		 "which mail transfer method to use smtp=1, php mail=0","yes");
    };   
}

function wp_greet_deactivate()
{
  $options = array("wp-greet-minseclevel", 
		   "wp-greet-captcha", 
		   "wp-greet-mailreturnpath", 
		   "wp-greet-autofillform",
		   "wp-greet-bcc",
		   "wp-greet-imgattach",
		   "wp-greet-default-title",
		   "wp-greet-default-header",
		   "wp-greet-default-footer",
		   "wp-greet-imagewidth",
		   "wp-greet-logging",
		   "wp-greet-gallery",
		   "wp-greet-smilies",
		   "wp-greet-formpage",
		   "wp-greet-galarr",
		   "wp-greet-linesperpage",
		   "wp-greet-usesmtp");

  reset($options);
  while (list($key,$val) = each($options)) {
    delete_option($val);
  }
}

?>