<?php
/**
 * Main Plugin Class
 *
 * @package TwoSecond
 */

namespace TwoSecond;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 */
final class Plugin {

	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Admin class instance
	 *
	 * @var \TwoSecond\Admin\Admin
	 */
	private $admin;

	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	private $twosecond_database;
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->twosecond_database = new \TwoSecond\Database();
		$this->twosecond_init_hooks();
		$this->init_components();
	}

	/**
	 * Initialize hooks
	 */
	private function twosecond_init_hooks() {
		// Load text domain
		
		// Initialize components
		add_action( 'init', array( $this, 'init_core' ) );
		add_action( 'init', array( $this, 'init_admin' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_twosecond_ajax_call', array( $this, 'handle_ajax_call' ) );
		add_action( 'wp_ajax_nopriv_twosecond_ajax_call', array( $this, 'handle_ajax_call' ) );
		add_action( 'wp_ajax_twosecond_optimize_page', array( $this, 'handle_optimize_page' ) );
		add_action( 'wp_ajax_nopriv_twosecond_optimize_page', array( $this, 'handle_optimize_page' ) );
		add_action( 'wp_ajax_twosecond_reset_single_page', array( $this, 'handle_reset_single_page' ) );
		add_action( 'wp_ajax_nopriv_twosecond_reset_single_page', array( $this, 'handle_reset_single_page' ) );
		add_action( 'wp_ajax_twosecond_restart_optimization', array( $this, 'handle_restart_optimization' ) );
		add_action( 'wp_ajax_nopriv_twosecond_restart_optimization', array( $this, 'handle_restart_optimization' ) );
		add_action( 'wp_ajax_twosecond_insert_site_urls', array( $this, 'twosecond_handle_insert_site_urls' ) );
		add_action( 'wp_ajax_nopriv_twosecond_insert_site_urls', array( $this, 'twosecond_handle_insert_site_urls' ) );
		
		// Cache purge AJAX handlers
		add_action( 'wp_ajax_twosecond_cache_purge', array( $this, 'twosecond_handle_cache_purge' ) );
		add_action( 'wp_ajax_twosecond_critical_cache_purge', array( $this, 'twosecond_handle_critical_cache_purge' ) );
		add_action( 'wp_ajax_twosecond_html_cache_purge', array( $this, 'twosecond_handle_html_cache_purge' ) );
		// License activation
		add_action( 'wp_ajax_twosecond_activate_license_key', array( $this, 'twosecond_handle_license_activation' ) );
		
		
		// Web vitals logging
		add_action( 'wp_ajax_nopriv_twosecond_put_data', array( $this, 'twosecond_handle_web_vitals_data' ) );
		add_action( 'wp_ajax_twosecond_put_data', array( $this, 'twosecond_handle_web_vitals_data' ) );
		add_action( 'wp_ajax_twosecond_get_log_data', array( $this, 'twosecond_handle_get_log_data' ) );
		
		add_action( 'wp_ajax_twosecond_get_change_log_data', array( $this, 'twosecond_handle_get_change_log_data' ) );
		add_action( 'wp_ajax_twosecond_delete_change_log_data', array( $this, 'twosecond_handle_delete_change_log_data' ) );
		add_action( 'wp_ajax_twosecond_delete_log_data', array( $this, 'twosecond_handle_delete_log_data' ) );
		add_action( 'wp_ajax_twosecond_show_url_suggestions', array( $this, 'twosecond_handle_show_url_suggestions' ) );
		
		// Plugin data deletion
		add_action( 'wp_ajax_twosecond_delete_plugin_data', array( $this, 'twosecond_handle_delete_plugin_data' ) );
		add_action( 'wp_ajax_nopriv_twosecond_delete_plugin_data', array( $this, 'twosecond_handle_delete_plugin_data' ) );
		
		// Post update cache clearing
		add_action( 'save_post', array( $this, 'twosecond_clear_cache_on_post_update' ) );
		
		// Plugin action links
		add_filter( 'plugin_action_links_' . TWOSECOND_BASENAME, array( $this, 'add_action_links' ) );
		
		// Deactivation script
		add_action( 'admin_enqueue_scripts', array( $this, 'twosecond_enqueue_deactivation_script' ) );

		// Import/Export settings (admin-post)
		add_action( 'admin_post_twosecond_export_settings', array( $this, 'twosecond_handle_export_settings' ) );
		add_action( 'admin_post_twosecond_import_settings', array( $this, 'twosecond_handle_import_settings' ) );
	}

	/**
	 * Initialize components
	 */
	private function init_components() {
		// Initialize frontend functionality
		if ( ! is_admin() ) {
			$this->admin = new \TwoSecond\Frontend();
		}else{
			$this->admin = new \TwoSecond\Admin\Admin();
		}
	}

	/**
	 * Initialize core
	 */
	public function init_core() {
		$this->init_cron_jobs();
		
		// Initialize admin bar
		if ( ! is_admin() ) {
			add_action( 'admin_bar_menu', array( 'TwoSecond\Admin\Admin_Bar', 'add_cache_purge_node' ), 999 );
			add_action( 'wp_enqueue_scripts', array( 'TwoSecond\Admin\Admin_Bar', 'enqueue_admin_bar_assets' ) );
		}
	}

	/**
	 * Initialize admin
	 */
	public function init_admin() {
		if ( current_user_can( 'twosecond_settings' ) ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( $this->admin, 'add_network_menu' ) );
			}
			add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );
			add_action( 'admin_bar_menu', array( 'TwoSecond\Admin\Admin_Bar', 'add_cache_purge_node' ), 999 );
			add_action( 'admin_enqueue_scripts', array( 'TwoSecond\Admin\Admin_Bar', 'enqueue_admin_bar_assets' ) );
		}
	}

	/**
	 * Initialize cron jobs
	 */
	private function init_cron_jobs() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		$this->schedule_cron_event();
		add_action( 'twosecond_cron_event', array( $this, 'handle_cron_event' ) );
	}

	/**
	 * Add cron schedule
	 *
	 * @param array $schedules Cron schedules.
	 * @return array
	 */
	public function add_cron_schedule( $schedules ) {
		$schedules['twosecond_every_minute'] = array(
			'interval' => 60,
			'display'  => __( 'TwoSecond Every Minute Cron', 'twosecond' ),
		);
		return $schedules;
	}

	/**
	 * Schedule cron event
	 */
	public function schedule_cron_event() {
		if ( ! wp_next_scheduled( 'twosecond_cron_event' ) ) {
			wp_schedule_event( time(), 'twosecond_every_minute', 'twosecond_cron_event' );
		}
	}

	/**
	 * Handle cron event
	 */
	public function handle_cron_event() {
		$cron_manager = new \TwoSecond\CronManager();
		$cron_manager->twosecond_run_cron_jobs();
		exit;
	}

	/**
	 * Add action links
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( add_query_arg( 'page', 'twosecond', admin_url( 'admin.php' ) ) ) . '">' . __( 'Settings', 'twosecond' ) . '</a>';
		return array_merge( array( $settings_link ), $links );
	}

	/**
	 * Handle AJAX call
	 */
	public function handle_ajax_call() {
		$this->admin->core->twosecond_save_data_with_ajax();
	}

	/**
	 * Handle optimize page
	 */
	public function handle_optimize_page() {
		if ( is_user_logged_in() && ! current_user_can( 'twosecond_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}
		// For non-logged-in users, require auth token in request
		if ( ! is_user_logged_in() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Auth token is used as security mechanism for non-logged-in access, verified via twosecond_check_security_key()
			$auth = isset( $_POST['auth'] ) ? sanitize_text_field( wp_unslash( $_POST['auth'] ) ) : '';
			if ( empty( $auth ) || ! $this->admin->core->twosecond_check_security_key( $auth, 'twosecond_ai_optimize' ) ) {
				wp_send_json_error( array( 'message' => 'Invalid authentication' ), 403 );
				exit;
			}
		}
		$this->admin->core->twosecond_optimize_with_ai();
		exit;
	}

	/**
	 * Handle reset single page
	 */
	public function handle_reset_single_page() {
		if ( is_user_logged_in() && ! current_user_can( 'twosecond_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}
		
		if ( ! is_user_logged_in() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Auth token is used as security mechanism for non-logged-in access, verified via twosecond_check_security_key()
			$auth = isset( $_POST['auth'] ) ? sanitize_text_field( wp_unslash( $_POST['auth'] ) ) : '';
			if ( empty( $auth ) || ! $this->admin->core->twosecond_check_security_key( $auth, 'twosecond_ai_optimize' ) ) {
				wp_send_json_error( array( 'message' => 'Invalid authentication' ), 403 );
				exit;
			}
		}
		$this->admin->core->twosecond_reset_single_page_optimization();
		exit;
	}

	/**
	 * Handle restart optimization
	 */
	public function handle_restart_optimization() {
		// For logged-in users, require capability
		if ( is_user_logged_in() && ! current_user_can( 'twosecond_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}

		$this->admin->core->twosecond_restart_optimization();
		exit;
	}

	/**
	 * Handle insert site URLs
	 */
	public function twosecond_handle_insert_site_urls() {
		if ( is_user_logged_in() && ! current_user_can( 'twosecond_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}

		$this->admin->core->twosecond_insert_site_urls();
		exit;
	}

	/**
	 * Handle cache purge
	 */
	public function twosecond_handle_cache_purge() {
		if ( ! current_user_can( 'twosecond_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}

		$this->admin->core->twosecond_log_settings_changes( array( array( 'action' => 'Js/Css Cache Flushed', 'old' => 'Cache', 'new' => 'Clear' ) ) );
		$this->admin->core->twosecond_cache_purge_callback();
		$this->admin->core->purge_all();
	}

	/**
	 * Handle critical cache purge
	 */
	public function twosecond_handle_critical_cache_purge() {
		if ( ! current_user_can( 'twosecond_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}

		$this->admin->core->twosecond_log_settings_changes( array( array( 'action' => 'Critical Css Cache Flushed', 'old' => 'Cache', 'new' => 'Clear' ) ) );
		$this->admin->core->twosecond_reset_optimization_status();
		$this->admin->core->twosecond_critical_cache_purge_callback();
		$this->admin->core->purge_all();
	}

	/**
	 * Handle HTML cache purge
	 */
	public function twosecond_handle_html_cache_purge() {
		if ( ! current_user_can( 'twosecond_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}
		$this->admin->core->twosecond_log_settings_changes( array( array( 'action' => 'Html Cache Flushed', 'old' => 'Cache', 'new' => 'Clear' ) ) );
		$this->admin->core->twosecond_html_cache_purge_callback();
		$this->admin->core->purge_all();
	}

	/**
	 * Handle export settings (download serialized file)
	 */
	public function twosecond_handle_export_settings() {

		if ( empty($_GET['_twosecond_nonce']) || ! wp_verify_nonce( sanitize_text_field( wp_unslash($_GET['_twosecond_nonce']) ), 'twosecond_settings_export' ) ) {
			wp_die( esc_html__( 'Invalid export request.', 'twosecond' ) );
		}

		if ( ! current_user_can( 'twosecond_settings' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'twosecond' ) );
		}

		$settings = $this->admin->core->twosecond_get_settings();
		$settings = is_array($settings) ? $settings : array();
		unset($settings['license_key'], $settings['is_activated']);
		$payload = serialize( $settings );
		
		// Sanitize filename to prevent header injection
		$hostname = sanitize_file_name( $this->admin->core->addSettings['site_url_array']['host'] );
		$filename = $hostname . '-' . gmdate('Y-m-d-H-i-s') . '.dat';
		
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . strlen($payload));
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Serialized binary data for download, escaping would corrupt it
		echo $payload;
		exit;
	}

	/**
	 * Handle import settings (upload serialized file)
	 */
	public function twosecond_handle_import_settings() {
		if ( ! current_user_can( 'twosecond_settings' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'twosecond' ) );
		}

		if ( empty($_POST['_twosecond_nonce']) || ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_twosecond_nonce']) ), 'twosecond_settings_import' ) ) {
			wp_die( esc_html__( 'Invalid import request.', 'twosecond' ) );
		}

		if ( empty($_FILES['twosecond_import_file']) || ! isset( $_FILES['twosecond_import_file']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'twosecond', 'import' => 'fail' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is a server-generated temporary file path, validated with is_uploaded_file() below
		$tmp_file = isset( $_FILES['twosecond_import_file']['tmp_name'] ) ? wp_unslash( $_FILES['twosecond_import_file']['tmp_name'] ) : '';
		if ( empty( $tmp_file ) || ! is_uploaded_file( $tmp_file ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'twosecond', 'import' => 'fail' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$raw = file_get_contents( $tmp_file );
		$raw = is_string( $raw ) ? trim( $raw ) : '';
		$data = false;

		if ( ! empty( $raw ) && function_exists( 'is_serialized' ) && is_serialized( $raw ) ) {
			$data = unserialize( $raw, array( 'allowed_classes' => false ) );
		} elseif ( ! empty( $raw ) ) {
			$decoded = base64_decode( $raw, true );
			if ( is_string( $decoded ) && function_exists( 'is_serialized' ) && is_serialized( $decoded ) ) {
				$data = unserialize( $decoded, array( 'allowed_classes' => false ) );
			}
		}

		if ( is_array( $data ) ) {
			$this->admin->core->twosecond_update_option( 'twosecond_option', $data, 'no' );
			wp_safe_redirect( add_query_arg( array( 'page' => 'twosecond', 'import' => 'success', 'tab' => 'import' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'twosecond', 'import' => 'fail', 'tab' => 'import' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handle license activation
	 */
	public function twosecond_handle_license_activation() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
			exit;
		}
		// Verify nonce
		if ( empty( $_GET['_twosecond_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_twosecond_nonce'] ) ), 'twoSecondActivateLicense' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			exit;
		}
		$this->admin->core->twosecond_activate_license_key();
	}

	/**
	 * Handle web vitals data
	 */
	public function twosecond_handle_web_vitals_data() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
			exit;
		}

		if ( empty( $_POST['_twosecond_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_twosecond_nonce'] ) ), 'cwvLog' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request' ), 400 );
			exit;
		}

		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_core_web_vitals';
		$data = array(
			'url' => (!empty($_POST['url']) ? sanitize_text_field( wp_unslash($_POST['url']) ) : ''),
			'issueType' => (!empty($_POST['issueType']) ? sanitize_text_field( wp_unslash($_POST['issueType']) ) : ''),
			'data' => (!empty($_POST['data']) ? sanitize_textarea_field( wp_unslash($_POST['data']) ) : ''),
			'deviceType' => (!empty($_POST['deviceType']) ? sanitize_text_field( wp_unslash($_POST['deviceType']) ) : ''),
		);

		$query = $this->twosecond_database->twosecond_prepare("INSERT INTO  %i (url, issueType, data, deviceType) VALUES (%s, %s, %s, %s)", [$table_name, $data['url'], $data['issueType'], $data['data'], $data['deviceType']]);
		if($this->twosecond_database->twosecond_query( $query )){
			wp_send_json_success('Data inserted successfully');
		}
		wp_send_json_error( array( 'message' => 'Error inserting data' ), 500 );
	}

	/**
	 * Handle get log data
	 */
	public function twosecond_handle_get_log_data() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
			exit;
		}
		$this->admin->core->twosecond_get_log_data();
		exit;
	}

	/**
	 * Handle get change log data
	 */
	public function twosecond_handle_get_change_log_data() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
			exit;
		}
		$this->admin->core->twosecond_get_change_log_data();
		exit;
	}

	/**
	 * Handle delete change log data
	 */
	public function twosecond_handle_delete_change_log_data() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
			exit;
		}
		
		if ( empty( $_POST['_twosecond_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_twosecond_nonce'] ) ), 'twosecond_change_logs' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			exit;
		}
		
		$table_name = $this->twosecond_database->twosecond_get_prefix() . 'twosecond_change_logs';
		$time_interval = ( ! empty( $_POST['time_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['time_interval'] ) ) : '' );

		// Build prepared query per interval
		switch ( $time_interval ) {
			case 'last7days':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE time < DATE_SUB(CURDATE(), INTERVAL %d DAY)",[$table_name, 7] );
				break;
			case 'lastMonth':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE time < DATE_SUB(CURDATE(), INTERVAL %d DAY)",[$table_name, 30] );
				break;
			case 'last3months':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE time < DATE_SUB(CURDATE(), INTERVAL %d MONTH)",[$table_name, 3] );
				break;
			case 'last6months':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE time < DATE_SUB(CURDATE(), INTERVAL %d MONTH)",[$table_name, 6] );
				break;
			case 'lastYear':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE time < DATE_SUB(CURDATE(), INTERVAL %d YEAR)",[$table_name, 1] );
				break;
			case 'all':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i",[$table_name] ); // no placeholders needed
				break;
			default:
				wp_send_json_error( array( 'message' => 'Invalid time interval' ), 500 );
				return;
		}

		$result = $this->twosecond_database->twosecond_query( $prepared );

		if ( false !== $result ) {
			$this->admin->core->twosecond_get_change_log_data();
		} else {
			wp_send_json_error( array( 'message' => 'Error deleting data' ), 500 );
		}
		exit;
	}

	/**
	 * Handle delete log data
	 */
	public function twosecond_handle_delete_log_data() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'twosecond_core_web_vitals';
		
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
			exit;
		}

		if ( empty( $_POST['_twosecond_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_twosecond_nonce'] ) ), 'twosecond_log' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			exit;
		}

		$time_interval = ( ! empty( $_POST['time_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['time_interval'] ) ) : '' );

		switch ( $time_interval ) {
			case 'last7days':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL %d DAY)", array( $table_name, 7 ) );
				break;
			case 'lastMonth':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL %d DAY)", array( $table_name, 30 ) );
				break;
			case 'last3months':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL %d MONTH)", array( $table_name, 3 ) );
				break;
			case 'last6months':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL %d MONTH)", array( $table_name, 6 ) );
				break;
			case 'lastYear':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL %d YEAR)", array( $table_name, 1 ) );
				break;
			case 'all':
				$prepared = $this->twosecond_database->twosecond_prepare( "DELETE FROM %i", array( $table_name ) );
				break;
			default:
				wp_send_json_error( array( 'message' => 'Invalid time interval' ), 403 );
				return;
		}
		$result = $this->twosecond_database->twosecond_query( $prepared );

		if ( $result !== false ) {
			$this->admin->core->twosecond_get_log_data();
		} else {
			wp_send_json_error( array( 'message' => 'Error deleting data' ), 403 );
		}
		exit;
	}

	/**
	 * Handle show URL suggestions
	 */
	public function twosecond_handle_show_url_suggestions() {
		global $wpdb;

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ), 403 );
			exit;
		}

		if ( empty( $_POST['_twosecond_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_twosecond_nonce'] ) ), 'twosecond_url_suggestions' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			exit;
		}
		$search_term = trim( (!empty($_POST['s_text']) ? sanitize_text_field( wp_unslash($_POST['s_text']) ) : '') );
		$search_query = '%' . $wpdb->esc_like( $search_term ) . '%';
		
		$table_name = $wpdb->prefix . 'twosecond_core_web_vitals';
		$results = $this->twosecond_database->twosecond_get_results(
			$this->twosecond_database->twosecond_prepare(
				'SELECT * FROM %i WHERE url LIKE %s GROUP BY url LIMIT %d',
				array( $table_name, $search_query, 10 )
			)
		);
		
		$url_array = array();
		foreach ( $results as $result ) {
			$url_array[] = $result->url;
		}
		
		wp_send_json( $url_array );
	}

	/**
	 * Handle delete plugin data
	 */
	public function twosecond_handle_delete_plugin_data() {
		global $wpdb;

		// Verify capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ), 403 );
			exit;
		}

		// Verify nonce
		if ( empty( $_POST['_twosecond_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_twosecond_nonce'] ) ), 'twosecond_delete_data' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			exit;
		}

		$this->twosecond_database->twosecond_query( $this->twosecond_database->twosecond_prepare( "DROP TABLE IF EXISTS %i", array( $wpdb->prefix . 'twosecond_change_logs' ) ) );
		$this->twosecond_database->twosecond_query( $this->twosecond_database->twosecond_prepare( "DROP TABLE IF EXISTS %i", array( $wpdb->prefix . 'twosecond_core_web_vitals' ) ) );
		$this->twosecond_database->twosecond_query( $this->twosecond_database->twosecond_prepare( "DROP TABLE IF EXISTS %i", array( $wpdb->prefix . 'twosecond_site_urls' ) ) );

		$this->admin->core->twosecond_rmdir( $this->admin->core->addSettings['rootCachePath'] );
		$this->admin->core->twosecond_rmdir( $this->admin->core->addSettings['criticalCssPath'] );
		$this->admin->core->twosecond_rmdir( $this->admin->core->addSettings['webp_path'] );

		$options = array(
			'twosecond_filesize',
			'twosecond_cron_jobs',
			'twosecond_rand_key',
			'twosecond_errors',
			'twosecond_css_urls',
			'twosecond_permission_errors',
		);

		foreach ( $options as $option ) {
			delete_option( $option );
			delete_site_option( $option );
		}
	}

	/**
	 * Clear cache on post update
	 *
	 * @param int $post_id Post ID.
	 * @return bool|false
	 */
	public function twosecond_clear_cache_on_post_update( $post_id ) {
		$parent_id = wp_is_post_revision( $post_id );
		if ( false !== $parent_id ) {
			return false;
		}
		return $this->admin->core->twosecond_check_html_cache_delete();
	}

	/**
	 * Enqueue deactivation script
	 *
	 * @param string $hook Current admin page.
	 */
	public function twosecond_enqueue_deactivation_script( $hook ) {
		if ( $hook === 'plugins.php' ) {
			wp_enqueue_style(
				'twosecond-admin-style',
				TWOSECOND_URL . 'assets/css/admin-style.css',
				array(),
				TWOSECOND_VERSION
			);
			wp_enqueue_script(
				'twosecond-deactivate',
				TWOSECOND_URL . 'assets/js/twosecond-deactivate.js',
				array( 'jquery' ),
				time(),
				true
			);
			wp_localize_script(
				'twosecond-deactivate',
				'twosecondDeactivate',
				array(
					'pluginUrl' => TWOSECOND_URL,
				)
			);
		}
	}
}
