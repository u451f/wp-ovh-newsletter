=== Subscription for OVH mailinglists ===
Contributors: veganist
Tags: email, newsletter, OVH
Requires at least: 4.5
Tested up to: 4.5.2
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds a shortcode [ovh_newsletter] through which users can subscribe to existing OVH mailing lists.

== Description ==

OVH is a european ISP and hoster. Their offer includes mailinglists which customers can configure to their needs.
This plugin provides a shortcode which allows people to add a subscription field to their Wordpress installation.
It uses Ajax to display subscription confirmations.

When people subscribe, a confirmation email is sent to them. They have to click on the link provided in the email in order to confirm their mailing list subscription.

In order to use this plugin you need an OVH account and need to have configured a mailinglist to use.
You will need to provide the plugin with your OVH credentials in order to allow it to talk to the OVH API.
As for now, the password is stored in cleartext in the database. This shall change in a new version of the plugin.

== Installation ==

1. Unzip and upload `/ovh-newsletter/` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the list of email addresses in the settings menu

== Changelog ==

= 0.1 =
* Initial release
