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

// if called directly, get parameters from GET and output the gretcardform
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 
  require_once("wpg-func.php");
  require_once( dirname(__FILE__) . '/../../../wp-config.php');

  // get post vars
  $galleryID = attribute_escape( $_GET['gallery'] );
  $picurl = attribute_escape( $_GET['image'] );

  $out = showGreetcardForm($galleryID,$picurl);
  echo $out;
  //FIXME
 }

// apply the filter to the page or post content
function searchwpgreet($content) {

  // look for wp-greet tag
  if ( stristr( $content, '[wp-greet]' )) {

    // get post vars
    $galleryID=$_GET['gallery'];
    $picurl=$_GET['image'];

    // replace tag with html form
    $search = '[wp-greet]';
    $replace= showGreetcardForm($galleryID,$picurl); 
    $content= str_replace ($search, $replace, $content);
  }

  return $content;

  }

//
// this function controls thw whole greetcard workflow and the forms
//
function showGreetcardForm($galleryID,$picurl) {
  global $userdata;
  
  // hole optionen
  $wpg_options = wpgreet_get_options();
  
  // get translation 
  $locale = get_locale();
  if ( empty($locale) )
    $locale = 'en_US';
  if(function_exists('load_textdomain')) 
    load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
  
  // pruefe berechtigung zum versenden von grusskarten
  if ( !current_user_can('wp-greet-send') 
       and $wpg_options['wp-greet-minseclevel']!="everyone" ) {
    return "<p><b>".__("You are not permitted to send greeting cards.","wp-greet")."<br />".__("Please contact you wordpress Administrator.","wp-greet")."</b></p>";
  }
  

  // uebernehme user daten bei erstaufruf
  if ( $_POST['action'] == "" ) {
    get_currentuserinfo();
    $_POST['sender'] = $userdata->user_email;
  }

  // uebernehme default subject bei erstufruf
  if ( $_POST['title'] == "" ) 
    $_POST['title'] =  $wpg_options['wp-greet-default-title'];
  

  // Feldinhalte pruefen
   if ( $_POST['action'] == __("Preview","wp-greet") or  $_POST['action'] ==  __("Send","wp-greet") ) {

     if ( isset($_POST['sender']) && $_POST['sender'] != '' )
       $_POST['sender'] = attribute_escape($_POST['sender']);
     if ( isset($_POST['ccsender']) && $_POST['ccsender'] != '' )
       $_POST['ccsender'] = attribute_escape($_POST['ccsender']);
     if ( isset($_POST['recv']) && $_POST['recv'] != '' )
       $_POST['recv'] = attribute_escape($_POST['recv']); 
     if ( isset($_POST['title']) && $_POST['title'] != '' )
       $_POST['title'] = stripslashes($_POST['title']);
     // entferne die von wordpress automatisch beigefügten slashes
     if ( isset($_POST['message']) && $_POST['message'] != '' )  
       $_POST['message'] =  stripslashes($_POST['message']);
     
     if ( ! check_email($_POST['sender']) ) {
       $_POST['action'] = "Formular";
       echo __("Invalid sender  mail address.","wp-greet")."<br />";
     }
     if ( ! check_email($_POST['recv']) ) {
       $_POST['action'] = "Formular";
       echo __("Invalid recipient mail address.","wp-greet")."<br />";
     }

     // pruefe captcha 
     if ( $wpg_options['wp-greet-captcha'] and isset($_POST['public_key'])) {
       require_once(ABSPATH . "wp-content/plugins/captcha/captcha.php");
       $Cap = new Captcha();
       $Cap->debug = false;
       $Cap->public_key=$_POST['public_key'];

       if (! $Cap->check_captcha($Cap->public_key_id(),$_POST['captcha']) ) {
	 $_POST['action'] = "Formular";
	 echo __("No valid captcha entry.<br />","wp-greet");
	 echo __("Please try again:<br />Tip: If you cannot identify the chars, you can generate a new image. Using Reload.","wp-greet")."<br />";
       } 
     }
   }


   

  // Vorschau
  if ( $_POST['action'] == __("Preview","wp-greet") ) {

    // Vorschau anzeigen
    $out  = "</p><table><tr><th>". __("From","wp-greet").":</th><td>". $_POST['sender'];
    if ($_POST['ccsender'] == '1')
      $out .= " (".__("CC","wp-greet").")";

    $out .= "</td></tr>";
    $out .= "<tr><th>" . __("To","wp-greet").":</th><td>".  $_POST['recv'] . "</td></tr>"; 
    $out .= "<tr><th>" .  __("Subject","wp-greet").":</th><td>". attribute_escape($_POST['title']) . "</td></tr></table>";
    $out .= $wpg_options['wp-greet-default-header'] . "\n";
    $out .= '<p><img src="' . $picurl . '" width="'.$wpg_options['wp-greet-imagewidth'] .'" alt="wp-greet-image" /></p><br />';
    $out .= "\n<p>" . nl2br(attribute_escape($_POST['message'])) . "</p>\n";
    $out .= $wpg_options['wp-greet-default-footer'];


    // steuerungs informationen
    $out .= "<form method='post' action=''>";
    $out .= "<input name='sender' type='hidden' value='" . $_POST['sender']  . "' />\n";
    $out .= "<input name='ccsender' type='hidden' value='" . $_POST['ccsender']  . "' />\n";
    $out .= "<input name='recv' type='hidden' value='" . $_POST['recv']  . "' />\n"; 
    $out .= "<input name='title' type='hidden' value='" . attribute_escape($_POST['title'])  . "' />\n"; 
    $out .= "<input name='message' type='hidden' value='" . attribute_escape($_POST['message']) . "' />\n";

    $out .= "<input name='action' type='submit' value='".__("Back","wp-greet")."' /><input name='action' type='submit'  value='".__("Send","wp-greet")."' /></form><p>&nbsp;";

  }  else if ( $_POST['action'] == __("Send","wp-greet") ) {
    // ---------------------------------------------------------------------
    // Mail senden
    // ----------------------------------------------------------------------
    //
    // zuerst werte fuer beide mail varianten ermitteln
    //
    // add default subject or the one given via the form
    //if ( $wpg_options['wp-greet-default-title'] !="" )
    //  $subj = $wpg_options['wp-greet-default-title'];
    //else
    //$subj = @html_entity_decode($_POST['title'],ENT_QUOTES,"UTF-8");
    $subj = $_POST['title'];

 
    //
    // zuerst pruefen ob inline images moeglich und gewuenscht sind
    // wenn inline true wird, dann wird phpmailer zum versenden der mail 
    // mit eingebettetem bild verwendet, sonst wird die php mail() 
    // funktion verwenet.

    $inline = false;
    if ( $wpg_options['wp-greet-imgattach']) 
      $inline = true;
 
    
    // html message bauen
    $message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
    $message .= "<title>".$subj."</title>\n</head><body>";
    $message .= $wpg_options['wp-greet-default-header'] . "\r\n";
    if ($inline)
      $message .= "<p><img src=\"cid:wpgreetimg\" alt=\"wp-greet card image\" width=\"".$wpg_options['wp-greet-imagewidth']."\"/></p>";
    else
      $message .= "<p><img src='".$picurl ."' width='".$wpg_options['wp-greet-imagewidth'] ."' /></p>";
    $message .= "<br />";
    $message .= "\r\n" . nl2br(attribute_escape($_POST['message'])) . "\r\n";
    $message .= "<p>". $wpg_options['wp-greet-default-footer']. "</p>\r\n";
    $message .= "</body></html>";


    // mail senden in abhängigkeit von inline ja/nein
    if ( $inline ) {
      // mail senden mit phpmailer
      require_once(ABSPATH . "/wp-includes/class-phpmailer.php");
      require("phpmailer-conf.php");
      
      $mail = new PHPMailer();
      $mail->IsSMTP();                // set mailer to use SMTP
      $mail->CharSet = 'utf-8';       // set mail encoding
      
      $mail->Host = $wpg_smtpserver;  
      if ( $wpg_smtpuser != "" and $wpg_smtppass !="") {
	$mail->SMTPAuth = true;     // turn on SMTP authentication
	$mail->Username = $wpg_smtpuser;  // SMTP username
	$mail->Password = $wpg_smtppass; // SMTP password
      }
      
      $mail->From = addslashes($_POST['sender']);
      $mail->FromName = "<" . $_POST['sender'] . ">";
      
      $mail->AddAddress( $_POST['recv'],  $_POST['recv']);
      
      if ( $wpg_options['wp-greet-mailreturnpath'] !="" )
	$mail->AddReplyTo( $wpg_options['wp-greet-mailreturnpath'], $wpg_options['wp-greet-mailreturnpath'] );
      
      // add bcc if option is set
      if ( $wpg_options['wp-greet-bcc'] !="" )
	$mail->AddBCC($wpg_options['wp-greet-bcc']);
      
      // add cc if option is set
      if ( $_POST['ccsender'] == '1' ) 
	$mail->AddCC($_POST['sender']);
      

      $mail->WordWrap = 50;           // set word wrap to 50 characters
	
      // aus der url des bildes den dateinamen bauen
      $surl=get_option('siteurl');
      $picpath = ABSPATH . substr($picurl, strpos($picurl, $surl)+ strlen($surl)+1);
      $picfile = substr($picurl, strrpos($picurl,"/") +1 );
      $mtype = get_mimetype($picfile);

      // und ans mail haengen
      $mail->AddEmbeddedImage($picpath,"wpgreetimg",$picfile,"base64",$mtype);

      $mail->IsHTML(true);                     // set email format to HTML
      $mail->Subject = $subj;                  // subject hinzufuegen
      $mail->Body = $message;                  // nachricht hinzufuegen

      //$mail->AltBody = "Your mail-client is either in text mode or does not support HTML Mail.Sorry.";

      if ( $mail->Send()) {
	$out = __("Your greeting card has been sent.","wp-greet")."<br />";
	// create log entry
	log_greetcard($_POST['recv'],addslashes($_POST['sender']),$picurl,$message);
      } else {
	$out = __("An error occured while sending you greeting card.","wp-greet")."<br />";
	$out .= __("Problem report","wp-greet") . " " . $mail->ErrorInfo;
      }
	

    } else {
      // mail senden ohne phpmailer und inline images	
      // header bauen
      $header = "From: \"".addslashes($_POST['sender'])."\" <".$_POST['sender'].">\n";   
      // add bcc if option is set
      if ( $wpg_options['wp-greet-bcc'] !="" )
	$header .= "Bcc: " . $wpg_options['wp-greet-bcc'] . "\n";
      
      // add reply to if option is set
      if ( $wpg_options['wp-greet-mailreturnpath'] !="" )
	$header .= "Reply-To: ".$wpg_options['wp-greet-mailreturnpath']."\n";
      
      $header .= "MIME-Version: 1.0\n"; // ohne \r das ist wichtig
      $header .= "Content-Type: text/html; charset=utf-8\r\n";

      $stat = mail  ( $_POST['recv'] , $subj , $message, $header);
      if ( $stat) {
	$out = __("Your greeting card has been sent.","wp-greet")."<br />";
	// create log entry
	log_greetcard($_POST['recv'],addslashes($_POST['sender']),$picurl,$message);
      } else {
	$out = __("An error occured while sending you greeting card.","wp-greet")."<br />";
      }
    }
    
  } else {
    
    // Formular anzeigen
    $captcha = false;
    if ( $wpg_options['wp-greet-captcha']) {
      require_once(ABSPATH . "wp-content/plugins/captcha/captcha.php");
      $Cap = new Captcha();
      $Cap->debug = false;	
      $Cap->public_key = intval($_GET['x']);
      $captcha = true;
    }


    $out = "&nbsp;</p><div class='wp-greet-form'>\n";
    $out .= '<img src="' . $picurl . '" alt="'.basename($picurl)."\" width='".$wpg_options['wp-greet-imagewidth']."'/><br />\n";
    $out .= "<form method='post' action=''>\n";
    $out .= '<table class="wp-greet-form"><tr class="wp-greet-form"><td class="wp-greet-form">'.__("Sender","wp-greet").':</td><td class="wp-greet-form"><input name="sender" type="text" size="40" maxlength="80" value="' . $_POST['sender']  . '"/></td></tr>'."\n";
    $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form\">".__("CC to Sender","wp-greet").":</td><td class=\"wp-greet-form\"><input name='ccsender' type='checkbox' value='1' " . ($_POST['ccsender']==1 ? 'checked="checked"':'')  . " /></td></tr>\n";
     $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form\">".__("Recipient","wp-greet").":</td><td class=\"wp-greet-form\"><input name='recv' type='text' size='40' maxlength='80' value='" . $_POST['recv']  . "'/></td></tr>\n";
     $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form\">".__("Subject","wp-greet").":</td><td class=\"wp-greet-form\"><input name='title'  type='text' size='40' maxlength='80' value='" . attribute_escape($_POST['title'])  . "'/></td></tr>\n";
     // $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form\">".__("Message","wp-greet").":</td><td class=\"wp-greet-form\"><textarea name='message' cols='50' rows='10'>" . attribute_escape($_POST['message']) . "</textarea></td></tr>\n"; 
 $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form\">".__("Message","wp-greet").":</td><td class=\"wp-greet-form\"><textarea class=\"wp-greet-form\" name='message'>" . attribute_escape($_POST['message']) . "</textarea></td></tr>\n";
    if ($captcha)
      $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form\">". __("Captcha-protect:")."</td><td class=\"wp-greet-form\" >".$Cap->display_captcha()."&nbsp;<input name=\"captcha\" type=\"text\" size=\"10\" maxlength=\"10\" />"."</td></tr>";
    $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form\">&nbsp;</td><td class=\"wp-greet-form\"><input name='action' type='submit' value='".__("Preview","wp-greet")."' /><input name='action' type='submit'  value='".__("Send","wp-greet")."' /><input type='reset' value='".__("Reset form","wp-greet")."'/>&nbsp;<a href=\"javascript:history.back()\">".__("Back","wp-greet")."</a></td></tr></table></form></div>\n<p>&nbsp;";
   
  }

  // Rueckgabe des HTML Codes
  return $out;
}
?>