<?php 
/**
 * Plugin Name: Invalidate Reports (Matomo Plugin)
 * Plugin URI: http://plugins.matomo.org/InvalidateReports
 * Description: This plugin allows Super Users to invalidate historical reports in the UI in Administration > System > Invalidate reports.
 * Author: InnoCraft
 * Author URI: https://www.innocraft.com
 * Version: 4.0.1
 */
?><?php
/**
 * InnoCraft - the company of the makers of Piwik Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\InvalidateReports;

/**
 *
 */
 
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

class InvalidateReports extends \Piwik\Plugin
{
    /**
     * @see Piwik_Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys'
        );
    }

    /**
     * Adds required JS files
     * @param $jsFiles
     */
    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/InvalidateReports/javascripts/invalidatereports.controller.js";
    }

    /**
     * Adds required CSS files
     * @param $stylesheets
     */
    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/InvalidateReports/stylesheets/styles.less";
    }

    /**
     * Adds translation keys required in JS
     * @param $translationKeys
     */
    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = "InvalidateReports_AllSegments";
        $translationKeys[] = "InvalidateReports_InvalidationSuccess";
        $translationKeys[] = "InvalidateReports_InvalidateAPIReturn";
    }
}