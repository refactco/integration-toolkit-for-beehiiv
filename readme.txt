=== beehiiv to WordPress - Publish beehiiv newsletters as posts ===
 
Contributors:      refact, saeedja, masoudin
Requires at least: 5.5.0
Tested up to:      6.6.1
Requires PHP:      7.4
Stable tag:        2.0.1
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: esp, beehiiv, import, email, importer
 
beehiiv to WordPress is a powerful tool between beehiiv and your WordPress site. Import all your content automatically and save time 
 
== Description ==
 
beehiiv to WordPress provides you with the necessary integration to import content automatically between beehiiv and your WordPress site.

This plugin is designed to be simple and ensures your WordPress site is always updated with the latest content. "beehiiv to WordPress" allows you to: 
 
- Select the content type to import from beehiiv (free, premium, etc.)
- Choose which posts to import from beehiiv (published, archived, draft, etc.)
- Choose the post type, taxonomy, and term for the imported posts in WordPress
- Select the WordPress user to assign the imported posts to
- Decide whether to import beehiiv post tags as WordPress post tags
- Determine the post status for the imported content
- Choose to import only new posts, update existing posts, or both
- Set an import interval to prevent high load on slow servers
- Configure the import schedule frequency for importing posts from beehiiv
 
In addition to its importing capabilities, this version of The "beehiiv to WordPress" integrates perfectly with Yoast SEO plugin, allowing you to add Canonical tags to all your imported content. This integration helps in improving your SEO ranking by preventing duplicate content issues and directing search engines to the original content.
 
While “beehiiv to WordPress” is not officially developed or endorsed by beehiiv, it adheres to all best practices and protocols, ensuring a secure and effective synchronization between your WordPress site and beehiiv.

### Third-Party Service Information

This plugin utilizes Beehiiv’s external API services to facilitate content importation. When you use this plugin, data such as post content, author information, and tags are transmitted to and from Beehiiv under the following conditions:
- When manually or automatically importing posts to WordPress.
- When updating existing posts with new content from Beehiiv.

For more detailed information about the Beehiiv services and their privacy practices, please refer to:
- Beehiiv API Homepage: [https://api.beehiiv.com/v2]
- Terms of Use: [https://www.beehiiv.com/tou]
- Privacy Policy: [https://www.beehiiv.com/privacy]

### API Integration Points

Our plugin communicates with Beehiiv’s API at the following endpoint:
- `BASE_URL`: `https://api.beehiiv.com/v2` — Main endpoint for fetching and sending data to Beehiiv.

### Javascript Packages
- Font Awesome: [https://docs.fontawesome.com/]
- Tippy.js: [https://atomiks.github.io/tippyjs/v6/getting-started/]
- Popper.js: [https://popper.js.org/docs/v2/]

### Legal and Security

The secure handling of your data is paramount. We use industry-standard security measures during data transmission to Beehiiv. By using this plugin, you acknowledge and consent to the transfer of data to Beehiiv as described in the Third-Party Service Information section. It is your responsibility to ensure that the use of Beehiiv’s API complies with all relevant legal requirements applicable to your geographical location.

While “beehiiv to WordPress” is not officially developed or endorsed by Beehiiv, it adheres to all best practices and protocols, ensuring a secure and effective synchronization between your WordPress site and Beehiiv.

= We want your input =
 
If you have any suggestions for improvements, feature updates, etc., or would like to simply give us feedback, then we want to hear it. Please email your comments to dev@refact.co
 
== Frequently Asked Questions ==
 
= What is "beehiiv to WordPress"? =
"beehiiv to WordPress" is a WordPress plugin designed to seamlessly integrate and synchronize your beehiiv content with your WordPress website. It provides a user-friendly interface for manual or auto-import of your beehiiv content directly into your WordPress..
 
= How do I connect my beehiiv account to the plugin? =
Once you’ve installed and activated the "beehiiv to WordPress" plugin, navigate to the plugin’s settings in your WordPress dashboard. Here, you’ll be prompted to input your beehiiv API key and Publication ID to establish the connection.
 
 
= Can I choose which content to import from beehiiv? =
With manual imports, you can select specific content, whereas the auto-import feature will periodically sync your beehiiv content based on your set preferences.
 
= How frequently can I set the auto-import feature? =
The plugin allows you to define the time intervals for auto-imports. You can set the frequency in hours based on your content update needs.
 
= Will my beehiiv tags be imported as well? =
 Absolutely! The plugin ensures that your beehiiv tags are imported, helping maintain the organization and structure of your content on your WordPress site.
 
= Is there a way to select a specific author for the imported content? =
Yes, when setting up your import preferences, you have the option to assign the imported posts to a specific user or author in your WordPress setup.
 
= I’m facing an issue with the plugin. How can I get support? =
We’re here to assist you! Please reach out to us through [support-link], and our team will address your concerns promptly.
 
= Is my beehiiv data secure during the import process? =
The "beehiiv to WordPress" plugin establishes a secure connection using your beehiiv API key to ensure a safe and trusted data transfer process.
 
= Can I disconnect my beehiiv account from the plugin? =
Yes, at any time you can navigate to the plugin’s settings in your dashboard and click the ‘Disconnect’ button to unlink your beehiiv account. 
 
== Installation ==
= Installation from within WordPress =
1. Visit **Plugins > Add New**.
2. Search for **beehiiv to WordPress**.
3. Install and activate the 'beehiiv to WordPress' plugin.
4. After activation, navigate to the beehiiv to WordPress section in your WordPress dashboard.
5. Follow the on-screen instructions to set up content imports and subscription forms.
 
= Manual installation =
1. Download the plugin zip file
2. Upload it to the `/wp-content/plugins/` directory in your website.
3. Visit the **Plugins** in your WordPress Dashboard.
4. Locate 'beehiiv to WordPress' and activate it.
5. After activation, navigate to the beehiiv to WordPress section in your WordPress dashboard.
6. Follow the on-screen instructions to set up content imports and subscription forms.
 
== Screenshots ==
1. The Settings page
2. Manual Import
3. Automatic Import

== Upgrade Notice ==
= v1.0.0 =
New: Initial release
 
== Changelog ==
 
= v1.0.0 =
New: Initial release

= v1.0.1 =
Fix: Minor bug fixes

= v2.0.0 =
improvements: Added new features

= v2.0.1 =
Fix: Resolved bugs and improved stability
Compatibility: Added backward compatibility with versions earlier than v2.0.0


