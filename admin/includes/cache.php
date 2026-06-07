<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="cache" class="tab-pane fade<?php echo $tab == 'cache' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('Cache', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/#Cache"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container"> <img
				src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/caches-icon.webp' ); ?>" alt="Cache" >
			</div>
	</div>
	<hr>
	<div class="caches_box">
		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Delete HTML cache', 'twosecond'); ?><span class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Delete HTML cache when you do any changes', 'twosecond'); ?></span></label>
			<button class="btn" type="button" id="del_html_cache">
				<?php esc_html_e('Delete Now', 'twosecond'); ?>
			</button>
			<div class="in-progress bsd-flex delete_html_cache" style="display:none">
				<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
					class="loader-img">
				<small
					class="extra-small m-0">&nbsp;<em>&nbsp;<?php esc_html_e('Deleting HTML Cache...', 'twosecond'); ?></em></small>
			</div>
		</div>
		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Delete HTML/JS/CSS Cache', 'twosecond'); ?><span class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Delete Html, javascript and css combined and minified cache files', 'twosecond'); ?></span></label>
			<button class="btn" type="button" id="del_js_css_cache">
				<?php esc_html_e('Delete Now', 'twosecond'); ?>
			</button>
			<div class="in-progress bsd-flex delete_css_js_cache" style="display:none">
				<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
					class="loader-img">
				<small
					class="extra-small m-0">&nbsp;<em>&nbsp;<?php esc_html_e('Deleting HTML/JS/Css Cache...', 'twosecond'); ?></em></small>
			</div>
		</div>
		<div class="bsd-flex gap20 ">
			<label><?php esc_html_e('Delete critical css', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Delete critical css only when you have made any changes to style. This may take considerable amount of time to regenerate depending upon the pages on the site', 'twosecond'); ?></span></label>
			<button class="btn" type="button" id="del_critical_css_cache">
				<?php esc_html_e('Delete Now', 'twosecond'); ?>
			</button>
			<div class="in-progress bsd-flex delete_critical_css_cache" style="display:none">
				<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
					class="loader-img">
				<small
					class="extra-small m-0">&nbsp;<em>&nbsp;<?php esc_html_e('Deleting Critical Css Cache...', 'twosecond'); ?></em></small>
			</div>
		</div>
	</div>
</section>
