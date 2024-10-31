=== Responder Integration ===
Contributors: natashasconnections
Tags:
Requires at least: 5.2
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author URI: https://www.responder.co.il/

Integrates WooCommerce with the Rav Messer mailing platform for advanced email marketing campaigns.

== Description ==

Woosponder Integration is designed exclusively for Rav Messer users with a WooCommerce account. This plugin seamlessly connects WooCommerce with the Rav Messer mailing platform, enabling you to enhance your email marketing strategies directly from your WordPress dashboard. With Woosponder Integration, you can sync your customer data with Rav Messer, segment customers based on their purchase history, and trigger targeted email campaigns.

Key Features:
- Automatic synchronization of customer details and order history with Rav Messer.
- Advanced segmentation options to target customers based on their behavior.
- Easy setup and management within the WordPress admin area.
- Supports custom fields for detailed customer profiling.
- Integrates with the WooCommerce checkout process for opt-in marketing.

This plugin is ideal for WooCommerce store owners who use Rav Messer for email marketing. It provides the necessary tools to create effective, data-driven campaigns that resonate with your audience.

## Third-Party Service Usage

This plugin uses external services provided by Rav Messer. Here are the details:

### Service Description:
The plugin makes requests to the Rav Messer API to manage email lists and subscribers, and to send subscriber data from WooCommerce to Rav Messer.

### When It's Used:
API calls to Rav Messer are made when:
- A new subscriber is added to a list.
- Subscriber details are updated.
- Custom fields are managed.

### Links to Service and Policies:
- [Rav Messer Home Page] http://www.responder.co.il 
- [Terms of Service] https://www.responder.co.il/%d7%aa%d7%a7%d7%a0%d7%95%d7%9f/

Please note that by using this plugin, you are agreeing to the terms of service and privacy policy of Responder.

## API Endpoints Accessed

This plugin accesses the following Responder API endpoints:
- http://api.responder.co.il/main/lists/
- http://api.responder.co.il/main/lists/$listId/subscribers
- http://api.responder.co.il/main/lists/$listId/personal_fields
- https://graph.responder.live/v2/tag
- https://graph.responder.live/v2/subscribers
- https://graph.responder.live/v2/lists
- https://graph.responder.live/v2/oauth/token



== Installation ==

1. Ensure you have WooCommerce installed and activated on your WordPress site.
2. Upload the `woosponder-integration` folder to the `/wp-content/plugins/` directory.
3. Activate the Woosponder Integration plugin through the 'Plugins' menu in WordPress.
4. Navigate to the Woosponder menu in the WordPress admin area to configure the plugin and connect it with your Rav Messer account.

== Frequently Asked Questions ==

= How do I connect to Rav Messer? =
After installing and activating the plugin, navigate to the Woosponder settings in your WordPress dashboard, and follow the instructions to connect your Rav Messer account.

= Is there support for custom fields in Rav Messer? =
Yes, the plugin supports custom fields, allowing you to send detailed customer information to your Rav Messer account.

== Changelog ==

= 1.0.0 =
- Initial release. Fully functional plugin with all features.

== Upgrade Notice ==

= 1.0.0 =
Initial release.