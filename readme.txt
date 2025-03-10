=== FlyWP Helper - Page Cache, Page Optimization, Emails for FlyWP Server Control Panel ===
Contributors: flywp, tareq1988
Tags: OPcache, Optimize, cache, page cache, performance
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.4.0
Requires PHP: 7.1 or higher
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize WordPress performance with server-level caching, Redis purging, and page speed tools for FlyWP-powered cloud servers.

== Description ==

FlyWP is a modern, Docker-based server control panel designed specifically for WordPress. It simplifies managing your WordPress servers on various cloud platforms, such as DigitalOcean, AWS, Vultr, Linode, and Google Cloud. FlyWP ensures best practices, security, caching, and cron for every server you create, allowing even non-technical users to manage their own WordPress servers and sites.

[FlyWP](https://flywp.com) control panel is built with performance, security, and automation in mind. It applies best practices from the very beginning to ensure your sites run efficiently with maximum page speed. Enjoy site deployment, management, security, and optimization facilities even without having any technical expertise.

👉 Have a quick overview
[youtube https://www.youtube.com/watch?v=cKP5cfbx2f8] 

The FlyWP WordPress plugin allows you to communicate with your server control panel, control and purge nginx caching on post update, edit or delete events, and provides the ability to purge redis object cache. The plugin enhances the overall performance of your WordPress site by leveraging the power of FlyWP's cloud server management platform.

**FlyWP Helper** plugin enables direct communication with the FlyWP server control panel. It ensures smooth site operations by handling page cache clearing and optimizations whenever the content is updated. It also provides control over Nginx caching on post-update and edit and allows you to purge the Redis object cache when required. By integrating with FlyWP’s cloud server management platform, this plugin enhances WordPress sites' page speed, reliability, and efficiency.
**Note**: To use this plugin properly, you must set up your cloud servers and host websites with FlyWP.


### Useful Links
[Docs](https://flywp.com/docs/) | [Videos](https://www.youtube.com/channel/UC1-e7ewkKB1Dao1U90QFQFA) | [Contact Support](https://flywp.com/contact/) | [Feature Request](https://feedback.flywp.com/) | [Facebook Group](https://www.facebook.com/groups/flywp) | [Get Started](https://flywp.com/pricing/)

## FlyWP Helper Plugin Features
Here are all the features listed for FlyWP Helper plugin: 

**Admin Customization**
-   Remove WordPress Logo – Removes the WordPress logo from the admin bar.
-   Show Login Logo – Displays the site logo on the login page.
-   Remove Dashboard Widgets – Removes default dashboard widgets from WordPress admin.
**Site Header Customization**
-   Remove Feed Links – Removes RSS and Atom feed links from the site header.
-   Remove RSD Link – Removes the RSD (Really Simple Discovery) link from the site header.
-   Remove WP Version Number – Hides the WordPress version from the site header for security.
-   Remove REST API Links – Removes REST API links from the site header.
-   Remove Shortlink – Removes shortlink tags from the site header.
-   Remove oEmbed Discovery Links – Removes oEmbed discovery links from the site header.
**WordPress Optimizations**
-   Enable WordPress Optimizations – Toggles all optimization settings.
-   Disable Emojis – Removes extra JavaScript that supports emojis in older browsers.
-   Disable Embeds – Prevents others from embedding the site.
-   Disable Self-Pingbacks – Disables self-pingbacks within the site.
-   Disable Comments – Option to disable comments on the site (not enabled in the screenshot).
-   Disable jQuery Migrate – Removes jQuery Migrate from the frontend and admin panel.
-   Clean Navigation Menu – Removes unnecessary classes from the navigation menu.
-   Disable RSS Feed – Disables the RSS and Atom feed on the site (not enabled in the screenshot).
-   Disable XML-RPC – Disables XML-RPC for security improvements.


FlyWP Helper delivers powerful server-side caching (for FlyWP-hosted servers), site speed optimization, and other tweaks while using any VPS or cloud servers with FlyWP.

## Why FlyWP?

FlyWP takes care of server provisioning, caching, security, database optimization, and migrations, so you can focus on growing your business instead of bearing server maintenance hassles.

## Features

### 1. [Host WordPress Site on Any Cloud Server](https://flywp.com/)
FlyWP works with AWS, DigitalOcean, Linode, Vultr, Google Cloud, Hetzner, and any VPS or cloud server provider. If your server has an IP, you can connect it to FlyWP.

### 2. Docker-Based Control Panel for WordPress Sites
Unlike traditional panels, FlyWP leverages Docker containers for site isolation and streamlined management. This provides:
- Improved security (each site runs in its own container).
- Efficient resource allocation.

### 3. [Powerful Clone and Migration Across Servers](https://flywp.com/features/powerful-clone-and-transfer/)
FlyWP makes WordPress site clone and migration very easy:
- Clone sites or Migrate sites across different servers without manual hiccups.
- Migration or Clone progress visual will be shown while you do the operation.
- Zero-downtime migration ensures users experience no interruptions.

### 4. [Optimized Website Stack](https://flywp.com/features/nginx-litespeed-flywp/)
Being a modern cloud control panel, FlyWP provides you the latest versions of:
- Nginx (scalable, high-performance web server)
- OpenLiteSpeed (optimized for super fast WordPress sites)
- PHP (multiple versions supported)
- MySQL Database (fully optimized)
- Redis (object caching for fast queries)

### 5. High-Performance Caching Support
FlyWP provides built-in multiple page caching mechanism to enhance WordPress site and server performance:
PHP OPcache – Speeds up script execution by storing precompiled PHP code in memory.
Memcache – Reduces database load by storing frequently accessed data in memory.
[Redis Object Cache](https://flywp.com/features/redis-caching/) – Optimizes database performance by caching queries and objects.
LiteSpeed Cache – Delivers high-speed page caching for improved site speed.
[FastCGI Cache](https://flywp.com/features/fastcgi-caching/) - Improves PHP performance by keeping processes running persistently. It reduces load times and server resource usage.
Auto Cache Purge – Automatically clears the cache on content updates.

### 6. [Built-in 7G Firewall](https://flywp.com/features/built-in-security/)
Security is big concern for your online presence. FlyWP includes the 7G Firewall, which:
- Blocks spam, malware, and bad bots.
- Prevents SQL injection (SQLi) and XSS attacks.
- Enhances DDoS protection, applies to your hosting.

### 7. [Automated Website Backups](https://flywp.com/features/intelligent-backup-restore/)
FlyWP allows you to store your website backups on the providers you trust. You can enjoy scheduled backups of:
- Files & database (media, themes, plugins).
- Cloud storage support (Amazon S3, DigitalOcean Spaces, Google Cloud Storage, Wasabi, Backblaze B2, or any S3-based).
- Easily restore your sites from the site backup list when you need.

### 8. [Full WordPress Multisite Support](https://flywp.com/features/wordpress-multisite/)
FlyWP supports WordPress Multisite (both subdirectory and subdomain configurations), with:
- Network-wide cache optimization.
- Efficient multisite database handling.
- Seamless site cloning and domain mapping.

### 9. Free SSL Certificates
FlyWP integrates with Let’s Encrypt for [automatic SSL/TLS certificates](https://flywp.com/docs/site/custom-ssl-management/) with auto-renewal.


### 10. [Git Push-to-Deploy](https://flywp.com/features/git-deployment/)
Developers can push code to GitHub, Bitbucket, or private Git repositories, and FlyWP will deploy to server and make changes accordingly.

### 11. Team Management & Collaboration
We know how important your privacy is when it comes to controlling your server and sites. With FlyWP you can share [granular team permission](https://flywp.com/features/granular-team-permission/): 
- Create team accounts with role-based access.
- Control permissions for adding sites, managing servers, and modifying configurations.

### 12. Error Logs & Debugging
FlyWP enables secure WordPress error logging and stores logs efficiently without affecting performance.

### 13. [Periodically Security Updates](https://flywp.com/changelog/)
FlyWP applies seamless security patches to protect your servers from vulnerabilities and threats.
- Firewall auto-configuration for enhanced security.
- Fail2Ban protection against brute-force attacks.
- SSH hardening (key-based authentication only).
- Regular software updates to prevent exploits.

### 14. [WordPress Magic Login](https://flywp.com/features/wordpress-magic-login/)
Admins can securely log into WordPress without needing passwords, using one-click authentication from the FlyWP dashboard.

### 15. Scheduled Cron Jobs
FlyWP offers easy [Cron job management](https://flywp.com/docs/server/how-to-create-cron-job-with-flywp/) for your WordPress sites. Additionally, server-side cron jobs replace unreliable WP-cron.

### 16. [WP-CLI Preinstalled](https://flywp.com/docs/server/wp-cli/.)
FlyWP includes WP-CLI for easy WordPress management via the command line.

### 17. Site Isolation for Security
Each WordPress site has its own system user, preventing malware from spreading between sites. As our control panel is Docker based, you get the best possible security for your sites.

### 18. [SSH Access](https://flywp.com/features/ssh/)
- Secure user management (clients only access their own site files).

### 19. Flexible Email Integration
FlyWP simplifies WordPress email sending with your preferred [SMTP configuration](https://flywp.com/docs/site/email/) from multiple providers. (e.g. Postmark, Mailgun, Sendgrid, or any custom SMTP)


### 20. [MySQL Binlog Optimization](https://flywp.com/docs/server/how-to-manage-binary-logs-in-flywp/)
For high-performance databases, FlyWP enables binary logging (binlog) optimization, which:
- Improves replication performance for large-scale sites.
- Reduces disk I/O overhead.
- Ensures efficient point-in-time recovery.


## See, What People Are Talking About Us 👇
**SAAS Master** review on FlyWP: launch lightning-fast WP sites with your server on FlyWP
[youtube https://www.youtube.com/watch?v=lnU9A08DsUw&pp=ygUFZmx5d3A%3D]

**SiteCrafter** review on FlyWP: was surprised with the helpful and easy UI
[youtube https://www.youtube.com/watch?v=mlGuqKFvLwQ&pp=ygUFZmx5d3A%3D]

**Suburbia Press** said FlyWP is a GREAT WordPress Server Control Panel
[youtube https://www.youtube.com/watch?v=n5tND8CaWkM&t=16s&pp=ygUFZmx5d3A%3D] 


###Can’t Find Your Desired Feature? 
Fee free to [Submit ideas here.](https://feedback.flywp.com/)

## Support

For help and documentation, visit: [FlyWP Documentation](https://flywp.com/docs)
Support: [support@flywp.com](mailto:support@flywp.com)

<strong>Connect With FlyWP Team And Community 🌐</strong>
<ul>
<li>Facebook Group: <a href="https://www.facebook.com/groups/flywp" target="_blank" rel="">FlyWP Community</a></li>
<li>Facebook: <a href="https://www.facebook.com/flywpcom" target="_blank" rel="">FlyWP</a></li>
<li>Twitter / X: <a href="https://twitter.com/FlyWPOfficial" target="_blank" rel="">FlyWP</a></li>
<li>Youtube: <a href="https://www.youtube.com/@flywp" target="_blank" rel="">FlyWP</a></li>
<li>LinkedIn: <a href="https://www.linkedin.com/company/flywp/" target="_blank" rel="">FlyWP</a></li>
</ul>

### Show Your Love
If you are pleased with our product, please delight us by giving [5***** rating](https://wordpress.org/support/plugin/flywp/reviews/).


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

= Do I need a FlyWP account to use this plugin? =

Yes, this plugin is designed specifically for FlyWP-managed WordPress sites.

= Does FlyWP support WordPress Multisite and custom server setups? =

Yes! FlyWP allows multisite installations, custom VPS setups, and server configurations, and it supports features like Let’s Encrypt auto SSL, Redis caching, and full SSH access.

= What makes FlyWP better than other server management platforms? =

FlyWP offers a user-friendly interface, powerful automation, Docker-based isolation, and extensive security features that many users find superior to other platforms.

= Can I use FlyWP on different cloud hosting providers like AWS, DigitalOcean, or Hetzner? =

Yes! FlyWP supports AWS, DigitalOcean, Vultr, Hetzner, Linode, Google Cloud, and any other VPS providers. Our users are currently managing various cloud servers to host their WordPress sites.

= How does FlyWP improve WordPress site speed and performance? =

FlyWP optimizes caching, includes OPcache and Redis support, provides both Nginx and OpenLiteSpeed stack support, and isolates sites using Docker to ensure high performance even without requiring any advanced setup.

= How can I back up and restore my WordPress site? =

FlyWP provides automated site backups in your preferred backup providers (e.g., Wasabi, Backblaze B2, Google Drive, or any S3-based) and a smart restore feature to recover your site instantly.

= Does FlyWP support ARM-based servers? =

Yes! FlyWP supports ARM-based servers (e.g., Hetzner ARM servers) as an open choice for cost-effective hosting environments.

= How does OPcache work in FlyWP? =

OPcache in FlyWP boosts performance by caching precompiled PHP scripts in memory, reducing load times. It’s enabled by default but customizable.

= Do you have other products? =

FlyWP is a product of weDevs. We have very popular products like Dokan Multivendor, WP User Frontend Pro, Happy Addons, WP Project Manager Pro, weDocs, weMail, wePos, Appsero, WP ERP, InboxWP, StoreGrowth, etc.

== Screenshots ==

1. FlyWP settings page.

== Changelog ==

= v1.4.0 (18 September, 2024) =

 * **New:** Periodic updates for WP theme, plugin, and core update data to FlyWP dashboard.

= v1.3.1 (18 July, 2024) =

 * **Fix:** Filename error.

= v1.3 (18 July, 2024) =

 * **New:** Added support for OpenLiteSpeed.

= v1.2 (25 June, 2024) =

 * **New:** Added optimization settings panel.

= v1.1 (24 May, 2024) =

 * **Improved:** Magic login wasn't working when a site was migrated due to username mismatch. Now it'll work nevertheless.

= v1.0 (06 February, 2024) =

 * **New:** Added email settings tab.
 * **New:** Added health check API endpoint.

= v0.4.3 (24 January, 2024) =

 * **Fix:** Cache toggle from FlyWP application.

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


