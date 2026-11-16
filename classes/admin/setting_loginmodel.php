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

use theme_epure\setup;

/**
 * The models of the login page by sector, in the tab of the login page, as in the getting started: choosing one fills
 * the tagline and the text, and the box of the catalogue, on the page (theme_epure/login_model). Not a setting: nothing
 * of its own is stored, the fields it fills are saved with the page.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_loginmodel extends \admin_setting {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct(
            'theme_epure/loginmodel',
            get_string('setup_model', 'theme_epure'),
            get_string('loginmodel_desc', 'theme_epure'),
            ''
        );
    }

    /**
     * Nothing is stored.
     *
     * @return string
     */
    public function get_setting() {
        return '';
    }

    /**
     * Nothing is stored.
     *
     * @param mixed $data Data.
     * @return string Empty: no error.
     */
    public function write_setting($data) {
        return '';
    }

    /**
     * The list of the models, each with its texts, and the script that fills the fields.
     *
     * @param mixed $data Value.
     * @param string $query Search.
     * @return string HTML.
     */
    public function output_html($data, $query = '') {
        global $PAGE;
        $options = [\html_writer::tag('option', get_string('setup_model_none', 'theme_epure'), ['value' => ''])];
        foreach (setup::MODELS as $model) {
            $texts = setup::model_texts($model);
            $options[] = \html_writer::tag('option', get_string('setup_model_' . $model, 'theme_epure'), [
                'value' => $model,
                'data-tagline' => $texts['logintagline'],
                'data-text' => $texts['logintext'],
                'data-catalogue' => (int) setup::CATALOGUE[$model],
            ]);
        }
        // Named « model », as in the getting started, which applies it when it saves (the catalogue is on another tab).
        $element = \html_writer::tag('select', implode('', $options), [
            'id' => $this->get_id(),
            'name' => 'model',
            'class' => 'form-select custom-select',
            'data-region' => 'epure-loginmodel',
        ]) . \html_writer::tag('p', '', ['class' => 'form-text mt-2 mb-0', 'role' => 'status',
            'data-region' => 'epure-loginmodel-status', 'data-done' => get_string('loginmodel_done', 'theme_epure')]);
        $PAGE->requires->js_call_amd('theme_epure/login_model', 'init', ['#' . $this->get_id()]);
        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', null, $query);
    }
}
