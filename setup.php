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
 * Getting started with Épure, step by step: the tabs of the settings of the theme that matter most on a new site, as
 * they are in the settings, then the accessibility statement ({@see \theme_epure\setup}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use theme_epure\setup;

admin_externalpage_setup('theme_epure_setup');

// Dismissed from the home of the administration: not offered any more.
if (optional_param('dismiss', 0, PARAM_BOOL) && confirm_sesskey()) {
    setup::finish();
    redirect(new moodle_url('/admin/search.php'));
}

$keys = array_keys(setup::STEPS);
$total = count($keys);
$step = min(max(optional_param('step', 1, PARAM_INT), 1), $total);
$key = $keys[$step - 1];
$page = setup::page($key);
$settings = ($key === 'identity' ? setup::site_settings() : []);

if (($data = data_submitted()) && confirm_sesskey()) {
    if (setup::save($key, (array) $data)) {
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
    \core\notification::error(get_string('errorwithsettings', 'admin'));
}

// The steps, each a link to it, the current one marked.
$title = fn(string $name) => get_string('setup_' . $name, 'theme_epure');
$items = '';
foreach ($keys as $i => $name) {
    $items .= html_writer::tag('li', $i + 1 === $step
        ? html_writer::span($title($name), '', ['aria-current' => 'step'])
        : html_writer::link(setup::url($i + 1), $title($name)));
}

// The step: its introduction, then the tab of the settings as it is in the settings.
$content = html_writer::tag('p', get_string('setup_' . $key . '_intro', 'theme_epure'), ['class' => 'epure-setup-intro']);
if ($settings) {
    $content .= setup::settings_html($settings);
}
if ($key === 'login') {
    // A model by sector fills the tagline and the text left empty, and decides whether the catalogue shows.
    $models = ['' => get_string('setup_model_none', 'theme_epure')];
    foreach (setup::MODELS as $model) {
        $models[$model] = get_string('setup_model_' . $model, 'theme_epure');
    }
    $content .= html_writer::div(
        html_writer::div(html_writer::label(get_string('setup_model', 'theme_epure'), 'id_model'), 'form-label col-md-3') .
        html_writer::div(
            html_writer::select($models, 'model', '', false, ['id' => 'id_model', 'class' => 'form-select custom-select']) .
            html_writer::div(get_string('setup_model_desc', 'theme_epure'), 'form-description mt-2'),
            'form-setting col-md-9'
        ),
        'form-item row mb-3'
    );
}
if ($page) {
    $content .= $page->output_html();
}
if ($key === 'accessibility') {
    $status = (string) get_config('theme_epure', 'a11ystatus');
    $content .= html_writer::div(
        get_string(
            $status === '' ? 'setup_accessibility_none' : 'setup_accessibility_published',
            'theme_epure',
            $status === '' ? null : get_string('a11ystatus_' . $status, 'theme_epure')
        ),
        'alert alert-info'
    );
    $content .= html_writer::tag('p', html_writer::link(
        theme_epure\accessibility_statement::edit_url(1),
        get_string('setup_accessibility_link', 'theme_epure'),
        ['class' => 'btn btn-secondary']
    ));
}

$buttons = '';
if ($step > 1) {
    $buttons .= html_writer::tag(
        'button',
        get_string('a11ywizard_back', 'theme_epure'),
        ['type' => 'submit', 'name' => 'back', 'value' => 1, 'class' => 'btn btn-secondary me-2 mr-2']
    );
}
$next = $step < $total ? get_string('a11ywizard_next', 'theme_epure') : get_string('setup_finish', 'theme_epure');
$buttons .= html_writer::tag(
    'button',
    $next,
    ['type' => 'submit', 'name' => 'next', 'value' => 1, 'class' => 'btn btn-primary']
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('setup', 'theme_epure'));
echo html_writer::tag('ol', $items, ['class' => 'epure-a11ywizard-steps']);
echo $OUTPUT->heading(get_string('a11ywizard_step', 'theme_epure', (object) [
    'step' => $step,
    'total' => $total,
    'title' => $title($key),
]), 3);
// The same form as the page of the settings (its fields, its scripts), posted here.
echo html_writer::start_tag('form', ['action' => setup::url($step)->out(false), 'method' => 'post', 'id' => 'adminsettings']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::div($content, 'settingsform clearfix');
echo html_writer::div($buttons, 'epure-setup-buttons mt-3');
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
