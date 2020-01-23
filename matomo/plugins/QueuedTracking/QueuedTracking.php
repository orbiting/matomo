<?php 
/**
 * Plugin Name: Queued Tracking (Matomo Plugin)
 * Plugin URI: http://plugins.matomo.org/QueuedTracking
 * Description: Scale your large traffic Matomo service by queuing tracking requests in Redis or MySQL for better performance and reliability when experiencing peaks.
 * Author: Matomo
 * Author URI: https://matomo.org
 * Version: 3.3.6
 */
?><?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\QueuedTracking;

use Piwik\Common;
use Piwik\Plugins\QueuedTracking\Queue\Backend\MySQL;
use Piwik\Plugins\QueuedTracking\Tracker\Handler;

 
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

class QueuedTracking extends \Piwik\Plugin
{
    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'Tracker.newHandler' => 'replaceHandlerIfQueueIsEnabled'
        );
    }

    public function install()
    {
        $mysql = new MySQL();
        $mysql->install();

        $configuration = new Configuration();
        $configuration->install();
    }

    public function uninstall()
    {
        $mysql = new MySQL();
        $mysql->uninstall();

        $configuration = new Configuration();
        $configuration->uninstall();
    }

    public function isTrackerPlugin()
    {
        return true;
    }

    public function replaceHandlerIfQueueIsEnabled(&$handler)
    {
        $useQueuedTracking = Common::getRequestVar('queuedtracking', 1, 'int');
        if (!$useQueuedTracking) {
            return;
        }

        $settings = Queue\Factory::getSettings();

        if ($settings->queueEnabled->getValue()) {
            $handler = new Handler();

            if ($settings->processDuringTrackingRequest->getValue()) {
                $handler->enableProcessingInTrackerMode();
            }
        }
    }

}
