<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace theme_epure;

#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
/**
 * Tests of the theme of the IOMAD company followed by the pages.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\hook_callbacks
 */
final class company_theme_test extends \advanced_testcase {
    /** @var string|null Server name before the test. */
    protected ?string $servername = null;

    /**
     * Two companies, the first one with the address of the site and the theme Boost, the second one with Épure.
     *
     * @return int[] Ids of the company of the address and of the other company.
     */
    protected function companies(): array {
        global $DB;
        if (!company_style::iomad_installed()) {
            $this->markTestSkipped('IOMAD is not installed.');
        }
        $this->resetAfterTest();
        $this->servername = $_SERVER['SERVER_NAME'] ?? null;
        $_SERVER['SERVER_NAME'] = 'learning.example.com';
        $ids = [];
        foreach ([['host', 'boost', 'learning.example.com'], ['other', 'epure', '']] as $i => [$name, $theme, $hostname]) {
            $category = $this->getDataGenerator()->create_category();
            $ids[] = (int) $DB->insert_record(iomad::table('company'), ['name' => $name, 'shortname' => $name,
                'city' => 'Lyon', 'country' => 'FR', 'theme' => $theme, 'hostname' => $hostname,
                'category' => $category->id]);
        }
        return $ids;
    }

    /**
     * Gives back the server name.
     */
    protected function tearDown(): void {
        if ($this->servername === null) {
            unset($_SERVER['SERVER_NAME']);
        } else {
            $_SERVER['SERVER_NAME'] = $this->servername;
        }
        parent::tearDown();
    }

    /**
     * Follows the theme of the company, as at the start of a page.
     */
    protected function follow(): void {
        $method = new \ReflectionMethod(hook_callbacks::class, 'follow_company_theme');
        $method->invoke(null);
    }

    /**
     * A user of another company than that of the address gets the theme of their company, at each page.
     */
    public function test_user_of_another_company(): void {
        global $DB, $SESSION;
        [$hostid, $otherid] = $this->companies();
        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record(iomad::table('company_users'), ['companyid' => $otherid, 'userid' => $user->id,
            'departmentid' => 0, 'managertype' => 0, 'suspended' => 0]);
        $this->setUser($user);

        // IOMAD sets the theme and the company of the address at each page, the company only for a new session.
        $SESSION->theme = 'boost';
        $SESSION->currenteditingcompany = $hostid;
        $this->follow();
        $this->assertSame('epure', $SESSION->theme);

        $SESSION->theme = 'boost';
        $SESSION->currenteditingcompany = $otherid;
        $this->follow();
        $this->assertSame('epure', $SESSION->theme);
    }

    /**
     * The users of the company of the address, and an administrator working on it, keep its theme.
     */
    public function test_company_of_the_address(): void {
        global $DB, $SESSION;
        [$hostid, $otherid] = $this->companies();
        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record(iomad::table('company_users'), ['companyid' => $hostid, 'userid' => $user->id,
            'departmentid' => 0, 'managertype' => 0, 'suspended' => 0]);
        $this->setUser($user);
        $SESSION->theme = 'boost';
        $SESSION->currenteditingcompany = $hostid;
        $this->follow();
        $this->assertSame('boost', $SESSION->theme);

        $this->setAdminUser();
        $SESSION->theme = 'boost';
        $SESSION->currenteditingcompany = $hostid;
        $this->follow();
        $this->assertSame('boost', $SESSION->theme);

        // The administrator chose the other company in the IOMAD dashboard.
        $SESSION->currenteditingcompany = $otherid;
        $this->follow();
        $this->assertSame('epure', $SESSION->theme);
    }

    /**
     * A theme set for the session by something else than the address of a company is kept.
     */
    public function test_other_session_theme(): void {
        global $SESSION;
        [, $otherid] = $this->companies();
        $this->setAdminUser();
        $SESSION->theme = 'classic';
        $SESSION->currenteditingcompany = $otherid;
        $this->follow();
        $this->assertSame('classic', $SESSION->theme);
    }
}
