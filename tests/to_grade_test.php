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

use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

#[\PHPUnit\Framework\Attributes\CoversClass(mycourses::class)]
/**
 * Tests for what waits for a grade in a course: the submissions of the assignments, the quizzes graded by hand.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\mycourses
 */
final class to_grade_test extends \advanced_testcase {
    /**
     * A submitted assignment and a finished attempt with an essay count, each leading to its grading page; for the
     * teacher, not for the learner.
     */
    public function test_to_grade(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');

        $this->setUser($teacher);
        $this->assertSame([], mycourses::to_grade($course));

        // A submission waiting for a grade.
        $assign = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Report',
            'assignsubmission_onlinetext_enabled' => 1]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $instance = new \assign(\context_module::instance($cm->id), $cm, $course);
        $submission = $instance->get_user_submission($learner->id, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $submission->timemodified = time();
        $DB->update_record('assign_submission', $submission);

        // A quiz with an essay, answered.
        $quiz = $generator->create_module('quiz', ['course' => $course->id, 'name' => 'Essay']);
        $questions = $generator->get_plugin_generator('core_question');
        $category = $questions->create_question_category(['contextid' => \context_module::instance($quiz->cmid)->id]);
        quiz_add_quiz_question($questions->create_question('essay', null, ['category' => $category->id])->id, $quiz);
        quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
        $this->setUser($learner);
        $attempt = quiz_prepare_and_start_new_attempt(quiz_settings::create($quiz->id, $learner->id), 1, null);
        $attempt = quiz_attempt::create($attempt->id);
        $attempt->process_submitted_actions(time(), false, [1 => ['answer' => 'Wash hands.', 'answerformat' => FORMAT_HTML]]);
        if (method_exists($attempt, 'process_submit')) {
            $attempt->process_submit(time(), false);
            $attempt->process_grade_submission(time());
        } else {
            $attempt->process_finish(time(), false);
        }

        $this->assertSame([], mycourses::to_grade($course));
        $this->setUser($teacher);
        $items = mycourses::to_grade($course);
        $this->assertSame(['Report', 'Essay'], array_column($items, 'name'));
        $this->assertSame([1, 1], array_column($items, 'count'));
        $this->assertStringContainsString('action=grader', $items[0]['url']);
        $this->assertStringContainsString('mode=grading', $items[1]['url']);
    }
}
