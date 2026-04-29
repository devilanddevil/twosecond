<?php
/**
 * HTML Optimization Class
 *
 * This class handles HTML optimization, caching, and performance improvements
 * for the TwoSecond plugin. It extends JsOptimize to inherit JavaScript
 * optimization capabilities.
 *
 * @package TwoSecond
 * @author TwoSecond Team
 */

namespace TwoSecond;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TwoSecond\JsOptimize;
/**
 * HTML Optimization Class
 *
 * Handles HTML content optimization, caching, and performance improvements.
 * Extends JsOptimize to inherit JavaScript optimization capabilities.
 */
class HtmlOptimize extends JsOptimize {

	/**
	 * Current page content type
	 *
	 * @var string
	 */
	public $current_page_content_type = '';

	/**
	 * Cache file path for HTML content
	 *
	 * @var string
	 */
	public $cacheFilePath = '';

	/**
	 * Cache message for debugging
	 *
	 * @var string
	 */
	public $cacheMsg = '';

	/**
	 * Main HTML optimization method
	 *
	 * This is the primary method that processes HTML content and applies
	 * various optimizations including minification, lazy loading, and caching.
	 *
	 * @param string $html The HTML content to optimize
	 * @return string The optimized HTML content
	 */
	public function twoSecond( $html ) {
		// Store the HTML content
		$this->html = $html;
		
		// Get HTTP response code
		$this->statusCode = http_response_code();
		
		// Extract page title for optimization
		$this->addSettings['title'] = $this->twosecond_get_tags_data( $this->html, '<title>', '</title>' );
		
		// Start performance timing
		$this->twosecond_debug_time( 'start optimization' );
		
		// Set cache file path
		$this->twosecond_set_cache_file_path();

		$this->twosecond_apply_pre_optimization_hooks();
		
		// Check if optimization should be skipped
		$twosecond_no_optimization = $this->twosecond_no_optimization();
		if ( $twosecond_no_optimization > 0 ) {
			// Handle no optimization cases
			$this->twosecond_handle_no_optimization( $twosecond_no_optimization );
			return $this->html;
		}

		// Apply customization hooks
		$this->twosecond_apply_customization_hooks();
		
		// Clean script tags
		$this->html = $this->twosecond_clean_script_tags( $this->html );

		$this->twosecond_apply_before_optimization_hooks();
		
		// Handle lazy loading for background images
		if ( ! empty( $this->settings['lazy_load'] ) ) {
			$this->twosecond_lazy_load_background_image();
		}
		
		// Process all links and resources
		$allLinks = $this->twosecond_process_all_links();
		
		// Handle CSS optimization
		$this->twosecond_handle_css_optimization($allLinks['style']);
		
		// Get insert link content
		$insertLink = $this->twosecond_get_contents_insert_link( $allLinks );
		
		// Load Google Fonts
		$google_fonts = $this->twosecond_load_google_fonts();
		
		// Performance timing for JavaScript insertion
		$this->twosecond_debug_time( 'after javascript insertion' );
		
		// Insert critical CSS
		list( $criticalCssInsertion, $criticalReplace ) = $this->twosecond_insert_critical_css();
		
		// Generate preload HTML
		$preload_html = $this->twosecond_preload_resources();
		
		// Insert content in head section
		$this->twosecond_insert_head_content( $preload_html, $google_fonts, $insertLink );
		
		// Perform bulk string replacements
		$this->twosecond_str_replace_bulk();
		
		// Handle critical CSS insertion
		if ( $criticalCssInsertion ) {
			$this->twosecond_handle_critical_css_insertion( $criticalReplace );
		}
		
		// Insert Web Vitals logging script
		if ( ! empty( $this->settings['webvitals_logs'] ) ) {
			$this->twosecond_insert_content_head( $this->twosecond_core_web_vitals_script(), 4 );
		}
		
		// Insert lazy load images script before closing body tag
		$this->twosecond_insert_lazy_load_script();
		
		// Performance timing for BS script
		$this->twosecond_debug_time( 'bs script' );

		$this->twosecond_apply_after_optimization_hooks();

		$this->html = apply_filters('twosecond_apply_settings', $this->html, $this->settings);
		
		// Handle HTML caching
		if ( isset( $this->settings['html_caching'] ) && $this->settings['html_caching'] == 'on' && empty( $this->addSettings['twosecond_get']['twosecond_crawler'] )) {
			$this->twosecond_create_html_cache_file();
		}
		
		// Enqueue server tasks
		$this->twosecond_enque_task_on_server();
		
		// Final performance timing
		// Add detailed cache/optimization comment
		$isMobile = $this->twosecond_is_mobile_device();
		$mob_msg = $isMobile ? 'Mobile ' : '';
		$endtime = $this->twosecond_microtime_float();
		$current_time = gmdate("Y-m-d H:i:s T");
		$duration = number_format($endtime - $this->addSettings['starttime'], 2);
		
		// Remove existing TwoSecond comments to avoid duplication
		$this->html = preg_replace('/<!--.*?(Optimization|Cache) Created By TwoSecond at.*?-->/is', '', $this->html);
		$this->html = preg_replace('/<!-- TwoSecond Status:.*?-->/is', '', $this->html);

		$this->html .= "\n<!-- {$mob_msg}Cache Created By TwoSecond at {$current_time} in {$duration} secs -->";
		$this->html .= "\n<!-- TwoSecond Status: Active | Cache: " . ( isset( $this->settings['html_caching'] ) ? $this->settings['html_caching'] : 'off' ) . " -->";

		// Trigger immediate image optimization for the current page
		if ( ! empty( $this->pageToken ) ) {
			$queuedCount = count($this->webpEnqueImageUrls);
			error_log("TwoSecond: Finalizing page. Queued Images: {$queuedCount}");
			$this->html .= "\n<!-- TwoSecond Debug: PageToken: {$this->pageToken} | Queued Images: {$queuedCount} -->";
			$this->twosecond_flush_webp_image_queue_for_tokens( array( $this->pageToken ) );
		} else {
			$this->html .= "\n<!-- TwoSecond Debug: PageToken is EMPTY! -->";
		}

		return $this->html;
	}

	/**
	 * Check if optimization should be skipped
	 *
	 * Determines whether the current page should be optimized based on
	 * various conditions like user status, page type, and settings.
	 *
	 * @return int Optimization status code:
	 *             0 = Optimize normally
	 *             1 = Skip optimization
	 *             2 = Skip optimization and remove no-optimize tags
	 */
	public function twosecond_no_optimization() {
		// Check for original URL or missing body tag
		if ( ! empty( $this->addSettings['twosecond_get']['orgurl'] ) || strpos( $this->html, '<body' ) === false ) {
			return 2;
		}
		
		// Check if page is twosecond_ignored
		if ( $this->twosecond_ignored() ) {
			return 1;
		}
		
		// Check if URL is allowed for optimization
		if ( ! $this->twosecond_is_url_allowed_for_optimization() ) {
			return 1;
		}
		
		// Check if it's an AMP endpoint
		if ( $this->addSettings['is_amp_endpoint'] ) {
			return 1;
		}
		
		// Check header conditions
		if ( $this->twosecond_header_check() ) {
			return 1;
		}
		
		// Check if optimization is enabled
		if ( empty( $this->settings['optimization_on'] ) && 
			 empty( $this->addSettings['twosecond_get']['twosecond_get_css_post_type'] ) && 
			 empty( $this->addSettings['twosecond_get']['tester'] ) && 
			 empty( $this->addSettings['twosecond_get']['testing'] ) ) {
			return true;
		}
		
		// Check if user is logged in and optimization is disabled for logged-in users
		if ( empty( $this->settings['optimize_user_logged_in'] ) && $this->addSettings['twosecond_user_logged_in'] ) {
			return true;
		}
		
		// Check query parameters
		if ( empty( $this->settings['optimize_query_parameters'] ) && 
			 $this->addSettings['full_url'] != $this->addSettings['full_url_without_param'] && 
			 empty( $this->addSettings['twosecond_get']['tester'] ) && empty( $this->addSettings['twosecond_get']['twosecond_crawler'] ) ) {
			return 2;
		}
		
		// Check excluded pages
		if ( ! empty( $this->settings['exclude_pages_from_optimization'] ) && 
			 $this->twosecond_check_if_page_excluded( $this->settings['exclude_pages_from_optimization'] ) ) {
			return 1;
		}
		
		// Check if it's a 404 page
		if ( $this->twosecond_is_404() ) {
			return 1;
		}
		
		// Check admin user status
		if ( $this->addSettings['admin_user'] ) {
			return 1;
		}

		if ($this->twosecond_exclude_page_optimization( $this->html ) ) {
			return 1;
		}
		
		return 0;
	}

	/**
	 * Start optimization callback
	 *
	 * Initiates the output buffering for HTML optimization.
	 * Ensures that any buffer we start is also safely closed by registering a shutdown hook.
	 */
	public function twosecond_start_optimization_callback() {
		if ( ! isset( $GLOBALS['twosecond_ob_started'] ) ) {
			$GLOBALS['twosecond_ob_started'] = true;
			ob_start( array( $this, 'twoSecond' ) );
			
			// Register shutdown action to ensure buffer is always closed
			add_action( 'shutdown', array( $this, 'twosecond_ob_end_flush' ), 999 );
		}
	}

	/**
	 * End output buffer flush
	 *
	 * Safely ends the output buffer started by twosecond_start_optimization_callback().
	 */
	public function twosecond_ob_end_flush() {
		if ( ! empty( $GLOBALS['twosecond_ob_started'] ) && ob_get_level() > 0 ) {
			$GLOBALS['twosecond_ob_started'] = false;
			ob_end_flush();
		}
	}

	/**
	 * Set cache file path
	 *
	 * Determines the appropriate cache file path based on user agent
	 * and current URL for mobile/desktop optimization.
	 */
	public function twosecond_set_cache_file_path() {
		$isMobile = $this->twosecond_is_mobile_device();
		$url = $this->addSettings['full_url'];
		$this->cacheFilePath = $this->twosecond_get_html_cache_file_path( $url, $isMobile );
	}

	/**
	 * Handle cases where optimization is skipped
	 *
	 * @param int $twosecond_no_optimization The optimization status code
	 */
	private function twosecond_handle_no_optimization( $twosecond_no_optimization ) {

		if ( $twosecond_no_optimization == 1 ) {
			$this->twosecond_remove_no_optimize_page_if_exists();
		}
		if ( ! empty( $this->settings['html_caching'] ) ) {
			$this->twosecond_create_html_cache_file();
		}
	}

	/**
	 * Apply customization hooks
	 *
	 * Executes hooks for customizing addSettings and main settings.
	 */
	private function twosecond_apply_customization_hooks(){
		// Customize addSettings
		$this->addSettings = $this->twosecond_customize_add_settings($this->addSettings);

		// Customize settings
		$this->settings = $this->twosecond_customize_main_settings($this->settings);

		// Apply custom htaccess webp hook
		$this->twosecond_disable_htaccess_webp();
	}

	/**
	 * Process all links and resources
	 *
	 * Handles the processing of all links including scripts, stylesheets,
	 * images, and other resources for optimization.
	 */
	private function twosecond_process_all_links() {
		$this->twosecond_debug_time( 'before create all links' );
		
		// Define lazy load elements
		$lazyload = array( 'script', 'link', 'img', 'url' );
		
		// Add SVG to lazy load if enabled
		if ( ! empty( $this->settings['inlineToUrlSVG'] ) ) {
			$lazyload[] = 'svg';
		}
		
		// Add iframe to lazy load if enabled
		if ( ! empty( $this->settings['lazy_load_iframe'] ) ) {
			$lazyload[] = 'iframe';
		}
		
		// Add video to lazy load if enabled
		if ( ! empty( $this->settings['lazy_load_video'] ) ) {
			$lazyload[] = 'video';
		}
		
		// Add audio to lazy load if enabled
		if ( ! empty( $this->settings['lazy_load_audio'] ) ) {
			$lazyload[] = 'audio';
		}
		
		// Parse all links
		$this->twosecond_debug_time( 'parse all links' );
		$allLinks = $this->twosecond_set_all_links( $this->html, $lazyload );
		$this->twosecond_debug_time( 'after create all links' );
		
		// Minify scripts if present
		if ( ! empty( $allLinks['script'] ) ) {
			$this->twosecond_optimize_scripts( $allLinks['script'] );
		}
		$this->twosecond_debug_time( 'minify script' );
		
		$this->twosecond_debug_time( 'lazyload images' );
		
		$this->twosecond_minify_css( $allLinks['link'] );
		$this->twoSecondDataBgLoadCss = $this->twosecond_get_data_bg_css_from_critical();
		$this->twosecond_preload_image_from_critical();
		$this->twosecond_debug_time( 'minify css' );
		// Apply lazy loading to various elements
		$this->twosecond_lazyload_all( array(
			'iframe'  => $allLinks['iframe'],
			'video'   => $allLinks['video'],
			'audio'   => $allLinks['audio'],
			'img'     => $allLinks['img'],
			'picture' => $allLinks['picture'],
			'url'     => $allLinks['url'],
			'svg'     => $allLinks['svg']
		) );
		return $allLinks;
	}

	/**
	 * Handle CSS optimization
	 *
	 * Processes CSS optimization including critical CSS and custom CSS.
	 */
	private function twosecond_handle_css_optimization($styleLinks) {
		// Handle style tag loading in head
		if ( ! empty( $this->settings['load_style_tag_in_head'] ) ) {
			$this->twosecond_load_style_tag_in_head( $styleLinks );
		}
	}

	/**
	 * Insert content in head section
	 *
	 * @param string $preload_html Preload HTML content
	 * @param string $google_fonts Google Fonts content
	 * @param string $insertLink Insert link content
	 */
	private function twosecond_insert_head_content( $preload_html, $google_fonts, $insertLink ) {
		$head_content = $preload_html . 
					   '<style id="twosecond-bg-load">' . $this->twosecond_exclude_lazy_load_background_images() . ' video[data-class="LazyLoad"]{opacity: 0;} '.$this->twosecond_image_ratio_css().'</style>' .
					   $google_fonts .
					   '<script>' . $this->twosecond_lazy_load_javascript() . '</script>';
		
		$this->twosecond_insert_content_head( $head_content, 2, $insertLink );
	}

	/**
	 * Handle critical CSS insertion
	 *
	 * @param array $criticalReplace Critical CSS replacement data
	 */
	private function twosecond_handle_critical_css_insertion( $criticalReplace ) {
		$this->twosecond_insert_content_head( "\n" . '{{main_twosecond_critical_css}}', 2, '<style id="twosecond-bg-load">' );
		$this->html = str_replace( $criticalReplace[0], $criticalReplace[1], $this->html );
	}

	/**
	 * Insert lazy load script
	 *
	 * Inserts the lazy loading JavaScript before the closing body tag.
	 */
	private function twosecond_insert_lazy_load_script() {
		$position = strrpos( $this->html, '</body>' );
		if ( $position ) {
			$this->html = substr_replace( 
				$this->html, 
				'<script>' . $this->twosecond_lazy_load_images() . '</script>', 
				$position, 
				0 
			);
		} else {
			$this->html .= '<script>' . $this->twosecond_lazy_load_images() . '</script>';
		}
	}
}