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

use theme_epure\iomad;
use theme_epure\management;

$context = context_system::instance();
require_login();
// The dashboard of an IOMAD company, or of the whole platform. A manager of a company who does not see the reports of
// the site gets the one of their company.
$companyid = optional_param('companyid', -1, PARAM_INT);
if ($companyid < 0) {
    $companyid = iomad::installed() && !has_capability(management::CAPABILITY, $context) ? iomad::my_companyid() : 0;
}
if (!management::visible_to($companyid, (int) $USER->id)) {
    throw new required_capability_exception($context, management::CAPABILITY, 'nopermissions', '');
}
$params = $companyid ? ['companyid' => $companyid] : [];
// The category of the scores of accessibility of the courses, 0 for all.
$categoryid = optional_param('categoryid', 0, PARAM_INT);
if (has_capability('moodle/site:config', $context)) {
    // In the administration, under the reports.
    admin_externalpage_setup('theme_epure_management', '', $params);
} else {
    // The managers who do not configure the site: Moodle builds the settings of the themes only for those who do, so
    // the page sets itself up as a report.
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/theme/epure/management.php', $params));
    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('management', 'theme_epure'));
    $PAGE->set_heading($SITE->fullname);
}

echo $OUTPUT->header();
$data = management::export(null, $companyid, management::choices(), $categoryid);
echo $OUTPUT->render_from_template('theme_epure/management', $data);
echo $OUTPUT->footer();
