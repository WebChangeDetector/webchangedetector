<?php require 'help-headline.php'; ?>
<p>
	The Monitoring tracks your webpages in intervals and sends you an email to notify you of any changes.
</p>
<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Step by step</h3>
	<div class="accordion-content">
		<ol>
			<li>Add the webpages you want to track at <?php echo get_device_icon( 'add-url', 'white' ); ?></li>
			<li>Select the screen size you want to track the webpage:<br>
				<?php echo get_device_icon( 'desktop', 'white' ); ?>Desktop (with: 1920px)<br>
				<?php echo get_device_icon( 'mobile', 'white' ); ?>Mobile (with: 375px)<br>
			</li>
			<li>Select the interval (1h up to 24h) you want to track the webpages in the group settings
			<?php echo get_device_icon( 'settings', 'white' ); ?></li>
		</ol>

		<p>
			To get started, select the webpages, which interval to check, the time to start the interval, and which email
			address to send alerts to when a change is detected. Note that you can always create another group with
			different settings, with the same or other webpages.
		</p>
		<p>
			Now we will automatically detect changes according to your settings, and if we notice a difference, you will
			receive an email with a link to the compared versions.
		</p>

		<p>
			You can always see your change detections in "Show Change Detections".
		</p>

	</div>
</div>

<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Groups & Webpages</h3>
	<div class="accordion-content">
		<p>Your webpages are organized in groups.</p>
		<p>
			<strong>Webpages</strong><br>
			Click <?php echo get_device_icon( 'add-url', 'white' ); ?> to add a new webpage.
			Here you can assign the webpage to groups and enable it to be tracked
			on desktop and mobile screen sizes. You can always go back and assign your webpages to groups or remove them later.
		</p>

		<p>
			<strong>Groups</strong><br>
			Groups are where you organize your webpages. This could be a group of essential pages from one website,
			similar pages from different sites, or simply using other settings in different groups of the same pages.
		</p>
	</div>
</div>

<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Webpage settings</h3>
	<div class="accordion-content">
		<p>
			<strong><?php echo get_device_icon( 'desktop', 'white' ); ?>Desktop /
				<?php echo get_device_icon( 'mobile', 'white' ); ?>mobile</strong><br>
			For each webpage, you can choose which devices you want to enable change detection for.
		</p>
		<p>
			<strong>CSS</strong><br>
			You can inject CSS before we take the screenshots. This allows you to hide a cookie banner or any
			dynamic content, which might change by individual page visits.
		</p>
		<p>
			<strong>Remove from group</strong><br>
			When removing an webpage from a group, the settings for CSS and selected devices will be deleted.
			The webpage will still be available in other groups and can be added back again at any point.
		</p>
		<p>
			If you want to delete an webpage permanently, you can do so in
			<a href="/webchangedetector/?tab=manage-urls">Manage webpages</a>. Note that change detections are
			not available for deleted webpages.
		</p>
	</div>
</div>

<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Group settings</h3>
	<div class="accordion-content">
		<p><strong>Start / stop monitoring</strong><br>
			You can start and stop by clicking the  button next to the group name.
			The screenshots will be only taken for webpages in enabled groups.
		</p>
		<p><strong>Assign URLs</strong><br>
			Every new URL you add can be assigned to a group. Just click the button
			<span class="dashicons dashicons-welcome-add-page"></span> and you can assign any URL’s
			which are not yet assigned to this group.
		</p>
		<p><strong>Group settings</strong><br>
			In the group settings <span class="dashicons dashicons-admin-generic"></span>you can change the name of
			the group, set monitoring intervals, set the hour to start the monitoring,
			define notification email addresses and delete the group. Note that change detections will not be
			available after deleting a group, and the URL settings like CSS will also be removed.
		</p>
	</div>
</div>
<?php require 'help-contact-form.php'; ?>