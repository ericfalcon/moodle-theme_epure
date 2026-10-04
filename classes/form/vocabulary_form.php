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

namespace theme_epure\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use theme_epure\vocabulary\manager;
use theme_epure\vocabulary\terms;

/**
 * Choice of the words used for the objects of the platform, in each language.
 *
 * Custom data: concepts (objects to choose, by default those of the platform), values (setting
 * name => value, by default the settings of the theme), companyid (for the words of IOMAD companies,
 * 0 for all companies), platformvalues (settings of all companies, inherited by a company) and
 * resetlabel (label of the button that restores the default words).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vocabulary_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $translations = get_string_manager()->get_list_of_translations(true);
        $presets = terms::presets();
        $concepts = $this->_customdata['concepts'] ?? terms::concepts();
        $values = $this->_customdata['values'] ?? null;
        // A company (but not the platform, company 0) inherits the words of the platform by default.
        $inherit = !empty($this->_customdata['companyid']);
        $value = fn(string $name) => (string) ($values === null ? get_config('theme_epure', $name) : ($values[$name] ?? ''));
        $platformvalues = $this->_customdata['platformvalues'] ?? null;

        if (isset($this->_customdata['companyid'])) {
            $mform->addElement('hidden', 'companyid', $this->_customdata['companyid']);
            $mform->setType('companyid', PARAM_INT);
        }

        foreach (manager::languages() as $lang) {
            $mform->addElement('header', 'lang_' . $lang, $translations[$lang]);
            $mform->setExpanded('lang_' . $lang);

            foreach ($concepts as $concept) {
                $name = terms::setting($lang, $concept);
                $options = [];
                foreach ($presets[$lang][$concept] as $key => $term) {
                    $options[$key] = $term['singular'] === $term['plural']
                        ? $term['singular']
                        : $term['singular'] . ' / ' . $term['plural'];
                }
                $options[terms::CUSTOM] = get_string('vocabcustom', 'theme_epure');
                if ($inherit) {
                    $platform = terms::chosen($lang, $concept, $platformvalues)
                        ?? $presets[$lang][$concept][terms::DEFAULTS[$lang][$concept]];
                    $options = ['' => get_string('vocabplatform', 'theme_epure', $platform['plural'])] + $options;
                }
                $mform->addElement('select', $name, get_string('vocab_' . $concept, 'theme_epure'), $options);
                $mform->setDefault($name, $value($name) ?: ($inherit ? '' : terms::DEFAULTS[$lang][$concept]));

                foreach (['singular', 'plural'] as $form) {
                    $field = terms::setting($lang, $concept, $form);
                    $mform->addElement('text', $field, get_string('vocab' . $form, 'theme_epure'), ['size' => 24]);
                    $mform->setType($field, PARAM_TEXT);
                    $mform->setDefault($field, $value($field));
                    $mform->hideIf($field, $name, 'neq', terms::CUSTOM);
                }
                if ($lang === 'fr') {
                    $field = terms::setting($lang, $concept, 'gender');
                    $mform->addElement('select', $field, get_string('vocabgender', 'theme_epure'), [
                        'm' => get_string('vocabmasculine', 'theme_epure'),
                        'f' => get_string('vocabfeminine', 'theme_epure'),
                    ]);
                    $mform->setDefault($field, $value($field) ?: 'm');
                    $mform->hideIf($field, $name, 'neq', terms::CUSTOM);
                }
            }
        }

        $buttons = [
            $mform->createElement('submit', 'submitbutton', get_string('vocabapply', 'theme_epure')),
            $mform->createElement(
                'submit',
                'resetbutton',
                $this->_customdata['resetlabel'] ?? get_string('vocabreset', 'theme_epure'),
                [],
                false
            ),
        ];
        $mform->addGroup($buttons, 'buttonar', '', ' ', false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Validation: a custom word needs at least its singular.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['resetbutton'])) {
            return $errors;
        }
        foreach (manager::languages() as $lang) {
            foreach ($this->_customdata['concepts'] ?? terms::concepts() as $concept) {
                $singular = terms::setting($lang, $concept, 'singular');
                if (($data[terms::setting($lang, $concept)] ?? '') === terms::CUSTOM && trim($data[$singular] ?? '') === '') {
                    $errors[$singular] = get_string('required');
                }
            }
        }
        return $errors;
    }

    /**
     * The settings submitted, as setting name => value; the default words when the reset button was used.
     *
     * @param \stdClass $data Submitted data.
     * @param string[] $concepts Objects of the form.
     * @param bool $inherit Whether the default is the word of the platform (for a company).
     * @return array<string, string>
     */
    public static function values_from(\stdClass $data, array $concepts, bool $inherit = false): array {
        $reset = !empty($data->resetbutton);
        $values = [];
        foreach (manager::languages() as $lang) {
            foreach ($concepts as $concept) {
                $name = terms::setting($lang, $concept);
                $values[$name] = $reset ? ($inherit ? '' : terms::DEFAULTS[$lang][$concept]) : (string) $data->$name;
                foreach (['singular', 'plural', 'gender'] as $suffix) {
                    $field = terms::setting($lang, $concept, $suffix);
                    if (!$reset && isset($data->$field)) {
                        $values[$field] = trim($data->$field);
                    }
                }
            }
        }
        return $values;
    }
}
