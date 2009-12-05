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
  // wp-greet-touswitch - activates terms of usage feature 1=yes, 0=no
  // wp-greet-termsofusage - contains the html text for the terms of usage
  // wp-greet-mailconfirm - activates the confirmation mail feature 1=yes, 0=no
  // wp-greet-mctext - text for the confirmation mail
  // wp-greet-mcduration - valid time of the confirmation link
  // wp-greet-onlinecard - dont get cards via email, fetch it online, yes=1, no=0
  // wp-greet-fields - a string of 0 and 1 describing the mandatory fields in the form

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
		   "wp-greet-usesmtp" => "",
		   "wp-greet-stampimage" => "",
		   "wp-greet-stamppercent" => "",
		   "wp-greet-mailconfirm" => "",
		   "wp-greet-mcduration" =>"",
		   "wp-greet-mctext" =>"",
		   "wp-greet-touswitch" =>"",
		   "wp-greet-termsofusage" =>"",
		   "wp-greet-onlinecard" => "",
		   "wp-greet-ocduration" => "",
		   "wp-greet-octext" => "",
		   "wp-greet-logdays" => "",
		   "wp-greet-carddays" => "",
		   "wp-greet-fields" => "");


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
function check_email($email) {
    //Leading and following whitespaces are ignored
    $mail_address = trim($mail_address);
    //Email-address is set to lower case
    $mail_address = strtolower($mail_address);
    // First, we check that there's one @ symbol, 
    // and that the lengths are right.
    if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
	// Email invalid because wrong number of characters 
	// in one section or wrong number of @ symbols.
	return false;
    }
    // Split it into sections to make life easier
    $email_array = explode("@", $email);
    $local_array = explode(".", $email_array[0]);
    for ($i = 0; $i < sizeof($local_array); $i++) {
	if
	    (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
		   $local_array[$i])) {
	    return false;
	}
    }
    // Check if domain is IP. If not, 
    // it should be valid domain name
    if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
	$domain_array = explode(".", $email_array[1]);
	if (sizeof($domain_array) < 2) {
	    return false; // Not enough parts to domain
    }
	for ($i = 0; $i < sizeof($domain_array); $i++) {
	    if
		(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$",
		       $domain_array[$i])) {
		return false;
	    }
	}
    }

    // check for domain existence
    if ( function_exists( 'checkdnsrr') ) {
	if (!checkdnsrr($email_array[1], "MX"))
	    return false;
    }

    // no error found it must be a valid domain
    return true;
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
    global $wpdb;
 
    $now = gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 ));
    
    $sql = "insert into ". $wpdb->prefix . "wpgreet_stats values (0,'" . $now . "', '" . $from . "','" . $to . "','" . $pic . "','" . $wpdb->Escape($msg). "','". $_SERVER["REMOTE_ADDR"] . "');" ;

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
     
      //$sql="SELECT post_type FROM ".$wpdb->prefix."posts WHERE id= ". $wpg_options['wp-greet-formpage'] .";";
      //$pagetype = $wpdb->get_row($sql);
      //$url_prefix =  get_settings('siteurl');

      //if ($pagetype->post_type == "page")
      // $url_prefix .= '?page_id=';
      //else
      //$url_prefix .= '?p=';	
      //$url_prefix .= $wpg_options['wp-greet-formpage'];
      $folder_url  = get_option ('siteurl')."/".$picture->path."/";

    $url_prefix = get_permalink($wpg_options['wp-greet-formpage'])."?";
    $link = stripslashes($url_prefix . "\&amp;gallery=" . $picture->gid .
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

function wpg_debug($text)
{
  $fd=fopen("/tmp/wpg.log","a+");
  fwrite($fd,$text."\n");
  fclose($fd);
}

function test_gd()
{
    $res="";
    $res .= "GD support on your server: ";
    
    // Check if the function gd_info exists (way to know if gd is istalled)
    if(function_exists("gd_info"))
    {
	$res .= "YES\n";
	$gd = gd_info();
	
        // Show status of all values that might be supported(unsupported)
	foreach($gd as $key => $value)
	{
	    $res .= $key . ": ";
	    if($value)
		$re .= "YES\n";
	    else
		$res .= "NO\n";
	}
    }
    else
	$res .= "NO";
    
    return $res;
}

//
// speichert eine karte  in der datenbank
//
function save_greetcard($sender, $sendername, $recv, $recvname, 
			$title, $message, $picurl, $cc2sender, 
			$confirmuntil, $confirmcode,$fetchuntil,$fetchcode)
{
    global $wpdb;

    if ($fetchcode == "" or $confirmcode == "") {
	$sql = "insert into ". $wpdb->prefix . "wpgreet_cards values (0, '$sendername', '$sender', '$recvname', '$recv', '$cc2sender', '". $wpdb->Escape($title)."', '$picurl','". $wpdb->Escape($message)."', '$confirmuntil', '$confirmcode', '$fetchuntil', '$fetchcode','','');";
	$wpdb->query($sql);
    } else {
	$sql = "select count(*) as anz from " .  $wpdb->prefix . "wpgreet_cards where confirmcode='$confirmcode';";

	$count = $wpdb->get_row($sql);
	if ( $count->anz == 0)
	    $sql = "insert into ". $wpdb->prefix . "wpgreet_cards values (0, '$sendername', '$sender', '$recvname', '$recv', '$cc2sender', '".$wpdb->Escape($title)."', '$picurl','". $wpdb->Escape($message)."', '$confirmuntil', '$confirmcode','$fetchuntil', '$fetchcode','','');";
	else
	    $sql = "update ". $wpdb->prefix . "wpgreet_cards set fetchuntil='$fetchuntil', fetchcode='$fetchcode' where confirmcode='$confirmcode';";
	$wpdb->query($sql);
    }
}

//
// markiert die karte mit dem confirmcode ccode als versendet
//
function mark_sentcard($ccode)
{
    global $wpdb; 
    $now = gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 ));
    $sql = "update ". $wpdb->prefix . "wpgreet_cards set card_sent='$now' where confirmcode='".$ccode."';";
    $wpdb->query($sql); 
}

//
// markiert die karte mit dem fetchcode fcode als mindestens einmal abgeholt
//
function mark_fetchcard($fcode)
{
    global $wpdb;
    $now =  gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 ));
    $sql = "update ". $wpdb->prefix . "wpgreet_cards set card_fetched='$now' where fetchcode='".$fcode."';";
    $wpdb->query($sql); 
}

//
// loescht alle karteneintraege die länger als das höchste mögliche abholdatum
// plus die die angegebene zahl an tagen sind
//
function remove_cards()
{ 
    // wp-greet optionen aus datenbank lesen
    $wpg_options = wpgreet_get_options();

    // nichts löschen wenn der parameter auf 0 oder leer steht
    if ( $wpg_options['wp-greet-carddays'] == 0 or $wpg_options['wp-greet-carddays'] == "")
	return;

    // berechne höchstes gültiges  fetch datum
    $then = time() + ( get_option('gmt_offset') * 60 * 60 ) - 
	( $wpg_options['wp-greet-carddays'] * 60 * 60 * 24 );
    $then =  gmdate("Y-m-d H:i:s",$then);
    
    
    global $wpdb;
    $sql = "delete from ". $wpdb->prefix . "wpgreet_cards where fetchuntil < '$then';";
    $wpdb->query($sql); 

    log_greetcard('',get_option("blogname"),'',"Cards cleaned until $then"); 
}


//
// loescht alle logeinträge die länger als die vorgegebene anzahl von tagen
// in der tabelle stehen
//
function remove_logs()
{
    // wp-greet optionen aus datenbank lesen
    $wpg_options = wpgreet_get_options();

    // nichts löschen wenn der parameter auf 0 oder leer steht
    if ( $wpg_options['wp-greet-logdays'] == 0 or $wpg_options['wp-greet-logdays'] == "")
	return;

    // berechne höchstes gültiges  fetch datum
    $then = time() + ( get_option('gmt_offset') * 60 * 60 ) - 
	( $wpg_options['wp-greet-logdays'] * 60 * 60 * 24 );
    $then = gmdate("Y-m-d H:i:s",$then);
    
    
    global $wpdb;
    $sql = "delete from ". $wpdb->prefix . "wpgreet_stats where senttime < '$then';";
    $wpdb->query($sql);

    log_greetcard('',get_option("blogname"),'',"Log cleaned until $then"); 
}

//
// wandelt ein mysql timestamp in einer zahl um, die die sekunden seit 1970
// wiedergibt. funktioniert für mysql4 und mysql5
//
function msql2time($m)
{
    // mysql5 2009-11-05 12:45:01
    if ( strpos( $m, ":" ) > 0 )
	return strtotime( $m );
    else {
	// mysql 4 - 20091105124501
	preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $m, $p);
	return mktime($p[4], $p[5], $p[6], $p[2], $p[3], $p[1]); 
    }
}

//
// erzeugt die url für das bild mit briefmarke
// $pic - url des bildes fuer die grusskarte
//
function build_stamp_url($pic)
{
    // wp-greet optionen aus datenbank lesen
    $wpg_options = wpgreet_get_options();
    
    $surl=get_option('siteurl');
    $picpath = ABSPATH . substr($pic, strpos($pic, $surl) + strlen($surl)+1);
    $stampimg = ABSPATH . $wpg_options['wp-greet-stampimage'];
    if (file_exists($stampimg))
	$alttext = basename($pic);
    else
	$alttext = __("Stampimage not found - Please contact your administrator","wp-greet");
    
    $link = '<img src="' . site_url("wp-content/plugins/wp-greet/").
	"wpg-stamped.php?cci=$picpath&amp;sti=".
	$stampimg . "&amp;stw=" . $wpg_options['wp-greet-stamppercent'].
	'" alt="'.$alttext."\" width='".
	($wpg_options['wp-greet-imagewidth']==""?"100%":$wpg_options['wp-greet-imagewidth'])."'/>";
    
    return $link;
}
?>