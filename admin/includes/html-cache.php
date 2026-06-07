<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="htmlCache" class="tab-pane fade<?php echo $tab == 'htmlCache' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading"><?php esc_html_e('HTML Caches', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container"> <img
				src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/html_caches-icon1.webp' ); ?>"></div>
	</div>
	<hr>
	<div class="html-cache-main">
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Enable HTML Caching', 'twosecond'); ?><span class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable to on html caching', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-html-caching">
					<input type="checkbox" name="html_caching" <?php if (!empty($twoSecond_result['html_caching']) && $twoSecond_result['html_caching'] == "on")
						echo "checked"; ?> id="enable-html-caching">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Enable caching for logged in user', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable caching for logged in user', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-caching-loggedin-user">
					<input type="checkbox" name="enable_loggedin_user_caching" <?php if (!empty($twoSecond_result['enable_loggedin_user_caching']) && $twoSecond_result['enable_loggedin_user_caching'] == "on")
						echo "checked"; ?>
						id="enable-caching-loggedin-user">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Serve html cache file by', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Check method for serve cache html file', 'twosecond'); ?></span></label>
			<div class="input_box bsd-flex gap10">
				<label class="switch" for="htaccess">
					<input value="htaccess" type="radio" name="by_serve_cache_file" <?php if (empty($twoSecond_result['by_serve_cache_file']) || $twoSecond_result['by_serve_cache_file'] == "htaccess")
						echo "checked"; ?> id="htaccess">
					<div class="checked"></div>
				</label>
				<span><?php esc_html_e('Htaccess', 'twosecond'); ?></span>
			</div>
			<div class="input_box bsd-flex gap10">
				<label class="switch" for="advanceCache">
					<input value="advanceCache" type="radio" name="by_serve_cache_file" <?php if (!empty($twoSecond_result['by_serve_cache_file']) && $twoSecond_result['by_serve_cache_file'] == "advanceCache")
						echo "checked"; ?> id="advanceCache">
					<div class="checked"></div>
				</label>
				<span><?php esc_html_e('PHP Cache', 'twosecond'); ?></span>
			</div>

		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Enable caching page with GET parameters', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable caching page with GET parameters', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-caching-page-get-para">
					<input type="checkbox" name="enable_caching_get_para" <?php if (!empty($twoSecond_result['enable_caching_get_para']) && $twoSecond_result['enable_caching_get_para'] == "on")
						echo "checked"; ?>
						id="enable-caching-page-get-para">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Cache Expiry Time', 'twosecond'); ?><span class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Input an time for cache expiry default time is 3600(1 hour)', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="html-cache-expiry bsd-flex" for="html-cache-expiry-time">
					<input type="text" name="html_caching_expiry_time"
						value="<?php echo esc_attr( !empty($twoSecond_result['html_caching_expiry_time']) ? $twoSecond_result['html_caching_expiry_time'] : '3600' ); ?>"
						id="html-cache-expiry-time" style="max-width:80px;"><small>&nbsp;
						<?php esc_html_e('*Time delay in seconds', 'twosecond'); ?></small>
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Clear Cache when Page or Post is Updated', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable to Clear Cache when Page or Post is Updated', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="clear-cache-page-post-updated">
					<input type="checkbox" name="clear_cache_page_post_updated" <?php if (!empty($twoSecond_result['clear_cache_page_post_updated']) && $twoSecond_result['clear_cache_page_post_updated'] == "on")
						echo "checked"; ?>
						id="clear-cache-page-post-updated">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Preload Caching', 'twosecond'); ?><span class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable to create preload caching', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-preload-caching">
					<input type="checkbox" name="preload_caching" <?php if (!empty($twoSecond_result['preload_caching']) && $twoSecond_result['preload_caching'] == "on")
						echo "checked"; ?> id="enable-preload-caching">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Preload page caching per minute', 'twosecond'); ?> <span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('how many pages preload per minute', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label for="pmin-url">
					<input type="number" name="preload_per_min" id="preload_per_min" min="1" max="12"
						value="<?php echo esc_attr( !empty($twoSecond_result['preload_per_min']) ? $twoSecond_result['preload_per_min'] : '1' ); ?>">
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Enable leverage browsing cache', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable to turn on leverage browsing cache.', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-leverage-browsing-cache">
					<input type="checkbox" name="lbc" id="enable-leverage-browsing-cache" <?php if (!empty($twoSecond_result['lbc']) && $twoSecond_result['lbc'] == "on")
						echo "checked"; ?>>
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Enable Gzip compression', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable to turn on Gzip compresssion.', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="enable-gzip-compression">
					<input type="checkbox" name="gzip" <?php if (!empty($twoSecond_result['gzip']) && $twoSecond_result['gzip'] == "on")
						echo "checked"; ?> id="enable-gzip-compression">
					<div class="checked"></div>
				</label>
			</div>
		</div>
			<div class="bsd-flex gap20 <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<label><?php esc_html_e('Remove query parameters', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enable to remove query parameters from resources.', 'twosecond'); ?></span></label>
			<div class="input_box">
				<label class="switch" for="remove-query-parameters">
					<input type="checkbox" name="remquery" <?php if (!empty($twoSecond_result['remquery']) && $twoSecond_result['remquery'] == "on")
						echo "checked"; ?> id="remove-query-parameters">
					<div class="checked"></div>
				</label>
			</div>
		</div>
		<hr>
		<div class="cdn_resources <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<div class="bsd-flex gap20 bsalign-item-baseline">
				<label for="cache_path"><?php esc_html_e('Cache Path', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enter path where cache can be stored. Leave empty for default path', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="cdn_input_box">
						<input type="text" name="cache_path"
							placeholder="<?php esc_html_e('Please Enter full cache path', 'twosecond'); ?>"
							value="<?php echo esc_attr( !empty($twoSecond_result['cache_path']) ? $twoSecond_result['cache_path'] : '' ); ?>"
							id="cache_path"
							placeholder="<?php esc_html_e('Please Enter full cache path', 'twosecond'); ?>">
						<small
							class="bsd-block"><?php esc_html_e('Default cache path:', 'twosecond'); ?>
							<?php echo esc_html( $twoSecond_admin->core->addSettings['content_path'] . '/cache' ); ?>
						</small>
					</div>
				</div>

			</div>
		</div>
		<hr>
		<div class="save-changes bsd-flex gap10">
			<input type="button" value="<?php esc_html_e('Save Changes', 'twosecond'); ?>" class="btn hook_submit">
			<div class="in-progress bsd-flex save-changes-loader" style="display:none">
				<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
					class="loader-img">
			</div>
		</div>
	</div>

</section>