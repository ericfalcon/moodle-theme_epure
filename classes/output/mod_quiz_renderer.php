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

use mod_quiz\output\navigation_panel_attempt;
use mod_quiz\output\navigation_panel_base;
use mod_quiz\quiz_attempt;

/**
 * Renderer of the quizzes: the summary of an attempt tells what is left before submitting, with the buttons to submit
 * at the top as at the bottom; the panel of the questions explains its marks.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_renderer extends \mod_quiz\output\renderer {
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
