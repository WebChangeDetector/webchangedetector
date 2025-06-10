# WebChangeDetector Wizard Migration to Driver.js

## Overview

The WebChangeDetector plugin wizard has been migrated from a custom CSS/jQuery-based system to use the modern **driver.js** library for guided tours. This provides better accessibility, smoother animations, and a more professional user experience.

## Files Modified

### 1. `/admin/js/wizard.js` (NEW)
- Complete rewrite using driver.js
- Modular class-based architecture
- Support for multiple page flows
- Automatic step detection based on current page
- AJAX integration for wizard state management

### 2. `/admin/class-webchangedetector-admin.php`
- Added driver.js and wizard.js to `enqueue_scripts()`
- Added driver.css to `enqueue_styles()`
- Added AJAX handler `ajax_disable_wizard()`
- Added script localization for AJAX data

### 3. `/includes/class-webchangedetector.php`
- Registered AJAX action `wp_ajax_wcd_disable_wizard`

### 4. `/admin/css/webchangedetector-admin.css`
- Added custom styling for driver.js popover
- Maintains WebChangeDetector brand colors (#01A0D2)
- Added styles to hide legacy wizard elements

## Wizard Flow Mapping

### Dashboard (webchangedetector)
1. **Welcome** - `.webchangedetector .box-plain`
2. **Account Info** - `.webchangedetector .box-half.credit`
3. **Change Detections** - Dynamic h2 selector

### URL Selection (webchangedetector-update-settings)
1. **URL Settings Header** - `.webchangedetector .wcd-frm-settings h2`
2. **URL Table** - `.webchangedetector .group_urls_container table`
3. **Manual Checks Button** - `form[method="post"] input[value="Start manual checks >"]`

### Monitoring (webchangedetector-auto-settings)
1. **Settings Form** - `.webchangedetector .wcd-frm-settings`
2. **URL Container** - `.webchangedetector .group_urls_container`
3. **Save Button** - `input[type="submit"][value*="Save"]`

### Change Detections (webchangedetector-change-detections)
1. **Section Overview** - `.webchangedetector .wcd-section`

## Key Features

### Automatic Detection
- Detects WebChangeDetector admin pages
- Auto-starts on `?wcd-wizard=true` parameter
- Detects legacy wizard elements for compatibility

### Event Handling
- Listens for "Start Wizard" button clicks
- Handles "Exit wizard" button clicks
- Cleans up URL parameters automatically

### AJAX Integration
- Disables wizard via AJAX when completed
- Maintains nonce security
- Graceful fallback if AJAX fails

### Backward Compatibility
- Legacy wizard elements remain functional
- Public API methods exposed globally:
  - `window.wcdStartWizard()`
  - `window.wcdStopWizard()`

## Configuration

### Driver.js Settings
```javascript
{
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
    popoverClass: 'wcd-wizard-popover'
}
```

### Custom Styling
- Blue theme matching WebChangeDetector branding
- Custom popover header with brand color
- Consistent button styling
- Professional shadows and borders

## Migration Benefits

1. **Better UX**: Smooth animations and transitions
2. **Accessibility**: Built-in keyboard navigation and screen reader support
3. **Mobile Responsive**: Works well on all devices
4. **Maintainable**: Less custom CSS and JavaScript to maintain
5. **Modern**: Uses industry-standard guided tour library
6. **Performance**: Optimized rendering and DOM manipulation

## Usage

The wizard automatically initializes when:
1. User visits a WebChangeDetector admin page
2. URL contains `?wcd-wizard=true` parameter
3. Legacy wizard element `#wizard_dashboard_welcome` exists

Users can:
- Start wizard manually via "Start Wizard" button
- Navigate with Next/Previous buttons
- Exit at any time with close button or overlay click
- Resume from any step if interrupted

## Dependencies

- **jQuery** (already included in WordPress)
- **driver.js** (v4.x IIFE version included)
- **driver.css** (included)

## Browser Support

Driver.js supports all modern browsers:
- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 16+
- IE 11+ (with polyfills) 