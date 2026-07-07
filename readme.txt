=== Custom Password Protected Messages ===
Contributors: lorenzof
Tags: password, password protected, private, message, page
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize the message displayed on password protected content, globally or with a different message for each page.

== Description ==

By default, WordPress displays a message such as "This content is password protected. To view it please enter your password below:" on password protected posts and pages.

**Custom Password Protected Messages** lets you replace that message with your own text or HTML. You can set a default message for all password protected content, and you can also assign a **different custom message to each specific page** from the plugin settings screen.

Features:

* Change the default password protected message to any text/HTML, using the visual editor.
* Add as many page-specific messages as you like: select a page, write its custom message, and add another one for a different page.
* Page-specific messages override the default message.
* Remove the "Protected:" prefix from the title, so "Protected: Page Title" shows as "Page Title".
* Change the "Password" input label to any custom text.
* Dedicated settings screen under **Settings → Password Messages**.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/custom-password-protected-messages` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to **Settings → Password Messages** to configure your messages.

== Frequently Asked Questions ==

= Can I set a different message for each page? =

Yes. On the settings screen, under "Page-specific messages", select a page and write its custom message. Click "Add another message" to configure a different message for another page.

= What happens if a page has no specific message? =

The default message is shown. If no default message is set either, the standard WordPress message is displayed.

= Does it work with posts too? =

The default message applies to all password protected posts and pages. Page-specific messages currently apply to pages.

== Screenshots ==

1. Settings screen with the default message and page-specific messages.
2. Custom message displayed on a password protected page.

== Changelog ==

= 1.0.0 =
* Initial release.
* Dedicated settings page under Settings → Password Messages.
* Default message with visual editor.
* Multiple page-specific messages: select a page and assign it a custom message.
* Custom "Password" field label.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
