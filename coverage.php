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
 * Page « What Épure covers in accessibility »: for each point of the referential of the country of the site (RGAA,
 * EN 301 549 or WCAG), who answers it: the theme, the guide « Accessibility of the course », the authors or Moodle.
 * Open without logging in: it holds nothing of the platform, to give to those who choose or audit it.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- The page holds nothing of the platform, to give to the auditors.
require(__DIR__ . '/../../config.php');

$model = optional_param('model', null, PARAM_ALPHA);
$model = in_array($model, \theme_epure\accessibility_coverage::MODELS, true) ? $model : null;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(\theme_epure\accessibility_coverage::url($model));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('coverage', 'theme_epure'));
$PAGE->set_heading(get_string('coverage', 'theme_epure'));

// The grid of the referential, criterion by criterion, as a file (CSV, Excel, ODS, PDF…).
$dataformat = optional_param('download', '', PARAM_ALPHA);
if ($dataformat !== '') {
    $grid = \theme_epure\accessibility_coverage::model($model);
    $columns = ['number' => get_string('coverage_grid_criterion', 'theme_epure'),
        'title' => get_string('coverage_grid_title', 'theme_epure'), 'owner' => get_string('coverage_grid_who', 'theme_epure'),
        'state' => get_string('coverage_grid_state', 'theme_epure'), 'note' => get_string('coverage_grid_remark', 'theme_epure')];
    $rows = array_map(fn(array $row): array => [
        'number' => $row['number'],
        'title' => $row['title'],
        'owner' => get_string('coverage_owner_' . $row['owner'], 'theme_epure'),
        'state' => get_string('coverage_state_' . $row['state'], 'theme_epure'),
        'note' => get_string('coverage_note_' . $row['note'], 'theme_epure'),
    ], \theme_epure\accessibility_grid::rows($grid));
    $filename = 'epure-' . ['fr' => 'rgaa', 'eu' => 'en301549', 'world' => 'wcag22'][$grid];
    \core\dataformat::download_data($filename, $dataformat, $columns, new ArrayIterator($rows));
    die();
}

echo $OUTPUT->header();
$context = \theme_epure\accessibility_coverage::export($model);
$context['downloadselector'] = $OUTPUT->download_dataformat_selector(
    get_string('coverage_grid_download', 'theme_epure'),
    $PAGE->url->out_omit_querystring(),
    'download',
    ['model' => \theme_epure\accessibility_coverage::model($model)]
);
echo $OUTPUT->render_from_template('theme_epure/coverage', $context);
echo $OUTPUT->footer();
