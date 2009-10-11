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

// if called directly, get parameters from GET and output the greetcardform
if ( preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 
    require_once("wpg-func.php");
    require_once( dirname(__FILE__) . '/../../../wp-config.php');
    
    // direktaufruf des formulars
    if ( isset($_GET['gallery']) and isset($_GET['image']) ) {
	// get post vars
	$galleryID=attribute_escape(isset($_GET['gallery'])?$_GET['gallery']:'');
	$picurl=attribute_escape($_GET['image']);
	
	$out = showGreetcardForm($galleryID,$picurl);
	echo $out;
    }
}  

// apply the filter to the page or post content
function searchwpgreet($content) {
    
    // look for wp-greet tag
    if ( stristr( $content, '[wp-greet]' )) {
	
	// get GET vars
	$galleryID = attribute_escape(isset($_GET['gallery'])?$_GET['gallery']:'');
	$picurl    = attribute_escape($_GET['image']);
	$verify    = attribute_escape($_GET['verify']);
	$display   = attribute_escape($_GET['display']);

	// Karte wird abgeholt
	if ($display !="") {
	    $content = showGreetcard($display);
	} else {
	    // replace tag with html form
	    $search    = '[wp-greet]';
	    $replace   = showGreetcardForm($galleryID,$picurl,$verify); 
	    $content   = str_replace ($search, $replace, $content);
	}
    }
    
    return $content;
}

//
// this function controls the whole greetcard workflow and the forms
//
function showGreetcardForm($galleryID,$picurl,$verify = "") {
  global $userdata;
  
  // hole optionen
  $wpg_options = wpgreet_get_options();
  

  // ausgabebuffer init
  $out = "";

  // get translation 
  $locale = get_locale();
  if ( empty($locale) )
    $locale = 'en_US';
  if(function_exists('load_textdomain')) 
    load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");

  // ---------------------------------------------------------------------
  //  bestätigungsaufruf für den grußkartenversand
  //
  // ---------------------------------------------------------------------
  if ( $verify !="" ) {
	
      global $wpdb;
      $sql="select * from " . $wpdb->prefix . "wpgreet_cards where confirmcode='" . $verify ."';";
      $res = $wpdb->get_row($sql);
      
      $now = strtotime( gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 )));
      $then = strtotime( $res->confirmuntil);
	
      if ( is_null($res)) {
	  // ungültiger code
	  $out .= __("Your verification code is invalid.","wp-greet")."<br />" . 
	      __("Please send a new card at","wp-greet") .
	      " <a href='" . site_url()."' >".site_url()."</a>";
	  return $out;

      } else if ($res->card_sent != 0) {
	  // karte wurde bereits versendet
	  $out .= __("Your greeting card has already been sent.","wp-greet")."<br />" . 
	       __("Please send a new card at","wp-greet") . 
	      " <a href='" . site_url()."' >".site_url()."</a>";
	  return $out;
	  
	  
      } else if ($now > $then ) {
	  // die gültigkeiteisdauer ist abgelaufen 
	  $out .= __("Your confirmation link is timedout.","wp-greet")."<br />".
	      __("Please send a new card at","wp-greet") . 
	      " <a href='" . site_url()."' >".site_url()."</a>";
	  return $out;

      } else {
	  // alles okay, karte versenden
	  $_POST['action']     = __("Send","wp-greet");
	  $_POST["sender"]     = $res->frommail;
	  $_POST["sendername"] = $res->fromname;
	  $_POST["recv"]       = $res->tomail;
	  $_POST["recvname"]   = $res->toname;
	  $_POST["title"]      = $res->subject;
	  $_POST["message"]    = $res->mailbody;
	  $_POST["ccsender"]   = $res->cc2from;
	  $picurl              = $res->picture;
	  $galleryID           = "";
      }
  }  


  // pruefe berechtigung zum versenden von grusskarten
  if ( !current_user_can('wp-greet-send') 
       and $wpg_options['wp-greet-minseclevel']!="everyone" ) {
    return "<p><b>".__("You are not permitted to send greeting cards.","wp-greet")."<br />".__("Please contact you wordpress Administrator.","wp-greet")."</b></p>";
  }
  

  // uebernehme user daten bei erstaufruf
  if ( ! isset($_POST['action']) ) {
    get_currentuserinfo();
    $_POST['sender'] = $userdata->user_email;
  }

  // uebernehme default subject bei erstufruf
  if ( ! isset($_POST['title']) ) 
    $_POST['title'] =  $wpg_options['wp-greet-default-title'];
  

  // Feldinhalte pruefen
  if ( isset($_POST['action']) and 
       ( $_POST['action'] == __("Preview","wp-greet") or  
	 $_POST['action'] ==  __("Send","wp-greet") ) ) {

     if ( isset($_POST['sender']) && $_POST['sender'] != '' )
       $_POST['sender'] = attribute_escape($_POST['sender']);
     if ( isset($_POST['sendername']) && $_POST['sendername'] != '' )
       $_POST['sendername'] = attribute_escape($_POST['sendername']);
     if ( isset($_POST['ccsender']) && $_POST['ccsender'] != '' )
       $_POST['ccsender'] = attribute_escape($_POST['ccsender']);
     if ( isset($_POST['recv']) && $_POST['recv'] != '' )
       $_POST['recv'] = attribute_escape($_POST['recv']); 
     if ( isset($_POST['recvname']) && $_POST['recvname'] != '' )
       $_POST['recvname'] = attribute_escape($_POST['recvname']); 
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
     if ( ($wpg_options['wp-greet-captcha'] > 0) and (isset($_POST['public_key']) or isset($_POST['mcspinfo']))) {

       // check CaptCha!
       if ($wpg_options['wp-greet-captcha']==1) {
	 require_once(ABSPATH . "wp-content/plugins/captcha/captcha.php");
	 
	 $Cap = new Captcha();
	 $Cap->debug = false;
	 $Cap->public_key=$_POST['public_key'];
	 
	 if (! $Cap->check_captcha($Cap->public_key_id(),$_POST['captcha']) ) {
	   $_POST['action'] = "Formular";
	   echo __("Spamprotection - Code is not valid.<br />","wp-greet");
	   echo __("Please try again.<br />Tip: If you cannot identify the chars, you can generate a new image. Using Reload.","wp-greet")."<br />";
	 } 
       }	 
       // check Math Protect
       if ($wpg_options['wp-greet-captcha']==2) {
	 require_once(ABSPATH . "wp-content/plugins/math-comment-spam-protection/math-comment-spam-protection.classes.php");

	 $Cap = new MathCheck();
	 if ( $Cap->InputValidation( $_POST['mcspinfo'], $_POST['mcspvalue']) !="") {
	   $_POST['action'] = "Formular";
	   echo __("Spamprotection - Code is not valid.<br />","wp-greet");
	   echo __("Please try again.","wp-greet")."<br />"; 
	 }
       } // end of pruefe captcha 

       // nutzungsbedingungen prüfen
       if ($wpg_options['wp-greet-touswitch']==1 and  $_POST['accepttou'] != 1) {
	   $_POST['action'] = "Formular";
	   echo __("Please accept the terms of usage before sending a greeting card.<br />","wp-greet");
       }
       
     } // end of Feldinhalte pruefen
  } // end of if action
  
 
  // Vorschau
  if ( isset($_POST['action']) and 
       $_POST['action'] == __("Preview","wp-greet") ) {

    // message escapen
    $show_message = nl2br(attribute_escape($_POST['message']));

    // smilies ersetzen
    if ( $wpg_options['wp-greet-smilies']) { 
      $smprefix = get_settings('siteurl') . '/wp-content/plugins/wp-greet/smilies/';
      preg_match_all('(:[^\040]+:)', $show_message, $treffer);

      foreach ($treffer[0] as $sm) {
	$smrep='<img src="' . $smprefix . substr($sm,1,strlen($sm)-2) . '" alt='.$sm.'/>';
	$show_message = str_replace($sm,$smrep,$show_message);
      }
    }

    // Vorschau anzeigen
    $out  = "&nbsp;</p><table><tr><th>". __("From","wp-greet").":</th><td>". $_POST['sendername'] . "&nbsp;&lt;" . $_POST['sender'] . "&gt;";
    if ($_POST['ccsender'] == '1')
      $out .= " (".__("CC","wp-greet").")";

    $out .= "</td></tr>";
    $out .= "<tr><th>" . __("To","wp-greet").":</th><td>".   $_POST['recvname'] . "&nbsp;&lt;". $_POST['recv'] . "&gt;</td></tr>"; 
    $out .= "<tr><th>" .  __("Subject","wp-greet").":</th><td>". attribute_escape($_POST['title']) . "</td></tr></table>";
    $out .= $wpg_options['wp-greet-default-header'] . "\n";
    
    
    if (trim ($wpg_options['wp-greet-stampimage']) !="")
    {
	// briefmarke einbauen
	// aus der url des bildes den dateinamen bauen 
	$surl=get_option('siteurl');
	$picpath = ABSPATH . substr($picurl, 
				    strpos($picurl, $surl) + strlen($surl)+1);
    
	$out .= '<p><img src="' . site_url("wp-content/plugins/wp-greet/").
	    "wpg-stamped.php?cci=$picpath&amp;sti=".
	    ABSPATH . $wpg_options['wp-greet-stampimage'].
	    "&amp;stw=" . $wpg_options['wp-greet-stamppercent'].
	    '" alt="'.basename($picurl)."\" width='".
	    $wpg_options['wp-greet-imagewidth']."'/></p><br />\n";
    } else {
	$out .= '<p><img src="' . $picurl . '" width="'.$wpg_options['wp-greet-imagewidth'] .'" alt="wp-greet-image" /></p><br />';
    }
    $out .= "\n<p>" . $show_message . "</p>\n";
    $out .= $wpg_options['wp-greet-default-footer'];


    // steuerungs informationen
    $out .= "<form method='post' action=''>";
    $out .= "<input name='sender' type='hidden' value='" . $_POST['sender']  . "' />\n";
    $out .= "<input name='sendername' type='hidden' value='" . $_POST['sendername']  . "' />\n";
    $out .= "<input name='ccsender' type='hidden' value='" . $_POST['ccsender']  . "' />\n";
    $out .= "<input name='recv' type='hidden' value='" . $_POST['recv']  . "' />\n"; 
    $out .= "<input name='recvname' type='hidden' value='" . $_POST['recvname']  . "' />\n"; 
    $out .= "<input name='title' type='hidden' value='" . attribute_escape($_POST['title'])  . "' />\n"; 
    $out .= "<input name='message' type='hidden' value='" . attribute_escape($_POST['message']) . "' />\n";

    $out .= "<input name='action' type='submit' value='".__("Back","wp-greet")."' /><input name='action' type='submit'  value='".__("Send","wp-greet")."' /></form><p>&nbsp;";

  }  else if ( isset($_POST['action']) and 
	       $_POST['action'] == __("Send","wp-greet") and
	       ($wpg_options['wp-greet-mailconfirm'] != "1" or $verify !="") ) {
      // ---------------------------------------------------------------------
      // Grußkarten Mail senden oder Grußkarten Link Mail senden
      // ----------------------------------------------------------------------
      //
      if ( $wpg_options['wp-greet-onlinecard'] == 1) { 
          // grußkarten link mail senden
	  require_once("wpg-func-mail.php");
          // karte ablegen inkl. bestätigungscode
	  $fetchcode  = uniqid("wpgreet_",false);
	  $fetchuntil = gmdate("Y-m-d H:i:s",time() + 
			     ( get_option('gmt_offset') * 60 * 60 ) + 
			     ( $wpg_options['wp-greet-ocduration'] * 60 * 60 *24)  );
	  save_greetcard( 
	      $_POST['sender'],
	      $_POST['sendername'],
	      $_POST['recv'],
	      $_POST['recvname'],
	      $_POST['title'],
	      $_POST['message'],
	      $picurl,
	      $_POST['ccsender'],
	      "",                     // confirm until stays blank
	      $verify,                // confirmcode if available
	      $fetchuntil, 
	      $fetchcode);

	  // link mail senden
	  $sendstatus = sendGreetcardLink(
	      $_POST['sender'],
	      $_POST['sendername'],
	      $_POST['recv'],
	      $_POST['recvname'],
	      $wpg_options['wp-greet-ocduration'],
	      $fetchcode,
	      false);

      } else {  // grußkarten mail senden
	  require_once("wpg-func-mail.php");
	  $sendstatus = sendGreetcardMail(
	      $_POST['sender'],
	      $_POST['sendername'],
	      $_POST['recv'],
	      $_POST['recvname'],
	      $_POST['title'],
	      $_POST['message'],
	      $picurl,
	      $_POST['ccsender'],
	      false); 
      }
      
      if ( $sendstatus == true ) {
	  $out = __("Your greeting card has been sent.","wp-greet")."<br />";
	  // create log entry
	  log_greetcard($_POST['recv'],addslashes($_POST['sender']),$picurl,$_POST['message']);

	  // clean log and cards table
	  // we are doing this whenever a card has been successfully sent
	  // beacause wp-cron does not work properly at the moment
	  remove_cards();
	  remove_logs();

	  // haben wir eine karte mit bestätigungsverfahren gesendet, 
	  // dann markieren wir sie als versendet
	  if ( $verify != "" ) 
	      mark_sentcard($verify);
	  
      } else {

	  $out = __("An error occured while sending you greeting card.","wp-greet")."<br />";
	  $out .= __("Problem report","wp-greet") . " " . $sendstatus;
      }
  } else if ( isset($_POST['action']) and 
	      $_POST['action'] == __("Send","wp-greet") and
	      ( $wpg_options['wp-greet-mailconfirm'] == "1" or $verify == "") ) {    
       // ---------------------------------------------------------------------
      // Bestätigungsmail senden und Grußkarte inklusive bestätigungscode ablegen
      // ----------------------------------------------------------------------
      //
      require_once("wpg-func-mail.php");

      // karte ablegen inkl. bestätigungscode
      $confirmcode  = uniqid("wpgreet_",false);
      $confirmuntil = gmdate("Y-m-d H:i:s",time() + 
			     ( get_option('gmt_offset') * 60 * 60 ) + 
			     ( $wpg_options['wp-greet-mcduration'] * 60 * 60 )  );
      save_greetcard( 
	  $_POST['sender'],
	  $_POST['sendername'],
	  $_POST['recv'],
	  $_POST['recvname'],
	  $_POST['title'],
	  $_POST['message'],
	  $picurl,
	  $_POST['ccsender'],
	  $confirmuntil, 
	  $confirmcode,
	  "",                  // fetchuntil stays blank until confirmation
	  "");                 // fetchcode stays blank until confirmation

      // bestätigungsmail senden
      $sendstatus = sendConfirmationMail(
	  $_POST['sender'],
	  $_POST['sendername'],
	  $_POST['recvname'],
	  $confirmcode,
	  $confirmuntil,
	  false); 
      
      
      if ( $sendstatus == true ) {
	  $out =  __("A confirmation mail has been sent to your address.","wp-greet")."<br />"; 
	  $out .= __("Please enter the link contained within the email into your browser and the greeting card will be send.","wp-greet")."<br />";
	  // create log entry
	  log_greetcard($_POST['sender'],get_option("blogname"),'',"Confirmation sent: ".$confirmcode);
      } else {
	  $out = __("An error occured while sending the confirmation mail.","wp-greet")."<br />";
	  $out .= __("Problem report","wp-greet") . " " . $sendstatus;
      }     


  } else {
    
    // Formular anzeigen
    $captcha = 0;
    // CaptCha! plugin
    if ( $wpg_options['wp-greet-captcha'] == 1) {
      require_once(ABSPATH . "wp-content/plugins/captcha/captcha.php");
      $Cap = new Captcha();
      $Cap->debug = false;	
      $Cap->public_key = intval($_GET['x']);
      $captcha = 1;
    }

    // Math Comment Spam Protection Plugin
    if ( $wpg_options['wp-greet-captcha'] == 2) {
      require_once(ABSPATH . "wp-content/plugins/math-comment-spam-protection/math-comment-spam-protection.classes.php");
      $cap = new MathCheck; 
      
      // Set class options
      $cap_opt = get_option('plugin_mathcommentspamprotection');
      $cap->opt['input_numbers'] = $cap_opt['mcsp_opt_numbers'];
      
      // Generate numbers to be displayed and result
      $cap->GenerateValues();
      $cap_info = array();
      $cap_info['operand1'] = $cap->info['operand1'];
      $cap_info['operand2'] = $cap->info['operand2'];
      $cap_info['result']   = $cap->info['result'];
      $captcha = 2;
    }
 
    // javascript fuer smilies ausgeben falls notwendig
    if ( $wpg_options['wp-greet-smilies']) {
      ?>
      <script type="text/javascript">
          function smile(fname) {
    	     var tarea;
    	     fname = ' :'+fname+': ';
	     tarea = document.getElementById('message');

     	     if (document.selection) {
    		tarea.focus();
    		sel = document.selection.createRange();
    		sel.text = fname;
    		tarea.focus();
    	     }
    	     else if (tarea.selectionStart || tarea.selectionStart == '0') {
    		var startPos = tarea.selectionStart;
    		var endPos = tarea.selectionEnd;
    		var cursorPos = endPos;
    		tarea.value = tarea.value.substring(0, startPos)
    			    + fname
 			    + tarea.value.substring(endPos, tarea.value.length);
    		cursorPos += fname.length;
    		tarea.focus();
    		tarea.selectionStart = cursorPos;
    		tarea.selectionEnd = cursorPos;
    	     }
    	     else {
    		tarea.value += fname;
    		tarea.focus();
    	     }
          }
      </script>
    <?php }
    $out = "&nbsp;</p><div class='wp-greet-form'>\n"; 

     if (trim ($wpg_options['wp-greet-stampimage']) !="")
    {
	// briefmarke einbauen
	// aus der url des bildes den dateinamen bauen 
	$surl=get_option('siteurl');
	$picpath = ABSPATH . substr($picurl, 
				    strpos($picurl, $surl) + strlen($surl)+1);
    
	$out .= '<img src="' . site_url("wp-content/plugins/wp-greet/").
	    "wpg-stamped.php?cci=$picpath&amp;sti=".
	    ABSPATH . $wpg_options['wp-greet-stampimage'].
	    "&amp;stw=" . $wpg_options['wp-greet-stamppercent'].
	    '" alt="'.basename($picurl)."\" width='".
	    $wpg_options['wp-greet-imagewidth']."'/><br />\n";
    } else {
	 $out .= '<img src="' . $picurl . '" alt="'.
	     basename($picurl)."\" width='".
	     $wpg_options['wp-greet-imagewidth']."'/><br />\n";
     }
    $out .= "<form method='post' action=''>\n";
    $out .= '<table class="wp-greet-form"><tr class="wp-greet-form">';
    $out.='<td class="wp-greet-form-left">'.__("Sendername","wp-greet").':</td><td class="wp-greet-form"><input name="sendername" type="text" size="40" maxlength="60" value="' . ( isset($_POST['sendername']) ? $_POST['sendername'] : '')  . '"/></tr>'."\n";
    $out.='<tr class="wp-greet-form"><td class="wp-greet-form-left">'.__("Sender","wp-greet").':</td><td class="wp-greet-form"><input name="sender" type="text" size="40" maxlength="60" value="' . $_POST['sender']  . '"/></td></tr>'."\n";

    $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">".__("CC to Sender","wp-greet").":</td><td class=\"wp-greet-form\"><input name='ccsender' type='checkbox' value='1' " . (isset($_POST['ccsender']) and $_POST['ccsender']==1 ? 'checked="checked"':'')  . " /></td></tr>\n";
    $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">".__("Recipientname","wp-greet").":</td><td class=\"wp-greet-form\"><input name='recvname' type='text' size='40' maxlength='60' value='" . (isset($_POST['recvname']) ? $_POST['recvname'] : '')  . "'/></td></tr>\n";
     $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">".__("Recipient","wp-greet").":</td><td class=\"wp-greet-form\"><input name='recv' type='text' size='40' maxlength='60' value='" . (isset($_POST['recv']) ? $_POST['recv'] : '')  . "'/></td></tr>\n";
     $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">".__("Subject","wp-greet").":</td><td class=\"wp-greet-form\"><input name='title'  type='text' size='40' maxlength='80' value='" . attribute_escape($_POST['title'])  . "'/></td></tr>\n";
     $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">".__("Message","wp-greet").":</td><td class=\"wp-greet-form\"><textarea class=\"wp-greet-form\" name='message' id='message'>" . (isset($_POST['message']) ? attribute_escape($_POST['message']) : '') . "</textarea></td></tr>\n";
     // smilies unter formular anzeigen
     if ( $wpg_options['wp-greet-smilies']) {
       $smileypath=ABSPATH . "wp-content/plugins/wp-greet/smilies"; 
       $smprefix = get_settings('siteurl') . '/wp-content/plugins/wp-greet/smilies/';
       $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">".__("Smileys","wp-greet").":</td><td class=\"wp-greet-form\">";

       $smarr = get_dir_alphasort($smileypath);

       foreach ($smarr as $file) {
	 $out .= '<img src="' . $smprefix . $file . '" alt="'.$file.'" onclick=\'smile("'.$file.'")\' />';
       }

       $out .= "</td></tr>\n";
     } 
     
     // captcha anzeigen
     if ( $captcha == 1 or $captcha == 2)
       $out .="<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">". __("Spamprotection:","wp-greet")."</td><td class=\"wp-greet-form\" >";
     // CaptCha!
     if ($captcha==1)
       $out .= $Cap->display_captcha()."&nbsp;<input name=\"captcha\" type=\"text\" size=\"10\" maxlength=\"10\" />";
    
     // Math Protect
    if ($captcha==2)
      $out.='<label for="mcspvalue"><small>'. __("Sum of","wp-greet")."&nbsp;". $cap_info['operand1'] . ' + ' . $cap_info['operand2'] . ' ? '.'</small></label><input type="text" name="mcspvalue" id="mcspvalue" value="" size="23" maxlength="10" /><input type="hidden" name="mcspinfo" value="'. $cap_info['result'].'" />';
    
    if ( $captcha != 0)
	$out.="</td></tr>";

    // terms of usage
    if ( $wpg_options['wp-greet-touswitch'] == 1) {
	$out .= '<tr class="wp-greet-form"><td class="wp-greet-form" colspan="2">'.
	    "<input name='accepttou' type='checkbox' value='1' " . 
	    (isset($_POST['accepttou']) and $_POST['accepttou']==1 ? 'checked="checked"':'')  . 
	    " />". __("I accept the terms of usage of the greeting card service","wp-greet").
            ' <a href="'. site_url("wp-content/plugins/wp-greet/wpg_service.php") . '?height=600&amp;width=400" class="thickbox" Title="">'.
	    __("(show)","wp-greet")."</a>".
	    "</td></tr>\n";
    }

    // submit buttons
    $out .= "<tr class=\"wp-greet-form\"><td class=\"wp-greet-form-left\">&nbsp;</td><td class=\"wp-greet-form\"><input name='action' type='submit' value='".__("Preview","wp-greet")."' /><input name='action' type='submit'  value='".__("Send","wp-greet")."' /><input type='reset' value='".__("Reset form","wp-greet")."'/>&nbsp;<a href=\"javascript:history.back()\">".__("Back","wp-greet")."</a></td></tr></table></form></div>\n<p>&nbsp;";
   
  }

  // Rueckgabe des HTML Codes
  return $out;
}


//
// anzeige einer grußkarte über den karten code
//
function showGreetcard($display) 
{
    
    // hole optionen
    $wpg_options = wpgreet_get_options();
    
    // ausgabebuffer init
    $out = "";
    
    // get translation 
    $locale = get_locale();
    if ( empty($locale) )
	$locale = 'en_US';
    if(function_exists('load_textdomain')) 
	load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
    

    
    global $wpdb;
    $sql="select * from " . $wpdb->prefix . "wpgreet_cards where fetchcode='" . $display ."';";
    $res = $wpdb->get_row($sql);

    $now = strtotime( gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 )));
    $then = strtotime( $res->fetchuntil);
	
    if ( is_null($res)) {
	// ungültiger code
	$out .= __("Your verification code is invalid.","wp-greet")."<br />" . 
	    __("Send a new card at","wp-greet") .
	    " <a href='" . site_url()."' >".site_url()."</a>";
	return $out;
	
    } else if ($now > $then ) {
	// die gültigkeiteisdauer ist abgelaufen 
	$out .= __("Your greetcard link is timed out.","wp-greet")."<br />".
	    __("Send a new card at","wp-greet") . 
	    " <a href='" . site_url()."' >".site_url()."</a>";
	return $out;
	
    } else {
	// alles okay, karte anzeigen
	$out .= "&nbsp;</p>\n";
	$out .= "<h2>".__("A Greeting Card for you","wp-greet")."</h2>\n";
	$out .= "<table><tr><th>". __("From","wp-greet").":</th><td>". $res->fromname . "&nbsp;&lt;" . $res->frommail . "&gt;";
	
	
	$out .= "</td></tr>";
	$out .= "<tr><th>" . __("To","wp-greet").":</th><td>".   $res->toname . "&nbsp;&lt;". $res->tomail . "&gt;</td></tr>"; 
	$out .= "<tr><th>" .  __("Subject","wp-greet").":</th><td>". $res->subject . "</td></tr></table>";
	$out .= $wpg_options['wp-greet-default-header'] . "\n";
	
	
	if (trim ($wpg_options['wp-greet-stampimage']) !="")
	{
	    // briefmarke einbauen
	    // aus der url des bildes den dateinamen bauen
	    $surl=get_option('siteurl');
	    $picpath = ABSPATH . substr($res->picture, 
					strpos($res->picture, $surl) + strlen($surl)+1);

	    $out .= '<p><img src="' . site_url("wp-content/plugins/wp-greet/").
		"wpg-stamped.php?cci=$picpath&amp;sti=".
		ABSPATH . $wpg_options['wp-greet-stampimage'].
		"&amp;stw=" . $wpg_options['wp-greet-stamppercent'].
		'" alt="'.basename($res->picture)."\" width='".
		$wpg_options['wp-greet-imagewidth']."'/></p><br />\n";
	} else {
	    $out .= '<p><img src="' . $res->picture . '" width="'.$wpg_options['wp-greet-imagewidth'] .'" alt="wp-greet-image" /></p><br />';
	}
	
	// message escapen
	$show_message = nl2br(attribute_escape($res->mailbody));
	
	// smilies ersetzen
	if ( $wpg_options['wp-greet-smilies']) { 
	    $smprefix = get_settings('siteurl') . '/wp-content/plugins/wp-greet/smilies/';
	    preg_match_all('(:[^\040]+:)', $show_message, $treffer);
	    
	    foreach ($treffer[0] as $sm) {
		$smrep='<img src="' . $smprefix . substr($sm,1,strlen($sm)-2) . '" alt='.$sm.'/>';
		$show_message = str_replace($sm,$smrep,$show_message);
	    }
	}
	
	$out .= "\n<p>" . $show_message . "</p>\n";
	$out .= $wpg_options['wp-greet-default-footer'];
	// Karte als abgeholt markieren
	mark_fetchcard($display);
	// und log eintrag vornehmen
	log_greetcard('',get_option("blogname"), '', "Card fetched: ".$display);
	
    }
    return $out;
}  

?>