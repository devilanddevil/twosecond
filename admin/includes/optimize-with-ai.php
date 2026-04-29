<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="optimizeAi" class="tab-pane fade">
    <div class="header bsd-flex gap20">
        <div class="heading_container">
            <h4 class="bsheading">Optimize with AI</h4>
            <span class="twosecondinfo">
                <a href="https://twosecond.ai/">More info ?</a>
            </span>
        </div>
        <div class="icon_container">
            <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/optimize-ai-icon.png' ); ?>" alt="Optimize with AI">
        </div>
    </div>
    <hr>
    <div class="bsd-flex gap20 ">
        <label>
            <?php esc_html_e('Enable AI Optimization', 'twosecond'); ?>
            <span class="twosecondinfo"></span>
            <span class="twosecond-info-display"><?php esc_html_e('Automatically optimize your website with AI-powered performance enhancements', 'twosecond'); ?>
            </span>
        </label>
        <div class="input_box">
            <label class="switch" for="enable-ai-optimization">
                <input type="checkbox" name="ai-optimization" <?php if (!empty($twoSecond_result['ai-optimization']) && $twoSecond_result['ai-optimization'] == "on")
					echo esc_attr( 'checked' ); ?> id="enable-ai-optimization">
                <div class="checked"></div>
            </label>
        </div>
        <div class="bsd-flex gap10 reverse">
            <button type="button" class="btn" id="twosecond_restart_optimization"><?php esc_html_e('Reset', 'twosecond'); ?></button>
            <div class="in-progress bsd-flex" id="restart-optimization-loader" style="display:none">
                <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader" class="loader-img">
            </div>
        </div>
    </div>
    <?php if (!empty($twoSecond_result['ai-optimization']) && $twoSecond_result['ai-optimization'] == "on") : ?>
    <div class="enable-optimizeAi">
        <div class="bsd-flex gap20">
            <label for="" class="bsd-flex mb-10">
                <?php esc_html_e('Optimization Progress', 'twosecond'); ?>
            </label>
        </div>
        <div class="progress-bar-bg optimize-bar-bg">
            <div class="progress progress-bar progress-bar-striped bsbg-success progress-bar-animated-img optimize-bar" id="optimize-ai-bar"></div>
        </div>
        <div class="bsd-flex gap20 justify-space-between">
            <span class="extra_display" id="optimize-pages-count">
                <?php esc_html_e('Loading...', 'twosecond'); ?>
            </span>
            <span class="extra_display" id="optimize-remaining-time">
                <?php esc_html_e('Estimated time: Loading...', 'twosecond'); ?>
            </span>
        </div>
    </div>
    <div class="bsd-flex gap20 ">
        <label>
            <?php esc_html_e('Pages per Minutes', 'twosecond'); ?>
            <span class="twosecondinfo"></span>
            <span class="twosecond-info-display"><?php esc_html_e('Number of pages to optimize simultaneously', 'twosecond'); ?></span>
        </label>
        <div class="input_box">
            <label class="html-cache-expiry bsd-flex" for="page-batch">
                <input type="number" name="page_batch" value="<?php echo esc_attr( !empty($twoSecond_result['page_batch']) ? $twoSecond_result['page_batch'] : (!empty($twoSecond_result['preload_per_min']) ? $twoSecond_result['preload_per_min'] : '1') ); ?>" id="page-batch"  max="20"
                    style="max-width:80px;">
                <small>&nbsp; <?php esc_html_e('Max 20 pages', 'twosecond'); ?></small>
                <div class="checked"></div>
            </label>
        </div>
        <div class="bsd-flex gap10 reverse">
            <input type="button" value="<?php esc_html_e('Save', 'twosecond'); ?>" class="btn hook_submit">
            <div class="in-progress bsd-flex save-changes-loader" style="display:none">
                <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/loader-gif.gif' ); ?>" alt="loader" class="loader-img">
            </div>
        </div>
    </div>

    <div class="bsd-flex gap20 filter-row">
        <div class="filter-list optai-filter-list bsd-flex gap10">
            <label for="filter-optai-rows"><?php esc_html_e('Rows Per Page', 'twosecond'); ?></label>
            <select id="filter-optai-rows" name="temp_input">
                <option value="10" selected><?php esc_html_e('10', 'twosecond'); ?></option>
                <option value="20"><?php esc_html_e('20', 'twosecond'); ?></option>
                <option value="50"><?php esc_html_e('50', 'twosecond'); ?></option>
                <option value="100"><?php esc_html_e('100', 'twosecond'); ?></option>
            </select>
        </div>
        <div class="filter-list optai-filter-list bsd-flex gap10">
            <label for="filter-optai-status"><?php esc_html_e('Status', 'twosecond'); ?></label>
            <select id="filter-optai-status" name="filter-optai-status">
                <option value="all" selected><?php esc_html_e('All', 'twosecond'); ?></option>
                <option value="pending"><?php esc_html_e('Pending', 'twosecond'); ?></option>
                <option value="in-progress"><?php esc_html_e('In-progress', 'twosecond'); ?></option>
                <option value="error"><?php esc_html_e('Error', 'twosecond'); ?></option>
                <option value="done"><?php esc_html_e('Done', 'twosecond'); ?></option>
            </select>
        </div>
        <div class="filter-list optai-filter-list bsd-flex gap10">
            <label for="filter-optai-url"><?php esc_html_e('Url', 'twosecond'); ?></label>
            <input type="text" id="filter-optai-url" name="filter-optai-url" placeholder="<?php esc_html_e('Enter matching url...', 'twosecond'); ?>">
            <button type="button" class="btn" id="opt-ai-filter-btn"><?php esc_html_e('Filter', 'twosecond'); ?></button>
        </div>
    </div>
    <div class="optimize-ai-data-table" id="optimize-ai-data-table">
        <table class="optimize-url-table">
            <thead>
				<tr>
					<th><?php esc_html_e('URL', 'twosecond'); ?></th>
					<th style="width: 10%;"><?php esc_html_e('Desktop', 'twosecond'); ?></th>
					<th style="width: 10%;"><?php esc_html_e('Mobile', 'twosecond'); ?></th>
					<th style="width: 12%;"><?php esc_html_e('Timestamp', 'twosecond'); ?></th>
					<th style="width: 10%;"><?php esc_html_e('Actions', 'twosecond'); ?></th>
				</tr>
			</thead>
            <tbody>
                <tr>
                    <td colspan="4" style="text-align: center;"><?php esc_html_e('Loading...', 'twosecond'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="ai-optimize-container">
        <div class="ai-optimize-banner">
            <div class="ai-optimize-icon">
            <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/optimize-ai-icon.png' ); ?>" alt="AI Icon">
            </div>
            <div class="ai-optimize-text">
            <h3><?php esc_html_e('Note !', 'twosecond'); ?></h3>
            <p><?php esc_html_e('Enabling Optimize with AI will automatically apply the recommended optimal settings for your website performance.', 'twosecond'); ?></p>
            </div>
        </div>
        <h4 class="ai-optimize-title"><?php esc_html_e('The following optimal settings will be enabled:', 'twosecond'); ?></h4>
        <ul class="ai-optimize-list">
            <li><span class="opt-icon">✅ <?php esc_html_e('Enable HTML Caching', 'twosecond'); ?></span></li>
            <li><span class="opt-icon">✅ <?php esc_html_e('Turn ON optimization', 'twosecond'); ?></span></li>
            <li><span class="opt-icon">✅ <?php esc_html_e('JPG to WebP Image Conversion', 'twosecond'); ?></span></li>
            <li><span class="opt-icon">✅ <?php esc_html_e('PNG to WebP Image Conversion', 'twosecond'); ?></span></li>
            <li><span class="opt-icon">✅ <?php esc_html_e('Enable Lazy Load (Image, Iframe, Video, Audio', 'twosecond'); ?></span></li>
            <li><span class="opt-icon">✅ <?php esc_html_e('Enable CSS Optimization', 'twosecond'); ?></span></li>
            <li><span class="opt-icon">✅ <?php esc_html_e('Load Critical CSS', 'twosecond'); ?></span></li>
        </ul>
    </div>
    <?php endif; ?>
</section>