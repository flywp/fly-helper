=== FlyWP Helper ===
Contributors: flywp, tareq1988
Tags: cache, helper, performance, hosting
Requires at least: 5.0
Tested up to: 6.2
Requires PHP: 7.4
Stable tag: 0.2.1
Requires PHP: 7.1 or higher
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily manage and communicate with your FlyWP cloud servers, control and purge nginx caching, and purge redis object cache for your WordPress sites.

== Description ==

FlyWP is a Docker based modern server control panel designed specifically for WordPress. It simplifies the process of managing your WordPress servers on various cloud platforms, such as DigitalOcean, AWS, Vultr, Linode, and Google Cloud. FlyWP ensures best practices, security, caching, and cron for every server you create, allowing even non-technical users to manage their own WordPress servers and sites.

The FlyWP WordPress plugin allows you to communicate with your server control panel, control and purge nginx caching on post update, edit or delete events, and provides the ability to purge redis object cache. The plugin enhances the overall performance of your WordPress site by leveraging the power of FlyWP's cloud server management platform.

**Features**

* **Seamless Integration**: Connect your WordPress site with the FlyWP server control panel with ease.
* **Cloud Server Management**: Manage your WordPress servers on DigitalOcean, AWS, Vultr, Linode, and Google Cloud with FlyWP's intuitive interface.
* **Nginx Caching Control**: Purge nginx caching on post update, edit, or delete events to keep your content up-to-date and your site running smoothly.
* **Redis Object Cache**: Enhance your site performance by purging redis object cache when needed.
* **Best Practices & Security**: FlyWP implements best practices for server management and ensures a secure environment for your WordPress sites.
* **Cron Management**: Automate and manage cron jobs for your WordPress sites directly through FlyWP.
* **User-Friendly Interface**: Designed to be accessible for non-technical users, FlyWP makes it easy to manage your WordPress server and sites.

== Installation ==

1. Upload the `flywp` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the 'FlyWP' settings page.
4. Configure your caching settings and other preferences.

== Frequently Asked Questions ==

= Does this plugin work with any hosting provider? =

This plugin is specifically designed to work with FlyWP, a cloud server management platform that supports DigitalOcean, AWS, Vultr, Linode, and Google Cloud.

= Is this plugin suitable for non-technical users? =

Yes, FlyWP and the plugin are designed to be user-friendly and accessible for non-technical users. You can easily manage your WordPress server and sites without any technical expertise.

= How does this plugin enhance my site's performance? =

The plugin allows you to control and purge nginx caching and redis object cache, ensuring that your content stays up-to-date and your site runs smoothly.

== Screenshots ==

1. FlyWP settings page.
2. Cloud server management dashboard.
3. Caching settings and controls.

== Changelog ==

= v0.2.1 (14 June, 2023) = 

 * **Fix:** API should respond to authenticated requests only, it was bypassed during development.

= v0.2.0 (13 June, 2023) = 

 * **New:** Added plugin update API.
 * **New:** Added Nginx purge caching UI.

= v0.1 (5 May, 2023) =
 
 * Initial release.

== Upgrade Notice ==

= 0.1 =
This is the initial release of the plugin.


