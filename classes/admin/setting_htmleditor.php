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

/**
 * A setting written in the editor of Moodle, as admin_setting_confightmleditor, without the autosave of the editor: a
 * setting is saved with its page, and the getting started shows it on a page of its own, where the session of the
 * autosave has nothing to resume.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_htmleditor extends \admin_setting_confightmleditor {
    /**
     * The field, in the editor without autosave.
     *
     * @param string $data Value.
     * @param string $query Search of the administration.
     * @return string
     */
    public function output_html($data, $query = '') {
        $editor = editors_get_preferred_editor(FORMAT_HTML);
        $editor->set_text($data);
        $editor->use_editor($this->get_id(), ['noclean' => true, 'autosave' => false]);
        return \admin_setting_configtextarea::output_html($data, $query);
    }
}
