<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="nav nav-tabs twoSecondnav">
	<?php if (empty($twoSecond_result['manage_site_separately'])) { ?>
		<li class="twosecond_optimize_ai<?php echo $tab == 'optimizeAi' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="optimizeAi" href="javascript:void(0)">
				<?php esc_html_e('Optimize with AI', 'twosecond'); ?>
				<span class="new-feature">New</span>
			</a>
		</li>
		<li class="twosecond_html_cache<?php echo $tab == 'htmlCache' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="htmlCache" href="javascript:void(0)">
				<?php esc_html_e('HTML Cache', 'twosecond'); ?>
			</a>
		</li>
	<?php } ?>
	<li class="twosecond_general<?php echo empty($tab) || $tab == 'general' ? ' active' : ''; ?>">
		<a data-toggle="tab" data-section="general" href="javascript:void(0)">
			<?php esc_html_e('General', 'twosecond'); ?>
		</a>
	</li>
	<?php if (empty($twoSecond_result['manage_site_separately'])) { ?>
		<li class="twosecond_cdn<?php echo $tab == 'twosecond-cdn' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="twosecond-cdn" href="javascript:void(0)">
				<?php esc_html_e('CDN', 'twosecond'); ?>
			</a>
		</li>
		<li class="twosecond_opt_img<?php echo $tab == 'opt_img' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="opt_img" href="javascript:void(0)">
				<?php esc_html_e('Image Optimization', 'twosecond'); ?>
			</a>
		</li>
		<li class="twosecond_css<?php echo $tab == 'css' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="css" href="javascript:void(0)">
				<?php esc_html_e('CSS', 'twosecond'); ?>
			</a>
		</li>
		<li class="twosecond_js<?php echo $tab == 'js' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="js" href="javascript:void(0)">
				<?php esc_html_e('JavaScript', 'twosecond'); ?>
			</a>
		</li>
		<li class="twosecond_exclusions<?php echo $tab == 'exclusions' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="exclusions" href="javascript:void(0)">
				<?php esc_html_e('Exclusions', 'twosecond'); ?>
			</a>
		</li>
		<?php do_action('twosecond_after_exclusions'); ?>
		<li class="twosecond_cache<?php echo $tab == 'cache' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="cache" href="javascript:void(0)">
				<?php esc_html_e('Clear Cache', 'twosecond'); ?>
			</a>
		</li>
		<li class="twosecond_webvitals_log<?php echo $tab == 'webvitalslogs' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="webvitalslogs" href="javascript:void(0)">
				<?php esc_html_e('Web Vitals Logs', 'twosecond'); ?>
			</a>
		</li>
		<li class="twosecond_import<?php echo $tab == 'import' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="import" href="javascript:void(0)">
				<?php esc_html_e('Import/Export', 'twosecond'); ?>
			</a>
		</li>
		<li class="twosecond_change_logs<?php echo $tab == 'twosecondChangeLog' ? ' active' : ''; ?>">
			<a data-toggle="tab" data-section="twosecondChangeLog" href="javascript:void(0)">
				<?php esc_html_e('Change Logs', 'twosecond'); ?>
			</a>
		</li>
	<?php } ?>
</ul>
			