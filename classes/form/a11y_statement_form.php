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

use theme_epure\accessibility_statement;

/**
 * One step of the accessibility statement, filled step by step: the settings of the step (the same as in the
 * Accessibility tab of the theme), with what the RGAA asks for above them.
 *
 * Custom data: step (from 1).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class a11y_statement_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $step = (int) $this->_customdata['step'];
        $keys = array_keys(accessibility_statement::STEPS);
        $key = $keys[$step - 1];
        $config = get_config('theme_epure');

        $mform->addElement('hidden', 'step', $step);
        $mform->setType('step', PARAM_INT);
        $mform->addElement('html', \html_writer::div(
            get_string('a11ywizard_' . $key . '_intro', 'theme_epure'),
            'epure-a11ywizard-intro'
        ));

        if ($key === 'status') {
            // The rate of the audit, as a reminder: the status comes from it, the theme does not compute it.
            $rate = trim((string) ($config->a11yrate ?? ''));
            $mform->addElement('html', \html_writer::div(
                $rate === '' ? get_string('a11ywizard_norate', 'theme_epure')
                    : get_string('a11ywizard_rate', 'theme_epure', s($rate)),
                'alert alert-info'
            ));
        }

        foreach (accessibility_statement::STEPS[$key] as $name) {
            if ($name === 'a11ystatus') {
                $statuses = ['' => get_string('a11ystatus_unpublished', 'theme_epure')];
                foreach (accessibility_statement::STATUSES as $status) {
                    $statuses[$status] = get_string('a11ystatus_' . $status, 'theme_epure');
                }
                $mform->addElement('select', $name, get_string($name, 'theme_epure'), $statuses);
            } else if (in_array(substr($name, 4), accessibility_statement::TEXTS, true)) {
                $mform->addElement('textarea', $name, get_string($name, 'theme_epure'), ['rows' => 6, 'cols' => 60]);
                $mform->setType($name, PARAM_RAW);
            } else {
                $mform->addElement('text', $name, get_string($name, 'theme_epure'), ['size' => 50]);
                $mform->setType($name, PARAM_TEXT);
            }
            $mform->addElement('static', $name . '_help', '', get_string($name . '_desc', 'theme_epure'));
            $mform->setDefault($name, (string) ($config->$name ?? ''));
        }

        $buttons = [];
        if ($step > 1) {
            $buttons[] = $mform->createElement('submit', 'back', get_string('a11ywizard_back', 'theme_epure'));
        }
        $buttons[] = $mform->createElement('submit', 'next', $step < count($keys)
            ? get_string('a11ywizard_next', 'theme_epure') : get_string('savechanges'));
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * The settings of the step, cleaned, as setting name => value.
     *
     * @param \stdClass $data Submitted data.
     * @return array
     */
    public static function values_from(\stdClass $data): array {
        $keys = array_keys(accessibility_statement::STEPS);
        $names = accessibility_statement::STEPS[$keys[(int) $data->step - 1]] ?? [];
        $values = [];
        foreach ($names as $name) {
            $value = trim((string) ($data->$name ?? ''));
            if ($name === 'a11ystatus' && !in_array($value, accessibility_statement::STATUSES, true)) {
                $value = '';
            }
            $values[$name] = $value;
        }
        return $values;
    }
}
