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
            $DB->insert_record(iomad::table('company_users'), (object) ['companyid' => $companyid, 'userid' => $userid,
                'managertype' => 0, 'departmentid' => $departmentid]);
        }
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
