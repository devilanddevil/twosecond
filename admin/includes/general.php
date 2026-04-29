<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="general" class="tab-pane fade<?php echo empty($tab) || $tab == 'general' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('General Setting', 'twosecond'); ?>
			</h4>
			<h4 class="twosecond_sub_heading">
				<?php esc_html_e('Optimization Level', 'twosecond'); ?>
			</h4> <span class="twosecondinfo"><a
					href="https://twosecond.ai/#general_setting"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container">
			<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/general-setting-icon.webp' ); ?>">
		</div>
	</div>
	<hr>
	<div class="license_key bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
		<label for="">
			<?php esc_html_e('License Key', 'twosecond'); ?><span class="twosecondinfo"></span><span
				class="twosecond-info-display">
				<?php esc_html_e('Activate key to get updates and access to all features of the plugin.', 'twosecond'); ?>
			</span>
		</label>
		<div class="key bsd-flex">
			<input type="text" name="license_key"
				placeholder="<?php esc_html_e('Key', 'twosecond'); ?>"
				value="<?php echo esc_attr( $twoSecond_admin->core->addSettings['mainLicenseKey'] ); ?>"
				style="">
			<input type="hidden" name="twosecondApiUrl"
				value="<?php echo esc_attr( !empty($twoSecond_result['twosecondApiUrl']) ? $twoSecond_result['twosecondApiUrl'] : '' ); ?>">
			<input type="hidden" name="is_activated"
				value="<?php echo esc_attr( !empty($twoSecond_result['is_activated']) ? $twoSecond_result['is_activated'] : '' ); ?>">
			<input type="hidden" name="_twosecond_nonce"
				value="<?php echo esc_attr( $twoSecond_admin->core->twosecond_create_secure_key('twosecond_settings') ); ?>">
			<input type="hidden" name="ws_action" value="cache">
			<?php if (!empty($twoSecond_result['license_key']) && !empty($twoSecond_result['is_activated'])) {
				?>
				<i class="fa fa-check-circle-o" aria-hidden="true"></i>
				<?php
			} else { ?>
				<div class="bsd-flex gap10">
					<button class="activate-key btn" type="button">
						<?php esc_html_e('Activate', 'twosecond'); ?>
					</button>
					<div class="in-progress bsd-flex" id="verify-key-loader" style="display: none;">
						<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
							class="loader-img">
					</div>
				</div>
			<?php }
			?>
		</div>
	</div>
	<?php
	if($twoSecond_network_admin){ ?>
		<div class="manage-separately bsd-flex gap20">
			<label><?php esc_html_e('Manage Each Site Separately', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable this option to enter separate settings for each site. Plugin page will then be available in the backend of every site.', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="manage-site-separately">
					<input type="checkbox" name="manage_site_separately" <?php if (!empty($twoSecond_result['manage_site_separately']) && $twoSecond_result['manage_site_separately'] == "on")
						echo "checked"; ?> id="manage-site-separately">
					<div class="checked"></div>
				</label>
			</div>
		</div>
	<?php } ?>
	<hr class="<?php echo esc_attr( $twoSecond_hidden_class ); ?>">
	<div class="main <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
		<div class="turn_on_optimization <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<div class="bsd-flex gap20">
				<label><?php esc_html_e('Turn ON optimization', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Site will start to optimize. All optimization settings will be applied.', 'twosecond'); ?></span></label>
				<div class="input_box">
					<label class="switch" for="turn-on-optimization">
						<input type="checkbox" name="optimization_on" <?php if (!empty($twoSecond_result['optimization_on']) && $twoSecond_result['optimization_on'] == "on")
							echo "checked"; ?> id="turn-on-optimization">
						<div class="checked"></div>
					</label>
				</div>
			</div>
			<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
				<label><?php esc_html_e('Optimize Pages with Query Parameters', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('It will optimize pages with query parameters. Recommended only for servers with high performance.', 'twosecond'); ?></span></label>
				<div class="input_box">
					<label class="switch" for="optimize-pages-with-query-parameters">
						<input type="checkbox" name="optimize_query_parameters"
							id="optimize-pages-with-query-parameters" <?php if (!empty($twoSecond_result['optimize_query_parameters']))
								echo "checked"; ?>>
						<div class="checked"></div>
					</label>
				</div>
			</div>
			<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
				<label><?php esc_html_e('Optimize pages when User Logged In', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('It will optimize pages when users are logged in. Recommended only for servers with high performance', 'twosecond'); ?></span></label>
				<div class="input_box">
					<label class="switch" for="optimize-pages-when-user-logged-in">
						<input type="checkbox" name="optimize_user_logged_in"
							id="optimize-pages-when-user-logged-in" <?php if (!empty($twoSecond_result['optimize_user_logged_in']))
								echo "checked"; ?>>
						<div class="checked"></div>
					</label>
				</div>
			</div>
			<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
				<label><?php esc_html_e('Fix INP Issues', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enable to fix Interactive next paint issues appearing in googe page speed assessment test and/or google search console.', 'twosecond'); ?></span></label>
				<div class="input_box">
					<label class="switch">
						<input type="checkbox" name="enable_inp" <?php if (!empty($twoSecond_result['enable_inp']) && $twoSecond_result['enable_inp'] == "on")
							echo "checked"; ?>
							id="enable-inp">
						<div class="checked"></div>
					</label>
				</div>
			</div>
		</div>
	</div>
	<hr class="<?php echo esc_attr( $twoSecond_hidden_class ); ?>">
	<div class="save-changes bsd-flex gap10">
		<input type="button" value="<?php esc_html_e('Save Changes', 'twosecond'); ?>"
			class="btn hook_submit">
		<div class="in-progress bsd-flex save-changes-loader" style="display:none">
			<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
				class="loader-img">
		</div>
	</div>
</section>
