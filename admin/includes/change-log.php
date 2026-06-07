<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="twosecondChangeLog" class="tab-pane fade<?php echo $tab == 'twosecondChangeLog' ? ' active in' : ''; ?>">
    <div class="header bsd-flex gap20">
        <div class="heading_container">
            <h4 class="bsheading"><?php esc_html_e('Change Logs', 'twosecond'); ?>
            </h4>
            <span class="twosecondinfo"><a href="https://twosecond.ai/"><?php esc_html_e('More info', 'twosecond'); ?> ?</a></span>
        </div>
        <div class="icon_container"> <img src="<?php $twoSecond_admin->core->twosecond_esc_url_echo( TWOSECOND_URL . 'assets/images/logs-icon.webp' ); ?>" ></div>
    </div>
    <hr>
    <div class="bsd-flex gap20 
        <?php echo esc_attr( $twoSecond_hidden_class ); ?>">
        <label><?php esc_html_e('Enable Settings Change Logs', 'twosecond'); ?>
            <span class="twosecondinfo"></span><span class="twosecond-info-display"><?php esc_html_e('Enable to Log Settings Change Logs.', 'twosecond'); ?></span>
        </label>
        <div class="input_box">
            <label class="switch" for="enable-change-log">
                <input type="checkbox" name="change_logs" 
                    <?php if (!empty($twoSecond_result['change_logs']) && $twoSecond_result['change_logs'] == "on") echo esc_attr( 'checked' ); ?> id="enable-change-log" >
                <div class="checked"></div>
            </label>
        </div>
    </div>
    <?php if (empty($twoSecond_result['change_logs'])) { ?>
        <p class="alert_message"><?php esc_html_e('Enable Change Log option for Settings Change Logging', 'twosecond'); ?></p>
    <?php } else { ?>
        <div class="bsd-flex gap20 filter-row">
            <div class="show_log bsd-flex gap10">
                <label for="show_change_log_entry"><?php esc_html_e('Show', 'twosecond'); ?></label>
                <select name="temp_input" id="show_change_log_entry" class="show_change_log_entry">
                    <option value="10"><?php esc_html_e('10', 'twosecond'); ?></option>
                    <option value="20"><?php esc_html_e('20', 'twosecond'); ?></option>
                    <option value="30"><?php esc_html_e('30', 'twosecond' ); ?></option>
                    <option value="40"><?php esc_html_e('40', 'twosecond'); ?></option>
                    <option value="50"><?php esc_html_e('50', 'twosecond'); ?></option>
                </select>
            </div>
            <div class="delete-log-data bsd-flex gap10">
                <label for="changelog_delete_time"><?php esc_html_e('Delete Logs', 'twosecond'); ?></label>
                <select id="changelog_delete_time" name="temp_input">
                    <option value=""><?php esc_html_e('Select Log Time', 'twosecond'); ?></option>
                    <option value="last7days"><?php esc_html_e('Keep last 7 Days', 'twosecond'); ?></option>
                    <option value="lastMonth"><?php esc_html_e('Keep last 30 Days', 'twosecond'); ?></option>
                    <option value="last3months"><?php esc_html_e('Keep last 90 Days', 'twosecond'); ?></option>
                    <option value="last6months"><?php esc_html_e('Keep last 180 Days', 'twosecond'); ?></option>
                    <option value="all"><?php esc_html_e('All', 'twosecond'); ?></option>
                </select>
                <button type="button" class="btn btn-changelog-delete"><?php esc_html_e('Delete', 'twosecond'); ?></button>
            </div>
        </div>
        <div class="bsd-flex gap10 filter-row">
            <div class="filter_by_date bsd-flex gap10">
                <label for="changelog_start_date"><?php esc_html_e('From', 'twosecond'); ?></label>
                <input type="text" name="temp_input" class="changelog_start_date">
                <label for="changelog_end_date"><?php esc_html_e('To', 'twosecond'); ?></label>
                <input type="text" name="temp_input" class="changelog_end_date">
            </div>
            <button type="button" class="btn btn-changelog-apply-filter"><?php esc_html_e('Apply Filters', 'twosecond'); ?></button>
            <button type="button" class="btn btn-changelog-rem-filter"><?php esc_html_e('Clear', 'twosecond'); ?></button>
        </div>
        <div class="change-log-data-table">
            <?php $twoSecond_admin->core->twosecond_get_change_log_data() ?>
        </div>
    <?php } ?>
</section>