<?php
/**
 * Help - manual checks
 *
 *  @package    WebChangeDetector
 *  @author     Mike Miler <mike@wp-mike.com>
 */

/**
 * Require headline
 */
require 'help-headline.php';
?>
	<p>
		Check your website by taking and comparing screenshots before and after updates.
		You can see the exact difference between the screenshots in the Change Detections.
	</p>

<h3>Auto Update Checks</h3>
<p>
	To enable WebChange Detector checks during WordPress auto updates, simply do the following steps:
	<ol>
	<li>
		Enable auto updates in themes, plugins and / or WordPress itself.
	</li>
	<li>
		Enable "Checks at WP auto updates" in the WebChange Detector settings.
	</li>
	<li>
		Select the time window and days of the week in which you want to perform automatic updates.
	</li>
	<li>
		Set the notification email to send an email about the auto update check results.
	</li>
	<li>
		Select the URLs and screen sizes (desktop and / or mobile) you want to check before and after the updates.
	</li>
</ol>
</p>

	<h3>Manual Checks</h3>
	<p>The wizard takes you step by step through the process:</p>
	<ol>
		<li>
			Select the URLs and screen sizes (desktop and / or mobile) you want to check before and after the updates.
		</li>
		<li>
			Click "Start manual checks" and follow instructions to take screenshots before you do updates or other changes.
		</li>
		<li>
			Make updates or other changes on your website.
		</li>
		<li>
			Create the post-update screenshots and compare them to the pre-update screenshots to see differences.
		</li>
		<li>
			Check the Change Detections for differences between the pre- and post-update screenshots.
		</li>
	</ol>
	<p>If something broke during the update, you will see it in the Change Detection. To verify the fix, you can start the
		Change Detection again. The new post-update screenshots will be compared against the pre-update screenshots.
	</p>