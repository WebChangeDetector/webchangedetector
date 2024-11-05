=== Web Change Detector ===
Contributors: Mike.Miler,Repat
Tags: vrt, visual regression test, visual check, screenshots, auto-updates, monitoring
Requires at least: 5.2
Tested up to: 6.6.2
Stable tag: 3.1.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
## The #1 Visual Regression Test Tool For WordPress

Check your website visually after **WP auto updates** to see if something broke with visual regression tests. Or monitor your website visually in intervals and get notified about any change.
With WebChange Detector you can find any difference on your website

*   after installing updates,
*   after installing new plugins,
*   after pagespeed optimization,
*   after CSS changes or
*   any other customization on your WordPress website.

See all differences **highlighted in a new screenshot** to easily compare your website before and after the changes.
With our visual regression tests for WordPress you can make sure your website looks like it should, and fix anything which looks wrong, before your visitors see the issues.

### Why You Should Use WebChange Detector
*   **Save trouble** by checkin your website visually for changes after installing updates.
*   **Save time** with checking all pages yourself after some changes.
*   **Don’t miss sales** because of overlooking issues on your site.

### What You Can Do With Visual Regression Tests of WebChange Detector
*   Run a change detection to see changes before and after updates at your website.
*   Set **Monitorings** in intervals which automatically detects changes.
*   Receive **email alerts** for visual regression test results and check the changes.
*   Check **desktop and mobile** screen sizes.
*   **Share the change detection link** with your web designer to fix issues.

### Free Plan Limitations
*   Get **50 checks per month** for the visual regression tests.
*   Monitoring only works in 24h intervals.

### Premium Plans
Get more checks and lower monitoring intervals with our premium plans. You find them at [www.webchangedetector.com/pricing/](https://www.webchangedetector.com/pricing/ "WebChangeDetector Pricing").

== Installation ==

1. Upload 'webchangedetector' to the `/wp-content/plugins/` directory or install it directly from your backend at "Install Plugin"
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a free account by filling the signup form
4. Activate your account by clicking the 'Activate Account' button in the email we sent you
5. Now you can start your visual regression tests

== Changelog ==

= 3.1.2 =
* Fix: Use local auto update settings (for real)

= 3.1.1 =
* Fix: Use local auto update settings

= 3.1.0 =
* Feature: Limitations for agencies
* Improvement: URL sync for large amount of pages
* Improvement: Show failed change detections
* Fix: Upgrade account URL

= 3.0.4 =
* Fix: Error handling

= 3.0.3 =
* Fix: Signup issues

= 3.0.2 =
* Fix: Multiple API token usage

= 3.0.1 =
* Fix: Activate account

= 3.0.0 =
* Feature: Checks on WP auto updates
* Feature: Set status for change detections
* Improvement: Group change detections
* Improvement: Cleaner design
* Improvement: Better URL sync
* Improvement: Filter and search urls to select
* Fix: Calculation of available checks
* Fix: Compatibility with WPML and Polylang
* Fix: Saving of settings with more than 500 pages

= 2.3.3 =
* Show updated pricing calculations
* Fix when API is down

= 2.3.2 =
* Fix: Activating mobile pages

= 2.3.1 =
* Fix: Signup process
* Test: Current WordPress version

= 2.3.0 =
* Improvement: Integration of new account structure
* Improvement: Changed names for screenshot types
* Test: Current WordPress Version
* Test: Current PHP Version

= 2.2.3 =
* Fix: Upgrade Link
* Test: Current WordPress version

= 2.2.2 =
* Feature: Auto Detection Intervals of 15min and 30 min
* Fix: Finish update change detections

= 2.2.1 =
* Fix: Sync post_types

= 2.2.0 =
* Fix: Bulk select in tables
* Improvement: Simplify update detection wizard
* Feature: Sync additional post types and taxonomies

= 2.1.2 =
* Fix: Update Detection not shown

= 2.1.1 =
* Testing  compatibility for WP 5.7
* Filter for urls
* Compatibility fixes for api requests
* Show selected URLs on top

= 2.1.0 =
* Feature: Add threshold setting for change detections
* Fix: Saving settings for auto and update detections

= 2.0.0 =
* Feature: Create account directly from plugin
* Feature: Slider for comparing screenshots
* Feature: Navigate directly through change detections
* Feature: Show browser time for next auto detections
* Feature: Copy url settings to other detection type
* Feature: Compare before and after screenshot with slider
* Feature: Show live status when taking screenshots
* Feature: Pagination for logs
* Feature: Show change detections and screenshots in logs
* Improvement: Available screenshot overview
* Improvement: User experience
* Fix: Error handling when changing domain
* Fix: Skip URL sync for other post types

= 1.1.6 =
* Fix comparison view

= 1.1.5 =
* Fix for new accounts
* Fix link to create free account
* Update readme

= 1.1.4 =
* Validation fixes

= 1.1.3 =
* Remove Plugin Updater
* Prepare for WP Directory

= 1.1.2 =
* Update repository url

= 1.1.1 =
* Fix initial sync of urls

= 1.1.0 =
* New Layout
* New dashboard
* New Change Detection tab
* Help texts
* Directlink to upgrades

= 1.0.8 =
* Redesign selecting urls
* Move Change Detections to seperate tab

= 1.0.7 =
* Switch to new API

= 1.0.6 =
* Improvement: Free account with 50 change detections every month
* Improvement: Only show change detections with differences
* Improvement: Show icons for devices
* Feature: Show queue for currently processing urls and already processed ones
* Feature: Pre- and post-update change detections for less unnecessary compares
* Feature: Filter showing change detections by days

= 1.0.5 =
* Fix: Prepare Plugin for changes in API

= 1.0.4 =
* Fix: Change in API for Amount of selected urls in Update Change Detection

= 1.0.3 =
* Fix: Disabled auto sync after publishing posts. This caused issues on publishing posts.

= 1.0.2 =
* Feature: Order urls by page title

= 1.0.1 =
* Feature: Sync new posts with API

= 1.0.0 =
* Launch Stable Version

== Screenshots ==
1. Show Change Detection: Check the changes before and after updates
2. Dashboard:  Show the latest change detections and account information
3. Auto-update checks: Make all settings for WP auto update checks and select the pages to check
5. Manual Checks: See all Change detections and their change in percent. Set the status for a better overview.
4. Monitoring: Make all settings for Monitoring and select the pages to check
6. Change Detections: Have all change detections in on overview - grouped by monitoring, manual checks or auto update checks
7. Queue: Find all tasks which were done in this overwiew
8. Settings: Additional settings are listed here.
