<?php
/**
 * Email Input Component
 *
 * Reusable component for email input fields with validation.
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
 * @var string $email_value      Current email value
 * @var string $field_name       Form field name
 * @var string $label            Label text
 * @var string $description      Description text
 * @var string $css_class        Optional CSS classes
 * @var bool   $multiple         Whether multiple emails are allowed
 * @var bool   $show_validation  Whether to show validation error message
 * @var string $placeholder      Placeholder text
 */

$email_value     = $email_value ?? get_option( 'admin_email' );
$field_name      = $field_name ?? 'email';
$label           = $label ?? __( 'Email address', 'webchangedetector' );
$description     = $description ?? __( 'Enter the email address for notifications.', 'webchangedetector' );
$css_class       = $css_class ?? '';
$multiple        = $multiple ?? false;
$show_validation = $show_validation ?? true;
$placeholder     = $placeholder ?? ( $multiple ? __( 'email1@example.com, email2@example.com', 'webchangedetector' ) : __( 'email@example.com', 'webchangedetector' ) );

$validation_id = 'error-' . sanitize_title( $field_name ) . '-validation';
?>

<div class="setting-row <?php echo esc_attr( $css_class ); ?>">
	<label for="<?php echo esc_attr( $field_name ); ?>">
		<?php echo esc_html( $label ); ?>
		<?php if ( $multiple ) : ?>
			<?php esc_html_e( 'Alert email addresses (comma separated)', 'webchangedetector' ); ?>
		<?php endif; ?>
	</label>
	<input
		name="<?php echo esc_attr( $field_name ); ?>"
		id="<?php echo esc_attr( $field_name ); ?>"
		style="width: 100%"
		type="<?php echo $multiple ? 'text' : 'email'; ?>"
		value="<?php echo esc_attr( $email_value ); ?>"
		class="<?php echo esc_attr( $field_name ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
		<?php if ( $multiple ) : ?>
		data-multiple="true"
		<?php endif; ?>>
	<?php if ( $description ) : ?>
		<br><small><?php echo esc_html( $description ); ?></small>
	<?php endif; ?>

	<?php if ( $show_validation ) : ?>
		<span class="notice notice-error" id="<?php echo esc_attr( $validation_id ); ?>" style="display: none;">
			<span style="padding: 10px; display: block;" class="default-bg">
				<?php esc_html_e( 'Please check your email address', 'webchangedetector' ); ?><?php echo $multiple ? esc_html__( '(es)', 'webchangedetector' ) : ''; ?>.
			</span>
		</span>
	<?php endif; ?>
</div>

<?php if ( $show_validation ) : ?>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			// Email validation.
			function validateEmail(email) {
				var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				return emailRegex.test(email);
			}

			function validateEmailField() {
				var emailField = document.getElementById('<?php echo esc_js( $field_name ); ?>');
				var errorElement = document.getElementById('<?php echo esc_js( $validation_id ); ?>');


				if (!emailField || !errorElement) return true;

				var emailValue = emailField.value.trim();


				if (!emailValue) {
					errorElement.style.display = 'none';
					return true; // Empty is allowed, let server-side handle required validation.
				}

				<?php if ( $multiple ) : ?>
					// Multiple emails validation.
					var emails = emailValue.split(',').map(function(email) {
						return email.trim();
					});

					var allValid = emails.every(function(email) {
						return email === '' || validateEmail(email);
					});

					if (!allValid) {
						errorElement.style.display = 'block';
						return false;
					} else {
						errorElement.style.display = 'none';
						return true;
					}
				<?php else : ?>
					// Single email validation.
					if (!validateEmail(emailValue)) {
						errorElement.style.display = 'block';
						return false;
					} else {
						errorElement.style.display = 'none';
						return true;
					}
				<?php endif; ?>
			}

			// Add event listener to email field.
			var emailField = document.getElementById('<?php echo esc_js( $field_name ); ?>');
			if (emailField) {
				emailField.addEventListener('blur', validateEmailField);
				emailField.addEventListener('input', function() {
					// Hide error on input, validate on blur.
					var errorElement = document.getElementById('<?php echo esc_js( $validation_id ); ?>');
					if (errorElement && this.value.trim() !== '') {
						setTimeout(validateEmailField, 500); // Debounced validation.
					}
				});
			}

			// Make validation function globally available.
			window['validate_<?php echo esc_js( $field_name ); ?>'] = validateEmailField;

		});
	</script>
<?php endif; ?>