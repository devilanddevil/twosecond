<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="exclusions" class="tab-pane fade<?php echo $tab == 'exclusions' ? ' active in' : ''; ?>">
	<div class="header bsd-flex gap20">
		<div class="heading_container">
			<h4 class="bsheading">
				<?php esc_html_e('Exclusions', 'twosecond'); ?>
			</h4>
			<span class="twosecondinfo"><a
					href="https://twosecond.ai/"><?php esc_html_e('More info', 'twosecond'); ?>?
				</a></span>
		</div>
		<div class="icon_container"> 
			<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/exclusions-icon1.webp' ); ?>">
		</div>
	</div>
	<hr>
	<div class="way_to_psi">
	<details>
		<summary>
			<h4 class="bsheading bstext-skyblue"><?php esc_html_e('Media Exclusions (Image, Video, Audio)', 'twosecond'); ?></h4>
		</summary>
		<div class="cdn_resources <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<div class="bsd-flex gap20 bsalign-item-baseline">
				<label
					for="Preload Media"><?php esc_html_e('Preload Media', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enter url of the Resources, which are to be preloaded..', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="single-row">
						<?php
						if (!empty($twoSecond_result['preload_resources']) && is_array($twoSecond_result['preload_resources'])) {
							foreach ($twoSecond_result['preload_resources'] as $twoSecond_row) {
								if (!empty(trim($twoSecond_row))) {
									?>
									<div class="cdn_input_box minus bsd-flex">
										<input type="text" name="preload_resources[]"
											value="<?php echo esc_attr( rtrim( $twoSecond_row ) ); ?>"
											placeholder="<?php esc_html_e('Please Enter Resource Url', 'twosecond'); ?>"><button
											type="button" class="bstext-white rem-row bsbg-danger"><i
												class="fa fa-times"></i></button>
									</div>
									<?php
								}
							}
						} ?>
					</div>
					<div class="cdn_input_box plus">
						<button type="button" data-name="preload_resources"
							data-placeholder="<?php esc_html_e('Please Enter Resource Url', 'twosecond'); ?>"
							class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
					</div>

				</div>

			</div>
		</div>

		<!-- <hr> -->
		<div class="cdn_resources 
			<?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<div class="bsd-flex gap20 bsalign-item-baseline">
				<label
					for="Exclude Media from Lazy Loading"><?php esc_html_e('Exclude Media from Lazy Loading', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enter any matching text of image/iframe/video/audio tag to exclude from lazy loading. For more than one exclusion, click on add rule. For eg. (class / Id / url / alt).', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="single-row">
						<?php
						if (!empty($twoSecond_result['exclude_lazy_load']) && is_array($twoSecond_result['exclude_lazy_load'])) {
							foreach ($twoSecond_result['exclude_lazy_load'] as $twoSecond_row) {
								if (!empty(trim($twoSecond_row))) {
									?>
									<div class="cdn_input_box minus bsd-flex">
										<input type="text" name="exclude_lazy_load[]"
											value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
											placeholder="<?php esc_html_e('Please Enter matching text of the image here', 'twosecond'); ?>"><button
											type="button" class="bstext-white rem-row bsbg-danger"><i
												class="fa fa-times"></i></button>
									</div>
									<?php
								}
							}
						} ?>

					</div>
					<div class="cdn_input_box plus">
						<button type="button" data-name="exclude_lazy_load"
							data-placeholder="<?php esc_html_e('Please Enter matching text of the image here', 'twosecond'); ?>"
							class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
					</div>

				</div>

			</div>
		</div>
	</details>
	</div>
	<hr>
	<div class="way_to_psi">
	<details>
		<summary>
			<h4 class="bsheading bstext-skyblue"><?php esc_html_e('CSS Exclusions', 'twosecond'); ?></h4>
		</summary>

	<div class="css_box cdn_resources ">
		<div class="bsd-flex gap20 bsalign-item-baseline">
			<label><?php esc_html_e('Exclude Stylesheet URLs from Optimization', 'twosecond'); ?><span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enter matching text of css link url, which are to be excluded from css optimization. For each Exclusion, click on add rule', 'twosecond'); ?></span></label>
			<div class="input_box">
				<div class="single-row">
					<?php
					if (!empty($twoSecond_result['exclude_css']) && is_array($twoSecond_result['exclude_css'])) {
						foreach ($twoSecond_result['exclude_css'] as $twoSecond_row) {
							if (!empty(trim($twoSecond_row))) {
								?>
								<div class="cdn_input_box minus bsd-flex">
									<input type="text" name="exclude_css[]" 
										value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
										placeholder="<?php esc_html_e('Please Enter part of Stylesheet URL here', 'twosecond'); ?>"><button
										type="button" class="bstext-white rem-row bsbg-danger"><i
											class="fa fa-times"></i></button>
								</div>
								<?php
							}
						}
					} ?>
				</div>
				<div class="cdn_input_box plus">
					<button type="button" data-name="exclude_css"
						data-placeholder="<?php esc_html_e('Please Enter part of Stylesheet URL here', 'twosecond'); ?>"
						class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
				</div>
			</div>
		</div>

	</div>
	<!-- <hr> -->

	<div class="css_box cdn_resources">
		<div class="bsd-flex gap20 bsalign-item-baseline">
			<label><?php esc_html_e('Force Lazy Load Stylesheet URLs', 'twosecond'); ?> <span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enter matching text of css link url, which are forced to be lazyloaded and will load on user interaction. For each Exclusion, click on add rule.', 'twosecond'); ?></span></label>
			<div class="input_box">
				<div class="single-row">
					<?php
					if (!empty($twoSecond_result['force_lazyload_css']) && is_array($twoSecond_result['force_lazyload_css'])) {
						foreach ($twoSecond_result['force_lazyload_css'] as $twoSecond_row) {
							if (!empty(trim($twoSecond_row))) {
								?>
								<div class="cdn_input_box minus bsd-flex">
									<input type="text" name="force_lazyload_css[]"
										value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
										placeholder="<?php esc_html_e('Please Enter part of Stylesheet URL here', 'twosecond'); ?>"><button
										type="button" class="bstext-white rem-row bsbg-danger"><i
											class="fa fa-times"></i></button>
								</div>
								<?php
							}
						}
					} ?>
				</div>
				<div class="cdn_input_box plus">
					<button type="button" data-name="force_lazyload_css"
						data-placeholder="<?php esc_html_e('Please Enter part of Stylesheet URL here', 'twosecond'); ?>"
						class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
				</div>
			</div>
		</div>
	</div>
	<!-- <hr> -->
	
	</details>
	</div>
	<hr>
	<div class="way_to_psi">
	<details>
		<summary>
			<h4 class="bsheading bstext-skyblue"><?php esc_html_e('JS Exclusions', 'twosecond'); ?></h4>
		</summary>
		<div class="js_box cdn_resources">
			<div class="bsd-flex gap20 bsalign-item-baseline">
				<label><?php esc_html_e('Force Lazy Load Javascript', 'twosecond'); ?> <span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enter matching text of inline javascript which needs to be forced to lazyload. For each Exclusion, click on add rule.', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="single-row">
						<?php
						if (!empty($twoSecond_result['force_lazy_load_inner_javascript']) && is_array($twoSecond_result['force_lazy_load_inner_javascript'])) {
							foreach ($twoSecond_result['force_lazy_load_inner_javascript'] as $twoSecond_row) {
								if (!empty(trim($twoSecond_row))) {
									?>
									<div class="cdn_input_box minus bsd-flex">
										<input type="text" name="force_lazy_load_inner_javascript[]"
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
						<button type="button" data-name="force_lazy_load_inner_javascript"
							data-placeholder="<?php esc_html_e('Please Enter matching text of the inline javascript here', 'twosecond'); ?>"
							class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
					</div>

				</div>
			</div>
		</div>
		<!-- <hr> -->
		<div class="js_box cdn_resources">
			<div class="bsd-flex gap20 bsalign-item-baseline">
				<label><?php esc_html_e('Exclude Javascript from Lazyload', 'twosecond'); ?> <span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enter matching text of javascript url, which are to be excluded from javascript optimization. For each Exclusion, click on add rule.', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="single-row">
						<?php
						if (!empty($twoSecond_result['exclude_both_javascript']) && is_array($twoSecond_result['exclude_both_javascript'])) {
							foreach ($twoSecond_result['exclude_both_javascript'] as $twoSecond_row) {
								if (!empty(trim($twoSecond_row))) {
									?>
									<div class="cdn_input_box minus bsd-flex">
										<input type="text" name="exclude_both_javascript[]"
											value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
											placeholder="<?php esc_html_e('Please Enter matching text of the javascript here', 'twosecond'); ?>"><button
											type="button" class="bstext-white rem-row bsbg-danger"><i
												class="fa fa-times"></i></button>
									</div>
									<?php
								}
							}
						} ?>
					</div>
					<div class="cdn_input_box plus">
						<button type="button" data-name="exclude_both_javascript"
							data-placeholder="<?php esc_html_e('Please Enter matching text of the javascript here', 'twosecond'); ?>"
							class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
					</div>

				</div>
			</div>
		</div>
		<!-- <hr> -->
	</details>
	</div>
	<hr>
	<div class="way_to_psi">
	<details>
		<summary>
			<h4 class="bsheading bstext-skyblue"><?php esc_html_e('Pages Exclusions', 'twosecond'); ?></h4>
		</summary>
		<div class="cdn_resources 
			<?php echo esc_attr( $twoSecond_hidden_class ); ?>">
			<div class="bsd-flex gap20 html-cache-row">
				<label for="Preload Resources"><?php esc_html_e('Exclude pages from HTML caching', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Dont cache the url which match rule', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="single-row">
						<?php
						if (!empty($twoSecond_result['exclude_url_exclusions_html_cache']) && is_array($twoSecond_result['exclude_url_exclusions_html_cache'])) {
							foreach ($twoSecond_result['exclude_url_exclusions_html_cache'] as $twoSecond_row) {
								if (!empty(trim($twoSecond_row))) {
									?>
									<div class="cdn_input_box minus bsd-flex">
										<input type="text" name="exclude_url_exclusions_html_cache[]"
											value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
											placeholder="<?php esc_html_e('Please Enter Url/String', 'twosecond'); ?>"><button
											type="button" class="bstext-white rem-row bsbg-danger"><i
												class="fa fa-times"></i></button>
									</div>
									<?php
								}
							}
						} ?>
					</div>
					<div class="cdn_input_box plus">
						<button type="button" data-name="exclude_url_exclusions_html_cache"
							data-placeholder="<?php esc_html_e('Please Enter Url/String', 'twosecond'); ?>"
							class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
					</div>

				</div>

			</div>
			<div class="bsd-flex gap20 bsalign-item-baseline">
				<label
					for="Exclude Pages From Optimization"><?php esc_html_e('Exclude Pages From Optimization', 'twosecond'); ?><span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enter slug of the url to exclude from optimization. For  eg. (/blog/). For home page, enter home url. For each Exclusion, click on add rule', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="single-row">
						<?php
						if (!empty($twoSecond_result['exclude_pages_from_optimization']) && is_array($twoSecond_result['exclude_pages_from_optimization'])) {
							foreach ($twoSecond_result['exclude_pages_from_optimization'] as $twoSecond_row) {
								if (!empty(trim($twoSecond_row))) {
									?>
									<div class="cdn_input_box minus bsd-flex">
										<input type="text" name="exclude_pages_from_optimization[]"
											value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
											placeholder="<?php esc_html_e('Please Enter Page Url', 'twosecond'); ?>"><button
											type="button" class="bstext-white rem-row bsbg-danger"><i
												class="fa fa-times"></i></button>
									</div>
									<?php
								}
							}
						}
						?>

					</div>
					<div class="cdn_input_box plus">
						<button type="button" data-name="exclude_pages_from_optimization"
							data-placeholder="<?php esc_html_e('Please Enter Page Url', 'twosecond'); ?>"
							class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
					</div>

				</div>

			</div>
		</div>
		<div class="css_box cdn_resources">
		<div class="bsd-flex gap20 bsalign-item-baseline">
			<label><?php esc_html_e('Exclude Pages from CSS Optimization', 'twosecond'); ?> <span
					class="twosecondinfo"></span><span
					class="twosecond-info-display"><?php esc_html_e('Enter slug of the page to exclude from css optimization', 'twosecond'); ?></span></label>
			<div class="input_box">
				<div class="single-row">
					<?php
					if (!empty($twoSecond_result['exclude_page_from_load_combined_css']) && is_array($twoSecond_result['exclude_page_from_load_combined_css'])) {
						foreach ($twoSecond_result['exclude_page_from_load_combined_css'] as $twoSecond_row) {
							if (!empty(trim($twoSecond_row))) {
								?>
								<div class="cdn_input_box minus bsd-flex">
									<input type="text" name="exclude_page_from_load_combined_css[]"
										value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
										placeholder="<?php esc_html_e('Please Enter Page Url', 'twosecond'); ?>"><button
										type="button" class="bstext-white rem-row bsbg-danger"><i
											class="fa fa-times"></i></button>
								</div>
								<?php
							}
						}
					} ?>
				</div>
				<div class="cdn_input_box plus">
					<button type="button" data-name="exclude_page_from_load_combined_css"
						data-placeholder="<?php esc_html_e('Please Enter Page Url', 'twosecond'); ?>"
						class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
				</div>
			</div>
		</div>
		</div>
		<div class="js_box cdn_resources">
			<div class="bsd-flex gap20 bsalign-item-baseline">
				<label><?php esc_html_e('Exclude Pages from Javascript Optimization', 'twosecond'); ?> <span
						class="twosecondinfo"></span><span
						class="twosecond-info-display"><?php esc_html_e('Enter slug of the page to exclude from javascript optimization', 'twosecond'); ?></span></label>
				<div class="input_box">
					<div class="single-row">
						<?php
						if (!empty($twoSecond_result['exclude_page_from_load_combined_js']) && is_array($twoSecond_result['exclude_page_from_load_combined_js'])) {
							foreach ($twoSecond_result['exclude_page_from_load_combined_js'] as $twoSecond_row) {
								if (!empty(trim($twoSecond_row))) {
									?>
									<div class="cdn_input_box minus bsd-flex">
										<input type="text" name="exclude_page_from_load_combined_js[]"
											value="<?php echo esc_attr( trim( $twoSecond_row ) ); ?>"
											placeholder="<?php esc_html_e('Please Enter Js Page Url', 'twosecond'); ?>"><button
											type="button" class="bstext-white rem-row bsbg-danger"><i
												class="fa fa-times"></i></button>
									</div>
									<?php
								}
							}
						} ?>
					</div>
					<div class="cdn_input_box plus">
						<button type="button" data-name="exclude_page_from_load_combined_js"
							data-placeholder="<?php esc_html_e('Please Enter Js Page Url', 'twosecond'); ?>"
							class="btn small bstext-white bsbg-success add_more_row"><?php esc_html_e('Add Rule', 'twosecond'); ?></button>
					</div>

				</div>
			</div>
		</div>
	</details>
	</div>
	<hr>
	<div class="single-hook_btn">
		<div class="save-changes bsd-flex gap10">
			<input type="button" value="<?php esc_html_e('Save Changes', 'twosecond'); ?>" class="btn hook_submit">
			<div class="in-progress bsd-flex save-changes-loader" style="display:none">
				<img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader"
					class="loader-img">
			</div>
		</div>
	</div>
</section>
