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

#[\PHPUnit\Framework\Attributes\CoversClass(learner_dashboard::class)]
/**
 * Tests for the overview of the learner on the dashboard.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\learner_dashboard
 */
final class learner_dashboard_test extends \advanced_testcase {
    /**
     * The overview is shown on the dashboard of logged in users, and can be turned off.
     */
    public function test_applies(): void {
        $this->resetAfterTest();
        $page = new \moodle_page();
        $page->set_pagetype('my-index');
        $this->assertFalse(learner_dashboard::applies($page));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertTrue(learner_dashboard::applies($page));

        $other = new \moodle_page();
        $other->set_pagetype('my-courses');
        $this->assertFalse(learner_dashboard::applies($other));

        set_config('learnerdashboard', '0', 'theme_epure');
        $this->assertFalse(learner_dashboard::applies($page));
    }

    /**
     * The course to resume is the last one visited; completed courses are listed apart, the most recent first.
     */
    public function test_export(): void {
        global $CFG, $DB, $PAGE;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/completion/completion_completion.php');
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $learner = $generator->create_user();
        $output = $PAGE->get_renderer('core');

        // A user who takes no course gets no overview.
        $this->setUser($learner);
        $this->assertNull(learner_dashboard::export($output));

        $courses = [];
        foreach (['Old', 'Recent', 'Done', 'Done long ago'] as $name) {
            $courses[$name] = $generator->create_course(['fullname' => $name, 'enablecompletion' => 1]);
            $generator->create_module('page', ['course' => $courses[$name]->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
            $generator->enrol_user($learner->id, $courses[$name]->id, 'student');
        }
        $DB->insert_record('user_lastaccess', ['userid' => $learner->id, 'courseid' => $courses['Old']->id,
            'timeaccess' => time() - DAYSECS]);
        $DB->insert_record('user_lastaccess', ['userid' => $learner->id, 'courseid' => $courses['Recent']->id,
            'timeaccess' => time()]);
        (new \completion_completion(['userid' => $learner->id, 'course' => $courses['Done long ago']->id]))
            ->mark_complete(time() - 30 * DAYSECS);
        (new \completion_completion(['userid' => $learner->id, 'course' => $courses['Done']->id]))
            ->mark_complete(time() - DAYSECS);

        $data = learner_dashboard::export($output);
        $this->assertSame('Recent', $data['resume']['name']);
        $this->assertSame(['Old'], array_column($data['inprogress'], 'name'));
        $this->assertSame(['Done', 'Done long ago'], array_column($data['completed'], 'name'));
        $this->assertSame(2, $data['completedcount']);
        $this->assertNotEmpty($data['completed'][0]['datecompleted']);
        $this->assertFalse($data['hasdeadlines']);
        $this->assertNull($data['certificates']);
    }
}
