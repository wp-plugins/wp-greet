<?php
/* This file is part of the wp-greet plugin for wordpress */

/*  Copyright 2009-2013  Hans Matzen  (email : webmaster at tuxlog dot de)

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
		$galleryID=esc_attr(isset($_GET['gallery'])?$_GET['gallery']:'');
		$picurl=esc_attr($_GET['image']);


		$out = showGreetcardForm($galleryID,$picurl,$picdesc);
		echo $out;
	}
}

// apply the filter to the page or post content
function searchwpgreet($content) {

	// look for wp-greet tag
	if ( stristr( $content, '[wp-greet]' )) {

		// get GET vars
		$galleryID = ( isset($_GET['gallery']) ? esc_attr($_GET['gallery']) : "");
		$picurl    = ( isset($_GET['image'])   ? esc_attr($_GET['image'])   : "");
		$verify    = ( isset($_GET['verify'])  ? esc_attr($_GET['verify'])  : "");
		$display   = ( isset($_GET['display']) ? esc_attr($_GET['display']) : "");
		// for bwcards
		$pid       = ( isset($_GET['pid']) ? esc_attr($_GET['pid']) : "");
		$approved  = ( isset($_GET['approved'])? esc_attr($_GET['approved']): "");

		// check if BWCARDS is active
		if (defined('BWCARDS') ) {
			// get post vars
			$bwgalleryID = ( isset($_POST['gallery']) ? esc_attr($_POST['gallery']) : "");
			$bwpicurl    = ( isset($_POST['image'])   ? esc_attr($_POST['image'])   : "");
			$bwpid       = ( isset($_POST['pid'])     ? esc_attr($_POST['pid'])     : "");
			$bwapproved  = ( isset($_POST['approved'])? esc_attr($_POST['approved']): "");

			if ( nggcf_get_gallery_field($bwgalleryID, "Connect to BW-Cards") == 'enabled') {
				$galleryID = $bwgalleryID;
				$picurl = $bwpicurl;
				$pid = $bwpid;
				$approved = $bwapproved;
			}
		}

		// Karte wird abgeholt
		if ($display !="") {
			$content = showGreetcard($display);
		} else {
			// replace tag with html form
			$search    = '[wp-greet]';
			$replace   = showGreetcardForm($galleryID,$picurl,$verify, $pid, $approved);
			$content   = str_replace ($search, $replace, $content);
		}
	}

	return $content;
}

//
// this function controls the whole greetcard workflow and the forms
//
function showGreetcardForm($galleryID, $picurl, $verify = "", $pid = "", $approved = "") {

	global $userdata;

	// hole optionen
	$wpg_options = wpgreet_get_options();

	// ausgabebuffer init
	$out = "";

	// get translation
	load_plugin_textdomain('wp-greet',false,dirname( plugin_basename( __FILE__ ) ) . "/lang/");


	// ---------------------------------------------------------------------
	//  bestätigungsaufruf für den grußkartenversand
	//
	// ---------------------------------------------------------------------
	if ( $verify !="" ) {

		global $wpdb;
		$sql="select * from " . $wpdb->prefix . "wpgreet_cards where confirmcode='" . $verify ."';";
		$res = $wpdb->get_row($sql);

		$now = strtotime( gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 )));

		$then = msql2time( $res->confirmuntil );


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


		} else if ($now > $then and $wpg_options["wp-greet-mcduration"]!=0 ) {
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
	  $_POST['accepttou']  = 1;
	  $picurl              = $res->picture;
	  $galleryID           = "";
	  $_POST['fsend']      = $res->future_send;
		}
	}

	// calculate sendtime 
	if ($wpg_options['wp-greet-future-send'] and $_POST['fsend']!="") {
		$toffset = get_option('gmt_offset') * 3600 +  date_offset_get(new DateTime);
		$sendtime = strtotime($_POST['fsend']) - $toffset;
	} else
		$sendtime = 0;
	
	// -------------------------------------------------------------------------------------
	// pruefe berechtigung zum versenden von grusskarten
	if ( !current_user_can('wp-greet-send')
			and $wpg_options['wp-greet-minseclevel']!="everyone" ) {
		return "<p><b>".__("You are not permitted to send greeting cards.","wp-greet")."<br />".__("Please contact you wordpress Administrator.","wp-greet")."</b></p>";
	}


	// uebernehme user daten bei erstaufruf
	if ( ! isset($_POST['action']) and is_user_logged_in() ) {
		get_currentuserinfo();
		global $user_email;
		$_POST['sender'] = $user_email;
	}

	// uebernehme default subject bei erstufruf
	if ( ! isset($_POST['title']) )
		$_POST['title'] =  $wpg_options['wp-greet-default-title'];


	// Feldinhalte pruefen
	if ( isset($_POST['action']) and
			( $_POST['action'] == __("Preview","wp-greet") or
					$_POST['action'] ==  __("Send","wp-greet") ) ) {

		if ( isset($_POST['sender']) && $_POST['sender'] != '' )
			$_POST['sender'] = esc_attr($_POST['sender']);
		if ( isset($_POST['sendername']) && $_POST['sendername'] != '' )
			$_POST['sendername'] = esc_attr($_POST['sendername']);
		if ( isset($_POST['ccsender']) && $_POST['ccsender'] != '' )
			$_POST['ccsender'] = esc_attr($_POST['ccsender']);
		if ( isset($_POST['recv']) && $_POST['recv'] != '' )
			$_POST['recv'] = esc_attr($_POST['recv']);
		if ( isset($_POST['recvname']) && $_POST['recvname'] != '' )
			$_POST['recvname'] = esc_attr($_POST['recvname']);
		if ( isset($_POST['title']) && $_POST['title'] != '' )
			$_POST['title'] = stripslashes($_POST['title']);
	 // entferne die von wordpress automatisch beigefügten slashes
	 if ( isset($_POST['message']) && $_POST['message'] != '' )
	 	$_POST['message'] =  stripslashes($_POST['message']);
	  
	 // plausibilisieren der feldinhalte
	 // pruefe pflichtfelder
	 if (substr($wpg_options['wp-greet-fields'],0,1)=="1" and trim($_POST['sendername'])=="")
	 {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Please fill in mandatory field","wp-greet")." ". __("Sendername","wp-greet")."<br /></div>";
	 }
	 if (substr($wpg_options['wp-greet-fields'],1,1)=="1" and trim($_POST['sender'])=="")
	 {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Please fill in mandatory field","wp-greet")." ". __("Sender","wp-greet")."<br /></div>";
	 }
	 else if ( ! check_email($_POST['sender']) ) {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Invalid sender  mail address.","wp-greet")."<br /></div>";
	 }
	 if (substr($wpg_options['wp-greet-fields'],2,1)=="1" and trim($_POST['recvname'])=="")
	 {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Please fill in mandatory field","wp-greet")." ". __("Recipientname","wp-greet")."<br /></div>";
	 }
	 if (substr($wpg_options['wp-greet-fields'],3,1)=="1" and trim($_POST['recv'])=="")
	 {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Please fill in mandatory field","wp-greet")." ". __("Recipient","wp-greet")."<br /></div>";
	 }
	 else if ( $wpg_options['wp-greet-multi-recipients']) {
	 	$ems = explode(",",$_POST['recv']);
	 	foreach($ems as $i) {
	 		if (! check_email(trim($i))) {
	 			$_POST['action'] = "Formular";
		 		echo "<div class='wp-greet-error'>" . __("Invalid recipient mail address.","wp-greet")."<br /></div>";
	 		}
	 	}
	 } else if ( ! check_email($_POST['recv']) ) {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Invalid recipient mail address.","wp-greet")."<br /></div>";
	 }
	 if (substr($wpg_options['wp-greet-fields'],4,1)=="1" and trim($_POST['title'])=="")
	 {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Please fill in mandatory field","wp-greet")." ". __("Subject","wp-greet")."<br /></div>";
	 }
	 if (substr($wpg_options['wp-greet-fields'],5,1)=="1" and trim($_POST['message'])=="")
	 {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Please fill in mandatory field","wp-greet")." ". __("Message","wp-greet")."<br /></div>";
	 }
	  

	 // pruefe captcha
	 if ( ($wpg_options['wp-greet-captcha'] > 0) and
	 		(isset($_POST['public_key']) or isset($_POST['mcspinfo']) or isset($_POST['cptch_result']) or isset($_POST['recaptcha_challenge_field']))) {

	 	// check CaptCha!
	 	if ($wpg_options['wp-greet-captcha']==1) {
	 		require_once(ABSPATH . "wp-content/plugins/captcha/captcha.php");
	 		if (class_exists("Captcha")) {
	 			$Cap = new Captcha();
	 			$Cap->debug = false;
	 			$Cap->public_key=$_POST['public_key'];

	 			if (! $Cap->check_captcha($Cap->public_key_id(),$_POST['captcha']) ) {
	 				$_POST['action'] = "Formular";
	 				echo "<div class='wp-greet-error'>" . __("Spamprotection - Code is not valid.<br />","wp-greet");
	 				echo __("Please try again.<br />Tip: If you cannot identify the chars, you can generate a new image. Using Reload.","wp-greet")."<br /></div>";
	 			}
	 		} else {
	 			if ( 0 != strcasecmp( trim( decode( $_POST['cptch_result'], "123" ) ), $_POST['cptch_number'] ) ) {
	 				$_POST['action'] = "Formular";
	 				echo "<div class='wp-greet-error'>" . __("Spamprotection - Code is not valid.<br />","wp-greet");
	 				echo __("Please try again.<br />Tip: If you cannot identify the chars, you can generate a new image. Using Reload.","wp-greet")."<br /></div>";
	 			}
	 		}
	 	}
	 	// check Math Protect
	 	if ($wpg_options['wp-greet-captcha']==2) {
	 		require_once(ABSPATH . "wp-content/plugins/math-comment-spam-protection/math-comment-spam-protection.classes.php");

	 		$Cap = new MathCheck();

	 		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	 		$tap = get_plugins();

	 		if ( version_compare($tap['math-comment-spam-protection/math-comment-spam-protection.php']['Version'],"3.0","<"))
	 			$mc_nok = $Cap->InputValidation( $_POST['mcspinfo'], $_POST['mcspvalue']);
	 		else
	 			$mc_nok = $Cap->MathCheck_InputValidation( $_POST['mcspinfo'], $_POST['mcspvalue']);

	 		if ($mc_nok!="") {
	 			$_POST['action'] = "Formular";
	 			echo "<div class='wp-greet-error'>" . __("Spamprotection - Code is not valid.<br />","wp-greet");
	 			echo __("Please try again.","wp-greet")."<br /></div>";
	 		}
	 	}

	 	// check wp-reCAPTCHA
	 	if ($wpg_options['wp-greet-captcha']==3) {
	 		require_once(ABSPATH . "wp-content/plugins/wp-recaptcha/recaptchalib.php");

	 		$rec_opt = get_option('recaptcha_options');
	 		$rec_ok  = recaptcha_check_answer ($rec_opt['private_key'], $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);

	 		if (!$rec_ok->is_valid) {
	 			$_POST['action'] = "Formular";
	 			echo "<div class='wp-greet-error'>" . __("Spamprotection - Code is not valid.<br />","wp-greet");
	 			echo __("Please try again.","wp-greet")."<br /></div>";
	 		}
	 	}
	 } // end of pruefe captcha
	  
	 // nutzungsbedingungen prüfen
	 if ($wpg_options['wp-greet-touswitch']==1 and  $_POST['accepttou'] != 1)
	 {
	 	$_POST['action'] = "Formular";
	 	echo "<div class='wp-greet-error'>" . __("Please accept the terms of usage before sending a greeting card.<br />","wp-greet")."</div>";
	 }
	  
	} // end of Feldinhalte pruefen



	// Vorschau
	if ( isset($_POST['action']) and
			$_POST['action'] == __("Preview","wp-greet") ) {

		// message escapen
		$show_message = nl2br(esc_attr($_POST['message']));

		// smilies ersetzen
		if ( $wpg_options['wp-greet-smilies']) {
			$smprefix = get_option('siteurl') . '/wp-content/plugins/wp-greet/smilies/';
			preg_match_all('(:[^\040]+:)', $show_message, $treffer);

			foreach (array_unique($treffer[0]) as $sm) {
				$smrep='<img src="' . $smprefix . substr($sm,1,strlen($sm)-2) . '" alt=' . $sm ." />";
				$show_message = str_replace($sm,$smrep,$show_message);
			}
		}

		// Vorschau anzeigen
		$ccsender="";
		if (isset($_POST['ccsender']) && $_POST['ccsender'] == '1') {
			$ccsender = " (".__("CC","wp-greet").")";
		}

		// steuerungs informationen
		if (!isset($_POST['wp-greet-enable-confirm'])) $_POST['wp-greet-enable-confirm']="";
		if (!isset($_POST['accepttou'])) $_POST['accepttou']="";
		if (!isset($_POST['fsend'])) $_POST['fsend']="";
		if (!isset($_POST['ccsender'])) $_POST['ccsender']="";


		// load template
		ob_start();
		include(plugin_dir_path(__FILE__) . "/templates/wp-greet-preview-template.php");
		$template=ob_get_clean();

		// replace placeholders
		$template = str_replace('{%sendername%}', $_POST['sendername'], $template);
		$template = str_replace('{%sendermail%}', $_POST['sender'], $template);
		$template = str_replace('{%ccsender%}', $ccsender, $template);
		$template = str_replace('{%recvname%}', $_POST['recvname'], $template);
		$template = str_replace('{%recvmail%}', $_POST['recv'], $template);
		$template = str_replace('{%subject%}', esc_attr($_POST['title']), $template);
		$template = str_replace('{%wp-greet-default-header%}', $wpg_options['wp-greet-default-header'], $template);
		$template = str_replace('{%wp-greet-default-footer%}', $wpg_options['wp-greet-default-footer'], $template);
		$template = str_replace('{%image_url%}', get_imgtag($picurl), $template);
		$template = str_replace('{%message%}', $show_message, $template);
		$template = str_replace('{%send_time%}', $_POST['fsend'], $template);

		$template .= "<form method='post' action='#'>";
		$template .= "<input name='sender' type='hidden' value='" . $_POST['sender']  . "' />\n";
		$template .= "<input name='sendername' type='hidden' value='" . $_POST['sendername']  . "' />\n";
		$template .= "<input name='ccsender' type='hidden' value='" . $_POST['ccsender']  . "' />\n";
		$template .= "<input name='wp-greet-enable-confirm' type='hidden' value='" . $_POST['wp-greet-enable-confirm']  . "' />\n";
		$template .= "<input name='recv' type='hidden' value='" . $_POST['recv']  . "' />\n";
		$template .= "<input name='recvname' type='hidden' value='" . $_POST['recvname']  . "' />\n";
		$template .= "<input name='title' type='hidden' value='" . esc_attr($_POST['title'])  . "' />\n";
		$template .= "<input name='message' type='hidden' value='" . esc_attr($_POST['message']) . "' />\n";
		$template .= "<input name='accepttou' type='hidden' value='" . esc_attr($_POST['accepttou']) . "' />\n";
		$template .= "<input name='fsend' type='hidden' value='" . $_POST['fsend']  . "' />\n";

		// make sure parameters are sent when normal get mechanism is off for BWCards
		if (defined('BWCARDS')) {
			$template .= "<input name='vcode' type='hidden' value='" . $_POST['vcode']  . "' />\n";
			$template .= "<input name='image' type='hidden' value='$picurl' />\n";
			$template .= "<input name='gallery' type='hidden' value='$galleryID' />\n";
			$template .= "<input name='pid' type='hidden' value='$pid' />\n";
			$template .= "<input name='approved' type='hidden' value='$approved' />\n";
		}

		if (defined('BWCARDS')) {
			$template .= "<input name='action' type='submit' value='".__("Edit","wp-greet")."' />";
		} else {
			$template .= "<input name='action' type='submit' value='".__("Back","wp-greet")."' />";
		}
		$template .= "&nbsp;&nbsp;&nbsp;";
		$template .= "<input name='action' type='submit'  value='".__("Send","wp-greet")."' />";


		$template .= "</form>";
		$template .= "<p>&nbsp;";

		// output preview
		echo $template;

	}  else if ( isset($_POST['action']) and $_POST['action'] == __("Send","wp-greet") and
			($wpg_options['wp-greet-mailconfirm'] != "1" or $verify !="") ) {
		// ---------------------------------------------------------------------
		// Grußkarten Mail senden oder Grußkarten Link Mail senden
		// ----------------------------------------------------------------------
		//
		$fetchcode="";
		if (checkMailSent($_POST['sender'],$_POST['recv']) == false) {
			if ( $wpg_options['wp-greet-onlinecard'] == 1) {
				// grußkarten link mail senden
				require_once("wpg-func-mail.php");
				// karte ablegen inkl. bestätigungscode
				$fetchcode  = uniqid("wpgreet_",false);

				if ($wpg_options['wp-greet-ocduration']=="0") {
					$fetchuntil = "2035-12-31 23:59:59";
				} else {
					$fetchuntil = gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 ) + ( $wpg_options['wp-greet-ocduration'] * 60 * 60 *24)  );
				}

				save_greetcard( $_POST['sender'], $_POST['sendername'], $_POST['recv'], $_POST['recvname'],
						$_POST['title'], $_POST['message'], $picurl, $_POST['ccsender'] * 1 + $_POST['wp-greet-enable-confirm'] * 2,
						"",     					// confirm until stays blank
						$verify,                	// confirmcode if available
						$fetchuntil, $fetchcode,$sendtime,session_id());

				//
				// Here comes the Google Wallet stuff for BWcards
				//

				// go to google wallet here and if sucessfull call a script which sends the card
				if (defined('BWCARDS')) {
					$bwc_options=bwc_get_global_options();
					$conn = nggcf_get_gallery_field($galleryID, "Connect to BW-Cards");
					$price = nggcf_get_field($pid, 'Price');
					if (!isset($price)) {
						$price = 1;
					}
				}

				// check for package voucher code and set approved appropriate
				if (defined('BWCARDS') and trim($_POST['vcode'])!="") {
					$approved="";
					$cleft=bwc_use_voucher_code(trim($_POST['vcode']));
					if ($cleft>=0) {
						$approved="approved";
						echo "Package code accepted.<br/>You have $cleft cards left.<br/>";
					} else {
						echo "Invalid package code. You will be redirected to the payment gateway now.<br/>";
					}
				}
					
				if (defined('BWCARDS') and $conn and $bwc_options['bwc_general_formhook'] and $price>0 and $approved!="approved") {
					$picture = nggdb::find_image($pid);
					$bjs = bwc_generate_pay_script($picture->gid, $picture->imageURL, $picture);
					echo $bjs;
					$sendstatus = "no";

				} else {

					// link mail senden or schedulen
					if ($sendtime != 0) {
						wp_schedule_single_event($sendtime, "wpgreet_sendcard_link",
								array($_POST['sender'], $_POST['sendername'], $_POST['recv'], $_POST['recvname'],
										$wpg_options['wp-greet-ocduration'], $fetchcode, false));

						$sendstatus = true;
					} else {
						$sendstatus = sendGreetcardLink( $_POST['sender'], $_POST['sendername'], $_POST['recv'], $_POST['recvname'],
								$wpg_options['wp-greet-ocduration'], $fetchcode, $_POST['ccsender'], false);
					}
				}
			} else {  // grußkarten mail senden

				require_once("wpg-func-mail.php");


				if ($sendtime) {
					wp_schedule_single_event($sendtime, "wpgreet_sendcard_mail",
							array($_POST['sender'], $_POST['sendername'], $_POST['recv'], $_POST['recvname'],
									$_POST['title'], $_POST['message'], $picurl, $_POST['ccsender'], false));
					$sendstatus = true;
				} else {
					$sendstatus = sendGreetcardMail( $_POST['sender'], $_POST['sendername'], $_POST['recv'], $_POST['recvname'],
							$_POST['title'], $_POST['message'], $picurl, $_POST['ccsender'], false);
				}
			} // end grußkarten mail senden
		} else {
			$sendstatus = true;
		}

		if (strval($sendstatus) != "no") { // this is to prevent output if used with bwcards
			if ( $sendstatus == true ) {
				$out = __("Your greeting card has been sent or scheduled.","wp-greet")."<br />";
				// show resend link
				if ($wpg_options['wp-greet-offerresend'] ) {  
					$out .= "<form method='post' action='#'>";
					$out .= "<input name='sender' type='hidden' value='" . $_POST['sender']  . "' />\n";
					$out .= "<input name='sendername' type='hidden' value='" . $_POST['sendername']  . "' />\n";
					$out .= "<input name='ccsender' type='hidden' value='" . $_POST['ccsender']  . "' />\n";
					$out .= "<input name='wp-greet-enable-confirm' type='hidden' value='" . $_POST['wp-greet-enable-confirm']  . "' />\n";
					//$out .= "<input name='recv' type='hidden' value='" . $_POST['recv']  . "' />\n";
					//$out .= "<input name='recvname' type='hidden' value='" . $_POST['recvname']  . "' />\n";
					$out .= "<input name='title' type='hidden' value='" . esc_attr($_POST['title'])  . "' />\n";
					$out .= "<input name='message' type='hidden' value='" . esc_attr($_POST['message']) . "' />\n";
					$out .= "<input name='accepttou' type='hidden' value='" . esc_attr($_POST['accepttou']) . "' />\n";
					$out .= "<input name='fsend' type='hidden' value='" . $_POST['fsend']  . "' />\n";
					// make sure parameters are sent when normal get mechanism is off for BWCards
					if (defined('BWCARDS')) {
						$out .= "<input name='image' type='hidden' value='$picurl' />\n";
						$out .= "<input name='gallery' type='hidden' value='$galleryID' />\n";
						$out .= "<input name='pid' type='hidden' value='$pid' />\n";
						//$out .= "<input name='approved' type='hidden' value='$approved' />\n";
					}

					$out .= "<input name='action' type='submit'  value='".__("Send this card to another recipient","wp-greet")."' />";
					$out .= "</form>";
				}
				// create log entry
				log_greetcard($_POST['recv'],addslashes($_POST['sender']),$picurl,$_POST['message']);

				// clean log and cards table
				// we are doing this whenever a card has been successfully sent
				// beacause wp-cron does not work properly at the moment
				remove_cards();
				remove_logs();

				// haben wir eine karte mit bestätigungsverfahren gesendet,
				// dann markieren wir sie als versendet
				//if ( $verify != "" )
				//	mark_sentcard($verify);

			} else {
				$out = __("An error occured while sending you greeting card.","wp-greet")."<br />";
				$out .= __("Problem report","wp-greet") . " " . $sendstatus;
			}
		}


	} else if ( isset($_POST['action']) and $_POST['action'] == __("Send","wp-greet") and
			( $wpg_options['wp-greet-mailconfirm'] == "1" or $verify == "") ) {
		// ---------------------------------------------------------------------
		// Bestätigungsmail senden und Grußkarte inklusive bestätigungscode ablegen
		// ----------------------------------------------------------------------
		//
		if (checkMailSent($_POST['sender'],$_POST['recv']) == false) {
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
					$_POST['ccsender'] * 1 + $_POST['wp-greet-enable-confirm'] * 2,
					$confirmuntil,
					$confirmcode,
					"",                  // fetchuntil stays blank until confirmation
					"", $sendtime);                 // fetchcode stays blank until confirmation

			// bestätigungsmail senden
			$sendstatus = sendConfirmationMail(
					$_POST['sender'],
					$_POST['sendername'],
					$_POST['recvname'],
					$confirmcode,
					$confirmuntil,
					false,
					$sendtime);
		} else {
			$sendstatus=true;
		}

		if ( $sendstatus == true ) {
	  $out =  __("A confirmation mail has been sent to your address.","wp-greet")."<br />";
	  $out .= __("Please enter the link contained within the email into your browser and the greeting card will be send.","wp-greet")."<br />";
	  // create log entry
	  log_greetcard($_POST['sender'],get_option("blogname"),'',"Confirmation sent: ".$confirmcode);
		} else {
	  $out = __("An error occured while sending the confirmation mail.","wp-greet")."<br />";
	  $out .= __("Problem report","wp-greet") . " " . $sendstatus;
		}
			

	} else {		// ------------------------------ formular anzeigen
		// Vorbelegung setzen, bei Erstaufruf
		if ( isset($_POST['action']) && $_POST['action'] != __("Zurück","wp-greet")) {
			$_POST['wp-greet-enable-confirm']=1;
			$_POST['ccsender']=1;
		}

		// Formular anzeigen
		$captcha = 0;
		// CaptCha! plugin
		if ( $wpg_options['wp-greet-captcha'] == 1) {
			require_once(ABSPATH . "wp-content/plugins/captcha/captcha.php");
			$captcha = 1;
			if (class_exists("Captcha")) {
				$Cap = new Captcha();
				$Cap->debug = false;
				$Cap->public_key = intval($_GET['x']);
			}
		}

		// Math Comment Spam Protection Plugin
		if ( $wpg_options['wp-greet-captcha'] == 2) {
			require_once(ABSPATH . "wp-content/plugins/math-comment-spam-protection/math-comment-spam-protection.classes.php");
			$cap = new MathCheck;

			// Set class options
			$cap_opt = get_option('plugin_mathcommentspamprotection');
			$cap->opt['input_numbers'] = $cap_opt['mcsp_opt_numbers'];

			// Generate numbers to be displayed and result
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			$tap = get_plugins();
			if (version_compare($tap['math-comment-spam-protection/math-comment-spam-protection.php']['Version'],"3.0","<")) {
				$cap->GenerateValues();
				$cap_info = array();
				$cap_info['operand1'] = $cap->info['operand1'];
				$cap_info['operand2'] = $cap->info['operand2'];
				$cap_info['result']   = $cap->info['result'];
			} else {
				$cap_info = $cap->MathCheck_GenerateValues();
			}
			$captcha = 2;
		}

		// WP-reCAPTCHA plugin
		if ( $wpg_options['wp-greet-captcha'] == 3) {
			require_once(ABSPATH . "wp-content/plugins/wp-recaptcha/recaptchalib.php");
			$captcha = 3;
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

// lets calculate and set the variables to use in the template file
$image_url=get_imgtag($picurl);
$sendername_label=__("Sendername","wp-greet").(substr($wpg_options['wp-greet-fields'],0,1)=="1" ? "<sup>*</sup>":"");
$sendername_input='<input name="sendername" type="text" size="30" maxlength="60" value="' . ( isset($_POST['sendername']) ? $_POST['sendername'] : '')  . '"/>'."\n";
$sendermail_label=__("Sender","wp-greet").(substr($wpg_options['wp-greet-fields'],1,1)=="1" ? "<sup>*</sup>":"");
$sendermail_input='<input name="sender" type="text" size="30" maxlength="60" value="' . $_POST['sender']  . '"/>'."\n";

$confirm_label="";
$confirm_input="";
if ($wpg_options['wp-greet-enable-confirm']) {
	$confirm_label= __("Send confirmation to Sender","wp-greet");
	$confirm_input="<input name='wp-greet-enable-confirm' type='checkbox' value='1' " . ((isset($_POST['wp-greet-enable-confirm']) and $_POST['wp-greet-enable-confirm']==1)?'checked="checked"':'')  . " />\n";
}
$ccsender_label=__("CC to Sender","wp-greet");
$ccsender_input="<input name='ccsender' type='checkbox' value='1' " . ((isset($_POST['ccsender']) and $_POST['ccsender']=="1")?'checked="checked"':'')  . " />\n";
$recvname_label=__("Recipientname","wp-greet").(substr($wpg_options['wp-greet-fields'],2,1)=="1" ? "<sup>*</sup>":"");
$recvname_input="<input name='recvname' type='text' size='30' maxlength='60' value='" . (isset($_POST['recvname']) ? $_POST['recvname'] : '')  . "'/>\n";
$recvmail_label=__("Recipient","wp-greet").(substr($wpg_options['wp-greet-fields'],3,1)=="1" ? "<sup>*</sup>":"");
$recvmail_input="<input name='recv' type='text' size='30' maxlength='255' value='" . (isset($_POST['recv']) ? $_POST['recv'] : '')  . "'/>";

$futuresend_label="";
$futuresend_input="";
if ($wpg_options['wp-greet-future-send']) {
	$futuresend_label= __("Time to send card","wp-greet");
	$futuresend_input='<input type="text" size="30" name="fsend" id="fsend" value="'.(isset($_POST['fsend'])?$_POST['fsend']:'') .'" />';
}

$subject_label= __("Subject","wp-greet").(substr($wpg_options['wp-greet-fields'],4,1)=="1" ? "<sup>*</sup>":"");
$subject_input="<input name='title'  type='text' size='30' maxlength='80' value='" . esc_attr($_POST['title'])  . "'/>\n";
$message_label=__("Message","wp-greet").(substr($wpg_options['wp-greet-fields'],5,1)=="1" ? "<sup>*</sup>":"");
$message_input="<textarea class=\"wp-greet-form\" name='message' id='message' rows='40' cols='15'>" . (isset($_POST['message']) ? stripslashes(esc_attr($_POST['message'])) : '') . "</textarea>\n";

// smilies unter formular anzeigen
$smilies_label="";
$smilies_input="";
if ( $wpg_options['wp-greet-smilies']) {
	$smileypath=ABSPATH . "wp-content/plugins/wp-greet/smilies";
	$smprefix = get_option('siteurl') . '/wp-content/plugins/wp-greet/smilies/';
	$smilies_label= __("Smileys","wp-greet");

	$smarr = get_dir_alphasort($smileypath);
	$smilies_input="";
	foreach ($smarr as $file) {
		$smilies_input .= '<img src="' . $smprefix . $file . '" alt="'.$file.'" onclick=\'smile("'.$file.'")\' />';
	}
}

// captcha anzeigen
$captcha_label="";
$captcha_input="";
if ( $captcha != 0)
	$captcha_label = __("Spamprotection:","wp-greet");
// CaptCha!
if ($captcha==1) {
	if (isset($Cap)) {
		$captcha_input= $Cap->display_captcha()."&nbsp;<input name=\"captcha\" type=\"text\" size=\"10\" maxlength=\"10\" />";
	} else {
		ob_start();
		cptch_comment_form_wp3();
		$captcha_input= ob_get_contents();
		ob_end_clean();
	}
}
// Math Protect
if ($captcha==2)
	$captcha_input='<label for="mcspvalue"><small>'. __("Sum of","wp-greet")."&nbsp;". $cap_info['operand1'] . ' + ' . $cap_info['operand2'] . ' ? '.'</small></label><input type="text" name="mcspvalue" id="mcspvalue" value="" size="23" maxlength="10" /><input type="hidden" name="mcspinfo" value="'. $cap_info['result'].'" />';

// WP-reCAPTCHA
if ($captcha==3) {
	$rec_opt = get_option('recaptcha_options');
	$captcha_input= recaptcha_get_html ($rec_opt['public_key']);
}


// terms of usage
$tou_label="";
$tou_input="";
if ( $wpg_options['wp-greet-touswitch'] == 1) {
	$tou_input="<input name='accepttou' type='checkbox' value='1' " . (isset($_POST['accepttou']) and $_POST['accepttou']==1 ? 'checked="checked"':'')  ." />";
	$tou_label= __("I accept the terms of usage of the greeting card service","wp-greet").
	' <a href="'. site_url("wp-content/plugins/wp-greet/wpg_service.php") . '?height=600&amp;width=400" class="thickbox" title="">'.
	__("(show)","wp-greet")."</a>";
}

// submit buttons
$preview_button="<input name='action' type='submit' value='".__("Preview","wp-greet")."' />";
$reset_button="<input type='reset' value='".__("Reset form","wp-greet")."'/>";
$send_button="<input name='action' type='submit'  value='".__("Send","wp-greet")."' />";
$vcode_input="<input name='vcode' type='text' size='30' maxlength='15' value='" . (isset($_POST['vcode']) ? $_POST['vcode'] : '')  . "'/>";


// load template
ob_start();
include(plugin_dir_path(__FILE__) . "/templates/wp-greet-form-template.php");
$template=ob_get_clean();
// replace placeholders
$template = str_replace('{%sendername_label%}', $sendername_label, $template);
$template = str_replace('{%sendername_input%}', $sendername_input, $template);
$template = str_replace('{%sendermail_label%}', $sendermail_label, $template);
$template = str_replace('{%sendermail_input%}', $sendermail_input, $template);
$template = str_replace('{%ccsender_label%}', 	$ccsender_label, $template);
$template = str_replace('{%ccsender_input%}', 	$ccsender_input, $template);
$template = str_replace('{%confirm_label%}', 	$confirm_label, $template);
$template = str_replace('{%confirm_input%}', 	$confirm_input, $template);
$template = str_replace('{%recvname_label%}', 	$recvname_label, $template);
$template = str_replace('{%recvname_input%}', 	$recvname_input, $template);
$template = str_replace('{%recvmail_label%}', 	$recvmail_label, $template);
$template = str_replace('{%recvmail_input%}', 	$recvmail_input, $template);
$template = str_replace('{%subject_label%}', 	$subject_label, $template);
$template = str_replace('{%subject_input%}', 	$subject_input, $template);
$template = str_replace('{%image_url%}', 		$image_url, $template);
$template = str_replace('{%message_label%}', 	$message_label, $template);
$template = str_replace('{%message_input%}', 	$message_input, $template);
$template = str_replace('{%futuresend_label%}', $futuresend_label, $template);
$template = str_replace('{%futuresend_input%}', $futuresend_input, $template);
$template = str_replace('{%smilies_label%}', 	$smilies_label, $template);
$template = str_replace('{%smilies_input%}', 	$smilies_input, $template);
$template = str_replace('{%captcha_label%}',	$captcha_label , $template);
$template = str_replace('{%captcha_input%}', 	$captcha_input, $template);
$template = str_replace('{%tou_label%}', 		$tou_label, $template);
$template = str_replace('{%tou_input%}', 		$tou_input, $template);
$template = str_replace('{%preview_button%}', 	$preview_button, $template);
$template = str_replace('{%send_button%}', 		$send_button, $template);
$template = str_replace('{%reset_button%}', 	$reset_button , $template);
$template = str_replace('{%vcode_input%}',		$vcode_input, $template);

// make sure parameters are sent when normal get mechanism is off for BWCards
$bwout="";
if (defined('BWCARDS')) {
	$bwout .= "<input name='image' type='hidden' value='$picurl' />\n";
	$bwout .= "<input name='gallery' type='hidden' value='$galleryID' />\n";
	$bwout .= "<input name='pid' type='hidden' value='$pid' />\n";
	$bwout .= "<input name='approved' type='hidden' value='$approved' />\n";
}

// put formtag around output
$out = "<div class='wp-greet-form'><form method='post' action='#'>\n" . $bwout . $template . "</form></div><p>&nbsp;";
	}

	// Rueckgabe des HTML Codes
	return $out;
}


//
// anzeige einer grußkarte über den karten code
//
function showGreetcard($display)
{
	require_once("wpg-func-mail.php");
	// hole optionen
	$wpg_options = wpgreet_get_options();

	// ausgabebuffer init
	$out = "";

	// get translation
	load_plugin_textdomain('wp-greet',false,dirname( plugin_basename( __FILE__ ) ) . "/lang/");

	global $wpdb;
	$sql="select * from " . $wpdb->prefix . "wpgreet_cards where fetchcode='" . $display ."';";
	$res = $wpdb->get_row($sql);

	$now = strtotime( gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 )));
	$then = msql2time( $res->fetchuntil);

	// NGG picture ID ermitteln
	$fname = substr($res->picture,strrpos($res->picture,"/")+1);
	$sql = "select pid from " . $wpdb->prefix . "ngg_pictures where filename='$fname';";
	$res1 = $wpdb->get_row($sql);
	$pid = $res1->pid;


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

		// message escapen
		$show_message = nl2br(esc_attr($res->mailbody));

		// smilies ersetzen
		if ( $wpg_options['wp-greet-smilies']) {
			$smprefix = get_option('siteurl') . '/wp-content/plugins/wp-greet/smilies/';
			preg_match_all('(:[^\040]+:)', $show_message, $treffer);

			foreach (array_unique($treffer[0]) as $sm) {
				$smrep='<img src="' . $smprefix . substr($sm,1,strlen($sm)-2) . '" alt="'.$sm.'" />';
				$show_message = str_replace($sm,$smrep,$show_message);
			}
		}

		// Karte als abgeholt markieren
		mark_fetchcard($display);
		// und log eintrag vornehmen
		log_greetcard('',get_option("blogname"), '', "Card fetched: ".$display);
		// und falls gewünscht und noch nicht erfolgt bestätigung an sender schicken
		if ($res->cc2from & 2 and $res->card_fetched == '0000-00-00 00:00:00') {
			sendGreetcardConfirmation($res->frommail,$res->fromname,$res->frommail,$res->fromname,$wpg_options['wp-greet-ocduration'], $display);
			log_greetcard('',get_option("blogname"),'',"Confirmation mail sent to sender for card:" . $display);
		}

		// load template
		ob_start();
		include(plugin_dir_path(__FILE__) . "/templates/wp-greet-display-template.php");
		$template=ob_get_clean();

		// replace placeholders
		$template = str_replace('{%sendername%}', $res->fromname, $template);
		$template = str_replace('{%sendermail%}', $res->frommail, $template);
		$template = str_replace('{%ccsender%}', $res->cc2from, $template);
		$template = str_replace('{%recvname%}', $res->toname, $template);
		$template = str_replace('{%recvmail%}', $res->tomail, $template);
		$template = str_replace('{%subject%}', $res->subject, $template);
		$template = str_replace('{%wp-greet-default-header%}', $wpg_options['wp-greet-default-header'], $template);
		$template = str_replace('{%wp-greet-default-footer%}', $wpg_options['wp-greet-default-footer'], $template);
		$template = str_replace('{%image_url%}', get_imgtag($res->picture), $template);
		$template = str_replace('{%message%}', $show_message, $template);
	}
	return $template;
}
?>