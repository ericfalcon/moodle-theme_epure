<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use theme_epure\accessibility_statement;
use theme_epure\palette;
use theme_epure\setup;

/**
 * One step of the getting started with Épure ({@see setup}): its fields, filled with the current settings.
 *
 * Custom data: step (from 1).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $step = (int) $this->_customdata['step'];
        $key = setup::STEPS[$step - 1];

        $mform->addElement('hidden', 'step', $step);
        $mform->setType('step', PARAM_INT);
        $mform->addElement('html', \html_writer::tag(
            'p',
            get_string('setup_' . $key . '_intro', 'theme_epure'),
            ['class' => 'epure-a11ywizard-intro']
        ));

        switch ($key) {
            case 'identity':
                $mform->addElement('text', 'fullname', get_string('fullsitename'), ['size' => 50]);
                $mform->setType('fullname', PARAM_TEXT);
                $mform->addRule('fullname', null, 'required', null, 'client');
                $mform->addElement('text', 'shortname', get_string('shortsitename'), ['size' => 20]);
                $mform->setType('shortname', PARAM_TEXT);
                $mform->addRule('shortname', null, 'required', null, 'client');
                $mform->addElement(
                    'filemanager',
                    'logo',
                    get_string('logo', 'admin'),
                    null,
                    ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.jpg', '.jpeg', '.png', '.svg', '.webp']]
                );
                $mform->addElement('static', 'logo_help', '', get_string('setup_logo_desc', 'theme_epure'));
                // The logo of the theme comes before the logo of the site: it is the one changed here, and said so.
                if (setup::logo_target()['theme']) {
                    $mform->addElement('static', 'logo_theme', '', \html_writer::div(
                        get_string('setup_logo_theme', 'theme_epure'),
                        'alert alert-info mb-0'
                    ));
                }
                $mform->setDefault('logo', setup::logo_draft());
                break;
            case 'colours':
                $mform->addElement('text', 'brandcolor', get_string('brandcolor', 'theme_epure'), ['size' => 10]);
                $mform->setType('brandcolor', PARAM_TEXT);
                $mform->addRule('brandcolor', null, 'required', null, 'client');
                $mform->addElement('static', 'brandcolor_help', '', get_string('setup_brandcolor_desc', 'theme_epure'));
                // The colours of the logo of step 1, as under the brand colour of the tab Appearance.
                $mform->addElement('static', 'brandcolor_logo', '', $this->logo_colours('brandcolor', false));
                $mform->addElement('text', 'accentcolor', get_string('accentcolor', 'theme_epure'), ['size' => 10]);
                $mform->setType('accentcolor', PARAM_TEXT);
                $mform->addElement('static', 'accentcolor_help', '', get_string('accentcolor_desc', 'theme_epure'));
                $mform->addElement('static', 'accentcolor_logo', '', $this->logo_colours('accentcolor', true));
                $styles = [];
                foreach (setup::HEADER_STYLES as $style) {
                    $styles[$style] = get_string('headerstyle' . $style, 'theme_epure');
                }
                $mform->addElement('select', 'headerstyle', get_string('headerstyle', 'theme_epure'), $styles);
                break;
            case 'login':
                $models = ['' => get_string('setup_model_none', 'theme_epure')];
                foreach (setup::MODELS as $model) {
                    $models[$model] = get_string('setup_model_' . $model, 'theme_epure');
                }
                $mform->addElement('select', 'model', get_string('setup_model', 'theme_epure'), $models);
                $mform->addElement('static', 'model_help', '', get_string('setup_model_desc', 'theme_epure'));
                $mform->addElement('text', 'logintagline', get_string('logintagline', 'theme_epure'), ['size' => 60]);
                $mform->setType('logintagline', PARAM_TEXT);
                $mform->addElement('text', 'logintext', get_string('logintext', 'theme_epure'), ['size' => 80]);
                $mform->setType('logintext', PARAM_TEXT);
                $mform->addElement('advcheckbox', 'catalogue', get_string('catalogue', 'theme_epure'));
                $mform->addElement('static', 'catalogue_help', '', get_string('setup_catalogue_desc', 'theme_epure'));
                break;
            case 'footer':
                $mform->addElement('textarea', 'footertext', get_string('footertext', 'theme_epure'), ['rows' => 4, 'cols' => 60]);
                $mform->setType('footertext', PARAM_RAW);
                foreach (['footerlegalurl', 'footerprivacyurl', 'footercontacturl'] as $name) {
                    $mform->addElement('text', $name, get_string($name, 'theme_epure'), ['size' => 60]);
                    $mform->setType($name, PARAM_URL);
                }
                break;
            case 'accessibility':
                $status = (string) get_config('theme_epure', 'a11ystatus');
                $mform->addElement('html', \html_writer::div(
                    get_string(
                        $status === '' ? 'setup_accessibility_none' : 'setup_accessibility_published',
                        'theme_epure',
                        $status === '' ? null : get_string('a11ystatus_' . $status, 'theme_epure')
                    ),
                    'alert alert-info'
                ));
                $mform->addElement('html', \html_writer::tag('p', \html_writer::link(
                    accessibility_statement::edit_url(1),
                    get_string('setup_accessibility_link', 'theme_epure'),
                    ['class' => 'btn btn-secondary']
                )));
                break;
        }
        $this->set_data(setup::values($key));

        $buttons = [];
        if ($step > 1) {
            $buttons[] = $mform->createElement('submit', 'back', get_string('a11ywizard_back', 'theme_epure'));
        }
        $buttons[] = $mform->createElement('submit', 'next', $step < count(setup::STEPS)
            ? get_string('a11ywizard_next', 'theme_epure') : get_string('setup_finish', 'theme_epure'));
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * The colours of the logo of the site proposed under a colour field (theme_epure/logo_colours): a click on a colour,
     * or on a point of the logo, fills the field.
     *
     * @param string $field Field (brandcolor, accentcolor).
     * @param bool $hidelogo Whether the logo is already shown above, under the brand colour.
     * @return string HTML.
     */
    protected function logo_colours(string $field, bool $hidelogo): string {
        global $OUTPUT, $PAGE;
        $logo = \theme_epure\logos::main_url();
        $id = 'id_' . $field . '_logocolours';
        $PAGE->requires->js_call_amd('theme_epure/logo_colours', 'init', ['#' . $id]);
        return $OUTPUT->render_from_template('theme_epure/logo_colours', [
            'id' => $id,
            'inputid' => 'id_' . $field,
            'logourl' => $logo ? $logo->out(false) : null,
            'hidelogo' => $hidelogo,
            'help' => get_string('setup_logocolours_' . $field, 'theme_epure'),
            'none' => $hidelogo ? '' : get_string('setup_logocolours_none', 'theme_epure'),
        ]);
    }

    /**
     * Validation: the colours as #RRGGBB (or #RGB), the accent colour optional.
     *
     * @param array $data Submitted data.
     * @param array $files Files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (array_key_exists('brandcolor', $data) && palette::normalise((string) $data['brandcolor']) === null) {
            $errors['brandcolor'] = get_string('brandcolor_invalid', 'theme_epure');
        }
        if (!empty($data['accentcolor']) && palette::normalise((string) $data['accentcolor']) === null) {
            $errors['accentcolor'] = get_string('brandcolor_invalid', 'theme_epure');
        }
        return $errors;
    }
}
