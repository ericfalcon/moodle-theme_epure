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
 * Management dashboard of the platform ({@see \theme_epure\management}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();
if (has_capability('moodle/site:config', $context)) {
    // In the administration, under the reports.
    admin_externalpage_setup('theme_epure_management');
} else {
    // The managers who see the reports of the site without configuring it: Moodle builds the settings of the themes
    // only for those who configure the site, so the page sets itself up as a report.
    require_login();
    require_capability(\theme_epure\management::CAPABILITY, $context);
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/theme/epure/management.php'));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('management', 'theme_epure'));
    $PAGE->set_heading($SITE->fullname);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_epure/management', \theme_epure\management::export());
echo $OUTPUT->footer();
