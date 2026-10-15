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

namespace theme_epure\output;

use mod_quiz\output\attempt_summary_information;
use mod_quiz\output\navigation_panel_attempt;
use mod_quiz\output\navigation_panel_base;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use theme_epure\activity_state;

/**
 * Renderer of the quizzes: a choice is chosen by a click on its row; the summary of an attempt tells what is left
 * before submitting, with the buttons to submit at the top as at the bottom; the panel of the questions explains its
 * marks.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_renderer extends \mod_quiz\output\renderer {
    /** @var array|null The attempt reviewed and what can be seen of it, while its page is made. */
    protected ?array $review = null;

    /**
     * Review of an attempt, with its result at the top ({@see review_result()}).
     *
     * @param quiz_attempt $attemptobj Attempt.
     * @param array $slots Questions of the page.
     * @param int $page Page.
     * @param bool $showall Whether all the questions are on the page.
     * @param bool $lastpage Whether it is the last page.
     * @param display_options $displayoptions What can be seen.
     * @param attempt_summary_information|array $summarydata Summary.
     * @return string HTML.
     */
    public function review_page(
        quiz_attempt $attemptobj,
        $slots,
        $page,
        $showall,
        $lastpage,
        display_options $displayoptions,
        $summarydata
    ) {
        $this->review = ['attempt' => $attemptobj, 'options' => $displayoptions, 'slots' => $slots];
        try {
            return parent::review_page($attemptobj, $slots, $page, $showall, $lastpage, $displayoptions, $summarydata);
        } finally {
            $this->review = null;
        }
    }

    /**
     * Summary of the attempt reviewed: its result first, on the first page.
     *
     * @param attempt_summary_information $summarydata Summary.
     * @param int $page Page.
     * @return string HTML.
     */
    public function review_attempt_summary(attempt_summary_information $summarydata, int $page): string {
        $result = $page == 0 && $this->review ? $this->review_result(
            $this->review['attempt'],
            $this->review['options'],
            $this->review['slots']
        ) : '';
        return $result . parent::review_attempt_summary($summarydata, $page);
    }

    /**
     * Result of an attempt reviewed, when its grade can be seen: the grade, passed or not, and how many questions are
     * right, partly right, wrong or not answered, with a button to show only the questions to look at again.
     *
     * @param quiz_attempt $attemptobj Attempt.
     * @param display_options $options What can be seen.
     * @param int[] $slots Questions of the page.
     * @return string HTML, empty when the grade cannot be seen.
     */
    public function review_result(quiz_attempt $attemptobj, display_options $options, array $slots): string {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $quiz = $attemptobj->get_quiz();
        if (
            $options->marks < \question_display_options::MARK_AND_MAX || !quiz_has_grades($quiz)
                || $attemptobj->get_state() != quiz_attempt::FINISHED
        ) {
            return '';
        }
        $grade = quiz_rescale_grade($attemptobj->get_sum_marks(), $quiz, false);
        if ($grade === null) {
            return '';
        }
        $percent = $quiz->grade > 0 ? (int) round(100 * $grade / $quiz->grade) : 0;
        $data = [
            'grade' => quiz_format_grade($quiz, $grade),
            'max' => quiz_format_grade($quiz, $quiz->grade),
            'percent' => max(0, min(100, $percent)),
            'state' => null,
            'counts' => [],
        ];
        // Passed or not, when the quiz has a grade to pass.
        $item = \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0,
            'courseid' => $quiz->course]);
        if ($item && $item->gradepass > 0) {
            $export = activity_state::export($grade >= $item->gradepass - 0.000001 ? 'passed' : 'failed');
            $data['state'] = ['state' => $export['state'], 'icon' => $export['icon'], 'text' => $export['label']];
        }
        // The questions right, partly right, wrong and not answered, when their correctness can be seen.
        if ($options->correctness) {
            $counts = [];
            foreach ($attemptobj->get_slots() as $slot) {
                $class = $attemptobj->get_question_state_class($slot, true);
                $counts[$class] = ($counts[$class] ?? 0) + 1;
            }
            foreach (
                ['correct' => 'done', 'partiallycorrect' => 'progress', 'incorrect' => 'late',
                    'notanswered' => 'todo'] as $class => $state
            ) {
                if (!empty($counts[$class])) {
                    $data['counts'][] = ['class' => $class, 'icon' => activity_state::ICONS[$state],
                        'text' => get_string('quizreview_' . $class, 'theme_epure', $counts[$class])];
                }
            }
            $data['hascounts'] = !empty($data['counts']);
            // Only the questions to look at again: on a page with several questions, some right and some not.
            $here = array_map(fn($slot) => $attemptobj->get_question_state_class($slot, true), $slots);
            $tolook = count(array_filter($here, fn($class) => $class !== 'correct'));
            if (count($here) > 1 && $tolook && $tolook < count($here)) {
                $data['filter'] = ['count' => $tolook];
                $this->page->requires->js_call_amd('theme_epure/quiz_review', 'init');
            }
        }
        return $this->render_from_template('theme_epure/quiz_review_result', $data);
    }
    /**
     * Page of an attempt: a choice is chosen by a click anywhere on its row.
     *
     * @param quiz_attempt $attemptobj Attempt.
     * @param int $page Page.
     * @param \mod_quiz\access_manager $accessmanager Access manager.
     * @param array $messages Messages.
     * @param array $slots Questions of the page.
     * @param int $id Attempt id.
     * @param int $nextpage Next page.
     * @return string HTML.
     */
    public function attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $nextpage) {
        $this->page->requires->js_call_amd('theme_epure/quiz_attempt', 'init');
        return parent::attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $nextpage);
    }

    /**
     * Summary of an attempt, with what is left to do and the buttons to submit above the table of the questions.
     *
     * @param quiz_attempt $attemptobj Attempt.
     * @param \mod_quiz\question\display_options $displayoptions Options.
     * @return string HTML.
     */
    public function summary_page($attemptobj, $displayoptions) {
        $output = '';
        $output .= $this->header();
        $output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
        $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
        $output .= $this->heading(get_string('summaryofattempt', 'quiz'), 3);
        $output .= $this->summary_check($attemptobj);
        $output .= $this->summary_table($attemptobj, $displayoptions);
        $output .= $this->summary_page_controls($attemptobj);
        $output .= $this->footer();
        return $output;
    }

    /**
     * What is left before submitting an attempt in progress (questions not answered, flagged), with the buttons to go
     * back and to submit. The button to submit opens the confirmation of the button at the bottom of the page.
     *
     * @param quiz_attempt $attemptobj Attempt.
     * @return string HTML, empty when the attempt is not in progress.
     */
    public function summary_check($attemptobj): string {
        if ($attemptobj->get_state() != quiz_attempt::IN_PROGRESS) {
            return '';
        }
        $data = [
            'returnurl' => (new \moodle_url($attemptobj->attempt_url(null, $attemptobj->get_currentpage())))->out(false),
            'items' => [],
        ];
        // Moodle only counts the questions not answered when the learner can go back to them.
        if ($attemptobj->get_quiz()->navmethod == 'free') {
            $unanswered = $attemptobj->get_number_of_unanswered_questions();
            $data['items'][] = $unanswered ? [
                'icon' => 'fa fa-circle-exclamation',
                'text' => $unanswered === 1 ? get_string('quizcheck_unanswered_one', 'theme_epure')
                    : get_string('quizcheck_unanswered', 'theme_epure', $unanswered),
                'warning' => true,
            ] : ['icon' => 'fa fa-circle-check', 'text' => get_string('quizcheck_allanswered', 'theme_epure')];
            $flagged = count(array_filter($attemptobj->get_slots(), fn($slot) => $attemptobj->is_question_flagged($slot)));
            if ($flagged) {
                $data['items'][] = [
                    'icon' => 'fa fa-flag',
                    'text' => $flagged === 1 ? get_string('quizcheck_flagged_one', 'theme_epure')
                        : get_string('quizcheck_flagged', 'theme_epure', $flagged),
                ];
            }
        }
        $data['hasitems'] = !empty($data['items']);
        $this->page->requires->js_call_amd('theme_epure/quiz_summary', 'init');
        return $this->render_from_template('theme_epure/quiz_summary_check', $data);
    }

    /**
     * Panel of the questions, with the meaning of its marks during an attempt.
     *
     * @param navigation_panel_base $panel Panel.
     * @return string HTML.
     */
    public function navigation_panel(navigation_panel_base $panel) {
        $output = parent::navigation_panel($panel);
        if (!$panel instanceof navigation_panel_attempt) {
            return $output;
        }
        $legend = $this->render_from_template('theme_epure/quiz_legend', []);
        $position = strpos($output, '<div class="othernav">');
        return $position === false ? $output . $legend : substr_replace($output, $legend, $position, 0);
    }
}
