=== irrelevantComments ===
Contributors: loeffler
Donate link: http://www.kosmonauten.cc/wordpress/irrelevantcomments
Tags: comments
Requires at least: 2.9
Tested up to: 3.1
Stable tag: 1.0.1

This plugin adds the ability to mark a comment as irrelevant.


== Description ==

This plugin adds the ability to mark a comment as irrelevant.
 
*This version requires Wordpress 2.9 or higher. If you want to use this Plugin with Wordpress 2.8 or 2.7, you need to download [version 0.9.3](http://wordpress.org/extend/plugins/irrelevantcomments/download/) of the plugin!*


**Features**

*   Mark comments as irrelevant. The comment will be replaced by the text `irrelevant Comment`. Clicking on that reveals the actual comment. 
*   A comment author can mark his own comments as irrelevant
*   The admin can mark all of the comments as irrelevant (via admin panel or link on each comment, requires "Edit comment" link (`<?php edit_comment_link(); ?>` in theme))
*   The option to mark a comment as irrelevant can disabled on post edit screen
*   includes English and German localization


== Installation ==

1. Upload folder `irrelevantComments` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Done

Notice: By deactivate the plugin, all informations associated with the plugin will be deleted.


== Frequently Asked Questions ==

= Why can't I mark a comment as irrelevant, even though the plugin is enabled? =

You have to activate "Allow to mark comments as irrelevant" on the post edit screen.


== Screenshots ==

1. Example - Standard Theme
2. Example - Kosmonauten.cc Theme
3. Admin backend
4. Option to disable the plugin for certain posts


== Changelog ==

= 1.0.1 =
* Bugfix: The link to mark a comment as irrelevant now works again.

= 1.0 =
* The association between a comment and the status "irrelevant" is now stored in the wp_commentmeta table, introduced in Wordpress 2.9
* Including an upgrade function, which converts the data from the old to the new version

= 0.9.3 =
* The javascript function to view the irrelevant comments has been moved to `irrelevantcomments.js`
* Fixed autosave bug

= 0.9.2 =
* Bugfixes
* Tested up to Wordpress 2.9

= 0.9 =
* First stable release


== Customization ==

The plugin provides the following CSS classes:

  *   `a.irrComments_adminlink` - Link to mark a comment as irrelevant (only shown to admins or authorized users)
  *   `p.irrComments_form` - Checkbox under the comment form
  *   `p.irrComments_link` - Link to show the comment text for irrelevant comments
  *   `div.irrComments_marked` - The text area for irrelevant comments

Just rename `irrelevantComments.example.css` to `irrelevantComments.css`. If you don't need the stylesheet, you can delete it.
