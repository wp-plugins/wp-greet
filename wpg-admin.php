<?php
/* This file is part of the wp-greet plugin for wordpress */

/*  Copyright 2008,2009 Hans Matzen  (email : webmaster at tuxlog dot de)

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
function wpg_admin_form() 
{
  global $wpg_options;

  // wp-greet optionen aus datenbank lesen
  $wpg_options = wpgreet_get_options();
 
  // get translation 
  $locale = get_locale();
  if ( empty($locale) )
    $locale = 'en_US';
  if(function_exists('load_textdomain')) 
    load_textdomain("wp-greet",ABSPATH . "wp-content/plugins/wp-greet/lang/".$locale.".mo");
  
  // load possible form pages
  $wpdb =& $GLOBALS['wpdb'];
  $sql="SELECT id,post_title FROM ".$wpdb->prefix."posts WHERE post_type in ('page','post') and post_content like '%[wp-greet]%' order by id;";
  $pagearr = $wpdb->get_results($sql);

  // if this is a POST call, save new values
  if (isset($_POST['info_update'])) {
    $upflag=false;

    reset($wpg_options);
    $thispageoptions = array("wp-greet-mailreturnpath", "wp-greet-autofillform",
			     "wp-greet-bcc", "wp-greet-imgattach",
			     "wp-greet-default-title", "wp-greet-default-header",
			     "wp-greet-default-footer", "wp-greet-imagewidth",
			     "wp-greet-logging",      "wp-greet-gallery",
			     "wp-greet-formpage",     "wp-greet-smilies",
			     "wp-greet-usesmtp",      "wp-greet-stampimage",
			     "wp-greet-stamppercent", "wp-greet-onlinecard", 
			     "wp-greet-ocduration",   "wp-greet-octext",
			     "wp-greet-logdays",      "wp-greet-carddays");
    

    while (list($key, $val) = each($wpg_options)) {
	if (in_array($key,$thispageoptions) and $wpg_options[$key] != $_POST[$key] ) {
	    $wpg_options[$key] = stripslashes($_POST[$key]);
	    $upflag=true;
	}
    }
    
    // save options and put message after update
    echo"<div class='updated'><p><strong>";

    // check email adresses
    if ( ! check_email($wpg_options['wp-greet-mailreturnpath']) and $wpg_options['wp-greet-mailreturnpath']!="") {
      echo __('mailreturnpath is not valid (wrong format or no MX entry for domain).',"wp-greet"). "<br />";
      $upflag=false;
    }

    if ( ! check_email($wpg_options['wp-greet-bcc']) and $wpg_options['wp-greet-bcc']!="") {
      echo __('bcc email adress is not valid (wrong format or no MX entry for domain).',"wp-greet"). "<br />";
      $upflag=false;
    }

    if ( $wpg_options['wp-greet-imagewidth'] < 0  or $wpg_options['wp-greet-imagewidth']>3200) {
      echo __('imagewidth not in range (0..3200).',"wp-greet"). "<br />";
      $upflag=false;
    }

    if ($upflag) {
      wpgreet_set_options();
      echo __('Settings successfully updated',"wp-greet");
    } else
      echo __('You have to change a field to update settings.',"wp-greet");
    
    echo "</strong></p></div>";
  } 

?>

<script type="text/javascript">
 function wechsle_inline () {
    imga=document.getElementById('wp-greet-imgattach');
    usmtp=document.getElementById('wp-greet-usesmtp1');
    if (usmtp.checked == false) {
	imga.checked = false;
	imga.disabled = true;
    } else
	imga.disabled=false;
    wechsle_stamp();
} 

function wechsle_stamp () {
    imga=document.getElementById('wp-greet-imgattach');
    stamp=document.getElementById('wp-greet-stampimage');
    stamp.readOnly = (imga.checked == false);
}

function wechsle_onlinecard () {
    obja=document.getElementById('wp-greet-onlinecard');
    objb=document.getElementById('wp-greet-ocduration');
    objc=document.getElementById('wp-greet-octext');
    objb.readOnly = (obja.checked == false);
    objc.readOnly = (obja.checked == false);
}
</script>
<div class="wrap">
   <h2><?php echo __("wp-greet Setup","wp-greet") ?></h2>
   <form name="wpgreetadmin" method="post" action=''>
   <table class="optiontable">
          <tr valign="top">
          <th scope="row"><?php echo __('Gallery-Plugin',"wp-greet")?>:</th>
          <td><select name="wp-greet-gallery" size="1" >
          <option value="-" <?php if ($wpg_options['wp-greet-gallery']=="-") echo "selected='selected'";?>>none</option>
          <option value="ngg" <?php if ($wpg_options['wp-greet-gallery']=="ngg") echo "selected='selected'";?>>Nextgen Gallery</option>
          </select> 
          </td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php echo __('Form-Post/Page',"wp-greet")?>:</th>
          <td><select name="wp-greet-formpage" size="1">
<?php 
										  $r = '';
  foreach( $pagearr as $p )
    if ( $wpg_options['wp-greet-formpage'] == $p->id )
      $o = "\n\t<option selected='selected' value='".$p->id."'>".$p->post_title."</option>";
    else
      $r .= "\n\t<option value='".$p->id."'>".$p->post_title."</option>";
  echo $o . $r."\n";
?>
          </select></td></tr>
   
	      <tr><th scope="row"><?php echo __("Mailtransfermethod","wp-greet")?>:</th>
            <td>
              <input type="radio" name="wp-greet-usesmtp" id="wp-greet-usesmtp1" value="1" <?php if ($wpg_options['wp-greet-usesmtp']=="1") echo "checked=\"checked\" "; ?> onclick="wechsle_inline();"  />SMTP (class-phpmailer.php)
	      <input type="radio" name="wp-greet-usesmtp" id="wp-greet-usesmtp2" value="0" <?php if ($wpg_options['wp-greet-usesmtp']=="0") echo "checked=\"checked\" "; ?> onclick="wechsle_inline();" /> PHP mail() function  </td></tr>

          <tr valign="top">
          <th scope="row"><?php echo __('Mailreturnpath',"wp-greet")?>:</th>
          <td><input name="wp-greet-mailreturnpath" type="text" size="30" maxlength="80" value="<?php echo $wpg_options['wp-greet-mailreturnpath'] ?>" /></td>
          </tr>
    
          <tr valign="top">
          <th scope="row"><?php echo __('Send Bcc to',"wp-greet")?>:</th>
          <td><input name="wp-greet-bcc" type="text" size="30" maxlength="80" value="<?php echo $wpg_options['wp-greet-bcc'] ?>" /></td>   
          </tr>

          <tr valign="top">
          <th scope="row">&nbsp;</th>
          <td>
          <input type="checkbox" name="wp-greet-imgattach" id="wp-greet-imgattach" value="1" <?php if ($wpg_options['wp-greet-imgattach']=="1") echo "checked=\"checked\" "; ?>  onclick="wechsle_stamp();" /> <b><?php echo __('Send image inline',"wp-greet")?></b></td>
	  </tr>

          <tr valign="top">
          <th scope="row">&nbsp;</th>
          <td>
          <input type="checkbox" name="wp-greet-onlinecard" id="wp-greet-onlinecard" value="1" <?php if ($wpg_options['wp-greet-onlinecard']=="1") echo "checked=\"checked\" "; ?>  onclick="wechsle_onlinecard();"  /> <b><?php echo __('Fetch cards online',"wp-greet")?></b></td>
	  </tr>

          <tr valign="top">
          <th scope="row"><?php echo __('Number of days an online card can be fetched',"wp-greet")?>:</th>
          <td><input id="wp-greet-ocduration" name="wp-greet-ocduration" type="text" size="10" maxlength="5" value="<?php echo $wpg_options['wp-greet-ocduration'] ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php echo __('Online card HTML mail text','wp-greet'); ?>:
          <br />
          </th>
          <td><textarea id='wp-greet-octext' name='wp-greet-octext' cols='50'rows='4'><?php echo $wpg_options['wp-greet-octext']; ?></textarea>
          <img src="<?php echo site_url(PLUGINDIR . "/wp-greet/tooltip_icon.png");?>" alt="tooltip" title='<?php _e("HTML allowed, use %sender% for sendername, %sendermail% for sender email-address, %receiver% for receiver name, %link% for generated link, %duration% for time the link is valid","wp-greet");?>'>
          </td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php echo __('Fixed image width',"wp-greet")?>:</th>
          <td><input name="wp-greet-imagewidth" type="text" size="10" maxlength="5" value="<?php echo $wpg_options['wp-greet-imagewidth'] ?>" /></td>
          </tr>


          <tr valign="top">
          <th scope="row"><?php echo __('Add stamp image',"wp-greet")?>:</th>
          <td><input name="wp-greet-stampimage" id="wp-greet-stampimage" type="text" size="40" maxlength="60" value="<?php echo $wpg_options['wp-greet-stampimage'] ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php echo __('Stampwidth in % of imagewidth',"wp-greet")?>:</th>
          <td><input name="wp-greet-stamppercent" id="wp-greet-stamppercent" type="text" size="5" maxlength="3" value="<?php echo $wpg_options['wp-greet-stamppercent'] ?>" /></td>
          </tr>

           <tr valign="top">
           <th scope="row">&nbsp;</th>
           <td><input type="checkbox" name="wp-greet-smilies" value="1" <?php if ($wpg_options['wp-greet-smilies']=="1") echo "checked=\"checked\""?> /> <b><?php echo __('Enable Smileys on greetcard form',"wp-greet")?></b></td>
	   </tr>

         <tr valign="top">
         <th scope="row">&nbsp;</th>
         <td><input type="checkbox" name="wp-greet-autofillform" value="1" <?php if ($wpg_options['wp-greet-autofillform']=="1") echo "checked=\"checked\""?> /> <b><?php echo __('Use informations from profile',"wp-greet")?></b></td>
         </tr>

         <tr valign="top"> 
         <th scope="row">&nbsp;</th>
         <td><input type="checkbox" name="wp-greet-logging" value="1" <?php if ($wpg_options['wp-greet-logging']=="1") echo "checked=\"checked\""?> /> <b><?php echo __('enable logging',"wp-greet")?></b></td>
         </tr>


          <tr valign="top">
          <th scope="row"><?php echo __('Default mail subject',"wp-greet")?>:</th>
          <td><input name="wp-greet-default-title" type="text" size="30" maxlength="80" value="<?php echo $wpg_options['wp-greet-default-title'] ?>" /></td>   
          </tr>

	  <tr valign="top">
          <th scope="row"><?php echo __('Default mail header','wp-greet'); ?>:</th>
          <td><textarea name='wp-greet-default-header' cols='50'rows='4'><?php echo $wpg_options['wp-greet-default-header']; ?></textarea>
          <img src="<?php echo site_url(PLUGINDIR . "/wp-greet/tooltip_icon.png");?>" alt="tooltip" title='<?php _e("HTML is allowed","wp-greet");?>'>
          </td>
          </tr>

	  <tr valign="top">
          <th scope="row"><?php echo __('Default mail footer','wp-greet'); ?>:</th>
          <td><textarea name='wp-greet-default-footer' cols='50'rows='4'><?php echo $wpg_options['wp-greet-default-footer']; ?></textarea>
           <img src="<?php echo site_url(PLUGINDIR . "/wp-greet/tooltip_icon.png");?>" alt="tooltip" title='<?php _e("HTML is allowed","wp-greet");?>'>
           </td>
           </tr>
  
          <tr valign="top">
          <th scope="row"><?php echo __('Number of days log entries are stored',"wp-greet")?>:</th>
          <td><input id="wp-greet-logdays" name="wp-greet-logdays" type="text" size="10" maxlength="5" value="<?php echo $wpg_options['wp-greet-logdays'] ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php echo __('Number of days card entries are stored',"wp-greet")?>:</th>
          <td><input id="wp-greet-carddays" name="wp-greet-carddays" type="text" size="10" maxlength="5" value="<?php echo $wpg_options['wp-greet-carddays'] ?>" /></td>
          </tr>

  </table>
<?php
      echo "<div class='submit'><input type='submit' name='info_update' value='".__('Update options',"wp-greet")." »' /></div></form><script type=\"text/javascript\">wechsle_inline (); wechsle_onlinecard();wechsle_stamp();</script></div>";

}
?>
