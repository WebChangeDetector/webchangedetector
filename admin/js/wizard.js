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
            if (typeof wcdWizardData !== 'undefined') {
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
                        description: 'This wizard will help you get started with your website checks. You can exit the wizard any time and restart it from the dashboard.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .box-half.credit',
                    popover: {
                        title: 'Your Account',
                        description: 'See how many checks you have left and how many checks are used with your current settings until renewal.',
                        side: 'left',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .wizard-dashboard-latest-change-detections',
                    popover: {
                        title: 'Change Detections',
                        description: 'Your latest change detections will appear here. But first, let\'s do some checks and create some change detections.',
                        side: 'top',
                        align: 'start',
                        nextBtnText: 'Next →',
                        onNextClick: (element, step, options) => {
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
                    element: '.webchangedetector .wcd-frm-settings',
                    popover: {
                        title: 'Settings',
                        description: 'Here you can configure the WP Auto Update Check settings and some settings for the manual checks, too. <br><br>Remember to enable the WP auto-updates for everything you want to update. <br><br>Don\'t forget to save the settings.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .group_urls_container table',
                    popover: {
                        title: 'URL Selection Table',
                        description: 'Use the toggles to select which URLs to check on desktop and mobile devices. The toggle selections are automatically saved for the manual checks and for the auto update checks.',
                        side: 'top',
                        align: 'center'
                    }
                },
                {
                    element: '.webchangedetector .wizard-start-manual-checks',
                    popover: {
                        title: 'Start Manual Checks',
                        description: 'When you want to do updates or other changes and check your selected websites, start the wizard here. The wizard guides you through the process.',
                        side: 'left',
                        align: 'start',
                        nextBtnText: 'Next →',
                        onNextClick: (element, step, options) => {
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
                    element: '.webchangedetector .wcd-frm-settings',
                    popover: {
                        title: 'Monitoring Settings',
                        description: 'Configure your automatic monitoring settings here. This determines how often your website will be checked for changes.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '.webchangedetector .wizard-save-auto-settings',
                    popover: {
                        title: 'Save Settings',
                        description: 'Don\'t forget to save the settings to activate your monitoring configuration.',
                        side: 'left',
                        align: 'start'

                    }
                },
                {
                    element: '.webchangedetector .group_urls_container',
                    popover: {
                        title: 'Select URLs for Monitoring',
                        description: 'All URLs which you select here will be monitored with the settings configured above.',
                        side: 'top',
                        align: 'start',
                        nextBtnText: 'Next →',
                        onNextClick: (element, step, options) => {
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
                        title: 'Change Detections',
                        description: 'In this tab, you can see all your change detections. Here you can review and manage detected changes on your website.',
                        side: 'bottom',
                        align: 'start',
                        nextBtnText: 'Next →',
                        onNextClick: (element, step, options) => {
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
                        title: 'Logs',
                        description: 'The logs section shows you a detailed history of all checks performed on your website. You can track when checks were run and see any issues that occurred.',
                        side: 'bottom',
                        align: 'start',
                        nextBtnText: 'Next →',
                        onNextClick: (element, step, options) => {
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
                    element: '.webchangedetector .wcd-section',
                    popover: {
                        title: 'Settings',
                        description: 'In the settings tab, you can configure advanced options for WebChange Detector, manage your account preferences, and customize how the plugin behaves.',
                        side: 'bottom',
                        align: 'start',
                        doneBtnText: 'Complete Wizard →',
                        nextBtnText: 'Complete Wizard →',
                        onNextClick: (element, step, options) => {
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
            if (typeof wcdWizardData !== 'undefined') {
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
            if (typeof wcdWizardData !== 'undefined') {
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
