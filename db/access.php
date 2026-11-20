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
 * Capabilities of theme_epure: the guide « Accessibility of the course », to open it and to confirm its points.
 *
 * Given by default to the roles that change a course (editing teacher, manager), and to the roles that had
 * moodle/course:update (clonepermissionsfrom): nothing changes on an upgrade. A site can then keep the confirmations
 * to an accessibility referent, or open the guide, to read it, to a role that does not change the course.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Open the guide « Accessibility of the course » of a course, and its line in the edit mode and in « My courses ».
    'theme/epure:useaccessibilityguide' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],
    // Confirm the points that only a person can tell (subtitles, documents, instructions, colours…), for all the
    // teachers of the course.
    'theme/epure:confirmaccessibility' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],
];
