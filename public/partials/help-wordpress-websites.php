<?php require 'help-headline.php'; ?>

	<h2>How it works</h2>
<p>
	Here is where you manage all WordPress websites which use your API token. You can restrict access and set limit
	for new added websites and make individual settings for every website.
</p>
<p>
	Your API Token:<br>
	<strong><?php echo mm_api_token(); ?></strong>
</p>
<p>
	You can give your API Token to your client to use it in the plugin for WordPress. This way his website will be
	synchronized with your account.
</p>

<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Setup step by step</h3>
	<div class="accordion-content">
		<ol>
			<li>Download our <a href="<?php echo MM_APP_URL_PRODUCTION; ?>/download-wp-plugin" target="_blank">Plugin for WordPress</a> and install it on your WordPress website</li>
			<li>
				After activating the plugin, it asks you for an API token. Paste your token in the depending field
				and hit the "save" button. This is your API token :
				<br><br><strong><?php echo mm_api_token(); ?></strong><br><br>
			</li>
			<li>
				All published posts and pages will be synchronized now. You can see a new group here with the website name and all
				public posts and webpages
			</li>
			<li>
				All changes in webpages will be synchronized between the website and WebChangeDetector. Also new pages or posts
				will be synchronized automatically.
			</li>
		</ol>
	</div>
</div>

<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Restrictions</h3>
	<div class="accordion-content">
		<p><strong>Enable Limits</strong><br>
			If you want to restrict access in the plugin at the WordPress website, enable the limits. Use the limits when
			you use the plugin on a client website.
		</p>
		<p><strong>Allow selecting webpages for Manual Checks</strong><br>
			When this option is set to "Yes", you or your client can select webpages in the plugin for WordPress for Update
			Change Detection. When the Limits are enabled, the Change Detections can be only started from WebChangeDetector
			and not from the plugin.
		</p>
		<p><strong>Webpage limit for manual detection</strong><br>
			Set the limit for the amount of webpages per device which are allowed to be selected in the plugin on the WordPress
			website.
		</p>
		<p><strong>Allow Monitoring</strong><br>
			You can allow the Monitoring for your client.
		</p>
		<p><strong>Monitoring limit</strong><br>
			When you allow the monitoring on your client website, the change detections will be only performed
			until the limit is reached. This way you have full control over the credits your client is allowed to use from
			your account.
		</p>
	</div>
</div>
<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Defaults for new websites</h3>
	<div class="accordion-content">
		When you or your client uses your API Token in the Plugin, these default settings will be applied after adding
		this website. Once the website is synchronized with your WebChangeDetector account, you can set individual
		restrictions for the website.
	</div>
</div>
<?php require 'help-contact-form.php'; ?>