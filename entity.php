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
 * Appearance of an entity: a course category with its own colours, logos, pages and footer, for its courses and for the
 * members of its cohorts (theme_epure\entity).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_epure\entity;
use theme_epure\form\entity_form;

$categoryid = required_param('category', PARAM_INT);
$fromtheme = optional_param('fromtheme', 0, PARAM_BOOL);
$delete = optional_param('delete', 0, PARAM_BOOL);

$category = core_course_category::get($categoryid);
$context = context_coursecat::instance($category->id);
require_login();
require_capability('moodle/category:manage', $context);
if (!entity::available()) {
    throw new moodle_exception('entityunavailable', 'theme_epure');
}

$url = entity::url($category->id);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_primary_active_tab('home');
$name = $category->get_formatted_name();
$title = get_string('entityappearance', 'theme_epure');
$PAGE->set_title($title . moodle_page::TITLE_SEPARATOR . $name);
$PAGE->set_heading($name);
navigation_node::override_active_url(new moodle_url('/course/index.php', ['categoryid' => $category->id]));
$PAGE->navbar->add($title, $url);
$back = new moodle_url('/course/index.php', ['categoryid' => $category->id]);

// Removing the appearance: the category is no longer an entity.
if ($delete && entity::exists($category->id)) {
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        entity::delete($category->id);
        redirect($back, get_string('entitydeleted', 'theme_epure'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('entitydeleteconfirm', 'theme_epure', $name),
        new moodle_url($url, ['delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $url
    );
    echo $OUTPUT->footer();
    exit;
}

$admin = has_capability('moodle/site:config', context_system::instance());
$form = new entity_form($url, ['category' => $category->id, 'cohorts' => entity::cohort_options($category->id),
    'admin' => $admin]);
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    $before = entity::settings($category->id);
    $values = entity_form::values($data, $before['vocabulary']);
    // The CSS of the entity is set by the administrators of the site only: the others keep it as it is.
    if (!$admin) {
        $values['customcss'] = $before['customcss'];
    }
    entity::save($category->id, $values);
    foreach (entity::FILE_AREAS as $area) {
        file_save_draft_area_files(
            $data->$area,
            context_system::instance()->id,
            'theme_epure',
            $area,
            $category->id,
            entity_form::LOGO_OPTIONS
        );
    }
    redirect($url, get_string('entitysaved', 'theme_epure'), null, \core\output\notification::NOTIFY_SUCCESS);
}
$settings = entity::settings($category->id);
// A category under another theme: its brand colour starts the appearance.
if ($fromtheme && !entity::exists($category->id) && $category->theme && ($brand = entity::theme_brand((string) $category->theme))) {
    $settings['brandcolor'] = $brand;
}
$form->set_data(entity_form::data($category->id, $settings));

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo html_writer::tag('p', get_string('entityintro', 'theme_epure'));

// What Moodle does with the theme of the category, and what keeps the category and cohort themes from applying.
$theme = (string) $category->theme;
if ($theme === '') {
    $state = get_string('entitythemesite', 'theme_epure');
} else if (\theme_epure\theme_use::is_epure($theme)) {
    $state = get_string('entitythemeepure', 'theme_epure');
} else {
    $state = get_string('entitythemeother', 'theme_epure', s($theme));
}
echo $OUTPUT->notification($state, \core\output\notification::NOTIFY_INFO, false);
foreach (entity::blocked_settings() as $setting) {
    echo $OUTPUT->notification(get_string('entityblocked_' . $setting, 'theme_epure', (new moodle_url(
        '/admin/search.php',
        ['query' => $setting]
    ))->out(false)), \core\output\notification::NOTIFY_WARNING, false);
}

if (!\theme_epure\hook_callbacks::string_manager_available()) {
    echo $OUTPUT->notification(get_string('entityvocabunavailable', 'theme_epure'), \core\output\notification::NOTIFY_WARNING);
}
$form->display();
if (entity::exists($category->id)) {
    echo $OUTPUT->single_button(
        new moodle_url($url, ['delete' => 1]),
        get_string('entitydelete', 'theme_epure'),
        'get',
        ['type' => single_button::BUTTON_DANGER]
    );
}
if (has_capability('moodle/site:config', context_system::instance())) {
    echo html_writer::tag('p', html_writer::link(new moodle_url('/theme/epure/entities.php'), get_string(
        'entities',
        'theme_epure'
    )), ['class' => 'mt-3']);
}
echo $OUTPUT->footer();
