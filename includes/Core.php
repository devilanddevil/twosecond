<?php
/**
 * Core Plugin Class
 *
 * This class serves as the central core of the TwoSecond plugin, providing
 * essential functionality for settings management, caching, optimization,
 * and various utility methods. It handles plugin initialization, hooks,
 * and core operations.
 *
 * @package TwoSecond
 * @author TwoSecond Team
 */


namespace TwoSecond;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Plugin Class
 *
 * Handles core plugin functionality including settings management,
 * caching operations, optimization features, and utility methods.
 * This class is the foundation for all other plugin components.
 */
class Core extends Hosting {

	/**
	 * Plugin settings
	 *
	 * Stores all plugin configuration options and settings
	 *
	 * @var array
	 */
	public $settings;

	/**  
	 * Additional settings
	 *
	 * Stores computed and derived settings, CDN configurations,
	 * cache paths, and other runtime data
	 *
	 * @var array
	 */
	public $addSettings;
	
	/**
	 * HTML content buffer
	 *
	 * Stores the processed HTML content during optimization
	 *
	 * @var string
	 */
	public $html = "";
    
    /**
     * HTTP status code
     *
     * @var string
     */
    public $statusCode = "";
    
    /**
     * Supported CDN content types
     *
     * @var array
     */
    public $cdnTypes = ['image', 'css', 'js', 'font', 'audio', 'video'];
    
    /**
     * Keys for .htaccess modifications
     *
     * @var array
     */
    public $htaccessModifyKeys = [];
    
    /**
     * Critical CSS data
     *
     * @var array
     */
    public $criticalData = [];
    
    /**
     * WebP image URLs for processing
     *
     * @var array
     */
    public $webpEnqueImageUrls = [];
    
    /**
     * Unique page token for caching
     *
     * @var string
     */
    public $pageToken = '';
    
    /**
     * Inline style background images
     *
     * @var array
     */
    public $inlineStyleBackgroundImages = [];
    
    /**
     * BS data background load CSS
     *
     * @var string
     */
    public $twoSecondDataBgLoadCss = '';

    /**
     * BS database
     *
     * @var \TwoSecond\Database
     */
    public $twosecond_database;

    /**
     * actionHeading
     *
     * @var array
     */
    public $actionHeading;
	/**
	 * Set additional settings
	 *
	 * Initializes computed settings, CDN configurations, cache paths,
	 * and other runtime data based on the main plugin settings.
	 * This method sets up all the derived configuration values.
	 */
	protected function twosecond_set_additional_settings() {
		// This method sanitizes request data for later use throughout the plugin
		// Nonce verification is NOT performed here because:
		// 1. This is a sanitization/initialization method called early in request lifecycle
		// 2. Nonce verification is performed by calling code when these values are used for privileged actions
		// 3. This method is called from multiple contexts (admin, frontend, AJAX) where nonce requirements differ
		// All privileged actions that use these sanitized values MUST verify nonces before use
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- This IS the sanitization method. Nonce verification is performed by calling code when these values are used for privileged actions.
		$this->addSettings['twosecond_get'] = $this->twosecond_verify_request_value_arr($_GET); 
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This IS the sanitization method. Nonce verification is performed by calling code when these values are used for privileged actions.
        $this->addSettings['twosecond_post'] = $this->twosecond_verify_request_value_arr($_POST); 
        $this->addSettings['twosecond_server'] = $this->twosecond_verify_request_value_arr($_SERVER);
        
        // Determine the secure protocol (https/http) for the current request
        $this->addSettings['secure'] = (isset($this->addSettings['site_url']) && strpos($this->addSettings['site_url'], 'https') !== false) ? 'https://' : (!empty( $this->addSettings['twosecond_server']['REQUEST_SCHEME']) ? $this->addSettings['twosecond_server']['REQUEST_SCHEME'] . '://' : 'http://');
        $home_url_arr = $this->twosecond_parse_url($this->addSettings['home_url']);
        
        // Process CDN settings and set up type-specific CDN URLs and exclude paths
        if (!empty($this->settings['cdn']) && is_array($this->settings['cdn'])) {
            foreach ($this->settings['cdn'] as $key => &$value) {
                $value = (array)$value;
                if (!empty($value['type'])) {
                    foreach (explode(',', $value['type']) as $type) {
                        $this->addSettings["{$type}_cdn_url"] = rtrim($value['url'], '/');
                        $this->addSettings["{$type}_exclude_cdn_path"] = $value['exclude_path'];
                    }
                }
            }
        }

        // Set main license key or generate demo key
        $this->addSettings['mainLicenseKey'] = !empty($this->settings['license_key']) ? $this->settings['license_key'] : 'tsdemo-' . $home_url_arr['host'];
        
        // Process each CDN type and set up default values and URL arrays
        foreach ($this->cdnTypes as $type) {
            $this->addSettings[$type.'_cdn_url'] = !empty($this->addSettings[$type.'_cdn_url']) ? $this->addSettings[$type.'_cdn_url'] : $this->addSettings['site_url'];
            $this->addSettings[$type.'_exclude_cdn_path'] = !empty($this->addSettings[$type.'_exclude_cdn_path']) ? explode(',', str_replace(' ', '', $this->addSettings[$type.'_exclude_cdn_path'])) : [];
            $this->addSettings[$type.'_url_array'] = $this->twosecond_parse_url($this->addSettings[$type.'_cdn_url']);
            if(!empty($this->addSettings[$type.'_url_array']['path'])){
                $this->addSettings[$type.'_url_array']['host'] .= rtrim($this->addSettings[$type.'_url_array']['path'], '/'); 
            }
        }

        $this->addSettings['api_url'] = 'http://45.195.159.20:3000';
        $this->addSettings['http_user_agent'] = !empty($this->addSettings['twosecond_server']['HTTP_USER_AGENT']) ? $this->addSettings['twosecond_server']['HTTP_USER_AGENT'] : '';

        $this->addSettings['is_mobile'] = $this->twosecond_is_mobile_device();
        $this->addSettings['domain_url'] = $this->addSettings['secure'] . $this->addSettings['twosecond_server']['HTTP_HOST'];
        $this->addSettings['full_url'] = $this->addSettings['domain_url'] . $this->addSettings['twosecond_server']['REQUEST_URI'];

        $full_url_array = explode('?', $this->addSettings['full_url']);
        $this->addSettings['full_url_without_param'] = $full_url_array[0];
        $this->pageToken = md5(rtrim($this->addSettings['full_url_without_param'], '/'));
        $this->addSettings['cache_path'] = (!empty($this->settings['cache_path']) ? rtrim($this->settings['cache_path'], '/') : $this->addSettings['content_path'] . '/cache');
        $this->addSettings['root_cache_path'] = $this->addSettings['cache_path'] . '/ts-cache';
        $this->addSettings['critical_css_path'] = $this->addSettings['cache_path'] . '/critical-css';
        $site_path = rtrim(parse_url($this->addSettings['site_url'], PHP_URL_PATH) ?? '', '/');
        $this->addSettings['cache_url'] = $site_path . str_replace($this->addSettings['document_root'], '', $this->addSettings['root_cache_path']);
        $this->addSettings['cache_url'] = str_replace('//', '/', $this->addSettings['cache_url']);
        $this->addSettings['upload_path'] = $this->addSettings['content_path'];
        list($this->addSettings['upload_base_url'], $this->addSettings['upload_base_dir']) = $this->twosecond_get_uploads_basepath();
        $this->addSettings['webp_path'] = $this->addSettings['upload_path'] . '/uploads/ts-webp';
        $this->addSettings['twosecond_rand_key'] = $this->twosecond_get_option('twosecond_rand_key', 0);
        $this->addSettings['excluded_img'] = !empty($this->settings['exclude_lazy_load']) && is_array($this->settings['exclude_lazy_load']) ? $this->settings['exclude_lazy_load'] : array();
        $this->addSettings['excluded_img'] = array_merge($this->addSettings['excluded_img'], array('about:blank'));

        if ($this->addSettings['is_mobile']) {
            $this->addSettings['css_ext'] = 'mob.css';
            $this->addSettings['js_ext'] = 'mob.js';
            $this->addSettings['preload_css'] = !empty($this->settings['preload_css_mobile']) && is_array($this->settings['preload_css_mobile']) ? $this->settings['preload_css_mobile'] : array();
        } else {
            $this->addSettings['css_ext'] = '.css';
            $this->addSettings['js_ext'] = '.js';
            $this->addSettings['preload_css'] = !empty($this->settings['preload_css']) && is_array($this->settings['preload_css']) ? $this->settings['preload_css'] : array();
        }

        $this->addSettings['preload_css_url'] = [];
        // Only get needed headers to avoid exposing sensitive information
        $this->addSettings['main_css_url'] = [];
        $this->addSettings['lazy_load_js'] = [];
        $this->addSettings['webp_enable'] = [];
        $this->addSettings['webp_enable_instance'] = array($this->addSettings['site_url'] . (str_replace($this->addSettings['document_root'], "", $this->addSettings['upload_path'])));
        $this->addSettings['webp_enable_instance_replace'] = array($this->addSettings['site_url'] . '$ts$' . (str_replace($this->addSettings['document_root'], "", $this->addSettings['webp_path'])));
        if (!empty($this->addSettings['is_multisite_sub_domain'])) {
            $this->addSettings['webp_enable_instance'][] = $this->addSettings['network_site_url'] . (str_replace($this->addSettings['document_root'], "", $this->addSettings['upload_path']));
            $this->addSettings['webp_enable_instance_replace'][] = $this->addSettings['network_site_url'] . '$ts$' . (str_replace($this->addSettings['document_root'], "", $this->addSettings['webp_path']));
        }
        if (!empty($this->addSettings['site_url_diff'])) {
            $this->addSettings['webp_enable_in
            stance'][] = $this->addSettings['site_url_diff'] . (str_replace($this->addSettings['document_root'], "", $this->addSettings['upload_path']));
            $this->addSettings['webp_enable_instance_replace'][] = $this->addSettings['site_url_diff'] . '$ts$' . (str_replace($this->addSettings['document_root'], "", $this->addSettings['webp_path']));
        }
        if (!empty($this->settings['webp_jpg'])) {
            $this->addSettings['webp_enable'] = array_merge($this->addSettings['webp_enable'], array('.jpg', '.jpeg'));
        }
        if (!empty($this->settings['webp_png'])) {
            $this->addSettings['webp_enable'] = array_merge($this->addSettings['webp_enable'], array('.png'));
        }
        $this->addSettings['htaccess'] = 0;
        if (file_exists($this->addSettings['document_root'] . "/.htaccess")) {
            $htaccess = $this->twosecond_get_contents($this->addSettings['document_root'] . "/.htaccess");
            if (strpos($htaccess, 'TSWEBP') !== false) {
                $this->addSettings['htaccess'] = 1;
            }
        }
        $this->addSettings['critical_css'] = '';
        $this->addSettings['starttime'] = $this->twosecond_microtime_float();
        $this->addSettings['twosecond_user_logged_in'] = $this->twosecond_user_logged_in();
        $this->addSettings['fonts_api_links'] = [];
        $this->addSettings['fonts_api_links_css2'] = [];

        $this->addSettings['preload_resources'] = [];
        $this->addSettings['preload_resources']['all'] = [];
        $this->addSettings['js_is_excluded'] = 0;

        $this->addSettings['wptouch'] = false;
        $this->addSettings['blank_image_url'] = $this->twosecond_create_blank_data_image(16, 9);
        $this->addSettings['server_type'] = isset($this->addSettings['twosecond_server']['SERVER_SOFTWARE']) && stripos($this->addSettings['twosecond_server']['SERVER_SOFTWARE'], 'Apache') !== false ? 'apache' : '';
        $this->addSettings['disable_htaccess_webp'] = 1;
        $this->addSettings['advanced_cache_exist'] = 0;
        $this->addSettings['twosecond_responsive_img_urls'] = [];
        $this->addSettings['max_upload_size'] = 10;
        $this->addSettings['abspath'] = defined('ABSPATH') ? rtrim(ABSPATH,'/') : $this->addSettings['document_root'];

        $this->twosecond_copy_assets();
		$this->twosecond_create_cache_directories();
	}

	/**
	 * Create cache directories
	 *
	 * Creates necessary cache directories if they don't exist.
	 * This includes the main cache directory, critical CSS directory,
	 * and WebP images directory.
	 */
	protected function twosecond_create_cache_directories() {
		// Define required cache directories
		$directories = array(
			$this->addSettings['root_cache_path'],      // Main cache directory
			$this->addSettings['critical_css_path'],   // Critical CSS cache directory
			$this->addSettings['webp_path'],         // WebP images cache directory
		);
		
		// Create each directory if it doesn't exist
		foreach ( $directories as $directory ) {
			if ( ! is_dir( $directory ) ) {
				$this->twosecond_create_folder( $directory );
			}
		}
	}

	/**

	 * Check if user is logged in
	 *
	 * Wrapper for WordPress is_user_logged_in() function with safety check.
	 *
	 * @return bool True if user is logged in, false otherwise
	 */
	public function is_user_logged_in() {
		if ( function_exists( 'is_user_logged_in' ) ) {
			return is_user_logged_in();
		}
		return false;
	}

	/**
	 * Get settings
	 *
	 * Retrieves the main plugin settings array.
	 *
	 * @return array Plugin settings configuration
	 */
	public function twosecond_get_settings() {
		return $this->settings;
	}

	/**
	 * Get cache path
	 *
	 * Retrieves the file system path for different cache types.
	 * Supports root cache, critical CSS, and WebP image caches.
	 *
	 * @param string $type Cache type: 'root', 'critical', or 'webp'
	 * @return string File system path to the cache directory
	 */
	public function get_cache_path( $type = 'root' ) {
		switch ( $type ) {
			case 'critical':
				return $this->addSettings['critical_css_path'];
			case 'webp':
				return $this->addSettings['webp_path'];
			default:
				return $this->addSettings['root_cache_path'];
		}
	}

	/**
	 * Check BS folder permission errors
	 *
	 * Verifies that all required TwoSecond cache directories exist
	 * and are writable. Attempts to create directories if they don't exist.
	 * Returns an array of directories with permission issues.
	 *
	 * @return array Array of directory paths with permission errors
	 */
	function twosecond_check_folder_permission_errors()
    {
        // Define required cache directories
        $twoSecondFolders = [$this->addSettings['cache_path'], $this->addSettings['critical_css_path'], $this->addSettings['webp_path']];
        $twoSecondFolderErrors = [];
        
        // Check each directory for existence and writability
        foreach ($twoSecondFolders as $value) {
            if(!$this->twosecond_create_folder($value)){
                $twoSecondFolderErrors[] = $value;
            }
        }
        return $twoSecondFolderErrors;
    }

	/**
	 * Get errors
	 *
	 * Retrieves all stored error messages from the plugin options.
	 * Returns an empty array if no errors exist.
	 *
	 * @return array Array of error objects with 'type' and 'message' keys
	 */
	function twosecond_get_errors(){
        $errors = $this->twosecond_get_option('twosecond_errors', 1);
        return is_array($errors) ? $errors : [];
    }

	/**
	 * Add error
	 *
	 * Adds a new error message to the stored errors array.
	 * Errors are stored with a type (e.g., 'danger', 'warning') and message.
	 *
	 * @param string $type    Error type (e.g., 'danger', 'warning', 'info')
	 * @param string $message Error message text
	 */
	function twosecond_add_error($type, $message){
        $errors = $this->twosecond_get_option('twosecond_errors', 1);
        $errors = is_array($errors) ? $errors : [];
        $errors[] = ['type' => $type, 'message' => $message];
        $this->twosecond_update_option('twosecond_errors', $errors);
    }

	/**
	 * Flush errors
	 *
	 * Clears all stored error messages by setting the errors option to an empty array.
	 * This is typically called after errors have been displayed to the user.
	 */
    function twosecond_flush_errors(){
        $this->twosecond_update_option('twosecond_errors', []);
    }

	/**
	 * Replace backslashes
	 *
	 * Recursively processes arrays and strings to replace double backslashes
	 * with single backslashes. This is useful for cleaning up file paths
	 * and other string data.
	 *
	 * @param mixed $value Value to process (can be array or string)
	 * @return mixed Processed value with corrected backslashes
	 */
	public function twosecond_replace_backslashes($value) {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->twosecond_replace_backslashes($val);
            }
            return $value;
        }
        return str_replace('\\\\', '\\', $value);
    }

	/**
	 * Set action heading
	 *
	 * Initializes the actionHeading array with human-readable labels
	 * for all plugin settings. These labels are used in the admin interface
	 * to display user-friendly descriptions of each setting.
	 */
	function twosecond_set_action_heading(){
        $this->actionHeading = [
			// HTML Caching Settings
			"html_caching" => "Enable HTML Caching",
			"enable_loggedin_user_caching" => "Enable caching for logged in user",
			"by_serve_cache_file" => "Serve html cache file by",
			"enable_caching_get_para" => "Enable caching page with GET parameters",
			"html_caching_expiry_time" => "Cache Expiry Time",
			"clear_cache_page_post_updated" => "Clear Cache when Page or Post is Updated",
			"preload_caching" => "Preload Caching",
			"preload_per_min" => "Preload page caching per minute",
			
			// Server Optimization Settings
			"lbc" => "Enable leverage browsing cache",
			"gzip" => "Enable Gzip compression",
			"remquery" => "Remove query parameters",
			"cache_path" => "Cache Path",
			"license_key" => "License Key",
			
			// General Optimization Settings
			"optimization_on" => "Turn ON optimization",
			"optimize_query_parameters" => "Optimize Pages with Query Parameters",
			"optimize_user_logged_in" => "Optimize pages when User Logged In",
			"enable_inp" => "Fix INP Issues",
			
			// CDN Settings
			"cdn" => "CDN url",
			"exclude_cdn" => "Exclude file extensions from cdn",
			"image_exclude_cdn_path" => "Exclude Image path from cdn",
			"css_exclude_cdn_path" => "Exclude Css path from cdn",
			"js_exclude_cdn_path" => "Exclude Js path from cdn",
			"font_exclude_cdn_path" => "Exclude Font path from cdn",
			"audio_exclude_cdn_path" => "Exclude Audio path from cdn",
			"video_exclude_cdn_path" => "Exclude Video path from cdn",
			
			// Image Optimization Settings
			"keep_org_img" => "Keep Original Images",
			"webp_jpg" => "Convert to Webp",
			"webp_png" => "Convert to PNG",
			
			// Lazy Loading Settings
			"lazy_load" => "Enable Lazy Load Image",
			"lazy_load_iframe" => "Enable Lazy Load Iframe",
			"lazy_load_video" => "Enable Lazy Load Video",
			"lazy_load_audio" => "Enable Lazy Load Audio",
			"inlineToUrlSVG" => "Load SVG Inline Tag as URL",
			"resp_bg_img" => "Responsive Images",
			
			// CSS Optimization Settings
			"css" => "Enable CSS Optimization",
			"localize_google_fonts" => "Localize Google fonts",
			"load_critical_css" => "Load Critical CSS",
			"load_critical_css_style_tag" => "Load Critical CSS in Style Tag",
			"load_style_tag_in_head" => "Load Style Tag in Head to Avoid CLS",
			
			// JavaScript Optimization Settings
			"js" => "Enable Javascript Optimization",
			"load_combined_js" => "Lazyload Javascript",
			"load_script_tag_in_url" => "Load Inline Javascript as URL",
			"preload_resources" => "Preload Resources",
			
			// Exclusion Settings
			"exclude_lazy_load" => "Exclude Resources from Lazy Loading",
			"exclude_css" => "Exclude Link Tag CSS from Optimization",
			"force_lazyload_css" => "Force Lazy Load Link Tag CSS",
			"force_lazy_load_inner_javascript" => "Force Lazy Load Javascript",
			"exclude_both_javascript" => "Exclude Javascript from Lazyload",
			"exclude_url_exclusions_html_cache" => "Exclude pages from HTML caching",
			"exclude_pages_from_optimization" => "Exclude Pages From Optimization",
			"exclude_page_from_load_combined_css" => "Exclude Pages from CSS Optimization",
			"exclude_page_from_load_combined_js" => "Exclude Pages from Javascript Optimization",
			
			// Additional Settings
			"webvitals_logs" => "Enable Core Web Vitals Logs",
			"import_text" => "Import Settings",
			"page_batch" => "Page Optimize Per Batch",
		];
	}
	/**
	 * Get action heading
	 *
	 * Retrieves the human-readable label for a specific setting key.
	 * Initializes the actionHeading array if it hasn't been set yet.
	 *
	 * @param string $key Setting key to get the heading for
	 * @return string Human-readable label for the setting, or empty string if not found
	 */
	function twosecond_get_action_heading($key)
    {
		if(empty($this->actionHeading)){
			$this->twosecond_set_action_heading();
		}
		return $this->actionHeading[$key] ?? "";
    }

	/**
	 * Remove cache files hourly event callback
	 *
	 * Handles the hourly cleanup of cache files. This method is called
	 * by WordPress cron to maintain cache directory sizes and performance.
	 * Creates a new random key after cleanup for security.
	 *
	 * @param string $path Specific cache path to clean (defaults to all)
	 * @return array Cache size information after cleanup
	 */
	function twosecond_remove_cache_files_hourly_event_callback($path = '')
    {
        if ($path == "html") {
            $cachePath =  $this->addSettings['root_cache_path'] . '/html';
        } else {
            $cachePath =  $this->twosecond_get_cache_path($path);
            $this->twosecond_create_random_key();
        }
        $this->twosecond_cache_rmdir($cachePath);
        return $this->twosecond_cache_size_callback();
    }

	/**
	 * Remove cache directory
	 *
	 * Recursively removes a cache directory and all its contents.
	 * Protects the critical-css directory from deletion as it contains
	 * important optimization data.
	 *
	 * @param string $dir Directory path to remove
	 */
	public function twosecond_cache_rmdir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $objects = @scandir($dir);
        if ($objects === false) {
            return;
        }
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path) && $object !== 'critical-css') {
                $this->twosecond_cache_rmdir($path);
            } else {
                $this->twosecond_delete_file($path);
            }
        }
        if(method_exists($this,'twosecond_remove_single_directory')){
            $this->twosecond_remove_single_directory($dir);
        }
    }

	/**
	 * Get cache file size
	 *
	 * Calculates the total size of all cache files and stores the result
	 * in WordPress options. This information is used for monitoring cache
	 * usage and cleanup scheduling.
	 *
	 * @return int Cache size in MB
	 */
	function twosecond_cache_size_callback()
    {
        $filesize = $this->twosecond_get_cache_file_size();
        $this->twosecond_update_option('twosecond_filesize', $filesize, 'no', 0);
        return $filesize;
    }


	/**
	 * Get cache file size
	 *
	 * Recursively calculates the total size of all cache files and directories.
	 * Returns the size in megabytes for easy reading and comparison.
	 *
	 * @return int Total cache size in MB
	 */
	function twosecond_get_cache_file_size()
    {
        $dir = $this->twosecond_get_cache_path();
        $size = 0;
        
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            if (file_exists($each)) {
                $size += filesize($each);
            } else {
                $size += $this->twosecond_foldersize($each);
            }
        }
        return ($size / 1024) / 1024;
    }

	/**
	 * Remove critical css cache files
	 *
	 * Removes all critical CSS cache files and clears server-level caching.
	 * This is typically called when CSS optimization settings are changed
	 * to ensure fresh critical CSS generation.
	 *
	 * @return bool Always returns true on completion
	 */
	function twosecond_remove_critical_css_cache_files()
    {
        $this->twosecond_rmdir($this->addSettings['critical_css_path']);
        $this->twosecond_delete_server_cache();
        return true;
    }

	/**
	 * Set all links
	 *
	 * Extracts and categorizes various HTML elements from the page content
	 * for optimization processing. This method parses the HTML to find scripts,
	 * styles, images, and other resources that can be optimized.
	 *
	 * @param string $data      HTML content to parse
	 * @param array $resources  Array of resource types to extract
	 * @return array Array containing extracted resources by type
	 */
    function twosecond_set_all_links($data, $resources = array())
    {
        $resource_arr = array();
        
        $comment_tag = $this->twosecond_get_tags_data($data, '<!--', '-->');
        $new_comment_tag = array();
        
        foreach ($comment_tag as $key => $comment) {
            if (strpos($comment, '<script>') !== false || strpos($comment, '</script>') !== false || strpos($comment, '<link') !== false) {
                $new_comment_tag[] = $comment;
            }
        }
        
        $noscript_tag = $this->twosecond_get_tags_data($data, '<noscript', '</noscript>');
        $data = str_replace(array_merge($new_comment_tag, $noscript_tag), '', $data);
        $scripts = $this->twosecond_get_tags_data($data, '<script', '</script>');
        $data = str_replace($scripts, '', $data);

        // Remove all comment tags from data
        $data = str_replace($comment_tag, '', $data);
        
        // Store scripts if JavaScript optimization is enabled
        if (!empty($this->settings['js']) && in_array('script', $resources)) {
            $resource_arr['script'] = $scripts;
        } else {
            $resource_arr['script'] = array();
        }
        
        // Extract image elements for lazy loading and optimization
        $resource_arr['picture'] = array();
        if (in_array('img', $resources)) {
            $resource_arr['img'] = $this->twosecond_get_tags_data($data, '<img', '>');
            if(strpos($data, '</picture>') !== false){
                $resource_arr['picture'] = $this->twosecond_get_tags_data($data, '<picture', '</picture>');
            }
        } else {
            $resource_arr['img'] = array();
        }
        
        // Extract SVG elements for inline processing
        if (in_array('svg', $resources)) {
            $resource_arr['svg'] = $this->twosecond_get_tags_data($data, '<svg', '</svg>');
        } else {
            $resource_arr['svg'] = array();
        }
        
        // Extract CSS-related elements if CSS optimization is enabled
        if (!empty($this->settings['css']) && in_array('link', $resources)) {
            $resource_arr['link'] = $this->twosecond_get_tags_data($data, '<link', '>');
            $resource_arr['style'] = $this->twosecond_get_tags_data($data, '<style', '</style>');
        } else {
            $resource_arr['link'] = array();
            $resource_arr['style'] = array();
        }

        // Extract iframe elements for lazy loading
        if (in_array('iframe', $resources)) {
            $resource_arr['iframe'] = $this->twosecond_get_tags_data($data, '<iframe', '</iframe>');
        } else {
            $resource_arr['iframe'] = array();
        }
        
        // Extract video elements for lazy loading
        if (in_array('video', $resources)) {
            $resource_arr['video'] = $this->twosecond_get_tags_data($data, '<video', '</video>');
        } else {
            $resource_arr['video'] = array();
        }
        
        // Extract audio elements for lazy loading
        if (in_array('audio', $resources)) {
            $resource_arr['audio'] = $this->twosecond_get_tags_data($data, '<audio', '</audio>');
        } else {
            $resource_arr['audio'] = array();
        }
        
        // Extract URL references from CSS content
        if (in_array('url', $resources)) {
            $resource_arr['url'] = $this->twosecond_get_tags_data($data, 'url(', ')');
        } else {
            $resource_arr['url'] = array();
        }
        
        return $resource_arr;
    }
	
	/**
	 * Get folder size
	 *
	 * Recursively calculates the total size of a directory and all its contents.
	 * This method is used to determine cache directory sizes for monitoring
	 * and cleanup purposes.
	 *
	 * @param string $path Directory path to calculate size for
	 * @return int Total size in bytes
	 */
    function twosecond_foldersize($path)
    {
        $total_size = 0;
        if (is_dir($path)) {
            $files = scandir($path);
            $cleanPath = rtrim($path, '/') . '/';
            foreach ($files as $t) {
                if ($t <> "." && $t <> "..") {
                    $currentFile = $cleanPath . $t;
                    
                    if (is_dir($currentFile)) {
                        $size = $this->twosecond_foldersize($currentFile);
                        $total_size += $size;
                    } else {
                        $size = filesize($currentFile);
                        $total_size += $size;
                    }
                }
            }
        }
        return $total_size;
    }

	/**
	 * Create random key
	 *
	 * Generates a new random key and stores it in WordPress options.
	 * This key is used for security purposes and cache invalidation.
	 * The key is updated periodically to maintain security.
	 */
    function twosecond_create_random_key()
    {
        $this->twosecond_update_option('twosecond_rand_key', $this->twosecond_random_number(), 'no', 0);
        $this->addSettings['twosecond_rand_key'] = $this->twosecond_random_number();
    }

	/**
	 * Preload resources
	 *
	 * Generates HTML preload tags for critical resources to improve page load performance.
	 * This method creates preload directives for images, fonts, CSS, JavaScript, and other
	 * resources that are essential for above-the-fold content.
	 *
	 * @return string HTML string containing preload link tags
	 */
    function twosecond_preload_resources()
    {
        $preload_html = '';
        
        $preload_resources = !empty($this->settings['preload_resources']) && is_array($this->settings['preload_resources']) ? $this->settings['preload_resources'] : array();
        
        if (!empty($this->addSettings['preload_resources']['all']) && is_array($this->addSettings['preload_resources']['all']) && count($this->addSettings['preload_resources']['all']) > 0) {
            $preload_resources = array_merge($preload_resources, $this->addSettings['preload_resources']['all']);
        }
        
        if (!empty($this->addSettings['preload_resources']['critical_css'])) {
            $preload_resources = $this->addSettings['preload_resources']['critical_css'] != 1 ? array_merge($preload_resources, array($this->addSettings['preload_resources']['critical_css'])) : $preload_resources;
            $this->addSettings['preload_resources']['css'] = [];
        } elseif (!empty($this->addSettings['preload_resources']['css'])) {
            $preload_resources = array_merge($preload_resources, $this->addSettings['preload_resources']['css']);
        }
        if (!empty($this->addSettings['preload_resources']['preconnect'])) {
            $preloadArr = $this->addSettings['preload_resources']['preconnect'];
            foreach ($preloadArr as $link) {
                $preload_html .= '<link rel="preconnect" href="' . trim($link) . '">';
            }
        }
        
        if(!empty($this->addSettings['preload_resources']['font'])){
            $preload_resources = array_merge($preload_resources, $this->addSettings['preload_resources']['font']);
        }
        
        $preload_resources = array_unique($preload_resources);
        
        if (!empty($preload_resources)) {
            $preloaded_fonts_count = 0;
            foreach ($preload_resources as $link) {
                $link_arr = explode('?', $link);
                $extension = explode(".", $link_arr[0]);
                $extension = end($extension);
                
                if (empty($extension)) {
                    continue;
                }
                
                // Preload font files
                if ($preloaded_fonts_count <= 3 && in_array(strtolower($extension), array('otf', 'ttf', 'woff', 'woff2', 'gtf', 'mmm', 'pea', 'tpf', 'ttc', 'wtf'))) {
                    $preloaded_fonts_count++;
                    $preload_html .= '<link rel="preload" href="' . trim($link) . '" as="font" type="font/' . $extension . '" crossorigin fetchpriority="high">';
                }

                // Preload video files
                if (in_array($extension, array('mp4', 'webm'))) {
                    $preload_html .= '<link rel="preload" href="' . trim($link) . '" as="video" type="video/' . $extension . '" fetchpriority="high">';
                }
                
                // Preload CSS files
                if ($extension == 'css') {
                    $crossorigin = $this->twosecond_is_external($link, [], 'css') ? 'crossorigin' : '';
                    $preload_html .= '<link rel="preload" href="' . trim($link) . '" as="style" ' . $crossorigin . ' fetchpriority="high">';
                }
                
                // Preload JavaScript files
                if ($extension == 'js') {
                    $crossorigin = $this->twosecond_is_external($link, [], 'js') ? 'crossorigin' : '';
                    $preload_html .= '<link rel="preload" href="' . trim($link) . '" as="script" ' . $crossorigin . ' fetchpriority="high">';
                }
            }
        }
        
        return $preload_html;
    }

	/**
	 * Remove dot path segments
	 *
	 * Normalizes file paths by removing dot segments (./ and ../) according to
	 * RFC 3986. This method handles path normalization for URLs and file paths,
	 * ensuring consistent path representation.
	 *
	 * @param string $path Path to normalize
	 * @return string Normalized path without dot segments
	 */
    function twosecond_remove_dot_path_segments($path)
    {
        // Return early if no dot segments exist
        if (strpos($path, '.') === false) {
            return $path;
        }

        $inputBuffer = $path;
        $outputStack = [];

        // Process the path buffer until empty
        while ($inputBuffer != '') {
            // Remove current directory references (./)
            if (strpos($inputBuffer, "./") === 0) {
                $inputBuffer = substr($inputBuffer, 2);
                continue;
            }
            
            // Remove parent directory references (../)
            if (strpos($inputBuffer, "../") === 0) {
                $inputBuffer = substr($inputBuffer, 3);
                continue;
            }

            // Handle root current directory (/.)
            if ($inputBuffer === "/.") {
                $outputStack[] = '/';
                break;
            }
            
            // Handle root current directory with trailing slash (/.//)
            if (substr($inputBuffer, 0, 3) === "/./") {
                $inputBuffer = substr($inputBuffer, 2);
                continue;
            }

            // Handle root parent directory (/..)
            if ($inputBuffer === "/..") {
                array_pop($outputStack);
                $outputStack[] = '/';
                break;
            }
            
            // Handle root parent directory with trailing slash (/../)
            if (substr($inputBuffer, 0, 4) === "/../") {
                array_pop($outputStack);
                $inputBuffer = substr($inputBuffer, 3);
                continue;
            }

            // Stop processing for standalone dot segments
            if ($inputBuffer === '.' || $inputBuffer === '..') {
                break;
            }

            // Extract path segments and continue processing
            if (($slashPos = stripos($inputBuffer, '/', 1)) === false) {
                $outputStack[] = $inputBuffer;
                break;
            } else {
                $outputStack[] = substr($inputBuffer, 0, $slashPos);
                $inputBuffer = substr($inputBuffer, $slashPos);
            }
        }

        return implode($outputStack);
    }

	/**
	 * Check ignore critical css
	 *
	 * Determines whether critical CSS should be twosecond_ignored for the current page.
	 * This method checks various conditions including user login status, 404 pages,
	 * and custom hooks to decide if critical CSS optimization should be skipped.
	 *
	 * @return int 1 if critical CSS should be twosecond_ignored, 0 otherwise
	 */
    function twosecond_check_ignore_critical_css()
    {
        if (isset($this->addSettings['ignoreCriticalCss'])) {
            return $this->addSettings['ignoreCriticalCss'];
        }
        
        $ignore_critical_css = 0;
        
        if (!empty($this->addSettings['twosecond_user_logged_in']) || $this->twosecond_is_404()) {
            $ignore_critical_css = 1;
        }
        
        if(method_exists($this, 'twosecond_check_ignore_critical_css_callback')) {
            $ignore_critical_css = $this->twosecond_check_ignore_critical_css_callback($this->addSettings['full_url'],$ignore_critical_css);
        }

        $ignore_critical_css = $this->twosecond_no_critical_css($ignore_critical_css, $this->addSettings['full_url']);
        
        $this->addSettings['ignoreCriticalCss'] = $ignore_critical_css;
        return $ignore_critical_css;
    }

	/**
	 * Add page critical css
	 *
	 * Adds the current page to the critical CSS generation queue.
	 * This method is called when critical CSS needs to be generated for a
	 * specific page. It checks various conditions before
	 * adding the page to the queue.
	 */
    function twosecond_add_page_critical_css()
    {
        if ($this->twosecond_is_404()) {
            return;
        }
        
        if ((empty($this->settings['is_activated']) || empty($this->settings['license_key']) || strpos($this->settings['license_key'], 'tsdemo') !== false) && rtrim($this->addSettings['full_url_without_param'], '/') !== rtrim($this->addSettings['site_url'], '/')) {
            return;
        }
        
        if (!empty($this->settings['optimization_on'])) {
            $this->criticalData['url'] = $this->addSettings['full_url_without_param'];
            $this->criticalData['data'] = [$this->addSettings['critical_css'], 2, $this->twosecond_preload_css_path()];
        }
    }

	/**
	 * Load style tag in head
	 *
	 * Processes style tags to load them in the head section of the page.
	 * This method can either inline the styles or load them as external files
	 * based on the configuration settings.
	 *
	 * @param array $style_tags Array of style tags to process
	 */
    function twosecond_load_style_tag_in_head($style_tags)
    {
        $counter = 0;
        $load_style_tag_in_head_arr = array();
        
        // Parse the load_style_tag_in_head setting
        $load_style_tag_in_head = !empty($this->settings['load_style_tag_in_head']) && is_array($this->settings['load_style_tag_in_head']) ? $this->settings['load_style_tag_in_head'] : array();
        
        // Build array of CSS selectors and their load options
        foreach ($load_style_tag_in_head as $ex_css) {
            $ex_css_arr = explode(' ', $ex_css);
            $load_style_tag_in_head_arr[$counter][0] = $ex_css_arr[0];
            if (!empty($ex_css_arr[1])) {
                $load_style_tag_in_head_arr[$counter][1] = $ex_css_arr[1];
            }
            $counter++;
        }
        
        $styleArr = array();
        $styleRep = array();
        $stylesContent = '';

        foreach ($style_tags as $style_tag) {
            $file_name = $this->twosecond_get_option('twosecond_rand_key', 0);
            foreach ($load_style_tag_in_head_arr as $ex_css) {
                if (!empty($ex_css[0]) && !empty($style_tag) && strpos($style_tag, $ex_css[0]) !== false) {
                    $styleArr[] = $style_tag;

                    if (!empty($ex_css[1]) && $ex_css[1] === '1') {
                        $file_name = $ex_css[0];
                        $stylesContentFile = $this->twosecond_parse_script('style', $style_tag);
                        $link = $this->twosecond_load_style_in_file($file_name, $stylesContentFile);
                        $styleRep[] = $link;
                    } else {
                        $stylesContent .= $this->twosecond_parse_script('style', $style_tag);
                        $styleRep[] = '';
                    }
                    break;
                }
            }
        }
        if (count($styleArr) > 0 && count($styleRep) > 0) {
            $this->html = str_replace($styleArr, $styleRep, $this->html);
        }
        if (empty($stylesContent)) {
            return;
        }
        $this->html = str_replace('</head>', '<style>' . $this->twosecond_css_compress_init($stylesContent) . '</style></head>', $this->html);
    }

	/**
	 * Load style in file
	 *
	 * @param string $file_name File name.
	 * @param string $stylesContentFile Styles content file.
	 * @return string
	 */
    function twosecond_load_style_in_file($file_name, $stylesContentFile)
    {
        $file_name_cache = md5($file_name) . '.css';
        if (!file_exists($this->twosecond_get_cache_path('css') . '/' . $file_name_cache)) {
            $this->twosecond_create_file($this->twosecond_get_cache_path('css') . '/' . $file_name_cache, $this->twosecond_css_compress_init($stylesContentFile));
        }
        return $this->twosecond_create_css_link($this->twosecond_get_cache_url('css') . '/' . $file_name_cache );
    }

	/**
	 * Create css link
	 *
	 * @param string $file_name_cache File name cache.
	 * @return string
	 */
    function twosecond_create_css_link($file_name_cache){
        $defer = 'href=';
        if (!$this->twosecond_check_ignore_critical_css() && !empty($this->settings['load_critical_css']) && !empty($this->addSettings['critical_css']) && file_exists($this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css'])) {
            $defer = 'data-css="1" href=';
        }
        return '<link'.' rel="'.'stylesheet'.'" ' . $defer . '"' . $file_name_cache . '">';
    }

	/**
	 * Create blank data image
	 *
	 * Creates a transparent SVG image as a data URI for use as a placeholder.
	 * This method generates a minimal SVG that can be used for lazy loading
	 * or as a blank image source.
	 *
	 * @param int $width  Width of the image in pixels
	 * @param int $height Height of the image in pixels
	 * @return string Data URI containing the blank SVG image
	 */
    function twosecond_create_blank_data_image($width, $height)
    {
        // Create a minimal SVG with specified dimensions and transparent fill
        $image = '%3Csvg%20xmlns=\'http://www.w3.org/2000/svg\'%20width=\'' . $width . '\'%20height=\'' . $height . '\'%3E%3Crect%20width=\'100%25\'%20height=\'100%25\'%20opacity=\'0\'/%3E%3C/svg%3E';
        $dataURI = 'data:image/svg+xml,' . $image;
        return $dataURI;
    }

	/**
	 * Load google fonts
	 *
	 * Generates Google Fonts CSS links for the fonts specified in the settings.
	 * This method supports both the legacy CSS API and the newer CSS2 API.
	 * Fonts are optionally localized for better performance.
	 *
	 * @return string HTML string containing Google Fonts link tags
	 */
    function twosecond_load_google_fonts()
    {
        $google_font = array();
        
        if (!empty($this->addSettings['fonts_api_links'])) {
            $all_links = '';
            foreach ($this->addSettings['fonts_api_links'] as $key => $links) {
                $all_links .= !empty($links) && is_array($links) ? $key . ':' . implode(',', $links) . '|' : $key . '|';
            }
            $google_font[] = $this->addSettings['secure'] . "fonts.googleapis.com/css?display=swap&family=" . urlencode(trim($all_links, '|'));
        }
        
        if (!empty($this->addSettings['fonts_api_links_css2'])) {
            $all_links = 'https://fonts.googleapis.com/css2?';
            foreach ($this->addSettings['fonts_api_links_css2'] as $font) {
                $all_links .= $font . '&';
            }
            $all_links .= 'display=swap';
            $google_font[] = $all_links;
        }
        
        $google_font = $this->twosecond_localize_google_fonts($google_font);
        $fontsCss = '';
        if(is_array($google_font) && count($google_font) > 0){
            foreach($google_font as $font){
                $fontsCss .= $this->twosecond_create_css_link($font);
            }
        }

        return $fontsCss;
    }

	/**
	 * Get path from url
	 *
	 * Extracts the directory path from a URL by removing the filename.
	 * This method is useful for determining the base path of resources
	 * when processing relative URLs.
	 *
	 * @param string $url URL to extract path from
	 * @return string Directory path without the filename
	 */
    function twosecond_get_path_from_url($url){
        $replace_array = explode('/',str_replace('\'','/',$url));
		array_pop($replace_array);
		return implode('/',$replace_array);
    }

	/**
	 * Localize fonts
	 *
	 * Downloads and localizes font files referenced in CSS content.
	 * This method fetches external font files and stores them locally
	 * for improved performance and offline availability.
	 *
	 * @param string $url URL of the CSS file containing font references
	 * @return string Local path to the localized CSS file
	 */
    function twosecond_localize_fonts($url){
        if (empty($this->settings['localize_google_fonts'])) {
            return $url;
        }
        
        $cssfileName = md5($url) . '.css';
        $cssfilePath = $this->addSettings['root_cache_path'] . '/fonts/' . $cssfileName;
        
        if(file_exists($cssfilePath) && filesize($cssfilePath) > 0 ){
            $content = $this->twosecond_get_contents($cssfilePath);
             if(!empty($content)){
                return str_replace($this->addSettings['root_cache_path'], '', $cssfilePath);
             }
        }else{
            $content = $this->twosecond_remote_get($url);
        }
        
        if (empty($content)) {
            return $url;
        }
        
        // Process font URLs in the CSS content
        $parentUrl = $this->twosecond_get_path_from_url($url);
        $fontUrl = $this->twosecond_get_tags_data($content, 'url(', ')');
        $font = ['woff2','woff','ttf','eot'];
        
        foreach ($fontUrl as $url) {
            $sanitizeUrl = $this->twosecond_replace_url_brackets($url);
            $file_info = pathinfo($this->twosecond_parse_url($sanitizeUrl, PHP_URL_PATH));
            $ext = isset($file_info['extension']) ? $file_info['extension'] : '';
            
            if(in_array($ext,$font)){
                if(strpos($sanitizeUrl,'..') !== false){
                    $sanitizeUrl = $this->twosecond_remove_dot_path_segments($parentUrl.'/'.$sanitizeUrl);
                }
                
                if ($fileUrl = $this->twosecond_localize_google_font($sanitizeUrl)) {
                    $content = str_replace($url, 'url('.$fileUrl.')', $content);
                }
            } 
        }
        
        $this->twosecond_create_file($cssfilePath, $content);
        return str_replace($this->addSettings['root_cache_path'], '', $cssfilePath);
    }

	/**
	 * Localize google fonts
	 *
	 * Downloads and localizes Google Fonts CSS files and their referenced font files.
	 * This method processes each Google Font URL, downloads the CSS content,
	 * and localizes the font files for improved performance.
	 *
	 * @param array $google_font Array of Google Font URLs to localize
	 * @return array Array of localized font URLs
	 */
    function twosecond_localize_google_fonts($google_font)
    {
        if (empty($this->settings['localize_google_fonts'])) {
            return $google_font;
        }
        $fontsArr = $google_font;
        $enableCdn = $this->twosecond_check_enable_cdn('font');

        $google_font = array_filter($google_font);
        foreach ($google_font as $key => $font) {
            $cssfileName = md5($font) . '.css';
            $cssfilePath = $this->addSettings['root_cache_path'] . '/fonts/' . $cssfileName;
            
            $content = file_exists($cssfilePath) && filesize($cssfilePath) > 0 ? $this->twosecond_get_contents($cssfilePath) : $this->twosecond_remote_get($this->twosecond_ensure_url_scheme($font));
            if (empty($content)) {
                $content = '/* no-css-in-file */';
            }
            $fontUrl = $this->twosecond_get_tags_data($content, 'url(', ')');
            foreach ($fontUrl as $url) {
                if (strpos($url, 'fonts.gstatic.com') !== false) {
                    $sanitizeUrl = $this->twosecond_replace_url_brackets($url);
                    if ($fileUrl = $this->twosecond_localize_google_font($sanitizeUrl)) {
                        $content = str_replace($sanitizeUrl, $fileUrl, $content);
                    }
                }
            }
            
            $fontsArr[$key] = $this->addSettings['cache_url'] . '/fonts/' . $cssfileName;
            if($enableCdn && !$this->twosecond_check_excluded_path($fontsArr[$key], $this->addSettings['font_exclude_cdn_path'])){
                $fontsArr[$key] = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings['font_url_array']['host'], $fontsArr[$key]);
            }
            
            $this->twosecond_create_file($cssfilePath, $content);
        }
        
        return $fontsArr;
    }

	/**
	 * Localize google font
	 *
	 * Downloads and localizes a single Google Font file. This method fetches
	 * the font file from Google's servers and stores it locally for improved
	 * performance and offline availability.
	 *
	 * @param string $sanitizeUrl URL of the Google Font file to localize
	 * @return string|false Localized font URL or false if localization failed
	 */
    function twosecond_localize_google_font($sanitizeUrl)
    {
        $ext = '.' . pathinfo($sanitizeUrl, PATHINFO_EXTENSION);
        $urlArr = $this->twosecond_parse_url($sanitizeUrl);
        $fileName = !empty($urlArr['path']) ? $urlArr['path'] : md5($sanitizeUrl) . $ext;
        $filepath = $this->addSettings['root_cache_path'] . '/fonts' . $fileName;

        $fontData = file_exists($filepath) && filesize($filepath) > 0 ? $this->twosecond_get_contents($filepath) : $this->twosecond_remote_get($sanitizeUrl);
        $enableCdn = $this->twosecond_check_enable_cdn('font');
        
        if (!empty($fontData)) {
            $this->twosecond_create_file($filepath, $fontData);
            $fontUrl = $this->addSettings['cache_url'] . '/fonts' . $fileName;
            
            if($enableCdn && !$this->twosecond_check_excluded_path($fontUrl, $this->addSettings['font_exclude_cdn_path'])){
                $fontUrl = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings['font_url_array']['host'], $fontUrl);
            }
            return $fontUrl;
        }
        return false;
    }

	/**
	 * Lazy load iframe
	 *
	 * Applies lazy loading to iframe elements. This method modifies iframe tags
	 * to use lazy loading, including special handling for YouTube iframes with
	 * thumbnail previews. It also supports custom hooks for iframe processing.
	 *
	 * @param array $iframe_links Array of iframe HTML elements to process
	 */
    function twosecond_lazy_load_iframe($iframe_links)
    {
        if (!empty($this->settings['lazy_load_iframe'])) {
            foreach ($iframe_links as $img) {
                if (strpos($img, '\\') !== false) {
                    continue;
                }
                
                if ($this->twosecond_check_image_excluded($img)) {
                    continue;
                }
                
                $img_obj = $this->twosecond_parse_link('iframe', $img);
                $iframe_html = '';
                
                if (empty($img_obj['src'])) {
                    continue;
                }
                
                if (strpos($img_obj['src'], 'youtu') !== false) {
                    preg_match("#([\/|\?|&]vi?[\/|=]|youtu\.be\/|embed\/)([a-zA-Z0-9_-]+)#", $img_obj['src'], $matches);
                    if (empty($img_obj['style'])) {
                        $img_obj['style'] = '';
                    }
                    $img_obj['style'] .= 'background-image:url(https://i.ytimg.com/vi/' . trim(end($matches)) . '/sd1.jpg);background-size:contain;';
                }
                
                $img_obj['data-src'] = $img_obj['src'];
                $img_obj['src'] = 'about:blank';
                $img_obj['data-class'] = 'LazyLoad';

                $iframe_lazy = 0;
                $iframe_lazy = $this->twosecond_change_iframe_to_iframe_lazy($iframe_lazy);

                if($iframe_lazy){
                    $this->twosecond_str_replace_set_img($img, $this->twosecond_implode_link_array('iframelazy', $img_obj) . $iframe_html);
                } else {
                    $this->twosecond_str_replace_set_img($img, $this->twosecond_implode_link_array('iframe', $img_obj) . $iframe_html);
                }
            }
        }
    }

	/**
	 * Lazy load video
	 *
	 * Applies lazy loading to video elements. This method modifies video tags
	 * to use lazy loading with blank video placeholders and supports custom
	 * hooks for video processing. It also handles CDN integration for videos.
	 *
	 * @param array $video_links Array of video HTML elements to process
	 */
    function twosecond_lazy_load_video($video_links)
    {
        if (!empty($this->settings['lazy_load_video'])) {
            $enableCdn =  $this->twosecond_check_enable_cdn('video');
            
            if (strpos($this->addSettings['cache_url'], $this->addSettings['site_url']) !== false && $enableCdn) {
                $v_src = $this->addSettings['video_cdn_url'] . str_replace($this->addSettings['site_url'], '', $this->addSettings['cache_url']) . '/blank.mp4';
            } else {
                $v_src = $this->addSettings['cache_url'] . '/blank.mp4';
            }
            
            foreach ($video_links as $video) {
                // Skip videos with backslashes (malformed)
                if (strpos($video, '\\') !== false) {
                    continue;
                }
                
                // Skip excluded videos
                if ($this->twosecond_check_image_excluded($video)) {
                    continue;
                }
                
                // Skip lazy loading if source type is video/youtube
                if (preg_match('/<source[^>]*\s+type=["\']video\/youtube["\']/i', $video)) {
                    continue;
                }
                
                $video_new = $video;
                
                // Convert poster attribute to data-poster for lazy loading
                if (strpos($video, 'poster=') !== false) {
                    $video_new = str_replace('poster=', 'data-poster=', $video_new);
                }

                $video_new = preg_replace(
                    '/(<(?:video|source)[^>]*?)\ssrc=(["\']?)([^"\'>\s]+)(["\']?)/i',
                    '$1 src="' . $v_src . '" data-src=$2$3$4',
                    $video_new
                );
                $video_new = str_replace('<video ', '<video data-class="LazyLoad" ', $video_new);

                $videolazy = 0;
                $videolazy = $this->twosecond_change_video_to_videolazy($videolazy);
                // Apply videolazy conversion if enabled
                if ($videolazy) {
                    $video_new = str_replace(array('<video', '</video>'), array('<videolazy', '</videolazy>'), $video_new);
                }

                $videoObj = $this->twosecond_parse_link('video', $video);
                if($enableCdn && !empty($videoObj['src']) && !$this->twosecond_check_excluded_path($videoObj['src'], $this->addSettings['video_exclude_cdn_path'])){
                    $video_new = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings['video_url_array']['host'], $video);
                }
                
                $this->twosecond_str_replace_set_img($video, $video_new);
            }
        }
    }

	/**
	 * Lazy load audio
	 *
	 * Applies lazy loading to audio elements. This method modifies audio tags
	 * to use lazy loading with blank audio placeholders and supports CDN
	 * integration for audio files.
	 *
	 * @param array $audio_links Array of audio HTML elements to process
	 */
    function twosecond_lazy_load_audio($audio_links)
    {
        if (!empty($this->settings['lazy_load_audio']) && count($audio_links) > 0) {
            $enableCdn =  $this->twosecond_check_enable_cdn('audio');
            
            // Determine blank audio source (with or without CDN)
            if (strpos($this->addSettings['cache_url'], $this->addSettings['site_url']) !== false && $enableCdn) {
                $v_src = $this->addSettings['audio_cdn_url'] . str_replace($this->addSettings['site_url'], '', $this->addSettings['cache_url']) . '/blank.mp3';
            } else {
                $v_src = $this->addSettings['cache_url'] . '/blank.mp3';
            }
            
            foreach ($audio_links as $audio) {
                // Skip audio with backslashes (malformed)
                if (strpos($audio, '\\') !== false) {
                    continue;
                }
                
                // Skip excluded audio
                if ($this->twosecond_check_image_excluded($audio)) {
                    continue;
                }
                
                $audioObj = $this->twosecond_parse_link('audio', $audio);
                $audio_new = $audio;
                
                // Apply CDN if enabled and not excluded
                if($enableCdn && !empty($audioObj['src']) && !$this->twosecond_check_excluded_path($audioObj['src'], $this->addSettings['audio_exclude_cdn_path'])){
                    $audio_new = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings['audio_url_array']['host'], $audio_new);
                }
                
                // Set blank audio source and data-src for lazy loading
                $audio_new = str_replace('src=', 'data-class="LazyLoad" src="' . $v_src . '" data-src=', $audio_new);
                $this->twosecond_str_replace_set_img($audio, $audio_new);
            }
        }
    }

	/**
	 * Lazy load picture
	 *
	 * Applies lazy loading and WebP conversion to picture elements.
	 * Processes source tags within picture elements for WebP conversion
	 * and handles the main img tag similar to twosecond_lazy_load_img.
	 *
	 * @param array $picture Array of picture HTML elements to process
	 */
    function twosecond_lazy_load_picture($picture)
    {
        if (empty($this->settings['lazy_load'])) {
            return;
        }

        if (!empty($picture)) {
            $webp_enable = $this->addSettings['webp_enable'];
            list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
            
            foreach ($picture as $pictureElement) {
                $picture_org = $pictureElement;
                $picture_new = $pictureElement;
                
                // Extract all source tags from the picture element
                $sourceTags = $this->twosecond_get_tags_data($pictureElement, '<source', '>');
                
                // Process each source tag for WebP conversion
                if (!empty($sourceTags)) {
                    foreach ($sourceTags as $sourceTag) {
                        $source_org = $sourceTag;
                        
                        // Extract srcset using twosecond_parse_link (now fixed to preserve URL encoding)
                        $source_obj = $this->twosecond_parse_link('source', $sourceTag);
                        $srcset_original = !empty($source_obj['srcset']) ? $source_obj['srcset'] : '';
                        
                        if (!empty($srcset_original)) {
                            $components = $this->twosecond_parse_url($srcset_original);
                            
                            // Process internal images only
                            if (!$this->twosecond_is_external($srcset_original, $components)) {
                                // Parse srcset to get individual URLs
                                $urls = $this->twosecond_parse_srcset($srcset_original);
                                
                                // Check if any URL has a supported image extension
                                $hasSupportedExt = false;
                                foreach ($urls as $url) {
                                    $twoSecond_img_ext = '.' . pathinfo($url, PATHINFO_EXTENSION);
                                    if (count($webp_enable) > 0 && in_array($twoSecond_img_ext, $webp_enable)) {
                                        $hasSupportedExt = true;
                                        break;
                                    }
                                }
                                
                                // Convert srcset to WebP if supported
                                if ($hasSupportedExt) {
                                    $srcset_webp = $this->twosecond_srcset_to_webp($srcset_original, $img_root_url, $img_root_path);
                                    
                                    // Replace srcset in the source tag using regex to preserve exact format
                                    if ($srcset_webp != $srcset_original) {
                                        $sourceTag = preg_replace(
                                            '/(\s+srcset=["\'])([^"\']+)(["\'])/i',
                                            '$1' . $srcset_webp . '$3',
                                            $sourceTag
                                        );
                                        // Replace the source tag in the picture element
                                        $picture_new = str_replace($source_org, $sourceTag, $picture_new);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Apply CDN if enabled and not excluded
                if ($this->twosecond_check_enable_cdn('image') && !$this->twosecond_check_excluded_path($picture_new, $this->addSettings['image_exclude_cdn_path'])) {
                    $picture_new = str_replace($this->addSettings['site_url'], $this->addSettings['image_cdn_url'], $picture_new);
                }
                
                // Replace the picture element in HTML
                if ($picture_org != $picture_new) {
                    $this->twosecond_str_replace_set_img($picture_org, $picture_new);
                }
            }
        }
    }

	/**
	 * Lazy load img
	 *
	 * Applies lazy loading to image elements.
	 * This method handles responsive images, WebP conversion, CDN integration,
	 * and various image optimization features. It also supports custom hooks
	 * for image processing.
	 *
	 * @param array $img_links     Array of img HTML elements to process
	 */
    function twosecond_lazy_load_img($img_links)
    {
        // Don't skip entirely if lazy_load is off, because we still need WebP and Responsive images
        if (empty($img_links)) {
            return;
        }

        // Process img elements
        if (!empty($img_links)) {
            $webp_enable = $this->addSettings['webp_enable'];
            
            foreach ($img_links as $img) {
                $imgnn = $img;
                $imgnn_org_arr = $imgnn_arr = $this->twosecond_parse_link('img', $imgnn);
                
                if (empty($imgnn_arr['src'])) {
                    continue;
                }
                
                // Skip malformed or data URI images
                if (strpos($imgnn_arr['src'], '\\') !== false || strpos($imgnn_arr['src'], 'data:image') !== false) {
                    continue;
                }
                
                $components = $this->twosecond_parse_url($imgnn_arr['src']);
                
                // Process internal images
                if (!$this->twosecond_is_external($imgnn_arr['src'], $components)) {
                    $imgnn_arr['src'] = $this->twosecond_remove_query_params($imgnn_arr['src'], $components);
                    list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
                    $twoSecond_img_ext = '.' . pathinfo($imgnn_arr['src'], PATHINFO_EXTENSION);
                    
                    $imgsrc_filepath = $this->twosecond_get_resource_root_path($imgnn_arr['src'], $img_root_url, $img_root_path);
                    $imgnn = trim(preg_replace('/\s+/', ' ', $imgnn));
                    
                    // Get and apply image dimensions
                    $img_size = $this->twosecond_get_image_size($imgsrc_filepath);
                    if (!empty($img_size[0]) && !empty($img_size[1])) {
                        $imgnn = $this->twosecond_get_image_attributes($imgnn, $imgnn_arr, $img_size);
                    }
                    
                    // Handle mobile responsive images
                    if (!empty($this->addSettings['is_mobile']) && !empty($this->settings['resp_bg_img']) && !$this->twosecond_exclude_image_from_convert_to_webp($imgsrc_filepath)) {
                        if (!empty($img_size[0]) && $img_size[0] > 400) {
                            $this->twosecond_enque_responsive_image($imgnn_arr['src']);
                            [$imgnn_arr, $imgsrc_filepath] = $this->twosecond_convert_to_smaller_image($img_root_path, $imgsrc_filepath, $imgnn_arr);
                        }
                    }
                    // Process srcset for responsive images
                    if (strpos($imgnn, ' srcset=') !== false) {
                        $urls = $this->twosecond_parse_srcset($imgnn_arr['srcset']);
                        foreach ($urls as $url) {
                            $this->twosecond_enque_responsive_image($url);
                        }
                    }
                    
                    // Handle WebP conversion
                    if (count($webp_enable) > 0 && !$this->twosecond_exclude_image_from_convert_to_webp($imgsrc_filepath)) {
                        foreach($imgnn_arr as $attrName => $attrValue){
                            if(empty($attrValue)) continue;
                            $attrExt = '.' . pathinfo($attrValue, PATHINFO_EXTENSION);
                            if(in_array($attrExt, $webp_enable)){
                                if(strpos($attrName, 'srcset') !== false){
                                    if(strpos($imgnn_arr['src'], '-595xh.webp') !== false){
                                        $imgnn_arr[$attrName] = '';
                                    } else {
                                        $imgnn_arr[$attrName] = $this->twosecond_srcset_to_webp($attrValue, $img_root_url, $img_root_path);
                                    }
                                } else {
                                    $imgsrc_webpfilepath_attr = $this->twosecond_get_img_webp_path($img_root_path, $this->twosecond_get_resource_root_path($attrValue, $img_root_url, $img_root_path));
                                    if (file_exists($imgsrc_webpfilepath_attr)) {
                                        $imgnn_arr[$attrName] = $this->twosecond_convert_to_webp($attrValue);
                                    } else {
                                        $this->webpEnqueImageUrls[] = $attrValue;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Update srcset if modified
                if (!empty($imgnn_org_arr['srcset']) && $imgnn_arr['srcset'] != $imgnn_org_arr['srcset']) {
                    if(empty($imgnn_arr['srcset'])){
                        $imgnn = str_replace([' srcset="'.$imgnn_org_arr['srcset'].'"', " srcset='".$imgnn_org_arr['srcset']."'"],'', $imgnn);
                    }else{
                        $imgnn = str_replace($imgnn_org_arr['srcset'], $imgnn_arr['srcset'], $imgnn);
                    }
                }
                
                // Update src if modified
                if ($imgnn_arr['src'] != $imgnn_org_arr['src']) {
                    if(strpos($imgnn,' src=') !== false){
                        $imgnn = str_replace([' src="'.$imgnn_org_arr['src'].'"', " src='".$imgnn_org_arr['src']."'"],[' src="'.$imgnn_arr['src'].'"', " src='".$imgnn_arr['src']."'"], $imgnn);
                    }elseif(strpos($imgnn,'src=') !== false){
                        $imgnn = str_replace(['"src="'.$imgnn_org_arr['src'].'"', "'src='".$imgnn_org_arr['src']."'"],['" src="'.$imgnn_arr['src'].'"', "' src='".$imgnn_arr['src']."'"], $imgnn);
                    }
                }
                
                // Apply CDN if enabled and not excluded
                if ($this->twosecond_check_enable_cdn('image') && !$this->twosecond_check_excluded_path($imgnn, $this->addSettings['image_exclude_cdn_path'])) {
                    $imgnn = str_replace($this->addSettings['site_url'], $this->addSettings['image_cdn_url'], $imgnn);
                }
                $imgSrcArr = $this->twosecond_parse_url($imgnn_arr['src']);
                // Handle excluded images (preload instead of lazy load)
                if ($this->twosecond_check_image_excluded($img, $imgnn_arr,$imgSrcArr)) {
                    if(!in_array($imgSrcArr['path'], $this->addSettings['preload_resources']['all'])){
                        $this->addSettings['preload_resources']['all'][] = $imgSrcArr['path'];
                    }

                    $imgnn = $this->twosecond_customize_image($imgnn, $img, $imgnn_arr);
                    
                    // Set high priority loading for excluded images
                    $imgnn = str_replace('<img ', '<img fetchpriority="high" loading="eager" ', $imgnn);
                    if ($img != $imgnn) {
                        $this->twosecond_str_replace_set_img($img, $imgnn);
                    }
                    continue;
                } elseif (!empty($imgnn_arr['fetchpriority']) && $imgnn_arr['fetchpriority'] == 'high') {
                    // Set eager loading for high priority images
                    $imgnn = str_replace(' src=', ' loading="eager" src=', $imgnn);
                } else {
                    if (function_exists('twosecond_img_src_to_data_src')) {
                        $img_size = empty($img_size) ? array() : $img_size;
                        $blank_image_url = $this->twosecond_get_blank_image_url($img_size);
                        // Preserve original quote style around src when converting to data-src
                        $imgnn = preg_replace_callback(
                            '/\s+src=(["\'])([^"\']*)(["\'])/i',
                            function ($m) use ($blank_image_url) {
                                $quote = $m[1];
                                $origSrc = $m[2];
                                return ' data-class='. $quote .'LazyLoad'. $quote . ' src=' . $quote . $blank_image_url . $quote . ' data-src=' . $quote . $origSrc . $quote;
                            },
                            $imgnn,
                            1
                        );
                        if (strpos($imgnn, ' srcset=') !== false) {
                            $imgnn = preg_replace(
                                '/\s+srcset=(["\'])([^"\']*)(["\'])/i',
                                ' data-srcset=$1$2$3',
                                $imgnn,
                                1
                            );
                        }
                    } else {
                        $imgnn = str_replace([' src="', " src='"],[' loading="lazy" src="', " loading='lazy' src='"], $imgnn);
                    }
                }

                $imgnn = $this->twosecond_customize_image($imgnn, $img, $imgnn_arr);
                
                $this->twosecond_str_replace_set_img($img, $imgnn);
            }
        }
    }

	/**
	 * Convert to smaller image
	 *
	 * @param string $imgsrc_filepath Img src filepath.
	 * @param array $imgnn_arr Img array.
	 * @return array
	 */
    function twosecond_convert_to_smaller_image($img_root_path, $imgsrc_filepath, $imgnn_arr = [])   
    {
        $imgsrc_filepath_595xh = $this->twosecond_get_img_webp_path595xh($this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath),$imgsrc_filepath);
        if (file_exists($imgsrc_filepath_595xh)) {
            $imgsrc_filepath = $imgsrc_filepath_595xh;
            $imgsrc_filepath = str_replace(' ', '%20', $imgsrc_filepath);
            $imgnn_arr['src'] = $this->twosecond_get_media_resource_url($imgsrc_filepath);
        }
        return [$imgnn_arr, $imgsrc_filepath];
    }

	/**
	 * Convert srcset to webp
	 *
	 * @param string $srcset Srcset.
	 * @param string $img_root_url Img root url.
	 * @param string $img_root_path Img root path.
	 * @return string
	 */
    function twosecond_srcset_to_webp($srcset, $img_root_url, $img_root_path)
    {
        $urls = $this->twosecond_parse_srcset($srcset);
        $webpUrls = $this->twosecond_src_to_webp($urls, $img_root_url, $img_root_path);
        if (count($webpUrls) > 0) {
            $srcset = str_replace($urls, $webpUrls, $srcset);
        }
        return $srcset;
    }

	/**
	 * Convert src to webp
	 *
	 * @param array $urls Urls.
	 * @param string $img_root_url Img root url.
	 * @param string $img_root_path Img root path.
	 * @return array
	 */
    function twosecond_src_to_webp($urls, $img_root_url, $img_root_path)
    {
        $webpUrls = [];
        foreach ($urls as $url) {
            $imgsrc_filepath = $this->twosecond_get_resource_root_path($url, $img_root_url, $img_root_path);
            $imgsrc_webpfilepath = $this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath);
            if (file_exists($imgsrc_webpfilepath)) {
                $webpUrls[] = $this->twosecond_get_media_resource_url($imgsrc_webpfilepath);
            } else {
                $this->webpEnqueImageUrls[] = $url;
                $webpUrls[] = $url;
            }
        }
        return $webpUrls;
    }

	/**
	 * Parse srcset
	 *
	 * @param string $srcset Srcset.
	 * @return array
	 */
    function twosecond_parse_srcset($srcset)
    {
        $entries = explode(',', $srcset);
        $urls = [];

        foreach ($entries as $entry) {
            $parts = preg_split('/\s+/', trim($entry));
            if (count($parts) > 0) {
                $urls[] = $parts[0];
            }
        }

        return $urls;
    }

    /**
	 * Get img webp path 595xh
	 *
	 * @param string $imgsrc_webpfilepath Img webp filepath.
	 * @param string $imgsrc_filepath Img src filepath.
	 * @return string
	 */
    function twosecond_get_img_webp_path595xh($imgsrc_webpfilepath,$imgsrc_filepath)
    {
        $extension = strtolower(pathinfo($imgsrc_filepath, PATHINFO_EXTENSION));
        if($extension !== 'webp'){
            return str_replace('.webp','-595xh.webp', $imgsrc_webpfilepath);
        }
        return $imgsrc_webpfilepath.'-595xh.webp';
         
    }

	/**
	 * Get img webp path
	 *
	 * @param string $img_root_path Img root path.
	 * @param string $imgsrc_filepath Img src filepath.
	 * @return string
	 */
    function twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath)
    {
        if(strpos($imgsrc_filepath, 'ts-webp') !== false && strpos($imgsrc_filepath, '.webp') !== false){
            return $imgsrc_filepath;
        }
        $extension = strtolower(pathinfo($imgsrc_filepath, PATHINFO_EXTENSION));
        if($extension !== 'webp'){
            $imgsrc_filepath = $imgsrc_filepath . '.webp';
        }
        if (!empty($this->addSettings['upload_path']) && strpos($imgsrc_filepath, $this->addSettings['webp_path']) === false) {
            $imgsrc_webpfilepath = str_replace($this->addSettings['upload_path'], $this->addSettings['webp_path'], $imgsrc_filepath);
        } else {
            $imgsrc_webpfilepath = str_replace($img_root_path . $this->addSettings['upload_path'], $img_root_path . $this->addSettings['webp_path'], $imgsrc_filepath);
        }
        return $imgsrc_webpfilepath;
    }

	/**
	 * Remove query params
	 *
	 * @param string $url Url.
	 * @param array $components Components.
	 * @return string
	 */
    function twosecond_remove_query_params($url, $components = [])
    {
        $url = urldecode(trim($url));
        $components = isset($components['path']) ? $components : $this->twosecond_parse_url($url);
        $path = isset($components['path']) ? $components['path'] : '';
        $query = isset($components['query']) ? $components['query'] : '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $urlPath = false;
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'css', 'js'];
        if (in_array($extension, $allowedExtensions)) {
            $urlPath = $path;
        }else{
            $urlPath = $path.'?'.$query;
        }
        $urlPath = '/'.ltrim(ltrim($urlPath, './'), '/');
        if (!isset($components['scheme']) || !isset($components['host'])) {
            return $this->addSettings['site_url'] . $urlPath;
        } else {
            return $this->addSettings['site_url_array']['scheme'] . '://' . $components['host'] . $urlPath;
        }
    }

	/**
	 * Get image attributes
	 *
	 * @param string $imgnn Img nn.
	 * @param array $imgnn_arr Img array.
	 * @param array $img_size Img size.
	 * @return string
	 */
    function twosecond_get_image_attributes($imgnn, $imgnn_arr, $img_size)
    {
        if(!empty($imgnn_arr['width']) && strpos($imgnn_arr['width'], 'px') !== false){
            $imgnn = str_replace([' width="'.$imgnn_arr['width'].'"', " width='".$imgnn_arr['width']."'"], [' width="'.intval($imgnn_arr['width']).'"', " width='".intval($imgnn_arr['width'])."'"], $imgnn);
        }
        if(!empty($imgnn_arr['height']) && strpos($imgnn_arr['height'], 'px') !== false){
            $imgnn = str_replace([' height="'.$imgnn_arr['height'].'"', " height='".$imgnn_arr['height']."'"], [' height="'.intval($imgnn_arr['height']).'"', " height='".intval($imgnn_arr['height'])."'"], $imgnn);
        }

        if ((empty($imgnn_arr['width']) || empty($imgnn_arr['height'])) && !empty($img_size[0]) && !empty($img_size[1])) {
            $class = "twosecond-ratio-{$img_size[0]}x{$img_size[1]}";
            $this->addSettings['twosecond_img_aspect_ratio'][$class] = [$img_size[0], $img_size[1]];
            if(strpos($imgnn, ' class=') !== false){
               $imgnn = str_replace([' class="', " class='"],[' class="'.$class.' ', " class='".$class." "], $imgnn);  
            } else {
                if(strpos($imgnn, 'class=') !== false){
                    $imgnn = str_replace(['class="', "class='"],[' class="'.$class.' ', " class='".$class." "], $imgnn);
                } else {
                    $imgnn = str_replace([' src="', " src='"],[' class="'.$class.'" src="', " class='".$class."' src='"], $imgnn);
                }
            }
        }
        return $imgnn;
    }

    function twosecond_get_image_size($path)
    {
        if (!file_exists($path)) {
            return array('', '');
        }
        $img_size = array();
        $twoSecond_img_ext = '.' . pathinfo($path, PATHINFO_EXTENSION);
        if ($twoSecond_img_ext == '.svg') {
            list($img_size[0], $img_size[1], $alt) = $this->twosecond_get_svg_attributes($this->twosecond_get_contents($path));
        } else {
            $img_size = strlen($path) < 4097 && file_exists($path) ? @getimagesize($path) : array();
        }
        return $img_size;
    }
	
	/**
	 * Get img root path
	 *
	 * @return array
	 */
    function twosecond_get_img_root_path()
    {
        // On local subdirectory installs, ABSPATH is more reliable than document_root
        $root = defined('ABSPATH') ? ABSPATH : $this->addSettings['document_root'];
        return array(rtrim(str_replace('\\', '/', $root), '/'), $this->addSettings['is_multisite'] ? $this->addSettings['network_site_url'] : $this->addSettings['site_url']);
    }
	
	/**
	 * Get resource root path
	 *
	 * @param string $src Src.
	 * @param string $img_root_url Img root url.
	 * @param string $img_root_path Img root path.
	 * @return string
	 */
    function twosecond_get_resource_root_path($src, $img_root_url, $img_root_path)
    {
        if ($this->addSettings['is_multisite_sub_domain']) {
            $src = str_replace($this->addSettings['site_url'], $this->addSettings['network_site_url'], $src);
        }
        
        // Normalize schemes to prevent mismatch doubling
        $normalized_src = preg_replace('/^https?\:/i', '', $src);
        $normalized_root_url = preg_replace('/^https?\:/i', '', $img_root_url);
        
        if (strpos($normalized_src, $normalized_root_url) === false) {
            $src = str_replace(array('https://', 'http://'), $this->addSettings['site_url_array']['scheme'] . '://', $src);
            if(strpos($src,$this->addSettings['site_url_diff']) !== false){
                $src = str_replace($this->addSettings['site_url_diff'],$this->addSettings['site_url_array']['host'],$src);
            }
        }
        
        // Strip the root URL more safely
        $src_path_only = str_replace($img_root_url, '', $src);
        // If it still looks like an absolute URL after replacement, try without scheme
        if (preg_match('/^https?:\/\//i', $src_path_only)) {
             $src_path_only = str_replace($normalized_root_url, '', $normalized_src);
        }

        $path = $img_root_path . '/' . ltrim($src_path_only, '/');
        if (strpos($path, '..') !== false) {
            $path = $this->twosecond_remove_dot_path_segments($path);
        }
        // Remove potential double slashes
        $path = str_replace('//', '/', $path);
        if (strpos($path, ':/') !== false) {
            $path = str_replace(':/', '://', $path); // restore scheme if accidentally mangled
        }
        return $path;
    }
	
	/**
	 * Get resource url
	 *
	 * @param string $path Path.
	 * @return string
	 */
    function twosecond_get_media_resource_url($path)
    {
        if ($this->addSettings['is_multisite_sub_domain']) {
            return str_replace($this->addSettings['document_root'], $this->addSettings['network_site_url'], $path);
        } else {
            return str_replace($this->addSettings['document_root'], $this->addSettings['site_url'], $path);
        }
    }
	
	/**
	 * Get blank image url
	 *
	 * @param array $img_size Img size.
	 * @return string
	 */
    function twosecond_get_blank_image_url($img_size)
    {
        if (!empty($img_size[0]) && !empty($img_size[1])) {
            $blank_image_url = $this->twosecond_create_blank_data_image((int)$img_size[0], (int)$img_size[1]);
        } else {
            $blank_image_url = $this->addSettings['blank_image_url'];
        }
        return $blank_image_url;
    }
	
	/**
	 * Get svg xml
	 *
	 * @param string $data Data.
	 * @return string
	 */
    function twosecond_get_svg_xml($data)
    {
        if (strpos($data, '<svg') !== false) {
            return @simplexml_load_string($data);
        } elseif (file_exists($data)) {
            return simplexml_load_file($data);
        } else {
            return array();
        }
    }

	/**
	 * Get svg attributes
	 *
	 * @param string $content Content.
	 * @return array
	 */
    function twosecond_get_svg_attributes($content)
    {
        $svg = $this->twosecond_get_svg_xml(html_entity_decode($content));
        if (!empty($svg['width'])) {
            if (strpos($svg['width'], 'em') !== false) {
                $width = (int)$svg['width'] * 16;
            } elseif (strpos($svg['width'], 'mm') !== false) {
                $width = (int)$svg['width'] * 3.7795275591;
            } else {
                $width = (int)$svg['width'];
            }
        } else {
            $width = '';
        }
        if (!empty($svg['height'])) {
            if (strpos($svg['height'], 'em') !== false) {
                $height = (int)$svg['height'] * 16;
            } else {
                $height = (int)$svg['height'];
            }
        } else {
            $height = '';
        }
        return array($width, $height, $this->twosecond_get_svg_title($svg));
    }
		
	/**
	 * Get svg title
	 *
	 * @param string $svg Svg.
	 * @return string
	 */
    function twosecond_get_svg_title($svg)
    {
        return (!empty($svg->title) ? (string)$svg->title : '');
    }
	
	/**
	 * Check image excluded
	 *
	 * @param string $img Img.
	 * @param array $imgnn_arr Img array.
	 * @return int
	 */
    function twosecond_check_image_excluded($img, $imgnn_arr = [], $imgSrcArr = [])
    {
        $exclude_image = 0;
        if(!empty($imgnn_arr['data-lazyload'])){
            $exclude_image = 1;
        }elseif ($this->settings['lazy_load']) {
            foreach ($this->addSettings['excluded_img'] as $ex_img) {
                if (!empty($ex_img) && strpos($img, $ex_img) !== false) {
                    $exclude_image = 1;
                }
            }
            if(!empty($imgSrcArr['path'])){
                foreach ($this->addSettings['preload_resources']['all'] as $ex_img) {
                    if (!empty($ex_img) && strpos($imgSrcArr['path'], $ex_img) !== false) {
                        $exclude_image = 1;
                    }
                }
            }
            if (!empty($imgnn_arr['data-class']) && strpos($imgnn_arr['data-class'], 'LazyLoad') !== false) {
                $exclude_image = 1;
            }
        } else {
            $exclude_image = 1;
        }
        $exclude_image = $this->twosecond_exclude_image_to_lazyload($exclude_image, $img, $imgnn_arr);
        return $exclude_image;
    }
	
	/**
	 * Convert svgs to file
	 *
	 * @param array $svgs Svgs.
	 */
    function twosecond_convert_svg_to_file($svgs)
    {
        $convertedSvg = [];
        foreach ($svgs as $svg) {
            $path = $this->addSettings['root_cache_path'] . '/images/';
            $filename = md5($svg) . '.svg';
            if (!in_array($filename, $convertedSvg)) {
                $convertedSvg[] = $filename;
            } else {
                continue;
            }
            if ($this->twosecond_check_image_excluded($svg)) {
                continue;
            }
            if (!file_exists($path . $filename)) {
                $filePath = $this->twosecond_create_file($path . $filename, $svg);
            }
            if (file_exists($path . $filename)) {
                $newSvgArr = array();
                $newSvgArr['src'] = $this->addSettings['cache_url'] . '/images/' . $filename;
                list($newSvgArr['width'], $newSvgArr['height'], $newSvgArr['alt']) = $this->twosecond_get_svg_attributes($svg);
                $newSvgArr['loading'] = 'lazy';
                $newSvgArr['class'] = 'twosecond-svg';
                $this->twosecond_str_replace_set_img($svg, $this->twosecond_implode_link_array('img', $newSvgArr));
            }
        }
    }
	
	/**
	 * Lazyload
	 *
	 * @param array $all_links All links.
	 */
    function twosecond_lazyload_all($all_links)
    {
        $this->twosecond_lazy_load_iframe($all_links['iframe']);
        $this->twosecond_lazy_load_video($all_links['video']);
        $this->twosecond_lazy_load_audio($all_links['audio']);
        $this->twosecond_lazy_load_picture($all_links['picture']);
        $this->twosecond_lazy_load_img($all_links['img']);
        if (!empty($all_links['svg'])) {
            $this->twosecond_convert_svg_to_file($all_links['svg']);
        }
        $this->html = $this->twosecond_convert_arr_relative_to_absolute($this->html, $this->addSettings['full_url_without_param'] . '/index.php', $all_links['url']);
    }
	
	/**
	 * Lazy load background image
	 */
    function twosecond_lazy_load_background_image()
    {
        $elements = array('<div ', '<section ', '<iframelazy ', '<iframe ');
        $Repelements = array('<div data-bglz=1 ', '<section data-bglz=1 ', '<iframelazy data-bglz=1 ', '<iframe data-bglz=1 ');
        $this->html = str_replace($elements, $Repelements, $this->html);
    }
	
	/**
	 * TwoSecond core web vitals script
	 *
	 * @return string
	 */
    function twosecond_core_web_vitals_script()
    {
        $script_content = '<script>
			(function () {
				var secureKey = \'' . $this->twosecond_create_secure_key('cwvLog') . '\';
				var adminAjax = \'' . $this->twosecond_get_ajax_url() . '\';
				var device = /Mobi|Android/i.test(navigator.userAgent) ? "Mobile" : "Desktop" ;
				var script = document.createElement(\'script\');
				script.src = \'' . $this->twosecond_asset_url('assets/js/web-vitals.iife.js') . '\';
				script.onload = function () {
					webVitals.onCLS(handleVitalsCLS);
					webVitals.onFID(handleVitalsFID);
					webVitals.onLCP(handleVitalsLCP);
					webVitals.onINP(handleVitalsINP);
				};
				document.head.appendChild(script);
			
	
				function handleVitalsFID(metric) {
				   if(metric.rating != \'good\'){
						var metricString = JSON.stringify(metric);
						var metricObject = JSON.parse(metricString);
						var index = 0;
						metric.entries.forEach(() => {
							 metricObject.entries[index].targetElement = metric.entries[index].target.className;
							index++;
						});
						var lastString = JSON.stringify(metricObject);
						w3Ajax(lastString,\'LCP\');
					}
				}
		
				function handleVitalsCLS(metric) {
				   if(metric.rating != \'good\'){
					var metricString = JSON.stringify(metric);
					var metricObject = JSON.parse(metricString);
					metric.entries.forEach((e,i) => {
						e.sources.forEach((j,k) => {
							metricObject.entries[i].sources[k].targetElement = j.node["className"];
						});
					});
					var lastString = JSON.stringify(metricObject);
					w3Ajax(lastString,\'CLS\');
				  }
				}
		
				function handleVitalsLCP(metric) {
					if(metric.rating != \'good\'){
						var metricString = JSON.stringify(metric); // Serialize the metric object
						var metricObject = JSON.parse(metricString);
						var index = 0;
						metric.entries.forEach(() => {
							 metricObject.entries[index].targetElement = metric.entries[index].element.className;
							index++;
						});
						var lastString = JSON.stringify(metricObject);
						w3Ajax(lastString,\'LCP\');
					}
				} 
				function twoSecondAjax(lastString, issueType) {
					var xhr = new XMLHttpRequest();
					var url = adminAjax;  // Assuming `adminAjax` is defined elsewhere

					xhr.open(\'POST\', url, true);
					xhr.setRequestHeader(\'Content-Type\', \'application/x-www-form-urlencoded\');

					// Create the data string in the URL-encoded format
					var data = \'action=twosecond_put_data\' +
								\'&_twosecond_nonce=\'+encodeURIComponent(secureKey) +
							   \'&data=\' + encodeURIComponent(lastString) +
							   \'&url=\' + encodeURIComponent(window.location.href) +
							   \'&issueType=\' + encodeURIComponent(issueType) +
							   \'&deviceType=\' + encodeURIComponent(device); 

					xhr.onreadystatechange = function() {
						if (xhr.readyState === XMLHttpRequest.DONE) {
							if (xhr.status === 200) {
								console.log(\'data inserted\');
							} else {
								console.log(xhr.statusText);
							}
						}
					};

					xhr.onerror = function() {
						console.log(xhr.statusText);
					};

					xhr.send(data);
				}
				function handleVitalsINP(metric) {
					if(metric.rating != \'good\'){
						var metricString = JSON.stringify(metric); 
						var metricObject = JSON.parse(metricString);
						var index = 0;
						metric.entries.forEach(() => {
							 metricObject.entries[index].targetElement = metric.entries[index].target.className;
							index++;
						});
						var lastString = JSON.stringify(metricObject);
						w3Ajax(lastString,\'LCP\');
					}
				}
			})();
		</script>';
        $webVitalspath = $this->twosecond_get_cache_path('all-js') . '/webvital.js';
        if (!is_file($webVitalspath)) {
            $this->twosecond_create_file($webVitalspath, $this->twosecond_compress_js($script_content));
        }
        return $this->twosecond_get_contents($webVitalspath);
    }
	
	/**
	 * Copy assets
	 */
    function twosecond_copy_assets()
    {
        $path = $this->twosecond_get_cache_path();
        $fileArray = ['blank.mp4', 'blank.mp3', 'blank.png'];
        foreach ($fileArray as $value) {
            if (!file_exists($path . '/' . $value)) {
                copy(TWOSECOND_DIR. "assets/images/" . $value, $path . '/' . $value);
            }
        }
    }
	
	/**
	 * Insert critical css
	 *
	 * @return array
	 */
    function twosecond_insert_critical_css()
    {
        $criticalCssInsertion = '';
        $criticalReplace = [];
        if (!$this->twosecond_check_ignore_critical_css()) {
            if (!empty($this->addSettings['twosecond_get']['twosecond_get_css_post_type'])) {
                $this->html .= 'rocket22' . TWOSECOND_VERSION . str_replace($this->addSettings['document_root'], '', $this->twosecond_preload_css_path()) . '--' . $this->addSettings['critical_css'] . '--' . file_exists($this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css']);
            }
            if (!empty($this->settings['load_critical_css'])) {
                if (!file_exists($this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css']) || filesize($this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css']) < 10) {
                    $this->twosecond_delete_file($this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css']);
                    $this->twosecond_add_page_critical_css();
                } else {
                    $critical_css = $this->twosecond_get_contents($this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css']);
                    if (!empty($critical_css)) {
                        $critical_css = $this->twosecond_customize_critical_css($critical_css);
                        $criticalCssInsertion = 1;
                        $critical_css_modified = $this->twosecond_convert_webp_in_critical($critical_css);
                        if(strlen($critical_css_modified) !== strlen($critical_css)){
                            $this->twosecond_put_contents($this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css'], $critical_css_modified);
                        }
                        $this->twosecond_preload_font_from_critical($critical_css_modified);
                        if (!empty($this->settings['load_critical_css_style_tag'])) {
                            $critical_css_modified = preg_replace('/\/\*\s*<tsimages>.*?<\/tsimages>\s*\*\//s', '', $critical_css_modified);
                            $critical_css_modified = preg_replace('/\/\*\s*<twosecond_elements>.*?<\/twosecond_elements>\s*\*\//s', '', $critical_css_modified);
                            $critical_css_modified = preg_replace('/\/\*\s*<tsDataBgLoad>.*?<\/tsDataBgLoad>\s*\*\//s', '', $critical_css_modified);
                            $criticalReplace[0] = array('data-css="1" ', '{{main_twosecond_critical_css}}');
                            $criticalReplace[1] = array('data-', '<style id="twosecond-critical-css">' . $critical_css_modified . '</style>');
                            $this->addSettings['preload_resources']['critical_css'] = 1;
                        } else {
                            $enableCdnCss = $this->twosecond_check_enable_cdn('css');
                            $critical_css_url = str_replace($this->addSettings['document_root'], ($enableCdnCss ? $this->addSettings['css_cdn_url'] : $this->addSettings['site_url']), $this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css']);
                            $critical_css_url .= '?v='. $this->twosecond_random_number();
                            $criticalReplace[0] = array('data-css="1" ', '{{main_twosecond_critical_css}}');
                            $criticalReplace[1] = array('data-', '<link rel="'.'stylesheet'.'" href="' . $critical_css_url . '"/>');
                            $this->addSettings['preload_resources']['critical_css'] = $critical_css_url;
                        }
                    } else {
                        $this->twosecond_add_page_critical_css();
                    }
                }
            }
        }
        return array($criticalCssInsertion, $criticalReplace);
    }
	
	/**
	 * Preload font from critical
	 *
	 * @param string $content Content.
	 */
    function twosecond_preload_font_from_critical($content){
        $fontfaces = $this->twosecond_get_tags_data($content, '@font-face', '}');
        $preferredFormats = ['woff2', 'woff', 'ttf', 'eot', 'svg'];
        foreach ($fontfaces as $fontface) {
            $urls = $this->twosecond_get_tags_data($fontface, 'url(', ')');
            $fontAdded = false;
            $fontUrl = '';
            foreach ($preferredFormats as $format) {
                foreach ($urls as $url) {
                    $url = $this->twosecond_replace_url_brackets($url);
                    if(strpos($url, 'data:') !== false){
                        continue;
                    }
                    $fontUrl = $url = trim($url);
                    if (stripos($url, $format) !== false) {
                        $this->addSettings['preload_resources']['font'][] = $url;
                        $fontAdded = true;
                        break 2;
                    }
                }
            }
            if (!$fontAdded && count($urls) && !empty($fontUrl)) {
                $this->addSettings['preload_resources']['font'][] = $this->twosecond_change_url_scheme($fontUrl);
            }
        }
    }
	
	/**
	 * Compress js
	 *
	 * @param string $string String.
	 * @return string
	 */
    function twosecond_compress_js($string)
    {
        include_once TWOSECOND_DIR. 'includes/TwoSecond_TSjsMin.php';
        if (!\TwoSecond_TSjsMin::isLikelyMinified($string)) {
            $string = \TwoSecond_TSjsMin::minify($string);
        }
        return $string;
    }

	/**
	 * Get web cache path
	 *
	 * @param string $type Type.
	 * @param string $cachePath Cache path.
	 * @return string
	 */
    function twosecond_get_web_cache_path($type, $cachePath)
    {
        if ($this->twosecond_is_plugin_active('gtranslate/gtranslate.php')) {
            if (isset($this->addSettings['twosecond_server']["HTTP_X_GT_LANG"])) {
                $cacheFilePath = $this->addSettings['root_cache_path'] . $type . "/" . $this->addSettings['twosecond_server']["HTTP_X_GT_LANG"] . $cachePath;
            } else if (isset($this->addSettings['twosecond_server']["REDIRECT_URL"]) && $this->addSettings['twosecond_server']["REDIRECT_URL"] != "/index.php") {
                $cacheFilePath = $this->addSettings['root_cache_path'] . $type . "/" . $this->addSettings['twosecond_server']["REDIRECT_URL"];
            } else if (isset($this->addSettings['twosecond_server']["REQUEST_URI"])) {
                $cacheFilePath = $this->addSettings['root_cache_path'] . $type . "/" . $cachePath;
            }
        } else {
            $cacheFilePath = $this->addSettings['root_cache_path'] . $type . "/" . $cachePath;
        }
        return rtrim($cacheFilePath, '/');
    }

	/**
	 * TwoSecond remove html cache code
	 */
    function twosecond_remove_html_cache_code()
    {
        $htaccessPath = $this->addSettings['document_root'] . "/.htaccess";
        $htaccessContent = $this->twosecond_get_contents($htaccessPath);

        if (is_file($this->addSettings['document_root'] . "/.htaccess") && $this->twosecond_check_is_writable($this->addSettings['document_root'] . "/.htaccess") && strpos($htaccessContent, '# BEGIN BSHTMLCACHE') !== false && strpos($htaccessContent, '# END BSHTMLCACHE') !== false) {
            $htaccess = preg_replace("/#\s?BEGIN\s?BSHTMLCACHE.*?#\s?END\s?BSHTMLCACHE\s*/s", "", $htaccessContent);
            $this->twosecond_put_contents($htaccessPath, $htaccess);
        }
    }
	
	/**
	 * BS insert lbc rule
	 *
	 * @param string $htaccess Htaccess.
	 * @return string
	 */
    function twosecond_insert_lbc_rule($htaccess)
    {
        $data = "\n" . "# BEGIN BSLBC" . "\n" .
            '<FilesMatch "\.(webm|ogg|mp4|ico|pdf|flv|jpg|jpeg|png|gif|webp|js|css|swf|x-html|css|xml|js|woff|woff2|otf|ttf|svg|eot)(\.gz)?$">' . "\n" .
            '<IfModule mod_expires.c>' . "\n" .
            'AddType application/font-woff2 .woff2' . "\n" .
            'AddType application/x-font-opentype .otf' . "\n" .
            'ExpiresActive On' . "\n" .
            'ExpiresDefault A0' . "\n" .
            'ExpiresByType video/webm A10368000' . "\n" .
            'ExpiresByType video/ogg A10368000' . "\n" .
            'ExpiresByType video/mp4 A10368000' . "\n" .
            'ExpiresByType image/webp A10368000' . "\n" .
            'ExpiresByType image/gif A10368000' . "\n" .
            'ExpiresByType image/png A10368000' . "\n" .
            'ExpiresByType image/jpg A10368000' . "\n" .
            'ExpiresByType image/jpeg A10368000' . "\n" .
            'ExpiresByType image/ico A10368000' . "\n" .
            'ExpiresByType image/svg+xml A10368000' . "\n" .
            'ExpiresByType text/css A10368000' . "\n" .
            'ExpiresByType text/javascript A10368000' . "\n" .
            'ExpiresByType application/javascript A10368000' . "\n" .
            'ExpiresByType application/x-javascript A10368000' . "\n" .
            'ExpiresByType application/font-woff2 A10368000' . "\n" .
            'ExpiresByType application/x-font-opentype A10368000' . "\n" .
            'ExpiresByType application/x-font-truetype A10368000' . "\n" .
            '</IfModule>' . "\n" .
            '<IfModule mod_headers.c>' . "\n" .
            'Header set Expires "max-age=A10368000, public"' . "\n" .
            'Header unset ETag' . "\n" .
            'Header set Connection keep-alive' . "\n" .
            'FileETag None' . "\n" .
            '</IfModule>' . "\n" .
            '</FilesMatch>' . "\n" .
            "# END BSLBC" . "\n";

        $htaccess = preg_replace("/#\s?BEGIN\s?BSLBC.*?#\s?END\s?BSLBC/s", "", $htaccess);
        if (!empty($this->htaccessModifyKeys['lbc'])) {
            if ($this->twosecond_check_htaccess_status($data)) {
                $htaccess = $data . $htaccess;
            } else {
                unset($this->settings['lbc']);
                $this->twosecond_update_option('twosecond_option', $this->settings, 'no');
                $this->twosecond_add_error('danger', 'Server error: Unable to apply .htaccess LBC rules to the site.');
            }
        } else {
            $htaccess = $data . $htaccess;
        }
        return $htaccess;
    }

	/**
	 * BS insert gzip rule
	 *
	 * @param string $htaccess Htaccess.
	 * @return string
	 */
    function twosecond_insert_gzip_rule($htaccess)
    {
        $data = "\n" . "# BEGIN BSGzip" . "\n" .
            "<IfModule mod_deflate.c>" . "\n" .
            "AddType x-font/woff .woff" . "\n" .
            "AddType x-font/ttf .ttf" . "\n" .
            "AddOutputFilterByType DEFLATE image/svg+xml" . "\n" .
            "AddOutputFilterByType DEFLATE text/plain" . "\n" .
            "AddOutputFilterByType DEFLATE text/html" . "\n" .
            "AddOutputFilterByType DEFLATE text/xml" . "\n" .
            "AddOutputFilterByType DEFLATE text/css" . "\n" .
            "AddOutputFilterByType DEFLATE text/javascript" . "\n" .
            "AddOutputFilterByType DEFLATE application/xml" . "\n" .
            "AddOutputFilterByType DEFLATE application/xhtml+xml" . "\n" .
            "AddOutputFilterByType DEFLATE application/rss+xml" . "\n" .
            "AddOutputFilterByType DEFLATE application/javascript" . "\n" .
            "AddOutputFilterByType DEFLATE application/x-javascript" . "\n" .
            "AddOutputFilterByType DEFLATE application/x-font-ttf" . "\n" .
            "AddOutputFilterByType DEFLATE x-font/ttf" . "\n" .
            "AddOutputFilterByType DEFLATE application/vnd.ms-fontobject" . "\n" .
            "AddOutputFilterByType DEFLATE font/opentype font/ttf font/eot font/otf" . "\n" .
            "</IfModule>" . "\n";

        $data = $data . "# END BSGzip" . "\n";

        $htaccess = preg_replace("/\s*\#\s?BEGIN\s?BSGzip.*?#\s?END\s?BSGzip\s*/s", "", $htaccess);
        if (!empty($this->htaccessModifyKeys['gzip'])) {
            if ($this->twosecond_check_htaccess_status($data)) {
                $htaccess = $data . $htaccess;
            } else {
                unset($this->settings['gzip']);
                $this->twosecond_update_option('twosecond_option', $this->settings, 'no');
                $this->twosecond_add_error('danger', 'Server error: Unable to apply .htaccess Gzip rules to the site.');
            }
        } else {
            $htaccess = $data . $htaccess;
        }
        return $htaccess;
    }

	/**
	 * BS insert 404 redirect to file
	 *
	 * @param string $htaccess Htaccess.
	 * @return string
	 */
    function twosecond_insert404_redirect_to_file($htaccess)
    {
        $data = "\n". "# BEGIN BS404" . "\n" .
            "<IfModule mod_rewrite.c>" . "\n" .
            "RewriteEngine On" . "\n" .
            "RewriteBase /" . "\n" .
            "RewriteCond %{REQUEST_FILENAME} !-f" . "\n" .
            "RewriteRule (.*)/ts-cache/(css|js|woff|woff2|ttf|otf|eot|svg)/(\d)*(.*)[mob]*\.(css|js|woff|woff2|ttf|otf|eot|svg) $4.$5 [L]" . "\n" .
            "</IfModule>" . "\n";
        $data = $data . "# END BS404" . "\n";
        $htaccess = preg_replace("/\s*\#\s?BEGIN\s?BS404.*?#\s?END\s?BS404\s*/s", "", $htaccess);
        if(method_exists($this, 'twosecond_check_htaccess404_position')){
            return $this->twosecond_check_htaccess404_position($data, $htaccess);
        }
        return $data . $htaccess;
    }

	/**
	 * Get operating systems
	 *
	 * @return array
	 */
    public function twosecond_get_operating_systems()
    {
        $operating_systems  = array(
            'Android',
            'blackberry|\bBB10\b|rim\stablet\sos',
            'PalmOS|avantgo|blazer|elaine|hiptop|palm|plucker|xiino',
            'Symbian|SymbOS|Series60|Series40|SYB-[0-9]+|\bS60\b',
            'Windows\sCE.*(PPC|Smartphone|Mobile|[0-9]{3}x[0-9]{3})|Window\sMobile|Windows\sPhone\s[0-9.]+|WCE;',
            'Windows\sPhone\s10.0|Windows\sPhone\s8.1|Windows\sPhone\s8.0|Windows\sPhone\sOS|XBLWP7|ZuneWP7|Windows\sNT\s6\.[23]\;\sARM\;',
            '\biPhone.*Mobile|\biPod|\biPad',
            'Apple-iPhone7C2',
            'MeeGo',
            'Maemo',
            'J2ME\/|\bMIDP\b|\bCLDC\b', // '|Java/' produces bug #135
            'webOS|hpwOS',
            '\bBada\b',
            'BREW'
        );
        return $operating_systems;
    }

	/**
	 * Get mobile user agents
	 *
	 * @return string
	 */
    public function twosecond_get_mobile_user_agents()
    {
        return implode("|", $this->twosecond_get_mobile_browsers()) . "|" . implode("|", $this->twosecond_get_operating_systems());
    }
	
	/**
	 * Exclude rules
	 *
	 * @return string
	 */
    public function twosecond_exclude_rules()
    {
        $htaccess_page_rules = "";
        if (!empty($this->settings['exclude_url_exclusions_html_cache'])) {
            $rules_json = is_array($this->settings['exclude_url_exclusions_html_cache']) ? $this->settings['exclude_url_exclusions_html_cache'] : array();
            if (count($rules_json) > 0) {
                foreach ($rules_json as $value) {
                    $htaccess_page_rules = $htaccess_page_rules . "RewriteCond %{REQUEST_URI} !" . trim($value) . " [NC]\n";
                }
            }
        }

        return "# Start BS Exclude\n" . $htaccess_page_rules . "# End BS Exclude\n";
    }
	
	/**
	 * Http condition rule
	 *
	 * @return string
	 */
    public function http_condition_rule()
    {
        $http_host = preg_replace("/(http(s?)\:)?\/\/(www\d*\.)?/i", "", trim($this->addSettings['home_url'], "/"));

        if (preg_match("/\//", $http_host)) {
            $http_host = strstr($http_host, '/', true);
        }

        if (preg_match("/www\./", $this->addSettings['home_url'])) {
            $http_host = "www." . $http_host;
        }

        return "RewriteCond %{HTTP_HOST} ^" . $http_host;
    }
	
	/**
	 * Is subdirectory install
	 *
	 * @return bool
	 */
    public function twosecond_is_subdirectory_install()
    {
        if (strlen($this->addSettings['site_url']) > strlen($this->addSettings['home_url'])) {
            return true;
        }
        return false;
    }
	
	/**
	 * Query string rule
	 *
	 * @return string
	 */
    public function twosecond_query_string_rule()
    {
        $enableCachingGetParaRule = isset($this->settings['enable_caching_get_para']) ? 1 : '';
        if (!$enableCachingGetParaRule) {
            return "RewriteCond %{QUERY_STRING} !.+" . "\n";
        } else {
            return "RewriteCond %{QUERY_STRING} ^(.*)$" . "\n";
        }
    }
	
	/**
	 * BS header check
	 *
	 * @return bool
	 */
    public function twosecond_header_check()
    {
        return $this->addSettings['is_admin']
            || $this->twosecond_is_special_content_type()
            || $this->twosecond_is_special_route()
            || (!empty($this->addSettings['twosecond_server']['REQUEST_METHOD']) && $this->addSettings['twosecond_server']['REQUEST_METHOD'] === 'POST')
            || (!empty($this->addSettings['twosecond_server']['REQUEST_METHOD']) && $this->addSettings['twosecond_server']['REQUEST_METHOD'] === 'PUT')
            || (!empty($this->addSettings['twosecond_server']['REQUEST_METHOD']) && $this->addSettings['twosecond_server']['REQUEST_METHOD'] === 'DELETE')
            || $this->twosecond_is_404()
            || $this->twosecond_check_url()
            || $this->twosecond_is_ajax_request();
    }

	/**
	 * Is ajax request
	 *
	 * @return bool
	 */
    function twosecond_is_ajax_request()
    {
        return (!empty($this->addSettings['twosecond_server']['HTTP_X_REQUESTED_WITH']) && strtolower($this->addSettings['twosecond_server']['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false;
    }
	
	/**
	 * Check url
	 *
	 * @return bool
	 */
    function twosecond_check_url()
    {
        if (strpos($this->addSettings['full_url_without_param'], $this->addSettings['site_url']) !== false || strpos($this->addSettings['full_url_without_param'], $this->addSettings['home_url']) !== false || strpos($this->addSettings['full_url_without_param'], $this->addSettings['site_url_array']['scheme'] . '://' . $this->addSettings['site_url_diff']) !== false) {
            return false;
        }
        return true;
    }

	/**
	 * Set current page content type
	 *
	 * @param string $html Html.
	 */
    public function set_current_page_content_type($html)
    {
        $content_type = false;
        if (function_exists("headers_list")) {
            $headers = headers_list();
            foreach ($headers as $header) {
                if (preg_match("/Content-Type\:/i", $header)) {
                    $content_type = preg_replace("/Content-Type\:\s(.+)/i", "$1", $header);
                }
            }
        }

        if (preg_match("/xml/i", $content_type)) {
            $this->current_page_content_type = "xml";
        } else if (preg_match("/json/i", $content_type)) {
            $this->current_page_content_type = "json";
        } else {
            $this->current_page_content_type = "html";
        }
    }

	/**
	 * Check cache with query params
	 *
	 * @return bool
	 */
    function twosecond_check_cache_with_query_params()
    {
        $path = $this->addSettings['full_url'];
        $parsed_url = $this->twosecond_parse_url($path);
        $enableCachingGetPara = !empty($this->settings['enable_caching_get_para']) ? 1 : 0;
        if ($enableCachingGetPara == 0 && !empty($parsed_url['query'])) {
            return true;
        }
        return false;
    }

	/**
	 * Check is page excluded
	 *
	 * @return bool
	 */
    public function twosecond_check_is_page_excluded()
    {
        $uri_exclusions = isset($this->settings['exclude_url_exclusions_html_cache']) && is_array($this->settings['exclude_url_exclusions_html_cache']) ? $this->settings['exclude_url_exclusions_html_cache'] : array();
        $uri_exclusions = array_merge($uri_exclusions, array('login', '/admin', '/wp-admin', '/wp-login', 'json', 'sitemap'));
        if (!empty($uri_exclusions)) {
            foreach ($uri_exclusions as $element) {
                if (!empty($element) && strpos($this->addSettings['full_url'], $element) != false) {
                    return true;
                }
            }
        }
        return false;
    }

	/**
	 * Check html
	 *
	 * @param string $buffer Buffer.
	 * @return bool
	 */
    public function twosecond_check_html($buffer)
    {
        if (!$this->twosecond_check_html_content_type()) {
            return false;
        }
        if (preg_match('/<html[^\>]*>/si', $buffer) && preg_match('/<body[^\>]*>/si', $buffer)) {
            return false;
        }
        return true;
    }

	/**
	 * BS no html cache
	 *
	 * @param string $html Html.
	 * @return bool
	 */
    public function twosecond_no_html_cache($html)
    {
        $this->set_current_page_content_type($html);
        if ($this->twosecond_is_xml_page()) {
            return false;
        }elseif ($this->twosecond_check_firewall()) {
            return "<!-- DONOTCACHEPAGE is defined as TRUE -->";
        } elseif ($this->twosecond_check_is_page_excluded()) {
            return true;
        } elseif ($this->twosecond_header_check()) {
            return true;
        } elseif ($this->twosecond_is_json_page() && (!defined('TWOSECOND_CACHE_JSON') || (defined('TWOSECOND_CACHE_JSON') && TWOSECOND_CACHE_JSON !== true))) {
            return true;
        } elseif ($this->twosecond_is_feed_page()) {
            return true;
        } else if (preg_match("/Mediapartners-Google|Google\sWireless\sTranscoder/i", $this->addSettings['http_user_agent'])) {
            return true;
        } elseif ($this->twosecond_check_cache_with_query_params()) {
            return '<!-- not cached due to query parameter -->';
        } else if (empty($this->settings['enable_loggedin_user_caching']) && ($this->addSettings['twosecond_user_logged_in'] || $this->twosecond_check_commenter())) {
            return '<!--User logged In -->';
        } else if ($this->twosecond_check_password_protected($html)) {
            return '<!-- Password protected content has been detected -->';
        } else if ($this->twosecond_check_captcha($html)) {
            return '<!-- This page was not cached because captcha is present -->';
        } else if ($this->twosecond_check_error_page($html)) {
            return true;
        } else if ($this->twosecond_ignored()) {
            return true;
        } else if (isset($this->addSettings['twosecond_get']["preview"])) {
            return '<!-- not cached -->';
        } else if ($this->twosecond_check_html($html)) {
            return '<!-- html is corrupted -->';
        } else if ((function_exists("http_response_code")) && (http_response_code() == 301 || http_response_code() == 302)) {
            return '<!-- invalid reponse code ' . http_response_code() . ' -->';
        } else {
            return false;
        }
        return false;
    }

	/**
	 * BS get resource url
	 *
	 * @param string $css Css.
	 * @param bool $enable_cdn Enable cdn.
	 * @param int $excluded Excluded.
	 * @param string $type Type.
	 * @return string
	 */
    function twosecond_get_resource_url($css, $enable_cdn, $excluded = 0, $type = 'image')
    {
        $css = trim($css);
        $cssNew = $css;
        
        // If it's already an absolute URL, don't prepend anything unless we're doing CDN replacement
        $is_absolute = (strpos($css, 'http://') === 0 || strpos($css, 'https://') === 0 || strpos($css, '//') === 0);
        
        if ($enable_cdn && !$this->twosecond_check_excluded_path($css, $this->addSettings[$type.'_exclude_cdn_path'] ?? [])) {
            if ($is_absolute) {
                $cssNew = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings[$type.'_url_array']['host'], $css);
            } else {
                $base = ($excluded ? $this->addSettings['site_url'] : $this->addSettings['cache_url']);
                $cssNew = rtrim($base, '/') . '/' . ltrim($css, '/');
                $cssNew = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings[$type.'_url_array']['host'], $cssNew);
            }
        } elseif (!$is_absolute && !$this->twosecond_is_external($css, [], $type)) {
            $base = ($excluded ? $this->addSettings['site_url'] : $this->addSettings['cache_url']);
            $cssNew = rtrim($base, '/') . '/' . ltrim($css, '/');
        }
        
        // Final safety: if we accidentally doubled the domain (even with space or %20), fix it
        $cssNew = preg_replace('/(https?:\/\/[^\/]+\/?)(\s|%20)+\1/i', '$1', $cssNew);
        // Also handle the case where it's domain + absolute URL
        $cssNew = preg_replace('/(https?:\/\/[^\/]+\/?)(\s|%20)+https?:\/\//i', 'https://', $cssNew);
        // Catch direct concatenation without slash if not caught by first regex
        if (preg_match('/(https?:\/\/[^\/]+)\1/i', $cssNew, $matches)) {
            $cssNew = str_replace($matches[1].$matches[1], $matches[1], $cssNew);
        }

        return $cssNew;
    }

	/**
	 * BS preload css path
	 *
	 * @param string $url Url.
	 * @return string
	 */
    function twosecond_preload_css_path($url = '')
    {
        $orgurl = $url = empty($url) ? $this->addSettings['full_url_without_param'] : $url;
        if (!empty($this->addSettings['preload_css_url'][$url])) {
            return $this->addSettings['preload_css_url'][$url];
        }
        $url = $this->twosecond_preload_css_customize_path($url);
        $url = $this->twosecond_customize_critical_css_url($url, $orgurl);
        $full_url = str_replace($this->addSettings['secure'], '', rtrim($url, '/'));
        $path = urldecode($this->twosecond_get_critical_cache_path($full_url));
        $this->addSettings['preload_css_url'][$orgurl] = $path;
        return $path;
    }

	/**
	 * BS get cache url
	 *
	 * @param string $path Path.
	 * @return string
	 */
    function twosecond_get_cache_url($path = '')
    {
        $cacheUrl = $this->addSettings['cache_url'] . $this->addSettings['twosecond_current_blog'] . (!empty($path) ? '/' . ltrim($path, '/') : '');
        return $cacheUrl;
    }

	/**
	 * BS get cache path
	 *
	 * @param string $path Path.
	 * @return string
	 */
    function twosecond_get_cache_path($path = '')
    {
        $cache_path = $this->addSettings['root_cache_path'] . $this->addSettings['twosecond_current_blog'] . (!empty($path) ? '/' . $path : '');
        $this->twosecond_create_folder($cache_path);
        return $cache_path;
    }

	/**
	 * BS get critical cache path
	 *
	 * @param string $path Path.
	 * @return string
	 */
    function twosecond_get_critical_cache_path($path = '')
    {
        $cache_path = $this->addSettings['critical_css_path'] . $this->addSettings['twosecond_current_blog'] . (!empty($path) ? '/' . $path : '');
        $this->twosecond_create_folder($cache_path);
        return $cache_path;
    }

	/**
	 * Get critical css filename
	 *
	 * @param array $combined_css_files Combined css files.
	 * @return string
	 */
    function twosecond_get_critical_css_filename($combined_css_files)
    {
        $file_name = count($combined_css_files);
        $main_css_file_name = md5($file_name) . $this->addSettings['css_ext'];
        $main_css_file_name = $this->twosecond_customize_critical_css_filename($main_css_file_name, $combined_css_files);
        return $main_css_file_name;
    }

	/**
	 * TwoSecond get data advanced cache file
	 *
	 * @return string
	 */
    function twosecond_get_data_advanced_cache_file()
    {
        $scheme = $this->addSettings['site_url_array']['scheme'] === "https" ? "on" : "off";
        $host = $this->addSettings['site_url_array']['host'] ?? "";
        $cachePath =  $this->addSettings['root_cache_path'] . '/html{{HASH}}' . '/' . $scheme . '-' . $host;
        $data = '<?php
        /**
         * Advanced Cache file
         * Added By TwoSecond-' . TWOSECOND_VERSION . '
         
         */
		$expiryTime = ' . ($this->settings['html_caching_expiry_time'] ? $this->settings['html_caching_expiry_time'] : 3600) . ';
		$loggedinCaching = ' . (!empty($this->settings['enable_loggedin_user_caching']) ? 1 : 0) . ';
		$enableCachingGetPara = ' . (!empty($this->settings['enable_caching_get_para']) ? 1 : 0) . ';
		$seprateMobileCaching  = 1;
		if ( ! defined( "ABSPATH" ) ) {
            exit;
        }
		if (!empty($_SERVER["QUERY_STRING"])) {
			if (strpos($_SERVER["QUERY_STRING"],"orgurl") !== false) {
				return;
			} 
		}
		if (!empty($_POST)) {
			return;
		}
		if (twosecond_is_ajax_request()) {
			return;
		}
		$queryPara = 0;
        $path = getCurrentUrl();
        $parsed_url = parse_url($path);
		$parsed_url[\'path\'] = !empty($parsed_url[\'path\']) ? $parsed_url[\'path\'] : \'\';
        $ext = getExt($parsed_url);
        $filename = "/index.html";
		if($ext == \'xml\'){
			header("Content-Type: application/xml");
            $filename = "/index.xml";
		}
        
		$queryPara = 0;
		if (!empty($parsed_url[\'query\'])) {
			$queryPara = 1;
			$query = $parsed_url[\'query\'];
		} 
		$hash = \'\';
		$userLoggedin = 0;
		 foreach ($_COOKIE as $name => $value) {
            if(preg_match("/wordpress_logged_in/i", $name)){
                $userLoggedin = 1;
                $parts = explode(\'|\', $value);
				$hash = ($parts[0] ?? \'\');
                break;
            }
           
        }
        // Define the cache directory
        $path1 = trim($parsed_url[\'path\'],\'/\');
		if (!$enableCachingGetPara &&  $queryPara == 1) {
			return;
		}elseif(!empty($query)){
			$path1 .= \'/\'.$query;
		}
        $cachePath = "' . $cachePath . '";
        if($loggedinCaching == 1 && !empty($hash)){
           $cachePath = str_replace(\'{{HASH}}\', \'/\'.$hash, $cachePath);
        }else{
           $cachePath = str_replace(\'{{HASH}}\', \'\', $cachePath);
        }   
        $path1 = urldecode($path1);
		if($seprateMobileCaching){
			$cacheDirMobile = "$cachePath/$path1/bsmob";
			$cacheDirDesktop = "$cachePath/$path1";
			$userAgent = !empty($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";
			$isMobile = twosecond_is_mobile_device($userAgent);
			$type = $isMobile ? "/bsmob/" : "";
			$cacheDir = $isMobile ? $cacheDirMobile : $cacheDirDesktop;
		}else{
			$cacheDir = "$cachePath/".$path1;
		}	
		if ($loggedinCaching == 0 &&  $userLoggedin == 1) {
			return;
		}
		// Define the cache filename
        $cacheFile = $cacheDir . $filename;
        // Check if the cache file exists and is not expired
        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $expiryTime) { // Adjust the expiration time as needed (3600 seconds = 1 hour)
            // Serve the cached HTML
            readfile($cacheFile);
            exit;
        }else{
			@unlink($cacheFile);
		}
		function twosecond_is_ajax_request() {
			return isset($_SERVER[\'HTTP_X_REQUESTED_WITH\']) && strtolower($_SERVER[\'HTTP_X_REQUESTED_WITH\']) === \'xmlhttprequest\';
		}
        function twosecond_is_mobile_device($userAgent) {
			// Regular expression to identify common mobile user agents
				$pattern = "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i";
				return preg_match($pattern, $userAgent);
		}
        function getExt($path, $mode = \'last\') {
			$base = basename($path[\'path\']);
			if ($mode === \'full\') {
				// everything after the first dot in the basename
				return preg_match(\'/^[^.]+\.(.+)$/\', $base, $m) ? strtolower($m[1]) : \'\';
			}
			// default: last segment only (css, jpg, gz, etc.)
			return strtolower(pathinfo($base, PATHINFO_EXTENSION));
		}
        function getCurrentUrl() {
			$protocol = (!empty($_SERVER[\'HTTPS\']) && $_SERVER[\'HTTPS\'] !== \'off\'
						 || $_SERVER[\'SERVER_PORT\'] == 443) ? "https://" : "http://";
			$host     = $_SERVER[\'HTTP_HOST\'];
			$uri      = $_SERVER[\'REQUEST_URI\'];
		
			return $protocol . $host . $uri;
		}';
        return $data;
    }

	/**
	 * BS check license key
	 */
    function twosecond_check_license_key (){
		$res= $this->twosecond_validate_license_key();
		$response = !empty($res) ? json_decode($res) : array();
		if(!empty($response[0]) && $response[0] == 'fail' && strpos($response[1],'could not verify-1') !== false){
			$this->twosecond_update_option('twosecond_key_log',$this->twosecond_json_encode($response),'no');
			$settings = $this->twosecond_get_option( 'twosecond_option', true );
			$settings['is_activated'] = '';
			$this->twosecond_update_option( 'twosecond_option', $settings,'no' );	
		}
	}

	/**
	 * Get site urls
	 *
	 * @return array
	 */
	function twosecond_get_site_urls(){
        $link_urls = [];
		$url = $this->addSettings['site_url'];
        $key = $this->addSettings['mainLicenseKey'] ?? '';
		if (!empty($url) && !empty($key)) {
            $logData = [
                'time' => gmdate('Y-m-d H:i:s'),
                'licenseKey' => $key,
            ];
            $this->twosecond_create_file($this->addSettings['cache_path'] . '/sitemapcrawl.log', json_encode($logData) . PHP_EOL, true);
        	$url = htmlspecialchars($this->twosecond_strip_tags(trim($url)), ENT_QUOTES, 'UTF-8');
			$apiUrl = $this->addSettings['api_url'] . '/optimize/css/fetch-urls.php';
            $response = $this->twosecond_remote_post($apiUrl, ['urls' => [$url], 'key' => $key],true);
            if (!$response) {
				return $link_urls;
			} else {
				$data = json_decode($response, true);
				if (isset($data['urls']) && is_array($data['urls'])) {
                    $link_urls = $data['urls'];
				}
				return $link_urls;
			}
		} else {
			return $link_urls;
		}
	}

	/**
	 * Check enable cdn
	 *
	 * @param string $type Type.
	 * @return int
	 */
    function twosecond_check_enable_cdn($type = '')
    {
        $enableCdn = 0;
        $cdnUrl = [
            'image' => 'image_cdn_url',
            'js'    => 'js_cdn_url',
            'font'  => 'font_cdn_url',
            'css'   => 'css_cdn_url',
            'audio' => 'audio_cdn_url',
            'video' => 'video_cdn_url'
        ];
        if (array_key_exists($type, $cdnUrl) &&
            isset($this->addSettings['site_url'], $this->addSettings[$cdnUrl[$type]]) &&
            $this->addSettings['site_url'] != $this->addSettings[$cdnUrl[$type]]) {
            $enableCdn = 1;
        }
        return $enableCdn;
    }

	/**
	 * BS check excluded path
	 *
	 * @param string $string String.
	 * @param array $pathArray Path array.
	 * @return bool
	 */
    function twosecond_check_excluded_path($string, $pathArray) {
        return !empty(array_filter($pathArray, function($word) use ($string) {
            return strpos($string, $word) !== false;
        }));
    }

	/**
	 * Check htaccess status
	 *
	 * @param string $htaccess Htaccess.
	 * @return bool
	 */
    function twosecond_check_htaccess_status($htaccess){
        $tempFolder = $this->addSettings['root_cache_path'].'/temp/';
        $tempHtaccessPath = $tempFolder.'.htaccess';
        $tempFilePath = $tempFolder.'index.php';
        $tempUrl = $this->addSettings['cache_url'].'/temp/index.php';
        $this->twosecond_create_file($tempHtaccessPath, $htaccess);
        $this->twosecond_create_file($tempFilePath, '<?php echo "twoSecond"; ?>');

        // Prefer WordPress HTTP API over PHP URL wrapper functions for remote checks.
        $code = 0;
        if ( function_exists( 'wp_remote_head' ) ) {
            $response = \wp_remote_head(
                $tempUrl,
                array(
                    'timeout'     => 10,
                    'redirection' => 0,
                )
            );
        } elseif ( function_exists( 'wp_remote_request' ) ) {
            $response = \wp_remote_request(
                $tempUrl,
                array(
                    'method'      => 'HEAD',
                    'timeout'     => 10,
                    'redirection' => 0,
                )
            );
        } else {
            $response = new \WP_Error( 'twosecond_no_http_api', 'WordPress HTTP API is not available.' );
        }

        if ( \is_wp_error( $response ) ) {
            $code = 500;
        } else {
            $code = (int) \wp_remote_retrieve_response_code( $response );
        }

        $this->twosecond_rmdir($tempFolder);
        if((int)$code >= 500){
            return false;
        }
        return true;
    }

	


	/** @var int Max POST body size (bytes) for critical CSS multi-enqueue batches. */
	const TWOSECOND_CRITICAL_MULTI_MAX_BODY_BYTES = 20971520;

	/**
	 * Collect critical CSS token jobs for one page token (same shape as used for multi-enqueue).
	 *
	 * @param string $token    MD5 page token.
	 * @param bool   $internal If true, load mobile and desktop; else current device only.
	 * @return array<int, array{device_type:string,first:array,critical_url_path:string}>
	 */
	function twosecond_collect_critical_css_jobs_for_token( $token, $internal = false ) {
		$criticalJobs = array();
		if ( empty( $token ) ) {
			return $criticalJobs;
		}
		$mobile  = $this->twosecond_is_mobile_device() ? 'mobile' : 'desktop';
		$devices = $internal ? array( 'mobile', 'desktop' ) : array( $mobile );
		foreach ( $devices as $device ) {
			$criticalUrlPath = $this->addSettings['critical_css_path'] . '/tokens/' . $token . '-' . $device;
			if ( ! file_exists( $criticalUrlPath ) ) {
				continue;
			}
			$content = file_get_contents( $criticalUrlPath );
			if ( empty( $content ) ) {
				continue;
			}
			$urlsArray = json_decode( $content, true );
			if ( empty( $urlsArray['first'] ) || ! is_array( $urlsArray['first'] ) ) {
				continue;
			}
			$criticalJobs[] = array(
				'device_type'       => $device,
				'first'             => $urlsArray['first'],
				'critical_url_path' => $criticalUrlPath,
			);
		}
		return $criticalJobs;
	}

	/**
	 * Build multi-enqueue POST body from critical job rows.
	 *
	 * @param array $criticalJobs Job list from twosecond_collect_critical_css_jobs_for_token.
	 * @return array<string, mixed>
	 */
	function twosecond_build_critical_multi_payload_from_jobs( $criticalJobs ) {
		$firstJob = $criticalJobs[0]['first'];
		$payload  = array(
			'key'            => $firstJob['key'] ?? $this->addSettings['mainLicenseKey'],
			'base64_encoded' => true,
			'_w3nonce'       => $firstJob['_twosecond_nonce'] ?? '',
			'data'           => array(),
		);
		foreach ( $criticalJobs as $job ) {
			$f                  = $job['first'];
			$payload['data'][] = array(
				'url'         => $f['url'] ?? '',
				'filename'    => $f['filename'] ?? '',
				'css_url'     => $f['css_url'] ?? '',
				'path'        => $f['path'] ?? '',
				'html'        => $f['html'] ?? '',
				'device_type' => $job['device_type'],
			);
		}
		return $payload;
	}

	/**
	 * POST one critical CSS multi-enqueue batch and merge parse status into remark string.
	 *
	 * @param array  $criticalJobs Same shape as twosecond_collect_critical_css_jobs_for_token output.
	 * @param string $message      Existing remarks.
	 * @return array{0:int,1:string} critStatus, message
	 */
	function twosecond_send_one_critical_multi_batch( $criticalJobs, $message ) {
		if ( empty( $criticalJobs ) ) {
			return array( 200, $message );
		}
		$multiPayload = $this->twosecond_build_critical_multi_payload_from_jobs( $criticalJobs );
		$multiApiUrl  = $this->addSettings['api_url'] . '/optimize/css/multi-enqueue.php';
		$response     = $this->twosecond_remote_request_blocking( $multiApiUrl, $multiPayload, 'POST', false, true );
		$pathsOrder   = array_column( $criticalJobs, 'critical_url_path' );
		if ( ! empty( $response ) ) {
			return $this->twosecond_check_critical_css_multi_response( $response, $message, $pathsOrder );
		}
		return array( 201, $message . ' Critical CSS optimization in progress - Enqueue pending,' );
	}

	/**
	 * Send critical CSS jobs in batches: group by _twosecond_nonce, each group chunked by JSON body size.
	 *
	 * @param array $criticalJobs Flat list from one or many tokens.
	 * @param string $message     Remarks prefix (e.g. image optimization).
	 * @param int $maxBodyBytes   Default 20 MiB.
	 * @return array{0:int,1:string} Aggregated critStatus, message
	 */
	function twosecond_send_critical_css_jobs_chunked( $criticalJobs, $message = '', $maxBodyBytes = null ) {
		if ( empty( $criticalJobs ) ) {
			return array( 200, $message );
		}
		if ( null === $maxBodyBytes || $maxBodyBytes <= 0 ) {
			$maxBodyBytes = self::TWOSECOND_CRITICAL_MULTI_MAX_BODY_BYTES;
		}
		$byNonce = array();
		foreach ( $criticalJobs as $job ) {
			$n                = (string) ( $job['first']['_twosecond_nonce'] ?? '' );
			$byNonce[ $n ][] = $job;
		}
		$overallCrit = 200;
		foreach ( $byNonce as $group ) {
			$nJobs = count( $group );
			$i     = 0;
			while ( $i < $nJobs ) {
				$chunk        = array();
				$payloadOne   = $this->twosecond_build_critical_multi_payload_from_jobs( array( $group[ $i ] ) );
				$oneJson      = $this->twosecond_json_encode( $payloadOne );
				$oneLen       = is_string( $oneJson ) ? strlen( $oneJson ) : 0;
				if ( $oneLen > $maxBodyBytes ) {
					list( $critStatus, $message ) = $this->twosecond_send_one_critical_multi_batch( array( $group[ $i ] ), $message );
					if ( 201 === (int) $critStatus ) {
						$overallCrit = 201;
					}
					$i++;
					continue;
				}
				while ( $i < $nJobs ) {
					$trial  = array_merge( $chunk, array( $group[ $i ] ) );
					$encode = $this->twosecond_json_encode( $this->twosecond_build_critical_multi_payload_from_jobs( $trial ) );
					$len    = is_string( $encode ) ? strlen( $encode ) : 0;
					if ( $len > $maxBodyBytes && count( $chunk ) > 0 ) {
						break;
					}
					if ( $len > $maxBodyBytes && count( $chunk ) === 0 ) {
						$chunk = array( $group[ $i ] );
						$i++;
						break;
					}
					$chunk = $trial;
					$i++;
				}
				if ( ! empty( $chunk ) ) {
					list( $critStatus, $message ) = $this->twosecond_send_one_critical_multi_batch( $chunk, $message );
					if ( 201 === (int) $critStatus ) {
						$overallCrit = 201;
					}
				}
			}
		}
		return array( $overallCrit, $message );
	}

	/**
	 * BS save data with ajax
	 *
	 * @param string $token Token.
	 */
    function twosecond_save_data_with_ajax($token = '', $return = true, $internal = false){
        if(empty($token)){
            $token = $this->addSettings['twosecond_get']['token'] ?? '';
        }
        if(empty($token)) return;
        $message = '';
        $imgToken = $this->addSettings['webp_path'].'/'.$token;
        $status = 200;
        if(file_exists($imgToken)){
            $this->twosecond_enque_image_optimization_task($token);
            if(is_file($imgToken)){
                $status = 201;
                $message .= " Image optimization in progress,";
            }
        }
        $criticalJobs = $this->twosecond_collect_critical_css_jobs_for_token( $token, $internal );
        if ( count( $criticalJobs ) > 0 ) {
            list( $critStatus, $message ) = $this->twosecond_send_critical_css_jobs_chunked( $criticalJobs, $message, self::TWOSECOND_CRITICAL_MULTI_MAX_BODY_BYTES );
            $status                        = ( (int) $status === 201 || (int) $critStatus === 201 ) ? 201 : (int) $critStatus;
        }
        
        if($return){
            http_response_code($status);
            $this->twosecond_response(array(
                'status' => $status,
                'message' => $status === 200 ? 'Processing completed' : 'Processing in progress',
                'remarks' => rtrim(trim($message), ',').'.'
            ), $status);
        }
    }

    /**
     * Write critical CSS payload to disk, append preload markers, purge page caches.
     *
     * @param array  $data            API row (w3_css, path, filename, url, optional image/element lists).
     * @param string $criticalUrlPath Token file path to remove on success.
     */
    function twosecond_apply_critical_css_success_from_data( $data, $criticalUrlPath = '' ) {
        if ( empty( $data['w3_css'] ) || empty( $data['path'] ) || empty( $data['filename'] ) ) {
            return;
        }
        $criticalFilePath = $data['path'] . '/' . $data['filename'];
        $this->twosecond_create_file( $criticalFilePath, $data['w3_css'] );
        if ( empty( $criticalFilePath ) || ! file_exists( $criticalFilePath ) ) {
            return;
        }
        $css = $data['w3_css'];
        $tsimages            = $data['tsimages'] ?? $data['bsimages'] ?? $data['w3images'] ?? null;
        $twosecond_elements = $data['twosecond_elements'] ?? $data['w3elements'] ?? null;
        if ( ! empty( $tsimages ) && is_array( $tsimages ) && count( $tsimages ) ) {
            $css .= '/* <tsimages>' . implode( ',', $tsimages ) . '</tsimages> */';
        }
        if ( ! empty( $twosecond_elements ) && is_array( $twosecond_elements ) && count( $twosecond_elements ) ) {
            $css .= '/* <twosecond_elements>' . implode( ',', $twosecond_elements ) . '</twosecond_elements> */';
        }
        $css = str_replace( $this->addSettings['site_url'], '', $css );
        $this->twosecond_create_file( $criticalFilePath, $css );
        if ( ! empty( $data['url'] ) ) {
            [ $mobCacheFile, $desktopCacheFile ] = $this->twosecond_cache_file_path( $data['url'] );
            if ( file_exists( $mobCacheFile ) && strpos( $criticalFilePath, 'mob' ) !== false ) {
                $this->twosecond_delete_file( $mobCacheFile );
            }
            if ( file_exists( $desktopCacheFile ) && strpos( $criticalFilePath, 'mob' ) === false ) {
                $this->twosecond_delete_file( $desktopCacheFile );
            }
        }
        if ( ! empty( $criticalUrlPath ) ) {
            $this->twosecond_delete_file( $criticalUrlPath );
        }
    }

    /**
     * Handle one critical CSS API row (single or multi response).
     *
     * @param array  $item            Decoded row.
     * @param int    $status          Output status for this row (200 completed, 201 in progress).
     * @param string $message         Appended remark fragment.
     * @param string $criticalUrlPath Matching token file path.
     */
    function twosecond_process_critical_css_response_item( $item, &$status, &$message, $criticalUrlPath = '' ) {
        if ( ! is_array( $item ) ) {
            $status = 201;
            $message .= ' Critical CSS optimization in progress,';
            return;
        }
        if ( ! empty( $item['processing'] ) && $item['processing'] === 'process-enqueued' ) {
            $status = 201;
            $message .= ' Critical CSS optimization in progress - Enqueued,';
            return;
        }
        if ( ! empty( $item['urls'] ) && is_array( $item['urls'] ) && ! empty( $item['auth'] ) ) {
            $this->twosecond_save_assets_on_server( $item['urls'], $item['auth'] );
            $status = 201;
            $message .= ' Critical CSS optimization in progress,';
            return;
        }
        if ( ! empty( $item['w3_css'] ) && ! empty( $item['path'] ) && ! empty( $item['filename'] ) ) {
            $status = 200;
            $message .= 'Critical CSS optimization completed';
            $this->twosecond_apply_critical_css_success_from_data( $item, $criticalUrlPath );
            return;
        }
        $status = 201;
        $message .= ' Critical CSS optimization in progress,';
    }

    /**
     * Parse multi-enqueue (or wrapped) critical CSS JSON response.
     *
     * @param string $response          Raw body.
     * @param string $message           Existing remarks (e.g. image optimization).
     * @param array  $criticalUrlPaths  Token paths in the same order as request `data` rows.
     * @return array {0} int status, {1} string message
     */
    function twosecond_check_critical_css_multi_response( $response, $message, $criticalUrlPaths = array() ) {
        $data = json_decode( $response, true );
        if ( $data && isset( $data['data'] ) && is_array( $data['data'] ) && count( $data['data'] ) > 0 ) {
            $overallStatus = 200;
            foreach ( $data['data'] as $idx => $item ) {
                $rowStatus = 200;
                $rowMsg    = '';
                $tokenPath = $criticalUrlPaths[ $idx ] ?? '';
                $this->twosecond_process_critical_css_response_item( $item, $rowStatus, $rowMsg, $tokenPath );
                $message .= $rowMsg;
                if ( 201 === (int) $rowStatus ) {
                    $overallStatus = 201;
                }
            }
            return array( $overallStatus, $message );
        }
        $firstPath = $criticalUrlPaths[0] ?? '';
        return $this->twosecond_check_critical_css_response( $response, $message, array(), '', $firstPath );
    }

    function twosecond_check_critical_css_response($response,$message='',$options=[],$twoSecondApiUrl='',$criticalUrlPath=''){
        $data = json_decode($response, true);
        if($data && !empty($data['processing']) && $data['processing'] == 'process-enqueued'){
            $status = 201;
            $message .= " Critical CSS optimization in progress - Enqueued,";
        } elseif($data && !empty($data['urls']) && is_array($data['urls']) && !empty($data['auth'])){
            $this->twosecond_save_assets_on_server($data['urls'], $data['auth']);
            $status = 201;
            $message .= " Critical CSS optimization in progress,";
        } elseif($data && !empty($data['w3_css']) && !empty($data['path']) && !empty($data['filename'])){
            $status = 200;
            $message .= "Critical CSS optimization completed";
            $this->twosecond_apply_critical_css_success_from_data($data, $criticalUrlPath);
        } else {
            $status = 201;
            $message .= " Critical CSS optimization in progress,";
        }
        return array($status,$message);
    }
	/**
	 * Delete token file if it's older than specified age
	 *
	 * Checks if a token file exists and deletes it if it's older than
	 * the specified age threshold (default 10 minutes).
	 *
	 * @param string $filePath Path to the token file
	 * @param int $maxAgeSeconds Maximum age in seconds before deletion (default: 600 = 10 minutes)
	 * @return bool True if file was deleted, false otherwise
	 */
    function twosecond_delete_old_token_file($filePath, $maxAgeSeconds = 600)
    {
        if(!file_exists($filePath)){
            return false;
        }
        
        $fileTime = filemtime($filePath);
        $currentTime = time();
        $ageInSeconds = $currentTime - $fileTime;
        
        if($ageInSeconds > $maxAgeSeconds){
            \wp_delete_file($filePath);
            return true;
        }
        
        return false;
    }

	/**
	 * BS enque task on server
	 */
    function twosecond_enque_task_on_server()
    {
        $webpToken = $this->addSettings['webp_path'].'/'.$this->pageToken;
        
        // Delete token file if it's more than 10 minutes old
        $this->twosecond_delete_old_token_file($webpToken);
        
        // Force update the token file if we have images to optimize
        if(count($this->webpEnqueImageUrls) > 0 || count($this->addSettings['twosecond_responsive_img_urls']) > 0){
            $options = [
                'key' => $this->addSettings['mainLicenseKey'],
                'type' => 'webp',
                'token' => $this->pageToken,
                'host' => $this->addSettings['site_url_array']['host'],
                'urls' => $this->twosecond_filter_valid_images($this->webpEnqueImageUrls),
                'resize_urls' => $this->twosecond_filter_thumbnail_images($this->addSettings['twosecond_responsive_img_urls'])
            ];
            if(!empty($options['urls']) || !empty($options['resize_urls'])){
                error_log("TwoSecond: Writing WebP Token file with " . count($options['urls']) . " images.");
                $this->twosecond_create_file($webpToken,$this->twosecond_json_encode($options));
            }
        }

        if(!empty($this->criticalData)){
            $url = $this->criticalData['url'];
            $filename = $this->criticalData['data'][0];
            $isMobile = strpos($filename, 'mob.css') !== false ? 'mobile' : 'desktop';
            $css_path = $this->criticalData['data'][2]; 
            $txtFilePath = $this->addSettings['critical_css_path'] . '/tokens/' . $this->pageToken . '-' . $isMobile;
            $this->twosecond_delete_old_token_file($txtFilePath);
            if(file_exists($txtFilePath)){
                return true;
            }
            $nonce = $this->twosecond_create_secure_key('create_critical_css');
            if ($this->twosecond_check_enable_cdn('css')) {
                $css_urls = $this->addSettings['site_url'] . ',' . $this->addSettings['css_cdn_url'];
            } else {
                $css_urls = $this->addSettings['site_url'];
            }
            $responseCssPath = $this->addSettings['critical_css_path'] . '/responses';
            $this->twosecond_create_folder($responseCssPath);

            $optionsData = $options = array(
                'url' => $url,
                'key' => $this->addSettings['mainLicenseKey'],
                '_twosecond_nonce' => $nonce,
                'filename' => $filename,
                'css_url' => $css_urls,
                'path' => $css_path,
                'html' => base64_encode($this->html),
                'base64_encoded' => true
            );

            $this->twosecond_create_file($txtFilePath, $this->twosecond_json_encode(['first' => $optionsData]));
        }
        return true;
    }

	/**
	 * BS remote request non blocking
	 *
	 * @param string $url Url.
	 * @param array $options Options.
	 * @param string $method Method.
	 * @param bool $mobile Mobile.
	 * @param bool $json Json.
	 */
    function twosecond_remote_request_non_blocking($url, $options = [], $method = 'POST', $mobile = false, $json = false) {
        if (strtoupper($method) === 'GET') {
            return $this->twosecond_remote_get($url, $options, $mobile, false, 'header');
        } elseif (strtoupper($method) === 'POST') {
            return $this->twosecond_remote_post($url, $options, $json, $mobile, false, 'header');
        }
        return false;
    }

    /**
	 * BS remote request blocking
	 *
	 * @param string $url Url.
	 * @param array $options Options.
	 * @param string $method Method.
	 * @param bool $mobile Mobile.
	 * @param bool $json Json.
	 */
    function twosecond_remote_request_blocking($url, $options = [], $method = 'POST', $mobile = false, $json = false) {
        if (strtoupper($method) === 'GET') {
            return $this->twosecond_remote_get($url, $options, $mobile, true, 'body');
        } elseif (strtoupper($method) === 'POST') {
            return $this->twosecond_remote_post($url, $options, $json, $mobile, true, 'body');
        }
        return false;
    }
	
	/**
	 * Convert webp in critical
	 *
	 * @param string $string String.
	 * @param string $url Url.
	 * @return string
	 */
    function twosecond_convert_webp_in_critical($string)
    {
        $url = $this->addSettings['full_url_without_param'];
        $responsive = $this->addSettings['is_mobile'] && $this->settings['resp_bg_img'];
        $matches = $this->twosecond_get_tags_data($string, 'url(', ')');
        $url_parent_url = $this->twosecond_get_path_from_url($url);
        if ($this->addSettings['is_multisite_sub_domain']) {
            $url_parent_url = str_replace($this->addSettings['site_url'], $this->addSettings['network_site_url'], $url_parent_url);
        }
        foreach ($matches as $match) {
            if (strpos($match, '{{') !== false || strpos($match, 'data:') !== false || strpos($match, 'chrome-extension:') !== false) {
                continue;
            }
            $org_match = $match;
            $match1 = $this->twosecond_replace_url_brackets($match);
            $match1 = trim($match1);
            if (strpos($match1, '//') > 7) {
                $match1 = substr($match1, 0, 7) . str_replace('//', '/', substr($match1, 7));
            }
            if (empty($match1) || strpos(substr($match1, 0, 1), '#') !== false) {
                continue;
            }
            if ($this->addSettings['is_multisite_sub_domain'] && strpos($match1, $this->addSettings['site_url']) != false) {
                $match1 = str_replace($this->addSettings['site_url'], $this->addSettings['network_site_url'], $match1);
            }
            if (strpos($match, 'cdnjs.cloudflare.com') !== false) {
                continue;
            }
            if (strpos($match, 'fonts.gstatic.com') !== false) {
                continue;
            }
            if (strpos($match, 'fonts.googleapis.com') !== false) {
                continue;
            }
            $match1 = str_replace($this->addSettings['css_cdn_url'], $this->addSettings['site_url'], $match1);
            if ($this->twosecond_is_external($match1, [], 'css')) {
                continue;
            }
            $match_arr = $this->twosecond_custom_parse_url($match1);
            $img_arr = explode('?', $match1);
            $ext = '.' . pathinfo($img_arr[0], PATHINFO_EXTENSION);
            if ($ext == '.') {
                continue;
            }
            list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
            $webp_enable = $this->addSettings['webp_enable'];
            $imgsrc_filepath = $this->twosecond_get_resource_root_path($match1, $img_root_url, $img_root_path);
            $imgsrc_webpfilepath = $this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath);
            if (in_array($ext, $webp_enable) && !empty($imgsrc_webpfilepath)) {
                if (file_exists($imgsrc_webpfilepath)) {
                    $match1 = $this->twosecond_get_media_resource_url($imgsrc_webpfilepath);
                }
            }
            
            $imgsrc_595_webpfilepath = $this->twosecond_get_img_webp_path595xh($imgsrc_webpfilepath,$imgsrc_filepath);
            if ($responsive && file_exists($imgsrc_595_webpfilepath)) {
                $match1 = $this->twosecond_get_media_resource_url($imgsrc_595_webpfilepath);
            }
            $match1 = $this->twosecond_remove_webp_img($match1);
            if (substr($match1, 0, 1) == '/' || substr($match1, 0, 4) == 'http') {
                if ($this->addSettings['image_cdn_url'] == $this->addSettings['site_url']) {
                    if ($this->addSettings['is_multisite_sub_domain']) {
                        $match1 = str_replace($this->addSettings['network_site_url'], $this->addSettings['site_url'], $match1);
                    }
                    $replacement = 'url(\'' . $match1 . '\')';
                } else {
                    $replacement = 'url(\'' . $this->addSettings['site_url'] . '/' . trim($match_arr['path'], '/') . '\')';
                }
            } else {
                if ($this->addSettings['is_multisite_sub_domain']) {
                    $match1 = str_replace($this->addSettings['network_site_url'], $this->addSettings['site_url'], $match1);
                }
                $replacement = 'url(\'' . $url_parent_url . '/' . trim($match_arr['path'], '/') . '\')';
            }
            $replacement  = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings['image_url_array']['host'], $replacement);
            $string = str_replace($org_match, $replacement, $string);
        }
        return $string;
    }
	
	/**
	 * Replace url brackets
	 *
	 * @param string $string String.
	 * @return string
	 */
    function twosecond_replace_url_brackets($string){
        $string = str_replace(['&apos;', '&#39;', '&#039;', '&quot;', '&#34;'], '', $string);
        return trim(preg_replace('/^url\((\s*["\']?)(.*?)\1\)$/i', '$2', trim(html_entity_decode($string))));
    }
	
	/**
	 * Preload bs image from critical
	 */
    function twosecond_preload_image_from_critical(){
        $criticalPath = $this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css'];
        if ( file_exists( $criticalPath ) ) {
            $criticalCss = file_get_contents( $criticalPath ); // local file
            $data = $this->twosecond_get_tags_data($criticalCss, '<tsimages>', '</tsimages>');
            if(!empty($data)){
                $data = str_replace('<tsimages>', '', str_replace('</tsimages>', '', $data));
                if(!empty($data[0])){
                    $preloadImages = explode(',', $data[0]);
                    $preloadImages = array_map(function($url){
                        return $this->twosecond_change_url_scheme($this->twosecond_convert_to_webp($url));
                    }, $preloadImages);
                    if(empty($this->twoSecondDataBgLoadCss)){
                        preg_match_all(
                            '/<([a-z0-9]+)([^>]*\sstyle\s*=\s*(["\'])(?:(?!\3).)*background-image\s*:[^"\']*\3[^>]*)>/i',
                            $this->html,
                            $matches
                        );
                        foreach ($matches[0] as $element) {
                            preg_match('/background-image\s*:\s*url\(([^)]+)\)/i', $element, $bgMatch);
                            $bgUrl = isset($bgMatch[1]) ? trim($bgMatch[1], '\'" ') : '';
                            if(!empty($bgUrl)){
                                $bgUrl = $this->twosecond_convert_to_webp($bgUrl);
                                if(in_array($bgUrl, $preloadImages)){
                                    preg_match('/^<([a-z0-9]+)/i', $element, $tagMatch);
                                    $tagName = trim(strtolower($tagMatch[1] ?? ''));
                                    $selector = '';
                                    preg_match('/\sid\s*=\s*[\'"]([^\'"]+)[\'"]/i', $element, $idMatch);
                                    $id = trim($idMatch[1] ?? '');
                                    if(!empty($id)){
                                        $selector = "#$id";
                                    }else {
                                        preg_match('/\sclass\s*=\s*[\'"]([^\'"]+)[\'"]/i', $element, $classMatch);
                                        $class = trim($classMatch[1] ?? '');
                                        if(!empty($class)){
                                            $selector = '.' . str_replace(' ', '.', $class);
                                        } else {
                                            $selector = '[style*="'.$bgUrl.'"]';
                                        }
                                    }
                                    if(!empty($selector)){
                                        $this->inlineStyleBackgroundImages[$tagName][] = $selector;
                                    }
                                }
                            }
                        }
                    }
                    $preloadImages = array_map(function($url){
                        return str_replace($this->addSettings['site_url'], '', $this->twosecond_remove_webp_img($url));
                    }, $preloadImages);
                    $this->addSettings['preload_resources']['all'] = array_merge($this->addSettings['preload_resources']['all'], $preloadImages);
                }
            }
            $data = $this->twosecond_get_tags_data($criticalCss, '<twosecond_elements>', '</twosecond_elements>');
            if(!empty($data[0])){
                $data[0] = str_replace('<twosecond_elements>', '', str_replace('</twosecond_elements>', '', $data[0]));
                if(!empty($data[0])){
                    $this->addSettings['exclude_ccss_imgs'] = explode(',', $data[0]);
                }
            }
        }
    }

	/**
	 * Convert to webp
	 *
	 * @param string $src Src.
	 * @return string
	 */
    function twosecond_convert_to_webp($src){
        $components = $this->twosecond_parse_url($src);
        if(!$this->twosecond_is_external($src, $components)){
            $src = $this->twosecond_remove_query_params($src, $components);
            list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
            $imgsrc_filepath = $this->twosecond_get_resource_root_path($src, $img_root_url, $img_root_path);
            $imgsrc_webpfilepath = $this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath);
            if (file_exists($imgsrc_webpfilepath) && strpos($src, 'ts-webp') === false) {
                $src = $this->twosecond_get_media_resource_url($imgsrc_webpfilepath);
            }
            $img595src_webpfilepath = $this->twosecond_get_img_webp_path595xh($imgsrc_webpfilepath,$imgsrc_filepath);
            if($this->addSettings['is_mobile'] && !empty($this->settings['resp_bg_img']) && file_exists($img595src_webpfilepath)){
                $img_size = $this->twosecond_get_image_size($imgsrc_filepath);
                if (!empty($img_size[0]) && $img_size[0] > 400) {
                    $src = $this->twosecond_get_media_resource_url($img595src_webpfilepath);
                }
            }
            $src = str_replace('$ts$','', $src);
            if ($this->twosecond_check_enable_cdn('image') && !$this->twosecond_check_excluded_path($src, $this->addSettings['image_exclude_cdn_path'])) {
                $src = str_replace($this->addSettings['site_url'], $this->addSettings['image_cdn_url'], $src);
            }
        }
        return $src;
    }

	/**
	 * BS response
	 *
	 * @param array $data Data.
	 * @param int $status Status.
	 */
    function twosecond_response($data, $status = 200){
        if (!headers_sent()) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code($status);
            header('Content-Type: application/json');
        }
        $this->twosecond_json_encode_e($data); 
        exit;
    }

	/**
	 * TwoSecond cache file path
	 *
	 * @param string $url Url.
	 * @return array
	 */
    function twosecond_cache_file_path($url) {
        $parsed_url = $this->twosecond_parse_url($url);
        $path = $parsed_url['path'] ?? '/';
        $query = $parsed_url['query'] ?? '';
        $path1 = trim($path, '/');
        if (!empty($query)) {
            $path1 .= '/' . $query;
        }
        $path1 = urldecode($path1);
        $cachePath = $this->addSettings['root_cache_path']."/html/". ($parsed_url['scheme'] === "https" ? "on" : "off") . "-" . $parsed_url['host'];
        $cacheDirMob = $cachePath . '/' . $path1 . '/bsmob';
        $cacheDirDesktop = $cachePath . '/' . $path1;
        $cacheFileMob = $cacheDirMob . "/index.html";
        $cacheFileDesktop = $cacheDirDesktop . "/index.html";
        return [$cacheFileMob, $cacheFileDesktop];
    }

	/**
	 * Convert minutes to readable format
	 *
	 * @param int $minutes Minutes.
	 * @return string
	 */
    function twosecond_convert_minutes_to_readable_format($minutes) {
        $units = [
            'year'   => 525600,
            'month'  => 43200,
            'day'    => 1440,
            'hour'   => 60,
            'minute' => 1
        ];
        $result = [];
        foreach ($units as $name => $value) {
            if ($minutes >= $value) {
                $count = floor($minutes / $value);
                $minutes = $minutes % $value;
                $result[] = "$count $name" . ($count > 1 ? 's' : '');
            }
        }
        return implode(' ', array_slice($result, 0, 2));
    }

	/**
	 * BS restart optimization
	 */
    public function twosecond_restart_optimization(){
		$this->twosecond_truncate_site_urls_table();
		$this->twosecond_remove_critical_css_cache_files();
		$cachePath =  $this->twosecond_get_cache_path('');
        $this->twosecond_create_random_key();
        $this->twosecond_cache_rmdir($cachePath);
	}

	/**
	 * BS is url allowed for optimization
	 *
	 * @param string $url Url.
	 * @return bool
	 */
    public function twosecond_is_url_allowed_for_optimization($url = '') {
        if (empty($url)) {
            $url = rtrim($this->addSettings['full_url_without_param'], '/');
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\/.*/', $url)) {
            return false;
        }
        
        static $patterns = [
            '/wp-comments-post\.php/',
            '/wp-login\.php/',
            '/robots\.txt/',
            '/wp-cron\.php/',
            '/wp-content/',
            '/wp-admin/',
            '/wp-includes/',
            '/xmlrpc\.php/',
            '/wp-api/',
            '/leaflet-geojson\.php/',
            '/clientarea\.php/',
            '/wp-json/',
            '/favicon\.ico$/',
            '/style\.php$/',
            '/wp-config\.php/',
            '/wp-config\.php\.(orig|backup|bak)/',
            '/\.env(\.\w+)?$/',
            '/sendgrid\.env$/',
            '/docker-compose\.ya?ml$/',
            '/appsettings\.json$/',
            '/application\.ini$/',
            '/configuration\.php\.bak$/',
            '/settings\.py$/',
            '/config\.ini$/',
            '/app\.yaml$/',
            '/app\.log$/',
            '/phpinfo\.phtml$/',
            '/Default\.aspx$/',
            '/readme\.md$/',
            '/\.(xml|json|txt|ico|rss|webmanifest|xsl|map)$/i',
            '/\.(png|jpe?g|svg|gif|webp|avif|bmp|tiff|ico)$/i',
            '/\.(css|js)$/i',
        ];
        
        $path = $this->twosecond_parse_url($url, PHP_URL_PATH);
        
        if (empty($path) || $path === '/') {
            $normalizedUrl = rtrim($url, '/');
            $normalizedSiteUrl = rtrim($this->addSettings['site_url'], '/');
			return $normalizedUrl === $normalizedSiteUrl;
        }
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return false;
            }
        }
        
        return true;
    }

	/**
	 * Check ai optimization enabled
	 *
	 * @return bool
	 */
    function twosecond_check_ai_optimization_enabled(){
        $optaisettings = ['html_caching', 'optimization_on', 'css', 'load_critical_css', 'webp_jpg', 'webp_png', 'lazy_load', 'lazy_load_iframe', 'lazy_load_video', 'lazy_load_audio', 'js', 'localize_google_fonts', 'resp_bg_img', 'lbc', 'gzip', 'remquery', 'load_critical_css_style_tag'];
        foreach ($optaisettings as $value) {
            if(empty($this->settings[$value])){
               return false;
            }
        }
        return true;
    }

	/**
	 * Get url quote type
	 *
	 * @param string $cssValue Css value.
	 * @return string
	 */
    function twosecond_get_url_quote_type($cssValue) {
        $cssValue = trim($cssValue);
        if (stripos($cssValue, 'url(') !== 0) {
            return '';
        }
        $inside = substr($cssValue, 4, -1);
        $inside = trim($inside);

        $entities = ['&quot;', '&#39;', '&apos;'];
        foreach ($entities as $entity) {
            $len = strlen($entity);
            if (substr($inside, 0, $len) === $entity && substr($inside, -$len) === $entity) {
                return $entity;
            }
        }

        if (strlen($inside) > 1 && $inside[0] === "'" && substr($inside, -1) === "'") {
            return "'";
        } elseif (strlen($inside) > 1 && $inside[0] === '"' && substr($inside, -1) === '"') {
            return '"';
        }
        return '';
    }

	/**
	 * BS insert data bg css in critical
	 *
	 * @param string $css Css.
	 * @return bool
	 */
    function twosecond_insert_data_bg_css_in_critical($css){
        $criticalFile = $this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css'];
        if(is_file($criticalFile) && !empty($css)){
            $critical_css = $this->twosecond_get_contents($criticalFile);
            $critical_css .= '/* <tsDataBgLoad>'.$css.'</tsDataBgLoad> */';
            $this->twosecond_create_file($criticalFile, $critical_css);
            return true;
        }
        return false;
    }

	/**
	 * BS get data bg css from critical
	 *
	 * @return string
	 */
    function twosecond_get_data_bg_css_from_critical(){
        $criticalFile = $this->twosecond_preload_css_path() . '/' . $this->addSettings['critical_css'];
        if(is_file($criticalFile)){
            $criticalCss = $this->twosecond_get_contents($criticalFile);
            $data = $this->twosecond_get_tags_data($criticalCss, '<tsDataBgLoad>', '</tsDataBgLoad>');
            if(!empty($data[0])){
                $data[0] = str_replace('<tsDataBgLoad>', '', str_replace('</tsDataBgLoad>', '', $data[0]));
                if(!empty($data[0])){
                    return $data[0];
                }
            }
        }
        return '';
    }

	/**
	 * Modify CSS URL array for optimization
	 *
	 * Processes URL arrays to clean up paths, remove version numbers,
	 * and handle special directory structures.
	 *
	 * @param array $url_array Parsed URL array to modify
	 * @return array Modified URL array
	 */
	public function twosecond_modify_css_url_array( $url_array ) {
		if(strpos($url_array['path'],'./') !== false || strpos($url_array['path'],'../') !== false){
			$url_array['path'] = $this->twosecond_remove_dot_path_segments($url_array['path']);
		}
		if(strpos($url_array['path'],'/version') !== false){
			$url_array['path'] = preg_replace('/version(\d)*\//', '', $url_array['path']);
		}
		if(strpos($this->addSettings['document_root'],'/pub') !== false && strpos($url_array['path'],'/pub') !== false){
			$url_array['path'] = str_replace('/pub/', '/', $url_array['path']);
		}
		return $url_array;
	}
	
	/**
	 * Parse CSS URL for optimization
	 *
	 * Parses a CSS href URL and returns a structured array
	 * with path information for optimization.
	 *
	 * @param string $css_href The CSS href URL to parse
	 * @return array Parsed URL array with path information
	 */
	public function twosecond_parse_css_url( $css_href ) {
		$url_array = $this->twosecond_custom_parse_url(str_replace($this->addSettings['site_url'],'',$css_href));
		$url_array['path'] = !empty($url_array['path']) ? '/'.ltrim($url_array['path'],'/') : '';
		return $url_array;
	}

	/**
	 * Save assets on server
	 *
	 * @param array $assetUrls Asset urls.
	 * @param string $auth Auth.
	 */
    function twosecond_save_assets_on_server($assetUrls, $auth = ''){
        if(is_array($assetUrls) && count($assetUrls)){
            $cssData = [];
            foreach ($assetUrls as $key => $url) {
                $newUrl = str_replace($this->addSettings['css_cdn_url'],$this->addSettings['site_url'],$url);
                $url_array = $this->twosecond_parse_css_url($newUrl);
				$url_array = $this->twosecond_modify_css_url_array($url_array);
                $cssPath = $this->addSettings['document_root'].$url_array['path'];
                if(is_file($cssPath) && filesize($cssPath) > 0){
                    $content = @file_get_contents($cssPath);
                    if(!empty($content)){
                        $data = [];
                        $data['url'] = $url;
                        $ext = pathinfo($cssPath, PATHINFO_EXTENSION);
                        $data['content'] = ($ext === 'css') ? $content : base64_encode($content);
                        $cssData[] = $data;
                        unset($data);
                    }
                }else{
                    $data = [];
                    $data['url'] = $url;
                    $data['content'] = '/* no-css-in-file */';
                    $cssData[] = $data;
                    unset($data);
                }
            }
            
            if(is_array($cssData) && count($cssData)){
                $requestUrl = $this->addSettings['api_url'] . '/optimize/css/fetch-css.php';
                $this->twosecond_remote_request_non_blocking($requestUrl, ['auth' => $auth, 'domain' => $this->addSettings['site_url'], 'key' => $this->addSettings['mainLicenseKey'],  'urls' => $cssData], 'POST', false, true);
            }
        }
    }

	/**
	 * BS css files count
	 *
	 * @param string $url Url.
	 * @return array
	 */
    function twosecond_css_files_count($url) {
		$criticalPath = $this->twosecond_preload_css_path($url);
		$files = glob(rtrim($criticalPath, '/').'/*.css');
		$mobCount = 0;
		$nonMobCount = 0;
		foreach ($files as $file) {
			if (substr($file, -4) !== '.css') {
				continue;
			}
			if (substr($file, -7) === 'mob.css') {
				$mobCount++;
			} else {
				$nonMobCount++;
			}
		}

		return [
			'mobile'     => $mobCount,
			'desktop' => $nonMobCount
		];
	}

	/**
	 * Is json
	 *
	 * @return bool
	 */
    public function twosecond_is_json_page(){
		return $this->current_page_content_type == "json" ? true : false;
	}

	/**
	 * Is xml
	 *
	 * @return bool
	 */
	public function twosecond_is_xml_page(){
		return $this->current_page_content_type == "xml" ? true : false;
	}

	/**
	 * Is feed
	 *
	 * @return bool
	 */
	public function twosecond_is_feed_page() {
		return strpos($this->html, '<atom:link') !== false;
	}

	/**
	 * BS change url scheme
	 *
	 * @param string $url Url.
	 * @return string
	 */
    function twosecond_change_url_scheme($url){
		$parsedUrl = $this->twosecond_parse_url($url);
		if(!(empty($parsedUrl['host']) || empty($parsedUrl['scheme']) || $parsedUrl['host'] != $this->addSettings['site_url_array']['host'])){
			$url = str_replace($parsedUrl['scheme'], $this->addSettings['site_url_array']['scheme'], $url);
		}
		return $url;
	} 

	/**
	 * BS optimize with ai
	 */
    public function twosecond_optimize_with_ai() {
        $ids = $this->addSettings['twosecond_post']['ids'] ?? [];
		$rows = $this->addSettings['twosecond_post']['rows'] ?? 10;
		$page = $this->addSettings['twosecond_post']['page'] ?? 1;
		$count = $this->addSettings['twosecond_post']['count'] ?? 0;
		$url = $this->addSettings['twosecond_post']['url'] ?? '';
		$status = $this->addSettings['twosecond_post']['status'] ?? 'all';
		switch ($status) {
			case 'all':
				$statusArray = [0, 1, 2, 3, 4, 5, 6];
				break;
			case 'pending':
				$statusArray = [0];
				break;
			case 'in-progress':
				$statusArray = [1, 2, 3];
				break;
			case 'error':
				$statusArray = [5];
				break;
			case 'done':
				$statusArray = [4, 6];
				break;
			default:
				$statusArray = [0, 1, 2, 3, 4, 5, 6];
				break;
		}
		$reqCount = $this->addSettings['twosecond_post']['reqCount'] ?? 0;
		$response = [];
		$response['status'] = true;
		$response['message'] = 'Optimization is in progress';
		if($count){
			if(empty($ids) || !is_array($ids) || count($ids) == 0){
				$ids = $this->twosecond_get_pending_ids($count);
                if(!empty($ids)){
                    $this->twosecond_run_background_cron('crawl', $ids, $reqCount);
                }
			} else {
				$ids = $this->twosecond_filter_pending_optimization_ids($ids);
                if(!empty($ids)){
                    $this->twosecond_run_background_cron('optimize', $ids, $reqCount);
                }
			}
		}
		$response['ids'] = $ids;
		$response['table_data'] = $this->twosecond_get_optimize_ai_table_data(['rows' => $rows, 'page' => $page, 'status' => $statusArray, 'url' => $url]);
		$this->twosecond_response($response);
	}

	/**
	 * BS change url absolute to relative
	 *
	 * @param string $url Url.
	 * @param array $parsedUrl Parsed url.
	 * @return string
	 */
    function twosecond_change_url_absolute_to_relative($url, $parsedUrl = []){
		if(empty($parsedUrl)){
			$parsedUrl = $this->twosecond_parse_url($url);
		}
		if(empty($parsedUrl['host']) || empty($parsedUrl['scheme'])) return false;
		$url = trim(str_replace($parsedUrl['host'], '', str_replace($parsedUrl['scheme'].'://', '', $url)));
		$url = empty($url) ? '/' : $url;
		return $url;
	}

	/**
	 * BS change url relative to absolute
	 *
	 * @param string $url Url.
	 * @return string
	 */
    function twosecond_change_url_relative_to_absolute($url){
		return $this->addSettings['site_url_array']['scheme'] . '://' . $this->addSettings['site_url_array']['host'] . $url; 
	}

	/**
	 * Get sitemap urls
	 *
	 * @param string $siteUrl Site url.
	 * @return array
	 */
    function twosecond_get_sitemap_urls( $siteUrl ) {
        $request = wp_remote_get( trailingslashit( $siteUrl ) . 'robots.txt' );
        if ( is_wp_error( $request ) ) {
            return array();
        }

        $robotsTxt = wp_remote_retrieve_body( $request );
        if ( ! $robotsTxt ) {
            return array();
        }

        preg_match_all( '/Sitemap:\s*(.+)/i', $robotsTxt, $matches );
        $sitemaps = isset( $matches[1] ) ? $matches[1] : array();
        if ( empty( $sitemaps ) ) {
            $sitemaps[] = trailingslashit( $siteUrl ) . 'sitemap.xml';
        }

        $allUrls = array();
        foreach ( $sitemaps as $sitemapUrl ) {
            $allUrls = array_merge( $allUrls, $this->twosecond_parse_sitemap( $sitemapUrl ) );
        }
        return $allUrls;
    }

	/**
	 * Parse sitemap
	 *
	 * @param string $sitemapUrl Sitemap url.
	 * @return array
	 */
    function twosecond_parse_sitemap( $sitemapUrl ) {
        $response = wp_remote_get( $sitemapUrl );
        if ( is_wp_error( $response ) ) {
            return array();
        }

        $content = wp_remote_retrieve_body( $response );
        if ( ! $content ) {
            return array();
        }

        $urls = array();
        $xml  = @simplexml_load_string( $content );
        if ( ! $xml ) {
            return array();
        }

        if ( isset( $xml->sitemap ) ) {
            foreach ( $xml->sitemap as $s ) {
                $loc  = (string) $s->loc;
                $urls = array_merge( $urls, $this->twosecond_parse_sitemap( $loc ) );
            }
        }

        if ( isset( $xml->url ) ) {
            foreach ( $xml->url as $u ) {
                $urls[] = (string) $u->loc;
            }
        }

        return $urls;
    }

	/**
	 * BS enque responsive image
	 *
	 * @param string $url Url.
	 * @return array
	 */
    function twosecond_enque_responsive_image($url){
		list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
		$imgsrc_filepath = $this->twosecond_get_resource_root_path($url, $img_root_url, $img_root_path);
		$webp_path = $this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath);
		
		$extension = strtolower(pathinfo($imgsrc_filepath, PATHINFO_EXTENSION));
		if($extension === 'webp'){
			$imgsrc_webpfilepath = $webp_path.'-595xh.webp';
		} else {
			$imgsrc_webpfilepath = rtrim(str_replace('.webp$','-595xh.webp$', $webp_path.'$'), '$');
		}
		
		if(!file_exists($imgsrc_webpfilepath)){
            $this->addSettings['twosecond_responsive_img_urls'][] = $url;
        }
        return [$url, $imgsrc_webpfilepath];
	}

	/**
	 * BS create folder
	 *
	 * @param string $path Path.
	 * @return string
	 */
	function twosecond_create_folder($path)
    {
        $realpath = urldecode($path);
        if (is_dir($realpath)) {
            return $path;
        }
        try {
            $this->twosecond_mkdir($realpath, 0755, true);
        } catch (\Exception $e) {
            return false;
        }
        return $path;
    }

	/**
	 * Is mobile
	 *
	 * @return int
	 */
	function twosecond_is_mobile_device()
    {
        $useragent = $this->addSettings['twosecond_server']['HTTP_USER_AGENT'] ?? '';
        if (!empty($useragent)) {
            if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
                return 1;
            }
        }
        return 0;
    }

	/**
	 * TwoSecond create html cache file
	 *
	 * @param string $html Html.
	 * @return string
	 */
    function twosecond_create_html_cache_file()
    {
        if ($msg = $this->twosecond_no_html_cache($this->html)) {
            $this->html = preg_replace('/<\!--TWOSECOND_PAGE_TYPE_[a-z]+-->/i', '', $this->html);
            return $this->html . (strlen($msg) > 1 ? $msg : '');
        } else {
            $this->html = preg_replace('/<\!--TWOSECOND_PAGE_TYPE_[a-z]+-->/i', '', $this->html);
        }
        $this->twosecond_create_html_file($this->html);
    }

	/**
	 * Get mobile browsers
	 *
	 * @return array
	 */
    public function twosecond_get_mobile_browsers()
    {
        $mobile_browsers  = array(
            '\bCrMo\b|CriOS|Android.*Chrome\/[.0-9]*\s(Mobile)?',
            '\bDolfin\b',
            'Opera.*Mini|Opera.*Mobi|Android.*Opera|Mobile.*OPR\/[0-9.]+|Coast\/[0-9.]+',
            'Skyfire',
            'Mobile\sSafari\/[.0-9]*\sEdge',
            'IEMobile|MSIEMobile', // |Trident/[.0-9]+
            'fennec|firefox.*maemo|(Mobile|Tablet).*Firefox|Firefox.*Mobile|FxiOS',
            'bolt',
            'teashark',
            'Blazer',
            'Version.*Mobile.*Safari|Safari.*Mobile|MobileSafari',
            'Tizen',
            'UC.*Browser|UCWEB',
            'baiduboxapp',
            'baidubrowser',
            'DiigoBrowser',
            'Puffin',
            '\bMercury\b',
            'Obigo',
            'NF-Browser',
            'NokiaBrowser|OviBrowser|OneBrowser|TwonkyBeamBrowser|SEMC.*Browser|FlyFlow|Minimo|NetFront|Novarra-Vision|MQQBrowser|MicroMessenger',
            'Android.*PaleMoon|Mobile.*PaleMoon'
        );
        return $mobile_browsers;
    }

	/**
	 * BS check enable cdn path
	 *
	 * @param string $url Url.
	 * @param string $type Type.
	 * @return int
	 */
    public function twosecond_check_enable_cdn_path($url, $type = '')
    {
        $enable_cdn = 1;
        if (!empty($this->addSettings["{$type}_exclude_cdn_path"])) {
            foreach ($this->addSettings["{$type}_exclude_cdn_path"] as $path) {
                if (strpos($url, $path) !== false) {
                    $enable_cdn = 0;
                    break;
                }
            }
        }
        return $enable_cdn;
    }

	/**
	 * BS delete server cache
	 *
	 * @return bool
	 */
    function twosecond_delete_server_cache()
    {
        $options = array(
            'url' => $this->addSettings['home_url'],
            'key' => $this->addSettings['mainLicenseKey']
        );

        $response = $this->twosecond_remote_get($this->addSettings['api_url'] . '/optimize/css/delete-css.php', $options);
        if (!empty($response)) {
            return true;
        } else {
            return false;
        }
    }

	/**
	 * TwoSecond validate license key
	 *
	 * @param string $key Key.
	 * @return string
	 */
    function twosecond_validate_license_key($key = '')
    {
        $key = !empty($this->addSettings['twosecond_get']['key']) ? $this->addSettings['twosecond_get']['key'] : $key;
        if (!empty($key)) {
            $options = array(
                'license_id' => $key,
                'domain' => base64_encode($this->addSettings['home_url'])
            );

            $response = $this->twosecond_remote_get($this->addSettings['api_url'] . '/optimize/get_license_detail.php', $options);
            if (!empty($response)) {
                $res_arr = json_decode($response, true); // Force array to avoid Fatal Error on PHP 8
                if (isset($res_arr[0]) && $res_arr[0] == 'success') {
                    return $this->twosecond_json_encode(array('success', 'verified', ($res_arr[1] ?? 'premium')));
                } else {
                    return $this->twosecond_json_encode(array('fail', 'could not verify-1' . $response));
                }
            } else {
                return $this->twosecond_json_encode(array('fail', 'could not verify-3'));
            }
        }
    }

	/**
	 * Is special content type
	 *
	 * @return bool
	 */
    public function twosecond_is_special_content_type()
    {
        if ($this->twosecond_endswith($this->addSettings['full_url'], '.xml') || $this->twosecond_endswith($this->addSettings['full_url'], '.xsl')) {
            return true;
        }

        return false;
    }

	/**
	 * Get bs contents insert link
	 *
	 * @param array $all_links All links.
	 * @return string
	 */
    function twosecond_get_contents_insert_link($all_links)
    {
        $insertLink = '';
        if (strpos($this->html, '<script>var twosecondGoogleFont') !== false) {
            $insertLink = '<script>var twosecondGoogleFont';
        } elseif (!empty($all_links['link'])) {
            foreach ($all_links['link'] as $link) {
                if (strpos($link, 'stylesheet') !== false) {
                    $insertLink = $link;
                    break;
                }
            }
        } else {
            $insertLink = !empty($all_links['script'][0]) ? $all_links['script'][0] : '';
        }
        return $insertLink;
    }

	/**
	 * BS debug time
	 *
	 * @param string $process Process.
	 */
    function twosecond_debug_time($process)
    {
        if (!empty($this->addSettings['twosecond_get']['twosecond_debug'])) {
            $starttime = !empty($this->addSettings['starttime']) ? $this->addSettings['starttime'] : $this->twosecond_microtime_float();
            $endtime = $this->twosecond_microtime_float();
            $this->html .= $process . '-' . ($endtime - $starttime) . '-ram-' . (memory_get_usage() / 1024 / 1024) . '-cpu-' . $this->twosecond_json_encode(sys_getloadavg()) . "\n";
        }
    }

	/**
	 * Microtime float
	 *
	 * @return float
	 */
    function twosecond_microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

	/**
	 * BS str replace last
	 *
	 * @param string $search Search.
	 * @param string $replace Replace.
	 * @param string $str Str.
	 * @return string
	 */
    function twosecond_str_replace_last($search, $replace, $str)
    {
        if (($pos = strrpos($str, $search)) !== false) {
            $search_length = strlen($search);
            $str = substr_replace($str, $replace, $pos, $search_length);
        }
        return $str;
    }

	/**
	 * BS custom parse url
	 *
	 * @param string $src Src.
	 * @return array
	 */
    function twosecond_custom_parse_url($src)
    {
        $src = trim($src);
        if (!empty($this->addSettings['site_url_array']['path'])) {
            if (strpos($src, $this->addSettings['site_url_array']['host']) !== false) {
                $src = str_replace($this->addSettings['site_url_array']['host'] . $this->addSettings['site_url_array']['path'], $this->addSettings['site_url_array']['host'], $src);
            } else {
                $src = $this->twosecond_str_replace_first($this->addSettings['site_url_array']['path'], '', $src);
            }
        }
        if (substr_count($src, '//') > 0) {
            $src = preg_replace('/(?<!:)\/\//', '/', $src);
        }
        $src_arr = $this->twosecond_parse_url($src);
        return $src_arr;
    }

	/**
	 * Str replace first
	 *
	 * @param string $search Search.
	 * @param string $replace Replace.
	 * @param string $subject Subject.
	 * @return string
	 */
    function twosecond_str_replace_first($search, $replace, $subject)
    {
        $pos = strpos($subject, $search); // Find the position of the first occurrence
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

	/**
	 * BS is external
	 *
	 * @param string $url Url.
	 * @param array $components Components.
	 * @param string $type Type.
	 * @return bool
	 */
    function twosecond_is_external($url, $components = [], $type = 'image')
    {
        if (empty($components)) {
            $components = $this->twosecond_parse_url($url);
        }
        $siteHost = $this->addSettings['site_url_array']['host'] ?? '';
        $cdnHost = $this->addSettings[$type . '_url_array']['host'] ?? $siteHost;
        $siteHostDiff = $this->addSettings['site_url_diff'] ?? '';
        $urlHost = $components['host'] ?? '';
        if (empty($urlHost) || stripos($cdnHost, $urlHost) !== false || stripos($siteHost, $urlHost) !== false || stripos($siteHostDiff, $urlHost) !== false) {
            return false;
        }
        return true;
    }

	/**
	 * BS endswith
	 *
	 * @param string $string String.
	 * @param string $test Test.
	 * @return bool
	 */
    function twosecond_endswith($string, $test)
    {
        $str_arr = explode('?', $string);
        $string = $str_arr[0];
        $ext = '.' . pathinfo($str_arr[0], PATHINFO_EXTENSION);
        if ($ext == $test)
            return true;
        else
            return false;
    }

	/**
	 * BS get html cache file path
	 *
	 * @param string $url Url.
	 * @param int $mobile Mobile.
	 * @return string
	 */
    function twosecond_get_html_cache_file_path($url, $mobile = 0)
    {
        if ($path = $this->twosecond_get_html_cache_path($url, $mobile)) {
            return $path . ($this->twosecond_endswith($url,'.xml') ? "/index.xml" : "/index.html");
        }
        return false;
    }

	/**
	 * BS get html cache path
	 *
	 * @param string $url Url.
	 * @param int $mobile Mobile.
	 * @return string
	 */
    function twosecond_get_html_cache_path($url, $mobile = 0)
    {
        $type = "/html";
        $enableCachingGetPara = isset($this->settings['enable_caching_get_para']) ? 1 : 0;
        $parsed_url = $this->twosecond_parse_url($url);
        $cachePath = !empty($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
        $device = '';
        if ($mobile) {
            $device = '/bsmob';
        }
        if (!empty($enableCachingGetPara) && !empty($parsed_url['query'])) {
            $cachePath .= '/' . $parsed_url['query'];
        }
        if (!empty($this->settings['enable_loggedin_user_caching']) && !empty($this->addSettings['user_hash'])) {
            $type .= '/' . $this->addSettings['user_hash'];
        }
        $type .= "/" . ($parsed_url['scheme'] === "https" ? "on" : "off") . "-" . $parsed_url['host'];
        $cachePath = $this->twosecond_get_web_cache_path($type, $cachePath);
        $cachePath .= $device;
        if ($cachePath) {
            $cachePath = urldecode($cachePath);
        }
        // for security
        if (preg_match("/\.{2,}/", $cachePath)) {
            $cachePath = false;
        }
        return $cachePath;
    }

	/**
	 * BS css compress init
	 *
	 * @param string $minify Minify.
	 * @return string
	 */
    function twosecond_css_compress_init($minify)
    {
        $minify = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $minify);
        $minify = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), ' ', $minify);
        return $minify;
    }

	/**
	 * BS create file
	 *
	 * @param string $path Path.
	 * @param string $text Text.
	 * @return bool
	 */
	function twosecond_create_file($path, $text = '//',$append = false)
    {
        $path_arr = explode('/', $path);
        $filename = array_pop($path_arr);
        $realpath = urldecode(implode('/', $path_arr));
        if (is_link($realpath) || strpos($realpath, '/./') !== false || strpos($realpath, '/../') !== false) {
            $realpath = realpath($realpath);
        }
        $this->twosecond_create_folder($realpath);
        $realFullPath = $realpath . '/' . $filename;
        return $this->twosecond_put_contents($realFullPath, $text,$append);
    }
    /**
     * Find script tags occurrence without quotes
     *
     * @param string $text Text.
     * @return int
     */
	function twosecond_find_occurrence_without_quotes($text){
        // Pattern to match script tags while skipping quoted content
        $pattern = '/(?:
            "(?:\\\\.|[^"\\\\])*"         # double-quoted string (supports escapes)
          | \'(?:\\\\.|[^\'\\\\])*\'      # single-quoted string (supports escapes)
        )(*SKIP)(*FAIL)                   # skip any quoted region entirely
        | <script\b[^>]*>.*?<\/script>    # match script tags
        /isx';                            // i = case-insensitive, s = dot-newline, x = ignore whitespace
    
        preg_match_all($pattern, $text, $matches);
        return count($matches[0]);
    }
	/**
	 * BS get tags data
	 *
	 * @param string $data Data.
	 * @param string $start_tag Start tag.
	 * @param string $end_tag End tag.
	 * @param string $duplicate Duplicate.
	 * @return array
	 */
	function twosecond_get_tags_data($data, $start_tag, $end_tag,$duplicate='',$extract = 'o'){
        $data_exists = 0;
        $i = 0;
        $end_tag_char_len = strlen($end_tag);
        $start_tag_char_len = strlen($start_tag);
        $script_array = array();
        $duplicate = !empty($duplicate) ? $duplicate : $start_tag;
        $script_array = array();
        while($data_exists != -1 && $i<20000) {
            if($start_tag == '<script'){
                $data1 = strpos($data,$start_tag.' ',$data_exists);
                $data2 = strpos($data,$start_tag.'>',$data_exists);
                if($data1 === false && $data2 !== false){
                    $data_exists = $data2;
                    $duplicate = $start_tag.'>';
                }elseif($data2 === false && $data1 !== false){
                    $data_exists = $data1;
                    $duplicate = $start_tag.' ';
                }elseif( $data1 > $data2){
                    $data_exists = $data2;
                    $duplicate = $start_tag.'>';
                }else{
                    $data_exists = $data1;
                    $duplicate = $start_tag.' ';
                }
            }else{
                $data_exists = strpos($data,$start_tag,$data_exists);
            }
            if($data_exists !== false){
                $end_tag_pointer = strpos($data,$end_tag,$data_exists);
                $extractedText = substr($data, $data_exists, $end_tag_pointer-$data_exists+$end_tag_char_len);
                if(($count = $this->twosecond_find_occurrence_without_quotes(substr($extractedText,$start_tag_char_len)) > 0)){
                    $end_tag_pointer = $this->twosecond_find_nth_occurrence($data, $end_tag, $count,$data_exists);
                }
                if(empty($end_tag_pointer)){
                    $end_tag_pointer = strlen($data)-strlen($end_tag);
                }
                if($extract == 'o'){
                    $script_array[] = substr($data, $data_exists, $end_tag_pointer - $data_exists + $end_tag_char_len);
                }else{
                    $script_array[] = substr($data, $data_exists + $start_tag_char_len, $end_tag_pointer - $data_exists - $start_tag_char_len);
                }
                $data_exists = $end_tag_pointer;
            }else{
                $data_exists = -1;
            }
            $i++;
        }
        return $script_array;
    }

	/**
	 * Find nth occurrence
	 *
	 * @param string $haystack Haystack.
	 * @param string $needle Needle.
	 * @param int $nth Nth.
	 * @param int $data_exists Data exists.
	 * @return int
	 */
    function twosecond_find_nth_occurrence($haystack, $needle, $nth,$data_exists) {
        $pos = $data_exists;
        for ($i = 0; $i < $nth; $i++) {
            $pos = strpos($haystack, $needle, $pos);
            if ($pos === false) {
                return false; // Not found
            }
            $pos++; // Move past the last found position
        }
        return $pos - 1 ?? $data_exists; // Adjust position (since we increment after finding)
    }

	/**
	 * Clean script tags
	 *
	 * @param string $html Html.
	 * @return string
	 */
	function twosecond_clean_script_tags($html){
        $cleaned = preg_replace_callback('/(<script[\s\S]*?>)/i', function($matches){
            return preg_replace('/\s+/', ' ', $matches[1]);
        }, $html);
        return $cleaned;
    }

	/**
	 * BS parse link
	 *
	 * @param string $tag Tag.
	 * @param string $link Link.
	 * @return array
	 */
	function twosecond_parse_link($tag, $link)
    {
        $link_arr = array();
        
        $urlAttributes = ['src', 'srcset', 'href', 'data-src', 'data-srcset', 'data-href'];

        $linkForUrlAttrs = $link;
        if (preg_match('/<script\b([^>]*)>/i', $link, $m)) {
            $linkForUrlAttrs = '<script ' . $m[1] . '>';
        }

        foreach ($urlAttributes as $attrName) {
            if (preg_match('/(?:[\s>"]|^)' . preg_quote($attrName, '/') . '=["\']([^"\']+)["\']/i', $linkForUrlAttrs, $matches)) {
                $link_arr[$attrName] = trim($matches[1]);
            }
        }
        
        // Use DOMDocument for other attributes
        $xmlDoc = new \DOMDocument();
        if (!empty($link) && @$xmlDoc->loadHTML($link) !== false) {
            $tag_html = $xmlDoc->getElementsByTagName($tag);
            if (!empty($tag_html[0])) {
                foreach ($tag_html[0]->attributes as $attr) {
                    if (!in_array(strtolower($attr->nodeName), $urlAttributes)) {
                        $attrValue = $attr->nodeValue;
                        if (!mb_check_encoding($attrValue, 'UTF-8')) {
                            $attrValue = iconv('ISO-8859-1', 'UTF-8', $attrValue);
                        }
                        $link_arr[$attr->nodeName] = $attrValue;
                    }
                }
            }
        }
        
        if($tag == 'script'){
            $link_arr['type'] = empty($link_arr['type']) ? '' : $link_arr['type'];
        }
        if (strpos($link, '><') === false) {
            $link_arr['html'] = $this->twosecond_parse_script($tag, $link);
        }
        return $link_arr;
    }

	/**
	 * BS parse script
	 *
	 * @param string $tag Tag.
	 * @param string $link Link.
	 * @return array
	 */
	function twosecond_parse_script($tag, $link)
    {
        $data_exists = strpos($link, '>');
        if (!empty($data_exists)) {
            $end_tag_pointer = strpos($link, '</' . $tag . '>', $data_exists);
            $link_arr = substr($link, $data_exists + 1, $end_tag_pointer - $data_exists - 1);
        }
        return $link_arr;
    }

	/**
	 * BS str replace set js
	 *
	 * @param string $str String.
	 * @param string $rep Rep.
	 */
	function twosecond_str_replace_set_js($str, $rep)
    {
        global $twoSecond_str_replace_str_js, $twoSecond_str_replace_rep_js;
        $twoSecond_str_replace_str_js[] = $str;
        $twoSecond_str_replace_rep_js[] = $rep;
    }

	/**
	 * BS implode link array
	 *
	 * @param string $tag Tag.
	 * @param array $array Array.
	 * @return string
     */
	function twosecond_implode_link_array($tag, $array)
    {
        if (empty($array)) {
            return '';
        }
        $link = '<' . $tag . ' ';
        $html = '';
        if (!empty($array['html'])) {
            $html = $array['html'];
            unset($array['html']);
        }
        foreach ($array as $key => $arr) {
            if ($key != 'html') {
                $link .= $key . "=\"" . str_replace('"', "'", $arr) . "\" ";
            }
        }
        $link = trim($link);
        if ($tag == 'script') {
            $link .= '>' . $html . '</script>';
        } elseif ($tag == 'iframe') {
            $link .= '>' . $html . '</iframe>';
        } elseif ($tag == 'iframelazy') {
            $link .= '>' . $html . '</iframelazy>';
        } else {
            $link .= '>';
        }
        return $link;
    }

	/**
	 * BS insert content head
	 *
	 * @param string $content Content.
	 * @param int $pos Pos.
	 * @param string $link Link.
	 */
	function twosecond_insert_content_head($content, $pos, $link = '')
    {
        global $twoSecond_insert_content_head;
        $twoSecond_insert_content_head[] = array($content, $pos, $link);
        if ($pos == 1) {
            $this->html = preg_replace('/<style/', $content . '<style', $this->html, 1, $count);
        } elseif ($pos == 2) {
            if (!empty($link)) {
                $this->html = str_replace($link, $content . $link, $this->html);
            } else {
                $this->html = preg_replace('/(<link.*>)/', $content . "$1", $this->html, 1);
            }
        } elseif ($pos == 3) {
            $this->html = preg_replace('/<head([^<]*)>/', '<head$1>' . $content, $this->html, 1, $count);
            if (empty($count)) {
                $this->html = preg_replace('/<html([^<]*)>/', '<html$1>' . $content, $this->html, 1, $count);
            }
        } elseif ($pos == 4) {
            $this->html = preg_replace('/<\/head(\s*)>/', $content . '</head$1>', $this->html, 1, $count);
            if (empty($count)) {
                $this->html = preg_replace('/<body([^<]*)>/', $content . '<body$1>', $this->html, 1, $count);
            }
        } elseif ($pos == 5) {
            $this->html = preg_replace($content, '', $this->html, 1, $count);
        } elseif ($pos == 6) {
            $this->html = $this->twosecond_right_replace($this->html, '<link ', $content . '<link ');
        } else {
            $this->html = preg_replace('/<script/', $content . '<script', $this->html, 1, $count);
        }
    }

	/**
	 * Right replace
	 *
	 * @param string $string String.
	 * @param string $search Search.
	 * @param string $replace Replace.
	 */
    function twosecond_right_replace($string, $search, $replace)
    {
        $offset = strrpos($string, $search);
        if ($offset !== false) {
            $length = strlen($search);
            $string = substr_replace($string, $replace, $offset, $length);
        }
        return $string;
    }

	/**
	 * BS str replace set img
	 *
	 * @param string $str String.
	 * @param string $rep Rep.
	 */
    function twosecond_str_replace_set_img($str, $rep)
    {
        global $twoSecond_str_replace_str_img, $twoSecond_str_replace_rep_img;
        $twoSecond_str_replace_str_img[] = $str;
        $twoSecond_str_replace_rep_img[] = $rep;
    }

	/**
	 * BS str replace set css
	 *
	 * @param string $str String.
	 * @param string $rep Rep.
	 * @param string $key Key.
	 */
    function twosecond_str_replace_set_css($str, $rep, $key = '')
    {
        global $twoSecond_str_replace_str_css, $twoSecond_str_replace_rep_css;
        if ($key) {
            $twoSecond_str_replace_str_css[$key] = $str;
            $twoSecond_str_replace_rep_css[$key] = $rep;
        } else {
            $twoSecond_str_replace_str_css[] = $str;
            $twoSecond_str_replace_rep_css[] = $rep;
        }
    }

	/**
	 * BS str replace bulk
	 */
    function twosecond_str_replace_bulk()
    {
        global $twoSecond_str_replace_str_array, $twoSecond_str_replace_rep_array;
        global $twoSecond_str_replace_str_css, $twoSecond_str_replace_rep_css;
        global $twoSecond_str_replace_str_js, $twoSecond_str_replace_rep_js;
        global $twoSecond_str_replace_str_img, $twoSecond_str_replace_rep_img;
        if (!is_array($twoSecond_str_replace_str_array) && !is_array($twoSecond_str_replace_rep_array)) {
            $twoSecond_str_replace_str_array = array();
            $twoSecond_str_replace_rep_array = array();
        }
        if (!is_array($twoSecond_str_replace_str_css) && !is_array($twoSecond_str_replace_rep_css)) {
            $twoSecond_str_replace_str_css = array();
            $twoSecond_str_replace_rep_css = array();
        }
        if (!is_array($twoSecond_str_replace_str_js) && !is_array($twoSecond_str_replace_rep_js)) {
            $twoSecond_str_replace_str_js = array();
            $twoSecond_str_replace_rep_js = array();
        }
        if (!is_array($twoSecond_str_replace_str_img) && !is_array($twoSecond_str_replace_rep_img)) {
            $twoSecond_str_replace_str_img = array();
            $twoSecond_str_replace_rep_img = array();
        }
        $this->twosecond_debug_time('start json merge');
        $twoSecond_str_replace_str_array = array_merge($twoSecond_str_replace_str_img, $twoSecond_str_replace_str_css, $twoSecond_str_replace_str_js);
        $twoSecond_str_replace_rep_array = array_merge($twoSecond_str_replace_rep_img, $twoSecond_str_replace_rep_css, $twoSecond_str_replace_rep_js);
        $this->twosecond_debug_time('end json merge');
        $this->html = str_replace($twoSecond_str_replace_str_array, $twoSecond_str_replace_rep_array, $this->html);
    }

	/**
	 * Remove directory
	 *
	 * @param string $dir Directory.
	 */
	public function twosecond_rmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->twosecond_rmdir($dir . "/" . $object);
                    } else {
                        $this->twosecond_delete_file($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            return $this->twosecond_remove_single_directory($dir);
        }
    }

	/**
	 * Remove files
	 *
	 * @param string $dir Directory.
	 */
    function twosecond_rmfiles($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) != "dir") {
                        $this->twosecond_delete_file($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
        }
    }

    /**
     * Ensure that a given URL has a proper scheme (http or https).
     *
     * @param string $url The input URL (e.g., //fonts.googleapis.com, example.com, http://example.com).
     * @param string|null $defaultScheme The default scheme to use if missing (https or http).
     *                                   If null, it will detect the current request scheme.
     * @return string The URL with a proper scheme.
     */
    function twosecond_ensure_url_scheme($url, $defaultScheme = null) {
        $url = trim($url);
        if ($defaultScheme === null) {
            $defaultScheme = (!empty($this->addSettings['twosecond_server']['HTTPS']) && $this->addSettings['twosecond_server']['HTTPS'] !== 'off') ? 'https' : 'http';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return $defaultScheme . ':' . $url;
        }
        return $defaultScheme . '://' . ltrim($url, '/');
    }

    function twosecond_remove_webp_img($url){
        $responsive = $this->addSettings['is_mobile'] && $this->settings['resp_bg_img'];
        $webpJpg = !empty($this->settings['webp_jpg']) ? true : false;
        $webpPng = !empty($this->settings['webp_png']) ? true : false;

        if(!$webpJpg){
            if(strpos($url, '.jpg.webp') !== false && strpos($url, '/ts-webp/') !== false){
                $url = str_replace('/uploads/ts-webp/', '/', rtrim(str_replace('.jpg.webp$', '.jpg$', $url. '$'), '$'));
            }
            if(strpos($url, '.jpeg.webp') !== false && strpos($url, '/ts-webp/') !== false){
                $url = str_replace('/uploads/ts-webp/', '/', rtrim(str_replace('.jpeg.webp$', '.jpeg$', $url. '$'), '$'));
            }
        }
        if(!$webpPng){
            if(strpos($url, '.png.webp') !== false && strpos($url, '/ts-webp/') !== false){
                $url = str_replace('/uploads/ts-webp/', '/', rtrim(str_replace('.png.webp$', '.png$', $url. '$'), '$'));
            }
        }
        if(!$responsive){
            foreach (['jpg', 'png', 'webp', 'jpeg'] as $ext) {
                if(strpos($url, ".$ext-595xh.webp") !== false && strpos($url, '/ts-webp/') !== false){
                    $url = str_replace('/uploads/ts-webp/', '/', rtrim(str_replace(".$ext-595xh.webp$", ".$ext$", $url. '$'), '$'));
                    break;
                }
            }
        }
        return $url;
    }

    /**
     * Generate CSS for image aspect ratios.
     *
     * This function creates CSS rules for each image aspect ratio class found in
     * $this->addSettings['twosecond_img_aspect_ratio']. Each rule sets the aspect-ratio
     * property for the corresponding class, which helps maintain the correct
     * aspect ratio for images and prevents layout shifts.
     *
     * Example output:
     *   .twosecond-ratio-800x600 { aspect-ratio: 800/600; }
     *
     * @return string CSS string containing aspect-ratio rules for images.
     */
    function twosecond_image_ratio_css(){
        $css = '';
        if(!empty($this->addSettings['twosecond_img_aspect_ratio'])){
            foreach ($this->addSettings['twosecond_img_aspect_ratio'] as $key => $value) {
                $css .= ".$key{aspect-ratio:{$value[0]}/{$value[1]}} ";
            }
        }
        return $css;
    }

    /**
     * Convert HTML images to WebP format where applicable.
     * 
     * This function scans the HTML content for image tags and processes each image URL.
     * It checks if the image is eligible for conversion to WebP based on the plugin settings
     * @return void
     */

    function twosecond_convert_html_images_to_webp()
    {
        $images = [];
        $regex = '/(?:\s(?:data-[\w-]+)\s*=\s*["\'])(https?:\/\/[^\'"]+\.(?:jpg|jpeg|png))["\']/i';
		preg_match_all($regex, $this->html, $matches);
		$images = array_unique($matches[1]);
        if (!empty($images)) {
            $webp_enable = $this->addSettings['webp_enable'];
            
            foreach ($images as $img) {
                $imgnn = $img;
                $components = $this->twosecond_parse_url($img);
                if (!$this->twosecond_is_external($img, $components)) {
                    $imgnn = $this->twosecond_remove_query_params($img, $components);
                    list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
                    $twoSecond_img_ext = '.' . pathinfo($imgnn, PATHINFO_EXTENSION);
                    
                    $imgsrc_filepath = $this->twosecond_get_resource_root_path($imgnn, $img_root_url, $img_root_path);
                    $imgnn = trim(preg_replace('/\s+/', ' ', $imgnn));
                    $img_size = $this->twosecond_get_image_size($imgsrc_filepath);
                    
                    if (!empty($this->addSettings['is_mobile']) && !empty($this->settings['resp_bg_img'])) {
                        if (!empty($img_size[0]) && $img_size[0] > 400) {
                            $this->twosecond_enque_responsive_image($imgnn);
                            [$imgnn_arr, $imgsrc_filepath] = $this->twosecond_convert_to_smaller_image($img_root_path, $imgsrc_filepath, []);
                            $imgnn = !empty($imgnn_arr['src']) ? $imgnn_arr['src'] : $imgnn;
                        }
                    }
                    
                    if (count($webp_enable) > 0 && in_array($twoSecond_img_ext, $webp_enable)) {
                        $imgsrc_webpfilepath = $this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath);
                        if (file_exists($imgsrc_webpfilepath)) {
                            $imgnn = $this->twosecond_convert_to_webp($imgnn);
                        } else {
                            $this->webpEnqueImageUrls[] = $imgnn;
                        }
                    }
                }

                if ($this->twosecond_check_enable_cdn('image') && !$this->twosecond_check_excluded_path($imgnn, $this->addSettings['image_exclude_cdn_path'])) {
                    $imgnn = str_replace($this->addSettings['site_url'], $this->addSettings['image_cdn_url'], $imgnn);
                }

                if(!empty($imgnn) && $imgnn != $img){
                    $this->twosecond_str_replace_set_img($img, $imgnn);
                }
            }
        }
    }

    /**
     * Check if binary data is a valid WebP image.
     *
     * This function verifies whether the provided binary data represents a valid WebP image file.
     * It checks for the "RIFF" header at the start and the "WEBP" signature at the correct offset,
     * which are required for WebP format according to the specification.
     *
     * @param string $binaryData The binary data of the image file to validate.
     * @return bool Returns true if the data is a valid WebP image, false otherwise.
     */
    function twosecond_is_valid_webp($binaryData) {
        if (strlen($binaryData) < 12) {
            return false;
        }
        $riff = substr($binaryData, 0, 4);
        $webp = substr($binaryData, 8, 4);
        if ($riff !== 'RIFF' || $webp !== 'WEBP') {
            return false;
        }
        return true;
    }


    function twosecond_remove_htaccess_code(){
        $path = $this->addSettings['document_root'] . '/';
		if (!file_exists($path . ".htaccess")) {
			return;
		}
		$htaccess = $this->twosecond_get_contents($path . ".htaccess");
		if ($this->twosecond_check_is_writable($path . ".htaccess")) {
			if (strpos($htaccess, '# BEGIN BSLBC') !== false || strpos($htaccess, '# END BSLBC') !== false) {
				$htaccess = preg_replace("/#\s?BEGIN\s?BSLBC.*?#\s?END\s?BSLBC/s", "", $htaccess);
				$change_in_htaccess = 1;
			}
			if (strpos($htaccess, '# BEGIN BSGzip') !== false || strpos($htaccess, '# END BSGzip') !== false) {
				$htaccess = preg_replace("/\s*\#\s?BEGIN\s?BSGzip.*?#\s?END\s?BSGzip\s*/s", "", $htaccess);
				$change_in_htaccess = 1;
			}

			if (strpos($htaccess, '# BEGIN TSWEBP') !== false || strpos($htaccess, '# END TSWEBP') !== false) {
				$htaccess = preg_replace("/#\s?BEGIN\s?TSWEBP.*?#\s?END\s?TSWEBP/s", "", $htaccess);
				$change_in_htaccess = 1;
			}

            if (strpos($htaccess, '# BEGIN BS404') !== false || strpos($htaccess, '# END BS404') !== false) {
				$htaccess = preg_replace("/#\s?BEGIN\s?BS404.*?#\s?END\s?BS404/s", "", $htaccess);
				$change_in_htaccess = 1;
			}

            if (strpos($htaccess, '# BEGIN BSHTMLCACHE') !== false || strpos($htaccess, '# END BSHTMLCACHE') !== false) {
				$htaccess = preg_replace("/#\s?BEGIN\s?BSHTMLCACHE.*?#\s?END\s?BSHTMLCACHE/s", "", $htaccess);
				$change_in_htaccess = 1;
			}
            
			if ($change_in_htaccess) {
				$this->twosecond_put_contents($path . ".htaccess", $htaccess);
			}
		}
        return;
    }

    function twosecond_filter_valid_images($images){
        if(!is_array($images)) return [];
        $images = array_unique(array_filter($images));
        $valid_images = [];

        foreach ($images as $img) {
            list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
            $twoSecond_img_ext = '.' . pathinfo($img, PATHINFO_EXTENSION);
            
            if(!in_array($twoSecond_img_ext, ['.jpg', '.jpeg', '.png']) || strpos($img, 'wp-includes') !== false){
                continue;
            }
            
            $imgsrc_filepath = $this->twosecond_get_resource_root_path($img, $img_root_url, $img_root_path);
            $imgsrc_webpfilepath = $this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath);
            $img_size = $this->twosecond_get_image_size($imgsrc_filepath);
            
            if (is_file($imgsrc_filepath) && !is_file($imgsrc_webpfilepath) && !empty($img_size[0])) {
                $valid_images[] = $img;
            }
        }
        return $valid_images;
    }

	/**
	 * Drop queue entries whose WebP output already exists or source is missing.
	 *
	 * @param array $options WebP token JSON (urls, resize_urls, meta).
	 * @return array
	 */
	function twosecond_prune_webp_token_options( $options ) {
		if ( empty( $options ) || ! is_array( $options ) ) {
			return $options;
		}
		list( $img_root_path, $img_root_url ) = $this->twosecond_get_img_root_path();
		if ( ! empty( $options['urls'] ) && is_array( $options['urls'] ) ) {
			foreach ( $options['urls'] as $i => $url ) {
				$imgsrc_filepath     = $this->twosecond_get_resource_root_path( $url, $img_root_url, $img_root_path );
				$imgsrc_webpfilepath = $this->twosecond_get_img_webp_path( $img_root_path, $imgsrc_filepath );
				if ( ! is_file( $imgsrc_filepath ) || is_file( $imgsrc_webpfilepath ) ) {
					unset( $options['urls'][ $i ] );
				}
			}
			$options['urls'] = array_values( array_filter( $options['urls'] ) );
		}
		if ( ! empty( $options['resize_urls'] ) && is_array( $options['resize_urls'] ) ) {
			foreach ( $options['resize_urls'] as $i => $url ) {
				$imgsrc_filepath     = $this->twosecond_get_resource_root_path( $url, $img_root_url, $img_root_path );
				$imgsrc_webpfilepath = $this->twosecond_get_img_webp_path( $img_root_path, $imgsrc_filepath );
				$imgsrc_webpfilepath = $this->twosecond_get_img_webp_path595xh( $imgsrc_webpfilepath, $imgsrc_filepath );
				if ( ! is_file( $imgsrc_filepath ) || is_file( $imgsrc_webpfilepath ) ) {
					unset( $options['resize_urls'][ $i ] );
				}
			}
			$options['resize_urls'] = array_values( array_filter( $options['resize_urls'] ) );
		}
		return $options;
	}

	/**
	 * After optimization, prune fulfilled rows and delete or rewrite the token queue file.
	 *
	 * @param string $webpFile Absolute path to webp token file.
	 * @param array  $options  Queue options (mutated in memory for callers that keep ref).
	 */
	function twosecond_save_or_delete_webp_queue_file( $webpFile, $options ) {
		$options = $this->twosecond_prune_webp_token_options( $options );
		if ( empty( $options['urls'] ) && empty( $options['resize_urls'] ) ) {
			$this->twosecond_delete_file( $webpFile );
		} else {
			$this->twosecond_create_file( $webpFile, $this->twosecond_json_encode( $options ) );
		}
	}

	/**
	 * Download and store WebP files from imgopt API JSON body.
	 *
	 * @param array $data Decoded JSON.
	 */
	function twosecond_process_image_optimization_response_data( $data ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}
		if ( ! empty( $data['processed_files'] ) ) {
			foreach ( $data['processed_files'] as $key => $item ) {
				if ( empty( $item['optimized_url'] ) ) {
					continue;
				}
				list( $img_root_path, $img_root_url ) = $this->twosecond_get_img_root_path();
				$imgsrc_filepath     = $this->twosecond_get_resource_root_path( $key, $img_root_url, $img_root_path );
				$imgsrc_webpfilepath = $this->twosecond_get_img_webp_path( $img_root_path, $imgsrc_filepath );
				if ( file_exists( $imgsrc_webpfilepath ) ) {
					continue;
				}
				$bin = $this->twosecond_remote_get( $item['optimized_url'] );
				if ( empty( $bin ) ) {
					continue;
				}
				if ( $this->twosecond_is_valid_webp( $bin ) || imagecreatefromstring( $bin ) ) {
					$this->twosecond_create_file( $imgsrc_webpfilepath, $bin );
				}
			}
		}
		if ( ! empty( $data['processed_resized_files'] ) ) {
			foreach ( $data['processed_resized_files'] as $key => $item ) {
				if ( empty( $item['resized_url'] ) ) {
					continue;
				}
				list( $img_root_path, $img_root_url ) = $this->twosecond_get_img_root_path();
				$imgsrc_filepath     = $this->twosecond_get_resource_root_path( $key, $img_root_url, $img_root_path );
				$imgsrc_webpfilepath = $this->twosecond_get_img_webp_path( $img_root_path, $imgsrc_filepath );
				$imgsrc_webpfilepath = $this->twosecond_get_img_webp_path595xh( $imgsrc_webpfilepath, $imgsrc_filepath );
				if ( file_exists( $imgsrc_webpfilepath ) ) {
					continue;
				}
				$bin = $this->twosecond_remote_get( $item['resized_url'] );
				if ( empty( $bin ) ) {
					continue;
				}
				if ( $this->twosecond_is_valid_webp( $bin ) || imagecreatefromstring( $bin ) ) {
					$this->twosecond_create_file( $imgsrc_webpfilepath, $bin );
				}
			}
		}
	}

	/**
	 * Load webp queue JSON for many page tokens (cron batching).
	 *
	 * @param array $tokens MD5 tokens.
	 * @return array<string, array{path:string,options:array}>
	 */
	function twosecond_load_webp_queue_states_for_tokens( $tokens ) {
		$states = array();
		foreach ( array_unique( array_filter( (array) $tokens ) ) as $token ) {
			$path = $this->addSettings['webp_path'] . '/' . $token;
			if ( ! is_file( $path ) ) {
				continue;
			}
			$opt = json_decode( file_get_contents( $path ), true );
			if ( empty( $opt ) || ! is_array( $opt ) ) {
				continue;
			}
			$states[ $token ] = array(
				'path'    => $path,
				'options' => $opt,
			);
		}
		return $states;
	}

	/**
	 * Collect imgopt multipart batch items (unique URLs, all tokens that reference each) until size budget.
	 *
	 * @param array $states Same structure as twosecond_load_webp_queue_states_for_tokens output.
	 * @param int   $max_bytes Max total source bytes per request.
	 * @return array<int, array{queue:string,url:string,path:string,tokens:array<int,string>}>
	 */
	function twosecond_build_webp_merge_batch_items( $states, $max_bytes ) {
		$items      = array();
		$total_size = 0;
		if ( empty( $states ) ) {
			return $items;
		}
		list( $img_root_path, $img_root_url ) = $this->twosecond_get_img_root_path();
		$collect_unique = function ( $queue_key ) use ( $states ) {
			$seen   = array();
			$order  = array();
			foreach ( $states as $token => $row ) {
				$list = $row['options'][ $queue_key ] ?? array();
				if ( ! is_array( $list ) ) {
					continue;
				}
				foreach ( $list as $url ) {
					if ( isset( $seen[ $url ] ) ) {
						continue;
					}
					$seen[ $url ] = true;
					$order[]      = $url;
				}
			}
			return $order;
		};
		$tokens_having = function ( $queue_key, $url ) use ( $states ) {
			$out = array();
			foreach ( $states as $token => $row ) {
				$list = $row['options'][ $queue_key ] ?? array();
				if ( is_array( $list ) && in_array( $url, $list, true ) ) {
					$out[] = $token;
				}
			}
			return $out;
		};
		$try_add = function ( $queue_key, $url ) use ( &$items, &$total_size, $max_bytes, $img_root_path, $img_root_url, $tokens_having ) {
			$toks = $tokens_having( $queue_key, $url );
			if ( empty( $toks ) ) {
				return 'noop';
			}
			$img_filepath = $this->twosecond_get_resource_root_path( $url, $img_root_url, $img_root_path );
			if ( ! file_exists( $img_filepath ) ) {
				return 'missing';
			}
			$img_size = filesize( $img_filepath );
			if ( false === $img_size ) {
				return 'missing';
			}
			if ( $total_size + (int) $img_size > $max_bytes ) {
				return 'limit';
			}
			$items[] = array(
				'queue'  => $queue_key,
				'url'    => $url,
				'path'   => $img_filepath,
				'tokens' => $toks,
			);
			$total_size += (int) $img_size;
			return 'added';
		};
		foreach ( $collect_unique( 'urls' ) as $url ) {
			if ( 'limit' === $try_add( 'urls', $url ) ) {
				break;
			}
		}
		foreach ( $collect_unique( 'resize_urls' ) as $url ) {
			if ( 'limit' === $try_add( 'resize_urls', $url ) ) {
				break;
			}
		}
		return $items;
	}

	/**
	 * Remove one URL from a token queue in memory.
	 *
	 * @param array  $options Options array (by ref).
	 * @param string $queue_key urls or resize_urls.
	 * @param string $url       URL to remove.
	 */
	function twosecond_webp_state_remove_url( &$options, $queue_key, $url ) {
		if ( empty( $options[ $queue_key ] ) || ! is_array( $options[ $queue_key ] ) ) {
			return;
		}
		foreach ( $options[ $queue_key ] as $i => $u ) {
			if ( (string) $u === (string) $url ) {
				unset( $options[ $queue_key ][ $i ] );
			}
		}
		$options[ $queue_key ] = array_values( array_filter( $options[ $queue_key ] ) );
	}

	/**
	 * Send one merged multipart imgopt request for many tokens; on success, apply response and dequeue URLs from states.
	 *
	 * @param array $states By ref; twosecond_load_webp_queue_states_for_tokens structure.
	 * @return bool True if a request was sent and body parsed (even if no processed_files).
	 */
	function twosecond_send_webp_merged_multipart_from_states( &$states ) {
		if ( empty( $states ) ) {
			return false;
		}
		$max_upload_size = (int) $this->addSettings['max_upload_size'] * 1024 * 1024;
		$batch_items     = $this->twosecond_build_webp_merge_batch_items( $states, $max_upload_size );
		if ( empty( $batch_items ) ) {
			return false;
		}
		$first_tok = $batch_items[0]['tokens'][0];
		$base_opt  = $states[ $first_tok ]['options'];
		$apiUrl    = $this->addSettings['api_url'] . '/imgopt/api/v2/index.php';
		$boundary  = \wp_generate_password( 24, false );
		$headers   = array(
			'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
		);
		$body      = '';
		$add_field = function ( $name, $value ) use ( &$body, $boundary ) {
			$body .= "--{$boundary}\r\n";
			$body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
			$body .= "{$value}\r\n";
		};
		$add_file = function ( $name, $path ) use ( &$body, $boundary ) {
			$filename = basename( $path );
			$mime     = function_exists( 'mime_content_type' ) ? \mime_content_type( $path ) : 'application/octet-stream';
			$body    .= "--{$boundary}\r\n";
			$body    .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
			$body    .= "Content-Type: {$mime}\r\n\r\n";
			$body    .= \file_get_contents( $path ) . "\r\n";
		};
		$add_field( 'token', $base_opt['token'] ?? '' );
		$add_field( 'host', $base_opt['host'] ?? '' );
		$add_field( 'key', $base_opt['key'] ?? '' );
		$url_idx     = 0;
		$resize_idx  = 0;
		foreach ( $batch_items as $it ) {
			if ( 'urls' === $it['queue'] ) {
				$add_field( 'urls[' . $url_idx . ']', $it['url'] );
				$add_file( 'files[' . $url_idx . ']', $it['path'] );
				$url_idx++;
			} else {
				$add_field( 'resize_urls[' . $resize_idx . ']', $it['url'] );
				$add_file( 'resize_files[' . $resize_idx . ']', $it['path'] );
				$resize_idx++;
			}
		}
		$body .= "--{$boundary}--\r\n";
		
		error_log("TwoSecond: Sending " . count($batch_items) . " images to API: " . $apiUrl);

		$response = \wp_remote_post(
			$apiUrl,
			array(
				'timeout' => 60,
				'headers' => $headers,
				'body'    => $body,
				'sslverify' => false,
			)
		);

		if ( \is_wp_error( $response ) ) {
			error_log("TwoSecond: API Request Failed! Error: " . $response->get_error_message());
			return false;
		}

		$response_body = \wp_remote_retrieve_body( $response );
		error_log("TwoSecond: API Response Received (Length: " . strlen($response_body) . ")");

		if ( empty( $response_body ) ) {
			return false;
		}
		$data = \json_decode( $response_body, true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			error_log("TwoSecond: Invalid JSON from API");
			return false;
		}
		$this->twosecond_process_image_optimization_response_data( $data );
		foreach ( $batch_items as $it ) {
			foreach ( $it['tokens'] as $t ) {
				if ( isset( $states[ $t ] ) ) {
					$this->twosecond_webp_state_remove_url( $states[ $t ]['options'], $it['queue'], $it['url'] );
				}
			}
		}
		return true;
	}

	/**
	 * Cron/admin: merge webp queues for many URLs into one imgopt POST (one form `token`), then prune and delete token files when done.
	 *
	 * @param array $tokens MD5 page tokens that may have webp queue files.
	 */
	function twosecond_flush_webp_image_queue_for_tokens( $tokens ) {
		$apiUrl = $this->addSettings['api_url'] . '/imgopt/api/v2/index.php';
		$states = $this->twosecond_load_webp_queue_states_for_tokens( $tokens );
		if ( empty( $states ) ) {
			return;
		}
		$this->twosecond_send_webp_merged_multipart_from_states( $states );
		foreach ( $states as $state ) {
			$this->twosecond_save_or_delete_webp_queue_file( $state['path'], $state['options'] );
		}
	}

    function twosecond_enque_image_optimization_task( $token ) {

        $webpFile = $this->addSettings['webp_path'] . '/' . $token;
        if ( ! is_file( $webpFile ) ) {
            return;
        }
    
        $options = json_decode( file_get_contents( $webpFile ), true );
        if ( empty( $options ) ) {
            return;
        }
    
        $apiUrl = $this->addSettings['api_url'] . '/imgopt/api/v2/index.php';
    
        list( $img_root_path, $img_root_url ) = $this->twosecond_get_img_root_path();
    
        $max_upload_size = (int) $this->addSettings['max_upload_size'] * 1024 * 1024;
        $total_size      = 0;
    
        $boundary = wp_generate_password( 24, false );
        $headers  = [
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        ];
    
        $body = '';
    
        // ---- Helper to add form field ----
        $add_field = function ( $name, $value ) use ( &$body, $boundary ) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            $body .= "{$value}\r\n";
        };
    
        // ---- Helper to add file ----
        $add_file = function ( $name, $path ) use ( &$body, $boundary ) {
            $filename = basename( $path );
            $mime     = function_exists( 'mime_content_type' ) ? mime_content_type( $path ) : 'application/octet-stream';
    
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
            $body .= "Content-Type: {$mime}\r\n\r\n";
            $body .= file_get_contents( $path ) . "\r\n";
        };
    
        // ---- Base fields ----
        $add_field( 'token', $options['token'] );
        $add_field( 'host',  $options['host'] );
        $add_field( 'key',   $options['key'] );
    
        // ---- Original images ----
        foreach ( $options['urls'] as $i => $url ) {
    
            $img_filepath = $this->twosecond_get_resource_root_path( $url, $img_root_url, $img_root_path );
            if ( ! file_exists( $img_filepath ) ) {
                unset( $options['urls'][ $i ] );
                continue;
            }
    
            $img_size = filesize( $img_filepath );
            if ( $total_size + $img_size > $max_upload_size ) {
                break;
            }
    
            $add_field( "urls[{$i}]", $url );
            $add_file( "files[{$i}]", $img_filepath );
    
            $total_size += $img_size;
            unset( $options['urls'][ $i ] );
        }
    
        // ---- Resized images ----
        foreach ( $options['resize_urls'] as $i => $url ) {
    
            $img_filepath = $this->twosecond_get_resource_root_path( $url, $img_root_url, $img_root_path );
            if ( ! file_exists( $img_filepath ) ) {
                unset( $options['resize_urls'][ $i ] );
                continue;
            }
    
            $img_size = filesize( $img_filepath );
            if ( $total_size + $img_size > $max_upload_size ) {
                break;
            }
    
            $add_field( "resize_urls[{$i}]", $url );
            $add_file( "resize_files[{$i}]", $img_filepath );
    
            $total_size += $img_size;
            unset( $options['resize_urls'][ $i ] );
        }
    
        $body .= "--{$boundary}--\r\n";
    
        $response = wp_remote_post( $apiUrl, [
            'timeout' => 60,
            'headers' => $headers,
            'body'    => $body,
        ] );
    
        if ( is_wp_error( $response ) ) {
            return $options;
        }
    
        $response_body = wp_remote_retrieve_body( $response );
        if ( empty( $response_body ) ) {
            return;
        }
    
        $data = json_decode( $response_body, true );
        if ( empty( $data ) ) {
            return;
        }
    
		$this->twosecond_process_image_optimization_response_data( $data );
		$this->twosecond_save_or_delete_webp_queue_file( $webpFile, $options );
    }
    

    /**
     * Get the root path for a given relative path
     *
     * Attempts to resolve the absolute root directory for a given file or directory path by
     * checking multiple possible base paths, including the document root and WordPress ABSPATH.
     *
     * @param string $path Relative or absolute path to check
     * @return string The resolved root path, or empty string if not found
     */
    public function twosecond_get_root_path($path) {
        if (file_exists($this->addSettings['document_root'] . $path)) {
            return $this->addSettings['document_root'];
        }
        elseif (file_exists($this->addSettings['abspath'] . '/' . ltrim($path, '/'))) {
            return $this->addSettings['abspath'];
        }
        else {
            return '';
        }
    }

    public function twosecond_apply_pre_optimization_hooks() {
		$this->html = apply_filters('twosecond_apply_pre_optimization_hooks', $this->html);
	}

	public function twosecond_apply_before_optimization_hooks() {
		$this->html = apply_filters('twosecond_apply_before_optimization_hooks', $this->html);
	}

	public function twosecond_apply_after_optimization_hooks() {
		$this->html = apply_filters('twosecond_apply_after_optimization_hooks', $this->html);
	}

	public function twosecond_inner_js_customize($script_text) {
		return apply_filters('twosecond_inner_js_customize', $script_text);
	}

	public function twosecond_inner_js_excluded($exclude_js_bool, $inner_js) {
		return apply_filters('twosecond_inner_js_excluded', $exclude_js_bool, $inner_js);
	}

	public function twosecond_internal_js_customize($string, $path) {
		return apply_filters('twosecond_internal_js_customize', $string, $path);
	}

	public function twosecond_internal_css_customize($css, $path) {
		return apply_filters('twosecond_internal_css_customize', $css, $path);
	}

	public function twosecond_internal_css_minify($css_minify, $css, $path) {
		return apply_filters('twosecond_internal_css_minify', $css_minify, $css, $path);
	}

	public function twosecond_no_critical_css($ignore_critical_css, $url) {
		return apply_filters('twosecond_no_critical_css', $ignore_critical_css, $url);
	}

	public function twosecond_customize_critical_css($critical_css) {
		return apply_filters('twosecond_customize_critical_css', $critical_css);
	}

	public function twosecond_disable_htaccess_webp() {
		$this->addSettings['disable_htaccess_webp'] = apply_filters('twosecond_disable_htaccess_webp', $this->addSettings['disable_htaccess_webp']);
	}

	public function twosecond_customize_add_settings($addSettings) {
		return apply_filters('twosecond_customize_add_settings', $addSettings);
	}

	public function twosecond_customize_main_settings($settings) {
		return apply_filters('twosecond_customize_main_settings', $settings);
	}

	public function twosecond_change_video_to_videolazy($video_lazy) {
		return apply_filters('twosecond_change_video_to_videolazy', $video_lazy);
	}

	public function twosecond_change_iframe_to_iframe_lazy($iframe_lazy) {
		return apply_filters('twosecond_change_iframe_to_iframe_lazy', $iframe_lazy);
	}

	public function twosecond_exclude_image_to_lazyload($exclude_image, $img, $imgnn_arr) {
		return apply_filters('twosecond_exclude_image_to_lazyload', $exclude_image, $img, $imgnn_arr);
	}

	public function twosecond_customize_image($imgnn, $img, $imgnn_arr) {
		return apply_filters('twosecond_customize_image', $imgnn, $img, $imgnn_arr);
	}

	public function twosecond_exclude_css_filter($exclude_css, $css_obj, $css, $html) {
		return apply_filters('twosecond_exclude_css_filter', $exclude_css, $css_obj, $css, $html);
	}

	public function twosecond_customize_force_lazy_css($force_lazyload_css) {
		return apply_filters('twosecond_customize_force_lazy_css', $force_lazyload_css);
	}

	public function twosecond_external_javascript_customize($script_obj, $script) {
		return apply_filters('twosecond_external_javascript_customize', $script_obj, $script);
	}

	public function twosecond_external_javascript_filter($exclude_js,$script_obj,$script,$html) {
		return apply_filters('twosecond_external_javascript_filter', $exclude_js,$script_obj,$script,$html);
	}

	public function twosecond_customize_script_object($script_obj, $script) {
		return apply_filters('twosecond_customize_script_object', $script_obj, $script);
	}

	public function twosecond_exclude_internal_js_changes($exclude_from_twosecond_changes, $path, $string) {
		return apply_filters('twosecond_exclude_internal_js_changes', $exclude_from_twosecond_changes, $path, $string);
	}

	public function twosecond_exclude_page_optimization($html) {
        $exclude_page_optimization = 0;
        $exclude_page_optimization = apply_filters('twosecond_exclude_page_optimization', $exclude_page_optimization, $html);
        if ( $exclude_page_optimization ) {
            return true;
        }
		return false;
	}

	public function twosecond_customize_critical_css_url($url, $orgurl) {
        return apply_filters('twosecond_customize_critical_css_url', $url, $orgurl);
	}

	public function twosecond_customize_critical_css_filename($main_css_file_name, $combined_css_files) {
        return apply_filters('twosecond_customize_critical_css_filename', $main_css_file_name, $combined_css_files);
	}

	public function twosecond_exclude_image_from_convert_to_webp($path) {
        return apply_filters('twosecond_exclude_image_from_convert_to_webp', false, $path);
	}

	/**
	 * Remote GET request
	 *
	 * @param string $url Url.
	 * @param array $params Params.
	 * @param bool $mobile Mobile.
	 * @param bool $blocking Blocking.
	 * @param string $output Output.
	 * @return string
	 */
	function twosecond_remote_get($url, $params = array(), $mobile = false, $blocking = true, $output = 'body')
	{	
		$timeout = $blocking ? 15 : 3;
		
		// For GET requests, append parameters to the URL
		if (!empty($params)) {
			$url = add_query_arg($params, $url);
		}

		$options = array(
			'method' => 'GET',
			'timeout' => $timeout,
			'redirection' => 5,
			'sslverify' => false,
			'httpversion' => '1.0',
			'headers' => $mobile ? array('User-Agent' => 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Mobile Safari/537.36') : array('User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
			'cookies' => array()
		);

		// Make the request using WordPress HTTP API
		if (function_exists('wp_remote_get')) {
			$response = wp_remote_get($url, $options);
			if (!is_wp_error($response) && !empty($response['body'])) {
				if ($output == 'body') {
					return wp_remote_retrieve_body($response);
				} else {
					return wp_remote_retrieve_response_code($response);
				}
			}
		}
		return '';
	}
}
