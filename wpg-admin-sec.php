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
function wpg_admin_sec() 
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
  

  // if this is a POST call, save new values
  if (isset($_POST['info_update'])) {
    $upflag=false;
    
    reset($wpg_options);
    $thispageoptions = array("wp-greet-minseclevel", "wp-greet-captcha", 
			     "wp-greet-mailconfirm", "wp-greet-mcduration",
			     "wp-greet-mctext", "wp-greet-touswitch",
			     "wp-greet-termsofusage");
    while (list($key, $val) = each($wpg_options)) {
	if (in_array($key,$thispageoptions) and $wpg_options[$key] != $_POST[$key] ) {
	$wpg_options[$key] = stripslashes($_POST[$key]);
	$upflag=true;
	
	// add capabiliities if necessary
	if ($key=="wp-greet-minseclevel")
	  set_permissions($wpg_options[$key]);
      }
    }
    
    // save options and put message after update
    echo"<div class='updated'><p><strong>";

    // check for captcha plugin if captcha was set
    if ( $wpg_options['wp-greet-captcha'] >0 ) {
      $plugin_exists=false;
      $parr=get_plugins();
      foreach($parr as $key => $plugin) {
	//echo $plugin['Name']." ".ABSPATH.PLUGINDIR."/".$key."<br />";

	if ($plugin['Name'] == "CaptCha!" and 
	    file_exists(ABSPATH. PLUGINDIR . "/". $key) )
	  $plugin_exists=true;

	if ($plugin['Name'] == "Math Comment Spam Protection" and 
	    file_exists(ABSPATH. PLUGINDIR . "/". $key) )
	  $plugin_exists=true;
      }
      if (! $plugin_exists) {
	echo __('Captcha plugin not found.',"wp-greet"). "<br />";
	$upflag=false;
      }
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
 function wechsle_felder () {
    swa=document.getElementById('wp-greet-touswitch');
    swb=document.getElementById('wp-greet-termsofusage');
    if (swa.checked == false) 
	swb.readOnly = true;
    else
	swb.readOnly = false;

    swa=document.getElementById('wp-greet-mailconfirm');
    swb=document.getElementById('wp-greet-mcduration');
    swc=document.getElementById('wp-greet-mctext');
    if (swa.checked == false) { 
        swb.readOnly = true;
	swc.readOnly = true;
    } else {
	swb.readOnly = false;
	swc.readOnly = false;
	
    }
} 
</script>


<div class="wrap">
   <h2><?php echo __("wp-greet Security - Setup","wp-greet") ?></h2>
   <form name="wpgreetsec" method="post" action=''>
   <table class="optiontable">
   
   <tr valign="top">
      <th scope="row"><?php echo __('Spam protection',"wp-greet")?>:</th>
      <td><select name="wp-greet-captcha" size="1">		
 	 <option value="0" <?php if ($wpg_options['wp-greet-captcha']=="0") echo "selected=\"selected\""?> > <?php echo __("none","wp-greet"); ?></option>
               <option value="1" <?php if ($wpg_options['wp-greet-captcha']=="1") echo "selected=\"selected\""?> > CaptCha!</option>
               <option value="2" <?php if ($wpg_options['wp-greet-captcha']=="2") echo "selected=\"selected\""?> > Math Comment Spam Protection</option>
               </select>
           </td>
	   </tr>
 
        <tr valign="top">
         <th scope="row"><?php echo __('Minimum role to send card',"wp-greet")?>: </th>
            <td><select name="wp-greet-minseclevel" size="1">
<?php 
  $r = '';
  global $wp_roles;
  $roles = $wp_roles->role_names;
  foreach( $roles as $role => $name ) {
    if ( $wpg_options['wp-greet-minseclevel'] == $role )
      $r .= "\n\t<option selected='selected' value='$role'>$name</option>";
    else
      $r .= "\n\t<option value='$role'>$name</option>";
  }
  echo $r."\n";
  
?>
        <option value="everyone" <?php if ($wpg_options['wp-greet-minseclevel']=="everyone") echo "selected='selected'";?>><?php echo __('Everyone',"wp-greet")?></option>
   </select></td></tr>
       

    <tr valign="top">
           <th scope="row">&nbsp;</th>
           <td><input type="checkbox" id="wp-greet-touswitch" name="wp-greet-touswitch" value="1" <?php if ($wpg_options['wp-greet-touswitch']=="1") echo "checked=\"checked\""?> onclick="wechsle_felder();" /> <b><?php echo __('Enable Terms of Usage display and check',"wp-greet")?></b></td>
	   </tr>

    <tr valign="top">
          <th scope="row"><?php echo __('Terms of usage','wp-greet'); ?>:</th>
          <td><textarea id='wp-greet-termsofusage' name='wp-greet-termsofusage' cols='50'rows='6'><?php echo $wpg_options['wp-greet-termsofusage']; ?></textarea>
          <img src="<?php echo site_url(PLUGINDIR . "/wp-greet/tooltip_icon.png");?>" alt="tooltip" title='<?php _e("HTML is allowed","wp-greet");?>'></td>
          </tr>

         <tr valign="top">
         <th scope="row">&nbsp;</th>
         <td><input type="checkbox" id="wp-greet-mailconfirm" name="wp-greet-mailconfirm" value="1" <?php if ($wpg_options['wp-greet-mailconfirm']=="1") echo "checked=\"checked\""?> onclick="wechsle_felder();" /> <b><?php echo __('Use mail to verify sender address',"wp-greet")?></b></td>
         </tr>

          <tr valign="top">
	  <th scope="row">
          <?php _e('Verification mail text',"wp-greet");?>:</th>
          <td><textarea id='wp-greet-mctext' name='wp-greet-mctext' cols='50'rows='6'><?php echo $wpg_options['wp-greet-mctext']; ?></textarea>
          <img src="<?php echo site_url(PLUGINDIR . "/wp-greet/tooltip_icon.png");?>" alt="tooltip" title='<?php _e("HTML allowed, use %sender% for sendername, %sendermail% for sender email-address, %receiver% for receiver name, %link% for generated link, %duration% for time the link is valid","wp-greet");?>'>
           </td>
           </tr>
 
          <tr valign="top">
          <th scope="row"><?php echo __('Link valid time (hours)',"wp-greet")?>:</th>
          <td><input id="wp-greet-mcduration" name="wp-greet-mcduration" type="text" size="5" maxlength="4" value="<?php echo $wpg_options['wp-greet-mcduration'] ?>" /></td>
          </tr>


	
  </table>
<?php
      echo "<div class='submit'><input type='submit' name='info_update' value='".__('Update options',"wp-greet")." »' /></div></form><script type=\"text/javascript\">wechsle_felder();</script></div>";

}
?>
