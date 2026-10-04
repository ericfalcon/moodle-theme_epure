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

use theme_epure\vocabulary\company;

/**
 * Hook callbacks of theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Enables the string manager that applies the IOMAD vocabulary, when words were chosen.
     *
     * Moodle reads the custom string manager from config.php; the plugin sets it for the request
     * instead, so that the administrator does not have to edit config.php. A string manager
     * already set in config.php is kept.
     *
     * @param \core\hook\after_config $hook The hook.
     */
    public static function after_config(\core\hook\after_config $hook): void {
        global $CFG;
        if (
            during_initial_install() || !empty($CFG->config_php_settings['customstringmanager'])
                || !company_style::iomad_installed() || !company::active()
        ) {
            return;
        }
        $CFG->config_php_settings['customstringmanager'] = string_manager::class;
        get_string_manager(true);
    }

    /**
     * Whether the vocabulary of the companies can be applied on this site.
     *
     * @return bool False when config.php sets another custom string manager.
     */
    public static function string_manager_available(): bool {
        global $CFG;
        $custom = ltrim((string) ($CFG->config_php_settings['customstringmanager'] ?? ''), '\\');
        return $custom === '' || $custom === string_manager::class;
    }
}
