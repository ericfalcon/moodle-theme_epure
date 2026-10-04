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

namespace theme_epure\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy provider for theme_epure.
 *
 * The theme stores the display preferences of each user (button « Aa »), as user preferences.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describes the data stored by the theme.
     *
     * @param collection $collection Collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        foreach (array_keys(\theme_epure\a11y::definitions()) as $name) {
            $collection->add_user_preference($name, 'privacy:metadata:preference:' . substr($name, strlen('theme_epure_')));
        }
        return $collection;
    }

    /**
     * Exports the display preferences of a user.
     *
     * @param int $userid User id.
     */
    public static function export_user_preferences(int $userid) {
        foreach (array_keys(\theme_epure\a11y::definitions()) as $name) {
            $value = get_user_preferences($name, null, $userid);
            if ($value !== null) {
                writer::export_user_preference(
                    'theme_epure',
                    $name,
                    $value,
                    get_string('privacy:metadata:preference:' . substr($name, strlen('theme_epure_')), 'theme_epure')
                );
            }
        }
    }
}
