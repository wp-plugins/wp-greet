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
//if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }


// generic functions
require_once("../../../wp-config.php");
require_once("wpg-func.php");


global $wpg_options;

// wp-greet optionen aus datenbank lesen
$wpg_options = wpgreet_get_options();

// get translation 
load_plugin_textdomain('wp-greet',false,dirname( plugin_basename( __FILE__ ) ) . "/lang/");

//if (!isset($_GET['verify'])) {
    // show terms of usage
    echo $wpg_options['wp-greet-termsofusage'];
//} else {
    // verify card id
//}

?>
