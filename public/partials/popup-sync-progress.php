<div id="sync-progress-popup" class="ajax-popup" style="display: none;">
    <div class="popup">
        <div class="popup-inner">

            <h2>URL Synchronization in Progress</h2>
            <button class="et_pb_button close-popup-button" onclick="return closeSyncProgressPopup()">X<small>ESC</small></button>

            <div id="sync-progress-container">
                <div id="sync-progress-bar-container" style="width: 100%; background-color: #f0f0f0; border-radius: 4px; margin: 20px 0;">
                    <div id="sync-progress-bar" style="width: 0%; height: 30px; background-color: #4CAF50; border-radius: 4px; transition: width 0.3s ease;"></div>
                </div>
                <p id="sync-progress-text">Starting synchronization...</p>
                <div id="sync-background-notice" style="background-color: #e7f3ff; padding: 15px; border-radius: 4px; border-left: 4px solid #2196F3; margin-top: 20px;">
                    <strong>Background Processing:</strong> This process continues even if you close this window. You can check back later to see the results.
                </div>
            </div>

        </div>
    </div>
</div> 