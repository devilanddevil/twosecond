<?php

namespace TwoSecond;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Hosting Detection Class
 * 
 * This class provides methods to detect various WordPress hosting providers.
 * It uses environment variables, server variables, constants, and hostname patterns
 * to identify the hosting platform. This is useful for applying hosting-specific
 * optimizations and configurations.
 * 
 * @package TwoSecond
 */
class Hosting extends TwoSecondCache
{
    /**
     * Detects and returns the current hosting provider name
     * 
     * Checks for various hosting providers in a specific order and returns
     * the first match found. If no hosting provider is detected, returns "unknown".
     * 
     * @return string The hosting provider identifier (e.g., "flywheel", "cloudways", "wpengine")
     *                or "unknown" if no provider is detected
     */
    protected function get_hosting()
    {
        // Check hosting providers in order of priority
        if ($this->is_flywheel_hosting()) {
            return "flywheel";
        } else if ($this->is_cloudways_hosting()) {
            return "cloudways";
        } else if ($this->is_wp_engine_hosting()) {
            return "wpengine";
        } else if ($this->is_site_ground_hosting()) {
            return "siteground";
        } else if ($this->is_go_daddy_wpaas_hosting()) {
            return "godaddy_wpaas";
        } else if ($this->is_kinsta_hosting()) {
            return "kinsta";
        } else if ($this->is_closte_hosting()) {
            return "closte";
        } else if ($this->is_pagely_hosting()) {
            return "pagely";
        } else if ($this->is_wpx_hosting()) {
            return "wpx";
        } else if ($this->is_vimexx_hosting()) {
            return "vimexx";
        } else if ($this->is_pressable_hosting()) {
            return "pressable";
        } else if ($this->is_rocket_net_hosting()) {
            return "rocketnet";
        } else if ($this->is_savvii_hosting()) {
            return "savvii";
        } else if ($this->is_dream_host_hosting()) {
            return "dreamhost";
        } else if ($this->is_raidboxes_hosting()) {
            return "raidboxes";
        } else {
            return "unknown";
        }
    }

    /**
     * Checks if the site is hosted on SiteGround
     * 
     * Detects SiteGround hosting by checking if the hostname contains "siteground.eu"
     * 
     * @return bool True if SiteGround hosting is detected, false otherwise
     */
    public function is_site_ground_hosting()
    {
        if (strpos(gethostname(), "siteground.eu") !== false) return true;
        return false;
    }

    /**
     * Checks if the site is hosted on Closte
     * 
     * Detects Closte hosting by checking if the CLOSTE_APP_ID constant is defined
     * 
     * @return bool True if Closte hosting is detected, false otherwise
     */
    public function is_closte_hosting()
    {
        return defined("CLOSTE_APP_ID");
    }

    /**
     * Checks if the site is hosted on Cloudways
     * 
     * Detects Cloudways hosting by checking for the "cw_allowed_ip" server variable
     * or by matching the file path pattern containing "cloudways"
     * 
     * @return bool True if Cloudways hosting is detected, false otherwise
     */
    public function is_cloudways_hosting()
    {
        return array_key_exists("cw_allowed_ip", $_SERVER) || preg_match("~/home/.*?cloudways.*~", __FILE__);
    }

    /**
     * Checks if the site is hosted on DreamHost
     * 
     * Detects DreamHost hosting by checking if the hostname starts with "dp-"
     * 
     * @return bool True if DreamHost hosting is detected, false otherwise
     */
    public function is_dream_host_hosting()
    {
        return preg_match("/^dp-.+/", gethostname());
    }

    /**
     * Checks if the site is hosted on Flywheel
     * 
     * Detects Flywheel hosting by checking if the FLYWHEEL_PLUGIN_DIR constant is defined
     * 
     * @return bool True if Flywheel hosting is detected, false otherwise
     */
    public function is_flywheel_hosting()
    {
        return defined("FLYWHEEL_PLUGIN_DIR");
    }

    /**
     * Checks if the site is hosted on GoDaddy WPaaS (WordPress as a Service)
     * 
     * Detects GoDaddy WPaaS hosting by checking if the WPaaS\Plugin class exists
     * 
     * @return bool True if GoDaddy WPaaS hosting is detected, false otherwise
     */
    public function is_go_daddy_wpaas_hosting()
    {
        return class_exists('\WPaaS\Plugin');
    }

    /**
     * Checks if the site is hosted on Kinsta
     * 
     * Detects Kinsta hosting by checking if the KINSTAMU_VERSION constant is defined
     * 
     * @return bool True if Kinsta hosting is detected, false otherwise
     */
    public function is_kinsta_hosting()
    {
        return defined("KINSTAMU_VERSION");
    }

    /**
     * Checks if the site is hosted on Pagely
     * 
     * Detects Pagely hosting by checking if the PagelyCachePurge class exists
     * or if the HTTP_X_PAGELY_SSL server variable is set
     * 
     * @return bool True if Pagely hosting is detected, false otherwise
     */
    public function is_pagely_hosting()
    {
        return class_exists('\PagelyCachePurge') || isset($_SERVER["HTTP_X_PAGELY_SSL"]);
    }

    /**
     * Checks if the site is hosted on Pantheon
     * 
     * Detects Pantheon hosting by checking if the PANTHEON_ENVIRONMENT environment variable is set
     * 
     * @return bool True if Pantheon hosting is detected, false otherwise
     */
    public function is_pantheon_hosting()
    {
        return isset($_ENV['PANTHEON_ENVIRONMENT']);
    }

    /**
     * Checks if the site is hosted on Pressable
     * 
     * Detects Pressable hosting by checking for the PRESSABLE_PROXIED_REQUEST server variable
     * or if the hostname contains "atomicsites.net"
     * 
     * @return bool True if Pressable hosting is detected, false otherwise
     */
    public function is_pressable_hosting()
    {
        return isset($_SERVER["PRESSABLE_PROXIED_REQUEST"]) || strpos(gethostname(), "atomicsites.net") !== false;
    }

    /**
     * Checks if the site is hosted on Raidboxes
     * 
     * Detects Raidboxes hosting by checking if the hostname starts with "box-"
     * 
     * @return bool True if Raidboxes hosting is detected, false otherwise
     */
    public function is_raidboxes_hosting()
    {
        return substr(gethostname(), 0, 4) == "box-";
    }

    /**
     * Checks if the site is hosted on Rocket.net
     * 
     * Detects Rocket.net hosting by checking if the ROCKET_SITE_ID constant is defined
     * or if the hostname contains "onrocket.com"
     * 
     * @return bool True if Rocket.net hosting is detected, false otherwise
     */
    public function is_rocket_net_hosting()
    {
        return defined("ROCKET_SITE_ID") || strpos(gethostname(), "onrocket.com") !== false;
    }

    /**
     * Checks if the site is hosted on Savvii
     * 
     * Detects Savvii hosting by checking if the WARPDRIVE_API server variable is set
     * and equals the Savvii API endpoint URL
     * 
     * @return bool True if Savvii hosting is detected, false otherwise
     */
    public function is_savvii_hosting()
    {
        return isset($_SERVER['WARPDRIVE_API']) && $_SERVER['WARPDRIVE_API'] == 'https://api.savvii.services';
    }

    /**
     * Checks if the site is hosted on SpinupWP
     * 
     * Detects SpinupWP hosting by checking if the SPINUPWP_SITE environment variable is set
     * 
     * @return bool True if SpinupWP hosting is detected, false otherwise
     */
    public function is_spinup_wp_hosting()
    {
        return !!getenv('SPINUPWP_SITE');
    }

    /**
     * Checks if the site is hosted on Vimexx
     * 
     * Detects Vimexx hosting by checking if the hostname contains "zxcs"
     * or if the HTTP_X_ZXCS_VHOST server variable is set
     * 
     * @return bool True if Vimexx hosting is detected, false otherwise
     */
    public function is_vimexx_hosting()
    {
        $hostname = gethostname();
        return (!empty($hostname) && strpos($hostname, 'zxcs') !== false) || !empty($_SERVER['HTTP_X_ZXCS_VHOST']);
    }

    /**
     * Checks if the site is hosted on WP Engine
     * 
     * Detects WP Engine hosting by checking for:
     * - IS_WPE environment variable
     * - WPENGINE_ACCOUNT environment variable
     * - Document root path starting with "/nas/content/live/"
     * 
     * @return bool True if WP Engine hosting is detected, false otherwise
     */
    public function is_wp_engine_hosting()
    {
        return !!getenv('IS_WPE')
            || !!getenv('WPENGINE_ACCOUNT')
            || (strpos($this->addSettings['document_root'], '/nas/content/live/') === 0);
    }

    /**
     * Checks if the site is hosted on WPMU DEV Hosted
     * 
     * Detects WPMU DEV hosting by checking if the WPMUDEV_HOSTED server variable is set
     * 
     * @return bool True if WPMU DEV hosting is detected, false otherwise
     */
    public function is_wpmudev_hosting()
    {
        return isset($_SERVER['WPMUDEV_HOSTED']);
    }

    /**
     * Checks if the site is hosted on WPX Hosting
     * 
     * Detects WPX hosting by checking if the hostname ends with "wpx.net" or "wpxhosting.com"
     * 
     * @return bool True if WPX hosting is detected, false otherwise
     */
    public function is_wpx_hosting()
    {
        $hostname = gethostname();
        return $hostname && (preg_match("/wpx\.net$/", $hostname) || preg_match("/wpxhosting\.com$/", $hostname));
    }
}
