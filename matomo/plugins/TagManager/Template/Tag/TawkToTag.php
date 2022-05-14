<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TagManager\Template\Tag;

use Piwik\Settings\FieldConfig;
use Piwik\Plugins\TagManager\Template\Tag\BaseTag;
use Piwik\Validators\CharacterLength;
use Piwik\Validators\NotEmpty;

class TawkToTag extends BaseTag
{
    public function getName() {
        return "Tawk.to";
    }

    public function getCategory() {
        return self::CATEGORY_SOCIAL;
    }

    public function getIcon() {
        return 'plugins/TagManager/images/icons/tawk_to.png';
    }

    public function getParameters() {
        return array(
            $this->makeSetting('tawkToId', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = 'tawk.to Site ID';
                $field->description = 'You can get the Site ID by logging into Tawk.to, going to "Administration" and clicking on "Property Settings". The Site ID has typically about 25 characters, for example "123451c27295ad739e46b6b1".';
                $field->validators[] = new NotEmpty();
                $field->validate = function ($value) {
                    $value = trim($value);
                    $characterLength = new CharacterLength(16, 30); // we limit to 30 so users don't accidentally enter a 32 digit API key
                    $characterLength->validate($value);
                };
                $field->transform = function ($value) {
                    return trim($value);
                };
            }),
            $this->makeSetting('tawkToWidgetId', 'default', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
                $field->title = 'tawk.to Widget ID';
                $field->description = 'You can get the Widget ID by logging into Tawk.to, going to "Administration" and clicking on "Chat Widget" and selecting an appropriate widget. The widget ID can be retrieved from "Direct Chat Link" as https://tawk.to/chat/{SITE_ID}/{WIDGET_ID}.';
                $field->validators[] = new NotEmpty();
                $field->validate = function ($value) {
                    $value = trim($value);
                    $characterLength = new CharacterLength(7, 20); // we limit to 20 so users don't accidentally enter a 32 digit API key
                    $characterLength->validate($value);
                };
                $field->transform = function ($value) {
                    return trim($value);
                };
            }),
        );
    }

}
