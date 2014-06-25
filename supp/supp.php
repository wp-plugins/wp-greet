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

if (!function_exists('tl_add_supp')) {
  function tl_add_supp($echoit=false) {
    $out="";
    $out .= '<div style="text-align:right;">';
    // donation link
    require_once(plugin_dir_path(__FILE__) . "/donate.php");
    $out .= tl_add_donation_box();
    // support link
    
    if (WPLANG == "de_DE") {
      $bt="Supportanfrage stellen";
      $teaser="Haben Sie eine Frage?";
    } else if (WPLANG == "fr_FR") {
      $bt="Envoyez une demande de soutien";
      $teaser="Avez-vous une question ?";
    } else {
      $bt="Send support request";
      $teaser="Any Questions?";	
    }
    
    $out .= $teaser . "&nbsp;&nbsp;&nbsp;";
    $out .= '<a href="mailto:support@tuxlog.de">'. $bt . '</a>&nbsp;&nbsp;&nbsp;';
    $out .="</div>";
    
    if ($echoit)
      echo $out;
    else
      return $out;
  }
}
?>