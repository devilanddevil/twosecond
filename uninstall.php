<?php
/**
 * Uninstall TwoSecond Plugin
 *
 * @package TwoSecond
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Include the main plugin file to access classes
require_once plugin_dir_path( __FILE__ ) . 'includes/Database.php';

// Clean up database tables
$twoSecond_database = new \TwoSecond\Database();
$twoSecond_database->twosecond_drop_tables();
// Initialize WP Filesystem API
require_once ABSPATH . 'wp-admin/includes/file.php';
WP_Filesystem();
global $wp_filesystem;
// Clean up options
$twoSecond_options_to_delete = array(
	'twosecond_filesize',
	'twosecond_option',
	'twosecond_cron_jobs',
	'twosecond_rand_key',
	'twosecond_errors',
	'twosecond_css_urls',
	'twosecond_permission_errors',
);

foreach ( $twoSecond_options_to_delete as $twoSecond_option ) {
	delete_option( $twoSecond_option );
	delete_site_option( $twoSecond_option );
}



// Clear scheduled hooks
$twoSecond_hooks_to_clear = array(
	'twosecond_cron_event',
	'twosecond_cache_size',
	'twosecond_preload_css_min',
	'twosecond_image_optimization',
	'twosecond_check_key',
	'twosecond_preload_cache_cron_job',
	'twosecond_check_cron_needs_running',
	'twosecond_delete_logs',
);

foreach ( $twoSecond_hooks_to_clear as $twoSecond_hook ) {
	if ( wp_next_scheduled( $twoSecond_hook ) ) {
		wp_clear_scheduled_hook( $twoSecond_hook );
	}
}

// Clean up cache directories
$twoSecond_upload_dir = wp_upload_dir();
$twoSecond_cache_directories = array(
	$twoSecond_upload_dir['basedir'] . '/ts-cache',
);

foreach ( $twoSecond_cache_directories as $twoSecond_dir ) {
	if ( is_dir( $twoSecond_dir ) ) {
		// Recursively delete directory contents
		$twoSecond_files = glob( $twoSecond_dir . '/*' );
		foreach ( $twoSecond_files as $twoSecond_file ) {
			if ( is_file( $twoSecond_file ) ) {
				wp_delete_file( $twoSecond_file );
			} elseif ( is_dir( $twoSecond_file ) ) {
				// Recursively delete subdirectories via WP Filesystem
				if ( $wp_filesystem ) {
					$wp_filesystem->delete( $twoSecond_file, true, 'd' );
				} else {
					$twoSecond_sub_files = glob( $twoSecond_file . '/*' );
					foreach ( $twoSecond_sub_files as $twoSecond_sub_file ) {
						if ( is_file( $twoSecond_sub_file ) ) {
							wp_delete_file( $twoSecond_sub_file );
						}
					}
					if ( $wp_filesystem ) {
						$wp_filesystem->delete( $twoSecond_file, false, 'd' );
					}
				}
			}
		}
		if ( $wp_filesystem ) {
			$wp_filesystem->delete( $twoSecond_dir, false, 'd' );
		} 
	}
}

// Clean up .htaccess modifications if they exist
$twoSecond_htaccess_file = ABSPATH . '.htaccess';
if ( $wp_filesystem && $wp_filesystem->exists( $twoSecond_htaccess_file ) && $wp_filesystem->is_writable( $twoSecond_htaccess_file ) ) {
	$twoSecond_htaccess_content = $wp_filesystem->get_contents( $twoSecond_htaccess_file );
	
	// Remove TwoSecond specific rules
	$twoSecond_patterns = array(
		'# BEGIN TwoSecond.*?END TwoSecond\n?s#',
		'# TwoSecond.*?Cache.*?\n#',
		'# TwoSecond.*?Gzip.*?\n#',
		'# TwoSecond.*?Browser.*?Cache.*?\n#',
	);
	
	foreach ( $twoSecond_patterns as $twoSecond_pattern ) {
		$twoSecond_htaccess_content = preg_replace( $twoSecond_pattern, '', $twoSecond_htaccess_content );
	}
	
	// Clean up multiple blank lines
	$twoSecond_htaccess_content = preg_replace( '/\n\s*\n/', "\n\n", $twoSecond_htaccess_content );
	
	$wp_filesystem->put_contents( $twoSecond_htaccess_file, $twoSecond_htaccess_content, FS_CHMOD_FILE );
}

// Clean up wp-config.php modifications if they exist
$twoSecond_wp_config_file = ABSPATH . 'wp-config.php';
if ( $wp_filesystem && $wp_filesystem->exists( $twoSecond_wp_config_file ) && $wp_filesystem->is_writable( $twoSecond_wp_config_file ) ) {
	$twoSecond_wp_config_content = $wp_filesystem->get_contents( $twoSecond_wp_config_file );
	
	// Remove TwoSecond specific constants
	$twoSecond_patterns = array(
		"/\n\/\* TwoSecond.*?\*\/\n/",
		"/\ndefine\s*\(\s*'WP_CACHE'.*?\);\s*\n/",
	);
	
	foreach ( $twoSecond_patterns as $twoSecond_pattern ) {
		$twoSecond_wp_config_content = preg_replace( $twoSecond_pattern, "\n", $twoSecond_wp_config_content );
	}
	
	$wp_filesystem->put_contents( $twoSecond_wp_config_file, $twoSecond_wp_config_content, FS_CHMOD_FILE );
}

// Remove user capabilities
$role = get_role( 'administrator' );
if ( $role ) {
	$role->remove_cap( 'twosecond_settings' );
}

// Clean up any remaining files in wp-content
$twoSecond_advanced_cache = WP_CONTENT_DIR . '/twosecond-advanced-cache.php';
if ( $wp_filesystem && $wp_filesystem->exists( $twoSecond_advanced_cache ) ) {
	$wp_filesystem->delete( $twoSecond_advanced_cache, false, 'f' );
}
