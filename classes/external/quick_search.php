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

namespace theme_epure\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service of the quick search (Ctrl+K).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quick_search extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Text searched'),
        ]);
    }

    /**
     * Searches the courses, the activities and the administration pages.
     *
     * @param string $query Text searched.
     * @return array
     */
    public static function execute(string $query): array {
        ['query' => $query] = self::validate_parameters(self::execute_parameters(), ['query' => $query]);
        $context = \context_system::instance();
        self::validate_context($context);
        if (!\theme_epure\quick_search::enabled()) {
            return ['groups' => []];
        }
        return ['groups' => \theme_epure\quick_search::search(\core_text::substr($query, 0, 100))];
    }

    /**
     * Result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'groups' => new external_multiple_structure(new external_single_structure([
                'key' => new external_value(PARAM_ALPHANUMEXT, 'Group'),
                'label' => new external_value(PARAM_TEXT, 'Name of the group'),
                'items' => new external_multiple_structure(new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course or activity, 0 for the other links'),
                    'name' => new external_value(PARAM_TEXT, 'Name'),
                    'url' => new external_value(PARAM_URL, 'Link'),
                    'detail' => new external_value(PARAM_TEXT, 'Course, category or place in the administration'),
                    'icon' => new external_value(PARAM_ALPHANUMEXT, 'Font Awesome icon', VALUE_OPTIONAL),
                    'image' => new external_value(PARAM_URL, 'Icon of the activity', VALUE_OPTIONAL),
                ])),
            ])),
        ]);
    }
}
