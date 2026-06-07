<?php

namespace TwoSecond;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * TwoSecondCache Base Class
 * 
 * This class provides base cache purging functionality that works
 * with various WordPress hosting providers. Child classes can extend
 * this to add hosting-specific detection and methods.
 * 
 * @package TwoSecond
 */
class TwoSecondCache
{
    /**
     * Purges cache for a single URL
     * 
     * Detects the hosting provider and executes the appropriate
     * cache purge method for that provider.
     * 
     * @param string $url The URL to purge from cache
     * @return bool True on success, false on failure
     */
    public function purge_url($url)
    {
        $hosting = $this->get_hosting();
        
        switch ($hosting) {
            case "siteground":
                return $this->purge_url_site_ground($url);
            case "wpengine":
                return $this->purge_url_wp_engine($url);
            case "kinsta":
                return $this->purge_url_kinsta($url);
            case "pagely":
                return $this->purge_url_pagely($url);
            case "wpx":
                return $this->purge_url_wpx($url);
            case "pressable":
                return $this->purge_url_pressable($url);
            case "wpmudev":
                return $this->purge_url_wpmudev($url);
            case "cloudways":
                return $this->purge_url_cloudways($url);
            case "godaddy_wpaas":
                return $this->purge_url_go_daddy_wpaas($url);
            case "rocketnet":
                return $this->purge_url_rocket_net($url);
            case "savvii":
                return $this->purge_url_savvii($url);
            case "vimexx":
                return $this->purge_url_vimexx($url);
            case "dreamhost":
                return $this->purge_url_dream_host($url);
            case "flywheel":
                return false;
            case "closte":
                return false;
            case "raidboxes":
                return false;
            default:
                return false;
        }
    }

    /**
     * Purges all cache
     * 
     * Detects the hosting provider and executes the appropriate
     * full cache purge method for that provider.
     * 
     * @return bool True on success, false on failure
     */
    public function purge_all()
    {
        $hosting = $this->get_hosting();
        
        switch ($hosting) {
            case "siteground":
                return $this->purge_all_site_ground();
            case "wpengine":
                return $this->purge_all_wp_engine();
            case "kinsta":
                return $this->purge_all_kinsta();
            case "pagely":
                return $this->purge_all_pagely();
            case "wpx":
                return $this->purge_all_wpx();
            case "pressable":
                return $this->purge_all_pressable();
            case "wpmudev":
                return $this->purge_all_wpmudev();
            case "cloudways":
                return $this->purge_all_cloudways();
            case "godaddy_wpaas":
                return $this->purge_all_go_daddy_wpaas();
            case "rocketnet":
                return $this->purge_all_rocket_net();
            case "savvii":
                return $this->purge_all_savvii();
            case "vimexx":
                return $this->purge_all_vimexx();
            case "dreamhost":
                return $this->purge_all_dream_host();
            case "flywheel":
                // Flywheel doesn't have cache purge implementation
                return false;
            case "closte":
                // Closte doesn't have cache purge implementation
                return false;
            case "raidboxes":
                // Raidboxes uses file deletion, handled separately
                return false;
            default:
                return false;
        }
    }

    /**
     * Gets the hosting provider name
     * 
     * This method should be implemented by child classes.
     * Returns the hosting provider identifier.
     * 
     * @return string The hosting provider name
     */
    protected function get_hosting()
    {
        return "unknown";
    }

    /**
     * Gets the home URL
     * 
     * @return string The home URL
     */
    protected function get_home_url()
    {
        if (function_exists('home_url')) {
            return home_url();
        }
        return '';
    }

    /**
     * Purges URL for SiteGround hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_site_ground($url)
    {
        try {
            $urlParts = $this->twosecond_parse_url($url);
            $host = preg_replace("/^www\./", "", $urlParts['host']);
            $path = isset($urlParts['path']) ? $urlParts['path'] : '/';
            
            if (isset($urlParts['query'])) {
                $path .= "(.*)";
            }

            $sock_path = '/chroot/tmp/site-tools.sock';
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            WP_Filesystem();
            global $wp_filesystem;

            if (empty($wp_filesystem) || !$wp_filesystem->exists($sock_path)) {
                return false;
            }

            $sock = stream_socket_client('unix://' . $sock_path, $errno, $errstr, 5);
            if (false === $sock) {
                return false;
            }

            $req = array(
                'api' => 'domain-all',
                'cmd' => 'update',
                'settings' => array('json' => 1),
                'params' => array(
                    'flush_cache' => '1',
                    'id' => $host,
                    'path' => $path,
                ),
            );

            stream_socket_sendto($sock, json_encode($req, JSON_FORCE_OBJECT) . "\n");
            $response = fgets($sock, 32 * 1024);
            stream_socket_shutdown($sock, STREAM_SHUT_RDWR);
            $result = @json_decode($response, true);
            
            if (false === $result || isset($result['err_code'])) {
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for SiteGround hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_site_ground()
    {
        $homeUrl = $this->get_home_url();
        if ($homeUrl) {
            return $this->purge_url_site_ground(rtrim($homeUrl, '/') . '/(.*)');
        }
        return false;
    }

    /**
     * Purges URL for WP Engine hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_wp_engine($url)
    {
        try {
            if (!class_exists('\WpeCommon')) {
                return false;
            }

            $handler = function($paths) use($url) {
                $wpe_path = $this->twosecond_parse_url($url, PHP_URL_PATH);
                $wpe_query = $this->twosecond_parse_url($url, PHP_URL_QUERY);
                $varnish_path = $wpe_path;
                if (!empty($wpe_query)) {
                    $varnish_path .= '?' . $wpe_query;
                }
                if ($url && count($paths) == 1 && $paths[0] == ".*") {
                    return array($varnish_path);
                }
                return $paths;
            };
            
            if (function_exists('add_filter')) {
                add_filter('wpe_purge_varnish_cache_paths', $handler);
            }
            
            \WpeCommon::purge_varnish_cache();
            
            if (function_exists('remove_filter')) {
                remove_filter('wpe_purge_varnish_cache_paths', $handler);
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for WP Engine hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_wp_engine()
    {
        try {
            if (!class_exists('\WpeCommon')) {
                return false;
            }
            
            \WpeCommon::purge_varnish_cache();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for Kinsta hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_kinsta($url)
    {
        try {
            if (function_exists('kinsta_cache_purge_url')) {
                kinsta_cache_purge_url($url);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for Kinsta hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_kinsta()
    {
        try {
            if (function_exists('kinsta_cache_purge_all')) {
                kinsta_cache_purge_all();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for Pagely hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_pagely($url)
    {
        try {
            if (!class_exists('\PagelyCachePurge')) {
                return false;
            }
            
            $path = $this->twosecond_parse_url($url, PHP_URL_PATH);
            $pagely = new \PagelyCachePurge();
            $pagely->purgePath($path . "(.*)");
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for Pagely hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_pagely()
    {
        try {
            if (!class_exists('\PagelyCachePurge')) {
                return false;
            }
            
            $pagely = new \PagelyCachePurge();
            $pagely->purgeAll();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for WPX hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_wpx($url)
    {
        try {
            $urlParts = $this->twosecond_parse_url($url);
            $host = isset($urlParts['host']) ? $urlParts['host'] : '';
            $path = isset($urlParts['path']) ? $urlParts['path'] : '/';
            
            $purgeUrl = "http://" . $host . ":6081" . $path . ".*";
            
            return $this->send_varnish_purge($purgeUrl, array("127.0.0.1"), "PURGE");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for WPX hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_wpx()
    {
        try {
            $urlParts = $this->twosecond_parse_url($this->get_home_url());
            $host = isset($urlParts['host']) ? $urlParts['host'] : '';
            
            $purgeUrl = "http://" . $host . ":6081/.*";
            
            return $this->send_varnish_purge($purgeUrl, array("127.0.0.1"), "PURGE");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for Pressable hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_pressable($url)
    {
        global $batcache;
        
        if (!$batcache || !is_object($batcache)) {
            return false;
        }

        try {
            $urlParts = $this->twosecond_parse_url($url);
            $host = isset($urlParts['host']) ? $urlParts['host'] : '';
            $path = isset($urlParts['path']) ? $urlParts['path'] : '/';
            $query = array();
            
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $query);
            }

            // Remove ignored query args
            if (isset($batcache->ignored_query_args)) {
                foreach ($batcache->ignored_query_args as $arg) {
                    unset($query[$arg]);
                }
            }
            
            ksort($query);

            $keys = array(
                'host' => $host,
                'method' => "GET",
                'path' => $path,
                'query' => $query,
                'extra' => []
            );

            if (isset($batcache->origin)) {
                $keys['origin'] = $batcache->origin;
            }

            if (isset($urlParts['scheme']) && $urlParts['scheme'] == "https") {
                $keys['ssl'] = true;
            }

            if (function_exists('wp_cache_init')) {
                wp_cache_init();
            }
            
            if (method_exists($batcache, 'configure_groups')) {
                $batcache->configure_groups();
            }

            $deviceTypes = array("mobile", "tablet", "desktop");
            foreach ($deviceTypes as $deviceType) {
                $keys["extra"] = array($deviceType);
                $url_key = md5(serialize($keys));
                
                if (function_exists('wp_cache_delete')) {
                    wp_cache_delete($url_key, $batcache->group);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for Pressable hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_pressable()
    {
        if (function_exists('wp_cache_flush')) {
            return wp_cache_flush();
        }
        return false;
    }

    /**
     * Purges URL for WPMU DEV hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_wpmudev($url)
    {
        try {
            if (function_exists("wpmudev_hosting_purge_static_cache")) {
                $parts = $this->twosecond_parse_url($url);
                $path = isset($parts['path']) ? $parts['path'] : '/';
                return wpmudev_hosting_purge_static_cache($path);
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for WPMU DEV hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_wpmudev()
    {
        try {
            if (function_exists("wpmudev_hosting_purge_static_cache")) {
                return wpmudev_hosting_purge_static_cache();
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for Cloudways hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_cloudways($url)
    {
        try {
            return $this->send_varnish_purge($url, array("127.0.0.1"), "URLPURGE");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for Cloudways hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_cloudways()
    {
        try {
            $homeUrl = $this->get_home_url();
            if ($homeUrl) {
                $purgeUrl = rtrim($homeUrl, '/') . '/.*';
                return $this->send_varnish_purge($purgeUrl, array("127.0.0.1"), "PURGE");
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for GoDaddy WPaaS hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_go_daddy_wpaas($url)
    {
        try {
            if (!class_exists('\WPaaS\Plugin')) {
                return false;
            }
            
            if (function_exists('update_option')) {
                update_option('gd_system_last_cache_flush', time());
            }
            
            $hosts = array(\WPaaS\Plugin::vip());
            $url = preg_replace("/^https:\/\//", "http://", $url);
            
            return $this->send_varnish_purge($url, $hosts, 'BAN');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for GoDaddy WPaaS hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_go_daddy_wpaas()
    {
        $homeUrl = $this->get_home_url();
        if ($homeUrl) {
            return $this->purge_url_go_daddy_wpaas($homeUrl);
        }
        return false;
    }

    /**
     * Purges URL for Rocket.net hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_rocket_net($url)
    {
        try {
            if (class_exists("\CDN_Clear_Cache_Api")) {
                $urlParts = $this->twosecond_parse_url($url);
                $entry = isset($urlParts['path']) ? $urlParts['path'] : '/';
                
                if (isset($urlParts['query'])) {
                    $entry .= "?" . $urlParts['query'];
                }
                
                \CDN_Clear_Cache_Api::cache_api_call(array($entry), 'purge');
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for Rocket.net hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_rocket_net()
    {
        try {
            if (class_exists("\CDN_Clear_Cache_Hooks")) {
                \CDN_Clear_Cache_Hooks::purge_cache();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for Savvii hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_savvii($url)
    {
        try {
            $homeUrl = $this->get_home_url();
            if (!$homeUrl) {
                return false;
            }
            
            $urlParts = $this->twosecond_parse_url($url);
            $host = isset($urlParts['host']) ? $urlParts['host'] : '';
            $path = isset($urlParts['path']) ? $urlParts['path'] : '/';
            
            $purgeEndpoint = rtrim($homeUrl, '/') . '/purge';
            
            $response = wp_remote_request($purgeEndpoint, array(
                'method' => 'PURGE',
                'headers' => array(
                    'X-PURGE-HOST' => $host,
                    'X-PURGE-PATH-REGEX' => $path . '.*',
                ),
                'timeout' => 5,
                'sslverify' => false,
            ));
            
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 400;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for Savvii hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_savvii()
    {
        try {
            $homeUrl = $this->get_home_url();
            if (!$homeUrl) {
                return false;
            }
            
            $urlParts = $this->twosecond_parse_url($homeUrl);
            $host = isset($urlParts['host']) ? $urlParts['host'] : '';
            
            $purgeEndpoint = rtrim($homeUrl, '/') . '/purge';
            
            $response = wp_remote_request($purgeEndpoint, array(
                'method' => 'PURGE',
                'headers' => array(
                    'X-PURGE-HOST' => $host,
                ),
                'timeout' => 5,
                'sslverify' => false,
            ));
            
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 400;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for Vimexx hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_vimexx($url)
    {
        try {
            $response = wp_remote_request($url, array(
                'method' => 'PURGE',
                'headers' => array(
                    'X-Purge-ZXCS' => 'true',
                    'host-ZXCS' => isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '',
                ),
                'timeout' => 5,
                'sslverify' => false,
            ));
            
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 400;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for Vimexx hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_vimexx()
    {
        try {
            $homeUrl = $this->get_home_url();
            if ($homeUrl) {
                $purgeUrl = rtrim($homeUrl, '/') . '/?purgeAll';
                return $this->purge_url_vimexx($purgeUrl);
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges URL for DreamHost hosting
     * 
     * @param string $url The URL to purge
     * @return bool True on success, false on failure
     */
    private function purge_url_dream_host($url)
    {
        try {
            $urlParts = $this->twosecond_parse_url($url);
            $host = isset($urlParts['host']) ? $urlParts['host'] : '';
            $path = isset($urlParts['path']) ? $urlParts['path'] : '/';
            $purgeUrl = "https://" . $host . $path;
            
            $serverAddr = isset($_SERVER["SERVER_ADDR"]) ? sanitize_text_field(wp_unslash($_SERVER["SERVER_ADDR"])) : "127.0.0.1";
            
            if (isset($urlParts['query'])) {
                $purgeUrl .= ".*";
                return $this->send_varnish_purge($purgeUrl, array($serverAddr), "PURGE", array("x-purge-method" => "regex"));
            } else {
                return $this->send_varnish_purge($purgeUrl, array($serverAddr), "PURGE", array("x-purge-method" => "default"));
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Purges all cache for DreamHost hosting
     * 
     * @return bool True on success, false on failure
     */
    private function purge_all_dream_host()
    {
        try {
            $homeUrl = $this->get_home_url();
            if (!$homeUrl) {
                return false;
            }
            
            $purgeUrl = rtrim($homeUrl, '/') . '/.*';
            $serverAddr = isset($_SERVER["SERVER_ADDR"]) ? sanitize_text_field(wp_unslash($_SERVER["SERVER_ADDR"])) : "127.0.0.1";
            
            return $this->send_varnish_purge($purgeUrl, array($serverAddr), "PURGE", array("x-purge-method" => "regex"));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sends a Varnish purge request
     * 
     * @param string $url The URL to purge
     * @param array $hosts Array of host IPs
     * @param string $method The HTTP method (PURGE, BAN, URLPURGE)
     * @param array $headers Optional additional headers
     * @return bool True on success, false on failure
     */
    private function send_varnish_purge($url, $hosts, $method = "PURGE", $headers = array())
    {
        try {
            $urlParts = $this->twosecond_parse_url($url);
            $scheme = isset($urlParts['scheme']) ? $urlParts['scheme'] : 'http';
            $path = isset($urlParts['path']) ? $urlParts['path'] : '/';
            $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
            
            $defaultHeaders = array(
                'Host' => isset($urlParts['host']) ? $urlParts['host'] : '',
            );
            
            $headers = array_merge($defaultHeaders, $headers);
            
            $success = false;
            foreach ($hosts as $host) {
                $port = isset($urlParts['port']) ? $urlParts['port'] : (($scheme === 'https') ? 443 : 80);
                $purgeUrl = $scheme . '://' . $host . ':' . $port . $path . $query;
                
                $response = wp_remote_request($purgeUrl, array(
                    'method' => $method,
                    'headers' => $headers,
                    'timeout' => 5,
                    'sslverify' => false,
                ));
                
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 400) {
                    $success = true;
                }
            }
            
            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }
}

