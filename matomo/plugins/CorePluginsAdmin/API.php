<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CorePluginsAdmin;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugin\SettingsProvider;
use Exception;
use Piwik\Plugins\Login\PasswordVerifier;
use Piwik\Version;

/**
 * API for plugin CorePluginsAdmin
 *
 * @method static \Piwik\Plugins\CorePluginsAdmin\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * @var SettingsMetadata
     */
    private $settingsMetadata;

    /**
     * @var SettingsProvider
     */
    private $settingsProvider;

    /**
     * @var PasswordVerifier
     */
    private $passwordVerifier;

    public function __construct(SettingsProvider $settingsProvider, SettingsMetadata $settingsMetadata, PasswordVerifier $passwordVerifier)
    {
        $this->settingsProvider = $settingsProvider;
        $this->settingsMetadata = $settingsMetadata;
        $this->passwordVerifier = $passwordVerifier;
    }

    /**
     * @internal
     * @param array $settingValues Format: array('PluginName' => array(array('name' => 'SettingName1', 'value' => 'SettingValue1), ..))
     * @throws Exception
     */
    public function setSystemSettings($settingValues, $passwordConfirmation = false)
    {
        Piwik::checkUserHasSuperUserAccess();

        $skipPasswordConfirm = $passwordConfirmation === false && version_compare(Version::VERSION, '4.4.0-b1', '<');
        if (!$skipPasswordConfirm) {
            $this->confirmCurrentUserPassword($passwordConfirmation);
        }

        $pluginsSettings = $this->settingsProvider->getAllSystemSettings();

        $this->settingsMetadata->setPluginSettings($pluginsSettings, $settingValues);

        try {
            foreach ($pluginsSettings as $pluginSetting) {
                if (!empty($settingValues[$pluginSetting->getPluginName()])) {
                    $pluginSetting->save();
                }
            }
        } catch (Exception $e) {
            throw new Exception(Piwik::translate('CoreAdminHome_PluginSettingsSaveFailed'));
        }
    }

    /**
     * @internal
     * @param array $settingValues  Format: array('PluginName' => array(array('name' => 'SettingName1', 'value' => 'SettingValue1), ..))
     * @throws Exception
     */
    public function setUserSettings($settingValues)
    {
        Piwik::checkUserIsNotAnonymous();

        $pluginsSettings = $this->settingsProvider->getAllUserSettings();

        $this->settingsMetadata->setPluginSettings($pluginsSettings, $settingValues);

        try {
            foreach ($pluginsSettings as $pluginSetting) {
                if (!empty($settingValues[$pluginSetting->getPluginName()])) {
                    $pluginSetting->save();
                }
            }
        } catch (Exception $e) {
            throw new Exception(Piwik::translate('CoreAdminHome_PluginSettingsSaveFailed'));
        }
    }

    /**
     * @internal
     * @return array
     * @throws \Piwik\NoAccessException
     */
    public function getSystemSettings()
    {
        Piwik::checkUserHasSuperUserAccess();

        $systemSettings = $this->settingsProvider->getAllSystemSettings();

        return $this->settingsMetadata->formatSettings($systemSettings);
    }

    /**
     * @internal
     * @return array
     * @throws \Piwik\NoAccessException
     */
    public function getUserSettings()
    {
        Piwik::checkUserIsNotAnonymous();

        $userSettings = $this->settingsProvider->getAllUserSettings();

        return $this->settingsMetadata->formatSettings($userSettings);
    }

    private function confirmCurrentUserPassword($passwordConfirmation)
    {
        if (empty($passwordConfirmation)) {
            throw new Exception(Piwik::translate('UsersManager_ConfirmWithPassword'));
        }

        $passwordConfirmation = Common::unsanitizeInputValue($passwordConfirmation);

        $loginCurrentUser = Piwik::getCurrentUserLogin();
        if (!$this->passwordVerifier->isPasswordCorrect($loginCurrentUser, $passwordConfirmation)) {
            throw new Exception(Piwik::translate('UsersManager_CurrentPasswordNotCorrect'));
        }
    }
}
