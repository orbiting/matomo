<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MarketingCampaignsReporting\Reports;

use Piwik\Piwik;
use Piwik\Plugins\MarketingCampaignsReporting\Columns\CombinedKeywordContent;

class GetKeywordContentFromNameId extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension        = new CombinedKeywordContent();
        $this->name             = Piwik::translate('MarketingCampaignsReporting_CombinedKeywordContent');
        $this->isSubtableReport = true;
    }
}
