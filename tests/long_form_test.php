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

#[\PHPUnit\Framework\Attributes\CoversClass(long_form::class)]
/**
 * Tests for the pages of the long settings forms.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\long_form
 */
final class long_form_test extends \basic_testcase {
    /**
     * Page of a type.
     *
     * @param string $type Type of the page.
     * @return \moodle_page
     */
    protected function page(string $type): \moodle_page {
        $page = new \moodle_page();
        $page->set_pagetype($type);
        return $page;
    }

    /**
     * The settings of a course, a section and the activities are long forms; the other pages are not.
     */
    public function test_applies(): void {
        $long = ['course-edit', 'course-editsection', 'mod-assign-mod', 'mod-quiz-mod',
            'blocks-iomad_company_admin-company_course_create_form', 'blocks-iomad_company_admin-company_user_create_form',
            'blocks-iomad_company_admin-editadvanced'];
        foreach ($long as $type) {
            $this->assertTrue(long_form::applies($this->page($type)), $type);
        }
        $other = ['course-view-topics', 'mod-assign-view', 'mod-quiz-attempt', 'admin-setting-themesettingepure', 'my-index',
            'blocks-iomad_company_admin-index', 'blocks-iomad_company_admin-company_license_list'];
        foreach ($other as $type) {
            $this->assertFalse(long_form::applies($this->page($type)), $type);
        }
    }
}
