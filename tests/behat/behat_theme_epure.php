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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Moodle\BehatExtension\Exception\SkippedException;
use theme_epure\iomad;

/**
 * Steps of the Behat tests of theme_epure: IOMAD companies (IOMAD 4.5 has no data generator), and the colours of the
 * page.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_epure extends behat_base {
    /**
     * Skips the scenario without IOMAD.
     *
     * @Given /^IOMAD is installed$/
     */
    public function iomad_is_installed(): void {
        if (!iomad::installed()) {
            throw new SkippedException('IOMAD is not installed.');
        }
    }

    /**
     * Creates IOMAD companies, each with its top department.
     *
     * The columns are those of IOMAD's company table: name and shortname, and for example theme and headingcolor.
     *
     * @Given /^the following IOMAD companies exist:$/
     * @param TableNode $data Companies.
     */
    public function the_following_iomad_companies_exist(TableNode $data): void {
        global $DB;
        foreach ($data->getHash() as $row) {
            $company = (object) ($row + ['city' => 'Lyon', 'country' => 'FR', 'theme' => 'epure', 'headingcolor' => '',
                'linkcolor' => '', 'maincolor' => '', 'customcss' => '']);
            $company->id = $DB->insert_record(iomad::table('company'), $company);
            $department = ['name' => $company->name, 'shortname' => $company->shortname];
            $department += iomad::renamed() ? ['companyid' => $company->id, 'parentid' => 0]
                : ['company' => $company->id, 'parent' => 0];
            $DB->insert_record(iomad::table('department'), (object) $department);
        }
        theme_reset_all_caches();
    }

    /**
     * Makes users members of IOMAD companies, in their top department.
     *
     * @Given /^the following IOMAD company users exist:$/
     * @param TableNode $data Columns user (username) and company (short name).
     */
    public function the_following_iomad_company_users_exist(TableNode $data): void {
        global $DB;
        foreach ($data->getHash() as $row) {
            $userid = $DB->get_field('user', 'id', ['username' => $row['user']], MUST_EXIST);
            $companyid = $this->get_companyid($row['company']);
            $field = iomad::renamed() ? 'companyid' : 'company';
            $parent = iomad::renamed() ? 'parentid' : 'parent';
            $departmentid = $DB->get_field(iomad::table('department'), 'id', [$field => $companyid, $parent => 0], MUST_EXIST);
            if (!empty($row['manager'])) {
                // A company manager, through IOMAD itself, which also gives the role of the company.
                $this->iomad_company_class()::upsert_company_user($userid, $companyid, $departmentid, 1);
                continue;
            }
            $DB->insert_record(iomad::table('company_users'), (object) ['companyid' => $companyid, 'userid' => $userid,
                'managertype' => 0, 'departmentid' => $departmentid]);
        }
    }

    /**
     * On IOMAD, puts the users who have no company in a company with the Épure theme: IOMAD sends a user without a
     * company back to the home page of the site. Nothing without IOMAD, so that the same scenarios run on both.
     *
     * @Given /^the users belong to an IOMAD company when IOMAD is installed$/
     */
    public function the_users_belong_to_an_iomad_company_when_iomad_is_installed(): void {
        global $DB;
        if (!iomad::installed()) {
            return;
        }
        if (!$DB->record_exists(iomad::table('company'), ['shortname' => 'epuretests'])) {
            $this->the_following_iomad_companies_exist(new TableNode([
                ['name', 'shortname', 'theme'],
                ['Épure tests', 'epuretests', 'epure'],
            ]));
        }
        $rows = [['user', 'company']];
        $users = $DB->get_records_select('user', "deleted = 0 AND username NOT IN ('guest', 'admin')", null, 'id', 'id, username');
        foreach ($users as $user) {
            if (!$DB->record_exists(iomad::table('company_users'), ['userid' => $user->id])) {
                $rows[] = [$user->username, 'epuretests'];
            }
        }
        if (count($rows) > 1) {
            $this->the_following_iomad_company_users_exist(new TableNode($rows));
        }
    }

    /**
     * The class of the IOMAD companies: local_iomad\company from IOMAD 5.1, company before.
     *
     * @return string
     */
    protected function iomad_company_class(): string {
        global $CFG;
        if (iomad::renamed()) {
            return '\local_iomad\company';
        }
        require_once($CFG->dirroot . '/local/iomad/lib/company.php');
        return '\company';
    }

    /**
     * The pre-audit: each page in the four displays (light and dark on a computer, light on phones of 390 and 320
     * pixels), with the axe audit of Moodle (WCAG 2.2 A and AA) and, on phones, no scroll across the page (WCAG
     * 1.4.10). All the pages are checked before the step fails, with the list of what failed.
     *
     * A path can name a course by its short name, {course SAFE}, and an activity by its ID number, {cm page1}. The
     * column « except » takes « wide table »: a table in two dimensions, which may scroll across (WCAG 1.4.10).
     *
     * @Then /^the following pages should meet accessibility standards in the four displays:$/
     * @param TableNode $data Columns path and, optionally, except.
     */
    public function the_following_pages_should_meet_accessibility_standards_in_the_four_displays(TableNode $data): void {
        $displays = ['light 1300' => [1300, false], 'dark 1300' => [1300, true], 'light 390' => [390, false],
            'light 320' => [320, false]];
        $failures = [];
        foreach ($data->getHash() as $row) {
            $path = $this->epure_path($row['path']);
            foreach ($displays as $label => [$width, $dark]) {
                $this->getSession()->resizeWindow($width, 900);
                $this->getSession()->visit($this->locate_path($path));
                $this->wait_for_pending_js();
                if ($dark) {
                    $this->getSession()->executeScript("document.documentElement.classList.add('epure-dark');");
                }
                try {
                    $this->execute('behat_accessibility::run_axe_validation_for_tags', ['', 'wcag22aa']);
                } catch (\Exception $e) {
                    $failures[] = "{$row['path']} ({$label}): " . trim($e->getMessage());
                }
                if ($width < 1000 && ($row['except'] ?? '') !== 'wide table') {
                    $script = 'return document.scrollingElement.scrollWidth - window.innerWidth;';
                    $across = $this->getSession()->evaluateScript($script);
                    if ($across > 1) {
                        $failures[] = "{$row['path']} ({$label}): the page scrolls across, by {$across} px";
                    }
                }
            }
        }
        $this->getSession()->resizeWindow(1366, 900);
        if ($failures) {
            throw new ExpectationException(count($failures) . " failures:\n" . implode("\n", $failures), $this->getSession());
        }
    }

    /**
     * A path of the pre-audit, its courses and activities replaced by their ids.
     *
     * @param string $path Path with {course SHORTNAME} and {cm IDNUMBER}.
     * @return string
     */
    protected function epure_path(string $path): string {
        global $DB;
        return preg_replace_callback('/\{(course|cm) ([^}]+)\}/', function (array $m) use ($DB): string {
            return $m[1] === 'course'
                ? (string) $DB->get_field('course', 'id', ['shortname' => $m[2]], MUST_EXIST)
                : (string) $DB->get_field('course_modules', 'id', ['idnumber' => $m[2]], MUST_EXIST);
        }, $path);
    }

    /**
     * Gives an IOMAD company a logo, as IOMAD stores it (core_admin, logo<id>).
     *
     * @Given /^the IOMAD company "(?P<shortname>[^"]*)" has the logo "(?P<path>[^"]*)"$/
     * @param string $shortname Short name of the company.
     * @param string $path File, from the root of Moodle.
     */
    public function the_iomad_company_has_the_logo(string $shortname, string $path): void {
        global $CFG;
        $companyid = $this->get_companyid($shortname);
        $filename = basename($path);
        get_file_storage()->create_file_from_pathname([
            'contextid' => context_system::instance()->id,
            'component' => 'core_admin',
            'filearea' => 'logo' . $companyid,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], $CFG->dirroot . '/' . $path);
        set_config('logo' . $companyid, '/' . $filename, 'core_admin');
        theme_reset_all_caches();
    }

    /**
     * Checks the brand colour of the page: the colour the theme derives from a colour, for its contrast.
     *
     * @Then /^the brand colour of the page should come from "(?P<colour>#[0-9A-Fa-f]{6})"$/
     * @param string $colour Brand colour set.
     */
    public function the_brand_colour_of_the_page_should_come_from(string $colour): void {
        $expected = \theme_epure\palette::derive($colour)['fill'];
        $actual = $this->brand_colour();
        if ($actual !== strtoupper($expected)) {
            throw new ExpectationException("The brand colour of the page is $actual, not $expected.", $this->getSession());
        }
    }

    /**
     * Checks that the brand colour of the page is not the one the theme derives from a colour.
     *
     * @Then /^the brand colour of the page should not come from "(?P<colour>#[0-9A-Fa-f]{6})"$/
     * @param string $colour Brand colour.
     */
    public function the_brand_colour_of_the_page_should_not_come_from(string $colour): void {
        $colour = strtoupper(\theme_epure\palette::derive($colour)['fill']);
        if ($this->brand_colour() === $colour) {
            throw new ExpectationException("The brand colour of the page is $colour.", $this->getSession());
        }
    }

    /**
     * Brand colour of the page, as the custom property --epure-brand.
     *
     * @return string
     */
    protected function brand_colour(): string {
        return (string) $this->evaluate_script(
            'return getComputedStyle(document.documentElement).getPropertyValue("--epure-brand").trim().toUpperCase();'
        );
    }

    /**
     * Checks that the page shows the logo of an IOMAD company.
     *
     * @Then /^the logo of the IOMAD company "(?P<shortname>[^"]*)" should be shown$/
     * @param string $shortname Short name of the company.
     */
    public function the_logo_of_the_iomad_company_should_be_shown(string $shortname): void {
        $area = '/core_admin/logo' . $this->get_companyid($shortname) . '/';
        $this->execute('behat_general::should_exist', ["img[src*='$area']", 'css_element']);
    }

    /**
     * Identifier of an IOMAD company.
     *
     * @param string $shortname Short name.
     * @return int
     */
    protected function get_companyid(string $shortname): int {
        global $DB;
        return (int) $DB->get_field(iomad::table('company'), 'id', ['shortname' => $shortname], MUST_EXIST);
    }
}
