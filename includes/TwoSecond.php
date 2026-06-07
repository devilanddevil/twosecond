<?php
/**
 * TwoSecond Main Class
 *
 * This class extends the Core class and provides the main functionality
 * for the TwoSecond WordPress plugin. It handles plugin initialization,
 * settings management, caching operations, and various utility methods.
 *
 * @package TwoSecond
 * @author TwoSecond Team
 */

namespace TwoSecond;

// Prevent direct access
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Main TwoSecond Plugin Class
 *
 * Extends the Core class to provide comprehensive plugin functionality
 * including settings management, caching, optimization, and admin features.
 */
class TwoSecond extends Core
{
	/**
	 * Constructor
	 *
	 * Initializes the plugin with default settings, sets up hooks,
	 * and configures various plugin parameters and paths.
	 */
	public function __construct()
	{
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		$this->twosecond_init_settings();

		$this->addSettings['twosecond_img_aspect_ratio'] = [];
		$this->twosecond_set_additional_settings();
	}

	/**
	 * Initialize settings
	 *
	 * Loads plugin settings from WordPress options and initializes
	 * the settings arrays. Also sets up admin notices if needed.
	 */
	protected function twosecond_init_settings() {
		global $current_user;
        // Load main plugin settings
		$this->addSettings = array();
        // Set up basic URL settings
        $this->addSettings['home_url'] = rtrim(home_url(), '/');
		$this->addSettings['site_url'] = rtrim(site_url(),'/');
		$this->addSettings['is_admin'] = is_admin();
		$this->addSettings['admin_user'] = !empty($current_user) && current_user_can('edit_others_pages');
		$this->addSettings['is_amp_endpoint'] = function_exists('is_amp_endpoint') && is_amp_endpoint() ? true : false;
		$this->addSettings['is_multisite'] = function_exists('is_multisite') && is_multisite();

        $this->twosecond_database = new \TwoSecond\Database();
        $this->settings = $this->twosecond_get_option( 'twosecond_option', true );
        $this->settings = !empty($this->settings) && is_array($this->settings) ? $this->settings : array();
				
		$this->addSettings['user_hash'] = $this->twosecond_get_user_hash();
		$this->addSettings['can_access_settings'] = current_user_can('twosecond_settings');
		$this->addSettings['site_url_array'] = $this->twosecond_parse_url($this->addSettings['site_url']);
		
		// Handle cases where host is empty in site URL
		if(empty($this->addSettings['site_url_array']['host'])){
			$site_url = explode('/', rtrim(content_url(), '/'));
			array_pop($site_url);
			$this->addSettings['site_url'] = rtrim(implode('/', $site_url),'/');
			$this->addSettings['site_url_array'] = $this->twosecond_parse_url($this->addSettings['site_url']);
		}
		
		// Set up www/non-www URL variations
		if(str_starts_with($this->addSettings['site_url_array']['host'],'www.')){
			$this->addSettings['site_url_diff'] = str_replace('www.','',$this->addSettings['site_url_array']['host']);
		}else{
			$this->addSettings['site_url_diff'] = 'www.'.$this->addSettings['site_url_array']['host'];
		}
 
		// Load permission errors and handle URL parameters
		$this->addSettings['twosecond_permission_errors'] = $this->twosecond_get_option('twosecond_permission_errors') ?? [];
		if (strpos($this->addSettings['home_url'], '?') !== false) {
            $home_url_arr = explode('?', $this->addSettings['home_url']);
            $this->addSettings['home_url'] = $home_url_arr[0];
        }
		
		// Set up multisite settings
		$this->addSettings['network_site_url'] = rtrim(network_site_url(),'/');
		$this->addSettings['twosecond_current_blog'] = $this->twosecond_get_current_blog();
		$this->addSettings['is_multisite_networkadmin'] = $this->addSettings['is_multisite'] && function_exists('is_network_admin') && is_network_admin();
		$this->addSettings['is_multisite_sub_domain'] = $this->addSettings['is_multisite'] && $this->addSettings['site_url'] != $this->addSettings['network_site_url'];
		
		// Set up file paths
		$this->addSettings['content_path'] = WP_CONTENT_DIR;
		$content_arr = explode('/', $this->addSettings['content_path']);
        array_pop($content_arr);
		$this->addSettings['document_root'] = rtrim(implode('/', $content_arr), '/');
	}

	/**
	 * Initialize hooks
	 *
	 * Sets up WordPress hooks and filters for various optimization features.
	 * This includes version removal from assets, lazy loading control,
	 * and object cache configuration.
	 */
	public function twosecond_init_hooks()
	{
		if (! empty($this->settings['remquery']) && !$this->addSettings['is_admin']) {
			add_filter('style_loader_src', array($this, 'twosecond_remove_ver_css_js'), 9999, 2);
			add_filter('script_loader_src', array($this, 'twosecond_remove_ver_css_js'), 9999, 2);
		}

		// Disable WordPress default lazy loading if plugin lazy loading is enabled
		if (! empty($this->settings['lazy_load'])) {
			add_filter('wp_lazy_loading_enabled', '__return_false');
		}
	}

	/**
	 * Remove cache directory
	 *
	 * Removes a directory using the WP Filesystem API.
	 *
	 * @param string $dir Directory path to remove
	 */
	function twosecond_remove_single_directory($dir)
	{
		global $wp_filesystem;
		if (function_exists('WP_Filesystem')) {
			return $wp_filesystem->rmdir($dir);
		}
	}
	/**
	 * Get uploads base path
	 *
	 * Retrieves and processes the WordPress uploads directory path,
	 * handling multisite configurations and subdomain setups.
	 *
	 * @return array Array containing base URL and base directory
	 */
	function twosecond_get_uploads_basepath()
	{
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_dir = $upload_dir['basedir'];

		// Ensure base URL contains the site URL
		if (strpos($base_url, $this->addSettings['site_url']) === false) {
			$base_url_arr = $this->twosecond_parse_url($base_url);
			$base_url = $this->addSettings['site_url'] . (!empty($base_url_arr['path']) ? $base_url_arr['path'] : '');
		}

		// Handle multisite subdomain configurations
		if ($this->addSettings['is_multisite_sub_domain']) {
			$base_url = str_replace($this->addSettings['site_url'], $this->addSettings['network_site_url'], $base_url);
		}

		// Handle multisite with sites directory
		if ($this->addSettings['is_multisite'] && $this->addSettings['is_multisite_sub_domain'] && strpos($base_url, 'sites') !== false) {
			$site_path_parts = explode('/', trim($base_url, '/'));
			$base_url = str_replace("/" . implode('/', array_splice($site_path_parts, -2)), '', $base_url);

			$site_path = str_replace(ABSPATH, '', $base_dir);
			$site_path_parts = explode(DIRECTORY_SEPARATOR, trim($site_path, DIRECTORY_SEPARATOR));
			$base_dir = str_replace(DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($site_path_parts, 2)), '', $base_dir);
		}
		return array($base_url, $base_dir);
	}

	/**
	 * Check if user is logged in
	 *
	 * Wrapper function to check if a user is currently logged in
	 * to WordPress, with fallback for older WordPress versions.
	 *
	 * @return bool True if user is logged in, false otherwise
	 */
	public function twosecond_user_logged_in()
	{
		if (function_exists('is_user_logged_in')) {
			if (is_user_logged_in()) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Remove version parameters from CSS and JS URLs
	 *
	 * Filter callback to remove version query parameters from
	 * CSS and JavaScript file URLs for better caching.
	 *
	 * @param string $src   The source URL
	 * @param string $handle The script/style handle
	 * @return string Modified source URL without version parameters
	 */
	function twosecond_remove_ver_css_js($src, $handle)
	{
		$src = remove_query_arg(array('ver', 'v'), $src);
		return $src;
	}

	/**
	 * Activate plugin license key
	 *
	 * Handles license key activation requests and displays
	 * the validation result to the user.
	 */
	function twosecond_activate_license_key()
	{
		echo wp_kses_post($this->twosecond_validate_license_key());
		exit;
	}

	/**
	 * Customize CSS preload path for paginated content
	 *
	 * Removes pagination parameters from URLs when preloading CSS
	 * to ensure consistent caching across paginated content.
	 *
	 * @param string $url The URL to customize
	 * @return string Modified URL without pagination parameters
	 */
	function twosecond_preload_css_customize_path($url = '')
	{
		global $page;
		if ($page > 1 || is_paged()) {
			$url_arr = explode('/page/', $url);
			if (count($url_arr) > 1) {
				$url = $url_arr[0];
			}
		}
		return $url;
	}
	/**
	 * Check if critical CSS should be twosecond_ignored for specific pages
	 *
	 * Determines whether critical CSS generation should be skipped
	 * for certain page types like search results.
	 *
	 * @param string $url    The current page URL
	 * @param int    $ignore Current ignore status
	 * @return int Modified ignore status
	 */
	function twosecond_check_ignore_critical_css_callback($url, $ignore)
	{
		if (is_search() || is_page('search')) {
			$ignore = 1;
		}
		return $ignore;
	}
	/**
	 * Get current blog path for multisite installations
	 *
	 * Returns the current blog path for multisite setups,
	 * empty string for single site installations.
	 *
	 * @return string Blog path or empty string
	 */
	function twosecond_get_current_blog()
	{
		if ($this->addSettings['is_multisite']) {
			return '/' . get_current_blog_id();
		}
		return '';
	}

	/**
	 * Create directory with specified permissions
	 *
	 * Wrapper for WordPress mkdir function to create directories
	 * with specified permissions and recursive creation support.
	 *
	 * @param string $path        Directory path to create
	 * @param int    $permission  Directory permissions
	 * @param bool   $recusive    Whether to create directories recursively
	 * @return bool True on success, false on failure
	 */
	function twosecond_mkdir($path, $permission, $recusive)
	{
		return wp_mkdir_p($path, $permission, $recusive);
	}

	/**
	 * Perform remote GET request
	 *
	 * Makes HTTP GET requests to external URLs with configurable
	 * options including mobile user agent support.
	 *
	 * @param string $url     The URL to request
	 * @param array  $params  Additional parameters to send
	 * @param int    $method  HTTP method (0 = GET)
	 * @param bool   $mobile  Whether to use mobile user agent
	 * @param bool   $blocking Whether to block the request
	 * @param string $output   Whether to return the body or the response code 
	 * @return string Response body or the response code or empty string on failure
	 */
	function twosecond_remote_get($url, $params = array(), $mobile = false, $blocking = true, $output = 'body')
	{	
		$timeout = $blocking ? 10 : 3;
		$options = array(
			'method' => 'GET',
			'timeout' => $timeout,
			'redirection' => 5,
			'sslverify' => false,
			'httpversion' => '1.0',
			'headers' => $mobile ? array('User-Agent' => 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Mobile Safari/537.36') : array('User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
			'cookies' => array()
		);

		// Add parameters to request body if provided
		if (!empty($params)) {
			$options = array_merge($options, array('body' => $params));
		}

		// Make the request using WordPress HTTP API
		if (function_exists('wp_remote_get')) {
			$response = wp_remote_get($url, $options);
			if (!is_wp_error($response) && (int)$response['response']['code'] === 200 && !empty($response['body'])) {
				if ($output == 'body') {
					return wp_remote_retrieve_body($response);
				} else {
					return wp_remote_retrieve_response_code($response);
				}
			}
		}
		return '';
	}

	/**
	 * Delete a file
	 *
	 * Wrapper for WordPress file deletion function.
	 *
	 * @param string $file Path to the file to delete
	 * @return bool True on success, false on failure
	 */
	function twosecond_delete_file($file)
	{
		return wp_delete_file($file);
	}

	/**
	 * Generate random number
	 *
	 * Wrapper for WordPress random number generation.
	 *
	 * @return int Random number between 100 and 999
	 */
	function twosecond_random_number()
	{
		return wp_rand(100, 999);
	}

	/**
	 * Check if current page should be excluded from optimization
	 *
	 * Determines whether the current page matches any exclusion
	 * patterns defined in the plugin settings.
	 *
	 * @param string $exclude_setting Comma-separated list of excluded pages
	 * @return bool True if page should be excluded, false otherwise
	 */
	function twosecond_check_if_page_excluded($exclude_setting)
	{
		$e_p_from_optimization = !empty($exclude_setting) && is_array($exclude_setting) ? $exclude_setting : array();
		if (!empty($e_p_from_optimization)) {
			foreach ($e_p_from_optimization as $e_page) {
				if (empty($e_page)) {
					continue;
				}
				// Check home page exclusions
				if (empty($this->addSettings['twosecond_get']['testing']) && (is_home() || is_front_page()) && $this->addSettings['home_url'] == $e_page) {
					return true;
				} else if ($this->addSettings['home_url'] != $e_page) {
					// Check URL pattern matches
					if (strpos($this->addSettings['full_url'], $e_page) !== false) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if a plugin is active
	 *
	 * Checks whether a specific plugin is active on the current site
	 * or network-wide in multisite installations.
	 *
	 * @param string $plugin Plugin file path
	 * @return bool True if plugin is active, false otherwise
	 */
	public function twosecond_is_plugin_active($plugin)
	{
		return in_array($plugin, (array) get_option('active_plugins', array())) || $this->twosecond_is_plugin_active_for_network($plugin);
	}

    /**
     * Check if a plugin is network active
     *
     * Checks whether a plugin is active network-wide in multisite installations.
     *
     * @param string $plugin Plugin file path
     * @return bool True if plugin is network active, false otherwise
     */
    public function twosecond_is_plugin_active_for_network($plugin){
        if (!$this->addSettings['is_multisite'])
            return false;
        $plugins = get_site_option('active_sitewide_plugins');
        if (isset($plugins[$plugin]))
            return true;
        return false;
    }
	
	/**
	 * Encode array to JSON and echo it
	 *
	 * Wrapper for WordPress JSON encoding function.
	 *
	 * @param array $array Array to encode
	 * @return nothing
	 */
	function twosecond_json_encode_e($array){
		echo wp_json_encode($array);
	}
	/**
	 * Encode array to JSON
	 *
	 * Wrapper for WordPress JSON encoding function.
	 *
	 * @param array $array Array to encode
	 * @return string|false JSON string or false on failure
	 */
	function twosecond_json_encode($array){
		return wp_json_encode($array);
	}

	/**
	 * Check if current page should be twosecond_ignored for optimization
	 *
	 * Determines whether the current page matches patterns that
	 * should be excluded from optimization, including WordPress
	 * core pages and e-commerce specific pages.
	 *
	 * @return bool True if page should be twosecond_ignored, false otherwise
	 */
	public function twosecond_ignored()
	{
		// Default list of twosecond_ignored patterns
		$list = array(
			"\/wp\-comments\-post\.php",
			"\/wp\-login\.php",
			"\/robots\.txt",
			"\/wp\-cron\.php",
			"\/wp\-content",
			"\/wp\-admin",
			"\/wp\-includes",
			"\/index\.php",
			"\/xmlrpc\.php",
			"\/wp\-api\/",
			"leaflet\-geojson\.php",
			"\/clientarea\.php"
		);

		// Handle WooCommerce specific exclusions
		if ($this->twosecond_is_plugin_active('woocommerce/woocommerce.php')) {
			if (!is_front_page()) {
				global $post;

				if (isset($post->ID) && $post->ID) {
					if (function_exists("wc_get_page_id")) {
						$woocommerce_ids = array();

						array_push($woocommerce_ids, wc_get_page_id('cart'), wc_get_page_id('checkout'), wc_get_page_id('receipt'), wc_get_page_id('confirmation'), wc_get_page_id('myaccount'));

						if (in_array($post->ID, $woocommerce_ids)) {
							return true;
						}
					}
				}

				array_push($list, "\/cart\/?$", "\/checkout", "\/receipt", "\/confirmation", "\/wc-api\/");
			}
		}

		// Handle WP EasyCart exclusions
		if ($this->twosecond_is_plugin_active('wp-easycart/wpeasycart.php')) {
			array_push($list, "\/cart");
		}

		// Handle Easy Digital Downloads exclusions
		if ($this->twosecond_is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')) {
			array_push($list, "\/cart", "\/checkout");
		}

		// Check if current URI matches any twosecond_ignored patterns
		if (preg_match("/" . implode("|", $list) . "/i", $this->addSettings['twosecond_server']["REQUEST_URI"])) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current user is a commenter
	 *
	 * Determines whether the current user has previously commented
	 * on the site based on stored commenter data.
	 *
	 * @return bool True if user is a commenter, false otherwise
	 */
	public function twosecond_check_commenter()
	{
		$commenter = function_exists('wp_get_current_commenter') ? wp_get_current_commenter() : array();
		return isset($commenter["comment_author_email"]) && $commenter["comment_author_email"] ? true : false;
	}

	/**
	 * Check if page is password protected
	 *
	 * Determines whether the current page is password protected
	 * by checking for password form or post password cookies.
	 *
	 * @param string $html Page HTML content
	 * @return bool True if page is password protected, false otherwise
	 */
	public function twosecond_check_password_protected($html)
	{
		// Check for password form in HTML
		if (preg_match("/action\=[\'\"].+postpass.*[\'\"]/", $html)) {
			return true;
		}

		// Check for post password cookies
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cookie keys are sanitized via preg_match pattern check before use
		foreach ($_COOKIE as $key => $value) {
			// Sanitize cookie key before pattern matching
			$sanitized_key = $this->twosecond_sanitize_text_field($key);
			if (preg_match("/wp\-postpass\_/", $sanitized_key)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if page contains CAPTCHA
	 *
	 * Determines whether the current page contains CAPTCHA
	 * elements that might interfere with caching.
	 *
	 * @param string $html Page HTML content
	 * @return bool True if CAPTCHA is detected, false otherwise
	 */
	public function twosecond_check_captcha($html)
	{
		return false; // Currently disabled
		if (is_single() || is_page()) {
			if (preg_match("/<input[^\>]+_wpcf7_captcha[^\>]+>/i", $html)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if current page is an error page
	 *
	 * Determines whether the current page is an error page
	 * (404, error page, etc.) that should not be cached.
	 *
	 * @param string $html Page HTML content
	 * @return bool True if error page, false otherwise
	 */
	public function twosecond_check_error_page($html = false)
	{
		// Check HTTP response code
		if (function_exists("http_response_code") && (http_response_code() === 404)) {
			return true;
		}

		// Check WordPress 404 status
		if ($this->twosecond_is_404()) {
			return true;
		}

		// Check for error page HTML pattern
		if (preg_match("/<body id\=\"error-page\">\s*<p>[^\>]+<\/p>\s*<\/body>/i", $html)) {
			return true;
		}
	}

	/**
	 * Check if current page content is HTML
	 *
	 * Determines whether the current page content type is HTML.
	 *
	 * @return bool True if content is HTML, false otherwise
	 */
	public function twosecond_check_html_content_type()
	{
		return $this->current_page_content_type == "html" ? true : false;
	}

	/**
	 * Check firewall status for caching decisions
	 *
	 * Determines whether caching should be disabled due to
	 * firewall activity (e.g., Wordfence 503 responses).
	 *
	 * @return bool True if firewall is blocking, false otherwise
	 */
	function twosecond_check_firewall()
	{
		// for Wordfence: not to cache 503 pages
		if (defined('DONOTCACHEPAGE') && $this->twosecond_is_plugin_active('wordfence/wordfence.php')) {
			if (function_exists("http_response_code") && http_response_code() == 503) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate .htaccess data for HTML caching
	 *
	 * Creates the necessary .htaccess rules for HTML caching
	 * including mobile detection, user authentication checks,
	 * and cache file serving rules.
	 *
	 * @return string .htaccess rules for HTML caching
	 */
	public function twosecond_get_htaccess_data()
	{
		$mobile = "";
		$loggedInUser = "";
		$ifIsNotSecure = "";
		$trailing_slash_rule = "";
		$consent_cookie = "";

		$cache_path = str_replace($this->addSettings['content_path'], '', $this->addSettings['cache_path']);
		$cache_path .= '/ts-cache/html/%{ENV:HASH}/%{HTTPS}-%{HTTP_HOST}/';
		$mobile = "RewriteCond %{HTTP_USER_AGENT} !^.*(" . $this->twosecond_get_mobile_user_agents() . ").*$ [NC]" . "\n";

		if (isset($this->addSettings['twosecond_server']['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'])) {
			$mobile = $mobile . "RewriteCond %{HTTP_CLOUDFRONT_IS_MOBILE_VIEWER} false [NC]" . "\n";
			$mobile = $mobile . "RewriteCond %{HTTP_CLOUDFRONT_IS_TABLET_VIEWER} false [NC]" . "\n";
		}
		$hashString = '';

		if (empty($this->addSettings['twosecond_post']["enable_loggedin_user_caching"])) {
			$loggedInUser = "RewriteCond %{HTTP:Cookie} !wordpress_logged_in" . "\n";
		} else {
			$hashString = "RewriteCond %{HTTP:Cookie} wordpress_logged_in_[^=]+=([^%]+)%7C [NC]" . "\n";
			$hashString .= "RewriteRule ^ - [E=HASH:%1]" . "\n";
		}

		if (!preg_match("/^https/i", $this->addSettings['home_url'])) {
			$ifIsNotSecure = "RewriteCond %{HTTPS} !=on";
		}

		if ($this->twosecond_is_trailing_slash()) {
			$trailing_slash_rule = "RewriteCond %{REQUEST_URI} \/$ [OR]" . "\n" . "RewriteCond %{REQUEST_URI} \.(xml)$ [NC]" . "\n";
		} else {
			$trailing_slash_rule = "RewriteCond %{REQUEST_URI} ![^\/]+\/$" . "\n";
		}

		$data = "# BEGIN BSHTMLCACHE"."\n".
				"<IfModule mod_rewrite.c>"."\n".
				"RewriteEngine On"."\n".
				"RewriteBase /"."\n".
				"RewriteRule ^ - [E=REQ_EXT:html]"."\n".
				"RewriteCond %{REQUEST_URI} \.xml$ [NC]"."\n".
				"RewriteRule ^ - [E=REQ_EXT:xml]"."\n".
				$this->twosecond_prefix_redirect().
				$this->twosecond_exclude_rules()."\n".
				$this->twosecond_exclude_admin_cookie()."\n".
				$this->http_condition_rule()."\n".
				"RewriteCond %{HTTP_USER_AGENT} !(".$this->twosecond_get_excluded_useragent().")"."\n".
				"RewriteCond %{HTTP_USER_AGENT} !(BS\sCache\sPreload(\siPhone\sMobile)?\s*Bot)"."\n".
				"RewriteCond %{REQUEST_METHOD} !POST"."\n".
				"RewriteCond %{HTTP:X-Requested-With} !^XMLHttpRequest$ [NC]"."\n".
				$ifIsNotSecure."\n".
				"RewriteCond %{REQUEST_URI} !(\/){2}$"."\n".
				$trailing_slash_rule.
				$this->twosecond_query_string_rule().
				$loggedInUser.
				$consent_cookie.
				"RewriteCond %{HTTP:Cookie} !comment_author_"."\n".
				//"RewriteCond %{HTTP:Cookie} !woocommerce_items_in_cart"."\n".
				"RewriteCond %{HTTP:Cookie} !safirmobilswitcher=mobil"."\n".
				"SetEnvIf Request_URI ^ HASH="."\n".$hashString.
				'RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]'."\n".$mobile;
		
		if(ABSPATH == "//"){
			$data = $data."RewriteCond %{DOCUMENT_ROOT}/".TWOSECOND_WP_CONTENT_BASENAME.$cache_path."$1/%{QUERY_STRING}/index.%{ENV:REQ_EXT} -f"."\n";
		}else{
			//WARNING: If you change the following lines, you need to update webp as well
			$data = $data."RewriteCond %{DOCUMENT_ROOT}/".TWOSECOND_WP_CONTENT_BASENAME.$cache_path."$1/%{QUERY_STRING}/index.%{ENV:REQ_EXT} -f [or]"."\n";
			// to escape spaces
			$tmp_TWOSECOND_WP_CONTENT_DIR = str_replace(" ", "\ ", TWOSECOND_WP_CONTENT_DIR);

			$data = $data."RewriteCond ".$tmp_TWOSECOND_WP_CONTENT_DIR.$cache_path.$this->twosecond_get_rewrite_base(true)."$1/%{QUERY_STRING}/index.%{ENV:REQ_EXT} -f"."\n";
		}
		$data = $data.'RewriteRule ^(.*) "/'.$this->twosecond_get_rewrite_base().TWOSECOND_WP_CONTENT_BASENAME.$cache_path.$this->twosecond_get_rewrite_base(true).'$1/%{QUERY_STRING}/index.%{ENV:REQ_EXT}" [L]'."\n";	

		if ($this->twosecond_is_plugin_active('wptouch/wptouch.php') || $this->twosecond_is_plugin_active('wptouch-pro/wptouch-pro.php')) {
			$this->addSettings['wptouch'] = true;
		} else {
			$this->addSettings['wptouch'] = false;
		}

		$data = $data."\n\n\n".$this->twosecond_update_htaccess_mob($data);
		$data = $data."</IfModule>"."\n".
				"<FilesMatch \"index\.(html|htm)$\">"."\n".
				"AddDefaultCharset UTF-8"."\n".
				"<ifModule mod_headers.c>"."\n".
				"FileETag None"."\n".
				"Header unset ETag"."\n".
				"Header set Cache-Control \"max-age=0, no-cache, no-store, must-revalidate\""."\n".
				"Header set Pragma \"no-cache\""."\n".
				"Header set Expires \"Mon, 29 Oct 1923 20:30:00 GMT\""."\n".
				"</ifModule>"."\n".
				"</FilesMatch>"."\n".
				"# END BSHTMLCACHE"."\n";
		
		return preg_replace("/\n+/","\n", $data);
	}

	/**
	 * Ensure WP_CACHE constant is set to true
	 *
	 * Checks and modifies wp-config.php to ensure the WP_CACHE
	 * constant is defined and set to true for proper caching.
	 *
	 * @return array|void Error message array or void on success
	 */
	function twosecond_check_cache_true() {
		$wp_config_file = ABSPATH . 'wp-config.php';
		
		if (!file_exists($wp_config_file) || !is_readable($wp_config_file)) {
			return ["wp-config.php not found or not readable.", "error"];
		}
		
		$wp_config = file_get_contents($wp_config_file);
		if ($wp_config === false) {
			return ["Failed to read wp-config.php", "error"];
		}
		
		if (strpos($wp_config, "define('WP_CACHE', true); // added by twoSecond") === false) {
			$wp_config = preg_replace("/define\s*\(\s*['\"]WP_CACHE['\"]\s*,\s*(true|false)\s*\)\s*;\s*/i", "", $wp_config);
			preg_match("/(\$table_prefix\s*=\s*'[^']*';)/",$wp_config,$matches);
			if (is_array($matches) && count($matches) > 0) {
				$wp_config = preg_replace("/(\$table_prefix\s*=\s*'[^']*';)/", "$1\ndefine('WP_CACHE', true); // added by twoSecond", $wp_config);
			} else {
				$wp_config = preg_replace("/(<\?php\s+)/","<?php\ndefine('WP_CACHE', true); // added by twoSecond\n",$wp_config,1);
			}
			
			if (!$this->twosecond_check_is_writable($wp_config_file)) {
				return ["wp-config.php is not writable. Define('WP_CACHE', true) needs to be added manually.", "error"];
			}
			
			$result = $this->twosecond_create_file($wp_config_file, $wp_config);
			if ($result === false) {
				return ["Failed to write to wp-config.php", "error"];
			}
		}
	}

	function twosecond_check_is_writable($path)
	{
		global $wp_filesystem;
		if (function_exists('WP_Filesystem')) {
			return $wp_filesystem->is_writable($path);
		}
	}

	/**
	 * Get rewrite base for URL rewriting
	 *
	 * Determines the base path for URL rewriting rules,
	 * handling subdirectory installations and multisite setups.
	 *
	 * @param bool $sub Whether this is for subdirectory handling
	 * @return string Rewrite base path or empty string
	 */
	public function twosecond_get_rewrite_base($sub = "")
	{
		if ($sub && $this->twosecond_is_subdirectory_install()) {
			$trimedProtocol = preg_replace("/http:\/\/|https:\/\//", "", trim($this->addSettings['home_url'], "/"));
			$path = strstr($trimedProtocol, '/');

			if ($path) {
				return trim($path, "/") . "/";
			} else {
				return "";
			}
		}

		$url = rtrim($this->addSettings['site_url'], "/");
		preg_match("/https?:\/\/[^\/]+(.*)/", $url, $out);

		if (isset($out[1]) && $out[1]) {
			$out[1] = trim($out[1], "/");

			if (preg_match("/\/" . preg_quote($out[1], "/") . "\//", TWOSECOND_WP_CONTENT_DIR)) {
				return $out[1] . "/";
			} else {
				return "";
			}
		} else {
			return "";
		}
	}

	/**
	 * Update .htaccess with mobile-specific rules
	 *
	 * Modifies .htaccess content to include mobile-specific
	 * caching rules and WPtouch compatibility.
	 *
	 * @param string $data Original .htaccess content
	 * @return string Modified .htaccess content with mobile rules
	 */
	public function twosecond_update_htaccess_mob($data)
	{
		preg_match("/RewriteEngine\sOn(.+)/is", $data, $out);
		$htaccess = "\n##### mobile #####\n";
		$htaccess .= $out[0];

		// Add WPtouch specific rules if active
		if ($this->addSettings['wptouch']) {
			$wptouch_rule = "RewriteCond %{HTTP:Cookie} !wptouch-pro-view=desktop";
			$htaccess = str_replace("RewriteCond %{HTTP:Profile}", $wptouch_rule . "\n" . "RewriteCond %{HTTP:Profile}", $htaccess);
		}

		// Handle mobile switcher and user agent conditions
		$htaccess = str_replace("RewriteCond %{HTTP:Cookie} !safirmobilswitcher=mobil", "RewriteCond %{HTTP:Cookie} !safirmobilswitcher=masaustu", $htaccess);
		$htaccess = str_replace("RewriteCond %{HTTP_USER_AGENT} !^.*", "RewriteCond %{HTTP_USER_AGENT} ^.*", $htaccess);
		$htaccess = preg_replace("/\/index.%{ENV:REQ_EXT}/", "/bsmob/index.%{ENV:REQ_EXT}", $htaccess);
		$htaccess .= "\n##### mobile #####\n";

		return $htaccess;
	}

	/**
	 * Check if permalink structure uses trailing slashes
	 *
	 * Determines whether the WordPress permalink structure
	 * ends with a trailing slash, excluding Custom Permalinks plugin.
	 *
	 * @return bool True if trailing slash is used, false otherwise
	 */
	public function twosecond_is_trailing_slash()
	{
		if ($this->twosecond_is_plugin_active("custom-permalinks/custom-permalinks.php")) {
			return false;
		}
		if ($permalink_structure = get_option('permalink_structure')) {
			if (preg_match("/\/$/", $permalink_structure)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get excluded user agent patterns
	 *
	 * Returns a list of user agent patterns that should be
	 * excluded from caching and optimization.
	 *
	 * @return string Pipe-separated list of excluded user agents
	 */
	protected function twosecond_get_excluded_useragent()
	{
		return "facebookexternalhit|Twitterbot|LinkedInBot|WhatsApp|Mediatoolkitbot";
	}

	/**
	 * Generate .htaccess rules to exclude admin users from caching
	 *
	 * Creates .htaccess rules to prevent caching for logged-in
	 * administrator users, grouping them in chunks for efficiency.
	 *
	 * @return string .htaccess rules for admin user exclusions
	 */
	public function twosecond_exclude_admin_cookie()
	{
		$rules = "";
		$users_groups = array_chunk(get_users(array("role" => "administrator", "fields" => array("user_login"))), 5);
		foreach ($users_groups as $group_key => $group) {
			$tmp_users = "";
			$tmp_rule = "";
			foreach ($group as $key => $value) {
				if ($tmp_users) {
					$tmp_users = $tmp_users . "|" . sanitize_user(wp_unslash($value->user_login), true);
				} else {
					$tmp_users = sanitize_user(wp_unslash($value->user_login), true);
				}
				// to replace spaces with \s
				$tmp_users = preg_replace("/\s/", "\s", $tmp_users);
				if (!next($group)) {
					$tmp_rule = "RewriteCond %{HTTP:Cookie} !wordpress_logged_in_[^\=]+\=" . $tmp_users;
				}
			}
			if ($rules) {
				$rules = $rules . "\n" . $tmp_rule;
			} else {
				$rules = $tmp_rule;
			}
		}
		return "# Start_TWOSECOND_Exclude_Admin_Cookie\n" . $rules . "\n# End_TWOSECOND_Exclude_Admin_Cookie\n";
	}

	/**
	 * Generate prefix redirect rules for .htaccess
	 *
	 * Creates .htaccess rules to handle www/non-www redirects
	 * and HTTPS redirects based on the site configuration.
	 *
	 * @return string .htaccess redirect rules or empty string if disabled
	 */
	public function twosecond_prefix_redirect()
	{
		$forceTo = "";
		if (defined("TWOSECOND_DISABLE_REDIRECTION") && TWOSECOND_DISABLE_REDIRECTION) {
			return $forceTo;
		}

		// Handle HTTPS redirects
		if (preg_match("/^https:\/\//", $this->addSettings['home_url'])) {
			if (preg_match("/^https:\/\/www\./", $this->addSettings['home_url'])) {
				$forceTo = "\nRewriteCond %{HTTPS} =on" . "\n" .
					"RewriteCond %{HTTP_HOST} ^www." . str_replace("www.", "", $this->addSettings['twosecond_server']["HTTP_HOST"]) . "\n";
			} else {
				$forceTo = "\nRewriteCond %{HTTPS} =on" . "\n" .
					"RewriteCond %{HTTP_HOST} ^" . str_replace("www.", "", $this->addSettings['twosecond_server']["HTTP_HOST"]) . "\n";
			}
		} else {
			// Handle HTTP redirects
			if (preg_match("/^http:\/\/www\./", $this->addSettings['home_url'])) {
				$forceTo = "\nRewriteCond %{HTTP_HOST} ^" . str_replace("www.", "", $this->addSettings['twosecond_server']["HTTP_HOST"]) . "\n" .
					"RewriteRule ^(.*)$ " . preg_quote($this->addSettings['home_url'], "/") . "\/$1 [R=301,L]" . "\n";
			} else {
				$forceTo = "\nRewriteCond %{HTTP_HOST} ^www." . str_replace("www.", "", $this->addSettings['twosecond_server']["HTTP_HOST"]) . " [NC]" . "\n" .
					"RewriteRule ^(.*)$ " . preg_quote($this->addSettings['home_url'], "/") . "\/$1 [R=301,L]" . "\n";
			}
		}
		return $forceTo;
	}

	/**
	 * Get file contents using WordPress filesystem API
	 *
	 * Reads file contents using WordPress filesystem API with
	 * fallback to native PHP file functions.
	 *
	 * @param string $path Path to the file to read
	 * @return string|false File contents or false on failure
	 */
	function twosecond_get_contents($path)
	{
		if (!file_exists($path)) {
			return false;
		}
		if (!function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if (WP_Filesystem()) {
			$content = $wp_filesystem->get_contents($path);
		} else {
			// @codingStandardsIgnoreLine
			$content = file_get_contents($path);
		}
		return $content;
	}

	/**
	 * Write file contents using WordPress filesystem API
	 *
	 * Writes file contents using WordPress filesystem API with
	 * fallback to native PHP file functions.
	 *
	 * @param string $path    Path to the file to write
	 * @param string $content Content to write to the file
	 * @return int|false Number of bytes written or false on failure
	 */
	function twosecond_put_contents($path, $content, $append = false)
	{
		global $wp_filesystem;
		if (WP_Filesystem()) {
			$existing = '';
			if ($append) {
				$existing = $wp_filesystem->exists($path) ? $wp_filesystem->get_contents($path) : '';
			}
			$file = $wp_filesystem->put_contents($path, $existing . $content);
		}
		return $file;
	}

	/**
	 * Get asset URL
	 *
	 * Constructs the full URL for plugin assets.
	 *
	 * @param string $path Asset path relative to plugin directory
	 * @return string Full asset URL
	 */
	function twosecond_asset_url($path)
	{
		return TWOSECOND_URL . $path;
	}

	/**
	 * Get AJAX URL
	 *
	 * Returns the WordPress admin AJAX URL.
	 *
	 * @return string Admin AJAX URL
	 */
	function twosecond_get_ajax_url()
	{
		return admin_url('admin-ajax.php');
	}

	/**
	 * Escape URL
	 *
	 * Wrapper for WordPress URL escaping function with echo.
	 *
	 * @param string $text URL to escape
	 * @return void
	 */
	function twosecond_esc_url_echo($text)
	{
		echo esc_url($text);
	}

	/**
	 * Create secure nonce key
	 *
	 * Wrapper for WordPress nonce creation function.
	 *
	 * @param string $option Action name for the nonce
	 * @return string Generated nonce
	 */
	function twosecond_create_secure_key($option)
	{
		return wp_create_nonce($option);
	}

	/**
	 * Verify security nonce key
	 *
	 * Wrapper for WordPress nonce verification function.
	 *
	 * @param string $key    Nonce to verify
	 * @param string $option Action name for the nonce
	 * @return bool|int False on failure, 1 or 2 on success
	 */
	function twosecond_check_security_key($key, $option)
	{
		return wp_verify_nonce($key, $option);
	}

	/**
	 * Check if current page is a 404 error
	 *
	 * Determines whether the current page is a 404 error
	 * by checking HTTP status code or WordPress 404 function.
	 *
	 * @return bool True if 404 error, false otherwise
	 */
	function twosecond_is_404()
	{
		if ($this->statusCode >= 400) {
			return true;
		}
		return is_404();
	}

	/**
	 * Display import success notice
	 *
	 * Shows a success notice when data import is completed successfully.
	 */
	function twosecond_admin_notice_import_success()
	{
	?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e('Data imported successfully!', 'twosecond'); ?></p>
		</div>
	<?php
	}

	/**
	 * Remove advanced cache files
	 *
	 * Deletes both standard and TwoSecond advanced cache files
	 * to clean up caching remnants.
	 */
	function twosecond_remove_advance_cache_file()
	{
		$advancedCacheFile = $this->addSettings['content_path'] . '/advanced-cache.php';
		if (file_exists($advancedCacheFile)) {
			$this->twosecond_delete_file($advancedCacheFile);
		}
		$twoSecondAdvancedCacheFile = $this->addSettings['content_path'] . '/twosecond-advanced-cache.php';
		if (file_exists($twoSecondAdvancedCacheFile)) {
			$this->twosecond_delete_file($twoSecondAdvancedCacheFile);
		}
	}

	/**
	 * Enqueue admin head scripts and styles
	 *
	 */
	function twosecond_enqueue_admin_head() {
		if ( function_exists( 'wp_enqueue_code_editor' ) ) {
			$cm_settings['codeJs']  = wp_enqueue_code_editor( array( 'type' => 'text/javascript' ) );
			$cm_settings['codeCss'] = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
		} else {
			$cm_settings = array();
		}

		wp_add_inline_script(
			'twosecond-admin-bar-script',
			'var cm_settings = ' . wp_json_encode( $cm_settings ) . ';',
			'before'
		);
	}

	/**
	 * Check and configure HTML cache settings
	 *
	 * Handles HTML caching configuration based on user settings,
	 * including advanced cache file creation and .htaccess modifications.
	 */
	function twosecond_check_html_cache_settings($reset = false)
	{
		$this->twosecond_modify_htaccess();
		if ((!empty($this->addSettings['twosecond_post']['html_caching']) && $this->addSettings['twosecond_post']['by_serve_cache_file'] == 'advanceCache') || ($reset && $this->settings['by_serve_cache_file'] == 'advanceCache')) {
			$advancedCacheFile = $this->addSettings['content_path'] . '/advanced-cache.php';
			$this->twosecond_remove_html_cache_code();
			$this->twosecond_check_cache_true();
			if (file_exists($advancedCacheFile) && strpos($this->twosecond_get_contents($advancedCacheFile), 'Added By TwoSecond') === false) {
				$this->addSettings['advanced_cache_exist'] = 1;
			} else {
				$this->twosecond_create_advance_cache_file($advancedCacheFile);
			}
		} elseif ((!empty($this->addSettings['twosecond_post']['html_caching']) && $this->addSettings['twosecond_post']['by_serve_cache_file'] == 'htaccess') ||   ($reset && $this->settings['by_serve_cache_file'] == 'htaccess')) {
			$htaccessPath = $this->addSettings['document_root'] . "/.htaccess";
			if ($htaccessContent = $this->twosecond_get_contents($htaccessPath)) {
				$this->twosecond_remove_advance_cache_file();
				$htaccess = preg_replace("/#\s?BEGIN\s?BSHTMLCACHE.*?#\s?END\s?BSHTMLCACHE/s", "", $htaccessContent);
				$data = $this->twosecond_get_htaccess_data();
				if (!empty($this->htaccessModifyKeys['html_cache'])) {
					if ($this->twosecond_check_htaccess_status($data)) {
						$htaccess = $data . $htaccess;
					} else {
						$htaccess = $data . $htaccess;
						$this->twosecond_add_error('warning', 'Notice: Unable to verify .htaccess caching rules via loopback request. Rules have been applied, but please check if caching is working.');
					}
				} else {
					$htaccess = $data . $htaccess;
				}
				$this->twosecond_put_contents($htaccessPath, preg_replace('/^[ \t]*[\r\n]+/m', '', $htaccess));
			} else {
				return array("htaccess not writable", "twoSecond");
			}
		} elseif (empty($this->addSettings['twosecond_post']['html_caching'])) {
			$this->twosecond_remove_advance_cache_file();
			$this->twosecond_remove_html_cache_code();
		}
	}

	/**
	 * Create advanced cache file
	 *
	 * Creates the advanced cache file with TwoSecond-specific
	 * caching code, with fallback to alternative filename.
	 *
	 * @param string $advancedCacheFile Path to the advanced cache file
	 */
	function twosecond_create_advance_cache_file($advancedCacheFile)
	{
		$file_content = $this->twosecond_get_data_advanced_cache_file();
		$this->twosecond_put_contents($advancedCacheFile, $file_content);
		if (!is_file($advancedCacheFile)) {
			$advancedCacheFile = $this->addSettings['content_path'] . '/twosecond-advanced-cache.php';
			$this->twosecond_put_contents($advancedCacheFile, $file_content);
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * Loads all necessary CSS and JavaScript files for the admin interface,
	 * including third-party libraries and custom admin scripts.
	 */
	function twosecond_enqueue_admin_scripts()
	{
		wp_enqueue_style('wp-codemirror');

		wp_enqueue_style(
			'twosecond-style',
			TWOSECOND_URL . 'assets/css/twosecond.css',
			array(),
			time()
		);

		wp_enqueue_style(
			'twosecond-font-style',
			TWOSECOND_URL . 'assets/css/twosecond-fonts.css',
			array(),
			time()
		);

		wp_enqueue_style(
			'twosecond-ui-style',
			TWOSECOND_URL . 'assets/css/twosecond-ui.css',
			array(),
			time()
		);

		wp_enqueue_style(
			'twosecond-datatables-style',
			TWOSECOND_URL . 'assets/css/twosecond-datatables.css',
			array(),
			time()
		);

		wp_enqueue_style(
			'twosecond-jquery-select2',
			TWOSECOND_URL . 'assets/css/select2.min.css',
			array(),
			time()
		);

		wp_enqueue_style(
			'twosecond-admin-css',
			TWOSECOND_URL . 'assets/css/admin.css',
			array(),
			time()
		);

		wp_enqueue_script(
			'twosecond-datatables-script',
			TWOSECOND_URL . 'assets/js/twosecond-datatables.js',
			array('jquery'),
			time(),
			true
		);

		wp_enqueue_script('jquery-ui-datepicker');

		wp_enqueue_script(
			'bs-select2',
			TWOSECOND_URL . 'assets/js/select2.min.js',
			array('jquery'),
			time(),
			true
		);

		wp_enqueue_script(
			'bs-diff',
			TWOSECOND_URL . 'assets/js/diff.min.js',
			array('jquery'),
			time(),
			true
		);

		wp_enqueue_script(
			'twosecond-admin-script',
			TWOSECOND_URL . 'assets/js/ts-admin-core.js',
			array('jquery'),
			time(),
			true
		);

		wp_localize_script(
			'twosecond-admin-script',
			'twosecond_admin',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce'    => wp_create_nonce('twosecond_admin'),
				'license_nonce' => wp_create_nonce('twoSecondActivateLicense'),
			)
		);
	}

	/**
	 * Handle critical CSS cache purge requests
	 *
	 * Processes AJAX requests to purge critical CSS cache for
	 * specific pages or categories, with security validation.
	 */
	function twosecond_critical_cache_purge_callback()
	{
		if (!isset($this->addSettings['twosecond_get']['_twosecond_nonce']) || !$this->twosecond_check_security_key($this->addSettings['twosecond_get']['_twosecond_nonce'], 'purge_critical_css')) {
			return 'Request not valid';
		}

		$data_id = !empty($this->addSettings['twosecond_get']['data_id']) ? $this->addSettings['twosecond_get']['data_id'] : '';
		$data_type = !empty($this->addSettings['twosecond_get']['data_type']) ? $this->addSettings['twosecond_get']['data_type'] : '';
		if (!empty($data_id) && !empty($data_type)) {
			if ($data_type == 'category') {
				$url = get_term_link($data_id);
			} else {
				$url = get_permalink($data_id);
			}
			$path = $this->twosecond_preload_css_path($url);
			$this->twosecond_rmfiles($path);
			echo esc_html( round((int)$this->twosecond_get_option('twosecond_filesize') / 1024 / 1024, 2) );
		} else {
			$response = round((int)$this->twosecond_remove_critical_css_cache_files(), 2);
			echo esc_html( $response );
		}
		exit;
	}

	/**
	 * Handle cache purge requests
	 *
	 * Processes AJAX requests to purge various types of cache
	 * (HTML, general) with security validation and response handling.
	 */
	function twosecond_cache_purge_callback()
	{
		if (!isset($this->addSettings['twosecond_get']['_twosecond_nonce']) || !$this->twosecond_check_security_key($this->addSettings['twosecond_get']['_twosecond_nonce'], 'purge_cache')) {
			wp_send_json_error(array('message' => 'Request not valid'), 400);
			exit;
		}
		$data_cache = !empty($this->addSettings['twosecond_get']['data_cache']) ? $this->addSettings['twosecond_get']['data_cache'] : '';
		$data_id = !empty($this->addSettings['twosecond_get']['data_id']) ? $this->addSettings['twosecond_get']['data_id'] : '';
		$data_type = !empty($this->addSettings['twosecond_get']['data_type']) ? $this->addSettings['twosecond_get']['data_type'] : '';
		if (!empty($data_cache) && $data_cache == 'html' && !empty($data_id) && !empty($data_type)) {
			if ($data_type == 'category') {
				$url = get_term_link($data_id);
			} else {
				$url = get_permalink($data_id);
			}
			$path = $this->twosecond_get_html_cache_path($url);
			$this->twosecond_rmfiles($path);
			$response = round($this->twosecond_cache_size_callback() / 1024 / 1024, 2);
		} elseif (!empty($data_cache) && $data_cache == 'html') {
			$response = round($this->twosecond_remove_cache_files_hourly_event_callback('html'), 2);
		} else {
			$response = round($this->twosecond_remove_cache_files_hourly_event_callback(), 2);
			$response = round($this->twosecond_remove_cache_files_hourly_event_callback('html'), 2);
		}
		$this->twosecond_response( array( 'filesize' => (float) $response ) );
		exit;
	}

	/**
	 * Handle HTML cache purge requests
	 *
	 * Processes AJAX requests to purge HTML cache specifically,
	 * with security validation.
	 */
	function twosecond_html_cache_purge_callback()
	{
		if (isset($this->addSettings['twosecond_get']['_twosecond_nonce']) && $this->twosecond_check_security_key($this->addSettings['twosecond_get']['_twosecond_nonce'], 'purge_html_cache')) {
			$response = round($this->twosecond_remove_cache_files_hourly_event_callback('html'), 2);
		}
		exit;
	}

	/**
	 * Modify .htaccess file with TwoSecond rules
	 *
	 * Adds or removes various .htaccess rules including LBC, Gzip,
	 * WebP, and 404 redirect rules based on plugin settings.
	 *
	 * @return array Status message and type
	 */
	function twosecond_modify_htaccess()
	{
		$path = $this->addSettings['document_root'] . '/';
		if (!file_exists($path . ".htaccess")) {
			if (isset($this->addSettings['twosecond_server']["SERVER_SOFTWARE"]) && $this->addSettings['twosecond_server']["SERVER_SOFTWARE"] && (preg_match("/iis/i", $this->addSettings['twosecond_server']["SERVER_SOFTWARE"]) || preg_match("/nginx/i", $this->addSettings['twosecond_server']["SERVER_SOFTWARE"]))) {
				//
			} else {
				return array("<label>.htaccess was not found</label>", "twoSecond");
			}
		}

		$htaccess = $this->twosecond_get_contents($path . ".htaccess");

		if (!get_option('permalink_structure')) {
			return array("You have to set <strong><u><a href='" . admin_url() . "options-permalink.php" . "'>permalinks</a></u></strong>", "twoSecond");
		}
		// @codingStandardsIgnoreLine
		else if ($this->twosecond_check_is_writable($path . ".htaccess")) {
			$change_in_htaccess = 0;
			if (!empty($this->settings['lbc'])) {
				if (strpos($htaccess, '# BEGIN BSLBC') === false || strpos($htaccess, '# END BSLBC') === false) {
					$htaccess = $this->twosecond_insert_lbc_rule($htaccess) . "\n";
					$change_in_htaccess = 1;
				}
			} elseif (strpos($htaccess, '# BEGIN BSLBC') !== false || strpos($htaccess, '# END BSLBC') !== false) {
				$htaccess = preg_replace("/#\s?BEGIN\s?BSLBC.*?#\s?END\s?BSLBC/s", "", $htaccess);
				$change_in_htaccess = 1;
			}
			if (!empty($this->settings['gzip'])) {
				if (strpos($htaccess, '# BEGIN BSGzip') === false || strpos($htaccess, '# END BSGzip') === false) {
					$htaccess = $this->twosecond_insert_gzip_rule($htaccess);
					$change_in_htaccess = 1;
				}
			} elseif (strpos($htaccess, '# BEGIN BSGzip') !== false || strpos($htaccess, '# END BSGzip') !== false) {
				$htaccess = preg_replace("/\s*\#\s?BEGIN\s?BSGzip.*?#\s?END\s?BSGzip\s*/s", "", $htaccess);
				$change_in_htaccess = 1;
			}

			if (!empty($this->addSettings['disable_htaccess_webp']) && !$this->twosecond_check_enable_cdn('image')) {
				if (!empty($this->settings['webp_png']) || !empty($this->settings['webp_jpg'])) {
					if (strpos($htaccess, '# BEGIN TSWEBP') === false || strpos($htaccess, '# END TSWEBP') === false) {
						$htaccess = $this->twosecond_insert_webp($htaccess) . "\n";
						$change_in_htaccess = 1;
					}
				} elseif (strpos($htaccess, '# BEGIN TSWEBP') !== false || strpos($htaccess, '# END TSWEBP') !== false) {
					$htaccess = preg_replace("/#\s?BEGIN\s?TSWEBP.*?#\s?END\s?TSWEBP/s", "", $htaccess);
					$change_in_htaccess = 1;
				}
			} elseif (strpos($htaccess, '# BEGIN TSWEBP') !== false || strpos($htaccess, '# END TSWEBP') !== false) {
				$htaccess = preg_replace("/#\s?BEGIN\s?TSWEBP.*?#\s?END\s?TSWEBP/s", "", $htaccess);
				$change_in_htaccess = 1;
			}
			if (strpos($htaccess, '# BEGIN BS404') === false || strpos($htaccess, '# END BS404') === false) {
				$htaccess = $this->twosecond_insert404_redirect_to_file($htaccess);
				$change_in_htaccess = 1;
			}
			if ($change_in_htaccess) {
				$this->twosecond_put_contents($path . ".htaccess", $htaccess);
			}
		} else {
			return array( __( 'Options have been saved', 'twosecond' ), 'updated' );
		}
		return array( __( 'Options have been saved', 'twosecond' ), 'updated' );
	}

	/**
	 * Insert WebP .htaccess rules
	 *
	 * Adds .htaccess rules for WebP image serving, including
	 * rewrite conditions and rules for serving WebP images
	 * when the browser supports them.
	 *
	 * @param string $htaccess Current .htaccess content
	 * @return string Modified .htaccess content with WebP rules
	 */
	function twosecond_insert_webp($htaccess)
	{
		$wp_content_arr = explode('/', trim($this->addSettings['content_path'], '/'));
		$wp_content = array_pop($wp_content_arr);
		$wp_content_webp = $wp_content . "/ts-webp/";
		$basename = $wp_content_webp . "$1.webp";
		if (preg_match("/https?\:\/\/[^\/]+\/(.+)/", $this->addSettings['site_url'], $siteurl_base_name)) {
			if (preg_match("/https?\:\/\/[^\/]+\/(.+)/", $this->addSettings['home_url'], $homeurl_base_name)) {
				$homeurl_base_name[1] = trim($homeurl_base_name[1], "/");
				$siteurl_base_name[1] = trim($siteurl_base_name[1], "/");

				if ($homeurl_base_name[1] == $siteurl_base_name[1]) {
					if (preg_match("/" . preg_quote($homeurl_base_name[1], "/") . "$/", trim(ABSPATH, "/"))) {
						$basename = $homeurl_base_name[1] . "/" . $basename;
					}
				}
			} else {
				$siteurl_base_name[1] = trim($siteurl_base_name[1], "/");
				$basename = $siteurl_base_name[1] . "/" . $basename;
			}
		}

		if ($this->addSettings['document_root'] == "//") {
			$RewriteCond = "RewriteCond %{DOCUMENT_ROOT}/" . $basename . " -f" . "\n";
		} else {
			$tmp_ABSPATH = str_replace(" ", "\ ", $this->addSettings['document_root']);

			$RewriteCond = "RewriteCond %{DOCUMENT_ROOT}/" . $basename . " -f [or]" . "\n";
			$RewriteCond = $RewriteCond . "RewriteCond " . $tmp_ABSPATH . $wp_content_webp . "$1.$2.webp -f" . "\n";
		}

		$data = "\n" . "# BEGIN TSWEBP" . "\n" .
			"<IfModule mod_rewrite.c>" . "\n" .
			"RewriteEngine On" . "\n" .
			"RewriteCond %{HTTP_ACCEPT} image/webp" . "\n" .
			"RewriteCond %{REQUEST_URI} \.(jpe?g|png)" . "\n" .
			$RewriteCond .
			"RewriteRule ^" . $wp_content . "/([^/]+/.+)\.(jpe?g|png)$ /" . $wp_content_webp . "$1.$2.webp [L]" . "\n" .
			"</IfModule>" . "\n" .
			"<IfModule mod_headers.c>" . "\n" .
			"Header append Vary Accept env=REDIRECT_accept" . "\n" .
			"</IfModule>" . "\n" .
			"AddType image/webp .webp" . "\n" .
			"# END TSWEBP" . "\n";
		$htaccess = preg_replace("/#\s?BEGIN\s?TSWEBP.*?#\s?END\s?TSWEBP/s", "", $htaccess);
		if (!empty($this->htaccessModifyKeys['webp'])) {
			if ($this->twosecond_check_htaccess_status($data)) {
				$htaccess = $data . $htaccess;
			} else {
				unset($this->settings['webp_png'], $this->settings['webp_jpg']);
				$this->twosecond_update_option('twosecond_option', $this->settings, 'no');
				$this->twosecond_add_error('danger', 'Server error: Unable to apply .htaccess webp rules to the site.');
			}
		} else {
			$htaccess = $data . $htaccess;
		}
		return $htaccess;
	}

	/**
	 * Parse URL
	 *
	 * Wrapper for WordPress URL parsing function.
	 *
	 * @param string $url URL to parse
	 * @return array|false Parsed URL components or false on failure
	 */
	function twosecond_parse_url($url, $component = '')
	{
		if ($component) {
			return wp_parse_url($url, $component);
		}
		return wp_parse_url($url);
	}

	/**
	 * Get Web Vitals log data
	 *
	 * Retrieves and formats Web Vitals performance data from the database
	 * with filtering and pagination support.
	 *
	 * @return string HTML table with log data and pagination
	 */
	function twosecond_get_log_data()
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_core_web_vitals';
		$limit = isset($this->addSettings['twosecond_post']['limit']) ? (int) $this->addSettings['twosecond_post']['limit'] : 10;
		$paged = isset($this->addSettings['twosecond_post']['paged']) ? (int) $this->addSettings['twosecond_post']['paged'] : 1;
		$offset = isset($this->addSettings['twosecond_post']['paged']) ? ($paged - 1) * $limit : 0;
		$conditions = array();
		$placeholders = array();
		$sql = "SELECT * FROM %i";
		$sql_params = array($table_name);
		
		if (isset($this->addSettings['twosecond_post']['issuetype']) && !empty($this->addSettings['twosecond_post']['issuetype'])) {
			$conditions[] = "issuetype = %s";
			$placeholders[] = sanitize_text_field($this->addSettings['twosecond_post']['issuetype']);
		}
		if (isset($this->addSettings['twosecond_post']['deviceType']) && !empty($this->addSettings['twosecond_post']['deviceType'])) {
			$conditions[] = "deviceType = %s";
			$placeholders[] = sanitize_text_field($this->addSettings['twosecond_post']['deviceType']);
		}
		if (isset($this->addSettings['twosecond_post']['url']) && !empty($this->addSettings['twosecond_post']['url'])) {
			$url_conditions = array();
			foreach ($this->addSettings['twosecond_post']['url'] as $url) {
				$url_conditions[] = "url = %s";
				$placeholders[] = esc_url_raw($url);
			}
			// Combine URL conditions using OR
			$conditions[] = '(' . implode(" OR ", $url_conditions) . ')';
		}
		if (isset($this->addSettings['twosecond_post']['start_date']) && !empty($this->addSettings['twosecond_post']['start_date']) && isset($this->addSettings['twosecond_post']['end_date']) && !empty($this->addSettings['twosecond_post']['end_date'])) {
			$start_date = (int) strtotime($this->addSettings['twosecond_post']['start_date']);
			$end_date = (int) strtotime($this->addSettings['twosecond_post']['end_date']) + 86400;
			$conditions[] = "UNIX_TIMESTAMP(timestamp) BETWEEN %d AND %d";
			$placeholders[] = $start_date;
			$placeholders[] = $end_date;
		}
		if (!empty($conditions)) {
			$sql .= " WHERE " . implode(" AND ", $conditions);
		}
		
		// Merge params for prepared statement
		$sql_params = array_merge($sql_params, $placeholders);
		$prepared_sql = $this->twosecond_database->twosecond_prepare($sql, $sql_params);
		$logResultPagination = $this->twosecond_database->twosecond_get_results($prepared_sql);
		
		if (count($logResultPagination) < $limit) {
			$offset  = 0;
		}
		$sql .= " ORDER BY id DESC LIMIT %d OFFSET %d";
		$sql_params[] = $limit;
		$sql_params[] = $offset;
		$logResult = $this->twosecond_database->twosecond_get_results($this->twosecond_database->twosecond_prepare($sql, $sql_params));

		$logData = '';
		if ( $logResult ) {
			$logData .= '<table class="webvitals-table"><thead><th>ID</th><th>Url</th><th>Issue Type</th><th>Device Type</th><th>Data</th><th>Time</th></thead><tbody>';
			foreach ( $logResult as $entry ) {
				$entry_id = (int) $entry->id;
				$prettyJsonString = wp_json_encode(json_decode(stripcslashes($entry->data), true),JSON_PRETTY_PRINT);
				$logData .='<tr><td class="id_' . esc_attr( $entry_id ) . '">' . esc_html( $entry_id ) . '</td><td class="url url_' . esc_attr( $entry_id ) . '">' . esc_html( urldecode($entry->url) ) . '</td><td class="issueType_' . esc_attr( $entry_id ) . '">' . esc_html( $entry->issuetype ) . '</td><td class="deviceType_' . esc_attr( $entry_id ) . '">' . esc_html( $entry->deviceType ) . '</td><td class="data_' . esc_attr( $entry_id ) . '"><div class="log-data">' . esc_html( $prettyJsonString ) . '</div><button data-id="' . esc_attr( $entry_id ) . '" class="more_info" type="button" popovertarget="more_info" popovertargetaction="show">View More</button></td><td class="time_' . esc_attr( $entry_id ) . '">' . esc_html( $entry->timestamp ) . '</td></tr>';
			}
			$page = (int) ceil(count($logResultPagination)/$limit);
			$logData .= '</tbody></table><div class="pagination" data-last="' . esc_attr( $page ) . '">';
			if($paged > 1){
				$logData .= '<button type="button" class="page-prev" data-page="1">&lt;&lt;</button><button type="button" class="page-prev" data-page="' . esc_attr( $paged ) . '">&lt;</button>';
			}
			for($i=1; $i <= $page;$i++){
				if($paged == 1){
					$activeClass = $i == 1 ? 'active' : '';
				}else{
					$activeClass = $i == $paged ? 'active' : '';
				}
				if(($paged <= 2 && $i <= 5) || ($paged >= 3 && $i >= ($paged-2) && $i <= ($paged+2) )){
					$logData .= '<button type="button" class="p-num ' . esc_attr( $activeClass ) . '" data-page="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</button>';
				}
			}
			if($paged < $page){
				$logData .= '<button type="button" class="page-next" data-page="' . esc_attr( $paged ) . '">&gt;</button><button type="button" class="page-next-last" data-page="' . esc_attr( $page ) . '">&gt;&gt;</button>';
			}
			$logData .= '</div>';
		} else {
			$logData = '<div class="no-data-found-log">No Data Found</div>';
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All output is properly escaped above
		echo $logData;
		
	}

	/**
	 * Get settings change log data
	 *
	 * Retrieves and formats settings change log data from the database
	 * with filtering and pagination support.
	 *
	 * @return string HTML table with change log data and pagination
	 */
	function twosecond_get_change_log_data(){
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_change_logs';
		$limit = isset($this->addSettings['twosecond_post']['limit']) ? (int) $this->addSettings['twosecond_post']['limit'] : 10;
		$paged = isset($this->addSettings['twosecond_post']['paged']) ? (int) $this->addSettings['twosecond_post']['paged'] : 1;
		$offset = ($paged - 1) * $limit;
		$conditions = array();
		$sql = "SELECT * FROM %i";
		if (isset($this->addSettings['twosecond_post']['start_date']) && !empty($this->addSettings['twosecond_post']['start_date']) && isset($this->addSettings['twosecond_post']['end_date']) && !empty($this->addSettings['twosecond_post']['end_date'])) {
			$start_date = (int) strtotime($this->addSettings['twosecond_post']['start_date']);
			$end_date = (int) strtotime($this->addSettings['twosecond_post']['end_date']) + 86400;
			$conditions[] = "UNIX_TIMESTAMP(time) BETWEEN $start_date AND $end_date";
		}
		if (!empty($conditions)) {
			$sql .= " WHERE " . implode(" AND ", $conditions);
		}
		// Get total count for pagination
		$countSql = "SELECT COUNT(*) as total FROM %i";
		if (!empty($conditions)) {
			$countSql .= " WHERE " . implode(" AND ", $conditions);
		}
		$logResultPagination = $this->twosecond_database->twosecond_get_results($this->twosecond_database->twosecond_prepare($countSql, [$table_name]));
		$totalCount = $logResultPagination[0]->total ?? 0;
		
		$sql .= " ORDER BY id DESC LIMIT %d OFFSET %d";
		$logResult = $this->twosecond_database->twosecond_get_results($this->twosecond_database->twosecond_prepare($sql, [$table_name, $limit, $offset]));
		$logData = '';
		if ($logResult) {
			$logData .= '<table class="bschangelogs-table"><thead><th>ID</th><th>Time</th><th>User</th><th>Ip</th><th>Action</th><th>Previous</th><th>New</th></thead><tbody>';
			foreach ($logResult as $entry) {
				$entry_id = (int) $entry->id;
				$oldContent = (strlen($entry->old) > 20) ? substr($entry->old, 0, 20) . '...' : $entry->old;
				$newContent = (strlen($entry->new) > 20) ? substr($entry->new, 0, 20) . '...' : $entry->new;
				$oldShowMoreButton = (strlen($entry->old) > 20) ? '<button class="show-more" type="button" data-target="old_' . esc_attr( $entry_id ) . '">Show More</button>' : '';
				$newShowMoreButton = (strlen($entry->new) > 20) ? '<button class="show-more" type="button" data-target="new_' . esc_attr( $entry_id ) . '">Show More</button><button class="show-diff" type="button" data-target-new="new_' . esc_attr( $entry_id ) . '" data-target-old="old_' . esc_attr( $entry_id ) . '">Show Difference</button>' : '';
				$logData .= '<tr>
							<td class="id_' . esc_attr( $entry_id ) . '">' . esc_html( $entry_id ) . '</td>
							<td class="time_' . esc_attr( $entry_id ) . '">' . esc_html( $entry->time ) . '</td>
							<td class="time_' . esc_attr( $entry_id ) . '">' . esc_html( $entry->user ) . '</td>
							<td class="time_' . esc_attr( $entry_id ) . '">' . esc_html( $entry->ip ) . '</td>
							<td class="time_' . esc_attr( $entry_id ) . '">' . esc_html( $entry->action ) . '</td>
							<td class="time_' . esc_attr( $entry_id ) . '">
								<div id="old_' . esc_attr( $entry_id ) . '" style="display:none;"><pre>' . esc_html( $entry->old ) . '</pre></div>
								<div class="old-content" ><pre class="change-log-pre">' . esc_html( $oldContent ) . '</pre></div>
								' . $oldShowMoreButton . '
							</td>
							<td class="time_' . esc_attr( $entry_id ) . '">
								<div id="new_' . esc_attr( $entry_id ) . '" style="display:none;"><pre>' . esc_html( $entry->new ) . '</pre></div>
								<div class="new-content" id="new_' . esc_attr( $entry_id ) . '" ><pre class="change-log-pre">' . esc_html( $newContent ) . '</pre></div>
								' . $newShowMoreButton . '
							</td>
						</tr>';
			}

			$page = (int) ceil($totalCount / $limit);
			$logData .= '</tbody></table><div class="pagination" data-last="' . esc_attr( $page ) . '">';
			if ($paged > 1) {
				$logData .= '<button type="button" class="change-page-prev" data-page="1">&lt;&lt;</button><button type="button" class="change-page-prev" data-page="' . esc_attr( $paged ) . '">&lt;</button>';
			}
			for ($i = 1; $i <= $page; $i++) {
				if ($paged == 1) {
					$activeClass = $i == 1 ? 'active' : '';
				} else {
					$activeClass = $i == $paged ? 'active' : '';
				}
				if (($paged <= 2 && $i <= 5) || ($paged >= 3 && $i >= ($paged - 2) && $i <= ($paged + 2))) {
					$logData .= '<button type="button" class="change-p-num ' . esc_attr( $activeClass ) . '" data-page="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</button>';
				}
			}
			if ($paged < $page) {
				$logData .= '<button type="button" class="change-page-next" data-page="' . esc_attr( $paged ) . '">&gt;</button><button type="button" class="change-page-next-last" data-page="' . esc_attr( $page ) . '">&gt;&gt;</button>';
			}
			$logData .= '</div>';
		} else {
			$logData = '<div class="no-data-found-log">No Data Found</div>';
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All output is properly escaped above
		echo $logData;
		
	}

	/**
	 * Check if current route is a special route
	 *
	 * Determines whether the current URL is a special route
	 * that should not be cached (e.g., REST API, login, admin).
	 *
	 * @return bool True if special route, false otherwise
	 */
	public function twosecond_is_special_route()
	{
		$current_url = $this->addSettings['full_url'];

		if (preg_match('/(.*\/wp\/v2\/.*)/', $current_url)) {
			return true;
		}

		if (preg_match('/(.*wp-login.*)/', $current_url)) {
			return true;
		}

		if (preg_match('/(.*wp-admin.*)/', $current_url)) {
			return true;
		}

		return false;
	}

	/**
	 * Check if HTML cache should be deleted
	 *
	 * Determines whether HTML cache should be cleared based on
	 * plugin settings for page/post updates.
	 *
	 * @return bool|int True if cache cleared, false otherwise
	 */
	function twosecond_check_html_cache_delete()
	{
		if (!empty($this->settings['clear_cache_page_post_updated'])) {
			return $this->twosecond_remove_cache_files_hourly_event_callback('html');
		}
		return false;
	}

	/**
	 * Create HTML cache file
	 *
	 * Creates a cached HTML file with performance metrics
	 * and mobile detection information.
	 *
	 * @param string $html HTML content to cache
	 * @return string Modified HTML content
	 */
	function twosecond_create_html_file($html)
	{
		$isMobile = $this->twosecond_is_mobile_device();
		$mob_msg = '';
		if ($isMobile) {
			$mob_msg = 'Mobile';
		}
		$fileName = $this->cacheFilePath;
		$endtime = $this->twosecond_microtime_float();
		$current_time = gmdate("Y-m-d H:i:s T");
		$html .= '<!--' . $mob_msg . ' Cache Created By TwoSecond at ' . $current_time . ' in ' . number_format($endtime - $this->addSettings['starttime'], 2) . ' secs-->';
		$this->twosecond_create_file($fileName, $html);
	}

	/**
	 * Delete old Core Web Vitals logs
	 *
	 * Removes Core Web Vitals log entries older than 3 months
	 * to maintain database performance.
	 *
	 * @return bool True on success
	 */
	function twosecond_delete_core_web_vitals_log()
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_core_web_vitals';
		$sql = $this->twosecond_database->twosecond_prepare("DELETE FROM %i WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL %d MONTH)", [$table_name, 3]);
		$this->twosecond_database->twosecond_query($sql);
		return true;
	}

	/**
	 * Delete old settings change logs
	 *
	 * Removes settings change log entries older than 3 months
	 * to maintain database performance.
	 *
	 * @return bool True on success
	 */
	function twosecond_delete_settings_change_log(){
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_change_logs';
		$sql = $this->twosecond_database->twosecond_prepare("DELETE FROM %i WHERE time < DATE_SUB(CURDATE(), INTERVAL %d MONTH)", [$table_name, 3]);
		$this->twosecond_database->twosecond_query($sql);
		return true;
	}

	/**
	 * Get user IP address
	 *
	 * Retrieves the real IP address of the current user,
	 * handling various proxy and forwarding scenarios.
	 *
	 * @return string User IP address
	 */
	private function twosecond_get_user_ip()
	{
		if (! empty($this->core->addSettings['twosecond_server']['HTTP_CLIENT_IP'])) {
			$ip = $this->core->addSettings['twosecond_server']['HTTP_CLIENT_IP'];
		} elseif (! empty($this->core->addSettings['twosecond_server']['HTTP_X_FORWARDED_FOR'])) {
			$ip = $this->core->addSettings['twosecond_server']['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $this->core->addSettings['twosecond_server']['REMOTE_ADDR'] ?? '';
		}
		return $ip;
	}

	/**
	 * Log settings changes
	 *
	 * Records changes to plugin settings in the database
	 * for audit and debugging purposes.
	 *
	 * @param array $changes Array of changes with old/new values
	 */
	function twosecond_log_settings_changes($changes) {
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_change_logs';
		$time = gmdate('Y-m-d H:i:s');
		$ip = $this->twosecond_get_user_ip();
		$currentUser = wp_get_current_user();
		$currentUserEmail = $currentUser->user_email;
		foreach ($changes as $change) {
			$old_value = is_array( $change['old'] ) ? wp_json_encode( $change['old'] ) : (string) $change['old'];
			$new_value = is_array( $change['new'] ) ? wp_json_encode( $change['new'] ) : (string) $change['new'];
			$sql = "INSERT INTO %i (time, user, ip, action, old, new) VALUES (%s, %s, %s, %s, %s, %s)";
			$prepared = $this->twosecond_database->twosecond_prepare($sql, [$table_name, $time, $currentUserEmail, $ip, (string) $change['action'], $old_value, $new_value]);
			$this->twosecond_database->twosecond_query($prepared);
		}
	}

	/**
	 * Get user hash from cookies
	 *
	 * Extracts the user hash from WordPress login cookies
	 * for caching and user identification purposes.
	 *
	 * @return string User hash or empty string
	 */
	function twosecond_get_user_hash()
	{
		$hash = '';
		foreach ($_COOKIE as $key => $value) {
			if (strpos($key, 'wordpress_logged_in_') === 0) {
				// Sanitize cookie value before processing
				$sanitized_value = sanitize_text_field( wp_unslash( $value ) );
				$parts = explode('|', $sanitized_value);
				$hash = trim(($parts[0] ?? ''));
				break;
			}
		}
		return $hash;
	}

	/**
	 * Perform remote POST request
	 *
	 * Makes HTTP POST requests to external URLs with configurable
	 * content type (JSON or form data).
	 *
	 * @param string $url        The URL to request
	 * @param array  $body       Data to send
	 * @param bool   $sendAsJson Whether to send as JSON
	 * @param bool   $mobile     Whether to use mobile user agent
	 * @param bool   $blocking   Whether to block the request
	 * @param string $output     Whether to return the body or the response code
	 * @return string Response body or empty string on failure
	 */
	function twosecond_remote_post($url, $body, $sendAsJson = true, $mobile = false, $blocking = true, $output = 'body')
	{
		if (function_exists('wp_remote_post')) {
			if ($sendAsJson) {
				$headers = ['Content-Type' => 'application/json'];
				$bodyToSend = $this->twosecond_json_encode($body);
			} else {
				$headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
				$bodyToSend = http_build_query($body);
			}
			$headers['User-Agent'] = $mobile ? 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Mobile Safari/537.36' : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';
			$options = [
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => $bodyToSend,
				'timeout'   => $blocking ? 30 : 3,
			];
			$response = wp_remote_post($url, $options);
			if (!is_wp_error($response)) {
				if ($output == 'body') {
					return wp_remote_retrieve_body($response);
				} else {
					return wp_remote_retrieve_response_code($response);
				}
			}
		}
		return '';
	}

	/**
	 * Get AI optimization table data
	 *
	 * Retrieves and formats AI optimization data from the database
	 * with filtering, pagination, and status indicators.
	 *
	 * @param array $data Request parameters including pagination and filters
	 * @return array Table data with HTML, pagination info, and statistics
	 */
	function twosecond_get_optimize_ai_table_data($data)
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		$rowsPerPage = intval($data['rows']) ?? 10;
		$page = intval($data['page']) ?? 1;
		$status = $data['status'];
		$url = isset($data['url']) ? trim($data['url']) : '';
		$page = max($page, 1);
		$offset = ($page - 1) * $rowsPerPage;
		$placeholders = implode(',', array_fill(0, count($status), '%d'));

		$whereSQL    = "status IN ($placeholders)";
		$whereParams = $status;
		if ($url !== '') {
			if ($this->addSettings['site_url'] === rtrim($url, '/')) {
				$whereSQL     .= " AND url = '/'";
			} else {
				$url = str_replace($this->addSettings['site_url'], '', $url);
				$whereSQL     .= " AND url LIKE %s";
				$whereParams[] = '%' . $this->twosecond_database->twosecond_esc_like($url) . '%';
			}
		}
		$total_rows = $this->twosecond_database->twosecond_get_var(
			$this->twosecond_database->twosecond_prepare(
				"SELECT COUNT(*) FROM %i WHERE $whereSQL",
				array_merge([$table_name], $whereParams)
			)
		);
		$total_pages = ceil($total_rows / $rowsPerPage);
		$params = array_merge([$table_name], $whereParams, [$rowsPerPage, $offset]);
		$results = $this->twosecond_database->twosecond_get_results(
			$this->twosecond_database->twosecond_prepare("SELECT * FROM %i WHERE $whereSQL ORDER BY updated_at DESC LIMIT %d OFFSET %d", $params)
		);

		$max_pages_each_side = 1;
		$start_page = max(1, $page - $max_pages_each_side);
		$end_page = min($total_pages, $page + $max_pages_each_side);

		$table_html = '<table class="optimize-url-table">';
		$table_html .= '
			<thead>
				<tr>
					<th>URL</th>
					<th style="width: 10%;">Desktop</th>
					<th style="width: 10%;">Mobile</th>
					<th style="width: 12%;">Timestamp</th>
					<th style="width: 10%;">Actions</th>
				</tr>
			</thead>
			<tbody>';

		if (!empty($results)) {
			foreach ($results as $row) {
				$url = $this->twosecond_change_url_relative_to_absolute($row->url);
				$short_url = strlen($url) > 100 ? substr($url, 0, 100) . '...' : $url;
				$status = $row->status;
				switch ((int) $status) {
					case 0:
						$badgeMobClass = $badgeDeskClass = 'pending';
						$badgeMobLabel = $badgeDeskLabel = '<i class="fa fa-hourglass-o" aria-hidden="true"></i>';
						break;
					case 1:
						$badgeMobClass = $badgeDeskClass = 'inProgress';
						$badgeMobLabel = $badgeDeskLabel = '<div class="dots"></div>';
						break;
					case 2:
						$badgeDeskClass = 'optimized';
						$badgeDeskLabel = '<i class="fa fa-check" aria-hidden="true"></i>';
						$badgeMobClass = 'inProgress';
						$badgeMobLabel = '<div class="dots"></div>';
						break;
					case 3:
						$badgeMobClass = 'optimized';
						$badgeMobLabel = '<i class="fa fa-check" aria-hidden="true"></i>';
						$badgeDeskClass = 'inProgress';
						$badgeDeskLabel = '<div class="dots"></div>';
						break;
					case 4:
					case 6:
						$badgeMobClass = $badgeDeskClass = 'optimized';
						$badgeMobLabel = $badgeDeskLabel = '<i class="fa fa-check" aria-hidden="true"></i>';
						break;
					default:
						$badgeMobClass = $badgeDeskClass = 'error';
						$badgeMobLabel = $badgeDeskLabel = '<i class="fa fa-times" aria-hidden="true"></i>';
				}
				$updated_at = esc_html($row->updated_at);

				$actions = '';
				if ($status != 0) {
					$actions .= '<button type="button" class="btn actions optimize-with-ai-btn" data-id="' . $row->id . '" data-url="' . esc_attr($url) . '"><i class="fa fa-refresh" aria-hidden="true"></i></button>';
				}
				if ($status == 0) {
					$actions .= '<button type="button" class="btn actions optimize-with-ai-btn now"  data-id="' . $row->id . '" data-url="' . esc_attr($url) . '"><i class="fa fa-bolt" aria-hidden="true"></i></button>';
				}

				$actions .= '<button type="button" class="btn actions optimize-with-ai-url-copy"  data-id="' . $row->id . '" data-url="' . esc_attr($url) . '"><i class="fa fa-copy" aria-hidden="true"></i></button>';

				if (in_array((int)$status, [4, 6])) {
					$actions .= '<button type="button" class="btn actions view-page-score-now" data-url="' . esc_attr($url) . '"><i class="fa fa-tachometer" aria-hidden="true"></i></button>';
				}

				$table_html .= "
					<tr>
						<td><a href=\"$url\" target=\"_blank\" rel=\"noopener noreferrer\">$short_url</a><span>$url</span></td>
						<td><span class=\"badge $badgeDeskClass\">$badgeDeskLabel</span></td>
						<td><span class=\"badge $badgeMobClass\">$badgeMobLabel</span></td>
						<td>$updated_at</td>
						<td>$actions</td>
					</tr>";
			}
		} else {
			$table_html .= '<tr><td colspan="4">No URLs found.</td></tr>';
		}

		$table_html .= '</tbody></table>';
		$table_html .= '<div class="pagination" data-last="' . esc_attr($total_pages) . '">';

		if ($start_page > 1) {
			$table_html .= '<button type="button" class="change-p-num-oi" data-page="1">1</button>';
			if ($start_page > 2) {
				$table_html .= '<span>...</span>';
			}
		}

		for ($i = $start_page; $i <= $end_page; $i++) {
			$active = ($i == $page) ? 'active' : '';
			$table_html .= '<button type="button" class="change-p-num-oi ' . $active . '" data-page="' . $i . '">' . $i . '</button>';
		}

		if ($end_page < $total_pages) {
			if ($end_page < $total_pages - 1) {
				$table_html .= '<span>...</span>';
			}
			$table_html .= '<button type="button" class="change-p-num-oi" data-page="' . $total_pages . '">' . $total_pages . '</button>';
		}

		if ($page < $total_pages) {
			$table_html .= '<button type="button" class="change-page-next-oi" data-page="' . ($page + 1) . '">></button>';
			$table_html .= '<button type="button" class="change-page-next-last-oi" data-page="' . $total_pages . '">>></button>';
		}

		$table_html .= '</div>';

		$total_rows = $this->twosecond_database->twosecond_get_var($this->twosecond_database->twosecond_prepare("SELECT COUNT(*) FROM %i", [$table_name]));
		$optimized_count = $this->twosecond_database->twosecond_get_var($this->twosecond_database->twosecond_prepare("SELECT COUNT(*) FROM %i WHERE status IN (%d, %d)", [$table_name, 4, 6]));

		if ($total_rows <= 0 || $optimized_count <= 0) {
			$percentage = 0;
		} else {
			$percentage = number_format(($optimized_count / $total_rows) * 100, 2);
		}

		$time = '0 minutes remaining';
		$remainingPages = $total_rows - $optimized_count;
		if ($remainingPages > 0) {
			$pagePerBatch = !empty($this->settings['page_batch']) ? $this->settings['page_batch'] : (!empty($this->settings['preload_per_min']) ? $this->settings['preload_per_min'] : 1);
			$pagePerBatch = (int) $pagePerBatch;
			$pagePerBatch = $pagePerBatch > 0 ? $pagePerBatch : 1;
			$minutes = ceil($remainingPages / $pagePerBatch);
			$time = 'Estimated time: ' . $this->twosecond_convert_minutes_to_readable_format($minutes) . ' remaining';
		}

		return [
			'html' => $table_html,
			'current_page' => $page,
			'total_pages' => $total_pages,
			'optimized_rows' => (int)$optimized_count,
			'total_rows' => (int)$total_rows,
			'percentage' => $percentage,
			'time' => $time
		];
	}

	/**
	 * Get pending optimization IDs
	 *
	 * Retrieves a list of pending optimization IDs from the database
	 * for batch processing.
	 *
	 * @param int $count Number of IDs to retrieve
	 * @return array Array of pending IDs
	 */
	function twosecond_get_pending_ids($count)
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		$query = $this->twosecond_database->twosecond_prepare("SELECT id FROM %i WHERE status IN (0, 1, 2, 3) ORDER BY CASE WHEN status IN (1, 2, 3) THEN 0 ELSE 1 END, updated_at ASC LIMIT %d", [$table_name, $count]);
		return $this->twosecond_database->twosecond_get_col($query);
	}


	/**
	 * Filter pending optimization IDs
	 *
	 * Filters a list of IDs to only include those that are
	 * not already optimized or in progress.
	 *
	 * @param array $ids Array of IDs to filter
	 * @return array Filtered array of pending IDs
	 */
	function twosecond_filter_pending_optimization_ids($ids)
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		$ids = array_filter(array_map('intval', $ids));
		if (empty($ids)) {
			return array();
		}
		$placeholders = implode(',', array_fill(0, count($ids), '%d'));
		$query = $this->twosecond_database->twosecond_prepare("SELECT id FROM %i WHERE status NOT IN (4, 5, 6) AND id IN ($placeholders)", array_merge([$table_name], $ids));
		return $this->twosecond_database->twosecond_get_col($query);
	}

	/**
	 * Insert site URLs for optimization
	 *
	 * Populates the site URLs table with URLs that need
	 * optimization, handling both demo and full license scenarios.
	 */
	public function twosecond_insert_site_urls()
	{
		$this->twosecond_truncate_site_urls_table();
		$batchSize = $this->addSettings['twosecond_get']['batch_size'] ?? 1000;
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		if (strpos($this->addSettings['mainLicenseKey'], 'tsdemo') !== false || empty($this->settings['is_activated'])) {
			$url = '/';
			$prepared = $this->twosecond_database->twosecond_prepare("INSERT INTO %i (url, status) VALUES (%s, %d)", [$table_name, $url, 0]);
			$this->twosecond_database->twosecond_query($prepared);
			$this->twosecond_response([
				'status' => true,
				'done' => true,
				'inserted' => 1,
				'message' => 'All URLs inserted'
			]);
		} else {
			$siteUrls = $this->twosecond_get_site_urls();
			if (empty($siteUrls) || !is_array($siteUrls)) {
				$siteUrls = $this->twosecond_get_sitemap_urls($this->addSettings['site_url']);
			}
			if (empty($siteUrls) || !is_array($siteUrls)) {
				return ['status' => false, 'message' => 'No URLs found'];
			}
			$insertedCount = 0;
			$chunks = array_chunk($siteUrls, $batchSize);
			foreach ($chunks as $chunk) {
				$cleanUrls = array_filter($chunk, function ($url) {
					$url = trim($url);
					return !empty($url) && strpos($url, '?') === false;
				});

				$cleanUrls = array_map(function ($url) {
					return urldecode(trim($url));
				}, $cleanUrls);

				if (empty($cleanUrls)) {
					continue;
				}

				$insertValues = [];
				$insertPlaceholders = [];

				foreach ($cleanUrls as $url) {
					if (empty($url) || !$this->twosecond_is_url_allowed_for_optimization($url)) {
						continue;
					}
					$parsedUrl = $this->twosecond_parse_url($url);
					if (empty($parsedUrl['host']) || empty($parsedUrl['scheme'])) continue;
					$url = $this->twosecond_change_url_absolute_to_relative($url, $parsedUrl);
					$url = empty($url) ? '/' : $url;
					$exists = $this->twosecond_database->twosecond_get_var(
						$this->twosecond_database->twosecond_prepare("SELECT COUNT(*) FROM %i WHERE url = %s", [$table_name, $url])
					);
					if ($exists) {
						continue;
					}
					$insertPlaceholders[] = "(%s, %d)";
					$insertValues[] = $url;
					$insertValues[] = 0;
					$insertedCount++;
				}

				if (!empty($insertValues)) {
					$insertQuery = "INSERT INTO %i (url, status) VALUES " . implode(',', $insertPlaceholders);
					$query = $this->twosecond_database->twosecond_prepare($insertQuery, array_merge([$table_name], $insertValues));
					$this->twosecond_database->twosecond_query($query);
				}
			}

			$this->twosecond_response([
				'status' => true,
				'done' => true,
				'inserted' => $insertedCount,
				'message' => 'All URLs inserted in chunks of ' . $batchSize
			]);
		}
	}

	/**
	 * Reset optimization status
	 *
	 * Resets all optimization statuses to pending (0) in the database
	 * to allow re-optimization of all URLs.
	 */
	public function twosecond_reset_optimization_status()
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		$this->twosecond_database->twosecond_query(
			$this->twosecond_database->twosecond_prepare(
				"UPDATE %i SET status = %d, updated_at = %s",
				[$table_name, 0, current_time('mysql')]
			)
		);
	}

	/**
	 * Update site URLs status
	 *
	 * Updates the status of multiple site URLs in the database
	 * for tracking optimization progress.
	 *
	 * @param array $ids    Array of IDs to update
	 * @param int   $status New status value
	 * @return int|false Number of affected rows or false on failure
	 */
	function twosecond_update_site_urls_status($ids, $status = 0)
	{
		if (empty($ids)) {
			return false;
		}

		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';

		$placeholders = implode(',', array_fill(0, count($ids), '%d'));
		$replaceValues = array_merge([$status, current_time('mysql')], $ids);
		$query = $this->twosecond_database->twosecond_prepare("UPDATE %i SET status = %d, updated_at = %s WHERE id IN ($placeholders)", array_merge([$table_name], $replaceValues));
		return $this->twosecond_database->twosecond_query($query);
	}


	/**
	 * Run background cron tasks
	 *
	 * Executes background optimization tasks including crawling
	 * and optimization of URLs in batches.
	 */
	function twosecond_run_background_cron($task, $ids, $request_count)
	{
		$this->twosecond_update_optimize_urls_status();
		if (empty($task)) return;
		if (empty($ids) || !is_array($ids)) return;
		if ($task === 'crawl') {
			$data = $this->twosecond_get_urls_by_ids($ids);
			$this->twosecond_update_site_urls_status($ids, 1);
			foreach ($data as $item) {
				$url = trim($item['url']);
				[$mobCacheFile, $desktopCacheFile] = $this->twosecond_cache_file_path($url);
				if (file_exists($mobCacheFile)) {
					$this->twosecond_delete_file($mobCacheFile);
				}
				if (file_exists($desktopCacheFile)) {
					$this->twosecond_delete_file($desktopCacheFile);
				}
				$this->twosecond_remote_request_non_blocking($url, ['twosecond_crawler' => wp_rand(1000000, 9999999)], 'GET');
				$this->twosecond_remote_request_non_blocking($url, ['twosecond_crawler' => wp_rand(1000000, 9999999)], 'GET', true);
			}
		} else if ($task == 'optimize') {
			$data = $this->twosecond_get_urls_by_ids($ids);
			$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
			$criticalJobsBatch = [];
			$webpTokensForBatch = [];
			foreach ($data as $item) {
				if ($item['status'] == 4) continue;
				$id = $item['id'];
				$url = rtrim($item['url'], '/');
				$currentStatus = $item['status'];
				$token = md5($url);
				$webpToken = $this->addSettings['webp_path'] . '/' . $token;
				$cssFilesCount = $this->twosecond_css_files_count($url);
				$criticalTokenMobile = $this->addSettings['critical_css_path'] . '/tokens/' . $token . '-mobile';
				$criticalTokenDesktop = $this->addSettings['critical_css_path'] . '/tokens/' . $token . '-desktop';
				if (!file_exists($webpToken)) {
					$mobile = false;
					$desktop = false;
					$status = 1;
					if ($cssFilesCount['mobile'] > 0) {
						$mobile = true;
					}
					if ($cssFilesCount['desktop'] > 0) {
						$desktop = true;
					}
					if ($desktop && !$mobile) {
						$status = 2;
					} else if (!$desktop && $mobile) {
						$status = 3;
					} else if ($desktop && $mobile) {
						$status = 4;
					}
					if ($request_count >= 12 && $status != 4) {
						$status = 5;
					}
					if ($currentStatus != $status) {
						$prepared = $this->twosecond_database->twosecond_prepare("UPDATE %i SET status = %d, updated_at = %s WHERE id = %d", [$table_name, $status, current_time('mysql'), $id]);
						$this->twosecond_database->twosecond_query($prepared);
						$currentStatus = $status;
					}
				}
				if (in_array($currentStatus, [1, 2, 3])) {
					[$mobCacheFile, $desktopCacheFile] = $this->twosecond_cache_file_path($url);
					if ($currentStatus == 2 || !$cssFilesCount['mobile']) { /* Mobile Critical Css not created yet */
						if (file_exists($mobCacheFile)) {
							$this->twosecond_delete_file($mobCacheFile);
						}
					} else if ($currentStatus == 3) {
						if (file_exists($desktopCacheFile)) {
							$this->twosecond_delete_file($desktopCacheFile);
						}
					} else {
						if (file_exists($mobCacheFile)) {
							$this->twosecond_delete_file($mobCacheFile);
						}
						if (file_exists($desktopCacheFile)) {
							$this->twosecond_delete_file($desktopCacheFile);
						}
					}
				}
				if (file_exists($webpToken) || file_exists($criticalTokenMobile) || file_exists($criticalTokenDesktop)) {
					if (file_exists($webpToken)) {
						$webpTokensForBatch[$token] = true;
					}
					$criticalJobsBatch = array_merge(
						$criticalJobsBatch,
						$this->twosecond_collect_critical_css_jobs_for_token($token, true)
					);
				} else {
					continue;
				}
				$updateStatus = !file_exists($webpToken) || !file_exists($criticalTokenMobile) || !file_exists($criticalTokenDesktop);
				if ($updateStatus) {
					[$mobCacheFile, $desktopCacheFile] = $this->twosecond_cache_file_path($url);
					if (file_exists($mobCacheFile)) {
						$this->twosecond_delete_file($mobCacheFile);
					}
					if (file_exists($desktopCacheFile)) {
						$this->twosecond_delete_file($desktopCacheFile);
					}
				}
			}
			if (!empty($webpTokensForBatch)) {
				$this->twosecond_flush_webp_image_queue_for_tokens(array_keys($webpTokensForBatch));
			}
			if (!empty($criticalJobsBatch)) {
				$this->twosecond_send_critical_css_jobs_chunked(
					$criticalJobsBatch,
					'',
					Core::TWOSECOND_CRITICAL_MULTI_MAX_BODY_BYTES
				);
			}
		}
	}

	/**
	 * Get URLs by IDs
	 *
	 * Retrieves URL data from the database based on an array of IDs
	 * and converts relative URLs to absolute URLs.
	 *
	 * @param array $ids Array of IDs to retrieve
	 * @return array Array of URL data
	 */
	function twosecond_get_urls_by_ids(array $ids)
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		if (empty($ids)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($ids), '%d'));
		$query = $this->twosecond_database->twosecond_prepare("
			SELECT id, url, status
			FROM %i 
			WHERE id IN ($placeholders)
		", array_merge([$table_name], $ids));

		$data = $this->twosecond_database->twosecond_get_results($query, ARRAY_A);
		return array_map(function ($item) {
			$item['url'] = $this->twosecond_change_url_relative_to_absolute($item['url']);
			return $item;
		}, $data);
	}

	/**
	 * AI optimization cron job
	 *
	 * Main cron job for AI-powered optimization that processes
	 * pending URLs in batches and handles preload caching.
	 *
	 * @return bool True on completion
	 */
	public function twosecond_optimize_with_ai_cron()
	{
		if (!empty($this->settings['ai-optimization'])) {
			$count = (int)(!empty($this->settings['page_batch']) ? $this->settings['page_batch'] : (!empty($this->settings['preload_per_min']) ? $this->settings['preload_per_min'] : 1));
			$ids = $this->twosecond_get_pending_ids($count);
			if (count($ids)) {
				$reqCount = 0;
				while ($reqCount < 13) {
					if (!count($ids)) break;
					++$reqCount;
					if ($reqCount == 1) {
						$this->twosecond_hit_task_request($ids, $reqCount, 'crawl');
						sleep(5);
					} else {
						$ids = $this->twosecond_filter_pending_optimization_ids($ids);
						if (count($ids)) {
							$this->twosecond_hit_task_request($ids, $reqCount, 'optimize');
							sleep(5);
						} else {
							break;
						}
					}
				}
			} else {
				if (empty($this->settings['preload_caching'])) {
					return false;
				}
				$pages_per_minute = !empty($this->settings['preload_per_min']) ? $this->settings['preload_per_min'] : 1;
				$data = $this->twosecond_get_preload_caching_urls($pages_per_minute);
				if (!empty($data)) {
					foreach ($data as $item) {
						$url = $item['url'];
						$this->twosecond_remote_get($url);
						$this->twosecond_remote_get($url, [], 0, 1);
					}
					$ids = array_column($data, 'id');
					$this->twosecond_update_site_urls_status($ids, 6);
				} else {
					$this->twosecond_reset_preload_caching_status();
				}
			}
		}
		return true;
	}

	/**
	 * Truncate site URLs table
	 *
	 * Clears all data from the site URLs table to allow
	 * fresh population with current site URLs.
	 */
	function twosecond_truncate_site_urls_table()
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		$this->twosecond_database->twosecond_query($this->twosecond_database->twosecond_prepare("SET FOREIGN_KEY_CHECKS = %d", [0]));
		$this->twosecond_database->twosecond_query($this->twosecond_database->twosecond_prepare("TRUNCATE TABLE %i", [$table_name]));
		$this->twosecond_database->twosecond_query($this->twosecond_database->twosecond_prepare("SET FOREIGN_KEY_CHECKS = %d", [1]));
	}

	/**
	 * Remove no-optimize page if exists
	 *
	 * Removes the current page from the optimization queue
	 * if it exists and should not be optimized.
	 *
	 * @return bool True if removed, false otherwise
	 */
	function twosecond_remove_no_optimize_page_if_exists()
	{
		$url = trim($this->addSettings['full_url_without_param']);

		if (empty($url)) {
			return false;
		}

		$parsedUrl = $this->twosecond_parse_url($url);
		if (empty($parsedUrl['host']) || empty($parsedUrl['scheme'])) return;
		$url = $this->twosecond_change_url_absolute_to_relative($url, $parsedUrl);

		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';

		$exists = $this->twosecond_database->twosecond_get_var(
			$this->twosecond_database->twosecond_prepare("SELECT id FROM %i WHERE url = %s LIMIT 1", [$table_name, $url])
		);

		if (!$exists) {
			return false;
		}
		$prepared = $this->twosecond_database->twosecond_prepare("DELETE FROM %i WHERE url = %s", [$table_name, $url]);
		$deleted = $this->twosecond_database->twosecond_query($prepared);
		return $deleted !== false;
	}

	/**
	 * Reset single page optimization
	 *
	 * Resets the optimization status of a single page to allow
	 * re-optimization and clears associated cache files.
	 *
	 * @return bool True if reset successful, false otherwise
	 */
	public function twosecond_reset_single_page_optimization()
	{
		$id = $this->addSettings['twosecond_post']['id'];
		if (empty($id) || !is_numeric($id)) {
			return false;
		}

		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';

		$row = $this->twosecond_database->twosecond_get_row(
			$this->twosecond_database->twosecond_prepare("SELECT id, url FROM %i WHERE id = %d LIMIT 1", [$table_name, $id]),
			ARRAY_A
		);

		if (empty($row)) {
			return false;
		}

		$url = $this->twosecond_change_url_relative_to_absolute($row['url']);

		[$mobCacheFile, $desktopCacheFile] = $this->twosecond_cache_file_path($url);

		if (file_exists($mobCacheFile)) {
			$this->twosecond_delete_file($mobCacheFile);
		}
		if (file_exists($desktopCacheFile)) {
			$this->twosecond_delete_file($desktopCacheFile);
		}
		$prepared = $this->twosecond_database->twosecond_prepare("UPDATE %i SET status = %d WHERE id = %d", [$table_name, 0, $id]);
		$updated = $this->twosecond_database->twosecond_query($prepared);
		$this->twosecond_response([
			'status' => true,
			'message' => 'Optimization reset successfully for url: ' . $url
		]);
	}

	/**
	 * Update optimize URLs status
	 *
	 * Updates the optimization status of URLs based on their
	 * current state and available optimization files.
	 */
	function twosecond_update_optimize_urls_status()
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';

		$statusList = [1, 2, 3];
		$placeholders = implode(',', array_fill(0, count($statusList), '%d'));

		$sql = $this->twosecond_database->twosecond_prepare(
			"SELECT id, url, status
			 FROM %i
			 WHERE status IN ($placeholders)
			 ORDER BY updated_at ASC",
			array_merge([$table_name], $statusList)
		);
		$data = $this->twosecond_database->twosecond_get_results($sql, ARRAY_A);

		foreach ($data as $item) {
			$id = $item['id'];
			$url = $this->twosecond_change_url_relative_to_absolute($item['url']);
			$currentStatus = $item['status'];
			$token = md5(rtrim($url, '/'));
			$webpToken = $this->addSettings['webp_path'] . '/' . $token;
			$cssFilesCount = $this->twosecond_css_files_count($url);
			if (!file_exists($webpToken)) {
				$mobile = false;
				$desktop = false;
				$status = $currentStatus;
				if ($cssFilesCount['mobile'] > 0) {
					$mobile = true;
				}
				if ($cssFilesCount['desktop'] > 0) {
					$desktop = true;
				}
				if ($desktop && !$mobile) {
					$status = 2;
				} else if (!$desktop && $mobile) {
					$status = 3;
				} else if ($desktop && $mobile) {
					$status = 4;
				}
				if ($currentStatus != $status && $status == 4) {
					$this->twosecond_database->twosecond_query($this->twosecond_database->twosecond_prepare("UPDATE %i SET status = %d, updated_at = %s WHERE id = %d", [$table_name, (int) $status, current_time('mysql'), (int) $id]));
					$currentStatus = $status;
				}
			}
		}
	}

	/**
	 * Get preload caching URLs
	 *
	 * Retrieves URLs that are ready for preload caching
	 * based on their optimization status.
	 *
	 * @param int $count Number of URLs to retrieve
	 * @return array Array of preload caching URLs
	 */
	function twosecond_get_preload_caching_urls($count)
	{
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		$query = "
			SELECT id, url
			FROM %i
			WHERE status = %d
			ORDER BY updated_at ASC
			LIMIT %d
		";
		$data = $this->twosecond_database->twosecond_get_results($this->twosecond_database->twosecond_prepare($query, [$table_name, 4, $count]), ARRAY_A);
		return array_map(function ($item) {
			$item['url'] = $this->twosecond_change_url_relative_to_absolute($item['url']);
			return $item;
		}, $data);
	}

	/**
	 * Reset preload caching status
	 *
	 * Resets the status of URLs that have completed preload
	 * caching to allow for future preload operations.
	 *
	 * @return int Number of updated rows
	 */
	function twosecond_reset_preload_caching_status()
	{
		$status = 4;
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_site_urls';
		$updated = $this->twosecond_database->twosecond_query(
			$this->twosecond_database->twosecond_prepare(
				"UPDATE %i SET status = %d, updated_at = %s WHERE status = %d",
				[$table_name, $status, current_time('mysql'), 6]
			)
		);
		return $updated;
	}

	/**
	 * Filter thumbnail images
	 *
	 * Filters out thumbnail images from a list of image URLs,
	 * keeping only original images for optimization.
	 *
	 * @param array $urls Array of image URLs to filter
	 * @return array Filtered array with only original images
	 */
	function twosecond_filter_thumbnail_images(array $urls)
	{
		$originals = [];
		global $_wp_additional_image_sizes;
		$sizes = [1536, 2048, 768, 595];

		foreach (get_intermediate_image_sizes() as $size) {
			if (in_array($size, ['thumbnail', 'medium', 'large', 'medium_large', '1536x1536', '2048x2048'])) {
				$width  = (int) get_option("{$size}_size_w");
			} elseif (isset($_wp_additional_image_sizes[$size])) {
				$width  = (int) $_wp_additional_image_sizes[$size]['width'];
			} else {
				continue;
			}
			if ($width) {
				$sizes[] = $width;
			}
		}

		foreach ($urls as $url) {
			if(strpos($url, 'wp-includes') !== false) continue;
			$filename = basename($url);
			if (preg_match('/-(\d+)x(\d+)\.(jpg|jpeg|png|gif|webp)$/i', $filename, $m)) {
				$dim = $m[1];
				if ((int)$dim <= 595) {
					continue;
				}
			} elseif (strpos($url, '-595xh.') !== false) {
				continue;
			}
			$originals[] = $url;
		}

		$originals = array_values(array_unique($originals));

		return $originals;
	}

	/**
	 * Get option
	 *
	 * Retrieves plugin options with multisite support,
	 * handling both single site and network-wide options.
	 *
	 * @param string $option   Option name.
	 * @param bool   $is_array Whether the option is an array.
	 * @return mixed Option value
	 */
	public function twosecond_get_option($option, $is_array = false)
	{
		if ($this->addSettings['is_multisite']) {
			global $twoSecond_network_option;
			if (empty($twoSecond_network_option)) {
				$twoSecond_network_option = get_site_option('twosecond_option', true);
			}
		}

		if ($this->addSettings['is_multisite'] && (empty($twoSecond_network_option['manage_site_separately']) || is_network_admin())) {
			return get_site_option($option);
		} else {
			return get_option($option);
		}
	}

	/**
	 * Update option
	 *
	 * Updates plugin options with multisite support,
	 * handling both single site and network-wide options.
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    Option value.
	 * @param string $autoload Autoload value.
	 * @return int 1 on success, 0 on failure
	 */
	public function twosecond_update_option($option, $value, $autoload = null)
	{
		if ($this->addSettings['is_multisite']) {
			global $twoSecond_network_option;
			if (empty($twoSecond_network_option)) {
				$twoSecond_network_option = get_site_option('twosecond_option', true);
			}
		}

		if ($this->addSettings['is_multisite'] && (empty($twoSecond_network_option['manage_site_separately']) || is_network_admin())) {
			return update_site_option($option, $value, $autoload) ? 1 : 0;
		} else {
			return update_option($option, $value, $autoload) ? 1 : 0;
		}
	}

	/**
	 * Strip tags
	 *
	 * @param string $url URL.
	 * @return string Stripped URL
	 */
	public function twosecond_strip_tags($url)
	{
		return wp_strip_all_tags($url);
	}

	/**
	 * Unslash
	 *
	 * @param string $text Text.
	 * @return string Unslashed text
	 */
	public function twosecond_unslash($text)
	{
		return wp_unslash($text);
	}
	/**
	 * Sanitize text field
	 *
	 * @param string $text Text.
	 * @return string Sanitized text
	 */
	public function twosecond_sanitize_text_field($text)
	{
		return sanitize_text_field($text);
	}

	/**
	 * Verify request value
	 *
	 * @param string $key Key.
	 * @param string $value Value.
	 * @return bool True if value is valid, false otherwise
	 */
	public function twosecond_verify_request_value($key, $value = '', $sanitize = true)
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- This IS the sanitization method. Nonce verification should be done by calling code before using this utility.
		if (empty($value) && !empty($_REQUEST[$key])) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- This IS the sanitization method using twosecond_unslash() and twosecond_sanitize_text_field() wrappers.
			return $this->twosecond_sanitize_text_field($this->twosecond_unslash($_REQUEST[$key]));
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- This IS the sanitization method using twosecond_unslash() and twosecond_sanitize_text_field() wrappers.
		if (!empty($value) && !empty($_REQUEST[$key]) && $this->twosecond_sanitize_text_field($this->twosecond_unslash($_REQUEST[$key])) == $value) {
			return true;
		}
		return false;
	}

	/**
	 * Verify request value array
	 *
	 * @param array $keys Keys.
	 * @param bool $sanitize Sanitize.
	 * @return array Keys.
	 */
	public function twosecond_verify_request_value_arr($keys, $sanitize = true)
	{
		foreach ($keys as $key => $value) {
			if (is_array($value)) {
				$keys[$key] = array_map(array($this, 'twosecond_sanitize_text_field'), array_map(array($this, 'twosecond_unslash'), $value));
			} else {
				$keys[$key] = $this->twosecond_sanitize_text_field($this->twosecond_unslash($value));
			}
		}
		return $keys;
	}
}
