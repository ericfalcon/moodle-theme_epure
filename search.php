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

/**
 * All the results of a search in the courses of the user: their courses and the activities of all their courses, the
 * best first, where the quick search shows the first six and searches the thirty courses opened last.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_epure\quick_search;

require_login(null, false);
if (isguestuser()) {
    redirect(new moodle_url('/'));
}

$query = trim(core_text::substr(optional_param('q', '', PARAM_TEXT), 0, 100));

$PAGE->set_context(context_system::instance());
$PAGE->set_url(quick_search::results_url($query));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('quicksearch_mine', 'theme_epure'));
$PAGE->set_heading(get_string('quicksearch_mine', 'theme_epure'));

$context = [
    'action' => quick_search::results_url()->out(false),
    'query' => $query,
    'searched' => $query !== '',
];
$words = array_filter(explode(' ', quick_search::normalise($query)));
if ($words && core_text::strlen(implode('', $words)) >= 2) {
    [$courses, $activities] = quick_search::mine($words, quick_search::RESULTS, 0);
    $context['groups'] = array_values(array_filter([
        ['key' => 'mycourses', 'label' => get_string('mycourses'), 'items' => $courses, 'count' => count($courses)],
        ['key' => 'activities', 'label' => get_string('quicksearch_activities', 'theme_epure'), 'items' => $activities,
            'count' => count($activities)],
    ], fn($group) => $group['items']));
    // More than the hundred best of a group: to refine.
    $context['more'] = count($courses) >= quick_search::RESULTS || count($activities) >= quick_search::RESULTS;
    $context['limit'] = quick_search::RESULTS;
    $context['summary'] = !$courses && !$activities ? '' : get_string('quicksearch_found', 'theme_epure', [
        'courses' => count($courses),
        'activities' => count($activities),
        'query' => $query,
    ]);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_epure/search_results', $context);
echo $OUTPUT->footer();
