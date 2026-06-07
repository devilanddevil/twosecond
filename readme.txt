=== TwoSecond ===
Contributors: twosecond
Tags: performance, cache, optimization, pagespeed, web-vitals
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Boost your WordPress site speed with advanced AI optimization techniques.

== Description ==

Boost your WordPress site speed with advanced AI optimization techniques. Improve Core Web Vitals, achieve higher scores on Google PageSpeed Insights and GTmetrix, and deliver a faster, smoother browsing experience for your visitors.

== Screenshots ==

1. **AI Optimization Dashboard** – Automatically optimize pages using AI-powered performance analysis and track optimization progress for each URL.

2. **HTML Cache Settings** – Configure page caching, cache expiry time, preload options, GZIP compression, and browser caching to improve website speed.

3. **General Settings Panel** – Activate your TwoSecond license and enable global optimization features such as query parameter optimization and logged-in user optimization.

4. **CDN Integration Settings** – Connect your CDN by adding the CDN URL, selecting file types to serve via CDN, and excluding specific paths.

5. **Image Optimization Settings** – Configure image performance options including WebP conversion for JPG/PNG, lazy loading for images, iframes, video, and audio, SVG handling, and responsive images. This screen helps you reduce image payload and improve page load speed with a single click on Save Changes.

6. **CSS Optimization Settings** – Manage CSS performance options including minification, combination, and deferred loading of stylesheets, as well as advanced rules for excluding or including specific CSS files to reduce render‑blocking resources and speed up page rendering.

7. **JavaScript Optimization Settings** – Configure JavaScript performance options including minification, combination, and deferred or delayed loading of scripts, with granular controls to exclude specific files and prevent conflicts while reducing render‑blocking resources.

8. **Exclusions Settings** – Define URLs, file types, cookies, and user agents that should be excluded from caching and optimization, giving you precise control to avoid conflicts with sensitive pages such as checkout, cart, and logged‑in user areas.

9. **Clear Cache Tools** – Quickly purge all cached files or selectively clear cache for specific URLs, post types, and device types to instantly reflect changes on your site without waiting for cache expiry.

10. **Web Vitals Logs & Reports** – Monitor Core Web Vitals and performance scores over time with detailed logs for each URL, helping you identify slow pages, track optimization impact, and prioritize fixes based on real user experience data.

11. **Import & Export Settings** – Easily migrate TwoSecond configuration between websites by exporting all optimization settings to a file and importing them on another install, ensuring consistent performance tuning across multiple projects without manual reconfiguration.

12. **Change Logs & Activity History** – Review a chronological log of cache purges, optimization actions, and settings changes, making it easy to audit who changed what and to troubleshoot performance or compatibility issues.

13. **Quick Cache Clear Menu (Admin Bar)** – Access one‑click options from the WordPress admin header to clear all cache or purge specific sections, letting you instantly refresh optimized content while working anywhere in the dashboard or on the front end.

== Features ==

* CSS Optimization – Minification, combination, and critical CSS loading
* JavaScript Optimization – Minification, combination, and deferred loading
* HTML Caching – Page-level caching for improved performance
* Image Optimization – WebP conversion and lazy loading
* CDN Support – Content Delivery Network integration
* Lazy Loading – Images, iframes, and videos
* Asset Preloading – Preload critical assets like images and fonts for faster rendering
* Admin Bar Integration – Quick cache purge from admin bar
* Multisite Support – Network-wide and individual site management
* Web Vitals Monitoring – Core Web Vitals tracking and logging
* Change Logging – Track all settings changes

== Free vs Premium Features ==

TwoSecond offers a powerful free version with essential performance optimizations. A premium license unlocks advanced AI-powered optimization features for the entire website.

= Free Version (Included with Plugin) =

The following features are available for free and work on all pages:

* HTML page caching
* CSS minification
* JavaScript minification
* JavaScript lazy loading
* Image lazy loading
* Browser caching
* GZIP compression
* Cache management tools
* Multisite compatibility
* Critical CSS generation (Home Page Only)
* Advanced image optimization and WebP conversion (Home Page Only)

These optimizations help significantly improve page load speed and overall performance without requiring a license key.

= Premium Version (Requires License Key) =

The premium version unlocks advanced AI-powered optimization features for all pages:

* HTML page caching
* CSS minification
* JavaScript minification
* JavaScript lazy loading
* Image lazy loading
* Browser caching
* GZIP compression
* Cache management tools
* Multisite compatibility
* Critical CSS generation (All Pages)
* Advanced image optimization and WebP conversion (All Pages)

Without a license key, **critical CSS and advanced image optimization are available only for the homepage**.

= Why Premium Features Require a License =

Some advanced optimizations rely on TwoSecond cloud services to process CSS, images, and performance data. These services require additional server resources, so a license key is required to enable them for the entire website.

You can activate your license key from:

TwoSecond → General Settings → License Key

== Requirements ==

* WordPress 5.0 or higher
* PHP 7.4 or higher
* PHP XML extension
* PHP GD extension

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install via the WordPress admin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **TwoSecond** in the admin menu to configure settings.

== Plugin Architecture ==

The plugin follows WordPress coding standards and modular architecture.

= Main Files =

* `twosecond.php` – Main plugin file with header and initialization
* `includes/Plugin.php` – Main plugin class orchestrating functionality
* `includes/Core.php` – Core functionality and settings management
* `includes/Frontend.php` – Frontend optimization handling
* `includes/Database.php` – Database operations and table management

= Admin Structure =

* `admin/Admin.php` – Main admin class handling menu and settings
* `admin/Admin_Bar.php` – Admin bar cache purge functionality
* `admin/admin-page.php` – Admin page template

== Configuration ==

= General Settings =

* Enable Optimization – Turn on/off optimization features
* License Key – Enter your TwoSecond license key

= CSS Optimization =

* CSS Optimization – Enable CSS minification and combination
* Load Critical CSS – Inline critical CSS for above-the-fold content

= JavaScript Optimization =

* JavaScript Optimization – Enable JS minification and combination
* Load Combined JS – Choose when to load combined JavaScript files

= HTML Caching =

* HTML Caching – Enable page-level caching
* Cache Expiry Time – Set cache expiration time in seconds

= Image Optimization =

* Convert JPG to WebP – Automatically convert JPG images to WebP
* Convert PNG to WebP – Automatically convert PNG images to WebP
* Lazy Loading – Enable lazy loading for images

== Usage ==

= Basic Setup =

1. Activate the plugin.
2. Go to TwoSecond settings.
3. Configure optimization preferences.
4. Save settings.

= Cache Management =

* Purge cache directly from the WordPress admin bar.
* CSS/JS cache can be purged globally or per page.
* HTML cache can be purged globally or per page.
* Critical CSS cache can be purged per page.

= Monitoring =

* View Web Vitals logs for performance insights.
* Review change logs for settings updates.
* Monitor cache sizes and performance.

== Hooks ==

= Actions =

* `twosecond_init` – Plugin initialization
* `twosecond_settings_saved` – Settings saved
* `twosecond_cache_cleared` – Cache cleared

= Filters =

* `twosecond_settings` – Modify plugin settings
* `twosecond_cache_path` – Customize cache paths
* `twosecond_exclude_urls` – Exclude URLs from optimization

== Database Tables ==

The plugin creates the following tables:

* `wp_twosecond_core_web_vitals` – Stores Web Vitals performance data
* `wp_twosecond_change_logs` – Stores settings change logs
* `wp_twosecond_site_urls` – Stores site URL management data

== Cache Structure ==

wp-content/cache/
├── critical-css/
└── ts-cache/

== Performance Impact ==

* CSS/JS Optimization – Reduces file sizes by 20-40%
* HTML Caching – Improves page load times by 50-80%
* Image Optimization – Reduces image sizes by 25-50%
* Lazy Loading – Improves initial page load performance

== Common Issues ==

1. Permission errors – Ensure cache directory permissions are correct.
2. Cache not working – Verify optimization settings are enabled.
3. Images not converting – Confirm the GD extension is installed.
4. Admin bar missing – Check user capability settings.

== Security ==

* Input sanitization for all settings
* Nonce verification for forms
* Capability checks for admin actions
* Secure file operations

== Multisite Support ==

* Network-wide settings management
* Individual site overrides
* Centralized cache control
* Site-specific optimization rules

== External Services ==

This plugin connects to the TwoSecond API service:

https://rest.twosecond.ai

These services provide CSS optimization, image optimization, and license verification.

= Data Sent =

* CSS file URLs and content for optimization
* Image URLs for WebP conversion
* License key for verification

= Service Information =

Service Provider: https://twosecond.ai
Support Policy: https://twosecond.ai/support-policy/
Terms of Service: https://twosecond.ai/term-of-service/

All communication happens over secure HTTPS connections.

== Minified Assets ==

Some JavaScript files are distributed in minified form for performance.

Third-party libraries:

* assets/js/web-vitals.iife.js – https://github.com/GoogleChrome/web-vitals
* assets/js/diff.min.js – https://github.com/kpdecker/jsdiff

Plugin-specific scripts:

* assets/js/img-lazyload.js – https://twosecond.ai/resources/js/img-lazyload.js
* assets/js/script-load.min.js – https://twosecond.ai/resources/js/script-load.js

== Frequently Asked Questions ==

= Does this plugin work with multisite? =

Yes. TwoSecond supports both network-wide and per-site configuration.

= Does it require an external service? =

Yes. Some optimization features use the TwoSecond API for processing.

= Do I need a license key to use TwoSecond? =

No. TwoSecond works without a license key. You can still use most optimization features with the built-in demo license included in the plugin.

= What features work without a license key? =

The following features work on all pages using the built-in demo license:

* HTML caching
* Image lazy loading
* JavaScript lazy loading
* CSS minification
* JavaScript minification

These optimizations help improve site speed even without activating a full license.

= What features require a license key? =

Some advanced AI optimization features require an active license key:

* Critical CSS generation
* Advanced image optimization (WebP conversion via TwoSecond API)

Without a license key, these advanced optimizations only work for the **homepage** as a demo.

= Can I optimize my entire website without a license? =

Yes, basic optimizations like caching, minification, and lazy loading work across all pages without a license. However, advanced AI features such as **critical CSS generation and image optimization** require a valid license to work on all pages.

= Why are some features limited without a license? =

Advanced optimizations like critical CSS extraction and image processing use TwoSecond cloud services. These services require server resources, so they are limited in the free demo mode.

= How do I activate my license key? =

1. Go to **TwoSecond → General Settings** in your WordPress admin panel.
2. Enter your license key.
3. Click **Activate** to enable full optimization features.

== Screenshots ==

1. TwoSecond dashboard
2. Optimization settings
3. Cache management panel

== Changelog ==

= 1.0.5 =

* Initial stable release
* CSS and JavaScript optimization
* HTML caching system
* Web Vitals monitoring
* Multisite support

== Upgrade Notice ==

= 1.0.5 =
Initial stable release of TwoSecond optimization plugin.

= 1.0.6 =

* Minor Bug Fixes

= 1.0.7 =

* Minor Bug Fixes

= 2.0.0 =

* Implement Hosting Cache Clear Functionality in plugin and optimze plugin functionality