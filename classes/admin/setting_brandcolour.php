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

use theme_epure\palette;

/**
 * Brand colour setting: a colour picker that only accepts hex colours,
 * and proposes the main colours of the logo.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_brandcolour extends \admin_setting_configcolourpicker {
    /**
     * Only hex colours are accepted, because the accessible palette is computed from them.
     *
     * @param string $data Submitted value.
     * @return string|false The normalised colour, or false when it is not valid.
     */
    protected function validate($data) {
        if (trim((string) $data) === '') {
            return $this->get_defaultsetting();
        }
        return palette::normalise($data) ?? false;
    }

    /**
     * Saves the setting, with a clear message when the colour is not a hex code.
     *
     * @param string $data Submitted value.
     * @return string Empty string on success, an error message otherwise.
     */
    public function write_setting($data) {
        $colour = $this->validate($data);
        if ($colour === false) {
            return get_string('brandcolor_invalid', 'theme_epure');
        }
        return $this->config_write($this->name, $colour) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * Colour picker followed by the colours of the logo.
     *
     * @param string $data Current value.
     * @param string $query Admin search query.
     * @return string HTML
     */
    public function output_html($data, $query = '') {
        global $PAGE, $OUTPUT;

        $icon = new \pix_icon('i/loading', get_string('loading', 'admin'), 'moodle', ['class' => 'loadingicon']);
        $element = $OUTPUT->render_from_template('core_admin/setting_configcolourpicker', (object) [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => $data,
            'icon' => $icon->export_for_template($OUTPUT),
            'haspreviewconfig' => !empty($this->previewconfig),
            'forceltr' => $this->get_force_ltr(),
            'readonly' => $this->is_readonly(),
        ]);
        $PAGE->requires->js_init_call('M.util.init_colour_picker', [$this->get_id(), $this->previewconfig]);

        $logo = \theme_epure\logos::main_url();
        $regionid = $this->get_id() . '_logocolours';
        $element .= $OUTPUT->render_from_template('theme_epure/logo_colours', [
            'id' => $regionid,
            'inputid' => $this->get_id(),
            'logourl' => $logo ? $logo->out(false) : null,
        ]);
        if ($logo) {
            $PAGE->requires->js_call_amd('theme_epure/logo_colours', 'init', ['#' . $regionid]);
        }

        return format_admin_setting(
            $this,
            $this->visiblename,
            $element,
            $this->description,
            true,
            '',
            $this->get_defaultsetting(),
            $query
        );
    }
}
