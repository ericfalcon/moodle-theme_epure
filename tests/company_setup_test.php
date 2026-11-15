<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * Tests for the getting started with Épure of an IOMAD company.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\company_setup
 */
final class company_setup_test extends \advanced_testcase {
    /**
     * Offered to whoever can change the appearance of a company that uses Épure, until finished or dismissed; never
     * without a company, nor for a company with another theme.
     */
    public function test_offered(): void {
        global $DB;
        $this->resetAfterTest();
        iomad::reset();
        $this->assertNull(company_setup::offer(null));
        if (!iomad::installed()) {
            return;
        }
        $this->setAdminUser();
        $company = (object) ['name' => 'Clinique des Tilleuls', 'shortname' => 'tilleuls', 'city' => 'Lyon', 'country' => 'FR',
            'theme' => 'epure', 'headingcolor' => '', 'linkcolor' => '', 'maincolor' => '', 'customcss' => ''];
        $category = \core_course_category::create(['name' => $company->name]);
        $columns = $DB->get_columns(iomad::table('company'));
        $company->{isset($columns['coursecategoryid']) ? 'coursecategoryid' : 'category'} = $category->id;
        $company->id = $DB->insert_record(iomad::table('company'), $company);

        $offer = company_setup::offer($company);
        $this->assertSame('Clinique des Tilleuls', $offer['name']);
        $this->assertStringContainsString(company_setup::PARAM . '=1', $offer['url']);

        $other = clone($company);
        $other->theme = 'iomadboost';
        $this->assertNull(company_setup::offer($other));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertNull(company_setup::offer($company));

        $this->setAdminUser();
        company_setup::finish((int) $company->id);
        $this->assertNull(company_setup::offer($company));
    }
}
