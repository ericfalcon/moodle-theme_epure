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

#[\PHPUnit\Framework\Attributes\CoversClass(iomad_figures::class)]
/**
 * Tests for the key figures of the platform and of an IOMAD company.
 *
 * The figures of a company need IOMAD's tables, so they are skipped on a standard Moodle.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\iomad_figures
 */
final class iomad_figures_test extends \advanced_testcase {
    /**
     * Figures of the whole platform, for its managers, on a Moodle without IOMAD.
     */
    public function test_site(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['visible' => 0]);
        $user = $generator->create_user(['lastaccess' => time()]);
        $generator->enrol_user($user->id, $course->id);
        $DB->insert_record('course_completions', ['userid' => $user->id, 'course' => $course->id,
            'timeenrolled' => 0, 'timestarted' => 0, 'timecompleted' => time()]);

        $figures = array_column(iomad_figures::site(), null, 'key');
        $this->assertSame(
            $DB->count_records_select('user', 'deleted = 0 AND id > 1 AND confirmed = 1'),
            $figures['users']['value']
        );
        $this->assertSame(1, $figures['courses']['value']);
        $this->assertSame(get_string('sitefigures_visible', 'theme_epure', 0), $figures['courses']['detail']);
        $this->assertSame(1, $figures['enrolments']['value']);
        $this->assertSame(1, $figures['completions']['value']);

        $page = new \moodle_page();
        $page->set_pagetype('my-index');
        $this->setUser($user);
        $this->assertFalse(iomad_figures::site_applies($page));
        $this->setAdminUser();
        $this->assertSame(!company_style::iomad_installed(), iomad_figures::site_applies($page));
    }

    /**
     * Users, courses, licences and completions of the company, and of no other one.
     */
    public function test_export(): void {
        global $DB;
        if (!company_style::iomad_installed()) {
            $this->markTestSkipped('IOMAD is not installed.');
        }
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $active = $generator->create_user(['lastaccess' => time()]);
        $idle = $generator->create_user(['lastaccess' => time() - 30 * DAYSECS]);
        $other = $generator->create_user();
        foreach ([[1, $active], [1, $idle], [2, $other]] as [$companyid, $user]) {
            $DB->insert_record('company_users', ['companyid' => $companyid, 'userid' => $user->id, 'departmentid' => 0,
                'managertype' => 0, 'suspended' => 0]);
        }
        $course = $generator->create_course();
        $DB->insert_record('company_course', ['companyid' => 1, 'courseid' => $course->id, 'departmentid' => 0]);
        $DB->insert_record('companylicense', ['companyid' => 1, 'name' => 'L', 'allocation' => 10, 'used' => 4,
            'validlength' => 30, 'expirydate' => time() + DAYSECS, 'startdate' => 0]);
        $DB->insert_record('local_iomad_track', ['companyid' => 1, 'courseid' => $course->id, 'userid' => $active->id,
            'timecompleted' => time() - DAYSECS, 'coursename' => 'C']);

        $figures = array_column(iomad_figures::export(1), null, 'key');
        $this->assertSame(2, $figures['users']['value']);
        $this->assertSame(get_string('iomadfigures_active', 'theme_epure', 1), $figures['users']['detail']);
        $this->assertSame(1, $figures['courses']['value']);
        $this->assertSame(4, $figures['licences']['value']);
        $this->assertSame(1, $figures['completions']['value']);
    }
}
