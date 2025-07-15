/**
 * WebChange Detector Wizard - Driver.js Implementation
 * Replaces the custom wizard system with a modern, accessible driver.js tour
 */

(function ($) {
    'use strict';

    // Ensure driver.js is loaded
    if (typeof window.driver === 'undefined' || typeof window.driver.js === 'undefined' || typeof window.driver.js.driver === 'undefined') {
        console.error('Driver.js is not loaded. Please ensure driver.js.iife.js is included.');
        return;
    }

    /**
     * WebChange Detector Wizard Class
     */
    class WCDWizard {
        constructor() {
            this.driver = null;
            this.currentStep = 0;
            this.steps = [];
            this.isActive = false;

            // Bind methods
            this.init = this.init.bind(this);
            this.startWizard = this.startWizard.bind(this);
            this.stopWizard = this.stopWizard.bind(this);
            this.createSteps = this.createSteps.bind(this);
        }

        /**
         * Initialize the wizard system
         */
        init() {
            // Check if we're on a WebChangeDetector admin page
            if (!this.isWCDAdminPage()) {
                return;
            }

            // Create driver instance
            this.createDriver();

            // Set up event listeners
            this.setupEventListeners();

            // Auto-start wizard if parameter is present
            if (this.shouldAutoStart()) {
                this.forceStartWizard();
            }
        }

        /**
         * Check if we're on a WebChangeDetector admin page
         */
        isWCDAdminPage() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page');
            return page && page.includes('webchangedetector');
        }

        /**
 * Check if wizard should auto-start
 */
        shouldAutoStart() {
            // Don't auto-start if wizard was just completed
            if (sessionStorage.getItem('wcd-wizard-completed') === 'true') {
                return false;
            }

            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('wcd-wizard') === 'true';
        }

        /**
         * Force start wizard (ensures it starts even if there are timing issues)
         */
        forceStartWizard() {
            // Wait for DOM to be fully ready
            const checkAndStart = () => {
                if (document.readyState === 'complete') {
                    setTimeout(() => {
                        this.startWizard();
                    }, 200);
                } else {
                    setTimeout(checkAndStart, 100);
                }
            };
            checkAndStart();
        }

        /**
         * Get the appropriate step index based on current page for cross-page wizard flow
         */
        getCrossPageStepIndex() {
            const currentPage = this.getCurrentPage();

            // If coming from wizard parameter, start from beginning of that page's flow
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('wcd-wizard') === 'true') {
                return 0;
            }

            return 0;
        }

        /**
         * Create the driver.js instance
         */
        createDriver() {
            this.driver = window.driver.js.driver({
                showProgress: true,
                animate: true,
                allowClose: true,
                overlayClickBehavior: 'close',
                overlayOpacity: 0.7,
                smoothScroll: true,
                stagePadding: 10,
                stageRadius: 5,
                popoverOffset: 10,
                showButtons: ['next', 'previous', 'close'],
                nextBtnText: 'Next →',
                prevBtnText: '← Previous',
                doneBtnText: 'Finish Wizard',
                closeBtnText: 'Exit Wizard',
                progressText: 'Step {{current}} of {{total}}',
                popoverClass: 'wcd-wizard-popover',

                // Global event handlers
                onDestroyed: () => {
                    this.isActive = false;
                    this.onWizardEnd();
                },

                onCloseClick: () => {
                    this.stopWizard();
                    return true; // Allow the close action
                },

                onDeselected: (element, step, options) => {
                    // Optional: Handle step deselection
                },

                onHighlighted: (element, step, options) => {
                    // Optional: Handle step highlighting
                }
            });
        }

        /**
         * Create steps based on current page
         */
        createSteps() {
            const currentPage = this.getCurrentPage();

            switch (currentPage) {
                case 'webchangedetector':
                    return this.getDashboardSteps();
                case 'webchangedetector-update-settings':
                    return this.getUrlSelectionSteps();
                case 'webchangedetector-auto-settings':
                    return this.getMonitoringSteps();
                case 'webchangedetector-change-detections':
                    return this.getChangeDetectionSteps();
                case 'webchangedetector-logs':
                    return this.getLogsSteps();
                case 'webchangedetector-settings':
                    return this.getSettingsSteps();
                default:
                    return this.getGenericSteps();
            }
        }

        /**
         * Get current page identifier
         */
        getCurrentPage() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 'webchangedetector';
        }

        /**
         * Navigate to next wizard page
         */
        navigateToPage(page) {
            // Show loading state
            if (this.driver && this.isActive) {
                this.driver.destroy();
            }

            // Add a brief loading message
            const loadingDiv = document.createElement('div');
            loadingDiv.innerHTML = `
                <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                           background: #266FCB; color: white; padding: 20px; border-radius: 5px; 
                           z-index: 10001; box-shadow: 0 0 20px rgba(0,0,0,0.3);">
                    <h3 style="margin: 0 0 10px 0; color: white;">Let’s continue on the next page.</h3>
                    <div style="text-align: center;">Loading...</div>
                </div>
            `;
            document.body.appendChild(loadingDiv);

            // Navigate after a brief delay
            setTimeout(() => {
                window.location.href = `?page=${page}&wcd-wizard=true`;
            }, 800);
        }

        /**
         * Complete the wizard and navigate back to dashboard
         */
        completeWizard() {
            // Mark wizard as completed in session storage to prevent auto-restart
            sessionStorage.setItem('wcd-wizard-completed', 'true');

            // Show completion message
            const loadingDiv = document.createElement('div');
            loadingDiv.innerHTML = `
                <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                           background: #266FCB; color: white; padding: 20px; border-radius: 5px; 
                           z-index: 10001; box-shadow: 0 0 20px rgba(0,0,0,0.3);">
                    <h3 style="margin: 0 0 10px 0; color: white;">Wizard Complete!</h3>
                    <div style="text-align: center;">Returning to dashboard...</div>
                </div>
            `;
            document.body.appendChild(loadingDiv);

            // Stop current wizard
            if (this.driver && this.isActive) {
                this.driver.destroy();
            }
            this.isActive = false;

            // Send AJAX request to disable wizard
            if (typeof wcdWizardData !== 'undefined' && wcdWizardData.ajax_url) {
                $.post(wcdWizardData.ajax_url, {
                    action: 'wcd_disable_wizard',
                    nonce: wcdWizardData.nonce
                });
            }

            // Navigate back to dashboard WITHOUT wizard parameter
            setTimeout(() => {
                window.location.href = '?page=webchangedetector';
            }, 1500);
        }

        /**
         * Dashboard wizard steps
         */
        getDashboardSteps() {
            return [
                {
                    popover: {
                        title: 'Welcome to WebChange Detector',
                        description: 'WebChange Detector monitors your WordPress site for visual changes. It takes screenshots, compares them, and alerts you to any differences. Let\'s set up your monitoring!',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .box-half.credit',
                    popover: {
                        title: 'Your Check Credits',
                        description: 'This shows your available checks and current usage. Monitor your usage to stay within limits. You will see warnings if the estimated amount of checks is higher than your credits.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .wizard-dashboard-latest-change-detections',
                    popover: {
                        title: 'Recent Changes',
                        description: 'Your latest detected changes appear here. You\'ll see visual comparisons highlighting what changed on your site.',
                        side: 'top',
                        align: 'start',
                        nextBtnText: 'Next →',
                        onNextClick: () => {
                            // Navigate to URL selection page with wizard parameter
                            this.navigateToPage('webchangedetector-update-settings');
                        }
                    }
                }
            ];
        }

        /**
         * URL selection wizard steps
         */
        getUrlSelectionSteps() {
            return [
                {
                    element: '.webchangedetector .wcd-settings-card',
                    popover: {
                        title: 'Manual Checks & Auto Update Settings',
                        description: 'You can start the Manual Checks here. But first, let\'s walk through each important setting.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-auto-update-setting-enabled',
                    popover: {
                        title: 'Enable Auto Update Checks',
                        description: 'Please turn this ON to enable automatic checks during WordPress auto-updates. This is required to continue the wizard. You can always turn it off later if you don\'t want to use it.',
                        side: 'right',
                        align: 'center',
                        onNextClick: (element, step, options) => {
                            // Check if auto update checks are enabled
                            const autoUpdateCheckbox = document.querySelector('input[name="auto_update_checks_enabled"]');
                            console.log(autoUpdateCheckbox);
                            console.log(autoUpdateCheckbox.checked);

                            if (!autoUpdateCheckbox || !autoUpdateCheckbox.checked) {
                                // Create a more user-friendly notification
                                window.WCDWizard.showRequiredSettingNotification('Auto Update Checks');

                                autoUpdateCheckbox.focus();
                                return; // Don't proceed if validation fails
                            }

                            // Validation passed - proceed to next step
                            options.driver.moveNext();
                        }
                    }
                },
                {
                    element: '.wcd-form-row.wcd-auto-update-setting-enabled-auto-updates',
                    popover: {
                        title: 'Enabled Auto Updates',
                        description: 'Here you see a list of all enabled auto updates. Enable or disable the auto updates in the WordPress settings.',
                        side: 'right',
                        align: 'center'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-auto-update-setting-from',
                    popover: {
                        title: 'Auto Update Timeframe',
                        description: 'Set the time window when WordPress is allowed to perform auto-updates. WebChange Detector will check your site during this period. For example: 2:00 AM - 4:00 AM when traffic is low.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-auto-update-setting-weekday',
                    popover: {
                        title: 'Weekday Selection',
                        description: 'Choose which days WordPress can perform auto-updates. Many prefer weekdays to avoid weekend issues, or specific days when support is available.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-auto-update-setting-emails',
                    popover: {
                        title: 'Notification Emails',
                        description: 'Enter email addresses to receive notifications about auto-update check results. You can add multiple emails separated by commas.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-auto-update-setting-threshold',
                    popover: {
                        title: 'Change Detection Threshold',
                        description: 'Set the sensitivity for detecting changes (0-100%). Note: even small changes like 0.1% can be significant on long pages.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-auto-update-setting-css',
                    popover: {
                        title: 'CSS Injection',
                        description: 'Add custom CSS to hide dynamic elements before screenshots (like dates, counters, ads). Example: .dynamic-date { display: none !important; }',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .group_urls_container table',
                    popover: {
                        title: 'URL Selection Table',
                        description: 'Select which pages to monitor. Toggle Desktop/Mobile options for each URL. Pro tip: Start with your most important pages like homepage, contact, and key product pages.',
                        side: 'top',
                        align: 'center'
                    }
                },
                {
                    element: '.webchangedetector input[type="submit"][value="Save Settings"]',
                    popover: {
                        title: 'Save Your Settings',
                        description: 'Don\'t forget to save! Your settings will be applied to both manual checks and auto-update monitoring.',
                        side: 'top',
                        align: 'center',
                        onNextClick: () => {
                            // Navigate to monitoring settings page with wizard parameter
                            this.navigateToPage('webchangedetector-auto-settings');
                        }
                    }
                }
            ];
        }

        /**
         * Monitoring settings wizard steps
         */
        getMonitoringSteps() {
            return [
                {
                    element: '.webchangedetector .wcd-settings-card',
                    popover: {
                        title: 'Automatic Monitoring Settings',
                        description: 'Set up automatic monitoring to regularly check your website for unexpected changes. This is perfect for detecting hacks, broken layouts, or content issues. ',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-monitoring-enabled',
                    popover: {
                        title: 'Enable Monitoring',
                        description: 'Please turn this ON to activate automatic monitoring. This is required to continue the wizard. Your selected pages will be checked regularly based on your schedule settings.',
                        side: 'left',
                        align: 'start',
                        onNextClick: (element, step, options) => {
                            // Check if monitoring is enabled
                            const monitoringCheckbox = document.querySelector('input[name="enabled"]');
                            if (!monitoringCheckbox || !monitoringCheckbox.checked) {
                                // Create a more user-friendly notification
                                window.WCDWizard.showRequiredSettingNotification('Monitoring');

                                monitoringCheckbox.focus();
                                return; // Don't proceed if validation fails
                            }

                            // Validation passed - proceed to next step
                            options.driver.moveNext();
                        }
                    }
                },
                {
                    element: '.wcd-form-row.wcd-monitoring-interval',
                    popover: {
                        title: 'Check Frequency',
                        description: 'How often should we check your site? Daily (24h) is recommended for most sites. High-traffic sites may want more frequent checks.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-monitoring-hour-of-day',
                    popover: {
                        title: 'Preferred Check Time',
                        description: 'Choose when checks should run. Pick a low-traffic time like 3 AM to minimize impact on visitors.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-monitoring-threshold',
                    popover: {
                        title: 'Change Sensitivity',
                        description: 'Set how sensitive the monitoring should be. Note: even 0.1% changes can be significant on long pages.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-monitoring-alert-emails',
                    popover: {
                        title: 'Alert Recipients',
                        description: 'Who should be notified when changes are detected? Add multiple emails separated by commas. Include your developer and key stakeholders.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row.wcd-monitoring-css',
                    popover: {
                        title: 'CSS Customization',
                        description: 'Hide dynamic content that changes frequently (timestamps, visitor counters, etc.) to avoid false positives in monitoring.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector input[type="submit"][value="Save Settings"]',
                    popover: {
                        title: 'Save Monitoring Settings',
                        description: 'Save your configuration to activate monitoring. Changes take effect immediately.',
                        side: 'top',
                        align: 'center'
                    }
                },
                {
                    element: '.webchangedetector .group_urls_container',
                    popover: {
                        title: 'Select Pages to Monitor',
                        description: 'Choose which pages to monitor automatically. Select your most critical pages - homepage, checkout, contact forms, and high-traffic content.',
                        side: 'top',
                        align: 'start',
                        onNextClick: () => {
                            // Navigate to change detections page with wizard parameter
                            this.navigateToPage('webchangedetector-change-detections');
                        }
                    }
                }
            ];
        }

        /**
         * Change detection wizard steps
         */
        getChangeDetectionSteps() {
            return [
                {
                    element: '.webchangedetector .wizard-change-detections',
                    popover: {
                        title: 'Change Detection History',
                        description: 'This is your change detection hub. View all detected changes with visual comparisons showing exactly what changed, when, and by how much.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .wizard-change-detections table',
                    popover: {
                        title: 'Detection Table',
                        description: 'Each row shows a detected change. Click on any row to see before/after screenshots with differences highlighted. The filters above help you find specific changes.',
                        side: 'top',
                        align: 'center'
                    }
                },
                {
                    element: '.webchangedetector .wizard-change-detections form',
                    popover: {
                        title: 'Filter Options',
                        description: 'Use these filters to find specific changes by date, check type, status, or to show only changes with differences.',
                        side: 'bottom',
                        align: 'start',
                        onNextClick: () => {
                            // Navigate to logs page with wizard parameter
                            this.navigateToPage('webchangedetector-logs');
                        }
                    }
                }
            ];
        }

        /**
         * Logs wizard steps
         */
        getLogsSteps() {
            return [
                {
                    element: '.webchangedetector .wizard-logs',
                    popover: {
                        title: 'Activity Logs',
                        description: 'Track all WebChange Detector activities - scheduled checks, manual checks, API calls, and system events. Essential for troubleshooting.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .wizard-logs table',
                    popover: {
                        title: 'Log Details',
                        description: 'Each entry shows: timestamp, action type, status (success/error), and details. Green entries show successful operations, red indicates errors.',
                        side: 'top',
                        align: 'center',
                        onNextClick: () => {
                            // Navigate to settings page with wizard parameter
                            this.navigateToPage('webchangedetector-settings');
                        }
                    }
                }
            ];
        }

        /**
         * Settings wizard steps
         */
        getSettingsSteps() {
            return [
                {
                    element: '.webchangedetector .wcd-settings-card',
                    popover: {
                        title: 'URL Management',
                        description: 'Control which content types appear in your URL list. Add custom post types, taxonomies, or WooCommerce products for monitoring.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-form-row:has(button[onclick*="sync_urls"])',
                    popover: {
                        title: 'URL Synchronization',
                        description: 'WebChange Detector syncs your site\'s URLs automatically. Use "Sync Now" after adding new content or if URLs are missing.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-settings-card:has(input[name="wcd_disable_admin_bar_menu"])',
                    popover: {
                        title: 'Quick Access',
                        description: 'The admin bar menu provides quick access to WebChange Detector from your site\'s frontend. Disable if you prefer a cleaner toolbar.',
                        side: 'top',
                        align: 'start'
                    }
                },
                {
                    element: '.wcd-settings-section-api-token',
                    popover: {
                        title: 'API Connection',
                        description: 'Your API token connects this site to WebChange Detector\'s screenshot service. Keep it secret and secure!',
                        side: 'top',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector',
                    popover: {
                        title: 'Setup Complete!',
                        description: 'You\'re all set! WebChange Detector is now monitoring your site. Check the dashboard for updates and configure additional settings as needed.',
                        side: 'bottom',
                        align: 'start',
                        doneBtnText: 'Finish Tour →',
                        nextBtnText: 'Finish Tour →',
                        onNextClick: () => {
                            // Complete the wizard and navigate back to dashboard without wizard parameter
                            this.completeWizard();
                        }
                    }
                }
            ];
        }

        /**
         * Generic steps for other pages
         */
        getGenericSteps() {
            return [
                {
                    element: '.webchangedetector',
                    popover: {
                        title: 'WebChange Detector',
                        description: 'Welcome to WebChange Detector! Use the navigation tabs to access different features.',
                        side: 'bottom',
                        align: 'start'
                    }
                }
            ];
        }

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Listen for wizard start button clicks
            $(document).on('click', 'input[value="Start Wizard"]', (e) => {
                e.preventDefault();
                this.startWizard();
            });

            // Legacy wizard exit buttons have been removed

            // Handle wizard parameter in URL
            if (window.location.search.includes('wcd-wizard=true')) {
                // Remove the parameter from URL without page reload (after starting)
                setTimeout(() => {
                    const url = new URL(window.location);
                    url.searchParams.delete('wcd-wizard');
                    window.history.replaceState({}, document.title, url);
                }, 2000);
            }
        }

        /**
         * Start the wizard
         */
        startWizard() {
            if (this.isActive) {
                return;
            }

            // Clear completion flag when manually starting wizard
            sessionStorage.removeItem('wcd-wizard-completed');

            this.isActive = true;
            this.steps = this.createSteps();

            if (this.steps.length === 0) {
                console.warn('No wizard steps found for current page');
                return;
            }


            // Update driver configuration with steps
            this.driver.setSteps(this.steps);

            // Start the tour
            this.driver.drive();
        }

        /**
         * Stop the wizard
         */
        stopWizard() {
            if (this.driver && this.isActive) {
                this.driver.destroy();
            }
            this.isActive = false;

            // Send AJAX request to disable wizard
            if (typeof wcdWizardData !== 'undefined' && wcdWizardData.ajax_url) {
                $.post(wcdWizardData.ajax_url, {
                    action: 'wcd_disable_wizard',
                    nonce: wcdWizardData.nonce
                });
            }
        }

        /**
 * Handle wizard end
 */
        onWizardEnd() {
            // Send AJAX request to disable wizard
            if (typeof wcdWizardData !== 'undefined' && wcdWizardData.ajax_url) {
                $.post(wcdWizardData.ajax_url, {
                    action: 'wcd_disable_wizard',
                    nonce: wcdWizardData.nonce
                });
            }

        }

        /**
         * Navigate to next step (public method)
         */
        nextStep() {
            if (this.driver && this.isActive) {
                this.driver.moveNext();
            }
        }

        /**
         * Navigate to previous step (public method)
         */
        previousStep() {
            if (this.driver && this.isActive) {
                this.driver.movePrevious();
            }
        }

        /**
         * Navigate to specific step (public method)
         */
        goToStep(stepIndex) {
            if (this.driver && this.isActive) {
                this.driver.moveTo(stepIndex);
            }
        }

        /**
         * Check if wizard is currently active
         */
        isWizardActive() {
            return this.isActive;
        }

        /**
         * Show a notification for required settings
         */
        showRequiredSettingNotification(settingName) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #266FCB;
                color: white;
                padding: 20px 30px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 1000000001;
                text-align: center;
                max-width: 400px;
            `;
            notification.innerHTML = `
                <h3 style="margin: 0 0 10px 0; color: white;">Required Setting</h3>
                <p style="margin: 0 0 15px 0;">Please enable <strong>${settingName}</strong> to continue with the wizard. <br>You can disable this after finishing the wizard again.</p>
            `;
            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 2000);
        }
    }

    // Global instance
    window.WCDWizard = new WCDWizard();

    // Initialize when document is ready
    $(document).ready(function () {
        window.WCDWizard.init();

        // Additional fallback for wizard auto-start (only if not completed)
        if (window.location.search.includes('wcd-wizard=true') &&
            sessionStorage.getItem('wcd-wizard-completed') !== 'true') {
            setTimeout(() => {
                if (!window.WCDWizard.isWizardActive()) {
                    console.log('Fallback: Starting wizard after page load');
                    window.WCDWizard.startWizard();
                }
            }, 1500);
        }
    });

    // Expose public methods globally for backward compatibility
    window.wcdStartWizard = () => window.WCDWizard.startWizard();
    window.wcdStopWizard = () => window.WCDWizard.stopWizard();

})(jQuery);
