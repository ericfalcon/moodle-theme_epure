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

/**
 * The page « To do » of a learner: the activities still to do with a deadline, in all their courses, grouped by
 * deadline and filtered by course and by period ({@see \theme_epure\todo}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_epure\todo;

require_login(null, false);
if (isguestuser()) {
    throw new \require_login_exception('guestsnotallowed');
}
$courseid = optional_param('course', 0, PARAM_INT);
$period = optional_param('period', '', PARAM_ALPHA);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(todo::url(['course' => $courseid, 'period' => $period]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('todo_title', 'theme_epure'));
$PAGE->set_heading(get_string('todo_title', 'theme_epure'));
$PAGE->navbar->add(get_string('todo_title', 'theme_epure'), todo::url());

$output = $PAGE->get_renderer('core');
echo $OUTPUT->header();
echo $output->render_from_template('theme_epure/todo', todo::export($output, (int) $USER->id, $courseid, $period));
echo $OUTPUT->footer();
