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
        $this->assertSame(get_string('learnerresume', 'theme_epure'), $data['resume']['heading']);
        $this->assertSame(['Old'], array_column($data['inprogress'], 'name'));
        $this->assertSame(['Done', 'Done long ago'], array_column($data['completed'], 'name'));
        $this->assertSame(2, $data['completedcount']);
        $this->assertNotEmpty($data['completed'][0]['datecompleted']);
        $this->assertFalse($data['week']['hasweek']);
        $this->assertNull($data['certificates']);
    }

    /**
     * With deadlines in several courses, the course whose activity to continue is due the soonest is resumed, even if
     * another one was visited last; the others tell their deadline in the list of the courses in progress.
     */
    public function test_export_urgent(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $learner = $generator->create_user();
        $courses = [];
        foreach (['Visited' => time() + DAYSECS, 'Tonight' => time() + 5 * HOURSECS, 'Later' => null] as $name => $due) {
            $courses[$name] = $generator->create_course(['fullname' => $name, 'enablecompletion' => 1]);
            $generator->create_module('assign', ['course' => $courses[$name]->id, 'duedate' => $due ?? 0,
                'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionsubmit' => 1]);
            $generator->enrol_user($learner->id, $courses[$name]->id, 'student');
        }
        $DB->insert_record('user_lastaccess', ['userid' => $learner->id, 'courseid' => $courses['Visited']->id,
            'timeaccess' => time()]);
        $this->setUser($learner);

        $data = learner_dashboard::export($PAGE->get_renderer('core'));
        $this->assertSame('Tonight', $data['resume']['name']);
        // Brought up by its deadline, never opened and nothing done: « Next deadline », and « Start ».
        $this->assertSame(get_string('learnerresumedeadline', 'theme_epure'), $data['resume']['heading']);
        $this->assertSame(get_string('mycourses_start', 'theme_epure'), $data['resume']['action']);
        // Opened, with nothing done yet: « Continue ».
        $this->assertSame('', $data['resume']['detail']);
        $actions = array_column($data['inprogress'], 'action', 'name');
        $this->assertSame(get_string('mycourses_continue', 'theme_epure'), $actions['Visited']);
        $states = array_column($data['inprogress'], 'urgentstate', 'name');
        $this->assertSame('soon', $states['Visited']['state']);
        $this->assertNull($states['Later']);
    }

    /**
     * The week groups the deadlines by day, from today to the same day next week; the activities overdue come first.
     */
    public function test_week_days(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user(['timezone' => 'UTC']));
        $now = make_timestamp(2026, 10, 15, 10);
        $item = fn(string $name, string $base, int $due) => ['cmid' => crc32($name), 'name' => $name, 'url' => '#',
            'icon' => '', 'base' => $base, 'due' => $due];
        $card = ['id' => 2, 'name' => 'Safety', 'finished' => [], 'dated' => [
            $item('Next week', 'todo', $now + 8 * DAYSECS),
            $item('Tonight', 'todo', $now + 8 * HOURSECS),
            $item('Monday', 'progress', $now + 4 * DAYSECS),
            $item('Tomorrow', 'todo', $now + DAYSECS),
            $item('Sunday', 'todo', $now + 3 * DAYSECS),
            $item('Missed', 'todo', $now - 3 * DAYSECS),
            $item('This morning', 'progress', $now - HOURSECS),
        ]];
        $week = learner_dashboard::week([$card], (int) $GLOBALS['USER']->id, $now);

        $day = fn(int $days) => userdate($now + $days * DAYSECS, get_string('strftimedayshort', 'langconfig'));
        $labels = [get_string('today', 'calendar'), get_string('tomorrow', 'calendar'), $day(3), $day(4)];
        $this->assertSame($labels, array_column($week['days'], 'label'));
        $this->assertSame(['Tonight'], array_column($week['days'][0]['items'], 'name'));
        $this->assertSame('soon', $week['days'][0]['items'][0]['state']['state']);
        $this->assertSame('Safety', $week['days'][0]['items'][0]['course']);
        $this->assertSame(['Tomorrow'], array_column($week['days'][1]['items'], 'name'));
        // An activity started tells it; one to do does not, its time tells when to do it.
        $this->assertNull($week['days'][2]['items'][0]['state']);
        $this->assertSame('progress', $week['days'][3]['items'][0]['state']['state']);
        // Overdue: the most recently first, with their date.
        $this->assertSame(['This morning', 'Missed'], array_column($week['late'], 'name'));
        $this->assertSame(userdate($now - HOURSECS, get_string('strftimedatetimeshort', 'langconfig')), $week['late'][0]['time']);
        $this->assertTrue($week['haslate']);

        $empty = learner_dashboard::week([['id' => 2, 'name' => 'Safety']], (int) $GLOBALS['USER']->id, $now);
        $this->assertFalse($empty['hasweek']);
    }

    /**
     * The week shows the deadlines of the activities with their state, those finished too, and the sessions of the
     * calendar of the courses, of the groups and of the user; not the events of others.
     */
    public function test_week_calendar(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $learner = $generator->create_user();
        $other = $generator->create_user();
        $course = $generator->create_course(['fullname' => 'Safety', 'enablecompletion' => 1]);
        $generator->enrol_user($learner->id, $course->id, 'student');
        $generator->enrol_user($other->id, $course->id, 'student');
        $group = $generator->create_group(['courseid' => $course->id]);
        $othergroup = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $learner->id]);
        $assign = ['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionsubmit' => 1];
        $generator->create_module('assign', $assign + ['name' => 'Report', 'duedate' => time() + DAYSECS]);
        $submitted = $generator->create_module('assign', $assign + ['name' => 'Essay', 'duedate' => time() + 2 * DAYSECS]);
        $generator->create_module('assign', $assign + ['name' => 'Missed', 'duedate' => time() - DAYSECS]);
        $completion = new \completion_info($course);
        $cm = get_fast_modinfo($course)->get_cm($submitted->cmid);
        $data = $completion->get_data($cm, false, $learner->id);
        $data->completionstate = COMPLETION_COMPLETE;
        $data->timemodified = time();
        $completion->internal_set_data($cm, $data);
        $this->setAdminUser();
        $session = ['timestart' => time() + 3 * DAYSECS, 'timeduration' => HOURSECS];
        $generator->create_event($session + ['name' => 'Class', 'eventtype' => 'course', 'courseid' => $course->id]);
        $generator->create_event($session + ['name' => 'Group work', 'eventtype' => 'group', 'courseid' => $course->id,
            'groupid' => $group->id]);
        $generator->create_event($session + ['name' => 'Other group', 'eventtype' => 'group', 'courseid' => $course->id,
            'groupid' => $othergroup->id]);
        $generator->create_event($session + ['name' => 'Mine', 'eventtype' => 'user', 'userid' => $learner->id]);
        $generator->create_event($session + ['name' => 'Not mine', 'eventtype' => 'user', 'userid' => $other->id]);
        $generator->create_event(['name' => 'Ended', 'eventtype' => 'course', 'courseid' => $course->id,
            'timestart' => time() - HOURSECS, 'timeduration' => 60]);
        $this->setUser($learner);

        $week = learner_dashboard::export($PAGE->get_renderer('core'))['week'];
        $items = array_merge(...array_column($week['days'], 'items'));
        $names = array_column($items, 'name');
        $states = array_column($items, 'state', 'name');
        // The name of the deadline in the calendar, with the state of the activity.
        $report = preg_grep('/Report/', $names);
        $this->assertCount(1, $report);
        $this->assertSame('soon', $states[reset($report)]['state']);
        $essay = preg_grep('/Essay/', $names);
        $this->assertCount(1, $essay);
        $this->assertSame('done', $states[reset($essay)]['state']);
        foreach (['Class', 'Group work', 'Mine'] as $name) {
            $this->assertContains($name, $names);
            $this->assertNull($states[$name]);
        }
        foreach (['Other group', 'Not mine', 'Ended'] as $name) {
            $this->assertNotContains($name, $names);
        }
        $this->assertSame(['Missed'], array_column($week['late'], 'name'));
    }
}
