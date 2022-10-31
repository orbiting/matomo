<?php 
/**
 * Plugin Name: Provider (Matomo Plugin)
 * Plugin URI: http://plugins.matomo.org/Provider
 * Description: Reports the Internet Service Provider of the visitors.
 * Author: Matomo
 * Author URI: https://matomo.org
 * Version: 4.0.5
 */
?><?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\Provider;

use Exception;
use Piwik\Common;
use Piwik\Db;
use Piwik\FrontController;
use Piwik\Piwik;

 
if (defined( 'ABSPATH')
&& function_exists('add_action')) {
    $path = '/matomo/app/core/Plugin.php';
    if (defined('WP_PLUGIN_DIR') && WP_PLUGIN_DIR && file_exists(WP_PLUGIN_DIR . $path)) {
        require_once WP_PLUGIN_DIR . $path;
    } elseif (defined('WPMU_PLUGIN_DIR') && WPMU_PLUGIN_DIR && file_exists(WPMU_PLUGIN_DIR . $path)) {
        require_once WPMU_PLUGIN_DIR . $path;
    } else {
        return;
    }
    add_action('plugins_loaded', function () {
        if (function_exists('matomo_add_plugin')) {
            matomo_add_plugin(__DIR__, __FILE__, true);
        }
    });
}

class Provider extends \Piwik\Plugin
{
    public function isTrackerPlugin()
    {
        return true;
    }

    public function install()
    {
        // add column hostname / hostname ext in the visit table
        $query = "ALTER TABLE `" . Common::prefixTable('log_visit') . "` ADD `location_provider` VARCHAR(200) NULL";

        // if the column already exist do not throw error. Could be installed twice...
        try {
            Db::exec($query);
        } catch (Exception $e) {
            if (!Db::get()->isErrNo($e, '1060')) {
                throw $e;
            }
        }
    }

    public function uninstall()
    {
        // add column hostname / hostname ext in the visit table
        $query = "ALTER TABLE `" . Common::prefixTable('log_visit') . "` DROP `location_provider`";
        Db::exec($query);
    }

    /**
     * Returns the hostname extension (site.co.jp in fvae.VARG.ceaga.site.co.jp)
     * given the full hostname looked up from the IP
     *
     * @param string $hostname
     *
     * @return string
     */
    public static function getCleanHostname($hostname)
    {
        $extToExclude = [
            'com', 'net', 'org', 'co',
        ];

        $off = strrpos($hostname, '.');
        $ext = substr($hostname, $off);

        if (empty($off) || is_numeric($ext) || strlen($hostname) < 5) {
            return 'Ip';
        } else {
            $cleanHostname = null;

            /**
             * Triggered when prettifying a hostname string.
             *
             * This event can be used to customize the way a hostname is displayed in the
             * Providers report.
             *
             * **Example**
             *
             *     public function getCleanHostname(&$cleanHostname, $hostname)
             *     {
             *         if ('fvae.VARG.ceaga.site.co.jp' == $hostname) {
             *             $cleanHostname = 'site.co.jp';
             *         }
             *     }
             *
             * @param string &$cleanHostname The hostname string to display. Set by the event
             *                               handler.
             * @param string  $hostname      The full hostname.
             */
            Piwik::postEvent('Provider.getCleanHostname', [&$cleanHostname, $hostname]);
            if ($cleanHostname !== null) {
                return $cleanHostname;
            }

            $e = explode('.', $hostname);
            $s = sizeof($e);

            // if extension not correct
            if (isset($e[$s - 2]) && in_array($e[$s - 2], $extToExclude)) {
                return $e[$s - 3] . "." . $e[$s - 2] . "." . $e[$s - 1];
            } else {
                return $e[$s - 2] . "." . $e[$s - 1];
            }
        }
    }

}
