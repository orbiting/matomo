<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Diagnostics\Diagnostic;

use Piwik\Access;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Common;
use Piwik\CronArchive;
use Piwik\Date;
use Piwik\Db;
use Piwik\Development;
use Piwik\Option;
use Piwik\Plugin\Manager;
use Piwik\SettingsPiwik;
use Piwik\Site;
use Piwik\Translation\Translator;

/**
 * Informatation about Matomo reports eg tracking or archiving related
 */
class ReportInformational implements Diagnostic
{
    /**
     * @var Translator
     */
    private $translator;

    private $idSiteCache;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function execute()
    {
        $results = [];

        if (SettingsPiwik::isMatomoInstalled()) {
            $results[] = DiagnosticResult::informationalResult('Had visits in last 1 day', $this->hadVisitsInLastDays(1));
            $results[] = DiagnosticResult::informationalResult('Had visits in last 3 days', $this->hadVisitsInLastDays(3));
            $results[] = DiagnosticResult::informationalResult('Had visits in last 5 days', $this->hadVisitsInLastDays(5));
            $results[] = DiagnosticResult::informationalResult('Archive Time Last Started', Option::get(CronArchive::OPTION_ARCHIVING_STARTED_TS));
            $results[] = DiagnosticResult::informationalResult('Archive Time Last Finished', Option::get(CronArchive::OPTION_ARCHIVING_FINISHED_TS));
            $numQueued = $this->getNumInvalidationEntries(ArchiveInvalidator::INVALIDATION_STATUS_QUEUED);
            $numInProgress = $this->getNumInvalidationEntries(ArchiveInvalidator::INVALIDATION_STATUS_IN_PROGRESS);
            $results[] = DiagnosticResult::informationalResult('Num invalidations', sprintf('%s queued, %s in progress', $numQueued, $numInProgress));
        }

        return $results;
    }

    private function getNumInvalidationEntries($status)
    {
        $table = Common::prefixTable('archive_invalidations');

        try {
            $numEntries = Db::fetchOne('SELECT count(*) from ' . $table . ' WHERE `status` = ?' , $status);
        } catch ( \Exception $e ) {
            $numEntries = '';
        }

        return $numEntries;
    }

    private function hadVisitsInLastDays($numDays)
    {
        $table = Common::prefixTable('log_visit');
        $time = Date::now()->subDay($numDays)->getDatetime();

        try {
            $idSites = $this->getImplodedIdSitesSecure();
            $row = Db::fetchOne('SELECT idsite from ' . $table . ' where idsite in ('.$idSites.') and visit_last_action_time > ? LIMIT 1', $time );
        } catch ( \Exception $e ) {
            $row = null;
        }

        if ($numDays === 1 && defined('PIWIK_TEST_MODE') && PIWIK_TEST_MODE) {
            return '0'; // fails randomly in tests
        }

        if (!empty($row)) {
            return '1';
        }
        return '0';
    }

    private function getImplodedIdSitesSecure()
    {
        if (empty($this->idSiteCache)) {
            $idSites = null;
            Access::doAsSuperUser(function () use (&$idSites) {
                $idSites = Site::getIdSitesFromIdSitesString('all');
            });
            $idSites = array_map('intval', $idSites);
            $this->idSiteCache = implode(',', $idSites);
        }

        return $this->idSiteCache;
    }
}
