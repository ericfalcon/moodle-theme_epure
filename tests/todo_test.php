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
 * Tests for the page « To do » of a learner.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\todo
 */
final class todo_test extends \advanced_testcase {
    /**
     * The activities still to do with a deadline, in all the courses, grouped by deadline and filtered; not the
     * activities done, nor those without deadline.
     */
    public function test_export(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $learner = $generator->create_user();
        $now = time();
        $courses = [];
        foreach (['Safety at work', 'Managing a team'] as $name) {
            $course = $generator->create_course(['fullname' => $name, 'enablecompletion' => 1]);
            $generator->enrol_user($learner->id, $course->id, 'student');
            $courses[] = $course;
            foreach ([-3 => 'Report', 2 => 'Quiz', 20 => 'Essay'] as $days => $activity) {
                $generator->create_module('assign', ['course' => $course->id, 'name' => $activity . ' ' . $course->id,
                    'duedate' => $now + $days * DAYSECS], ['completion' => COMPLETION_TRACKING_MANUAL]);
            }
            $generator->create_module(
                'assign',
                ['course' => $course->id, 'name' => 'No deadline'],
                ['completion' => COMPLETION_TRACKING_MANUAL]
            );
        }
        // An activity done is not to do any more.
        $done = $generator->create_module('assign', ['course' => $courses[0]->id, 'name' => 'Done',
            'duedate' => $now + DAYSECS], ['completion' => COMPLETION_TRACKING_MANUAL]);
        $cm = get_fast_modinfo($courses[0])->get_cm($done->cmid);
        (new \completion_info($courses[0]))->update_state($cm, COMPLETION_COMPLETE, $learner->id);

        $this->setUser($learner);
        $output = $PAGE->get_renderer('core');
        $items = todo::items($output, (int) $learner->id, $now);
        $this->assertCount(6, $items);
        $this->assertSame(['late', 'late', 'week', 'week', 'later', 'later'], array_column($items, 'period'));
        $this->assertNotContains('Done', array_column($items, 'name'));
        $this->assertNotContains('No deadline', array_column($items, 'name'));

        $export = todo::export($output, (int) $learner->id, 0, '', $now);
        $this->assertSame(['late', 'week', 'later'], array_column($export['groups'], 'key'));
        $this->assertSame(get_string('todo_count', 'theme_epure', 6), $export['count']);
        $this->assertCount(3, $export['courses']);

        $export = todo::export($output, (int) $learner->id, (int) $courses[1]->id, 'later', $now);
        $this->assertSame(['later'], array_column($export['groups'], 'key'));
        $this->assertSame(['Essay ' . $courses[1]->id], array_column($export['groups'][0]['items'], 'name'));
        $this->assertTrue($export['filtered']);

        $export = todo::export($output, (int) $generator->create_user()->id, 0, '', $now);
        $this->assertFalse($export['hasitems']);
        $this->assertSame(get_string('todo_empty', 'theme_epure'), $export['empty']);
    }
}
