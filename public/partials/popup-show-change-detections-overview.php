<div id="change_detection_overview_popup" class="ajax-popup" style="display: none;">
	<div class="popup ">
		<div class="popup-inner" id="change-detections-overview">
			<h2>Change Detections<br>
			<small>for webpage: <span id="change-detection-url"></span></small></h2>

			<button onclick="closeChangeDetectionOverviewPopup()" class="et_pb_button close-popup-button">X<small>ESC</small></button>
			<div id="loading-change-detections" style="text-align: center; width: 100%;">
				<img src="<?php echo '/wp-content/plugins/app/public/img/loader.gif'; ?>">
			</div>
			<div id="change-detections-by-url-id"></div>
		</div>
	</div>
</div>

<style>
	#change_detection_overview_popup .latest_compares_content table td:first-child,
	#change_detection_overview_popup .latest_compares_content table th:first-child{
		display: none;
	}
</style>