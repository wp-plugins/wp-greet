<?php
/* This file is part of the wp-greet plugin for wordpress */

/*  Copyright 2008, 2009  Hans Matzen  (email : webmaster at tuxlog dot de)

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
// reads all wp-greet options from the database
//
function wpgreet_get_options() {

  // the following parameters are supported by wp-greet
  // wp-greet-version - the version of wp-greet used
  // wp-greet-minseclevel - the minimal security level needed to send a card
  // wp-greet-captcha - use captcha to prevent spaming? true, false
  // wp-greet-mailreturnpath - the email adresse uses as the default return path
  // wp-greet-autofillform - if set to true, the fields are filled from the profile of the logged in user
  // wp-greet-bcc - send bcc to this adress
  // wp-greet-imgattach - dont send a link, send inline image (true,false)
  // wp-greet-default-title - default title for mail
  // wp-greet-default-header - default header for email
  // wp-greet-default-footer - default footer for email
  // wp-greet-logging - enables logging of sent cards
  // wp-greet-imagewidth - sets fixed width for the image
  // wp-greet-gallery - the used gallery plugin
  // wp-greet-forüage - the pageid of the form page
  // wp-greet-galarr - the selected galleries for redirection to wp-greet
  //                   as array
  // wp-greet-smilies - switch to activate smiley support with greeting form
  // wp-greet-linesperpage - count of lines to show on each page of log
  // wp-greet-usesmtp - which method to use for mail transfer 1=smtp, 0=php mail

  $options = array("wp-greet-version" => "", 
		   "wp-greet-minseclevel" => "", 
		   "wp-greet-captcha" => "", 
		   "wp-greet-mailreturnpath" => "", 
		   "wp-greet-autofillform" => "",
		   "wp-greet-bcc" => "",
		   "wp-greet-imgattach" => "",
		   "wp-greet-default-title" => "",
		   "wp-greet-default-header" => "",
		   "wp-greet-default-footer" => "",
		   "wp-greet-imagewidth" => "",
		   "wp-greet-logging" => "",
		   "wp-greet-gallery" => "",
		   "wp-greet-formpage" => "",
		   "wp-greet-galarr" => array(),
		   "wp-greet-smilies" => "",
		   "wp-greet-linesperpage" => "",
		   "wp-greet-usesmtp" => "");


  reset($options);
  while (list($key, $val) = each($options)) {
    if ( $key != "wp-greet-galarr")
      $options[$key] = get_option($key);
    else {
      $options["wp-greet-galarr"] = unserialize( get_option("wp-greet-galarr"));
      if ( $options["wp-greet-galarr"] == False )
	$options["wp-greet-galarr"] = array();
    }
  }

  return $options;
}


//
// writes the current options to the wp-options table
//
function wpgreet_set_options() {
  global $wpg_options;
  reset($wpg_options);
  while (list($key, $val) = each($wpg_options)) {
    if (is_array($val) ) 
      update_option($key,serialize($val) );	 
    else
      update_option($key, $val);
  }
}


//
// function to check if an email adress is valid
// checks format and existance of mx record for mail host
//
function check_email($mail_address) {
  //Leading and following whitespaces are ignored
  $mail_address = trim($mail_address);
  //Email-address is set to lower case
  $mail_address = strtolower($mail_address);
    
  //List of signs which are illegal in name, subdomain and domain
  $illegal_string = '\\\\(\\n)@';
    
  //Parts of the regular expression = name@subdomain.domain.toplevel
  $name      = '([^\\.'.$illegal_string.'][^'.$illegal_string.']?)+';
  $subdomain = '([^\\._'.$illegal_string.']+\\.)?';
  $domain    = '[^\\.\\-_'.$illegal_string.'][^\\._'.$illegal_string.']*[^\\.\\-_'.$illegal_string.']';
  //.museum and .travel are the only TLDs longer than four signs
  $toplevel  = '([a-z]{2,4}|museum|travel)';    

  $regular_expression = '/^'.$name.'[@]'.$subdomain.$domain.'\.'.$toplevel.'$/';
    
  if ( preg_match($regular_expression, $mail_address) ) {
    $parts = explode("@", $mail_address);
    $hparts = explode (".", $parts[1]);
    $host = $hparts[count($hparts)-2]. "." . $hparts[count($hparts)-1];

    if (checkdnsrr($host, "MX")){
      //echo "The e-mail address is valid. $mail_address <br />" ;
      return true;
    } else {
      //echo "The e-mail host is not valid. $mail_address <br />";
      return false;
    }
  } else {
    //echo "The e-mail address contains invalid charcters.<br />";
    return false;
  }
}



//
// ermittelt anhand der file extension den zugehoerigen mimetype
//
function get_mimetype($fname)
{
  $ext = substr($fname,strrpos($fname,".")+1);
  
  switch ($ext) {
  case "jpeg":
  case "jpg":
  case "jpe":
  case "JPG":
    $mtype="image/jpeg";
    break;
  case "png":
    $mtype="image/png";
    break;
  case "gif":
    $mtype ="image/gif";
    break;
  case "tiff":
  case "tif":
    $mtype="image/tiff";
    break;
  default:
    $mtype="application/octet-stream";
    break;
  }
  return $mtype;
}

function log_greetcard($to, $from, $pic, $msg)
{
  $wpdb =& $GLOBALS['wpdb'];
  $now = gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 ));
  
  $sql = "insert into ". $wpdb->prefix . "wpgreet_stats values (0,'" . $now . "', '" . $from . "','" . $to . "','" . $pic . "','" . $wpdb->Escape($msg). "','". $_SERVER["REMOTE_ADDR"] . "');" ;
   

 ;
  
  $wpdb->query($sql);
}


//
// fuegt die capability wp-greet-send zu allen rollen >= $role hinzu
//
function set_permissions($role) {
  global $wp_roles;

  $all_roles = $wp_roles->role_names;

  // das recht fuer alle rollen entfernen
  foreach($all_roles as $key => $value) {
    $drole= get_role($key);
    if ( ($drole !== NULL) and $drole->has_cap("wp-greet-send") ) {
      $drole->remove_cap('wp-greet-send');
    }
  }
  
  
  foreach ($all_roles as $key => $value) {
    $crole = get_role($key);
    if ($crole !== NULL) {
      $crole->add_cap('wp-greet-send'); 
    }
    
    if ($key == $role)
      break;  
  }
}

//
// verbindet wp-greet mit ngg, es wird die url angepasst
//
function ngg_connect($link='' , $picture='') {
  $wpdb =& $GLOBALS['wpdb'];
  // wp-greet optionen aus datenbank lesen
  $wpg_options = wpgreet_get_options();
  
  // pruefe ob gallery umgelenkt werden soll
  if (array_search($picture->gid, $wpg_options['wp-greet-galarr']) !== False) {
    
    $sql="SELECT post_type FROM ".$wpdb->prefix."posts WHERE id= ". $wpg_options['wp-greet-formpage'] .";";
    $pagetype = $wpdb->get_row($sql);
    $url_prefix =  get_settings('siteurl');

    if ($pagetype->post_type == "page")
      $url_prefix .= '?page_id=';
    else
      $url_prefix .= '?p=';	
    $url_prefix .= $wpg_options['wp-greet-formpage'];
    $folder_url  = get_option ('siteurl')."/".$picture->path."/";
    $link = stripslashes($url_prefix . "?gallery=" . $picture->gid .
			 "\&amp;image=" . $folder_url.$picture->filename);
  }
  
  return $link;
}

//
// entfernt den ngg thumbcode
//
function ngg_remove_thumbcode($thumbcode,$picture) {
  
  // wp-greet optionen aus datenbank lesen
  $wpg_options = wpgreet_get_options();
  
  // pruefe ob gallery umgelenkt werden soll
  if (array_search($picture->gid, $wpg_options['wp-greet-galarr']) !== False) 
    $thumbcode = "";
  return $thumbcode;
}

//
// umkehrfunktion zu nl2br :-)
//
//function br2nl($text)
//{
//  return str_replace("<br />","",$text);
//}


function get_dir_alphasort($pfad)
{
  // Prüfung, ob das angegebene Verzeichnis geöffnet werden kann
  if( ($pointer = opendir($pfad)) == false ) {
    // Im Fehlfall ist das bereits der Ausgang
    return false;
  }

  $arr = array();
  while( $datei = readdir($pointer) ) {
    // Prüfung, ob es sich überhaupt um Dateien handelt
    // oder um Synonyme für das aktuelle (.) bzw.
    // das übergeordnete Verzeichnis (..)
    if( is_dir($pfad."/".$datei) || $datei == '.' || $datei == '..' ) 
      continue;
    
    $arr[] = $datei;
  }
  closedir($pointer);
  array_multisort($arr);

  return $arr;
}

function debug($text)
{
  $fd=fopen("/tmp/wpg.log","a+");
  fwrite($fd,$text."\n");
  fclose($fd);
}
?>