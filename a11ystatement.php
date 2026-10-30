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

/**
 * Accessibility statement, filled step by step: who publishes it, the audit, the compliance status, the contents not
 * accessible and the contact. The settings are those of the Accessibility tab of the theme.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use theme_epure\accessibility_statement;
use theme_epure\form\a11y_statement_form;

admin_externalpage_setup('theme_epure_a11ystatement');

$keys = array_keys(accessibility_statement::STEPS);
$total = count($keys);
$step = min(max(optional_param('step', 1, PARAM_INT), 1), $total);
$form = new a11y_statement_form(accessibility_statement::edit_url($step), ['step' => $step]);

if ($data = $form->get_data()) {
    foreach (a11y_statement_form::values_from($data) as $name => $value) {
        set_config($name, $value, 'theme_epure');
    }
    if (!empty($data->back)) {
        redirect(accessibility_statement::edit_url($step - 1));
    }
    if ($step < $total) {
        redirect(accessibility_statement::edit_url($step + 1));
    }
    redirect(
        accessibility_statement::url(),
        get_string(accessibility_statement::enabled() ? 'a11ywizard_saved' : 'a11ywizard_unpublished', 'theme_epure'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// The steps, each a link to it, the current one marked.
$items = '';
foreach ($keys as $i => $key) {
    $title = get_string('a11ywizard_' . $key, 'theme_epure');
    $items .= html_writer::tag('li', $i + 1 === $step
        ? html_writer::span($title, '', ['aria-current' => 'step'])
        : html_writer::link(accessibility_statement::edit_url($i + 1), $title));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('a11ywizard', 'theme_epure'));
echo html_writer::tag('ol', $items, ['class' => 'epure-a11ywizard-steps']);
echo $OUTPUT->heading(get_string('a11ywizard_step', 'theme_epure', (object) [
    'step' => $step,
    'total' => $total,
    'title' => get_string('a11ywizard_' . $keys[$step - 1], 'theme_epure'),
]), 3);
$form->display();
echo html_writer::div(
    html_writer::link(accessibility_statement::url(), get_string('a11ywizard_view', 'theme_epure')),
    'epure-a11ywizard-view'
);
echo $OUTPUT->footer();
