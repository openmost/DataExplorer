<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Fixtures;

use Piwik\Date;
use Piwik\Plugins\Goals\API as GoalsAPI;
use Piwik\Tests\Framework\Fixture;

/**
 * Site 1, 2024-03-01 to 2024-03-03 (UTC):
 *
 * | visit | country | device     | pages                          | goal |
 * |-------|---------|------------|--------------------------------|------|
 * | 1     | fr      | desktop    | /home, /pricing, /home         | 1    |
 * | 2     | fr      | smartphone | /home                          |      |
 * | 3     | de      | desktop    | /blog ("O'Brien 100%"), /home  |      |
 * | 4     | us      | smartphone | /contact ("Contact 1000")      | 1    |
 * | 5     | us      | desktop    | /home, /blog                   |      |
 * | 6     | fr      | desktop    | /pricing                       |      |
 *
 * Site 2, 2024-03-02: one French desktop visit viewing 1,001 distinct URLs.
 */
class ExplorerFixture extends Fixture
{
    public const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    public const SMARTPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    public const OBRIEN_TITLE = "O'Brien 100%";
    public const SITE2_URL_COUNT = 1001;

    public $idSite = 1;
    public $idSite2 = 2;
    public $idGoal = 1;

    private const PAGES = [
        'home' => 'Home',
        'pricing' => 'Pricing',
        'blog' => self::OBRIEN_TITLE,
        'contact' => 'Contact 1000',
    ];

    public function setUp(): void
    {
        // IntegrationTestCase turns the automatic super user off, the tracker needs its token for past timestamps
        self::createSuperUser();

        if (!self::siteCreated($this->idSite)) {
            self::createWebsite('2024-01-01 00:00:00', 0, 'Site 1', 'https://example.com');
        }
        if (!self::siteCreated($this->idSite2)) {
            self::createWebsite('2024-01-01 00:00:00', 0, 'Site 2', 'https://example.org');
        }
        if (!self::goalExists($this->idSite, $this->idGoal)) {
            GoalsAPI::getInstance()->addGoal($this->idSite, 'Signup', 'manually', '', '');
        }

        $this->trackVisit('1000000000000001', 'fr', self::DESKTOP_UA, '2024-03-01 10:00:00', ['home', 'pricing', 'home'], true);
        $this->trackVisit('1000000000000002', 'fr', self::SMARTPHONE_UA, '2024-03-01 14:00:00', ['home']);
        $this->trackVisit('1000000000000003', 'de', self::DESKTOP_UA, '2024-03-02 09:00:00', ['blog', 'home']);
        $this->trackVisit('1000000000000004', 'us', self::SMARTPHONE_UA, '2024-03-02 15:00:00', ['contact'], true);
        $this->trackVisit('1000000000000005', 'us', self::DESKTOP_UA, '2024-03-03 11:00:00', ['home', 'blog']);
        $this->trackVisit('1000000000000006', 'fr', self::DESKTOP_UA, '2024-03-03 16:00:00', ['pricing']);

        $this->trackManyUrlsOnSite2();
    }

    public function tearDown(): void
    {
    }

    private function trackVisit(string $visitorId, string $country, string $userAgent, string $dateTime, array $pages, bool $convert = false): void
    {
        $tracker = self::getTracker($this->idSite, $dateTime, true, true);
        $tracker->setVisitorId($visitorId);
        $tracker->setForceNewVisit();
        $tracker->setCountry($country);
        $tracker->setUserAgent($userAgent);

        $time = Date::factory($dateTime);
        foreach ($pages as $i => $page) {
            $tracker->setForceVisitDateTime($time->addPeriod($i, 'minute')->getDatetime());
            $tracker->setUrl('https://example.com/' . $page);
            self::checkResponse($tracker->doTrackPageView(self::PAGES[$page]));
        }

        if ($convert) {
            $tracker->setForceVisitDateTime($time->addPeriod(count($pages), 'minute')->getDatetime());
            self::checkResponse($tracker->doTrackGoal($this->idGoal, 10));
        }
    }

    private function trackManyUrlsOnSite2(): void
    {
        $tracker = self::getTracker($this->idSite2, '2024-03-02 12:00:00', true, true);
        $tracker->setVisitorId('2000000000000001');
        $tracker->setCountry('fr');
        $tracker->setUserAgent(self::DESKTOP_UA);
        // the local tracker stores bulk URLs before the token is appended, past timestamps need it in each URL
        $tracker->DEBUG_APPEND_URL = '&token_auth=' . self::getTokenAuth();
        $tracker->enableBulkTracking();

        $time = Date::factory('2024-03-02 12:00:00');
        for ($i = 0; $i < self::SITE2_URL_COUNT; $i++) {
            $tracker->setForceVisitDateTime($time->addPeriod($i, 'second')->getDatetime());
            $tracker->setUrl('https://example.org/p/' . $i);
            $tracker->doTrackPageView('Page ' . $i);
        }

        self::checkResponse($tracker->doBulkTrack());
    }
}
