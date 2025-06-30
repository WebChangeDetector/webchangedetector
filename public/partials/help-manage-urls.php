<?php require 'help-headline.php'; ?>
<p>
	All available webpage are listed here. You can see to which group the webpage are assigned.
</p>
<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Edit</h3>
	<div class="accordion-content">
		<p>
			Changing an webpage here will change it in all groups where the webpage is assigned to. Please edit an webpage only
			when it changed. After editing an webpage, it will be still compared with reference screenshots before
			changing the webpage.
		</p>
	</div>
</div>
<div class="accordion help">
	<h3><span class="dashicons dashicons-arrow-right-alt2"></span>Delete</h3>
	<div class="accordion-content">
		<p>
			When you delete an webpage, it will be also deleted from all groups.
			Please keep in mind that Change Detections are not available for a deleted webpage anymore.
		</p>
		<p>
			If you want to remove an webpage from a group, you can do so in the group settings. The webpage will be
			still available in other groups after removing them from the group.
		</p>
	</div>
</div>
<?php require 'help-contact-form.php'; ?>