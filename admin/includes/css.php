<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="css" class="tab-pane fade<?php echo $tab == 'css' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('CSS Optimization', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/#css_optimization"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container">
			<img 
			src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/css-icon.webp' ); ?>" alt="CSS Optimization">
		</div>
	</div>
	<hr>
	<div class="css_box">
		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Enable CSS Optimization', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Turn on to optimize css', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-css-minification">
					<input type="checkbox" name="css" <?php if (!empty($twoSecond_result['css']) && $twoSecond_result['css'] == "on")
						echo "checked"; ?> id="enable-css-minification"
						class="opt-css">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Localize Google fonts', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Turn on to load all google fonts from self domain', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="localize-google-fonts">
					<input type="checkbox" name="localize_google_fonts" <?php if (!empty($twoSecond_result['localize_google_fonts']) && $twoSecond_result['localize_google_fonts'] == "on")
						echo "checked"; ?>
						id="localize-google-fonts" class="opt-css">
					<div class="checked"></div>
				</label>
			</div>
		</div>
	</div>
	<hr>
	<div class="css_box">
		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Load Critical CSS', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Preload generated crictical css', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="load-critical-css">
					<input type="checkbox" name="load_critical_css" <?php if (!empty($twoSecond_result['load_critical_css']) && $twoSecond_result['load_critical_css'] == "on")
						echo "checked"; ?> id="load-critical-css" class="opt-css parent-fields">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 child-fields" <?php if (empty($twoSecond_result['load_critical_css'])) {
			echo 'style="display:none"';} ?>>
			<label><?php esc_html_e('Load Critical CSS in Style Tag', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Preload generated crictical css in style tag', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="load-critical-css-in-style-tag">
					<input type="checkbox" name="load_critical_css_style_tag" <?php if (!empty($twoSecond_result['load_critical_css_style_tag']) && $twoSecond_result['load_critical_css_style_tag'] == "on")
						echo "checked"; ?>
						id="load-critical-css-in-style-tag" class="opt-css">
					<div class="checked"></div>
				</label>
			</div>
		</div>
	</div>
	<hr>
	<div class="css_box cdn_resources">
		<div class="bsd-flex gap20 bsalign-item-baseline">
			<label><?php esc_html_e('Load Style Tag in Head to Avoid CLS', 'twosecond'); ?> <span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enter matching text of style tag, which are to be loaded in the head. Each style tag to be entered in a new line', 'twosecond'); ?></span></label>
			<div class="input_box">
				<div class="single-row">
					<?php
					if (!empty($twoSecond_result['load_style_tag_in_head']) && is_array($twoSecond_result['load_style_tag_in_head'])) {
						foreach ($twoSecond_result['load_style_tag_in_head'] as $twoSecond_row) {
							if (!empty(trim($twoSecond_row))) {
								?>
								<div class="cdn_input_box minus bsd-flex">
									<input type="text" name="load_style_tag_in_head[]"
										value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
										placeholder="<?php esc_html_e('Please Enter style tag text', 'twosecond'); ?>"><button
										type="button" class="bstext-white rem-row bsbg-danger"><i
											class="fa fa-times"></i></button>
								</div>
								<?php
							}
						}
					} ?>
				</div>
				<div class="cdn_input_box plus">
					<button type="button" data-name="load_style_tag_in_head"
						data-placeholder="<?php esc_html_e('Please Enter style tag text', 'twosecond'); ?>"
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
