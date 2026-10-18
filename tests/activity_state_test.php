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

#[\PHPUnit\Framework\Attributes\CoversClass(activity_state::class)]
/**
 * Tests of the state of the activities for a learner.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\activity_state
 */
final class activity_state_test extends \advanced_testcase {
    /**
     * Late after the deadline, due soon in the two days before it, for an activity to do or started only.
     */
    public function test_at(): void {
        $now = 1000000;
        $this->assertSame('late', activity_state::at('todo', $now - 1, $now));
        $this->assertSame('late', activity_state::at('progress', $now - 1, $now));
        $this->assertSame('soon', activity_state::at('todo', $now + DAYSECS, $now));
        $this->assertSame('todo', activity_state::at('todo', $now + 3 * DAYSECS, $now));
        $this->assertSame('progress', activity_state::at('progress', null, $now));
        $this->assertSame('done', activity_state::at('done', $now - 1, $now));
        $this->assertSame('failed', activity_state::at('failed', $now - 1, $now));
    }

    /**
     * Labels of Moodle for to do and done, the date with the states that depend on it, and what tells more than Moodle.
     */
    public function test_export(): void {
        $this->assertSame(get_string('todo', 'completion'), activity_state::export('todo')['label']);
        $this->assertSame(get_string('done', 'completion'), activity_state::export('done')['label']);
        $late = activity_state::export('late', 1700000000);
        $this->assertStringContainsString(get_string('state_late', 'theme_epure'), $late['text']);
        $this->assertStringContainsString(userdate(1700000000, get_string('strftimedateshort', 'langconfig')), $late['text']);
        $this->assertSame(activity_state::ICONS['late'], $late['icon']);
        $this->assertTrue($late['notable']);
        $this->assertFalse(activity_state::export('todo')['notable']);
        $this->assertSame(array_keys(activity_state::ICONS), array_keys(activity_state::all()));
    }

    /**
     * The state of each kind of activity of a course, and its deadline.
     */
    public function test_course(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $this->resetAfterTest();
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $manual = ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL];
        $todo = $generator->create_module('page', $manual);
        $done = $generator->create_module('page', $manual);
        $late = $generator->create_module('assign', ['course' => $course->id, 'duedate' => time() - DAYSECS,
            'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionsubmit' => 1]);
        $started = $generator->create_module('assign', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1, 'completionsubmit' => 1]);
        $expected = $generator->create_module('page', $manual + ['completionexpected' => time() + DAYSECS]);
        $passed = $generator->create_module('quiz', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionusegrade' => 1]);
        $failed = $generator->create_module('quiz', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionusegrade' => 1]);
        $locked = $generator->create_module('page', $manual + ['availability' => json_encode(['op' => '&', 'showc' => [true],
            'c' => [['type' => 'completion', 'cm' => (int) $todo->cmid, 'e' => COMPLETION_COMPLETE]]])]);
        $optional = $generator->create_module('url', ['course' => $course->id]);

        $modinfo = get_fast_modinfo($course);
        $completion = new \completion_info($course);
        $completion->update_state($modinfo->get_cm($done->cmid), COMPLETION_COMPLETE, $user->id);
        $completion->set_module_viewed($modinfo->get_cm($started->cmid), $user->id);
        foreach ([[$passed, COMPLETION_COMPLETE_PASS], [$failed, COMPLETION_COMPLETE_FAIL]] as [$module, $state]) {
            $cm = $modinfo->get_cm($module->cmid);
            $data = $completion->get_data($cm, false, $user->id);
            $data->completionstate = $state;
            $data->timemodified = time();
            $completion->internal_set_data($cm, $data);
        }

        $states = array_column(activity_state::course(activity_state::items($course, (int) $user->id)), 'state', 'cmid');
        $this->assertSame([
            $todo->cmid => 'todo',
            $done->cmid => 'done',
            $late->cmid => 'late',
            $started->cmid => 'progress',
            $expected->cmid => 'soon',
            $passed->cmid => 'passed',
            $failed->cmid => 'failed',
            $locked->cmid => 'locked',
            $optional->cmid => 'optional',
        ], $states);
        // Each activity tells its section, for the highlight of the activity to continue.
        $section = (int) get_fast_modinfo($course)->get_cm($todo->cmid)->section;
        $this->assertSame([$section], activity_state::items($course, (int) $user->id)[0]['sections']);
        $cm = get_fast_modinfo($course, $user->id)->get_cm($late->cmid);
        $this->assertSame((int) $late->duedate, activity_state::deadline($cm, (int) $user->id));
    }

    /**
     * The activity to continue: due in the next two days first, else overdue, else started, else the one after the last
     * completed.
     */
    public function test_next(): void {
        $now = 1000000;
        $item = fn(string $base, ?int $due = null) => ['base' => $base, 'due' => $due];
        $this->assertNull(activity_state::next([$item('done'), $item('optional')], $now));
        $this->assertSame(0, activity_state::next([$item('todo'), $item('todo')], $now));
        // After the last activity completed, even with a first one left to do.
        $this->assertSame(3, activity_state::next([$item('todo'), $item('done'), $item('passed'), $item('todo')], $now));
        // Back to the first one when nothing is left after the last completed.
        $this->assertSame(0, activity_state::next([$item('todo'), $item('done')], $now));
        // A started activity before.
        $this->assertSame(2, activity_state::next([$item('done'), $item('todo'), $item('progress')], $now));
        // The closest deadline in the next two days before all; a passed one does not count.
        $this->assertSame(3, activity_state::next([$item('progress'), $item('todo', $now + DAYSECS),
            $item('todo', $now - DAYSECS), $item('todo', $now + 3600)], $now));
        // Else the activity overdue the most recently, before a started one; one done or failed is not overdue.
        $this->assertSame(2, activity_state::next([$item('progress'), $item('todo', $now - 3 * DAYSECS),
            $item('progress', $now - 3600), $item('done', $now - 60), $item('failed', $now - 60)], $now));
    }

    /**
     * What is left to do, and its sentence.
     */
    public function test_remaining(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user(['timezone' => 'UTC']));
        $now = make_timestamp(2026, 10, 15, 10);
        $items = [['base' => 'done', 'due' => null], ['base' => 'todo', 'due' => $now + DAYSECS],
            ['base' => 'failed', 'due' => null], ['base' => 'progress', 'due' => $now + 5 * DAYSECS],
            ['base' => 'locked', 'due' => null]];
        $this->assertSame(['remaining' => 3, 'due' => $now + DAYSECS], activity_state::remaining($items, $now));
        $this->assertSame(get_string('continue_remaining', 'theme_epure', 3) . ' · '
            . get_string('continue_duetomorrow', 'theme_epure'), activity_state::summary(3, $now + DAYSECS, $now));
        $this->assertSame(get_string('continue_remaining_one', 'theme_epure') . ' · '
            . get_string('continue_duedays', 'theme_epure', 5), activity_state::summary(1, $now + 5 * DAYSECS, $now));
        $today = explode(' · ', activity_state::summary(2, $now + 3600, $now))[1];
        $this->assertSame(get_string('continue_duetoday', 'theme_epure'), $today);
        $this->assertSame(get_string('continue_remaining', 'theme_epure', 2), activity_state::summary(2, null, $now));
        $this->assertSame('', activity_state::summary(0, $now + DAYSECS, $now));
    }

    /**
     * Detail of the progress: completed, in progress (also when due soon), overdue and not passed.
     */
    public function test_detail(): void {
        $now = 1000000;
        $pending = [['progress', null], ['progress', $now + 3600], ['todo', $now - 3600], ['failed', null],
            ['todo', null]];
        $this->assertSame(implode(' · ', [
            get_string('detail_done', 'theme_epure', 3),
            get_string('detail_progress', 'theme_epure', 2),
            get_string('detail_late_one', 'theme_epure', 1),
            get_string('detail_failed_one', 'theme_epure', 1),
        ]), activity_state::detail(3, $pending, $now));
        $this->assertSame('', activity_state::detail(0, [['todo', null]], $now));
    }

    /**
     * No state without completion tracking in the course.
     */
    public function test_course_without_completion(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 0]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->create_module('page', ['course' => $course->id]);
        $this->assertSame([], activity_state::items($course, (int) $user->id));
    }
}
