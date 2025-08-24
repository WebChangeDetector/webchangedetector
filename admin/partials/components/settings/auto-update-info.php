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
 *
 * @var string $css_class    Optional CSS classes
 */

$css_class = $css_class ?? '';

// Determine core auto-update status.
$core_auto_update_enabled = false;
if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
	// Constant overrides site option.
	$wp_auto_update_core = constant( 'WP_AUTO_UPDATE_CORE' );
	if ( true === $wp_auto_update_core || in_array( $wp_auto_update_core, array( 'minor', 'beta', 'rc', 'development', 'branch-development' ), true ) ) {
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
$summary  = 'Core: ' . ( $core_auto_update_enabled ? '✓' : '✗' );
$summary .= ' | Plugins: ' . count( $plugins_auto_update ) . ' / ' . count( $all_plugins );
$summary .= ' | Themes: ' . count( $themes_auto_update ) . ' / ' . count( $all_themes );

// Build content.
ob_start();
?>
<div style="margin-bottom: 20px;">
	<h4 style="margin-bottom: 10px;">WordPress Core</h4>
	<div style="display: flex; align-items: center; margin-bottom: 5px;">
		<span style="color: <?php echo $core_auto_update_enabled ? '#46b450' : '#dc3232'; ?>; margin-right: 8px; font-size: 16px;">
			<?php echo $core_auto_update_enabled ? '✓' : '✗'; ?>
		</span>
		<span>WordPress Core</span>
	</div>
</div>

<div style="margin-bottom: 20px;">
	<h4 style="margin-bottom: 10px;">Plugins</h4>
	<?php
	foreach ( $all_plugins as $plugin_file => $plugin_data ) :
		$is_auto_update = is_array( $auto_update_plugins ) && in_array( $plugin_file, $auto_update_plugins, true );
		?>
		<div style="display: flex; align-items: center; margin-bottom: 5px;">
			<span style="color: <?php echo $is_auto_update ? '#46b450' : '#dc3232'; ?>; margin-right: 8px; font-size: 16px;">
				<?php echo $is_auto_update ? '✓' : '✗'; ?>
			</span>
			<span><?php echo esc_html( $plugin_data['Name'] ); ?> (<?php echo esc_html( $plugin_data['Version'] ); ?>)</span>
		</div>
	<?php endforeach; ?>
</div>

<div style="margin-bottom: 20px;">
	<h4 style="margin-bottom: 10px;">Themes</h4>
	<?php
	foreach ( $all_themes as $theme_slug => $theme_obj ) :
		$is_auto_update = is_array( $auto_update_themes ) && in_array( $theme_slug, $auto_update_themes, true );
		?>
		<div style="display: flex; align-items: center; margin-bottom: 5px;">
			<span style="color: <?php echo $is_auto_update ? '#46b450' : '#dc3232'; ?>; margin-right: 8px; font-size: 16px;">
				<?php echo $is_auto_update ? '✓' : '✗'; ?>
			</span>
			<span><?php echo esc_html( $theme_obj->get( 'Name' ) ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
<div>
<?php
$content = ob_get_clean();

// Include accordion component.
$header_text  = __( 'Currently enabled auto-updates: ', 'webchangedetector' ) . $summary;
$accordion_id = 'auto-update-info';
$open         = false;

require dirname( __DIR__ ) . '/ui-elements/accordion.php';
?>
</div>