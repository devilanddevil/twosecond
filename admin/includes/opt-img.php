<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="opt_img" class="tab-pane fade<?php echo $tab == 'opt_img' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('Image Optimization', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/#img_optimization"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container"> <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/image-icon.webp' ); ?>" alt="Image Optimization"></div>
	</div>
	<hr>
	<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
		<label><?php esc_html_e('Convert to Webp', 'twosecond'); ?><span class="twosecondinfo"></span><span
				class="twosecond-info-display"><?php esc_html_e('This will convert and render images in webp. Need to start image optimization in image optimization tab', 'twosecond'); ?></span></label>
		<div class="bsd-flex">
			<label for="jpg"><?php esc_html_e('JPG', 'twosecond'); ?>&nbsp;</label>
			<input type="checkbox" name="webp_jpg" <?php if (!empty($twoSecond_result['webp_jpg']) && $twoSecond_result['webp_jpg'] == "on")
														echo "checked"; ?> id="jpg" class="main-opt-img">
		</div>
		<div class="bsd-flex">
			<label for="png"><?php esc_html_e('PNG', 'twosecond'); ?>&nbsp;</label>
			<input type="checkbox" name="webp_png" <?php if (!empty($twoSecond_result['webp_png']) && $twoSecond_result['webp_png'] == "on")
														echo "checked"; ?> id="png" class="main-opt-img">
		</div>
	</div>

	<div class="bsd-flex gap20 
		<?php echo esc_attr( $twoSecond_hidden_class ); ?>">
		<label><?php esc_html_e('Enable Lazy Load', 'twosecond'); ?><span class="twosecondinfo"></span><span
				class="twosecond-info-display"><?php esc_html_e('This will enable lazy loading of resources.', 'twosecond'); ?></span></label>
		<div class="bsd-flex">
			<label for="image"><?php esc_html_e('Image', 'twosecond'); ?>&nbsp;</label>
			<input type="checkbox" name="lazy_load" <?php if (!empty($twoSecond_result['lazy_load']) && $twoSecond_result['lazy_load'] == "on")
														echo "checked"; ?> id="image">
		</div>
		<div class="bsd-flex">
			<label for="iframe"><?php esc_html_e('Iframe', 'twosecond'); ?>&nbsp;</label>
			<input type="checkbox" name="lazy_load_iframe" <?php if (!empty($twoSecond_result['lazy_load_iframe']) && $twoSecond_result['lazy_load_iframe'] == "on")
																echo "checked"; ?> id="iframe">
		</div>
		<div class="bsd-flex">
			<label for="video"><?php esc_html_e('Video', 'twosecond'); ?>&nbsp;</label>
			<input type="checkbox" name="lazy_load_video" <?php if (!empty($twoSecond_result['lazy_load_video']) && $twoSecond_result['lazy_load_video'] == "on")
																echo "checked"; ?> id="video">
		</div>
		<div class="bsd-flex">
			<label for="audio"><?php esc_html_e('Audio', 'twosecond'); ?>&nbsp;</label>
			<input type="checkbox" name="lazy_load_audio" <?php if (!empty($twoSecond_result['lazy_load_audio']) && $twoSecond_result['lazy_load_audio'] == "on")
																echo "checked"; ?> id="audio">
		</div>
	</div>

	<div class="bsd-flex gap20 
		<?php echo esc_attr( $twoSecond_hidden_class ); ?>">
		<label><?php esc_html_e('Load SVG Inline Tag as URL', 'twosecond'); ?><span
				class="twosecondinfo"></span><span
				class="twosecond-info-display"><?php esc_html_e('Load SVG inline tag as url to avoid large DOM elements', 'twosecond'); ?></span></label>
		<div class="input_box">
			<label class="switch" for="load-inline-svg-tag-url">
				<input type="checkbox" name="inlineToUrlSVG" <?php if (!empty($twoSecond_result['inlineToUrlSVG']) && $twoSecond_result['inlineToUrlSVG'] == "on") echo "checked"; ?> id="load-inline-svg-tag-url">
				<div class="checked"></div>
			</label>
		</div>
	</div>
	<div class="bsd-flex gap20 
			<?php echo esc_attr( $twoSecond_hidden_class ); ?>">
		<label><?php esc_html_e('Responsive Images', 'twosecond'); ?><span class="twosecondinfo"></span><span
				class="twosecond-info-display"><?php esc_html_e('Load smaller images on mobile to reduce load time', 'twosecond'); ?></span></label>
		<div class="input_box">
			<label class="switch" for="resp-imgs">
				<input type="checkbox" name="resp_bg_img" <?php if (!empty($twoSecond_result['resp_bg_img']) && $twoSecond_result['resp_bg_img'] == "on")
																echo "checked"; ?> id="resp-imgs">
				<div class="checked"></div>
			</label>
		</div>
	</div>
	<?php
	if (empty($twoSecond_result['license_key']) || empty($twoSecond_result['is_activated'])) {
		echo '<span class="non_licensed"><strong class="bstext-danger">* Starting 500 images will be optimized </strong><br><br><a href="https://twosecond.ai/" class="bstext-success"><strong>*<u>GO PRO</u> </strong></a> </span><br></br>';
	}
	?>
	<hr>
	<div class="save-changes bsd-flex gap10">
		<input type="button" value="<?php esc_html_e('Save Changes', 'twosecond'); ?>"
			class="btn hook_submit gen">
		<div class="in-progress bsd-flex save-changes-loader" style="display:none">
			<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
				class="loader-img">
		</div>
	</div>
</section>