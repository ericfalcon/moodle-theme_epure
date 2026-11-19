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
 * Download of the accessibility of all the courses of the management dashboard ({@see \theme_epure\accessibility_scores}),
 * of a category or of an IOMAD company, in a format of Moodle: each course with its score, its mistakes by severity and
 * when it was read. For the files of a quality process or an annual audit.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_epure\accessibility_scores;
use theme_epure\management;

require_login(null, false);
$dataformat = required_param('dataformat', PARAM_ALPHA);
$companyid = optional_param('companyid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$context = context_system::instance();
if (!management::visible_to($companyid, (int) $USER->id) || !accessibility_scores::shown($companyid)) {
    throw new required_capability_exception($context, management::CAPABILITY, 'nopermissions', '');
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/epure/a11yscores_download.php'));

$columns = [];
foreach (array_keys(accessibility_scores::download_row(null)) as $column) {
    $columns[$column] = get_string('a11yscores_column_' . $column, 'theme_epure');
}
$rows = array_map(
    [accessibility_scores::class, 'download_row'],
    array_values(accessibility_scores::latest($categoryid, $companyid))
);
\core\dataformat::download_data(
    clean_filename(get_string('a11yscores_downloadname', 'theme_epure') . '-' . userdate(time(), '%Y-%m-%d')),
    $dataformat,
    $columns,
    new ArrayIterator($rows)
);
