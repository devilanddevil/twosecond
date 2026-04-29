<?php
/**
 * Database Class
 *
 * @package TwoSecond
 */


namespace TwoSecond;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Class
 */
class Database {

	/**
	 * Create all required database tables
	 */
	public function twosecond_create_tables() {
		$this->twosecond_create_web_vitals_table();
		$this->twosecond_create_change_log_table();
		$this->twosecond_create_site_urls_table();
	}

	/**
	 * Delete old web vitals table (legacy)
	 */
	public function twosecond_delete_web_vitals_table() {
		$table_name   = $this->twosecond_get_prefix() . 'twosecond_core_web_vitals';
		$table_exists = $this->twosecond_get_var(
			$this->twosecond_prepare( 'SHOW TABLES LIKE %s', array( $table_name ) )
		);

		if ( $table_exists === $table_name ) {
			$sql = $this->twosecond_prepare( 'DROP TABLE IF EXISTS %i', array( $table_name ) );
			$this->twosecond_query( $sql );
		}
	}

	/**
	 * Get charset collate
	 *
	 * @return string Charset collate
	 */
	public function twosecond_get_charset_collate() {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}

	/**
	 * Create web vitals table
	 */
	public function twosecond_create_web_vitals_table() {
		$table_name = $this->twosecond_get_prefix() . 'twosecond_core_web_vitals';

		if ( $this->twosecond_get_var( $this->twosecond_prepare( 'SHOW TABLES LIKE %s', array( $table_name ) ) ) !== $table_name ) {
			$charset_collate = $this->twosecond_get_charset_collate();
			$sql             = $this->twosecond_prepare(
				"CREATE TABLE %i (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				url varchar(255) NOT NULL,
				issuetype varchar(50) NOT NULL,
				data text NOT NULL,
				deviceType varchar(255) NOT NULL,
				timestamp datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id)
			) $charset_collate;",
				array( $table_name )
			);

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		} else {
			// Check if the column 'deviceType' exists.
			$device_type_exists = $this->twosecond_get_var(
				$this->twosecond_prepare(
					'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					array( $table_name, 'deviceType' )
				)
			);

			if ( 'deviceType' !== $device_type_exists ) {
				$sql = $this->twosecond_prepare(
					'ALTER TABLE %i ADD COLUMN deviceType VARCHAR(255) NOT NULL',
					array( $table_name )
				);
				$this->twosecond_query( $sql );
			}
		}
	}

	/**
	 * Create change log table
	 */
	public function twosecond_create_change_log_table() {
		$table_name = $this->twosecond_get_prefix() . 'twosecond_change_logs';

		if ( $this->twosecond_get_var( $this->twosecond_prepare( 'SHOW TABLES LIKE %s', array( $table_name ) ) ) !== $table_name ) {
			$charset_collate = $this->twosecond_get_charset_collate();
			$sql             = $this->twosecond_prepare(
				"CREATE TABLE %i (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT CURRENT_TIMESTAMP,
				user varchar(255) NOT NULL,
				ip varchar(45) NOT NULL,
				action text NOT NULL,
				old text NOT NULL,
				new text NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;",
				array( $table_name )
			);

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Create site URLs table
	 */
	public function twosecond_create_site_urls_table() {
		$table_name = $this->twosecond_get_prefix() . 'twosecond_site_urls';

		if ( $this->twosecond_get_var( $this->twosecond_prepare( 'SHOW TABLES LIKE %s', array( $table_name ) ) ) !== $table_name ) {
			$charset_collate = $this->twosecond_get_charset_collate();

			$sql = $this->twosecond_prepare(
				"CREATE TABLE %i (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				url text NOT NULL,
				status TINYINT(1) NOT NULL DEFAULT 0,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY status (status),
				KEY idx_updated_at (updated_at)
			) $charset_collate;",
				array( $table_name )
			);

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Drop all plugin tables
	 */
	public function twosecond_drop_tables() {
		$tables = array(
			$this->twosecond_get_prefix() . 'twosecond_change_logs',
			$this->twosecond_get_prefix() . 'twosecond_core_web_vitals',
			$this->twosecond_get_prefix() . 'twosecond_site_urls',
		);

		foreach ( $tables as $table ) {
			$this->twosecond_query( $this->twosecond_prepare( 'DROP TABLE IF EXISTS %i', array( $table ) ) );
		}
	}

	/**
	 * Get table name with prefix
	 *
	 * @param string $table_name Table name without prefix.
	 * @return string
	 */
	public function get_table_name( $table_name ) {
		return $this->twosecond_get_prefix() . $table_name;
	}

	/**
	 * Insert change log entry
	 *
	 * @param array $data Data to insert.
	 * @return int|false Number of rows inserted or false on failure.
	 */
	public function insert_change_log_entry( $data ) {
		$table_name = $this->get_table_name( 'twosecond_change_logs' );

		$sql = $this->twosecond_prepare(
			'INSERT INTO %i (user, ip, action, old, new) VALUES (%s, %s, %s, %s, %s)',
			array(
				$table_name,
				$data['user'],
				$data['ip'],
				$data['action'],
				$data['old'],
				$data['new'],
			)
		);

		return $this->twosecond_query( $sql );
	}

	/**
	 * Query
	 *
	 * Execute a database query. IMPORTANT: Query must be prepared using twosecond_prepare() before calling this method.
	 *
	 * @param string $query Query (already prepared via twosecond_prepare).
	 * @return mixed Result.
	 */
	public function twosecond_query( $query ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query must be prepared by calling code using twosecond_prepare(). This is a wrapper method; caching should be implemented at business logic layer, not for writes/schema changes.
		return $wpdb->query( $query );
	}

	/**
	 * Get results
	 *
	 * @param string $query Query (already prepared via twosecond_prepare).
	 * @param string $options Output type.
	 * @return mixed Result.
	 */
	public function twosecond_get_results( $query, $options = 'OBJECT' ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query must be prepared by calling code using twosecond_prepare(). This is a wrapper method; caching should be implemented at business logic layer based on specific use case.
		return $wpdb->get_results( $query, $options );
	}

	/**
	 * Get a single value
	 *
	 * @param string $query Query (already prepared via twosecond_prepare).
	 * @return mixed Result.
	 */
	public function twosecond_get_var( $query ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query must be prepared by calling code using twosecond_prepare(). This is a wrapper method; caching should be implemented at business logic layer based on specific use case.
		return $wpdb->get_var( $query );
	}

	/**
	 * Get row
	 *
	 * @param string $query Query (already prepared via twosecond_prepare).
	 * @param mixed  $args  Optional arguments.
	 * @return mixed Result.
	 */
	public function twosecond_get_row( $query, $args = array() ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query must be prepared by calling code using twosecond_prepare(). This is a wrapper method; caching should be implemented at business logic layer based on specific use case.
		return $wpdb->get_row( $query, $args );
	}

	/**
	 * Get column
	 *
	 * @param string $query Query (already prepared via twosecond_prepare).
	 * @return mixed Result.
	 */
	public function twosecond_get_col( $query ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query must be prepared by calling code using twosecond_prepare(). This is a wrapper method; caching should be implemented at business logic layer based on specific use case.
		return $wpdb->get_col( $query );
	}

	/**
	 * Prepare query
	 *
	 * This method IS the preparation wrapper. It receives a query with placeholders
	 * (e.g., %s, %d, %i) and safely binds the arguments using $wpdb->prepare().
	 *
	 * @param string $query Query with placeholders (%s, %d, %i, etc).
	 * @param array  $args  Arguments to bind to placeholders.
	 * @return string Prepared query.
	 */
	public function twosecond_prepare( $query, $args = array() ) {
		global $wpdb;

		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- This IS the prepare method, $query contains placeholders.
			return $wpdb->prepare( $query, ...$args );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- This IS the prepare method, $query contains placeholders.
		return $wpdb->prepare( $query );
	}

	/**
	 * Get table prefix
	 *
	 * @return string Table prefix
	 */
	public function twosecond_get_prefix() {
		global $wpdb;

		return $wpdb->prefix;
	}

	/**
	 * Escape value for LIKE queries
	 *
	 * @param string $string String value.
	 * @return string Escaped string.
	 */
	public function twosecond_esc_like( $string ) {
		global $wpdb;

		return $wpdb->esc_like( $string );
	}
}

