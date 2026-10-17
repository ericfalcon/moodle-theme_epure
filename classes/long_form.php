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

namespace theme_epure;

/**
 * Long settings forms (course, activity, section): the buttons to save stay at the bottom of the screen, and a list
 * of the sections of the form beside it leads to each one.
 *
 * Only the look and the way to reach the sections change: the fields, their order and the saving stay Moodle's.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class long_form {
    /** @var string[] Types of the pages of the long forms, besides the settings of the activities (mod-*-mod). */
    public const PAGETYPES = [
        'course-edit',
        'course-editsection',
        'blocks-iomad_company_admin-company_course_create_form',
    ];

    /**
     * Whether a page is one of the long forms.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        $type = $page->pagetype;
        return in_array($type, self::PAGETYPES, true) || preg_match('/^mod-[a-z0-9_]+-mod$/', $type) === 1;
    }
}
