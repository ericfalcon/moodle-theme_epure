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
 * Download of the table of the courses the user teaches (« My courses »), in a format of Moodle: the figures the
 * user sees on the page, nothing more.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login(null, false);
if (isguestuser()) {
    throw new \moodle_exception('noguest');
}
$dataformat = required_param('dataformat', PARAM_ALPHA);

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url(new moodle_url('/theme/epure/mycourses_download.php'));

$columns = [];
foreach (['course', 'category', 'learners', 'active', 'followup', 'completion', 'tograde', 'deadline'] as $column) {
    $columns[$column] = get_string('mycourses_column_' . $column, 'theme_epure');
}
$rows = theme_epure\mycourses::teaching_rows((int) $USER->id);
\core\dataformat::download_data(
    clean_filename(get_string('mycourses_downloadname', 'theme_epure') . '-' . userdate(time(), '%Y-%m-%d')),
    $dataformat,
    $columns,
    new ArrayIterator($rows)
);
