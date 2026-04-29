<?php
/**
 * Admin Class
 *
 * @package TwoSecond
 */

namespace TwoSecond\Admin;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Class
 */
class Admin {

	/**.
	 *
	 * @var bool
	 */
	private static $twosecond_admin_hooks_registered = false;

	/**
	 * Core instance
	 *
	 * @var \TwoSecond\Core
	 */
	public $core;
	/**
	 * Settings instance
	 *
	 * @var \TwoSecond\Admin\Settings
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once TWOSECOND_DIR . 'includes/TwoSecond.php';
		$this->core = new \TwoSecond\TwoSecond();
		$this->twosecond_init_hooks();
		$this->handle_version_upgrade();
	}

	/**
	 * Initialize hooks
	 */
	private function twosecond_init_hooks() {
		if ( self::$twosecond_admin_hooks_registered ) {
			return;
		}
		self::$twosecond_admin_hooks_registered = true;

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'network_admin_menu', array( $this, 'add_network_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'twosecond_enqueue_global_admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'twosecond_enqueue_settings_page_assets' ), 9999 );
		// Add admin notices
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		
		// Add settings page
		add_action( 'admin_init', array( $this, 'twosecond_init_settings' ) );
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function twosecond_enqueue_global_admin_assets() {
		wp_enqueue_style(
			'twosecond-admin-style',
			TWOSECOND_URL . 'assets/css/admin-style.css',
			array(),
			TWOSECOND_VERSION
		);

		wp_enqueue_script(
			'twosecond-admin-bar-script',
			TWOSECOND_URL . 'assets/js/ts-admin-bar.js',
			array( 'jquery' ),
			TWOSECOND_VERSION,
			true
		);
	}

	/**
	 * Enqueue scripts and styles for the TwoSecond settings screen only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function twosecond_enqueue_settings_page_assets( $hook_suffix ) {
		if ( 'toplevel_page_twosecond' !== $hook_suffix ) {
			return;
		}

		add_action( 'admin_head', array( $this->core, 'twosecond_enqueue_admin_head' ) );

		$this->core->twosecond_enqueue_admin_scripts();
	}
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		if ( ! $this->core->addSettings['can_access_settings'] ) {
			return;
		}

		if($this->core->addSettings['is_multisite']){
			global $twoSecond_network_option;
			$options = !empty($twoSecond_network_option) ? $twoSecond_network_option : get_site_option('twosecond_option', true);
		} else {
			$options = $this->core->twosecond_get_settings();
		}
		
		if ( ! $this->core->addSettings['is_multisite'] || ( $this->core->addSettings['is_multisite'] && ! empty( $options['manage_site_separately'] ) ) ) {
			add_menu_page(
				__( 'TwoSecond', 'twosecond' ),
				__( 'TwoSecond', 'twosecond' ),
				'manage_options',
				'twosecond',
				array( $this, 'render_settings_page' ),
				TWOSECOND_URL . 'assets/images/transparent-logo.png',
				81
			);
		}
	}

	/**
	 * Add network menu
	 */
	public function add_network_menu() {
		if ( ! $this->core->addSettings['can_access_settings'] ) {
			return;
		}

		add_menu_page(
			__( 'TwoSecond', 'twosecond' ),
			__( 'TwoSecond', 'twosecond' ),
			'twosecond_settings',
			'twosecond',
			array( $this, 'render_settings_page' ),
			TWOSECOND_URL . 'assets/images/transparent-logo.png',
			81
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! $this->core->addSettings['can_access_settings'] ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'twosecond' ) );
		}
		// Handle form submission
		if ( $this->core->twosecond_verify_request_value('ws_action', 'cache') ) {
			// Verify nonce
			if ( empty( $_POST['_twosecond_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_twosecond_nonce'] ) ), 'twosecond_settings' ) ) {
				wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', 'twosecond' ) );
			}
			// Verify user capabilities
			if ( ! current_user_can( 'twosecond_settings' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'twosecond' ) );
			}

			if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
				$settings = $this->sanitize_settings();
				$this->core->twosecond_update_option( 'twosecond_option', $settings );
				$this->core->twosecond_check_html_cache_settings();
			}

			if ( $this->should_clear_cache( $settings ) ) {
				$this->clear_cache();
			}
			
			add_action( 'admin_notices', array( $this, 'show_success_notice' ) );
		}
		// Include the admin page template
		include TWOSECOND_DIR . 'admin/admin-page.php';
	}

	/**
	 * Recursively sanitize values including nested arrays
	 *
	 * @param mixed $value Value to sanitize.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_value( $value ) {
		if ( is_array( $value ) ) {
			// Recursively sanitize each element in the array
			return array_map( array( $this, 'sanitize_value' ), array_map( 'wp_unslash', $value ) );
		} else {
			// Sanitize scalar values
			return sanitize_text_field( wp_unslash( $value ) );
		}
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $raw_settings Raw settings from form.
	 * @return array
	 */
	private function sanitize_settings() {
		unset( $_POST['ws_action'], $_POST['temp_input'], $_POST['_twosecond_nonce'] );
		$oldSettings = $this->core->twosecond_get_settings();
		$newSettings = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification already performed in render_settings_page() before calling this method (lines 140-142)
		foreach ( $_POST as $key => $value ) {
			// Sanitize key - use sanitize_text_field to preserve case and structure
			$key = sanitize_text_field( $key );
			if ( 'preload_resources' === $key ) {
				$value  = str_replace( '####', 'https', $value );
				$value  = str_replace( '###', 'http', $value );
			}
			$newSettings[ $key ] = $this->sanitize_value( $value );
		}

		$newSettings = $this->sanitize_register_settings( $this->core->twosecond_replace_backslashes( $newSettings ) );
		if (empty($newSettings['license_key'])) {
			$newSettings['is_activated'] = '';
		}	
		$changes = [];
		foreach ($oldSettings as $key => $value) {
			if (!array_key_exists($key, $newSettings)) {
				$newSettings[$key] = '';
			}
		}
		$htmlCacheExcludeKeys = ['html_caching', 'enable_loggedin_user_caching', 'by_serve_cache_file', 'enable_caching_get_para', 'html_caching_expiry_time', 'clear_cache_page_post_updated', 'preload_caching', 'preload_per_min', 'lbc', 'gzip', 'optimize_user_logged_in'];

		$clearCache = false;
		foreach ($newSettings as $key => $value) {
			if ((!isset($oldSettings[$key]) || $oldSettings[$key] != $value) && $key != "_twosecond_nonce") {
				$action = empty($this->core->twosecond_get_action_heading($key)) ? $key :  $this->core->twosecond_get_action_heading($key);
				$changes[] = [
					'action' => $action,
					'new' => !empty($value) ? $value : 'Not Set',
					'old' => !empty($oldSettings[$key]) ? $oldSettings[$key] : 'Not Set',
				];
				if(!in_array($key, $htmlCacheExcludeKeys)){
					$clearCache = true;
				}
			}
		}
		if (!empty($changes)) {
			if($clearCache){
				$this->core->twosecond_remove_cache_files_hourly_event_callback('html');
			}
			$this->core->twosecond_log_settings_changes($changes);
		}

		if(!empty($newSettings['html_caching']) && !empty($newSettings['by_serve_cache_file']) && $newSettings['by_serve_cache_file'] == 'htaccess' && (empty($oldSettings['by_serve_cache_file']) || $oldSettings['by_serve_cache_file'] != 'htaccess' || empty($oldSettings['html_caching']))){
			$this->core->htaccessModifyKeys['html_cache'] = 1;
		}
		if(!empty($newSettings['gzip']) && (empty($oldSettings['gzip']) || $oldSettings['gzip'] != $newSettings['gzip'])){
			$this->core->htaccessModifyKeys['gzip'] = 1;
		}
		if(!empty($newSettings['lbc']) && (empty($oldSettings['lbc']) || $oldSettings['lbc'] != $newSettings['lbc'])){
			$this->core->htaccessModifyKeys['lbc'] = 1;
		}
		if((!empty($newSettings['webp_png']) && empty($oldSettings['webp_png'])) || (!empty($newSettings['webp_jpg']) && empty($oldSettings['webp_jpg']))){
			$this->core->htaccessModifyKeys['webp'] = 1;
		}
		if(($newSettings['license_key'] ?? '') != ($oldSettings['license_key'] ?? '')){
			$this->core->twosecond_truncate_site_urls_table();
		}
		return $newSettings;
	}

	/**
	 * Check if cache should be cleared
	 *
	 * @param array $new_settings New settings.
	 * @return bool
	 */
	private function should_clear_cache( $new_settings ) {
		$old_settings = $this->core->twosecond_get_settings();
		
		$cache_related_keys = array(
			'css',
			'js',
			'html_caching',
			'cdn',
			'webp_jpg',
			'webp_png',
		);
		
		foreach ( $cache_related_keys as $key ) {
			if ( isset( $new_settings[ $key ] ) && isset( $old_settings[ $key ] ) && $new_settings[ $key ] !== $old_settings[ $key ] ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Clear cache
	 */
	private function clear_cache() {
		// Clear CSS/JS cache
		$cache_path = $this->core->get_cache_path();
		if ( is_dir( $cache_path ) ) {
			$this->delete_directory_contents( $cache_path );
		}
	}

	/**
	 * Delete directory contents
	 *
	 * @param string $dir Directory path.
	 */
	private function delete_directory_contents( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		
		$files = glob( $dir . '/*' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				$this->core->twosecond_delete_file( $file );
			} elseif ( is_dir( $file ) ) {
				$this->delete_directory_contents( $file );
				$this->core->twosecond_rmdir( $file );
			}
		}
	}

	/**
	 * Initialize settings
	 */
	public function twosecond_init_settings() {
		// Register settings with proper sanitization
		register_setting( 
			'twosecond_settings', 
			'twosecond_option', 
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_register_settings' ),
				'default'           => array(),
			)
		);
		
		// Add settings sections
		$this->add_settings_sections();
		
	}

	/**
	 * Add settings sections
	 */
	private function add_settings_sections() {
		// General Settings
		add_settings_section(
			'twosecond_general',
			__( 'General Settings', 'twosecond' ),
			array( $this, 'render_general_section' ),
			'twosecond_settings'
		);
		
		// CSS Optimization
		add_settings_section(
			'twosecond_css',
			__( 'CSS Optimization', 'twosecond' ),
			array( $this, 'render_css_section' ),
			'twosecond_settings'
		);
		
		// JavaScript Optimization
		add_settings_section(
			'twosecond_js',
			__( 'JavaScript Optimization', 'twosecond' ),
			array( $this, 'render_js_section' ),
			'twosecond_settings'
		);
		
		// HTML Caching
		add_settings_section(
			'twosecond_html',
			__( 'HTML Caching', 'twosecond' ),
			array( $this, 'render_html_section' ),
			'twosecond_settings'
		);
		
		// Image Optimization
		add_settings_section(
			'twosecond_images',
			__( 'Image Optimization', 'twosecond' ),
			array( $this, 'render_images_section' ),
			'twosecond_settings'
		);
	}

	/**
	 * Render general section
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure general optimization settings.', 'twosecond' ) . '</p>';
	}

	/**
	 * Render CSS section
	 */
	public function render_css_section() {
		echo '<p>' . esc_html__( 'Configure CSS optimization settings.', 'twosecond' ) . '</p>';
	}

	/**
	 * Render JavaScript section
	 */
	public function render_js_section() {
		echo '<p>' . esc_html__( 'Configure JavaScript optimization settings.', 'twosecond' ) . '</p>';
	}

	/**
	 * Render HTML section
	 */
	public function render_html_section() {
		echo '<p>' . esc_html__( 'Configure HTML caching settings.', 'twosecond' ) . '</p>';
	}

	/**
	 * Render images section
	 */
	public function render_images_section() {
		echo '<p>' . esc_html__( 'Configure image optimization settings.', 'twosecond' ) . '</p>';
	}


	/**
	 * Show admin notices
	 */
	public function show_admin_notices() {
		// Show PHP version notice
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			echo '<div class="error"><p>' . 
				esc_html__( 'TwoSecond requires PHP 7.4 (or higher) to function properly.', 'twosecond' ) . 
				'</p></div>';
		}

		// Show extension notices
		if ( ! extension_loaded( 'xml' ) ) {
			echo '<div class="error"><p>' . 
				esc_html__( 'TwoSecond requires PHP-XML module to function properly.', 'twosecond' ) . 
				'</p></div>';
		}

		if ( ! extension_loaded( 'gd' ) ) {
			echo '<div class="error"><p>' . 
				esc_html__( 'TwoSecond image optimization requires GD module to function properly.', 'twosecond' ) . 
				'</p></div>';
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			echo '<div class="error"><p>' . 
				esc_html__( 'TwoSecond requires PHP-XML module to function properly.', 'twosecond' ) . 
				'</p></div>';
		}
	}

	/**
	 * Show success notice
	 */
	public function show_success_notice() {
		echo '<div class="notice notice-success is-dismissible"><p>' . 
			esc_html__( 'Settings saved successfully!', 'twosecond' ) . 
			'</p></div>';
	}

	/**
	 * Add action links
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=twosecond' ) ) . '">' . 
			__( 'Settings', 'twosecond' ) . '</a>';
		
		return array_merge( array( $settings_link ), $links );
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Raw input data
	 * @return array Sanitized data
	 */
	public function sanitize_register_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		$cdn_types_allowed = array( 'image', 'css', 'js', 'font', 'audio', 'video' );

		foreach ( $input as $key => $value ) {
			// License key / paths.
			if ( in_array( $key, array( 'license_key', 'cache_path' ), true ) && ! is_array( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
				continue;
			}

			// CDN configuration (array of entries with url/type/exclude_path).
			if ( 'cdn' === $key ) {
				$cdn_entries = is_array( $value ) ? $value : array();
				$out = array();
				foreach ( $cdn_entries as $idx => $entry ) {
					if ( ! is_array( $entry ) ) {
						continue;
					}
					$url = isset( $entry['url'] ) ? esc_url_raw( wp_unslash( (string) $entry['url'] ) ) : '';
					$type_raw = isset( $entry['type'] ) ? sanitize_text_field( wp_unslash( (string) $entry['type'] ) ) : '';
					$type_parts = array_filter( array_map( 'trim', explode( ',', $type_raw ) ) );
					$type_parts = array_values( array_intersect( $type_parts, $cdn_types_allowed ) );
					$exclude_path = isset( $entry['exclude_path'] ) ? sanitize_textarea_field( wp_unslash( (string) $entry['exclude_path'] ) ) : '';
					$out[ $idx ] = array(
						'url' => $url,
						'type' => implode( ',', $type_parts ),
						'exclude_path' => $exclude_path,
					);
				}
				$sanitized[ $key ] = $out;
				continue;
			}

			// Known array-of-strings settings.
			if ( in_array( $key, array( 'exclude_css', 'exclude_lazy_load', 'exclude_both_javascript', 'preload_css', 'load_style_tag_in_head' ), true ) ) {
				$arr = is_array( $value ) ? $value : array();
				$sanitized[ $key ] = array_values(
					array_filter(
						array_map(
							function ( $v ) {
								return sanitize_text_field( wp_unslash( (string) $v ) );
							},
							$arr
						)
					)
				);
				continue;
			}

			// Comma/newline separated exclusion lists & similar textarea inputs.
			if ( in_array( $key, array( 'exclude_cdn', 'image_exclude_cdn_path', 'css_exclude_cdn_path', 'js_exclude_cdn_path', 'font_exclude_cdn_path', 'audio_exclude_cdn_path', 'video_exclude_cdn_path' ), true ) && ! is_array( $value ) ) {
				$sanitized[ $key ] = sanitize_textarea_field( wp_unslash( (string) $value ) );
				continue;
			}

			// Boolean-like flags (checkboxes).
			if ( in_array( $key, array( 'html_caching', 'gzip', 'lbc', 'optimization_on', 'lazy_load', 'lazy_load_iframe', 'lazy_load_video', 'lazy_load_audio', 'webp_jpg', 'webp_png', 'optimize_user_logged_in', 'optimize_query_parameters' ), true ) ) {
				$sanitized[ $key ] = ( ! empty( $value ) && 'off' !== $value ) ? 'on' : '';
				continue;
			}

			// Integer values.
			if ( in_array( $key, array( 'html_caching_expiry_time', 'preload_per_min' ), true ) ) {
				$sanitized[ $key ] = absint( wp_unslash( $value ) );
				continue;
			}

			// Multi-line or textarea-like values.
			if ( in_array( $key, array( 'exclude_pages_from_optimization', 'preload_resources' ), true ) && ! is_array( $value ) ) {
				$sanitized[ $key ] = sanitize_textarea_field( wp_unslash( (string) $value ) );
				continue;
			}

			// Fallback: generic text sanitization.
			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_value($value);
			} else {
				$sanitized[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
			}
		}

		return $sanitized;
	}

	/**
	 * Handle Version Upgrade
	 *
	 * Removes old directories related to previous plugin versions, such as
	 * 'ts-webp' and 'critical-css', from the content path if they exist.
	 * This helps to clean up obsolete files and directories after an upgrade.
	 */
	private function handle_version_upgrade() {
		// Define the path to the old 'ts-webp' directory
		$previous_webp_path = $this->core->addSettings['content_path'] . '/ts-webp';

		// If the 'ts-webp' directory exists, remove it
		if ( is_dir( $previous_webp_path ) ) {
			$this->core->twosecond_rmdir( $previous_webp_path );
		}

		// Define the path to the old 'critical-css' directory
		$previous_critical_path = $this->core->addSettings['content_path'] . '/critical-css';

		// If the 'critical-css' directory exists, remove it
		if ( is_dir( $previous_critical_path ) ) {
			$this->core->twosecond_rmdir( $previous_critical_path );
		}
	}
}