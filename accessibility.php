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
 * Accessibility statement of the platform. Public: it must be readable without an account.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// The statement is public, like the legal notices: no login is required.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

use theme_epure\accessibility_statement;

// The statement of an IOMAD company that published its own (its users, its login page, its address); else, and for
// a company without one, the statement of the platform.
$companyid = optional_param('companyid', -1, PARAM_INT);
if ($companyid < 0) {
    $companyid = accessibility_statement::company();
}
if ($companyid && (!accessibility_statement::company_exists($companyid) || accessibility_statement::status($companyid) === null)) {
    $companyid = 0;
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(accessibility_statement::url($companyid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('a11ystatement', 'theme_epure'));
$PAGE->set_heading(format_string($SITE->fullname));

echo $OUTPUT->header();
$data = accessibility_statement::export($companyid);
// Who can change it finds the guided form from the statement.
$data['editurl'] = accessibility_statement::can_edit($companyid)
    ? accessibility_statement::edit_url(1, $companyid)->out(false) : '';
echo $OUTPUT->render_from_template('theme_epure/accessibility_statement', $data);
echo $OUTPUT->footer();
