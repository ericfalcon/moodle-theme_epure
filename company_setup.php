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
 * Dismisses the getting started of an IOMAD company, offered on IOMAD's dashboard ({@see \theme_epure\company_setup}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_epure\company_setup;
use theme_epure\iomad;

require_login();
require_sesskey();
$companyid = required_param('id', PARAM_INT);
if (!iomad::installed() || !company_setup::allowed(iomad::company($companyid))) {
    throw new \moodle_exception('nopermissions', 'error', '', get_string('setup_dismiss', 'theme_epure'));
}
company_setup::finish($companyid);
redirect(iomad::url('dashboard'));
