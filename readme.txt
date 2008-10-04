=== wp-greet ===
Tags: greetingcard, send, email, nextgengallery, plugin
Requires at least: 2.5
Tested up to: 2.6
Stable tag: 1.1

wp-greet is a wordpress plugin to send greeting cards from your wordpress blog. it takes advantage from nextGenGallery to maintain your greetingcard pictures.

== Description ==
/*
Plugin Name: wp-greet
Plugin URI: http://www.tuxlog.de
Description:  wp-greet is a wordpress plugin to send greeting cards from your wordpress blog.
Version: 1.1
Author: Barbara Jany, Hans Matzen <webmaster at tuxlog dot de>
Author URI: http://www.tuxlog.de
*/

/*  Copyright 2008 Barbara Jany, Hans Matzen(email: webmaster at tuxlog dot de)

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

you are reading the readme.txt file for the wp-greet plugin.
wp-greet is a plugin for the famous wordpress blogging package,
giving your users the ability to send greeting cards from your blog.

 
== features ==

   + uses nextGenGallery for maintainig the greeting card picturea
   + storing statistics about the sent greeting cards 
   + adding your own css
   + control who can send cards
   + add default subject, header and footer to the greeting cards
   + add a bcc and/or a mailreturnpath to the mail


== requirements ==

   + PHP >=4.3
   + Wordpress >= 2.5.x
   + nextGenGallery >= v0.98


== installation from v1.1 on ==
	
	1.  Upload to your plugins folder, usually
	    `wp-content/plugins/`, keeping the directory structure intact
	    (i.e. `wp-greet.php` should end up in
	    `wp-content/plugins/wp-greet/`).

	2.  Activate the plugin on the plugin screen.

	3.  Visit the configuration page (Options -> wp-greet) to
            configure the plugin (do not forget to add the forms page id)

        4.  Optional
	    If you would like to change the style, just edit wp-greet.css

== update from <v1.1 ==   IMPORTANT =======================================
   	Please be sure to remove all files belonging to versions prior 
	to v1.1 before uploading v1.1

   	Please be sure to remove the patched version of nggfunctions.php
	which was necessary to integrate wp-greet with NextGenGallery 
	prior to version 1.1



== usage from v1.1 on ==
   	 1. Create a page or posting containing the tag [wp-greet].
	 2. Remember the permalink of this page/post
	 3. Enter the page/post number at the wp-greet admin dialog
	    into the field Form-Post/Page and switch to your favourite 
	    gallery plugin
   	 3. Create a page with your favourite gallery on it 
	    using the following syntax, e.g. for ngg: [gallery=1]
	 4. thats it, just klick on a picture on the gallery page 
	    and send it

For more details see the online documentation of wp-greet.
http://www.tuxlog.de/wordpress/2008/wp-greet/	 

== translations ==

   wp-greet comes with english and german translations only, at the moment.
   if you would like to add a new translation, just take the file
   wp-greet.pot (in the wp-greet main directory) copy it to
   <iso-code>.po and edit it to add your translations (e.g. with poedit).



== history ==
2008-04-06 v0.9	   Initial release 
2008-04-14 v1.0	   added captcha support, removed dependency to 
	   	   phpmailer package
2008-10-04 v1.1	   integrate ngg without patching it (thanks to Alex Rabe 
	   	   for adding the needed filter hooks), add gallery selection 
		   to admin dialog, add form page selector to admin dialog, 
		   fixed quote handling in textarea, disable captcha parameter 
		   during installation, extended css to be more flexible with
		   different themes


== todo ==
   - add support for mygallery
   - add support for wp gallery (>=v2.5)





 

