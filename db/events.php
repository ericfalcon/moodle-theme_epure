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
 * Event observers of theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// The vocabulary fields of the IOMAD company form are saved with the company. Without IOMAD, these events never happen.
$observers = [
    [
        'eventname' => '\block_iomad_company_admin\event\company_created',
        'callback' => '\theme_epure\observer::company_saved',
    ],
    [
        'eventname' => '\block_iomad_company_admin\event\company_updated',
        'callback' => '\theme_epure\observer::company_saved',
    ],
];
