<?php
/**
 * Admin Page Template
 *
 * @package TwoSecond
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



// Get admin instance and core
$twoSecond_admin = new \TwoSecond\Admin\Admin();
// Get current tab
$tab = $twoSecond_admin->core->twosecond_verify_request_value('tab') ?? 'general';
$twoSecond_result = $twoSecond_admin->core->settings;
$twoSecond_network_admin = $twoSecond_admin->core->addSettings['is_multisite_networkadmin'] ? 1 : 0;
$twoSecond_hidden_class = ! empty( $twoSecond_result['manage_site_separately'] ) && $twoSecond_admin->core->addSettings['is_multisite_networkadmin'] ? 'tr-hidden' : '';
$twoSecond_result['ai-optimization'] = ! empty( $twoSecond_result['ai-optimization'] ) ? ( $twoSecond_admin->core->twosecond_check_ai_optimization_enabled() ? 'on' : '' ) : '';
$twoSecond_folder_errors = $twoSecond_admin->core->twosecond_check_folder_permission_errors();

// Add admin configuration as separate JS variables.
wp_add_inline_script(
	'twosecond-admin-bar-script',
	'var adminUrl = "' . esc_js( $twoSecond_admin->core->twosecond_get_ajax_url() ) . '";' .
	'var secureKey = "' . esc_js( $twoSecond_admin->core->twosecond_create_secure_key( 'hook_callback' ) ) . '";',
	'before'
);
?>
<div class="twosecond-toast-container"></div>
<main class="admin-twoSecond">
	<div class="top_panel_container">
		<div class="top_panel d-none">
			<div class="logo_container">
				<img class="logo" src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/Blinkspeed-logo-final-white.webp' ); ?>">
			</div>

			<div class="support_section">
				<div class="right_section">
					<div class="doc bsd-flex gap10">
						<p class="m-0"><i class="fa fa-file-text" aria-hidden="true"></i></p>
						<p class="m-0 text-center bstext-white">
							<?php esc_html_e('Need help or have question', 'twosecond'); ?><br><a
								href="https://twosecond.ai/"
								target="_blank"><?php esc_html_e('Check our documentation', 'twosecond'); ?></a>
						</p>

					</div>

				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="tab-panel col-md-2">
			<div class="mobile_toggle d-none">
				<button type="button">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
						<path fill="#fff"
							d="M246.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-9.2-9.2-22.9-11.9-34.9-6.9S63.9 115 63.9 128v256c0 12.9 7.8 24.6 19.8 29.6s25.7 2.2 34.9-6.9l128-128z" />
					</svg>
				</button>
			</div>
			<div class="logo_container">
				<img class="logo" src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/Blinkspeed-logo-final-white.webp' ); ?>">
			</div>
			<?php include 'includes/tabs.php'; ?>
			<div class="support_section">
				<a class="doc btn" href="https://twosecond.ai/"
					target="_blank"><?php esc_html_e('Documentation', 'twosecond'); ?> <i
						class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
				<a class="contact btn"
					href="https://twosecond.ai/"><?php esc_html_e('Contact Us', 'twosecond'); ?> <i
						class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
			</div>
		</div>

		<form method="post" class="main-form">
			<div class="tab-content col-md-10">
				<?php if(!empty($twoSecond_folder_errors)) :?>
					<div style='padding-top:10px'>
						<div class="bsd-flex gap10 error-div-main">
							<h3 class="error_heading"><?php esc_html_e('Permission Denied | Unable to create the following path:', 'twosecond') ?> <br>➝ <?php echo esc_html( implode("<br>➝ ", $twoSecond_folder_errors) ); ?></h3>
							<button type="button" class="error_close_btn">
								<svg width="25" height="25" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
									<path d="m19.41 18 8.29-8.29a1 1 0 0 0-1.41-1.41L18 16.59l-8.29-8.3a1 1 0 0 0-1.42 1.42l8.3 8.29-8.3 8.29A1 1 0 1 0 9.7 27.7l8.3-8.29 8.29 8.29a1 1 0 0 0 1.41-1.41Z"></path>
								</svg>
							</button>
						</div>
					</div>
				<?php endif; ?>
				
				<?php include 'includes/general.php'; ?>
				<?php include 'includes/cdn.php'; ?>
				<?php include 'includes/css.php'; ?>
				<?php include 'includes/js.php'; ?>
				<?php include 'includes/exclusions.php'; ?>
				<?php include 'includes/cache.php'; ?>
				<?php include 'includes/webvital-logs.php'; ?>
				<?php include 'includes/html-cache.php'; ?>
				<?php include 'includes/opt-img.php'; ?>
				<?php include 'includes/import-export.php'; ?>
				<?php include 'includes/change-log.php'; ?>
				<?php include 'includes/optimize-with-ai.php'; ?>
				<?php do_action('twosecond_optimize_result', $twoSecond_result); ?>
			</div>
		</form>
		<form id="import_form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
		</form>
	</div>
	<?php
	$twoSecond_errors = $twoSecond_admin->core->twosecond_get_errors();
	if ( ! empty( $twoSecond_errors ) ) :
		$twoSecond_admin->core->twosecond_flush_errors();
		$twoSecond_toast_script = '';
		foreach ( $twoSecond_errors as $twoSecond_value ) {
			$twoSecond_toast_script .= "twosecondShowToast(" .
				"'" . esc_js( $twoSecond_value['type'] ?? 'info' ) . "'," .
				"'" . esc_js( $twoSecond_value['message'] ?? 'Something Went Wrong' ) . "'" .
			");\n";
		}
		wp_add_inline_script( 'twosecond-admin-bar-script', $twoSecond_toast_script );
	endif;
	?>
</main>
<?php do_action('twosecond_js'); ?>