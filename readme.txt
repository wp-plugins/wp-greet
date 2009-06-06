=== wp-greet ===
Contributors: tuxlog, woodstock
Donate link: http://www.tuxlog.de
Tags: greetingcard, send, email, nextgengallery, plugin
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 1.5

wp-greet is a wordpress plugin to send greeting cards from your wordpress blog. it uses nextGenGallery to maintain your greetingcard pictures.

== Description ==
wp-greet is a plugin for the famous wordpress blogging package,
giving your users the ability to send greeting cards from your blog.

Features:

   + uses nextGenGallery for maintainig the greeting card picturea
   + storing statistics about the sent greeting cards 
   + adding your own css
   + control who can send cards
   + add default subject, header and footer to the greeting cards
   + add a bcc and/or a mailreturnpath to the mail
   + supports Antispam Plugins CaptCha! and Math-Comment-Spam-Protection-Plugin
   + sign your greeting cards with your own stamp


== requirements ==

* PHP >=4.3
* Wordpress >= 2.5.x
* nextGenGallery >= v1.00


== Installation ==
	
1.  Upload to your plugins folder, usually `wp-content/plugins/`, keeping the directory structure intact (e.g. `wp-greet.php` should end up in `wp-content/plugins/wp-greet/`).

1.  Activate the plugin on the plugin screen.

1.  Visit the configuration page (Options -> wp-greet) to configure the plugin (do not forget to add the forms page id)

1.  Optional: If you would like to change the style, just edit wp-greet.css

== Frequently Asked Questions ==

= My greetcard form is wider than my theme. What can I do? =

To adjust the design of your greetingcard page edit the file wp-greet.css.
If you have a narrow theme you might adjust the width of the textarea
textarea.wp-greet-form { width: 90%; } by replacing the 90% with something smaller than this.


= How can I use the Math Comment Spam Protection Plugin with wp-greet? =

Upload the unzipped directory "math-comment-spam-protection" on your webspace into wp-content/plugins and activate the plugin. Under Settings -> Math Comment Spam klick "Update Options" once even without having changed any options, otherwise the plugin won't work. You don't have to change the text of the error messages, as these are fixed within wp-greet.

== Screenshots ==

1. Sending a greetingcard with wp-greet (shows the user interface for entering a greetingcard)
2. Preview a greetingcard with wp-greet 
3. Admin-Dialog of wp-greet

== update from prio v1.1 ==   
IMPORTANT:
   	Please be sure to remove all files belonging to versions prior 
	to v1.1 before uploading v1.1

   	Please be sure to remove the patched version of nggfunctions.php
	which was necessary to integrate wp-greet with NextGenGallery 
	prior to version 1.1



== usage from v1.1 on ==
1. Create a page or posting containing the tag [wp-greet].
1. Remember the permalink of this page/post
1. Enter the page/post number at the wp-greet admin dialog into the field Form-Post/Page and switch to your favourite gallery plugin
1. Create a page with your favourite gallery on it using the following syntax, e.g. for ngg: [gallery=1]
1. thats it, just klick on a picture on the gallery page and send it

For more details see the online documentation of wp-greet.
http://www.tuxlog.de/wordpress/2008/wp-greet-documentation-english/	 

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

2008-11-30 v1.2	   fixed some typos, added smiley support, added remote ip 
	   	   adress to log information, added automatic sender an 
		   receiver name, disable options deletion during plugin 
		   deactivation (seems people like it more having a bit trash 
		   in their tables, instead of setting up the plugin 
		   every time ;-) ), added fields for sender and receiver name

2009-01-03 v1.3	   add support for Math-Comment-Spam-Protection-Plugin, 
	   	   add paging for logfile, fix bug with ngg >v0.99 and 
		   thickbox effect

2009-02-08 v1.4	   fixed missing semicolon in phpmailer-conf.php, added 
	   	   none option to disable spam protection, fixed bug with 
		   quotes in mail-header and mail-footer, added option to 
		   control the mailtransfer method (smtp or php-mail), 
		   fixed Spamlabel was showed, even when no captcha 
		   support was selected

2009-06-06 v1.5	   clean up code to avoid warnings in wordpress debug mode,
	   	   add stamp function to add a stamp to greetingcards,
		   readme.txt validated, added screenshots to package,
		   added icon for wordpress menu entry, added parameter to 
		   set width of stamp
