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
//if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

// get parameter 
$cardimg_url  = $_GET['cci'];
$stampimg_url = $_GET['sti'];
$stampwidth   = $_GET['stw'];
$ob = ( isset($_GET['ob']) && $_GET['ob']==1  ? 1 : 0 );

// wenn direkter aufruf, dann header ausgeben
if ( $ob != 1 )
    header("Content-type: image/png");


// Bilder laden
$i1 = file_get_contents($cardimg_url);
$i2 = file_get_contents($stampimg_url);

$imgsrc     = imagecreatefromstring($i1);
$imgzeichen = imagecreatefromstring($i2); 

// Bild Infos
$width  = imagesx($imgsrc);  // Höhe Hauptbild
$height = imagesy($imgsrc);  // Breite Hauptbild

$x = imagesx($imgzeichen); // Höhe Bild 2
$y = imagesy($imgzeichen); // Breite Bild 2

// neues Bild erzeugen
$img = imagecreatetruecolor($width, $height);

// Postkarte in neues Bild einfügen
imagecopy($img, $imgsrc, 0, 0, 0, 0, $width, $height);

// Breite und Höhe der Marke berechnen
$newx = (int) ($width  * $stampwidth / 100.0);
$newy = (int) ($y * ( $newx / $x ));

// Briefmarke einfügen
$abstand_links = $width - $newx + 1;
$abstand_oben =  1;

//imagecopy($img, $imgzeichen, $abstand_links, $abstand_oben, 0, 0, $x, $y);
imagecopyresampled($img, $imgzeichen, $abstand_links, $abstand_oben, 
		   0, 0, $newx, $newy, $x, $y);

// wenn aufruf zur rückgabe als string, dann output umleiten
if ( $ob == 1 ) {
    ob_start();
    // Bild anzeigen
    imagepng($img);
    $out = ob_get_contents();
    ob_end_clean();
    echo $out;
} else
     imagepng($img);

// Speicher freigeben
imagedestroy($img);
imagedestroy($imgsrc);
imagedestroy($imgzeichen);
?>