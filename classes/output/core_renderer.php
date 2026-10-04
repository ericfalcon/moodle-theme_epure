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

namespace theme_epure\output;

/**
 * Core renderer for Épure: uses the theme logos when they are set.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Logo shown in the header.
     *
     * When the header uses the brand colour, the logo for coloured backgrounds is preferred.
     * Falls back to the compact logo set in Appearance > Logos.
     *
     * @param int $maxwidth Maximum width, used by the core logo only.
     * @param int $maxheight Maximum height, used by the core logo only.
     * @return \moodle_url|false
     */
    public function get_compact_logo_url($maxwidth = 300, $maxheight = 300) {
        $theme = $this->page->theme;
        $settings = ($theme->settings->headerstyle ?? '') === 'brand' ? ['logoonbrand', 'logo'] : ['logo'];
        foreach ($settings as $setting) {
            if ($url = $this->theme_file_url($setting)) {
                return $url;
            }
        }
        return parent::get_compact_logo_url($maxwidth, $maxheight);
    }

    /**
     * Logo shown on the login page and the site home.
     *
     * Falls back to the logo set in Appearance > Logos.
     *
     * @param int|null $maxwidth Maximum width, used by the core logo only.
     * @param int $maxheight Maximum height, used by the core logo only.
     * @return \moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        if ($url = $this->theme_file_url('logo')) {
            return $url;
        }
        return parent::get_logo_url($maxwidth, $maxheight);
    }

    /**
     * URL of a file uploaded in a theme setting, where the file area has the setting name.
     *
     * The theme revision is part of the URL, so browsers fetch the new file after a change.
     *
     * @param string $setting Setting name.
     * @return \moodle_url|null Null when no file is set.
     */
    protected function theme_file_url(string $setting): ?\moodle_url {
        $path = $this->page->theme->settings->$setting ?? '';
        if ($path === '') {
            return null;
        }
        $filepath = rtrim(dirname($path), '/') . '/';
        return \moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'theme_epure',
            $setting,
            theme_get_revision(),
            $filepath,
            basename($path)
        );
    }
}
