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
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service of the quick search (Ctrl+K) for the visitors who are not logged in, and the guests: what they can see
 * without an account (the courses of the catalogue, the categories, the public pages), on a site open without logging
 * in. Called without a session key, as the other public services of Moodle.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quick_search_public extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return quick_search::execute_parameters();
    }

    /**
     * Searches the courses of the catalogue, the categories and the public pages.
     *
     * @param string $query Text searched.
     * @return array
     */
    public static function execute(string $query): array {
        global $PAGE;
        ['query' => $query] = self::validate_parameters(self::execute_parameters(), ['query' => $query]);
        // No context to validate: the service is open without logging in; what is searched is what the visitor sees.
        $PAGE->set_context(\context_system::instance());
        if (!\theme_epure\quick_search::enabled() || !\theme_epure\quick_search::visitor()) {
            return ['groups' => []];
        }
        return ['groups' => \theme_epure\quick_search::search_public(\core_text::substr($query, 0, 100))];
    }

    /**
     * Result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return quick_search::execute_returns();
    }
}
