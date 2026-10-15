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

#[\PHPUnit\Framework\Attributes\CoversClass(iomad::class)]
/**
 * Tests for what Épure uses of IOMAD.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\iomad
 */
final class iomad_test extends \advanced_testcase {
    /**
     * The tables are found by their name in the version of IOMAD installed, and exist; without IOMAD, nothing.
     */
    public function test_tables(): void {
        global $DB;
        iomad::reset();
        if (!iomad::installed()) {
            $this->assertFalse(iomad::renamed());
            $this->assertSame('company_users', iomad::table('company_users'));
            $this->assertSame(0, iomad::my_companyid());
            $this->assertNull(iomad::company(1));
            return;
        }
        foreach (array_keys(iomad::TABLES) as $name) {
            $this->assertTrue($DB->get_manager()->table_exists(iomad::table($name)), $name);
        }
        $this->assertSame(iomad::renamed() ? 'local_iomad_companies' : 'company', iomad::table('company'));
    }

    /**
     * The pages of IOMAD the theme links to.
     */
    public function test_url(): void {
        global $CFG;
        $this->assertSame('/blocks/iomad_company_admin/index.php', iomad::url('dashboard')->out_as_local_url(false));
        if (iomad::installed()) {
            foreach (iomad::PAGES as $path) {
                $this->assertFileExists($CFG->dirroot . $path);
            }
        }
    }

    /**
     * The courses of other companies are left out of « My courses », unless shared with the company or followed
     * through it; a course of no company stays.
     */
    public function test_other_company_courses(): void {
        global $DB;
        iomad::reset();
        $this->assertSame([], iomad::other_company_courses(2, [3], 0));
        if (!iomad::installed()) {
            $this->assertSame([], iomad::other_company_courses(2, [3], 1));
            return;
        }
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $companies = [];
        foreach (['A', 'B'] as $name) {
            $category = $generator->create_category();
            $companies[$name] = (int) $DB->insert_record(iomad::table('company'), ['name' => $name, 'shortname' => $name,
                'city' => 'Lyon', 'country' => 'FR', 'category' => $category->id]);
        }
        $courses = [];
        foreach (['ofa', 'ofb', 'open', 'sharedwithb', 'tracked', 'none'] as $name) {
            $courses[$name] = (int) $generator->create_course()->id;
            $DB->delete_records(iomad::table('company_course'), ['courseid' => $courses[$name]]);
            $DB->delete_records(iomad::table('iomad_courses'), ['courseid' => $courses[$name]]);
        }
        $link = fn(string $course, string $company) => $DB->insert_record(
            iomad::table('company_course'),
            ['companyid' => $companies[$company], 'courseid' => $courses[$course], 'departmentid' => 0]
        );
        $link('ofa', 'A');
        $link('ofb', 'B');
        $link('open', 'A');
        $link('sharedwithb', 'A');
        $link('tracked', 'A');
        $DB->insert_record(iomad::table('iomad_courses'), ['courseid' => $courses['open'], 'licensed' => 0, 'shared' => 1]);
        $DB->insert_record(
            iomad::table('company_shared_courses'),
            ['companyid' => $companies['B'], 'courseid' => $courses['sharedwithb']]
        );
        $DB->insert_record(iomad::table('local_iomad_track'), ['userid' => $user->id, 'courseid' => $courses['tracked'],
            'companyid' => $companies['B'], 'coursename' => 'Tracked', 'timeenrolled' => time()]);

        $others = iomad::other_company_courses((int) $user->id, array_values($courses), $companies['B']);
        $this->assertSame([$courses['ofa']], $others);
        $this->assertEqualsCanonicalizing(
            [$courses['ofb']],
            iomad::other_company_courses((int) $user->id, array_values($courses), $companies['A'])
        );
    }
}
