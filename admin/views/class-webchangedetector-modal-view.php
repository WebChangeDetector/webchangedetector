<?php
/**
 * Modal View Component for WebChangeDetector
 *
 * Handles rendering of modal dialogs and popups.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/views
 * @author     Mike Miler <mike@wp-mike.com>
 */

namespace WebChangeDetector;

/**
 * Modal View Component Class.
 */
class WebChangeDetector_Modal_View {

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
	 * Render comparison modal.
	 *
	 * @param array $comparison The comparison data.
	 */
	public function render_comparison_modal( $comparison ) {
		?>
		<div id="comparison-modal" class="wcd-modal" style="display: none;">
			<div class="wcd-modal-content">
				<div class="wcd-modal-header">
					<span class="wcd-modal-close">&times;</span>
					<h3>Comparison Details</h3>
				</div>
				<div class="wcd-modal-body">
					<div class="comparison-info">
						<p><strong>URL:</strong> <?php echo esc_url( $comparison['url'] ?? '' ); ?></p>
						<p><strong>Device:</strong> <?php echo esc_html( $comparison['device'] ?? '' ); ?></p>
						<p><strong>Date:</strong> <?php echo esc_html( $comparison['created_at'] ?? '' ); ?></p>
						<p><strong>Status:</strong> <?php echo esc_html( ucfirst( $comparison['status'] ?? '' ) ); ?></p>
					</div>
					
					<?php if ( ! empty( $comparison['image_before'] ) && ! empty( $comparison['image_after'] ) ) : ?>
						<div class="comparison-images-modal">
							<div class="image-container">
								<h4>Before</h4>
								<img src="<?php echo esc_url( $comparison['image_before'] ); ?>" alt="Before" class="comparison-image">
							</div>
							<div class="image-container">
								<h4>After</h4>
								<img src="<?php echo esc_url( $comparison['image_after'] ); ?>" alt="After" class="comparison-image">
							</div>
							<?php if ( ! empty( $comparison['image_diff'] ) ) : ?>
								<div class="image-container">
									<h4>Difference</h4>
									<img src="<?php echo esc_url( $comparison['image_diff'] ); ?>" alt="Difference" class="comparison-image">
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="wcd-modal-footer">
					<button type="button" class="button" onclick="closeModal()">Close</button>
					<?php if ( ! empty( $comparison['id'] ) ) : ?>
						<div class="status-actions">
							<form method="post" style="display: inline;">
								<input type="hidden" name="wcd_action" value="change_comparison_status">
								<input type="hidden" name="comparison_id" value="<?php echo esc_attr( $comparison['id'] ); ?>">
								<input type="hidden" name="status" value="ok">
								<?php wp_nonce_field( 'change_comparison_status' ); ?>
								<input type="submit" value="Mark as OK" class="button button-secondary">
							</form>
							<form method="post" style="display: inline;">
								<input type="hidden" name="wcd_action" value="change_comparison_status">
								<input type="hidden" name="comparison_id" value="<?php echo esc_attr( $comparison['id'] ); ?>">
								<input type="hidden" name="status" value="to_fix">
								<?php wp_nonce_field( 'change_comparison_status' ); ?>
								<input type="submit" value="Mark as To Fix" class="button button-primary">
							</form>
							<form method="post" style="display: inline;">
								<input type="hidden" name="wcd_action" value="change_comparison_status">
								<input type="hidden" name="comparison_id" value="<?php echo esc_attr( $comparison['id'] ); ?>">
								<input type="hidden" name="status" value="false_positive">
								<?php wp_nonce_field( 'change_comparison_status' ); ?>
								<input type="submit" value="Mark as False Positive" class="button">
							</form>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render confirmation modal.
	 *
	 * @param string $id      The modal ID.
	 * @param string $title   The modal title.
	 * @param string $message The confirmation message.
	 * @param string $action  The action to confirm.
	 * @param array  $hidden_fields Hidden form fields.
	 */
	public function render_confirmation_modal( $id, $title, $message, $action, $hidden_fields = array() ) {
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="wcd-modal" style="display: none;">
			<div class="wcd-modal-content">
				<div class="wcd-modal-header">
					<span class="wcd-modal-close">&times;</span>
					<h3><?php echo esc_html( $title ); ?></h3>
				</div>
				<div class="wcd-modal-body">
					<p><?php echo wp_kses_post( $message ); ?></p>
				</div>
				<div class="wcd-modal-footer">
					<button type="button" class="button" onclick="closeModal('<?php echo esc_attr( $id ); ?>')">Cancel</button>
					<form method="post" style="display: inline;">
						<input type="hidden" name="wcd_action" value="<?php echo esc_attr( $action ); ?>">
						<?php wp_nonce_field( $action ); ?>
						<?php foreach ( $hidden_fields as $field => $value ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>">
						<?php endforeach; ?>
						<input type="submit" value="Confirm" class="button button-primary">
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render API token modal.
	 */
	public function render_api_token_modal() {
		?>
		<div id="api-token-modal" class="wcd-modal" style="display: none;">
			<div class="wcd-modal-content">
				<div class="wcd-modal-header">
					<span class="wcd-modal-close">&times;</span>
					<h3>Add API Token</h3>
				</div>
				<div class="wcd-modal-body">
					<form method="post" id="api-token-form">
						<input type="hidden" name="wcd_action" value="save_api_token">
						<?php wp_nonce_field( 'save_api_token' ); ?>
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="api_token">API Token</label>
								</th>
								<td>
									<input type="text" id="api_token" name="api_token" class="regular-text" required>
									<p class="description">
										Enter your WebChangeDetector API token. You can find this in your account dashboard.
									</p>
								</td>
							</tr>
						</table>
					</form>
				</div>
				<div class="wcd-modal-footer">
					<button type="button" class="button" onclick="closeModal('api-token-modal')">Cancel</button>
					<button type="submit" form="api-token-form" class="button button-primary">Save Token</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render screenshot preview modal.
	 *
	 * @param string $image_url The screenshot URL.
	 * @param array  $details   Screenshot details.
	 */
	public function render_screenshot_modal( $image_url, $details = array() ) {
		?>
		<div id="screenshot-modal" class="wcd-modal" style="display: none;">
			<div class="wcd-modal-content screenshot-modal">
				<div class="wcd-modal-header">
					<span class="wcd-modal-close">&times;</span>
					<h3>Screenshot Preview</h3>
				</div>
				<div class="wcd-modal-body">
					<?php if ( ! empty( $details ) ) : ?>
						<div class="screenshot-details">
							<p><strong>URL:</strong> <?php echo esc_url( $details['url'] ?? '' ); ?></p>
							<p><strong>Device:</strong> <?php echo esc_html( $details['device'] ?? '' ); ?></p>
							<p><strong>Date:</strong> <?php echo esc_html( $details['created_at'] ?? '' ); ?></p>
						</div>
					<?php endif; ?>
					<div class="screenshot-container">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="Screenshot" class="screenshot-image">
					</div>
				</div>
				<div class="wcd-modal-footer">
					<button type="button" class="button" onclick="closeModal('screenshot-modal')">Close</button>
					<a href="<?php echo esc_url( $image_url ); ?>" target="_blank" class="button button-secondary">Open Full Size</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render help modal.
	 *
	 * @param string $title   The help title.
	 * @param string $content The help content.
	 */
	public function render_help_modal( $title, $content ) {
		?>
		<div id="help-modal" class="wcd-modal" style="display: none;">
			<div class="wcd-modal-content">
				<div class="wcd-modal-header">
					<span class="wcd-modal-close">&times;</span>
					<h3><?php echo esc_html( $title ); ?></h3>
				</div>
				<div class="wcd-modal-body">
					<?php echo wp_kses_post( $content ); ?>
				</div>
				<div class="wcd-modal-footer">
					<button type="button" class="button" onclick="closeModal('help-modal')">Close</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render account creation modal.
	 */
	public function render_account_creation_modal() {
		?>
		<div id="account-creation-modal" class="wcd-modal" style="display: none;">
			<div class="wcd-modal-content">
				<div class="wcd-modal-header">
					<span class="wcd-modal-close">&times;</span>
					<h3>Create Trial Account</h3>
				</div>
				<div class="wcd-modal-body">
					<form method="post" id="account-creation-form">
						<input type="hidden" name="wcd_action" value="create_trial_account">
						<?php wp_nonce_field( 'create_trial_account' ); ?>
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="email">Email Address</label>
								</th>
								<td>
									<input type="email" id="email" name="email" class="regular-text" required 
									       value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
									<p class="description">
										We'll create a trial account with this email address.
									</p>
								</td>
							</tr>
						</table>
					</form>
				</div>
				<div class="wcd-modal-footer">
					<button type="button" class="button" onclick="closeModal('account-creation-modal')">Cancel</button>
					<button type="submit" form="account-creation-form" class="button button-primary">Create Account</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render modal JavaScript.
	 */
	public function render_modal_scripts() {
		?>
		<script type="text/javascript">
			function openModal(modalId) {
				document.getElementById(modalId).style.display = 'block';
			}

			function closeModal(modalId) {
				if (modalId) {
					document.getElementById(modalId).style.display = 'none';
				} else {
					// Close all modals.
					var modals = document.querySelectorAll('.wcd-modal');
					modals.forEach(function(modal) {
						modal.style.display = 'none';
					});
				}
			}

			// Close modal when clicking outside of it.
			window.onclick = function(event) {
				if (event.target.classList.contains('wcd-modal')) {
					event.target.style.display = 'none';
				}
			};

			// Close modal with escape key.
			document.addEventListener('keydown', function(event) {
				if (event.key === 'Escape') {
					closeModal();
				}
			});

			// Close modal when clicking the X button.
			document.addEventListener('click', function(event) {
				if (event.target.classList.contains('wcd-modal-close')) {
					var modal = event.target.closest('.wcd-modal');
					if (modal) {
						modal.style.display = 'none';
					}
				}
			});
		</script>
		
		<style type="text/css">
			.wcd-modal {
				position: fixed;
				z-index: 1000;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%;
				background-color: rgba(0,0,0,0.5);
			}

			.wcd-modal-content {
				background-color: #fefefe;
				margin: 5% auto;
				border: 1px solid #888;
				border-radius: 4px;
				width: 80%;
				max-width: 800px;
				max-height: 90%;
				overflow-y: auto;
			}

			.wcd-modal-header {
				padding: 15px 20px;
				background-color: #f1f1f1;
				border-bottom: 1px solid #ddd;
				border-radius: 4px 4px 0 0;
				position: relative;
			}

			.wcd-modal-header h3 {
				margin: 0;
				font-size: 18px;
			}

			.wcd-modal-close {
				position: absolute;
				right: 15px;
				top: 15px;
				color: #aaa;
				font-size: 28px;
				font-weight: bold;
				cursor: pointer;
			}

			.wcd-modal-close:hover {
				color: #000;
			}

			.wcd-modal-body {
				padding: 20px;
			}

			.wcd-modal-footer {
				padding: 15px 20px;
				background-color: #f1f1f1;
				border-top: 1px solid #ddd;
				border-radius: 0 0 4px 4px;
				text-align: right;
			}

			.wcd-modal-footer .button {
				margin-left: 10px;
			}

			.comparison-images-modal {
				display: flex;
				flex-wrap: wrap;
				gap: 20px;
				margin-top: 20px;
			}

			.comparison-images-modal .image-container {
				flex: 1;
				min-width: 250px;
			}

			.comparison-image {
				max-width: 100%;
				height: auto;
				border: 1px solid #ddd;
			}

			.screenshot-modal .wcd-modal-content {
				max-width: 95%;
			}

			.screenshot-container {
				text-align: center;
				margin-top: 20px;
			}

			.screenshot-image {
				max-width: 100%;
				height: auto;
				border: 1px solid #ddd;
			}
		</style>
		<?php
	}
} 