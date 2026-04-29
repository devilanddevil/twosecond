<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="import" class="tab-pane fade<?php echo $tab == 'import' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('Import / Export', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container"> <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/import-export-icon.webp' ); ?>" alt="Import / Export"></div>
	</div>
	<hr>
	<div class="import_form">
		<label><?php esc_html_e('Import Settings', 'twosecond'); ?><span class="twosecondinfo"></span><span
				class="twosecond-info-display"><?php esc_html_e('Upload the exported file (.dat) from TwoSecond', 'twosecond'); ?></span></label>
		<div class="twosecond-import-row">
			<input form="import_form" type="file" id="twosecond_import_file" name="twosecond_import_file" accept=".dat,.txt,application/octet-stream">
			<input form="import_form" type="hidden" name="action" value="twosecond_import_settings">
			<input form="import_form" type="hidden" name="_twosecond_nonce"
			value="<?php echo esc_attr( $twoSecond_admin->core->twosecond_create_secure_key('twosecond_settings_import') ); ?>">
			<button form="import_form" id="import_button" class="btn" type="button"><?php esc_html_e('Import File', 'twosecond'); ?></button>
		</div>
	</div>
	<?php
	$twoSecond_export_setting = $twoSecond_result;
	$twoSecond_export_setting['license_key'] = '';
	$twoSecond_export_setting['is_activated'] = '';
	?>

	<hr>
	<div class="import_form">
		<label><?php esc_html_e('Export Settings', 'twosecond'); ?><span class="twosecondinfo"></span><span
				class="twosecond-info-display"><?php esc_html_e('Download a file with your plugin settings', 'twosecond'); ?></span></label>
		<?php 
		$twoSecond_export_base = str_replace('admin-ajax.php','admin-post.php', $twoSecond_admin->core->twosecond_get_ajax_url());
		$twoSecond_export_url = $twoSecond_export_base . '?action=twosecond_export_settings&_twosecond_nonce=' . $twoSecond_admin->core->twosecond_create_secure_key('twosecond_settings_export');
		?>
		<a class="btn" href="<?php $twoSecond_admin->core->twosecond_esc_url_echo($twoSecond_export_url); ?>"><?php esc_html_e('Download Export File', 'twosecond'); ?></a>
	</div>
</section>