=== Restrict Content Pro - EDD Member Downloads ===
Author URI: https://restrictcontentpro.com
Contributors: mindctrl, easydigitaldownloads, nosegraze
Author: Restrict Content Pro Team
Tags: Restrict Content Pro, Easy Digital Downloads, EDD, download plans, download packs, stock photos, premium content, memberships, subscriptions, restrictions, membership rewards
Requires at least: 4.5
Tested up to: 4.7.3
Stable tag: 1.0.2
License: GPLv2 or later

Sell download subscriptions on your Restrict Content Pro powered membership site.


== Description ==

Sell download subscriptions on your Restrict Content Pro powered membership site.

This plugin is an add-on for [Restrict Content Pro](https://restrictcontentpro.com/) and [Easy Digital Downloads](https://wordpress.org/plugins/easy-digital-downloads).

Once activated, this plugin will provide a new option on the subscription level add/edit screen that lets you define the number of free downloads a member can have during each subscription period.

For more information, see the [documentation](http://docs.pippinsplugins.com/article/1470-easy-digital-downloads-member-downloads).

== Installation ==

1. Go to Plugins > Add New in your WordPress dashboard.
2. Search for "Restrict Content Pro - EDD Member Downloads"
3. Click "Install Now" on the plugin listed in the search results.
4. Click "Activate Plugin" after the plugin is installed.
5. Define the downloads allowed in Restrict Content Pro under Restrict > Subscription Levels.

== Frequently Asked Questions ==
= What happens when a member renews the subscription or the subscription automatically renews? =
The download count is reset for each subscription period. For example, if a subscription level is set up for a 1 month duration, and the subscription level allows 10 downloads, that means the member can download 10 items per month as long as he or she has an active membership.

= What happens when a member reaches the download limit? =
The regular Add to Cart button is shown, allowing the member to purchase the product if desired.

= What happens if a member expires? =
The regular Add to Cart button is shown, allowing the member to purchase the product if desired.

= Do unused downloads carry over into the next period? =
No, not at this time.

= Does it support EDD product bundles? =
No, not at this time.

= Does it support EDD variable price options? =
No, not at this time.

== Screenshots ==

1. The Downloads Allowed option on the subscription level add/edit screen.
2. A download button is shown to members with a membership that allows downloads, as long as his or her download limit has not been reached.
3. A note is added to the $0 payment record, to show that the payment was created due to the member downloading the item allowed by their membership.
4. The standard purchase button is displayed when visitors do not have a membership that allows file downloads, or if the member has reached the download limit for his or her plan.

== Changelog ==

= 1.0.2 =
* New: Credit downloads remaining total when a member download payment record is refunded.

= 1.0.1 =
* New: Added [rcp_edd_member_downloads_remaining] shortcode to display downloads remaining in the member's current subscription period.
* Fix: Prevent multiple downloads from firing when an item's Add to Cart button is shown more than once on the same page.
* Fix: Wrong file possibly downloaded if the item was purchased before this plugin was activated and that purchase had multiple items attached to it.

= 1.0 =
* Initial Release