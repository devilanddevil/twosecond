<?php
/**
 * Plugin Name: 2Second (TwoSecond)
 * Description: Boost your WordPress site speed with advanced AI optimization techniques. Improve Core Web Vitals, achieve higher scores on Google PageSpeed Insights and GTmetrix, and deliver a faster, smoother browsing experience for your visitors.
 * Version: 2.1.0
 * Author: TwoSecond
 * Author URI: https://twosecond.ai/
 * License: GPLv2 or later
 * Text Domain: twosecond
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Network: true
 * 
 * @package TwoSecond
 * @copyright 2019-2025 TwoSecond
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('TWOSECOND_VERSION', '2.0.0');
define('TWOSECOND_FILE', __FILE__);
define('TWOSECOND_DIR', plugin_dir_path(__FILE__));
define('TWOSECOND_URL', plugin_dir_url(__FILE__));
define('TWOSECOND_BASENAME', plugin_basename(__FILE__));
define('TWOSECOND_FOLDERNAME', basename(TWOSECOND_DIR));

if (!defined('TWOSECOND_WP_CONTENT_BASENAME')) {
	if (!defined('TWOSECOND_WP_PLUGIN_DIR')) {
		if (preg_match("/(\/trunk\/|\/twosecond\/)$/", plugin_dir_path(__FILE__))) {
			define("TWOSECOND_WP_PLUGIN_DIR", preg_replace("/(\/trunk\/|\/twosecond\/)$/", "", plugin_dir_path(__FILE__)));
		} else if (preg_match("/\\\twosecond\/$/", plugin_dir_path(__FILE__))) {
			define("TWOSECOND_WP_PLUGIN_DIR", preg_replace("/\\\twosecond\/$/", "", plugin_dir_path(__FILE__)));
		} else {
			define("TWOSECOND_WP_PLUGIN_DIR", rtrim(plugin_dir_path(__FILE__), '/'));
		}
	}
	define("TWOSECOND_WP_CONTENT_DIR", dirname(TWOSECOND_WP_PLUGIN_DIR));
	define("TWOSECOND_WP_CONTENT_BASENAME", basename(TWOSECOND_WP_CONTENT_DIR));
}
// Autoloader
spl_autoload_register(function ($class) {
	// Handle new TwoSecond namespace
	if (strpos($class, 'TwoSecond\\') === 0) {
		$relative_class = substr($class, 10);

		// Check in admin directory first for Admin classes
		if (strpos($relative_class, 'Admin\\') === 0) {
			$file = TWOSECOND_DIR . 'admin/' . str_replace('\\', '/', substr($relative_class, 6)) . '.php';
			if (file_exists($file)) {
				require_once $file;
				return;
			}
		}

		// Check in includes directory for other classes
		// For classes like Frontend\Frontend, we need to extract just the class name
		$last_backslash_pos = strrpos($relative_class, '\\');
		if ($last_backslash_pos !== false) {
			$class_name = substr($relative_class, $last_backslash_pos + 1);
		} else {
			$class_name = $relative_class;
		}
		$file = TWOSECOND_DIR . 'includes/' . $class_name . '.php';
		if (file_exists($file)) {
			require_once $file;
			return;
		}
	}

	// Handle old TwoSecond namespace for backward compatibility
	if (strpos($class, 'TwoSecond\\') === 0) {
		$relative_class = substr($class, 10);
		$file = TWOSECOND_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';

		if (file_exists($file)) {
			require_once $file;
			return;
		}
	}
});

/**
 * Scan directory for cache keys
 *
 * Scans a directory and returns the first valid directory name (cache key).
 * Uses WordPress filesystem API when available, falls back to PHP filesystem.
 *
 * @param string $dir_path Directory path to scan.
 * @return string|false First directory name found, or false on failure.
 */
function twosecond_scan_dir($dir_path)
{
	// Initialize WordPress filesystem API
	global $wp_filesystem;
	$twoSecond_filesystem_initialized = false;

	if (function_exists('WP_Filesystem')) {
		if (!isset($wp_filesystem) || !is_object($wp_filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$twoSecond_filesystem_initialized = WP_Filesystem();
		} else {
			$twoSecond_filesystem_initialized = true;
		}
	}

	// Validate and sanitize directory path
	if (empty($dir_path) || strpos($dir_path, '../') !== false || strpos($dir_path, '..\\') !== false) {
		return false;
	}

	// Use WordPress filesystem API if available
	if ($twoSecond_filesystem_initialized && isset($wp_filesystem) && is_object($wp_filesystem)) {
		if (!$wp_filesystem->exists($dir_path) || !$wp_filesystem->is_dir($dir_path)) {
			return false;
		}

		$twoSecond_files = $wp_filesystem->dirlist($dir_path, false, false);
		if (!is_array($twoSecond_files)) {
			return false;
		}

		// Find first valid directory (skip . and ..)
		foreach ($twoSecond_files as $twoSecond_file => $twoSecond_file_info) {
			if ('.' === $twoSecond_file || '..' === $twoSecond_file) {
				continue;
			}
			// Check if it's a directory
			if (isset($twoSecond_file_info['type']) && 'd' === $twoSecond_file_info['type']) {
				// Sanitize filename - use WordPress function if available, otherwise basic sanitization
				if (function_exists('sanitize_file_name')) {
					return sanitize_file_name($twoSecond_file);
				}
				// Basic sanitization fallback
				return preg_replace('/[^a-zA-Z0-9_-]/', '', $twoSecond_file);
			}
		}
	} else {
		// Fallback to PHP filesystem
		if (!is_dir($dir_path) || !is_readable($dir_path)) {
			return false;
		}

		$twoSecond_handle = opendir($dir_path);
		if (false === $twoSecond_handle) {
			return false;
		}

		// Find first valid directory (skip . and ..)
		while (false !== ($twoSecond_file = readdir($twoSecond_handle))) {
			if ('.' === $twoSecond_file || '..' === $twoSecond_file) {
				continue;
			}
			$twoSecond_file_path = $dir_path . '/' . $twoSecond_file;
			if (is_dir($twoSecond_file_path)) {
				closedir($twoSecond_handle);
				// Sanitize filename - use WordPress function if available, otherwise basic sanitization
				if (function_exists('sanitize_file_name')) {
					return sanitize_file_name($twoSecond_file);
				}
				// Basic sanitization fallback
				return preg_replace('/[^a-zA-Z0-9_-]/', '', $twoSecond_file);
			}
		}

		closedir($twoSecond_handle);
	}

	return false;
}

function twosecond_check_resource_404()
{
	global $wp_filesystem;
	if (!function_exists('WP_Filesystem')) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$twoSecond_filesystem_initialized = WP_Filesystem();

	$twoSecond_url = !empty($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
	$twoSecond_path = strtok($twoSecond_url, '?');
	$twoSecond_root_path = !empty($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['DOCUMENT_ROOT'])) : '';
	if (strpos($twoSecond_path, '/ts-cache/') !== false && in_array(pathinfo($twoSecond_path, PATHINFO_EXTENSION), array('css', 'js', 'woff2', 'woff', 'ttf', 'otf', 'eot', 'svg'))) {
		if (strpos($twoSecond_path, '../') !== false || strpos($twoSecond_path, '..\\') !== false) {
			exit;
		}
		$twoSecond_split_path = explode('/ts-cache/', $twoSecond_path);
		if (empty($twoSecond_split_path[0]) || count($twoSecond_split_path) < 2) {
			exit;
		}
		$twoSecond_cache_path = $twoSecond_root_path . $twoSecond_split_path[0] . '/ts-cache/';
		preg_match('/(.*)\/ts-cache\/(?:(fonts)\/(.*)|(css|js)\/(\d+)(.*))[mob]*\.(css|js|woff2|woff|ttf|otf)/', $twoSecond_path, $twoSecond_match);
		if (!empty($twoSecond_match[2]) && $twoSecond_match[2] === 'fonts') {
			$twoSecond_rest_path = $twoSecond_match[3];
			$twoSecond_match[3] = '';
			$twoSecond_match[4] = $twoSecond_rest_path;
			$twoSecond_match[5] = $twoSecond_match[7];
			$twoSecond_path_parts = explode('/', $twoSecond_match[4], 2);
			$twoSecond_key = $twoSecond_path_parts[0];
			$twoSecond_match[4] = isset($twoSecond_path_parts[1]) ? $twoSecond_path_parts[1] : '';
		} else if (!empty($twoSecond_match[4])) {
			$twoSecond_match[2] = $twoSecond_match[4];
			$twoSecond_match[3] = $twoSecond_match[5];
			$twoSecond_match[4] = $twoSecond_match[6];
			$twoSecond_match[5] = $twoSecond_match[7];
			$twoSecond_key = twosecond_scan_dir($twoSecond_cache_path . $twoSecond_match[2]);
		}

		$twoSecond_new_path = $twoSecond_cache_path . $twoSecond_match[2] . '/' . $twoSecond_key . ($twoSecond_match[4] ? '/' . $twoSecond_match[4] : '') . '.' . $twoSecond_match[5];

		$twoSecond_file_exists = false;
		if ($twoSecond_filesystem_initialized && isset($wp_filesystem) && is_object($wp_filesystem)) {
			$twoSecond_file_exists = $wp_filesystem->exists($twoSecond_new_path);
		} else {
			$twoSecond_file_exists = file_exists($twoSecond_new_path);
		}

		if ($twoSecond_file_exists) {
			$twoSecond_real_path = realpath($twoSecond_new_path);
			$twoSecond_real_cache_path = realpath($twoSecond_cache_path);
			if (false === $twoSecond_real_path || false === $twoSecond_real_cache_path || strpos($twoSecond_real_path, $twoSecond_real_cache_path) !== 0) {
				exit;
			}
			$twoSecond_filename = basename($twoSecond_new_path);
			header("HTTP/1.1 200 OK");
			header("Content-Disposition: inline; filename=\"" . esc_attr($twoSecond_filename) . "\"");
			if (pathinfo($twoSecond_new_path, PATHINFO_EXTENSION) == 'js') {
				header("Content-Type: application/javascript");
			} elseif (pathinfo($twoSecond_new_path, PATHINFO_EXTENSION) == 'css') {
				header("Content-Type: text/css");
			} elseif (pathinfo($twoSecond_new_path, PATHINFO_EXTENSION) == 'woff2' || pathinfo($twoSecond_new_path, PATHINFO_EXTENSION) == 'woff' || pathinfo($twoSecond_new_path, PATHINFO_EXTENSION) == 'ttf' || pathinfo($twoSecond_new_path, PATHINFO_EXTENSION) == 'otf') {
				header("Content-Type: font/" . pathinfo($twoSecond_new_path, PATHINFO_EXTENSION));
			}
			if ($twoSecond_filesystem_initialized && isset($wp_filesystem) && is_object($wp_filesystem)) {
				$twoSecond_file_contents = $wp_filesystem->get_contents($twoSecond_new_path);
				if (false !== $twoSecond_file_contents) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file output (CSS/JS/fonts). Path validated with realpath() above. Escaping would corrupt binary data.
					echo $twoSecond_file_contents;
					exit;
				}
			}
		} else {
			$twoSecond_file_path = $twoSecond_root_path . $twoSecond_match[4] . '.' . $twoSecond_match[5];
			$twoSecond_fallback_exists = false;
			if ($twoSecond_filesystem_initialized && isset($wp_filesystem) && is_object($wp_filesystem)) {
				$twoSecond_fallback_exists = $wp_filesystem->exists($twoSecond_file_path);
			} else {
				$twoSecond_fallback_exists = file_exists($twoSecond_file_path);
			}

			if ($twoSecond_fallback_exists) {
				$twoSecond_real_file_path = realpath($twoSecond_file_path);
				$twoSecond_real_root_path = realpath($twoSecond_root_path);
				if (false === $twoSecond_real_file_path || false === $twoSecond_real_root_path || strpos($twoSecond_real_file_path, $twoSecond_real_root_path) !== 0) {
					exit;
				}
				$twoSecond_filename = basename($twoSecond_file_path);
				header("HTTP/1.1 200 OK");
				header("Content-Disposition: inline; filename=\"" . esc_attr($twoSecond_filename) . "\"");
				if ($twoSecond_match[5] == 'js') {
					header("Content-Type: application/javascript");
				} elseif ($twoSecond_match[5] == 'css') {
					header("Content-Type: text/css");
				} elseif ($twoSecond_match[5] == 'woff2' || $twoSecond_match[5] == 'woff' || $twoSecond_match[5] == 'ttf' || $twoSecond_match[5] == 'otf') {
					header("Content-Type: font/" . $twoSecond_match[5]);
				}
				if ($twoSecond_filesystem_initialized && isset($wp_filesystem) && is_object($wp_filesystem)) {
					$twoSecond_fallback_contents = $wp_filesystem->get_contents($twoSecond_file_path);
					if (false !== $twoSecond_fallback_contents) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file output (CSS/JS/fonts). Path validated with realpath() above. Escaping would corrupt binary data.
						echo $twoSecond_fallback_contents;
						exit;
					}
				}
			}
		}
		exit;
	}
}

twosecond_check_resource_404();
add_action('plugins_loaded', 'twosecond_init');

/**
 * Initialize the plugin
 */
function twosecond_init()
{
	if (version_compare(PHP_VERSION, '7.4', '<')) {
		add_action('admin_notices', 'twosecond_php_version_notice');
		return;
	}

	if (!extension_loaded('xml') || !extension_loaded('gd') || !class_exists('DOMDocument')) {
		add_action('admin_notices', 'twosecond_extension_notice');
		return;
	}

	TwoSecond\Plugin::get_instance();
}

/**
 * Display PHP version notice
 */
function twosecond_php_version_notice()
{
	echo '<div class="error"><p>' .
		esc_html__('TwoSecond requires PHP 7.4 (or higher) to function properly.', 'twosecond') .
		'</p></div>';
}

/**
 * Display extension notice
 */
function twosecond_extension_notice()
{
	echo '<div class="error"><p>' .
		esc_html__('TwoSecond requires PHP-XML and GD modules to function properly.', 'twosecond') .
		'</p></div>';
}

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'twosecond_activate');
register_deactivation_hook(__FILE__, 'twosecond_deactivate');

/**
 * Plugin activation
 */
function twosecond_activate()
{
	$twosecond_database = new TwoSecond\Database();
	$twosecond_database->twosecond_create_tables();

	// Set default options
	TwoSecond\Options::set_defaults();

	$twoSecond = new TwoSecond\TwoSecond();
	$twoSecond->twosecond_check_html_cache_settings(true);

	// Add custom capability to administrator role
	$admin_role = get_role('administrator');
	if ($admin_role) {
		$admin_role->add_cap('twosecond_settings');
	}

	// Flush rewrite rules
	flush_rewrite_rules();
}

/**
 * Plugin deactivation
 */
function twosecond_deactivate()
{
	wp_clear_scheduled_hook('twosecond_cron_event');

	// Remove admin bar
	remove_action('admin_bar_menu', 'TwoSecond\Admin\Admin_Bar::add_cache_purge_node', 999);

	// When de-activate the plugin then remove the advanced cache file and the html cache code
	$twoSecond = new TwoSecond\TwoSecond();
	$twoSecond->twosecond_remove_advance_cache_file();
	$twoSecond->twosecond_remove_htaccess_code();
	// Flush rewrite rules
	flush_rewrite_rules();
}