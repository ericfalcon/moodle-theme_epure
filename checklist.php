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
 * Reminder « Making my courses accessible »: the points to check in a course, on one page to print and keep at hand,
 * or to give in a training. Open without logging in: it holds nothing of the platform.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- The reminder holds nothing of the platform, to give in a training.
require(__DIR__ . '/../../config.php');

$courseid = optional_param('course', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/theme/epure/checklist.php', $courseid ? ['course' => $courseid] : []));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('checklist', 'theme_epure'));
$PAGE->set_heading(get_string('checklist', 'theme_epure'));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_epure/checklist', \theme_epure\course_accessibility::checklist($courseid));
echo $OUTPUT->footer();
