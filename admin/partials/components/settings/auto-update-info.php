<?php
/**
 * Auto Update Information Component
 *
 * Displays current WordPress auto-update settings in an accordion.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/partials/components
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Expected variables:
 * @var string $css_class    Optional CSS classes
 */

$css_class = $css_class ?? '';

// Determine core auto-update status.
$core_auto_update_enabled = false;
if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
	// Constant overrides site option.
	$wp_auto_update_core = constant( 'WP_AUTO_UPDATE_CORE' );
	if ( $wp_auto_update_core === true || in_array( $wp_auto_update_core, array( 'minor', 'beta', 'rc', 'development', 'branch-development' ), true ) ) {
		$core_auto_update_enabled = true;
	}
} else {
	// Check site option if constant is not defined.
	$core_update_setting = get_site_option( 'auto_update_core_major' );
	if ( 'enabled' === $core_update_setting ) {
		$core_auto_update_enabled = true;
	}
}

// Get auto-update plugins.
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$all_plugins         = get_plugins();
$plugins_auto_update = array();
$auto_update_plugins = get_site_option( 'auto_update_plugins' );
foreach ( $all_plugins as $plugin_file => $plugin_data ) {
	if ( is_array( $auto_update_plugins ) && in_array( $plugin_file, $auto_update_plugins, true ) ) {
		$plugins_auto_update[ $plugin_file ] = $plugin_data;
	}
}

// Get auto-update themes.
$all_themes         = wp_get_themes();
$themes_auto_update = array();
$auto_update_themes = get_site_option( 'auto_update_themes' );
foreach ( $all_themes as $theme_slug => $theme_obj ) {
	if ( is_array( $auto_update_themes ) && in_array( $theme_slug, $auto_update_themes, true ) ) {
		$themes_auto_update[ $theme_slug ] = $theme_obj;
	}
}

// Build summary.
$summary  = 'Core: ' . ( $core_auto_update_enabled ? 'Enabled' : 'Disabled' );
$summary .= ' | Plugins: ' . count( $plugins_auto_update );
$summary .= ' | Themes: ' . count( $themes_auto_update );

// Build content.
ob_start();
?>
<h4>WordPress Core</h4>
<p><?php echo $core_auto_update_enabled ? 'Core auto-update is enabled.' : 'Core auto-update is disabled.'; ?></p>

<h4>Plugins</h4>
<?php if ( ! empty( $plugins_auto_update ) ) : ?>
	<ul>
		<?php foreach ( $plugins_auto_update as $plugin_file => $plugin_data ) : ?>
			<li><?php echo esc_html( $plugin_data['Name'] ); ?> (<?php echo esc_html( $plugin_data['Version'] ); ?>)</li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p>No plugins with auto-update enabled.</p>
<?php endif; ?>

<h4>Themes</h4>
<?php if ( ! empty( $themes_auto_update ) ) : ?>
	<ul>
		<?php foreach ( $themes_auto_update as $theme_slug => $theme_obj ) : ?>
			<li><?php echo esc_html( $theme_obj->get( 'Name' ) ); ?></li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p>No themes with auto-update enabled.</p>
<?php endif; ?>
<?php
$content = ob_get_clean();

// Include accordion component.
$header_text = 'Currently enabled auto-updates: ' . $summary;
$accordion_id = 'auto-update-info';
$open = false;

include dirname( __DIR__ ) . '/ui-elements/accordion.php';
?> 