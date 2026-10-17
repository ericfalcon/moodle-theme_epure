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

#[\PHPUnit\Framework\Attributes\CoversClass(simple_view::class)]
/**
 * Tests for the simplified view of the learner, and the badges of a course in its navigation.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\simple_view
 */
final class simple_view_test extends \advanced_testcase {
    /**
     * A page of a course.
     *
     * @param \stdClass $course Course.
     * @return \moodle_page
     */
    protected function course_page(\stdClass $course): \moodle_page {
        global $PAGE;
        // The navigation reads the global page (user tours).
        $PAGE = $page = new \moodle_page();
        $page->set_course($course);
        $page->set_context(\context_course::instance($course->id));
        $page->set_url('/course/view.php', ['id' => $course->id]);
        $page->set_pagelayout('course');
        $page->set_pagetype('course-view-topics');
        return $page;
    }

    /**
     * Only when the setting is on, for the users who manage no activities: in a course, those of the course.
     */
    public function test_applies(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $other = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $generator->enrol_user($teacher->id, $other->id, 'student');

        $this->setUser($learner);
        $this->assertFalse(simple_view::applies($this->course_page($course)));
        set_config('simpleview', 'on', 'theme_epure');
        $this->assertTrue(simple_view::applies($this->course_page($course)));

        $this->setUser($teacher);
        $this->assertFalse(simple_view::applies($this->course_page($course)));
        // A learner in another course.
        $this->assertTrue(simple_view::applies($this->course_page($other)));
        // Outside the courses: whoever manages activities somewhere keeps the full view.
        $page = new \moodle_page();
        $page->set_context(\context_user::instance($teacher->id));
        $this->assertFalse(simple_view::applies($page));

        $this->setAdminUser();
        $this->assertFalse(simple_view::applies($this->course_page($course)));
    }

    /**
     * The course navigation of a learner: the course, the grades and the badges in sight, the others under « More »;
     * the badges only when the course has some, except for whoever can create them.
     */
    public function test_secondary(): void {
        global $CFG;
        require_once($CFG->libdir . '/badgeslib.php');
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        set_config('simpleview', 'on', 'theme_epure');

        $this->setUser($learner);
        $page = $this->course_page($course);
        simple_view::prepare($page);
        $this->assertFalse($page->secondarynav->get('badgesview'));
        $this->assertFalse($page->secondarynav->get('coursehome')->forceintomoremenu);
        $this->assertFalse($page->secondarynav->get('grades')->forceintomoremenu);
        $this->assertTrue($page->secondarynav->get('participants')->forceintomoremenu);

        $this->setUser($teacher);
        $page = $this->course_page($course);
        simple_view::prepare($page);
        $this->assertNotFalse($page->secondarynav->get('badgesview'));
        $this->assertFalse($page->secondarynav->get('participants')->forceintomoremenu);

        $generator->get_plugin_generator('core_badges')->create_badge(['courseid' => $course->id,
            'type' => BADGE_TYPE_COURSE, 'status' => BADGE_STATUS_ACTIVE]);
        $this->assertTrue(simple_view::has_badges($course->id));
        $this->setUser($learner);
        $page = $this->course_page($course);
        simple_view::prepare($page);
        $this->assertFalse($page->secondarynav->get('badgesview')->forceintomoremenu);
    }

    /**
     * The user menu without the calendar, the private files and the reports; the items of the administrator stay.
     */
    public function test_user_menu_items(): void {
        $items = "profile,moodle|/user/profile.php\ngrades,grades|/grade/report/mygrades.php\n"
            . "calendar,core_calendar|/calendar/view.php?view=month\nprivatefiles,moodle|/user/files.php\n"
            . "reports,core_reportbuilder|/reportbuilder/index.php\nHelp|https://example.com/help";
        $this->assertSame(
            "profile,moodle|/user/profile.php\ngrades,grades|/grade/report/mygrades.php\nHelp|https://example.com/help",
            simple_view::user_menu_items($items)
        );
    }
}
