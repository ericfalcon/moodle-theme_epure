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

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_epure/coverage', \theme_epure\accessibility_coverage::export($model));
echo $OUTPUT->footer();
