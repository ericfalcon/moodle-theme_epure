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

/**
 * Tests of the accessibility statement: the one of the platform, and the own statement of an IOMAD company.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\accessibility_statement
 */
final class accessibility_statement_test extends \advanced_testcase {
    /**
     * The statement of the platform: its settings, its status, its address without a company.
     */
    public function test_platform(): void {
        $this->resetAfterTest();
        $this->assertNull(accessibility_statement::status(0));
        set_config('a11ystatus', 'partial', 'theme_epure');
        set_config('a11yentity', 'Clinique des Tilleuls', 'theme_epure');
        $this->assertSame('partial', accessibility_statement::status(0));
        $this->assertSame('Clinique des Tilleuls', accessibility_statement::values(0)->a11yentity);
        $this->assertSame(accessibility_statement::names(), array_keys((array) accessibility_statement::values(0)));
        // No company on the page: the statement of the platform applies.
        $this->assertSame(0, accessibility_statement::company());
        $this->assertTrue(accessibility_statement::enabled());
        $this->assertStringNotContainsString('companyid', accessibility_statement::url()->out(false));
        $this->assertSame('Clinique des Tilleuls', accessibility_statement::export(0)['entity']);
        $this->assertSame('', accessibility_statement::export(0)['company']);
    }

    /**
     * The own statement of a company is kept apart: saved step by step, the other settings of the platform untouched,
     * forgotten once emptied; without IOMAD, no company has one.
     */
    public function test_company_values(): void {
        $this->resetAfterTest();
        set_config('a11ystatus', 'partial', 'theme_epure');
        accessibility_statement::save_company(7, ['a11yentity' => 'Clinique du Parc', 'a11ystatementdate' => '19/11/2026']);
        accessibility_statement::save_company(7, ['a11ystatus' => 'full', 'unknown' => 'x']);
        $values = accessibility_statement::values(7);
        $this->assertSame('Clinique du Parc', $values->a11yentity);
        $this->assertSame('19/11/2026', $values->a11ystatementdate);
        $this->assertSame('full', accessibility_statement::status(7));
        $this->assertFalse(property_exists($values, 'unknown'));
        $this->assertSame('partial', accessibility_statement::status(0));
        $this->assertSame('', accessibility_statement::values(8)->a11yentity);
        $this->assertStringContainsString('companyid=7', accessibility_statement::url(7)->out(false));
        $this->assertStringContainsString('companyid=7', accessibility_statement::edit_url(2, 7)->out(false));

        // Emptied: the setting is forgotten.
        accessibility_statement::save_company(7, array_fill_keys(accessibility_statement::names(), ''));
        $this->assertFalse(get_config('theme_epure', accessibility_statement::company_setting(7)));
        $this->assertNull(accessibility_statement::status(7));

        if (!iomad::installed()) {
            $this->assertFalse(accessibility_statement::company_exists(7));
            $this->setAdminUser();
            $this->assertFalse(accessibility_statement::can_edit(7));
            $this->assertTrue(accessibility_statement::can_edit(0));
        }
    }

    /**
     * With IOMAD: the statement of a company, with its name; its form for an administrator; the page of a company
     * without a statement of its own shows the one of the platform.
     */
    public function test_company_statement(): void {
        global $DB;
        if (!iomad::installed()) {
            $this->markTestSkipped('IOMAD is not installed.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        $companyid = (int) $DB->insert_record(iomad::table('company'), ['name' => 'Clinique du Parc', 'shortname' => 'parc',
            'city' => 'Lyon', 'country' => 'FR', 'theme' => 'epure', 'category' => $category->id]);
        $this->assertTrue(accessibility_statement::company_exists($companyid));
        $this->assertTrue(accessibility_statement::can_edit($companyid));

        accessibility_statement::save_company($companyid, ['a11ystatus' => 'none']);
        $export = accessibility_statement::export($companyid);
        $this->assertTrue($export['published']);
        $this->assertSame('Clinique du Parc', $export['entity']);
        $this->assertSame('Clinique du Parc', $export['company']);
        $this->assertSame(get_string('a11ystatus_none', 'theme_epure'), $export['status']);
    }
}
