<?php
/**
 * Form View Component for WebChangeDetector
 *
 * Handles rendering of forms and form elements.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/views
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Form View Component Class.
 */
class WebChangeDetector_Form_View {

	/**
	 * The admin instance.
	 *
	 * @var WebChangeDetector_Admin
	 */
	private $admin;

	/**
	 * Constructor.
	 *
	 * @param WebChangeDetector_Admin $admin The admin instance.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Render form opening tag with nonce field.
	 *
	 * @param string $action The action value.
	 * @param string $method The form method (post, get).
	 * @param array  $attributes Additional form attributes.
	 */
	public function render_form_start( $action, $method = 'post', $attributes = array() ) {
		$attr_string = '';
		foreach ( $attributes as $key => $value ) {
			$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}
		
		?>
		<form method="<?php echo esc_attr( $method ); ?>"<?php echo $attr_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<input type="hidden" name="wcd_action" value="<?php echo esc_attr( $action ); ?>">
			<?php wp_nonce_field( $action ); ?>
		<?php
	}

	/**
	 * Render form closing tag.
	 */
	public function render_form_end() {
		echo '</form>';
	}

	/**
	 * Render a filter form for change detections.
	 *
	 * @param array $filters Current filter values.
	 */
	public function render_filter_form( $filters = array() ) {
		?>
		<form method="get" style="margin-bottom: 20px;">
			<input type="hidden" name="page" value="webchangedetector-change-detections">

			from <input name="from" value="<?php echo esc_attr( $filters['from'] ?? '' ); ?>" type="date">
			to <input name="to" value="<?php echo esc_attr( $filters['to'] ?? '' ); ?>" type="date">

			<select name="group_type">
				<option value="" <?php selected( empty( $filters['group_type'] ) ); ?>>All Checks</option>
				<option value="post" <?php selected( $filters['group_type'] ?? '', 'post' ); ?>>Manual Checks & Auto Update Checks</option>
				<option value="auto" <?php selected( $filters['group_type'] ?? '', 'auto' ); ?>>Monitoring Checks</option>
			</select>
			
			<select name="status" class="js-dropdown">
				<option value="" <?php selected( empty( $filters['status'] ) ); ?>>All Status</option>
				<option value="new" <?php selected( $filters['status'] ?? '', 'new' ); ?>>New</option>
				<option value="ok" <?php selected( $filters['status'] ?? '', 'ok' ); ?>>Ok</option>
				<option value="to_fix" <?php selected( $filters['status'] ?? '', 'to_fix' ); ?>>To Fix</option>
				<option value="false_positive" <?php selected( $filters['status'] ?? '', 'false_positive' ); ?>>False Positive</option>
			</select>
			
			<select name="difference_only" class="js-dropdown">
				<option value="0" <?php selected( empty( $filters['difference_only'] ) ); ?>>All detections</option>
				<option value="1" <?php selected( $filters['difference_only'] ?? '', '1' ); ?>>With difference</option>
			</select>

			<input class="button" type="submit" value="Filter">
		</form>
		<?php
	}

	/**
	 * Render post type selection form.
	 *
	 * @param array $available_post_types Available post types to add.
	 */
	public function render_post_type_form( $available_post_types ) {
		if ( empty( $available_post_types ) ) {
			echo '<p><i>All available post types are already shown.</i></p>';
			return;
		}

		$this->render_form_start( 'add_post_type', 'post', array( 'style' => 'display: inline-block; margin-right: 10px;' ) );
		?>
		<select name="post_type">
			<?php foreach ( $available_post_types as $post_type ) : ?>
				<?php
				$post_type_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_slug( $post_type );
				$post_type_name = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_post_type_name( $post_type_slug );
				$add_post_type  = wp_json_encode(
					array(
						array(
							'url_type_slug'  => 'types',
							'url_type_name'  => 'Post Types',
							'post_type_slug' => $post_type_slug,
							'post_type_name' => $post_type_name,
						),
					)
				);
				?>
				<option value='<?php echo esc_attr( $add_post_type ); ?>'><?php echo esc_html( $post_type->label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( 'Show Post Type', 'secondary', 'submit', false ); ?>
		<?php
		$this->render_form_end();
	}

	/**
	 * Render taxonomy selection form.
	 *
	 * @param array $available_taxonomies Available taxonomies to add.
	 */
	public function render_taxonomy_form( $available_taxonomies ) {
		if ( empty( $available_taxonomies ) ) {
			echo '<p><i>All available taxonomies are already shown.</i></p>';
			return;
		}

		$this->render_form_start( 'add_post_type', 'post', array( 'style' => 'display: inline-block; margin-right: 10px;' ) );
		?>
		<select name="post_type">
			<?php foreach ( $available_taxonomies as $taxonomy ) : ?>
				<?php
				$taxonomy_slug = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_slug( $taxonomy );
				$taxonomy_name = \WebChangeDetector\WebChangeDetector_Admin_Utils::get_taxonomy_name( $taxonomy_slug );
				$add_taxonomy  = wp_json_encode(
					array(
						array(
							'url_type_slug'  => 'taxonomies',
							'url_type_name'  => 'Taxonomies',
							'post_type_slug' => $taxonomy_slug,
							'post_type_name' => $taxonomy_name,
						),
					)
				);
				?>
				<option value='<?php echo esc_attr( $add_taxonomy ); ?>'><?php echo esc_html( $taxonomy->label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( 'Show Taxonomy', 'secondary', 'submit', false ); ?>
		<?php
		$this->render_form_end();
	}

	/**
	 * Render admin bar setting form.
	 */
	public function render_admin_bar_form() {
		$this->render_form_start( 'save_admin_bar_setting' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Disable Admin Bar Menu</th>
				<td>
					<label>
						<input type="checkbox" name="wcd_disable_admin_bar_menu" value="1" <?php checked( get_option( 'wcd_disable_admin_bar_menu', 0 ), 1 ); ?> />
						Disable WCD Menu in Admin Bar
					</label>
					<p class="description">Check this box to hide the WCD menu item in the frontend admin bar.</p>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save Admin Bar Setting' ); ?>
		<?php
		$this->render_form_end();
	}

	/**
	 * Render screenshot form.
	 *
	 * @param string $sc_type The screenshot type.
	 * @param string $button_text The button text.
	 * @param array  $hidden_fields Additional hidden fields.
	 */
	public function render_screenshot_form( $sc_type, $button_text, $hidden_fields = array() ) {
		$this->render_form_start( 'take_screenshots' );
		?>
		<input type="hidden" name="sc_type" value="<?php echo esc_attr( $sc_type ); ?>">
		<?php foreach ( $hidden_fields as $field => $value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<?php endforeach; ?>
		<input type="submit" value="<?php echo esc_attr( $button_text ); ?>" class="button button-primary">
		<?php
		$this->render_form_end();
	}

	/**
	 * Render step update form.
	 *
	 * @param string $step The step to update to.
	 * @param string $button_text The button text.
	 */
	public function render_step_form( $step, $button_text ) {
		$this->render_form_start( 'start_manual_checks' );
		?>
		<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>">
		<input type="submit" value="<?php echo esc_attr( $button_text ); ?>" class="button button-primary">
		<?php
		$this->render_form_end();
	}

	/**
	 * Render form input field.
	 *
	 * @param string $type The input type.
	 * @param string $name The input name.
	 * @param string $value The input value.
	 * @param array  $attributes Additional attributes.
	 */
	public function render_input( $type, $name, $value = '', $attributes = array() ) {
		$attr_string = '';
		foreach ( $attributes as $key => $attr_value ) {
			$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $attr_value ) . '"';
		}
		
		?>
		<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>"<?php echo $attr_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
	}

	/**
	 * Render select dropdown.
	 *
	 * @param string $name The select name.
	 * @param array  $options The options array.
	 * @param string $selected The selected value.
	 * @param array  $attributes Additional attributes.
	 */
	public function render_select( $name, $options, $selected = '', $attributes = array() ) {
		$attr_string = '';
		foreach ( $attributes as $key => $attr_value ) {
			$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $attr_value ) . '"';
		}
		
		?>
		<select name="<?php echo esc_attr( $name ); ?>"<?php echo $attr_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
} 