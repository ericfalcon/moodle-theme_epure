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
 * The Moodle app: the display preferences of « Aa », applied to the app as soon as it opens, and their page in the
 * menu of the app (theme_epure\output\mobile).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'theme_epure' => [
        'handlers' => [
            'displaypreferences' => [
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'mobile_preferences',
                'init' => 'mobile_init',
                'displaydata' => [
                    'title' => 'a11ypanel',
                    'icon' => 'fas-font',
                ],
                'priority' => 50,
            ],
        ],
        'lang' => [
            ['a11ypanel', 'theme_epure'],
            ['a11ynotsaved', 'theme_epure'],
        ],
    ],
];
