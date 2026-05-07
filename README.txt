=== WebChange Detector ===
Contributors: Mike.Miler,Repat
Tags: visual regression, monitoring, auto updates, screenshot, change detection
Requires at least: 5.5
Tested up to: 6.9
Stable tag: 4.3.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Visual regression testing for WordPress: catch broken layouts after auto-updates, plugin installs, or deploys before your visitors do.

== Description ==

## Find visual bugs before your visitors do

Every time WordPress core, a plugin, or a theme updates, something on your site can quietly break. By the time a visitor reports it, you have already lost trust and probably sales.

WebChange Detector takes a screenshot of your pages **before** the change, another **after**, and tells you exactly what is different on desktop and mobile. The AI check ignores moving parts like sliders, carousels, and animations, so you only get alerts that actually matter.

### What is visual regression testing?

Visual regression testing is the practice of taking a screenshot of a web page in a known-good state, then comparing a new screenshot to it after any change like an update, a deploy, a CSS tweak, or a plugin install. Anything that looks different is flagged. 

It is the fastest way to catch layout breakage that traditional testing like PHP errors or broken links cannot see, because the page can render "successfully" while still looking wrong to a human visitor. WebChange Detector brings this practice to WordPress with one-click setup, no headless-browser scripting, and no Selenium or BackstopJS know-how required.

### What you can detect

* Layout breaks after **WordPress updates** (core, plugins, themes)
* Side effects of **installing or activating a new plugin**
* Visual regressions after **deployments, CSS edits, or pagespeed tweaks**
* Silent breakage from **third-party scripts, fonts, or external APIs**
* **Hacks, defacement, or injected ads** during continuous monitoring
* Theme regressions after switching themes or updating a child theme
* Block library or page-builder breakage (Gutenberg, Elementor, Divi, Beaver Builder, Bricks)
* Mobile-only layout issues that you would never see on desktop
* Scheduled monitoring checks from every 15 minutes up to once per month
* Quiet times when monitoring gets skipped to save check credits

### Three ways to detect changes

WebChange Detector gives you three independent check modes. Use one, two, or all three side by side, depending on how you manage your site.

**1. Auto Update Checks: automatic safety net for WordPress auto-updates.**
This mode hooks directly into the WordPress auto-update system. Right before WordPress installs a core, plugin, or theme update automatically, WebChange Detector takes a "before" screenshot of the pages you selected. Right after the native WordPress auto-update finishes, it takes the "after" screenshot. If anything changed visually you get an email with the affected pages highlighted, plus an AI summary of what looks different. This is the mode that lets you actually leave WordPress auto-updates turned on without losing sleep.

**2. Manual Checks: visual diffs on your own schedule.**
Run a check on demand right before and right after any change you make: a deploy, a plugin install, a CSS tweak, a theme switch, a hosting migration. Manual checks are perfect for teams that already have an update workflow in tools like **MainWP, ManageWP, WP Umbrella, InfiniteWP, or WP Remote**. Use those tools to push your updates, and use WebChange Detector to verify visually that nothing broke. The plugin captures the pre-state, you push your changes, you trigger the post-state, and you get a side-by-side diff.

**3. Monitoring: continuous visual surveillance with alerts.**
Schedule recurring checks at fixed intervals. The plugin captures and compares your selected pages on schedule, and emails you the moment a difference appears. Monitoring catches changes that nobody on your team pushed: hacks, defacement, expired SSL, third-party-script breakage, broken CDN assets, accidental edits, server-side issues, theme regressions, vendor outages. It is your "site is silently broken" alarm.

You can mix all three. A typical agency setup: auto-update checks on every client site so nothing breaks silently during automated WordPress updates, monitoring at 24h intervals on the same selection, and manual checks fired from the agency's MainWP or ManageWP dashboard before scheduled maintenance windows.

### Features

* Automatic cookie banner opt-in to load all content which is blocked initially.
* Hiding ads that might cause changes on every check.
* AI classification of whether a change is an actual change or just a moving element.
* Train the AI to recognize intentional changes like A/B tests so specific patterns get ignored.
* Smart shift detection: if your hero section gets taller, we don't flag the entire page below it as 'changed'.
* Check desktop and mobile screen sizes.
* Pausing dynamic content like sliders, carousels, GIFs, videos, etc. before taking screenshots.
* Trigger lazy load elements before starting the visual regression testing.
* Check browser console logs for new errors to detect errors like a broken contact form.
* WPML and Polylang support.
* WP Multisite support.
* Publicly accessible link to a change detection to share with your agency or developer.
* Basic Auth support to check websites behind a password.
* Static IP proxy to whitelist the IP in a firewall which blocks other users.


### Why site owners and agencies pick WebChange Detector

* **Works on staging, password-protected, and firewalled sites.** Basic-auth credentials and an optional static IP for firewall whitelisting come built in. Most visual-regression plugins refuse to run on anything that is not public.
* **Manual checks, auto-update checks, and the API are free.** No paywall on the core workflow.
* **AI-powered noise filtering.** Sliders, counters and other randomized content are filtered out automatically so you stop drowning in false positives.
* **Built for multisite.** Network-activated or per-site, with sub-site allowances so agencies can hand each client a controlled slice of features.
* **WPML and Polylang ready.** Each language version is synced and checked separately.
* **Share results without giving WP access.** Every comparison has a public link you can paste into Slack or send to a designer.
* **Desktop and/or mobile in every check.** Choose which screen size(s) you want to check per site.

### How it works

1. Install the plugin and create a free account from inside WordPress.
2. Pick the pages you want watched (posts, pages, custom post types, taxonomies, custom URLs).
3. Pick your check mode: auto-update, manual, monitoring, or any combination.
4. Get an email alert with the changed pages highlighted. Open the slider view to compare before/after, mark the result Ok / To Fix / False Positive, and share the link with your team.

### Manual QA vs. automated visual regression testing

Most WordPress site owners check their site after an update by clicking through a few pages and trusting their memory. That works until it does not.

A manual click-through covers only the pages you happen to remember, takes 10 to 60 minutes per round, almost never includes the mobile viewport, and depends on your eyes catching pixel shifts that the human visual system is genuinely bad at. The output, when something does break, is usually a panicked Slack message after a customer noticed first.

WebChange Detector checks every URL you selected, every single time, on desktop *and* mobile, in seconds. It runs unattended while you sleep (in monitoring and auto-update modes), and produces a public side-by-side comparison link you can paste straight to a designer or developer.

Manual QA is fine for one-off changes. WebChange Detector pays for itself the first time an auto-update silently breaks a checkout button at 3 a.m.

### Who is WebChange Detector for?

**Site owners and freelancers** who do not want to spend half a Sunday afternoon clicking through 30 pages after the WordPress auto-update ran.

**Agencies and maintenance providers** running MainWP, ManageWP, WP Umbrella, InfiniteWP, or WP Remote across dozens or hundreds of client sites. WebChange Detector slots into your existing update workflow as the visual-verification layer those tools do not provide on their own.

**WooCommerce and shop owners** who cannot afford a broken checkout, cart, or product page after a WooCommerce or payment-gateway update.

**Publishers and news sites** that release content many times a day on top of fragile templates and ad scripts.

**SaaS and lead-gen teams** running landing-page experiments where every CRO test carries layout risk.

**Multilingual sites** running WPML or Polylang where breakage often hides in non-English language versions.

**Multisite networks** where one update can ripple across dozens of sub-sites and a single dashboard view is the only way to stay sane.

### Compatibility

WebChange Detector renders the front-end of your site through a real browser, so anything that renders for a human visitor renders for our screenshot engine. We are tested with:

* **Page builders**: Gutenberg, Elementor, Divi, Beaver Builder, Oxygen, Bricks, GenerateBlocks, Cwicly, Spectra, Stackable.
* **Themes**: Astra, GeneratePress, Kadence, Blocksy, Hello, Divi, Avada, Storefront, and any well-coded theme.
* **WooCommerce**, EDD, MemberPress, LearnDash, BuddyBoss, bbPress, and most WordPress ecosystems.
* **Caching plugins**: WP Rocket, W3 Total Cache, LiteSpeed Cache, WP Super Cache, FlyingPress.
* **Multilingual**: WPML, Polylang.
* **Hosts**: works on shared hosting, VPS, managed (Kinsta, WP Engine, SiteGround, Pressable, Cloudways), and on-premise.

### Trial plan

* **1,000 checks in your first month**
* Use and test all features mentioned above

### Free plan

After the trial, you are automatically switched to our free plan with some restrictions:
* 50 checks per month
* No browser console log errors
* No AI classification of the detected changes

### Premium plans

Need more checks and all features unlocked? See the pricing page at [webchangedetector.com/pricing](https://www.webchangedetector.com/pricing/).

### Privacy and data

Screenshots are taken from our servers only for publicly available sites or sites you give access to via our proxy or basic auth. We never inject anything into your site or load any styles or scripts on the public site. We operate under GDPR.

### About WebChange Detector

WebChange Detector started because we kept finding client sites broken after updates. We first built it for our own web agency. Now we help other agencies catch problems before their clients do.

== Installation ==

1. From your WordPress admin, go to **Plugins > Add New**, search for "WebChange Detector", and click Install.
2. Activate the plugin through the **Plugins** menu.
3. Open **WebChange Detector** in the sidebar and complete the short signup form to create a free account.
4. Click **Activate Account** in the email we send you.
5. Pick the pages you want to monitor and run your first check.

Manual install: upload the `webchangedetector` folder to `/wp-content/plugins/`, then follow steps 2 through 5 above.

== Frequently Asked Questions ==

= What is the difference between auto-update checks, manual checks, and monitoring? =

These are the three check modes the plugin offers. **Auto-update checks** are fired automatically by the WordPress auto-update system: a "before" screenshot right before the update runs, an "after" screenshot right after, and an email if anything looks different. **Manual checks** are run by you on demand, typically right before and after a deploy or update you trigger yourself. **Monitoring** is a recurring schedule that catches changes nobody pushed. You can use one, two, or all three at the same time.

= Can I use it with MainWP, ManageWP, WP Umbrella, or InfiniteWP? =

Yes. WebChange Detector is the visual-verification layer those tools might not provide. Run your updates through whichever dashboard you already use, and let WebChange Detector handle the before/after visual diff. Manual checks can be triggered from the WordPress admin, the webapp or the REST API, so you can also wire them into your own scripts or pipelines.

= Will it work on a staging site or behind a password? =

Yes. WebChange Detector supports HTTP basic auth credentials and an optional static IP proxy that you can whitelist in your firewall.

= How does it differ from manually clicking through pages after an update? =

Manual QA does not scale, you forget pages, and you cannot remember the exact pixel state from yesterday. WebChange Detector compares the rendered page at the pixel level on desktop and mobile, highlights the differences, and emails you only when something actually changed.

= How does the AI know what to ignore? =

The AI is trained to recognize dynamic elements that are designed to change every page load: image sliders, ad slots, randomized testimonials, live counters. You can also create your own per-URL or per-website rules to ignore specific classifications permanently.

= Does it work with WordPress multisite? =

Yes. The plugin supports both network-activated and per-site activation modes. From the network admin you can register sub-sites, set per-site allowances, and run pre/post screenshots across the whole network during auto-updates.

= Does it work with WPML or Polylang? =

Yes. URLs are synced per language and each language version is checked independently.

= Are mobile and desktop both checked? =

Yes. You can select per URL whether to check the desktop and/or mobile viewport.

= Does it work with Elementor, Divi, Gutenberg, or other page builders? =

Yes. We screenshot the rendered front-end, so any builder, theme, or block library is supported by default.

= Can I share a result with a designer or developer who is not in WordPress? =

Yes. Every change detection has a public share link that opens the comparison view in a browser, no login required.

= What does the free plan include? =

1,000 checks in your first month, then 50 free checks every month after. The free plan activates automatically after your trial with some restrictions.

= Can I trigger checks from a deploy pipeline? =

Yes. The REST API covers websites, groups, comparisons, AI rules, and webhooks. 

= What is monitored besides visual changes? =

In addition to layout, the browser console log is checked for errors.

== Screenshots ==

1. Change detection view: see exactly what changed, with differences highlighted on the post-update screenshot.
2. Comparison slider: drag to compare the before and after states pixel by pixel.
3. Dashboard: account overview, recent change detections, and quick actions.
4. Auto-update settings: pick the pages WordPress will screenshot before and after each automatic update.
5. Manual checks: run an on-demand visual diff before a deployment.
6. Monitoring settings: select pages and intervals for continuous monitoring.
7. Change detections list: every result, grouped by source (monitoring, manual, auto-update) and filterable by status.
8. Multisite network admin: manage sub-sites and set per-site allowances from one place.

== Changelog ==
= 4.3.1 =
* Fix: Calendar selector in change detections
* Fix: Show only batches with comparisons in dashboard

= 4.3.0 =
* Feature: Multisite support (network-activated and per-site activation)
* Feature: Sub-site allowance management on the network admin layer
* Fix: Change detection view on mobile devices
* Fix: Translations

= 4.2.2 =
* Feature: AI analysis included in auto-update notification emails
* Fix: Filter behavior in change detections
* Fix: Calculation of available checks

= 4.2.1 =
* Fix: Possible stuck manual checks in rare cases
* Fix: Updated translations

= 4.2.0 =
* Feature: AI analysis for comparisons
* Feature: AI rules for ignoring detected changes in future checks
* Improvement: New change detection view
* Improvement: More details during manual checks
* Improvement: Better filters in change detections

= 4.1.1 =
* Improvement: Move cooldown messages to debug logs
* Fix: Checks were made without updates in some cases

= 4.1.0 =
* Feature: Basic auth support for sites behind authentication
* Feature: Optional static IP proxy for firewall whitelisting
* Feature: Individual delays between screenshots to prevent server overload
* Feature: Advanced monitoring settings for intervals longer than 24h
* Fix: Auto-update cooldown lock was set without actual running updates in some cases

= 4.0.6 =
* Fix: Consider summer/winter time for auto-update checks
* Improvement: Logging for skipped auto-update checks

= 4.0.5 =
* Fix: Update auto-update check times after saving

= 4.0.4 =
* Improvement: Skip creating an external webhook when auto-update checks are disabled

= 4.0.3 =
* Fix: Remove development folders from distribution

= 4.0.2 =
* Improvement: Backup cron for auto-updates when default WP cron is unavailable
* Improvement: Only load JS and CSS when necessary

= 4.0.1 =
* Fix: URL selection from the front-end admin bar
* Fix: Pre-update versions in logs
* Improvement: Countdown to next auto-update considers weekday settings

= 4.0.0 =
* Improvement: New design and code refactor
* Improvement: Optimized URL sync
* Improvement: More robust auto-update check system
* Improvement: URL sync for Polylang and WPML
* Feature: 1,000 checks free for the first month at signup
* Feature: Browser console-log checks
* Feature: Cache clearing on auto-update checks
* Feature: Optional debug logging system
* Feature: Multilingual support
* Feature: Auto-update history
* Feature: Health-check observer

= 3.2.4 =
* Improvement: URL sync via daily cron
* Improvement: JS file loading optimization

= 3.2.3 =
* Fix: In some cases error after signup

= 3.2.2 =
* Fix when creating account

= 3.2.1 =
* Fix in sync

= 3.2.0 =
* Fix: Message about no selected urls
* Fix: Delete of url settings in rare cases
* Improvement: New Wizard
* Feature: Beta updates with Git Updater plugin

= 3.1.8 =
* Fix: Error for new installations
* Fix: Warnings when there are no checks yet
* Fix: Selecting URLs the traditional way
* Fix: Show processing URLs during manual checks

= 3.1.7 =
* Feature: Select urls in frontend from admin bar
* Improvement: External cron for auto-update process
* Improvement: Show currently enabled auto-updates
* Fix: Show only change detections current domain

= 3.1.6 =
* Feature: Diffbar in Change Detection for easier detection where changes are
* Improvement: Load faster jpeg screenshots instead of pngs
* Fix: Missing frontpage in other languages when blog is homepage
* Fix: Cannot see bottom changes if post-screenshot is higher than pre-screenshot

= 3.1.5 =
* Fix: Improved compatibility with webapp
* Fix: Checks at auto-updates in some cases

= 3.1.4 =
* Fix: Error on activate / deactivate plugins when no api token provided

= 3.1.3 =
* Fix: Auto-update weekday settings from webapp were ignored

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
* Testing compatibility for WP 5.7
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

== Upgrade Notice ==

= 4.3.0 =
Adds full WordPress multisite support (network-activated and per-site) plus sub-site allowance management for agencies. Recommended for all users.

= 4.2.0 =
Adds AI analysis and per-URL rules for filtering noise from dynamic elements. Major reduction in false positives.

= 4.1.0 =
Adds basic-auth and static-IP-proxy support so checks now work on staging, password-protected, and firewalled sites.
