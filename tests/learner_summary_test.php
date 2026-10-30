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

#[\PHPUnit\Framework\Attributes\CoversClass(learner_summary::class)]
/**
 * Tests for « My summary ».
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\learner_summary
 */
final class learner_summary_test extends \advanced_testcase {
    /**
     * The summary is offered only when the site turns it on, with Épure as the theme.
     */
    public function test_enabled(): void {
        $this->resetAfterTest();
        set_config('theme', 'epure');
        $this->assertFalse(learner_summary::enabled());
        set_config('mysummary', 1, 'theme_epure');
        $this->assertTrue(learner_summary::enabled());
        set_config('theme', 'boost');
        $this->assertFalse(learner_summary::enabled());
    }

    /**
     * The summary lists the courses of the learner, their progress, the activities they completed with their date,
     * their grade and the date of completion of the course; the data of the others never.
     */
    public function test_export(): void {
        global $CFG, $PAGE;
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/completion/completion_completion.php');
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $learner = $generator->create_user();
        $other = $generator->create_user();
        $safety = $generator->create_course(['fullname' => 'Safety', 'enablecompletion' => 1]);
        $aid = $generator->create_course(['fullname' => 'First aid', 'enablecompletion' => 1]);
        $page = $generator->create_module('page', ['course' => $safety->id, 'name' => 'Risks',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $generator->create_module('page', ['course' => $safety->id, 'name' => 'Rules', 'completion' => COMPLETION_TRACKING_MANUAL]);
        $assign = $generator->create_module('assign', ['course' => $aid->id, 'name' => 'Report', 'grade' => 100]);
        foreach ([$learner, $other] as $user) {
            $generator->enrol_user($user->id, $safety->id, 'student');
            $generator->enrol_user($user->id, $aid->id, 'student');
        }

        // The learner completed « Risks » and the course of first aid, and got 80 in its assignment.
        $completion = new \completion_info($safety);
        $cm = get_fast_modinfo($safety)->get_cm($page->cmid);
        $completion->update_state($cm, COMPLETION_COMPLETE, $learner->id);
        $completion->update_state($cm, COMPLETION_COMPLETE, $other->id);
        (new \completion_completion(['userid' => $learner->id, 'course' => $aid->id]))->mark_complete(time() - DAYSECS);
        $grades = ['userid' => $learner->id, 'rawgrade' => 80];
        grade_update('mod/assign', $aid->id, 'mod', 'assign', $assign->id, 0, $grades);

        $this->setUser($learner);
        $data = learner_summary::export($PAGE->get_renderer('core'), (int) $learner->id);
        $this->assertSame(2, $data['taken']);
        $this->assertSame(1, $data['completedcount']);
        $courses = array_column($data['courses'], null, 'name');
        $this->assertSame(['Risks'], array_column($courses['Safety']['activities'], 'name'));
        $this->assertNotSame('', $courses['Safety']['activities'][0]['date']);
        $this->assertSame(50, $courses['Safety']['progress']);
        $this->assertNotSame('', $courses['First aid']['datecompleted']);
        $this->assertStringContainsString('80', $courses['First aid']['grade']);
        $this->assertSame(fullname($learner), $data['fullname']);

        // A course whose grades are hidden from the learners shows none.
        $aid->showgrades = 0;
        update_course($aid);
        $data = learner_summary::export($PAGE->get_renderer('core'), (int) $learner->id);
        $this->assertSame('', array_column($data['courses'], null, 'name')['First aid']['grade']);
    }
}
