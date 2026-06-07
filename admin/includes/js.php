<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="js" class="tab-pane fade white-bg-twoSecond<?php echo $tab == 'js' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('Javascript Optimization', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/#javascript_optimization"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container"><img
			src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/js-icon.webp' ); ?>" alt="Javascript Optimization"></div>
	</div>
	<hr>

	<div class="js_box">
		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Enable Javascript Optimization', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Turn on to optimize javascript', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-js-minification">
					<input type="checkbox" name="js" 
					<?php if (!empty($twoSecond_result['js']) && $twoSecond_result['js'] == "on") esc_html_e("checked", 'twosecond'); ?> id="enable-js-minification">
					<div class="checked"></div>
				</label>
			</div>
		</div>

		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Lazyload Javascript', 'twosecond'); ?> <span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Choose when to load javascript', 'twosecond'); ?></span></label>
			<select name="load_combined_js">
				<option value="after_page_load" <?php if (!empty($twoSecond_result['load_combined_js']) && $twoSecond_result['load_combined_js'] == 'after_page_load') echo esc_attr( 'selected' ); ?>>
					<?php esc_html_e('Yes', 'twosecond'); ?>
				</option>
				<option value="on_page_load" <?php if (!empty($twoSecond_result['load_combined_js']) && $twoSecond_result['load_combined_js'] == 'on_page_load') echo esc_attr( 'selected' ); ?>>
					<?php esc_html_e('No', 'twosecond'); ?>
				</option>

			</select>
		</div>
	</div>
	<hr>
	<div class="js_box cdn_resources">
		<div class="bsd-flex gap20 bsalign-item-baseline">
			<label><?php esc_html_e('Load Inline Javascript as URL', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enter matching text of inline script url which needs to be load in a url to avoid large page size, javascript execution time. Each exclusion to be entered in a new line.', 'twosecond'); ?></span></label>
			<div class="input_box">
				<div class="single-row">
					<?php
					if (!empty($twoSecond_result['load_script_tag_in_url']) && is_array($twoSecond_result['load_script_tag_in_url'])) {
						foreach ($twoSecond_result['load_script_tag_in_url'] as $twoSecond_row) {
							if (!empty(trim($twoSecond_row))) {
								?>
								<div class="cdn_input_box minus bsd-flex">
									<input type="text" name="load_script_tag_in_url[]"
										value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
										placeholder="<?php esc_html_e('Please Enter matching text of the inline javascript here', 'twosecond'); ?>"><button
										type="button" class="bstext-white rem-row bsbg-danger"><i
											class="fa fa-times"></i></button>
								</div>
								<?php
							}
						}
					} ?>
				</div>
				<div class="cdn_input_box plus">
					<button type="button" data-name="load_script_tag_in_url"
						data-placeholder="<?php esc_html_e('Please Enter matching text of the inline javascript here', 'twosecond'); ?>"
						class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
				</div>

			</div>
		</div>
	</div>
	<hr>
	<div class="save-changes bsd-flex gap10">
		<input type="button" value="<?php esc_html_e('Save Changes', 'twosecond'); ?>"
			class="btn hook_submit">
		<div class="in-progress bsd-flex save-changes-loader" style="display:none">
			<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
				class="loader-img">
		</div>
	</div>

</section>
