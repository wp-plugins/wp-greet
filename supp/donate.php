<?php
/*  Copyright 2009-2014  Hans Matzen  (email : support at tuxlog.de)

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

// donation meta box
if (!function_exists('tl_add_donation_box')) {
  function tl_add_donation_box() {
    if (WPLANG == "de_DE") {
      $lc="de";
      $teaser="Macht Ihnen das Plugin Freude?";
    } else if (WPLANG == "fr_FR") {
      $lc="fr";
      $teaser="voulez-vous de m'inviter à prendre un café?";
    } else {
      $lc="en";
      $teaser="Wanna buy me a coffee?";	
    }
    
    $img = "btn_donate_SM_$lc.gif";
    $imgurl=plugins_url( $img , __FILE__); 
    
    $backurl = get_option('home');
    $bm=str_rot13("unaf@unafzngmra.qr");
    $ret=<<<EOF
      <form style="display:inline;" action="https://www.paypal.com/cgi-bin/webscr" method="post">
      <input type="hidden" name="cmd" value="_donations" />
      <input type="hidden" name="business" value="$bm" />
      <input type="hidden" name="item_name" value="tuxlog Spende" />
      <input type="hidden" name="no_shipping" value="1" />
      <input type="hidden" name="return" value="$backurl" />
      <input type="hidden" name="cancel_return" value="$backurl" />
      <input type="hidden" name="cn" value="Nachricht" />
      <input type="hidden" name="currency_code" value="EUR" />
      <input type="hidden" name="tax" value="0" />
      <input type="hidden" name="lc" value="DE" />
      <input type="hidden" name="bn" value="PP-DonationsBF" />
      <label for="donlogo">$teaser</label>
      <input id="donlogo" type="image" src="$imgurl" name="submit" alt="tuxlog Spende"  style="vertical-align:middle;margin:10px;"/>
      </form>
EOF;
    
    return $ret;
  }
}
  // end of meta box
?> 