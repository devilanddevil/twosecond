<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="webvitalslogs" class="tab-pane fade<?php echo $tab == 'webvitalslogs' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading"><?php esc_html_e('Debug Logs', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container"> <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/logs-icon.webp' ); ?>"></div>
	</div>
	<hr>

	<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
		<label><?php esc_html_e('Enable Core Web Vitals Logs', 'twosecond'); ?><span
				class="twosecondinfo"></span><span
				class="twosecond-info-display"><?php esc_html_e('Enable to Log Core Web Vitals Logs.', 'twosecond'); ?></span></label>
		<div class="input_box">
			<label class="switch" for="enable-webvitals-log">
				<input type="checkbox" name="webvitals_logs" <?php if (!empty($twoSecond_result['webvitals_logs']) && $twoSecond_result['webvitals_logs'] == "on")
					echo "checked"; ?> id="enable-webvitals-log">
				<div class="checked"></div>
			</label>
		</div>
	</div>
	<?php if (empty($twoSecond_result['webvitals_logs'])) { ?>
		<p class="alert_message"><?php esc_html_e('Enable Debug Log options for Logging', 'twosecond') ?></p>
	<?php } else { ?>
		<div class="bsd-flex gap20 filter-row">
			<div class="show_log bsd-flex gap10">
				<label for="show_log_entry"><?php esc_html_e('Show', 'twosecond'); ?></label>
				<select name="temp_input" id="show_log_entry" class="show_log_entry">
					<option value="10"><?php esc_html_e('10', 'twosecond'); ?></option>
					<option value="20"><?php esc_html_e('20', 'twosecond'); ?></option>
					<option value="30"><?php esc_html_e('30', 'twosecond'); ?></option>
					<option value="40"><?php esc_html_e('40', 'twosecond'); ?></option>
					<option value="50"><?php esc_html_e('50', 'twosecond'); ?></option>
				</select>
			</div>
			<div class="delete-log-data bsd-flex gap10">
				<label for="log_delete_time"><?php esc_html_e('Delete Logs', 'twosecond'); ?></label>
				<select class="log_select" id="log_delete_time" name="temp_input">
					<option value=""><?php esc_html_e('Select Log Time', 'twosecond'); ?></option>
					<option value="last7days"><?php esc_html_e('Keep last 7 Days', 'twosecond'); ?></option>
					<option value="lastMonth"><?php esc_html_e('Keep last 30 Days', 'twosecond'); ?></option>
					<option value="last3months"><?php esc_html_e('Keep last 90 Days', 'twosecond'); ?>
					</option>
					<option value="last6months"><?php esc_html_e('Keep last 180 Days', 'twosecond'); ?>
					</option>
					<!-- <option value="lastYear">All</option> -->
					<option value="all"><?php esc_html_e('All', 'twosecond'); ?></option>
				</select>
				<button type="button"
					class="btn btn-log-delete"><?php esc_html_e('Delete', 'twosecond'); ?></button>
			</div>

		</div>
		<div class="bsd-flex gap10 filter-row">
			<div class="filter_by_issue bsd-flex gap10">
				<label for="filter_by_issue"><?php esc_html_e('Issue Type', 'twosecond'); ?></label>
				<select name="temp_input" class="filter_by_issuetype">
					<option value=""><?php esc_html_e('All', 'twosecond'); ?></option>
					<option value="CLS"><?php esc_html_e('CLS', 'twosecond'); ?></option>
					<option value="FID"><?php esc_html_e('FID', 'twosecond'); ?></option>
					<option value="INP"><?php esc_html_e('INP', 'twosecond'); ?></option>
					<option value="LCP"><?php esc_html_e('LCP', 'twosecond'); ?></option>
				</select>
			</div>
			<div class="filter_by_device bsd-flex gap10">
				<label for="filter_by_device"><?php esc_html_e('Device', 'twosecond'); ?></label>
				<select name="temp_input" class="filter_by_deviceType">
					<option value=""><?php esc_html_e('All', 'twosecond'); ?></option>
					<option value="Mobile"><?php esc_html_e('Mobile', 'twosecond'); ?></option>
					<option value="Desktop"><?php esc_html_e('Desktop', 'twosecond'); ?></option>
				</select>
			</div>
			<div class="filter_by_url ">
				<select class="url-select-multiple" id="filter_by_url" class="filter_by_url_input"
					name="temp_input[]" multiple="multiple">
					<input type="text" class="custom_select_inp"
						placeholder="<?php esc_html_e('https://...', 'twosecond'); ?>">
					<button type="button" class="btn_clear_url_inp" style="display:none">+</button>
					<div id="custom_select_url"></div>
				</select>
			</div>
			<div class="filter_by_date bsd-flex gap10">
				<label for="start_date"><?php esc_html_e('From', 'twosecond'); ?></label>
				<input type="text" name="temp_input" class="start_date">
				<label for="end_date"><?php esc_html_e('To', 'twosecond'); ?></label>
				<input type="text" name="temp_input" class="end_date">
			</div>
			<button type="button"
				class="btn btn-apply-filter"><?php esc_html_e('Apply Filters', 'twosecond'); ?></button>
			<button type="button"
				class="btn btn-rem-filter"><?php esc_html_e('Clear', 'twosecond'); ?></button>
		</div>
		<div popover="auto" id="more_info">
				<button type="button" popovertarget="more_info" popovertargetaction="hide" title="<?php esc_html_e('Close', 'twosecond'); ?>"
				class="close-popover">+</button>
			<ul class="log-info">

			</ul>
		</div>
		<div class="log-data-table">
			<?php $twoSecond_admin->core->twosecond_get_log_data(); ?>
		</div>
		<?php
	}
	?>
</section>
