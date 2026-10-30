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
 * « My summary »: the courses the user takes, their progress, the activities completed, the grades and the
 * certificates, on a page to print or to save as PDF. Their own data only.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login(null, false);
if (isguestuser() || !\theme_epure\learner_summary::enabled()) {
    redirect(new moodle_url('/my/'));
}

// The system context: a plain heading, without the picture of the user and their messages.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(\theme_epure\learner_summary::url());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mysummary', 'theme_epure'));
$PAGE->set_heading(get_string('mysummary', 'theme_epure'));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'theme_epure/learner_summary',
    \theme_epure\learner_summary::export($OUTPUT, (int) $USER->id)
);
echo $OUTPUT->footer();
