<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="twosecond-cdn" class="tab-pane fade<?php echo $tab == 'twosecond-cdn' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('CDN', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a href="https://twosecond.ai/"><?php esc_html_e('More info', 'twosecond'); ?>?</a></span>
		</div>
		<div class="icon_container"> <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/general-setting-icon.webp' ); ?>" alt="CDN" >
		</div>
	</div>
	<hr>
	<?php
	$twoSecond_has_entries = !empty($twoSecond_admin->core->settings['cdn']);
	if ($twoSecond_has_entries) :
		foreach ($twoSecond_admin->core->settings['cdn'] as $twoSecond_key => $twoSecond_value) : ?>
			<div class="twosecond-cdn" data-index="<?php echo esc_attr( $twoSecond_key ); ?>">
				<div class="bsd-flex gap10">
					<label><?php esc_html_e('CDN url', 'twosecond'); ?><span class="twosecondinfo"></span><span class="twosecond-info-display"><?php esc_html_e('Enter CDN url with http or https', 'twosecond'); ?></span></label>
					<div class="cdn_input_box minus">
						<button type="button" class="Remove_twosecond_cdnentries">
							<i class="fa fa-times"></i>
						</button>
					</div>
					<div class="input_box">
						<label for="cdn-url-<?php echo esc_attr( $twoSecond_key ); ?>">
							<input type="text" name="cdn[<?php echo esc_attr( $twoSecond_key ); ?>][url]" id="cdn-url-<?php echo esc_attr( $twoSecond_key ); ?>" placeholder="<?php esc_html_e('Please Enter CDN url here', 'twosecond'); ?>" value="<?php echo esc_attr( isset($twoSecond_value['url']) ? $twoSecond_value['url'] : '' ); ?>">
						</label>
					</div>
				</div>
				<div class="bsd-flex gap20">
					<label><?php esc_html_e('Select File Types To Include', 'twosecond'); ?><span class="twosecondinfo"></span></label>
					<div class="input_box custom-dropdown">
						<input type="hidden" name="cdn[<?php echo esc_attr( $twoSecond_key ); ?>][type]" id="exclude-file-extensions-from-cdn-<?php echo esc_attr( $twoSecond_key ); ?>" value="<?php echo esc_attr( isset($twoSecond_value['type']) ? $twoSecond_value['type'] : '' ); ?>">
						<div class="selected-items" id="selected-extensions-<?php echo esc_attr( $twoSecond_key ); ?>">
							<?php
							if (!empty($twoSecond_value['type'])) {
								$twoSecond_types = explode(',', strtolower($twoSecond_value['type']));
								foreach ($twoSecond_types as $twoSecond_type) {
									$twoSecond_display_type = ucfirst($twoSecond_type); ?>
									<span class="selected-item" data-value="<?php echo esc_attr( $twoSecond_type ); ?>"><?php echo esc_attr( $twoSecond_display_type ); ?><button type="button" class="remove-item" data-value="<?php echo esc_attr( $twoSecond_type ); ?>">x</button></span>
							<?php }
							} ?>
						</div>
						<input type="text" placeholder="<?php esc_html_e('Type to filter extensions', 'twosecond'); ?>" class="dropdown-input" oninput="filterOptions(this, 'extensions-list-<?php echo esc_attr( $twoSecond_key ); ?>')" data-list-id="extensions-list-<?php echo esc_attr( $twoSecond_key ); ?>">
						<div class="dropdown-list" id="extensions-list-<?php echo esc_attr( $twoSecond_key ); ?>">
							<div class="dropdown-item select-all" data-value="all"><?php esc_html_e('Select All', 'twosecond'); ?></div>
							<div class="dropdown-item" data-value="image">Image</div>
							<div class="dropdown-item" data-value="font">Fonts</div>
							<div class="dropdown-item" data-value="js">Js</div>
							<div class="dropdown-item" data-value="css">Css</div>
							<div class="dropdown-item" data-value="audio">Audio</div>
							<div class="dropdown-item" data-value="video">Video</div>
						</div>
					</div>
				</div>
				<div class="bsd-flex gap20">
					<label><?php esc_html_e('Exclude path from cdn', 'twosecond'); ?><span class="twosecondinfo"></span><span class="twosecond-info-display"><?php esc_html_e('Enter path separated by comma which are to be excluded from CDN. For eg. (/wp-includes/)', 'twosecond'); ?></span></label>
					<div class="input_box">
						<label for="exclude-path-from-cdn-<?php echo esc_attr( $twoSecond_key ); ?>">
							<input type="text" name="cdn[<?php echo esc_attr( $twoSecond_key ); ?>][exclude_path]" id="exclude-path-from-cdn-<?php echo esc_attr( $twoSecond_key ); ?>" placeholder="<?php esc_html_e('Please Enter paths separated by comma', 'twosecond'); ?>" value="<?php echo esc_attr( isset($twoSecond_value['exclude_path']) ? $twoSecond_value['exclude_path'] : '' ); ?>">
						</label>
					</div>
				</div>
			</div>
	<?php endforeach;
	endif;
	?>
	<div class="addmore_button"><button type="button" class="add_more_cdnRows btn">Add More</button></div>
	<hr>
	<div class="save-changes bsd-flex gap10">
		<input type="submit" value="<?php esc_html_e('Save Changes', 'twosecond'); ?>" class="btn hook_submit">
		<div class="in-progress bsd-flex save-changes-loader" style="display:none">
			<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader" class="loader-img" >
		</div>
	</div>
</section>

