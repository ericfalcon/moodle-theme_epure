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
use theme_epure\output\mod_quiz_renderer;

#[\PHPUnit\Framework\Attributes\CoversClass(mod_quiz_renderer::class)]
/**
 * Tests of the renderer of the quizzes.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\output\mod_quiz_renderer
 */
final class quiz_renderer_test extends \advanced_testcase {
    /**
     * An attempt of a learner at a quiz of three questions, the first one answered and flagged.
     *
     * @param string $navmethod Navigation of the quiz.
     * @return quiz_attempt
     */
    protected function attempt(string $navmethod = 'free'): quiz_attempt {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $quiz = $generator->create_module('quiz', ['course' => $course->id, 'navmethod' => $navmethod]);
        $questions = $generator->get_plugin_generator('core_question');
        $category = $questions->create_question_category(['contextid' => \context_module::instance($quiz->cmid)->id]);
        for ($i = 0; $i < 3; $i++) {
            quiz_add_quiz_question($questions->create_question('truefalse', null, ['category' => $category->id])->id, $quiz);
        }
        quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
        $this->setUser($user);
        $quizobj = quiz_settings::create($quiz->id, $user->id);
        $attempt = quiz_prepare_and_start_new_attempt($quizobj, 1, null);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions(time(), false, [1 => ['answer' => 1]]);
        $quba = \question_engine::load_questions_usage_by_activity($attempt->uniqueid);
        $quba->get_question_attempt(1)->set_flagged(true);
        \question_engine::save_questions_usage_by_activity($quba);
        return quiz_attempt::create($attempt->id);
    }

    /**
     * The summary tells the questions not answered and flagged, with the buttons to go back and to submit.
     */
    public function test_summary_check(): void {
        global $PAGE;
        $attemptobj = $this->attempt();
        $html = (new mod_quiz_renderer($PAGE, RENDERER_TARGET_GENERAL))->summary_check($attemptobj);
        $this->assertStringContainsString(get_string('quizcheck_unanswered', 'theme_epure', 2), $html);
        $this->assertStringContainsString(get_string('quizcheck_flagged_one', 'theme_epure'), $html);
        $this->assertStringContainsString('data-action="epure-quiz-finish"', $html);
        $this->assertStringContainsString(get_string('returnattempt', 'quiz'), $html);
    }

    /**
     * When the learner cannot go back to the questions, nothing is counted; the buttons stay.
     */
    public function test_summary_check_sequential(): void {
        global $PAGE;
        $attemptobj = $this->attempt('sequential');
        $html = (new mod_quiz_renderer($PAGE, RENDERER_TARGET_GENERAL))->summary_check($attemptobj);
        $this->assertStringNotContainsString('epure-quiz-check-items', $html);
        $this->assertStringContainsString('data-action="epure-quiz-finish"', $html);
    }
}
