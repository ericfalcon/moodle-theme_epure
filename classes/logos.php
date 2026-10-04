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

use moodle_url;

/**
 * Logos uploaded in the theme settings.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logos {
    /**
     * URL of an image uploaded in a theme setting whose file area has the setting name.
     *
     * The theme revision is part of the URL, so browsers fetch the new file after a change.
     *
     * @param string $setting Setting name, such as logo or logoonbrand.
     * @return moodle_url|null Null when no file is set.
     */
    public static function url(string $setting): ?moodle_url {
        $path = (string) get_config('theme_epure', $setting);
        if ($path === '') {
            return null;
        }
        $filepath = rtrim(dirname($path), '/') . '/';
        return moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'theme_epure',
            $setting,
            theme_get_revision(),
            $filepath,
            basename($path)
        );
    }

    /**
     * URL of the main logo: the theme logo, or the logo set in Appearance > Logos.
     *
     * @return moodle_url|null
     */
    public static function main_url(): ?moodle_url {
        if ($url = self::url('logo')) {
            return $url;
        }
        foreach (['logo', 'logocompact'] as $setting) {
            $file = (string) get_config('core_admin', $setting);
            if ($file !== '') {
                return moodle_url::make_pluginfile_url(
                    \context_system::instance()->id,
                    'core_admin',
                    $setting,
                    '300x200/',
                    theme_get_revision(),
                    $file
                );
            }
        }
        return null;
    }
}
