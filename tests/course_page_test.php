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

#[\PHPUnit\Framework\Attributes\CoversClass(course_page::class)]
/**
 * Tests for the course page: banner and progress of the sections.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\course_page
 */
final class course_page_test extends \advanced_testcase {
    /**
     * Course with completion: two pages in section 1, one in section 2, and a learner who completed the first one.
     *
     * @return array Course, learner and teacher.
     */
    protected function course(): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1, 'numsections' => 2]);
        $pages = [];
        foreach ([1, 1, 2] as $i => $section) {
            $pages[] = $generator->create_module('page', ['course' => $course->id, 'section' => $section,
                'name' => 'Page ' . ($i + 1), 'completion' => COMPLETION_TRACKING_MANUAL]);
        }
        $learner = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $cm = get_coursemodule_from_id('page', $pages[0]->cmid);
        (new \completion_info($course))->update_state($cm, COMPLETION_COMPLETE, $learner->id);
        return [$course, $learner, $teacher];
    }

    /**
     * The banner is shown on the pages of a course, not on the site home, and can be turned off.
     */
    public function test_applies(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = new \moodle_page();
        $page->set_course($course);
        $page->set_pagetype('course-view-topics');
        $this->assertTrue(course_page::applies($page));

        $page = new \moodle_page();
        $page->set_course($course);
        $page->set_pagetype('mod-page-view');
        $this->assertFalse(course_page::applies($page));

        $page = new \moodle_page();
        $page->set_course(get_site());
        $page->set_pagetype('course-view-site');
        $this->assertFalse(course_page::applies($page));

        set_config('coursebanner', '0', 'theme_epure');
        $page = new \moodle_page();
        $page->set_course($course);
        $page->set_pagetype('course-view-topics');
        $this->assertFalse(course_page::applies($page));
    }

    /**
     * A learner gets their progress and the next activity; a teacher the figures; a visitor the course only.
     */
    public function test_export(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        [$course, $learner, $teacher] = $this->course();
        $output = $PAGE->get_renderer('core');

        $this->setUser($learner);
        $data = course_page::export($output, $course, '<h1>Header</h1>');
        $this->assertTrue($data['learning']);
        $this->assertFalse($data['teaching']);
        $this->assertSame(33, $data['progress']);
        $this->assertSame('Page 2', $data['next']['name']);
        $this->assertSame('<h1>Header</h1>', $data['header']);
        $this->assertTrue($data['haslearnerpanel']);

        $this->setUser($teacher);
        $data = course_page::export($output, $course, '');
        $this->assertTrue($data['teaching']);
        $this->assertSame(1, $data['participants']);
        $this->assertNotSame('', $data['outline']);

        // A teacher who switched to the role of a student previews the banner of a learner.
        $this->setUser($teacher);
        $context = \context_course::instance($course->id);
        role_switch((int) $DB->get_field('role', 'id', ['shortname' => 'student']), $context);
        $data = course_page::export($output, $course, '');
        $this->assertTrue($data['learning']);
        $this->assertSame('Page 1', $data['next']['name']);
        role_switch(0, $context);

        $this->setUser($this->getDataGenerator()->create_user());
        $data = course_page::export($output, $course, '');
        $this->assertFalse($data['learning']);
        $this->assertFalse($data['teaching']);
        $this->assertFalse($data['haslearnerpanel']);
        $this->assertArrayNotHasKey('progress', $data);
    }

    /**
     * What a course holds, for its teachers: its sections and the activities that open a page, hidden ones included,
     * not the text and media areas.
     */
    public function test_outline(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 3]);
        $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $generator->create_module('assign', ['course' => $course->id, 'section' => 2, 'visible' => 0]);
        $generator->create_module('label', ['course' => $course->id, 'section' => 2]);
        $expected = get_string('courseoutline_sections', 'theme_epure', 4) . ' · '
            . get_string('catalogue_activities', 'theme_epure', 2);
        $this->assertSame($expected, course_page::outline($course));

        $generator->create_module('forum', ['course' => $course->id]);
        $this->assertStringEndsWith(get_string('catalogue_activities', 'theme_epure', 3), course_page::outline($course));

        $single = $generator->create_course(['numsections' => 0]);
        $this->assertSame('', course_page::outline($single));
        $generator->create_module('page', ['course' => $single->id]);
        $this->assertSame(get_string('courseoutline_sections_one', 'theme_epure') . ' · '
            . get_string('catalogue_activities_one', 'theme_epure'), course_page::outline($single));
    }

    /**
     * Each section with completion tells how many of its activities the learner completed.
     */
    public function test_sections(): void {
        $this->resetAfterTest();
        [$course, $learner] = $this->course();
        $sections = course_page::sections($course, $learner->id);
        $this->assertSame([[1, 2], [0, 1]], array_map(fn($section) => [$section['done'], $section['total']], $sections));
        $this->assertSame(
            get_string('coursesectionprogress', 'theme_epure', (object) ['done' => 1, 'total' => 2]),
            $sections[0]['label']
        );

        $course->enablecompletion = 0;
        $this->assertSame([], course_page::sections($course, $learner->id));
    }

    /**
     * The progress of the sections is shown to the learners of the course, with or without the banner.
     */
    public function test_section_progress_applies(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $page = new \moodle_page();
        $page->set_course($course);
        $page->set_pagetype('course-view-topics');

        $this->setUser($learner);
        $this->assertTrue(course_page::section_progress_applies($page, (int) $learner->id));
        set_config('coursebanner', 0, 'theme_epure');
        $this->assertTrue(course_page::section_progress_applies($page, (int) $learner->id));
        set_config('sectionprogress', 0, 'theme_epure');
        $this->assertFalse(course_page::section_progress_applies($page, (int) $learner->id));
        set_config('sectionprogress', 1, 'theme_epure');

        $this->setUser($teacher);
        $this->assertFalse(course_page::section_progress_applies($page, (int) $teacher->id));
    }

    /**
     * The bar to leave the edit mode is on the pages of a course in edit mode only, and posts to editmode.php.
     */
    public function test_edit_bar(): void {
        global $USER;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $page = new \moodle_page();
        $page->set_course($course);
        $page->set_pagetype('course-view-topics');
        $page->set_url(new \moodle_url('/course/view.php', ['id' => $course->id]));

        // Outside the edit mode: no bar.
        $this->assertNull(course_page::edit_bar($page));

        $USER->editing = 1;
        $bar = course_page::edit_bar($page);
        $this->assertNotNull($bar);
        $this->assertStringEndsWith('/editmode.php', $bar['action']);
        $this->assertEquals(\context_course::instance($course->id)->id, $bar['contextid']);
        $this->assertStringContainsString('/course/view.php?id=' . $course->id, $bar['pageurl']);
        $this->assertEquals(sesskey(), $bar['sesskey']);

        // Not on the page of an activity, nor on the site home.
        $page = new \moodle_page();
        $page->set_course($course);
        $page->set_pagetype('mod-page-view');
        $this->assertNull(course_page::edit_bar($page));
        $page = new \moodle_page();
        $page->set_course(get_site());
        $page->set_pagetype('course-view-site');
        $this->assertNull(course_page::edit_bar($page));
    }
}
