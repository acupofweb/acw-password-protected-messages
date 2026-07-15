=== ACW Custom Messages for Password Protected Pages ===
Contributors: lorenzof
Tags: password, password protected, private, message, page
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize the message displayed on password protected content, globally or with a different message for each page or post.

== Description ==

By default, WordPress displays a message such as "This content is password protected. To view it please enter your password below:" on password protected posts and pages.

**ACW Custom Messages for Password Protected Pages** lets you replace that message with your own text or HTML. You can set a default message for all password protected content, and you can also assign a **different custom message to each specific page, post or custom post type** from the plugin settings screen.

Features:

* Change the default password protected message to any text/HTML, using the visual editor.
* Add as many content-specific messages as you like: select a page, post or custom post type item, write its custom message, and add another one for different content.
* Content-specific messages override the default message.
* The content dropdown only lists password protected items, so it stays short and easy to browse even on large sites.
* Optional "wrong password" message: WordPress gives no feedback when a wrong password is entered — enable this message to let visitors know their attempt failed.
* Remove the "Protected:" prefix from the title, so "Protected: Page Title" shows as "Page Title".
* Change the "Password" input label to any custom text.
* Dedicated settings screen under **Settings → Password Messages**.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/acw-password-protected-messages` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to **Settings → Password Messages** to configure your messages.

== Frequently Asked Questions ==

= Can I set a different message for each page or post? =

Yes. On the settings screen, under "Content-specific messages", select the content and write its custom message. Click "Add another message" to configure a different message for other content.

= What happens if a page or post has no specific message? =

The default message is shown. If no default message is set either, the standard WordPress message is displayed.

= Does it work with posts and custom post types too? =

Yes. Both the default message and the content-specific messages work with pages, posts and any public custom post type.

= Can I show an error when the password is wrong? =

Yes. By default WordPress simply reloads the form without any feedback. Fill in the "Wrong password message" field on the settings screen and visitors will see it after a failed attempt. Leave it empty to keep the default behavior.

= Why doesn't my page appear in the dropdown? =

The dropdown only lists content that is password protected. Set the page visibility to "Password protected" first, then come back to the settings screen.

== Screenshots ==

1. Settings screen with the default message and page-specific messages.
2. Custom message displayed on a password protected page.

== Changelog ==

= 1.1.0 =
* Content-specific messages now work with posts and custom post types, not just pages.
* The content dropdown only lists password protected items, grouped by post type.
* Previously saved selections stay visible even if the content is no longer password protected.
* New optional "wrong password" message, shown after a failed attempt (WordPress shows no feedback by default).

= 1.0.0 =
* Initial release.
* Dedicated settings page under Settings → Password Messages.
* Default message with visual editor.
* Multiple page-specific messages: select a page and assign it a custom message.
* Custom "Password" field label.

== Upgrade Notice ==

= 1.1.0 =
Custom messages can now be assigned to posts and custom post types, and you can show an error message after a wrong password attempt.

= 1.0.0 =
Initial release.
