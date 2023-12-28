=== FlyWP Helper ===
Contributors: flywp, tareq1988
Tags: cache, helper, performance, hosting, opcache, page cache
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.4.2
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

== Changelog ==

= v0.4.2 (28 December, 2023) =

 * **Fix:** Theme REST API had a typo, hence was not working.
 * **Improved:** Added a parameter to forcefully update the theme and plugin list to have the latest changes.

= v0.4.1 (26 December, 2023) =

 * **Fix:** Updated the `Tested up to` version to `6.4`.

= v0.4 (26 December, 2023) =

 * **Fix:** Cache status was not updating in WordPress when a plugin gets updated from FlyWP.
 * **Fix:** Theme REST API `new_version` wasn't returning the updated version number.
 * **Enhancement:** REST API: Refactored plugin update, added support for updating bulk plugins and filter by 'upgrade` status.
 * **New:** REST API - Added ability to update themes.
 * **New:** REST API - Added ability to update WordPress core.

= v0.3.4 (10 December, 2023) =

 * **Fix:** Fix wrong API URL endpoint.
 * **New:** Added REST API endpoint to sync cache toggling from control panel.

= v0.3.3 (05 October, 2023) =

 * **Fix:** Fixed undefined index error for checking opcache.

= v0.3.2 (16 June, 2023) =

 * **Fix:** Removed unused Chart.js library.
 * **Improved:** The admin bar is improved with logo and a settings page. Now it shows on the frontend too, with singular resource clear link.
 * **Improved:** The settings page has moved to the Dashboard menu. It's responsive now and has a logo.

= v0.3.1 (16 June, 2023) =

 * **Fix:** The minified assets were not generated on the last release.

= v0.3 (16 June, 2023) =

 * **Fix:** Fatal error when doing magic login.
 * **New:** PHP OPcache clearing feature from the admin.

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


