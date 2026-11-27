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

namespace theme_epure\admin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * The palette under the colours of the theme settings, in a live preview that follows the fields as they change, before
 * they are saved: built when the page is shown, not when the tree of the administration is read.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_palette_live extends \admin_setting_heading {
    /**
     * Heading, then the live preview.
     *
     * @param mixed $data Unused.
     * @param string $query Admin search query.
     * @return string HTML.
     */
    public function output_html($data, $query = '') {
        $this->description = palette_preview::live(
            'epure-palette-live',
            get_config('theme_epure', 'brandcolor') ?: null,
            get_config('theme_epure', 'accentcolor') ?: null,
            (string) (get_config('theme_epure', 'headerstyle') ?: 'light'),
            [
                'brand' => '#id_s_theme_epure_brandcolor',
                'accent' => '#id_s_theme_epure_accentcolor',
                'header' => '#id_s_theme_epure_headerstyle',
            ]
        );
        return parent::output_html($data, $query);
    }
}
