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
     * Whether the login page uses the split layout, with the brand visual beside the form.
     *
     * @return bool
     */
    public function epure_login_split(): bool {
        return get_config('theme_epure', 'loginlayout') !== 'centered';
    }

    /**
     * Full name of the site, shown beside the logo on the login visual.
     *
     * @return string
     */
    public function epure_login_sitename(): string {
        global $SITE;
        return format_string($SITE->fullname, true, ['context' => \context_system::instance()]);
    }

    /**
     * Headline shown on the login visual.
     *
     * @return string
     */
    public function epure_login_tagline(): string {
        $tagline = trim((string) get_config('theme_epure', 'logintagline'));
        if ($tagline === '') {
            return get_string('logintagline_default', 'theme_epure');
        }
        return format_string($tagline, true, ['context' => \context_system::instance()]);
    }

    /**
     * Supporting text shown under the headline, or an empty string.
     *
     * @return string
     */
    public function epure_login_text(): string {
        $text = trim((string) get_config('theme_epure', 'logintext'));
        return $text === '' ? '' : format_string($text, true, ['context' => \context_system::instance()]);
    }

    /**
     * URL of the login background image, or an empty string.
     *
     * @return string
     */
    public function epure_login_image_url(): string {
        $url = $this->theme_file_url('loginimage');
        return $url ? $url->out(false) : '';
    }

    /**
     * Logo shown on the brand-coloured login visual, or an empty string.
     *
     * The logo for coloured backgrounds is preferred; otherwise the main logo is shown on a white backing.
     *
     * @return string
     */
    public function epure_login_logo_url(): string {
        $url = $this->theme_file_url('logoonbrand') ?? \theme_epure\logos::main_url();
        return $url ? $url->out(false) : '';
    }

    /**
     * Whether the login logo needs a white backing, because it is not made for coloured backgrounds.
     *
     * @return bool
     */
    public function epure_login_logo_backing(): bool {
        return $this->theme_file_url('logoonbrand') === null;
    }

    /**
     * URL of a file uploaded in a theme setting, where the file area has the setting name.
     *
     * @param string $setting Setting name.
     * @return \moodle_url|null Null when no file is set.
     */
    protected function theme_file_url(string $setting): ?\moodle_url {
        return \theme_epure\logos::url($setting);
    }
}
