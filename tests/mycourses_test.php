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

#[\PHPUnit\Framework\Attributes\CoversClass(mycourses::class)]
/**
 * Tests for the « My courses » page by role.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\mycourses
 */
final class mycourses_test extends \advanced_testcase {
    /**
     * A user who teaches a course and takes another sees two sections, with the cards of each role.
     */
    public function test_export(): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();
        $CFG->enablecompletion = true;
        $generator = $this->getDataGenerator();

        $taught = $generator->create_course(['fullname' => 'Taught', 'enablecompletion' => 1]);
        $taken = $generator->create_course(['fullname' => 'Taken', 'enablecompletion' => 1]);
        $first = $generator->create_module('page', ['course' => $taken->id, 'name' => 'First',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $generator->create_module('page', ['course' => $taken->id, 'name' => 'Second',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $assign = $generator->create_module('assign', ['course' => $taught->id, 'assignsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 0]);

        $user = $generator->create_user();
        $learner = $generator->create_user();
        $generator->enrol_user($user->id, $taught->id, 'editingteacher');
        $generator->enrol_user($user->id, $taken->id, 'student');
        $generator->enrol_user($learner->id, $taught->id, 'student');

        // The learner submits the assignment of the taught course: one submission to grade.
        $generator->get_plugin_generator('mod_assign')->create_submission([
            'userid' => $learner->id,
            'cmid' => $assign->cmid,
            'onlinetext' => 'My answer',
            'status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
        ]);

        // The user completed the first page of the course they take.
        $this->setUser($user);
        $completion = new \completion_info($taken);
        $completion->update_state(get_fast_modinfo($taken)->get_cm($first->cmid), COMPLETION_COMPLETE, $user->id);

        $data = mycourses::export($PAGE->get_renderer('core'));
        $this->assertTrue($data['showheadings']);
        $this->assertSame([mycourses::TEACHING, mycourses::LEARNING], array_column($data['sections'], 'role'));

        $teaching = $data['sections'][0]['courses'][0];
        $this->assertSame('Taught', $teaching['name']);
        $this->assertTrue($teaching['teaching']);
        $this->assertSame(1, $teaching['participants']);
        $this->assertSame(1, $teaching['tograde']);
        $this->assertCount(3, $teaching['links']);

        $learning = $data['sections'][1]['courses'][0];
        $this->assertSame('Taken', $learning['name']);
        $this->assertFalse($learning['teaching']);
        $this->assertSame(50, $learning['progress']);
        $this->assertSame('Second', $learning['next']['name']);
        $this->assertSame(get_string('mycourses_continue', 'theme_epure'), $learning['action']);
    }

    /**
     * A user with one role sees one list, without section headings; hidden courses stay hidden.
     */
    public function test_one_role(): void {
        global $PAGE;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $shown = $generator->create_course();
        $hidden = $generator->create_course();
        $generator->enrol_user($user->id, $shown->id, 'student');
        $generator->enrol_user($user->id, $hidden->id, 'student');
        set_user_preference('block_myoverview_hidden_course_' . $hidden->id, 1, $user);
        $this->setUser($user);

        $data = mycourses::export($PAGE->get_renderer('core'));
        $this->assertFalse($data['showheadings']);
        $this->assertCount(1, $data['sections']);
        $this->assertSame([(int) $shown->id], array_column($data['sections'][0]['courses'], 'id'));
    }

    /**
     * A hidden course is not shown to a learner enrolled in it, as in Moodle's « My courses »; its teacher sees it.
     */
    public function test_hidden_course(): void {
        global $PAGE;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $learner = $generator->create_user();
        $teacher = $generator->create_user();
        $shown = $generator->create_course();
        $hidden = $generator->create_course(['visible' => 0]);
        foreach ([$shown, $hidden] as $course) {
            $generator->enrol_user($learner->id, $course->id, 'student');
            $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        }
        $ids = fn(array $data) => array_merge(...array_map(fn($section) => array_column($section['courses'], 'id'),
            $data['sections']));

        $this->setUser($learner);
        $this->assertSame([(int) $shown->id], $ids(mycourses::export($PAGE->get_renderer('core'))));
        $this->setUser($teacher);
        $this->assertEqualsCanonicalizing([(int) $shown->id, (int) $hidden->id],
            $ids(mycourses::export($PAGE->get_renderer('core'))));
    }

    /**
     * The progress kept in the cache follows the completion of the activities: a completion forgets it at once.
     */
    public function test_progress_cache(): void {
        global $CFG, $PAGE;
        $this->resetAfterTest();
        $CFG->enablecompletion = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $first = $generator->create_module('page', ['course' => $course->id, 'name' => 'First',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Second',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);
        $output = $PAGE->get_renderer('core');
        $card = fn() => mycourses::export($output)['sections'][0]['courses'][0];

        $this->assertSame(0, $card()['progress']);
        $this->assertSame('First', $card()['next']['name']);
        $this->assertNotFalse(\cache::make('theme_epure', 'learnerprogress')->get($user->id . '_' . $course->id));

        $completion = new \completion_info($course);
        $completion->update_state(get_fast_modinfo($course)->get_cm($first->cmid), COMPLETION_COMPLETE, $user->id);
        $this->assertSame(50, $card()['progress']);
        $this->assertSame('Second', $card()['next']['name']);

        // A new activity changes the revision of the course, which the cache follows.
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Third',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $this->assertSame(33, $card()['progress']);
    }

    /**
     * The role in the course decides, also for a site administrator: enrolled as a learner, they take the course;
     * as a teacher, or not enrolled, they teach it.
     */
    public function test_is_teaching_admin(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $learning = $generator->create_course();
        $teaching = $generator->create_course();
        $other = $generator->create_course();
        $generator->enrol_user($USER->id, $learning->id, 'student');
        $generator->enrol_user($USER->id, $teaching->id, 'editingteacher');
        $this->assertFalse(mycourses::is_teaching(\context_course::instance($learning->id), (int) $USER->id));
        $this->assertTrue(mycourses::is_teaching(\context_course::instance($teaching->id), (int) $USER->id));
        $this->assertTrue(mycourses::is_teaching(\context_course::instance($other->id), (int) $USER->id));
        // Switched to the role of a student in a course they are not enrolled in.
        $student = $DB->get_field('role', 'id', ['shortname' => 'student']);
        role_switch($student, \context_course::instance($other->id));
        $this->assertFalse(mycourses::is_teaching(\context_course::instance($other->id), (int) $USER->id));

        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $teaching->id, 'teacher');
        $this->assertTrue(mycourses::is_teaching(\context_course::instance($teaching->id), (int) $teacher->id));
        $this->assertFalse(mycourses::is_teaching(\context_course::instance($other->id), (int) $teacher->id));
    }
}
