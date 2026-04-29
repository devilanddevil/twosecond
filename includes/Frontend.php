<?php
/**
 * Frontend Class
 *
 * @package TwoSecond
 */

namespace TwoSecond;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class
 */
class Frontend {

	/**
	 * Core instance
	 *
	 * @var Core
	 */
	public $core;

	private $twoSecondOptimized;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->core = new \TwoSecond\TwoSecond();
		$this->core->twosecond_init_hooks();
		$this->twosecond_init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function twosecond_init_hooks() {
		add_action( 'admin_bar_menu', array( 'TwoSecond\Admin\Admin_Bar', 'add_cache_purge_node' ), 999 );
		add_action( 'wp_enqueue_scripts', array( 'TwoSecond\Admin\Admin_Bar', 'enqueue_admin_bar_assets' ) );
		add_action( 'admin_enqueue_scripts', array( 'TwoSecond\Admin\Admin_Bar', 'enqueue_admin_bar_assets' ) );
		add_action( 'init', array( $this, 'twosecond_serve_cache' ), 1 );
		add_action( 'after_setup_theme', array( $this, 'twosecond_pre_start' ), 11 );
	}

	/**
	 * Pre start optimization
	 */
	public function twosecond_pre_start() {
		add_action('template_redirect', array($this, 'twosecond_start'), -1);
		add_action('wp', array($this, 'twosecond_start'), -1);
		add_action('wp_loaded', array($this, 'twosecond_start'), -1);
		add_action('init', array($this, 'twosecond_start'), -1);
	}

	/**
	 * Start optimization
	 */
	public function twosecond_start() {
		global $current_user;

		if(!empty($this->twoSecondOptimized)){
			return;
		}
		
		$this->twoSecondOptimized = true;
		if ( ! empty( $current_user ) && current_user_can( 'edit_others_pages' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'twosecond_enqueue_scripts' ) );
			return;
		}

		$twoSecond_optimize = new \TwoSecond\HtmlOptimize();
		$twoSecond_optimize->twosecond_start_optimization_callback();
	}

	/**
	 * Enqueue scripts
	 */
	public function twosecond_enqueue_scripts() {
		wp_enqueue_script(
			'twosecond-admin-bar-script',
			TWOSECOND_URL . 'assets/js/ts-admin-bar.js',
			array( 'jquery' ),
			TWOSECOND_VERSION,
			true
		);
	}

	/**
	 * Serve HTML cache from PHP if available
	 */
	public function twosecond_serve_cache() {
		try {
			// Skip if caching is disabled
			if ( empty( $this->core->settings['html_caching'] ) || $this->core->settings['html_caching'] !== 'on' ) {
				return;
			}

			// Skip for admin, POST requests, or AJAX
			if ( is_admin() || ! empty( $_POST ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}

			// Skip if user is logged in and logged-in caching is disabled
			if ( empty( $this->core->settings['enable_loggedin_user_caching'] ) && is_user_logged_in() ) {
				return;
			}
			
			$isMobile = $this->core->twosecond_is_mobile_device();
			$url = $this->core->addSettings['full_url'] ?? '';
			
			if ( empty( $url ) ) {
				return;
			}

			$cacheFilePath = $this->core->twosecond_get_html_cache_file_path( $url, $isMobile );
			
			if ( $cacheFilePath && @file_exists( $cacheFilePath ) && ! @is_dir( $cacheFilePath ) ) {
				$expiryTime = ( ! empty( $this->core->settings['html_caching_expiry_time'] ) ? (int)$this->core->settings['html_caching_expiry_time'] : 3600 );
				if ( ( time() - @filemtime( $cacheFilePath ) ) < $expiryTime ) {
					// Clean all existing output buffers to prevent duplicate data
					while ( ob_get_level() > 0 ) {
						@ob_end_clean();
					}
					
					// Add header and serve
					header( 'X-TwoSecond-Cache: Hit (PHP)' );
					@readfile( $cacheFilePath );
					exit;
				}
			}

			// If we are here, cache was missed. Let's add a debug note that will appear in the source later
			add_action( 'wp_footer', function() use ( $cacheFilePath ) {
				echo "\n<!-- TwoSecond Cache Miss | Checked Path: " . esc_html( $cacheFilePath ) . " -->";
			}, 9999 );

		} catch ( \Exception $e ) {
			// Silently fail to not break the site
		}
	}
}
