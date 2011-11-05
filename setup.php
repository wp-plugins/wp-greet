<?php
/* This file is part of the wp-greet plugin for wordpress */

/*  Copyright 2008,2009  Hans Matzen  (email : webmaster at tuxlog.de)

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
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You 
are not allowed to call this page directly.'); }

//
// setting up the default options in table wp-options during 
// plugin activation from the plugins page
//
function wp_greet_activate()
{
    global $wpdb, $wp_roles, $wp_version,$wpg_options;
    
    // upgrade function changed in WordPress 2.3	
    if (version_compare($wp_version, '2.3', '>='))		
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    else
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    
    $wpg_options = wpgreet_get_options();
    
    
    $wpg_options['wp-greet-version'] = WP_GREET_VERSION;
    update_option("wp-greet-version",$wpg_options['wp-greet-version'],
		  "versionnumber of the installed wp-greet","yes");
    
    // create table	
    $sql = "CREATE TABLE " . $wpdb->prefix . 'wpgreet_stats' . " (
	    mid BIGINT NOT NULL AUTO_INCREMENT ,
            senttime DATETIME NOT NULL,
	    frommail VARCHAR(80) NOT NULL,
            tomail VARCHAR(80) NOT NULL,
            picture VARCHAR(255) NOT NULL,
            mailbody MEDIUMTEXT NOT NULL,
            remote_ip VARCHAR(15) NOT NULL,
	    PRIMARY KEY  mid (mid)
	    );";
    
    dbDelta($sql);
    
    // create table for greet card store (since v1.7)	
    $sql = "CREATE TABLE " . $wpdb->prefix . 'wpgreet_cards' . " (
	    mid BIGINT NOT NULL AUTO_INCREMENT ,
            fromname VARCHAR(80) NOT NULL,
	    frommail VARCHAR(80) NOT NULL,
            toname VARCHAR(80) NOT NULL,
            tomail VARCHAR(80) NOT NULL,
            cc2from smallint NOT NULL,
            subject VARCHAR(255) NOT NULL,
            picture VARCHAR(255) NOT NULL,
            mailbody MEDIUMTEXT NOT NULL,
            confirmuntil timestamp NOT NULL,
            confirmcode VARCHAR(32) NOT NULL,
            fetchuntil timestamp NOT NULL,
            fetchcode VARCHAR(32) NOT NULL,
            card_sent timestamp NOT NULL,
	        card_fetched timestamp NOT NULL,
	        future_send timestamp NOT NULL
            PRIMARY KEY  mid (mid)
	    );";
    
    dbDelta($sql);
    

    if ($wpg_options['wp-greet-minseclevel'] == "") {
	$wpg_options['wp-greet-minseclevel'] = 'Registered';
	add_option("wp-greet-minseclevel",$wpg_options['wp-greet-minseclevel'],
		   "the minimum security level to send greeting cards","yes");
    };
    
    
    if ($wpg_options['wp-greet-captcha'] == "") {
	$wpg_options['wp-greet-captcha'] = 0;
	add_option("wp-greet-captcha",$wpg_options['wp-greet-captcha'],
		   "wanna use a captcha to prevent spamming?","yes");
    }; 
    
    if ($wpg_options['wp-greet-mailreturnpath'] == "") {
	$wpg_options['wp-greet-mailreturnpath'] = "";
	add_option("wp-greet-mailreturnpath",$wpg_options['wp-greet-mailreturnpath'],"the standard mail return path","yes");
    }; 
    
    if ($wpg_options['wp-greet-autofillform'] == "") {
	$wpg_options['wp-greet-autofillform'] = 1;
	add_option("wp-greet-autofillform",$wpg_options['wp-greet-autofillform'],
		   "try to fill form with user data","yes");
    };
    
    if ($wpg_options['wp-greet-imagewidth'] == "") {
	$wpg_options['wp-greet-imagewidth'] = 400;
	add_option("wp-greet-imagewidth",$wpg_options['wp-greet-imagewidth'],
		   "gives the fixe image width","yes");
    };

    if ($wpg_options['wp-greet-stampimage'] == "") {
	$wpg_options['wp-greet-stampimage'] = "wp-content/plugins/wp-greet/defaultstamp.jpg";
	add_option("wp-greet-stampimage",$wpg_options['wp-greet-stampimage'],
		   "file name of stamp-image","yes");
    };

    if ($wpg_options['wp-greet-stamppercent'] == "") {
	$wpg_options['wp-greet-stamppercent'] = 15;
	add_option("wp-greet-stamppercent",$wpg_options['wp-greet-stamppercent'],
		   "gives the stamp width in percent of imagewidth","yes");
    };

    if ($wpg_options['wp-greet-logging'] == "") {
	$wpg_options['wp-greet-logging'] = 1;
	add_option("wp-greet-logging",$wpg_options['wp-greet-logging'],
		   "enable logging","yes");
    };
    
    if ($wpg_options['wp-greet-gallery'] == "") {
	$wpg_options['wp-greet-gallery'] = "ngg";
	add_option("wp-greet-gallery",$wpg_options['wp-greet-gallery'],
		   "which gallery to use","yes");
    };
    
    if ($wpg_options['wp-greet-linesperpage'] == "") {
	$wpg_options['wp-greet-linesperpage'] = "10";
	add_option("wp-greet-linesperpage",$wpg_options['wp-greet-linesperpage'],
		   "lines on each page on log page","yes");
    }; 
    
    if ($wpg_options['wp-greet-usesmtp'] == "") {
	$wpg_options['wp-greet-usesmtp'] = "1";
	add_option("wp-greet-usesmtp",$wpg_options['wp-greet-usesmtp'],
		   "which mail transfer method to use smtp=1, php mail=0","yes");
    };
    
    if ($wpg_options['wp-greet-touswitch'] == "") {
	$wpg_options['wp-greet-touswitch'] = "1";
	add_option("wp-greet-touswitch",$wpg_options['wp-greet-touswitch'],
		   "show terms of usage confirmation yes=1 or no=0","yes");
    }; 
    
    if ($wpg_options['wp-greet-termsofusage'] == "") {
	$wpg_options['wp-greet-termsofusage'] = "<h2>Grußkarten (eCard) System: Nutzungsbedinungen- und Hinweise</h2>
<p>Bevor Sie eine Grußkarte absenden können, müssen Sie die Grußkarte bestätigen. Dazu erhalten Sie nach absenden der Grußkarte eine Email, die einen Bestätigungslink enthält. Erst nach klicken dieses Links wird Ihre Grußkarte tatsächlich abgeschickt.</p>

<p>Mit Absenden Ihrer eCard haben Sie nachfolgende Hinweise gelesen und bestätigt.
Der eCard Versender stellt sicher, dass er bei der Nutzung unseres eCard Service nicht gegen geltende Rechtsvorschriften verstößt. Der Versender stellt ebenso sicher und bestätigt, vor versenden seiner eCard, dass die Empfänger der verschickten Nachrichten mit dem Empfang der Nachricht einverstanden sind. Die Versendung kommerzieller Werbe Mails über den Grußkartendienst wird hiermit ausdrücklich untersagt. Ebenfalls ausdrücklich untersagt ist die Versendung von unerwünschten Mails (SPAM) oder Massen-Mails.</p>

<p>Das Urheberrecht aller eCards liegt beim Betreiber dieser Site. Die Genehmigung zum Versenden der eCards zwecks Grußkartenübermittlung ist hiermit erteilt. Jegliche weitere Nutzung bedarf einer schriftlichen Genehmigung.</p>

<p>Datenschutz: Ihre eMail-Adresse und die eMail-Adresse des Empfängers werden ausschließlich zu Übertragungszwecken verwendet . Zum Einen um Ihnen die Möglichkeit zu geben die Karte als Absender per Email zu bestätigen zum Anderen um den Empfänger über die eCard zu informieren, bzw. Sie, als Absender einer eCard, bei einem Fehler beim Versenden, zu benachrichtigen.</p>";
	add_option("wp-greet-termsofusage",$wpg_options['wp-greet-termsofusage'],
		   "html text for terms of usage","yes");
    };   
    
    if ($wpg_options['wp-greet-mailconfirm'] == "") {
	$wpg_options['wp-greet-mailconfirm'] = "0";
	add_option("wp-greet-mailconfirm",$wpg_options['wp-greet-mailconfirm'],
		   "use a confirmation mail to prevent spamming yes=1 or  no=0",
		   "yes");
    }; 
    
    if ($wpg_options['wp-greet-mcduration'] == "") {
	$wpg_options['wp-greet-mcduration'] = "0";
	add_option("wp-greet-mcduration",$wpg_options['wp-greet-mcduration'],
		   "the number of hours the confirmation link is valid",
		   "yes");
    };
    
    if ($wpg_options['wp-greet-mctext'] == "") {
	$wpg_options['wp-greet-mctext'] = "Hallo %sender%,
bitte bestätigen Sie, dass Sie %sender% (%sendermail%) eine Grußkarte senden möchten, indem Sie auf den unten stehenden Link klicken.

Bestätigungslink: %link%

Wenn Sie nicht wissen warum Sie diese E-Mail erhalten oder die Postkarte nicht absenden möchten, dann ignorieren Sie diese E-Mail oder kontaktieren den Administrator, indem Sie einfach auf diese E-Mail antworten.

Mit freundlichen Grüßen
wp-greet, das freundliche Wordpress Grußkartenplugin";
	add_option("wp-greet-mctext",$wpg_options['wp-greet-mctext'],
		   "html text for confirmation mail","yes");
    };  

    if ($wpg_options['wp-greet-ectext'] == "") {
	$wpg_options['wp-greet-ectext'] = "Hallo %sender% (%sendermail%),
Ihre Grusskarte wurde unter dem %link% abgeholt. Vielen Dank, dass Sie den Grusskartendienst von wp-greet genutzt haben.

Wenn Sie nicht wissen warum Sie diese E-Mail erhalten oder die Postkarte nicht absenden möchten, dann ignorieren Sie diese E-Mail oder kontaktieren den Administrator, indem Sie einfach auf diese E-Mail antworten.

Mit freundlichen Grüßen
wp-greet, das freundliche Wordpress Grußkartenplugin";
	add_option("wp-greet-ectext",$wpg_options['wp-greet-ectext'],
		   "html text for confirmation mail to sender","yes");
    };  
    
    if ($wpg_options['wp-greet-onlinecard'] == "") {
	$wpg_options['wp-greet-onlinecard'] = "0";
	add_option("wp-greet-onlinecard",$wpg_options['wp-greet-onlinecard'],
		   "fetch cards online, dont send cards via email",
		   "yes");
    };  
    
    if ($wpg_options['wp-greet-ocduration'] == "") {
	$wpg_options['wp-greet-ocduration'] = "0";
	add_option("wp-greet-ocduration",$wpg_options['wp-greet-ocduration'],
		   "number of days an online card can be fetched",
		   "yes");
    }; 
    
    if ($wpg_options['wp-greet-octext'] == "") {
	$wpg_options['wp-greet-octext'] = "Hallo %receiver%,\n\n Sie haben eine Grußkarte von %sender% erhalten. Die Grußkarte kann unter %link% abgeholt werden.\n

Die Postkarte steht für Sie für die nächsten %duration% Tage zur Abholung bereit.

Mit freundlichen Grüßen
wp-greet, das freundliche Wordpress Grußkartenplugin";
	add_option("wp-greet-octext",$wpg_options['wp-greet-octext'],
		   "html text for link mail","yes");
    };   
    
    if ($wpg_options['wp-greet-logdays'] == "") {
	$wpg_options['wp-greet-logdays'] = "0";
	add_option("wp-greet-logdays",$wpg_options['wp-greet-logdays'],
		   "number of days log entries are stored",
		   "yes");
    };
    
    if ($wpg_options['wp-greet-carddays'] == "") {
	$wpg_options['wp-greet-carddays'] = "0";
	add_option("wp-greet-carddays",$wpg_options['wp-greet-carddays'],
		   "number of days card entries are stored",
		   "yes");
    };
   
    if ($wpg_options['wp-greet-fields'] == "") {
	$wpg_options['wp-greet-fields'] = "010100"; // sender and receiver email is allways mandatory
	add_option("wp-greet-fields",$wpg_options['wp-greet-fields'],
		   "string describing the mandatory fields in the form",
		   "yes");
    };
     
}

function wp_greet_deactivate()
{
    
    return; // avoid deletion of opts
    
    $options = array("wp-greet-minseclevel", 
		     "wp-greet-captcha", 
		     "wp-greet-mailreturnpath", 
		     "wp-greet-autofillform",
		     "wp-greet-bcc",
		     "wp-greet-imgattach",
		     "wp-greet-default-title",
		     "wp-greet-default-header",
		     "wp-greet-default-footer",
		     "wp-greet-imagewidth",
		     "wp-greet-logging",
		     "wp-greet-gallery",
		     "wp-greet-smilies",
		     "wp-greet-formpage",
		     "wp-greet-galarr",
		     "wp-greet-linesperpage",
		     "wp-greet-usesmtp",
		     "wp-greet-stampimage",
		     "wp-greet-stamppercent",
		     'wp-greet-touswitch',
		     'wp-greet-termsofusage',
		     'wp-greet-mailconfirm',
		     'wp-greet-mcduration',
		     'wp-greet-mctext',
		     'wp-greet-onlinecard',
		     'wp-greet-ocduration',
		     'wp-greet-octext',
		     'wp-greet-logdays',
		     'wp-greet-carddays',
		     'wp-greet-fields');
    
    reset($options);
    while (list($key,$val) = each($options)) {
	delete_option($val);
    }
}

?>