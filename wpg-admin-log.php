<?php
/* This file is part of the wp-greet plugin for wordpress */

/*  Copyright 2008  Hans Matzen  (email : webmaster at tuxlog.de)

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
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }


// generic functions
require_once("wpg-func.php");


//
// form handler for the admin dialog
//
function wpg_admin_log() 
{
  // base url for links
  $thisform = "admin.php?page=wp-greet/wpg_admin_log.php";
  // get sql object
  $wpdb =& $GLOBALS['wpdb'];

  // wp-greet optionen aus datenbank lesen
  $wpg_options = wpgreet_get_options();

  // get translation 
  $locale = get_locale();
  if ( empty($locale) )
    $locale = 'en_US';
  if(function_exists('load_textdomain')) 
    load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
  
  
  // if this is a POST call, save new values
  if (isset($_POST['clear_log'])) {
     $sql="delete from ".$wpdb->prefix."wpgreet_stats;";
     $results = $wpdb->query($sql);
    
    // put message after update
    echo"<div class='updated'><p><strong>Log cleared.</strong></p></div>";
  } 
  
  //
  // output log table
  //
  $out = "";
  $out = "<div class=\"wrap\">";
  $out .= "<h2>".__("Greetcard Log","wp-greet")."</h2>\n"; 
  $out .= "<table class=\"widefat\"><thead><tr>\n";
  $out .= '<th scope="col">'.__('No.',"wp-greet")."</th>"."\n";
  $out .= '<th scope="col">'.__("Date/Time","wp-greet").'</th>'."\n";
  $out .= '<th scope="col">'.__('From',"wp-greet").'</th>'."\n";
  $out .= '<th scope="col">'.__('To',"wp-greet").'</th>'."\n";
  $out .= '<th scope="col">'.__('Image',"wp-greet").'</th>';
  $out .= '<th scope="col">'.__('IP-Adress',"wp-greet").'</th>';
  $out .= '<th scope="col">'.__('Message',"wp-greet").'</th>'."</tr></thead>\n";
  // log loop
  $sql="select * from  ".$wpdb->prefix."wpgreet_stats order by mid DESC;";
  $results = $wpdb->get_results($sql);
 foreach($results as $res) {
   $out .= "<tr><td align=\"center\">".$res->mid."</td>";
   $out .= "<td>".$res->senttime."</td>";
   $out .= "<td>".$res->frommail."</td>";
   $out .= "<td>".$res->tomail."</td>";
   $out .= "<td><img src='".$res->picture."' width='60' /></td>";
   $out .= "<td>".$res->remote_ip."</td>";
   $out .= "<td>".attribute_escape($res->mailbody)."</td></tr>\n";

  }
  $out .= '</table></div>'."\n";
  $out .= "<div class='submit'><form name='clearlog' id='clearlog' method='post' action=''><input type='submit' name='clear_log' value='".__('Clear Log',"wp-greet")." »' /></form></div>";
  echo $out;

 

}
?>
