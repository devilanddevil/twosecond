<?php
/**
 * Admin Bar Class
 *
 * @package TwoSecond
 */

namespace TwoSecond\Admin;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Bar Class
 */
class Admin_Bar {

	/**
	 * Add cache purge node to admin bar
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public static function add_cache_purge_node( $wp_admin_bar ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'twosecond_settings' ) ) {
			return;
		}

		$filesize = round( get_option( 'twosecond_filesize', false ), 2 );
		$clear_cache_text = '';
		$clear_cache_id = '';

		if ( is_page() ) {
			global $post;
			$clear_cache_text = 'page';
			$clear_cache_id = $post->ID;
		} elseif ( is_single() ) {
			global $post;
			$clear_cache_text = 'post';
			$clear_cache_id = $post->ID;
		} elseif ( is_archive() || is_category() ) {
			$clear_cache_text = 'category';
			$clear_cache_id = get_queried_object_id();
		} elseif ( is_admin() ) {
			global $post;
			if ( isset( $post ) && is_object( $post ) && ! empty( $post->ID ) ) {
				$post_type = get_post_type( $post->ID );
				if ( $post_type ) {
					$clear_cache_text = $post_type;
					$clear_cache_id = $post->ID;
				}
			}
		}

		$args = array(
			'id'    => 'twosecond_purge_cache',
			'title' => self::get_cache_purge_title( $filesize, $clear_cache_text, $clear_cache_id ),
			'href'  => '#',
			'meta'  => array( 'class' => 'wp-twosecond-page' ),
		);

		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Get cache purge title HTML
	 *
	 * @param float  $filesize         Cache file size.
	 * @param string $clear_cache_text Cache type text.
	 * @param int    $clear_cache_id   Cache ID.
	 * @return string
	 */
	private static function get_cache_purge_title( $filesize, $clear_cache_text, $clear_cache_id ) {
		$title = '<div class="twosecond-spinner-container">';
		$title .= '<div id="twosecond_cache_purge"></div>';
		$title .= '</div>';
		$title .= self::get_cache_purge_styles();
		$title .= '<div class="twosecond-cache">' . __( 'TwoSecond cache', 'twosecond' ) . '</div>';
		$title .= '<div class="cache_size">';
		$title .= '<div class="twosecond-cache-purge-text" data-cache="jscss" data-id="0">' . __( 'Delete js/css cache for all pages', 'twosecond' ) . '</div>';
		$title .= '<div class="twosecond-cache-purge-text" data-cache="html" data-type="0" data-id="0">' . __( 'Delete html cache for all pages', 'twosecond' ) . '</div>';
		
		if ( ! empty( $clear_cache_text ) ) {
			/* translators: %s: content type (e.g. page, post, category, or post type) */
			$title .= '<div class="twosecond-cache-purge-text" data-cache="html" data-type="' . esc_attr( $clear_cache_text ) . '" data-id="' . esc_attr( $clear_cache_id ) . '">' . sprintf( __( 'Delete html cache for this %s only', 'twosecond' ), esc_attr( $clear_cache_text ) ) . '</div>';
			/* translators: %s: content type (e.g. page, post, category, or post type) */
			$title .= '<div class="twosecond-critical-cache-purge-single-text" data-type="' . esc_attr( $clear_cache_text ) . '" data-id="' . esc_attr( $clear_cache_id ) . '">' . sprintf( __( 'Delete critical css for this %s only', 'twosecond' ), esc_attr( $clear_cache_text ) ) . '</div>';
		}
		
		$title .= '<div><span>' . __( 'File Size', 'twosecond' ) . '</span>&nbsp;&nbsp;&nbsp;<span class="cache_folder_size">' . $filesize . '&nbsp;MB</span></div></div>';
		$title .= self::get_cache_purge_script();

		return $title;
	}

	/**
	 * Enqueue admin bar styles and scripts
	 */
	public static function enqueue_admin_bar_assets() {
		if ( ! is_user_logged_in() || ! current_user_can( 'twosecond_settings' ) ) {
			return;
		}

		// Ensure admin-bar style is enqueued
		if ( ! wp_style_is( 'admin-bar', 'enqueued' ) ) {
			wp_enqueue_style( 'admin-bar' );
		}

		// Ensure admin-bar script is enqueued for inline script
		if ( ! wp_script_is( 'admin-bar', 'enqueued' ) ) {
			wp_enqueue_script( 'admin-bar' );
		}

		// Enqueue inline styles for admin bar
		$admin_bar_css = '
			#wp-admin-bar-twosecond_purge_cache{min-width:135px;}
			.wp-twosecond-page .cache_size{display:none;}
			.cache_size.deleting{display:none!important;}
			.cache_size{position:absolute!important;color:#fff!important;background-color:#000;background: #000;min-width: 250px;}
			.cache_size div{padding: 2.5px 5px !important;}
			.cache_size:hover, .twosecond-cache:hover + .cache_size{display:block;}
			.twosecond-cache + .cache_size div:hover{background-color:#23282dcf;}
			.twosecond-cache{display: inline-block;vertical-align: top;}
		';
		wp_add_inline_style( 'admin-bar', $admin_bar_css );

		// Enqueue inline script for admin bar
		$config = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonces' => array(
				'purge_cache' => wp_create_nonce( 'purge_cache' ),
				'purge_critical_css' => wp_create_nonce( 'purge_critical_css' ),
				'purge_html_cache' => wp_create_nonce( 'purge_html_cache' ),
			),
			'texts' => array(
				'deleting' => __( 'Deleting...', 'twosecond' ),
				'cacheDeleted' => __( 'Cache Deleted!', 'twosecond' ),
				'tryAgain' => __( 'try again', 'twosecond' ),
				'cacheLabel' => __( 'TwoSecond cache', 'twosecond' ),
				'confirmCritical' => __( 'Are you sure you want to proceed? Critical css may take long time to regenerate.', 'twosecond' ),
			),
		);
		wp_add_inline_script( 'admin-bar', 'window.twoSecondAdminBar = ' . wp_json_encode( $config ) . ';' );
	}

	/**
	 * Get cache purge styles
	 * 
	 * @deprecated Use enqueue_admin_bar_assets() instead
	 * @return string Empty string (styles are now enqueued)
	 */
	private static function get_cache_purge_styles() {
		return '';
	}

	/**
	 * Get cache purge script
	 * 
	 * @deprecated Use enqueue_admin_bar_assets() instead
	 * @return string Empty string (scripts are now enqueued)
	 */
	private static function get_cache_purge_script() {
		return '';
	}
}
