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
		<form method="<?php echo esc_attr( $method ); ?>" 
								<?php
									echo $attr_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
								?>
														>
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
}
