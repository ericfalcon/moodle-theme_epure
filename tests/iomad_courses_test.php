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

#[\PHPUnit\Framework\Attributes\CoversClass(iomad_courses::class)]
/**
 * Tests for the column « Category » on IOMAD's page of the courses.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\iomad_courses
 */
final class iomad_courses_test extends \advanced_testcase {
    /**
     * The column is only added to IOMAD's page of the courses.
     */
    public function test_applies(): void {
        $page = new \moodle_page();
        $page->set_pagetype('course-index');
        $this->assertFalse(iomad_courses::applies($page));
        $page = new \moodle_page();
        $page->set_pagetype(iomad_courses::PAGETYPE);
        $this->assertEquals(company_style::iomad_installed(), iomad_courses::applies($page));
    }

    /**
     * Each IOMAD course gets the path of its category.
     */
    public function test_data(): void {
        global $DB;
        if (!company_style::iomad_installed()) {
            $this->markTestSkipped('IOMAD is not installed.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $parent = $generator->create_category(['name' => 'Clinic']);
        $child = $generator->create_category(['name' => 'Nursing', 'parent' => $parent->id]);
        $course = $generator->create_course(['category' => $child->id]);
        if (!$DB->record_exists('iomad_courses', ['courseid' => $course->id])) {
            $DB->insert_record('iomad_courses', ['courseid' => $course->id, 'licensed' => 0, 'shared' => 0]);
        }

        $data = iomad_courses::data();
        $this->assertEquals($child->id, $data['courses'][$course->id]);
        $this->assertEquals('Clinic / Nursing', $data['categories'][$child->id]);
    }
}
