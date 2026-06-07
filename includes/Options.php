<?php
/**
 * Options Class
 *
 * @package TwoSecond
 */


namespace TwoSecond;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options Class
 */
class Options {

	/**
	 * Set default options
	 */
	public static function set_defaults() {
		$default_options = self::get_default_options();
		
		// Set main options
		if ( ! get_option( 'twosecond_option' ) ) {
			update_option( 'twosecond_option', $default_options );
		}
		
		// Set other default options
		if ( ! get_option( 'twosecond_filesize' ) ) {
			update_option( 'twosecond_filesize', 0 );
		}
		
		if ( ! get_option( 'twosecond_rand_key' ) ) {
			update_option( 'twosecond_rand_key', wp_generate_password( 32, false ) );
		}
		
		// Set multisite options if applicable
		if ( is_multisite() ) {
			if ( ! get_site_option( 'twosecond_option' ) ) {
				update_site_option( 'twosecond_option', $default_options );
			}
		}
	}

	/**
	 * Get default options
	 *
	 * @return array
	 */
	public static function get_default_options() {
		return array(
			'license_key' => '',
			'twosecondApiUrl' => '',
			'is_activated' => '',
			'optimization_on' => 'on',
			'enable_inp' => '',
			'cdn' => '',
			'exclude_cdn' => '',
			'css' => 'on',
			'load_critical_css' => 'on',
			'load_critical_css_style_tag' => 'on',
			'js' => 'on',
			'load_combined_js' => 'after_page_load',
			'preload_resources' => '',
			'exclude_lazy_load' => '',
			'exclude_pages_from_optimization' => '',
			'exclude_css' => '',
			'force_lazyload_css' => '',
			'force_lazy_load_inner_javascript' => '',
			'exclude_both_javascript' => ['google-analytics', 'hbspt', 'logged-in'],
			'exclude_page_from_load_combined_css' => '',
			'html_caching' => 'on',
			'by_serve_cache_file' => 'htaccess',
			'html_caching_expiry_time' => '3600',
			'preload_per_min' => '12',
			'cache_path' => '',
			'webp_jpg' => 'on',
			'webp_png' => 'on',
			'lazy_load' => 'on',
			'lazy_load_iframe' => 'on',
			'lazy_load_video' => 'on',
			'remquery' => '',
			'manage_site_separately' => '',
			'enable_loggedin_user_caching' => '',
			'enable_caching_get_para' => '',
			'clear_cache_page_post_updated' => '',
			'preload_caching' => '',
			'lbc' => '',
			'gzip' => '',
			'optimize_user_logged_in' => '',
		);
	}
}
