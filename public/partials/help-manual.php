<?php require 'help-headline.php'; ?>
<p>
	Here you can compare how your page looks before and after updates at any time. We will show you the exact
	difference between the versions.
</p>

<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Step by step</h3>
	<div class="accordion-content">
		<ol>
			<li>
				Add the URL’s you want to check for differences. You can add multiple from any domain. Check the boxes for desktop
				and/or mobile and make sure you enabled the Group setting.
			</li>
			<li>
				Click the button "Take reference screenshot" to create your reference images. All following change detections
				will be compared against this screenshot.
			</li>
			<li>
				You can check the Log to see the status. If there are many screenshots to take, it might take few
				minutes.
			</li>
			<li>
				When you have done your updates on the website, it is time to check for differences. Click the button "Create
				change detections" to take screenshots again and compare with your reference.
			</li>
			<li>
				Go to "Change Detections", and click "Show" to display the changes. There you see the both screenshots
				which you can compare with the slider. Next to the screenshots you see the change detection image
				with all differences highlighted.
			</li>
			<li>
				If you made some updates after the last change detection, click "Create change detections" again.
				All new change detections will continue to compare against the reference screenshot until you
				take new ones.
				<br><br>
					For example, if your website update broke part of your CSS, you can continue to run new change detections
					until you have fixed the issue, and there is no longer a visual difference between the versions.

			</li>
		</ol>


	</div>
</div>
<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Groups & URLs</h3>
	<div class="accordion-content">
		<p>Your URL’s are organized in groups.</p>
		<p>
			<strong>URLs</strong><br>
			Click <?php echo get_device_icon( 'add-url', 'white' ); ?> to add a new page.
			Choose the screen sizes you want to create the change detections for
			(desktop and mobile). Once the URL was added to the group, you can enable / disable the screensizes
			in the URL list. You can always go back and assign your URL’s to groups or remove them later.
		</p>

		<p>
			<strong>Groups</strong><br>
			Groups are where you organize your URL’s. This could be a group of essential pages from one website,
			similar pages from different sites, or simply using other settings in different groups of the same pages.
			When creating a change detection, you simply enable the groups you want to check and click the
			"Take Reference Screenshots" or "Create Change Detections" button.
		</p>
	</div>
</div>

<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>URL settings</h3>
	<div class="accordion-content">
		<p>
			<strong><?php echo get_device_icon( 'desktop', 'white' ); ?>Desktop /
				<?php echo get_device_icon( 'mobile', 'white' ); ?>mobile</strong><br>
			For each URL, you can choose which devices you want to enable change detection for.
		</p>
		<p>
			<strong>CSS</strong><br>
			You can inject CSS before we take the screenshots. This allows you to hide a cookie banner or any
			dynamic content, which might change by individual page visits.
		</p>
		<p>
			<strong>Remove from group</strong><br>
			When removing an URL from a group, the settings for CSS and selected devices will be deleted.
			The URL will still be available in other groups and can be added back again at any point.
		</p>
		<p>If you want to delete an URL permanently, you can do so in
			<a href="/webchangedetector/?tab=manage-urls">Manage URLs</a>. Note that change detections are
			not available for deleted URL’s.
		</p>

	</div>
</div>

<div class="accordion help">
	<h3>
		<span class="dashicons dashicons-arrow-right-alt2"></span>Group settings
	</h3>
	<div class="accordion-content">
		<p>
			<strong>Enable / disable group</strong><br>
			Enable or disable a group by clicking the slider button next to the group name.
			The screenshots will be only taken for URL’s in enabled groups.
		</p>
		<p>
			<strong>Assign URLs</strong><br>
			Every new URL you add can be assigned to a group. Just click the button
			<span class="dashicons dashicons-welcome-add-page"></span> and you can assign any URL’s
			which are not yet assigned to this group.
		</p>
		<p>
			<strong>Change group name</strong><br>
			In the group settings <span class="dashicons dashicons-admin-generic"></span>you can change the name of
			the group and delete it. Note that change detections will not be available after deleting a group, and the
			URL settings like CSS will also be removed.
		</p>
	</div>
</div>

<div class="accordion help">
	<h3>
		<span class="dashicons dashicons-arrow-right-alt2"></span>Reference Screenshots and Change Detections
	</h3>
	<div class="accordion-content">
		<p>
			<strong>Reference Screenshots</strong><br>
			This screenshot is what we compare the changes against. Before making updates on your site,
			click "Create Reference Screenshot" to create images from the URLs in all enabled groups.
		</p>
		<p>
			<strong>Change Detections </strong><br>
			When you have done updates at your site and want to identify any difference, click
			"Create Change Detections". We will take screenshots again and
			compare them with the reference screenshot. You can see the status of screenshots in the
			<a href="/webchangedetector/?tab=queue">Logs</a>.
		</p>
		<p>
			<strong>Show Change Detections</strong><br>
			You can see all change detections at
			<a href="/webchangedetector/?tab=change-detections">Change Detections</a>.
		</p>
	</div>
</div>
<?php require 'help-contact-form.php'; ?>