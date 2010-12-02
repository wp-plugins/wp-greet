<?php
/* This file is part of the wp-greet plugin for wordpress */

/*  Copyright 2009  Hans Matzen  (email : webmaster at tuxlog dot de)

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

// include common functions
 require_once("wpg-func.php");

//
// this function sends the greeting card mail
//
// $sender     - sender email
// $sendername - sender name
// $recv       - receiver email
// $recvname   - receiver name
// $title      - mailsubject
// $msgtext    - the mail mesage
// $ccsender   - if 1 then the sender will receive a copy of the mail
// $debug      - if true SMTP Mailerclass debugger will be turned on
// $picurl     - url to the greet card picture
//
// returns mail->ErrInfo when error occurs or true when everything went wright
//
function sendGreetcardMail($sender,$sendername,$recv,$recvname,$title,
			   $msgtext,$picurl,$ccsender,$debug=false) 
{ 
    require_once(ABSPATH . "/wp-includes/class-phpmailer.php");
    require("phpmailer-conf.php");
    
    // hole optionen
    $wpg_options = wpgreet_get_options();
    
    // get translation 
    $locale = get_locale();
    if ( empty($locale) )
	$locale = 'en_US';
    if(function_exists('load_textdomain')) 
	load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
    
  
    //
    // hole gewünschte mail methode
    // wenn usesmtp true wird, dann wird phpmailer zum versenden der mail 
    // mit eingebettetem bild verwendet, sonst wird die php mail() 
    // funktion verwendet.
    //
    $usesmtp = false;
    if ( $wpg_options['wp-greet-usesmtp']) 
	$usesmtp = true;
    
    //
    // inline images gehen nur mit smtp versand
    // pruefen ob inline images gewuenscht sind
    //
    $inline = false;
    if ( $usesmtp && $wpg_options['wp-greet-imgattach']) 
	$inline = true;
    
    
    // html message bauen
    $message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
    $message .= "<title>".$title."</title>\n</head><body>";
    $message .= $wpg_options['wp-greet-default-header'] . "\r\n";
    if ($inline)
	$message .= "<p><img src=\"cid:wpgreetimg\" alt=\"wp-greet card image\" width=\"".$wpg_options['wp-greet-imagewidth']."\"/></p>";
    else
	$message .= "<p><img src='".$picurl ."' width='".$wpg_options['wp-greet-imagewidth'] ."' /></p>";
    $message .= "<br />";
    
    
    // nachrichtentext escapen
    $msgtext = nl2br(attribute_escape($msgtext));
    
    // smilies ersetzen
    if ( $wpg_options['wp-greet-smilies']) { 
	
	$smprefix = get_settings('siteurl') . '/wp-content/plugins/wp-greet/smilies/';
	preg_match_all('(:[^\040]+:)', $msgtext, $treffer);
	
	foreach ($treffer[0] as $sm) {
	    if ($inline) 
		$smrep='<img src="cid:'.substr($sm,1,strlen($sm)-2).'" alt="wp-greet smiley" />';
	    else
		$smrep='<img src="' . $smprefix . substr($sm,1,strlen($sm)-2) . '" alt="'.substr($sm,1,strlen($sm)-2).'" />';
	    $msgtext = str_replace($sm,$smrep,$msgtext);
	}
    }
    
    $message .= "\r\n" . $msgtext . "\r\n";
    $message .= "<p>". $wpg_options['wp-greet-default-footer']. "</p>\r\n";
    $message .= "</body></html>";
    
    // jetzt nehmen wir den eigentlichen mail versand vor
    $mail = new PHPMailer();
    $mail->SMTPDebug=$debug;          // for debugging
    if ($usesmtp) {
	$mail->IsSMTP();                // set mailer to use SMTP
	$mail->Host = $wpg_smtpserver;  
	if ( $wpg_smtpuser != "" and $wpg_smtppass !="") {
	    $mail->SMTPAuth = true;           // turn on SMTP authentication
	    $mail->Username = $wpg_smtpuser;  // SMTP username
	    $mail->Password = $wpg_smtppass;  // SMTP password
	}
    } else { 
	$mail->IsMail();                    // set mailer to mail
	$mail->Sender = addslashes($sender); 
    }
    $mail->CharSet = 'utf-8';         // set mail encoding
    
    $mail->From = addslashes($sender);
    $mail->FromName = addslashes($sendername) ;
    $mail->AddAddress( $recv, $recvname);
    
    if ( $wpg_options['wp-greet-mailreturnpath'] !="" )
	$mail->AddReplyTo( $wpg_options['wp-greet-mailreturnpath'], $wpg_options['wp-greet-mailreturnpath'] );
    
    // add bcc if option is set
    if ( $wpg_options['wp-greet-bcc'] !="" )
	$mail->AddBCC($wpg_options['wp-greet-bcc']);
    
    // add cc if option is set
    if ( $ccsender == '1' ) 
	$mail->AddCC($sender);
    
    $mail->WordWrap = 50;           // set word wrap to 50 characters
    
    // inline image anfügen
    if ($inline) 
    { 
	// mit briefmarke
	if (trim ($wpg_options['wp-greet-stampimage']) !="")
	{
	    // briefmarke einbauen
	    // aus der url des bildes den dateinamen bauen 
	    $surl=get_option('siteurl');
	    $picpath = ABSPATH . substr($picurl, strpos($picurl, $surl) + strlen($surl)+1);
	    $stampurl = site_url("wp-content/plugins/wp-greet/").
		"wpg-stamped.php?cci=$picpath&sti=".
		ABSPATH . $wpg_options['wp-greet-stampimage'].
		"&stw=" . $wpg_options['wp-greet-stamppercent']. 
		"&ob=1";
	    
	    $resp = wp_remote_request($stampurl, array('timeout' => 10));
	    $stampedimg = $resp['body'];
	    $picfile = substr($picurl, strrpos($picurl,"/") +1 );
	    // und ans mail haengen 
	    
	    $cur = count($mail->attachment);
	    $mail->attachment[$cur][0] = $stampedimg;
	    $mail->attachment[$cur][1] = $picfile;
	    $mail->attachment[$cur][2] = $picfile;
	    $mail->attachment[$cur][3] = "base64";
	    $mail->attachment[$cur][4] = "image/png";
	    $mail->attachment[$cur][5] = true;
	    $mail->attachment[$cur][6] = 'inline';
	    $mail->attachment[$cur][7] = "wpgreetimg";
	    
	    // ohne briefmarke   
	} else {
	    // aus der url des bildes den dateinamen bauen
	    $surl=get_option('siteurl');
	    $picpath = ABSPATH . substr($picurl, strpos($picurl, $surl)+ strlen($surl)+1);
	    $picfile = substr($picurl, strrpos($picurl,"/") +1 );
	    $mtype = get_mimetype($picfile);
	    
	    // und ans mail haengen
	    $mail->AddEmbeddedImage($picpath,"wpgreetimg",$picfile,"base64",$mtype);
	}
	
	// smileys an die mail haengen, wenn inline aktiviert ist
	if ( $wpg_options['wp-greet-smilies']) { 
	    foreach ($treffer[0] as $sm) {
		// aus dem namen des bildes den dateinamen bauen
		$picpath = ABSPATH . "wp-content/plugins/wp-greet/smilies/";
		$picfile = substr($sm, 1, strlen($sm)-2 );
		$mtype = get_mimetype($picfile);
		
		// und ans mail haengen
		$mail->AddEmbeddedImage($picpath."/".$picfile,$picfile,$picfile,"base64",$mtype);
	    }
	}
    }
    
    $mail->IsHTML(true);                     // set email format to HTML
    $mail->Subject = $title;                 // subject hinzufuegen
    $mail->Body = $message;                  // nachricht hinzufuegen
    
    
    if ( $mail->Send()) 
	return true;
    else 
	return $mail->ErrorInfo;
}


//
// this function sends the confirmation mail
//
// $sender     - sender email
// $sendername - sender name
// $recv       - receiver email
// $recvname   - receiver name
// $debug      - if true SMTP Mailerclass debugger will be turned on
// $confirmcode - uniquie code for validation
// $confirmuntil - time until the confiramtion has to be done
//
// returns mail->ErrInfo when error occurs or true when everything went wright
//
function sendConfirmationMail($sender,$sendername,$recvname,$confirmcode, $confirmuntil, $debug=false) 
{ 
    require_once(ABSPATH . "/wp-includes/class-phpmailer.php");
    require("phpmailer-conf.php");

    global $wpdb;
    
    // hole optionen
    $wpg_options = wpgreet_get_options();
    
    // get translation 
    $locale = get_locale();
    if ( empty($locale) )
	$locale = 'en_US';
    if(function_exists('load_textdomain')) 
	load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
    
  
    //
    // hole gewünschte mail methode
    // wenn usesmtp true wird, dann wird phpmailer zum versenden der mail 
    // mit eingebettetem bild verwendet, sonst wird die php mail() 
    // funktion verwendet.
    //
    $usesmtp = false;
    if ( $wpg_options['wp-greet-usesmtp']) 
	$usesmtp = true;
   
    // mail betreff aufbauen
    $subj = get_option("blogname")." - " . __("Greeting Card Confirmation Mail","wp-greet");
    
    $url_prefix = get_permalink($wpg_options['wp-greet-formpage'],false);

    $folder_url  = get_option ('siteurl')."/".$picture->path."/";
    if (strpos($url_prefix,"?") === false )
	$url_prefix .= "?";
    else
	$url_prefix .= "&";
    $confirmlink = stripslashes($url_prefix . "verify=" . $confirmcode );
    $confirmlink = '<a href="' . $confirmlink . '">' . $confirmlink . '</a>';


    // html message bauen
    $message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
    $message .= "<title>". $subj . "</title>\n</head><body>";
    $message .= "<br />";
    
    // hole nachrichten text
    $msgtext = $wpg_options['wp-greet-mctext'];
    // nachrichtentext escapen
    $msgtext = nl2br(attribute_escape($msgtext));
    $msgtext = str_replace("%sender%",$sendername,$msgtext);
    $msgtext = str_replace("%sendermail%",$sender,$msgtext);
    $msgtext = str_replace("%receiver%",$recvname,$msgtext);
    $msgtext = str_replace("%link%",$confirmlink,$msgtext);
    $msgtext = str_replace("%duration%",$wpg_options['wp-greet-mcduration'],$msgtext);
    

    $message .= "\r\n" . $msgtext . "\r\n";
    $message .= "</body></html>";
    
    // jetzt nehmen wir den eigentlichen mail versand vor
    $mail = new PHPMailer();
    $mail->SMTPDebug=$debug;          // for debugging
    if ($usesmtp) {
	$mail->IsSMTP();                // set mailer to use SMTP
	$mail->Host = $wpg_smtpserver;  
	if ( $wpg_smtpuser != "" and $wpg_smtppass !="") {
	    $mail->SMTPAuth = true;           // turn on SMTP authentication
	    $mail->Username = $wpg_smtpuser;  // SMTP username
	    $mail->Password = $wpg_smtppass;  // SMTP password
	}
    } else { 
	$mail->IsMail();                    // set mailer to mail
	$mail->Sender = addslashes($sender); 
    }
    $mail->CharSet = 'utf-8';         // set mail encoding
    
    $mail->From = addslashes( ($wpg_options['wp-greet-mailreturnpath']!=""? $wpg_options['wp-greet-mailreturnpath']:get_option("admin_email")) );
    $mail->FromName = addslashes(get_option("blogname")) ;
    $mail->AddAddress( $sender, $sendername);
    
    if ( $wpg_options['wp-greet-mailreturnpath'] !="" )
	$mail->AddReplyTo( $wpg_options['wp-greet-mailreturnpath'], $wpg_options['wp-greet-mailreturnpath'] );
    
    // add bcc if option is set
    if ( $wpg_options['wp-greet-bcc'] !="" )
	$mail->AddBCC($wpg_options['wp-greet-bcc']);
    
    $mail->WordWrap = 50;           // set word wrap to 50 characters
    
    $mail->IsHTML(true);                     // set email format to HTML
    $mail->Subject = $subj;                 // subject hinzufuegen
    $mail->Body = $message;                  // nachricht hinzufuegen
    
    if ( $mail->Send()) 
	return true;
    else 
	return $mail->ErrorInfo;
}


//
// this function sends the link mail
//
// $sender     - sender email
// $sendername - sender name
// $recv       - receiver email
// $recvname   - receiver name
// $debug      - if true SMTP Mailerclass debugger will be turned on
// $duration   - number of days the card can be fetched
// $fetchcode  - code to fetch the greet card
//
// returns mail->ErrInfo when error occurs or true when everything went wright
//
function sendGreetcardLink($sender,$sendername,$recv, $recvname,$duration, $fetchcode, $debug=false) 
{ 
    require_once(ABSPATH . "/wp-includes/class-phpmailer.php");
    require("phpmailer-conf.php");

    global $wpdb;
    
    // hole optionen
    $wpg_options = wpgreet_get_options();
    
    // get translation 
    $locale = get_locale();
    if ( empty($locale) )
	$locale = 'en_US';
    if(function_exists('load_textdomain')) 
	load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
    
  
    //
    // hole gewünschte mail methode
    // wenn usesmtp true wird, dann wird phpmailer zum versenden der mail 
    // mit eingebettetem bild verwendet, sonst wird die php mail() 
    // funktion verwendet.
    //
    $usesmtp = false;
    if ( $wpg_options['wp-greet-usesmtp']) 
	$usesmtp = true;
   
    // mail betreff aufbauen
    $subj = get_option("blogname")." - " . __("A Greeting Card for you","wp-greet");
    
    // abruflink aufbauen
    $url_prefix = get_permalink($wpg_options['wp-greet-formpage'],false);
 
    if (strpos($url_prefix,"?") === false )
	$url_prefix .= "?";
    else
	$url_prefix .= "&";
    $confirmlink = stripslashes($url_prefix . "verify=" . $confirmcode );
    $folder_url  = get_option ('siteurl')."/".$picture->path."/";
    $fetchlink = stripslashes($url_prefix . "display=" . $fetchcode );
    $fetchlink = '<a href="' . $fetchlink . '">' . $fetchlink . '</a>';
 

    // html message bauen
    $message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
    $message .= "<title>". $subj . "</title>\n</head><body>";
    $message .= "<br />";
    
    // hole nachrichten text
    $msgtext = $wpg_options['wp-greet-octext'];
    // nachrichtentext escapen
    $msgtext = nl2br(attribute_escape($msgtext));
    $msgtext = str_replace("%sender%",$sendername,$msgtext);
    $msgtext = str_replace("%sendermail%",$sender,$msgtext);
    $msgtext = str_replace("%receiver%",$recvname,$msgtext);
    $msgtext = str_replace("%link%",$fetchlink,$msgtext);
    $msgtext = str_replace("%duration%",$duration,$msgtext);


    $message .= "\r\n" . $msgtext . "\r\n";
    $message .= "</body></html>";
    
    // jetzt nehmen wir den eigentlichen mail versand vor
    $mail = new PHPMailer();
    $mail->SMTPDebug=$debug;          // for debugging
    if ($usesmtp) {
	$mail->IsSMTP();                // set mailer to use SMTP
	$mail->Host = $wpg_smtpserver;  
	if ( $wpg_smtpuser != "" and $wpg_smtppass !="") {
	    $mail->SMTPAuth = true;           // turn on SMTP authentication
	    $mail->Username = $wpg_smtpuser;  // SMTP username
	    $mail->Password = $wpg_smtppass;  // SMTP password
	}
    } else { 
	$mail->IsMail();                    // set mailer to mail
	$mail->Sender = addslashes($sender); 
    }
    $mail->CharSet = 'utf-8';         // set mail encoding
    
    $mail->From = addslashes( ($wpg_options['wp-greet-mailreturnpath']!=""? $wpg_options['wp-greet-mailreturnpath']:get_option("admin_email")) );
    $mail->FromName = addslashes(get_option("blogname")) ;
    $mail->AddAddress( $recv, $recvname);
    
    // add cc if option is set
    if ( $ccsender == '1' ) 
	$mail->AddCC($sender);

    if ( $wpg_options['wp-greet-mailreturnpath'] !="" )
	$mail->AddReplyTo( $wpg_options['wp-greet-mailreturnpath'], $wpg_options['wp-greet-mailreturnpath'] );
    
    // add bcc if option is set
    if ( $wpg_options['wp-greet-bcc'] !="" )
	$mail->AddBCC($wpg_options['wp-greet-bcc']);
    
    $mail->WordWrap = 50;           // set word wrap to 50 characters
    
    $mail->IsHTML(true);                     // set email format to HTML
    $mail->Subject = $subj;                 // subject hinzufuegen
    $mail->Body = $message;                  // nachricht hinzufuegen
    
    if ( $mail->Send()) 
	return true;
    else 
	return $mail->ErrorInfo;
}
?>