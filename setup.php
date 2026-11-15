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

/**
 * Getting started with Épure, step by step: the name and the logo of the site, its colours, its login page from a
 * model by sector, its footer, then its accessibility statement. Each step writes the settings of Moodle and of the
 * theme ({@see \theme_epure\setup}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use theme_epure\form\setup_form;
use theme_epure\setup;

admin_externalpage_setup('theme_epure_setup');

// Dismissed from the home of the administration: not offered any more.
if (optional_param('dismiss', 0, PARAM_BOOL) && confirm_sesskey()) {
    setup::finish();
    redirect(new moodle_url('/admin/search.php'));
}

$total = count(setup::STEPS);
$step = min(max(optional_param('step', 1, PARAM_INT), 1), $total);
$form = new setup_form(setup::url($step), ['step' => $step]);

if ($data = $form->get_data()) {
    setup::save(setup::STEPS[$step - 1], $data);
    if (!empty($data->back)) {
        redirect(setup::url($step - 1));
    }
    if ($step < $total) {
        redirect(setup::url($step + 1));
    }
    setup::finish();
    redirect(
        new moodle_url('/admin/search.php'),
        get_string('setup_done', 'theme_epure'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// The steps, each a link to it, the current one marked.
$items = '';
foreach (setup::STEPS as $i => $key) {
    $title = get_string('setup_' . $key, 'theme_epure');
    $items .= html_writer::tag('li', $i + 1 === $step
        ? html_writer::span($title, '', ['aria-current' => 'step'])
        : html_writer::link(setup::url($i + 1), $title));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('setup', 'theme_epure'));
echo html_writer::tag('ol', $items, ['class' => 'epure-a11ywizard-steps']);
echo $OUTPUT->heading(get_string('a11ywizard_step', 'theme_epure', (object) [
    'step' => $step,
    'total' => $total,
    'title' => get_string('setup_' . setup::STEPS[$step - 1], 'theme_epure'),
]), 3);
$form->display();
echo $OUTPUT->footer();
