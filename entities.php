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
 * Entities of the site: the categories with an appearance of Épure, those whose theme is Épure and that have none yet,
 * and those that keep another theme, to move to an entity of Épure (theme_epure\entity).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use theme_epure\entity;

admin_externalpage_setup('theme_epure_entities');
$title = get_string('entities', 'theme_epure');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
if (!entity::available()) {
    echo $OUTPUT->notification(get_string('entityunavailable', 'theme_epure'), \core\output\notification::NOTIFY_INFO, false);
    echo $OUTPUT->footer();
    exit;
}
echo html_writer::tag('p', get_string('entitiesintro', 'theme_epure'));
foreach (entity::blocked_settings() as $setting) {
    echo $OUTPUT->notification(get_string('entityblocked_' . $setting, 'theme_epure', (new moodle_url(
        '/admin/search.php',
        ['query' => $setting]
    ))->out(false)), \core\output\notification::NOTIFY_WARNING, false);
}

$categoryname = function (int $id): string {
    $category = core_course_category::get($id, IGNORE_MISSING, true);
    return $category ? $category->get_nested_name(false) : (string) $id;
};
$cohortnames = function (array $ids) use ($DB): string {
    if (!$ids) {
        return get_string('entitynocohort', 'theme_epure');
    }
    $names = $DB->get_records_list('cohort', 'id', $ids, 'name', 'id, name');
    return implode(', ', array_map(fn($cohort) => format_string($cohort->name), $names));
};
$swatch = fn(string $colour) => $colour === '' ? get_string('entitysitecolour', 'theme_epure')
    : html_writer::span('', 'd-inline-block border rounded me-2 mr-2 align-middle', [
        'style' => 'width:1.25rem;height:1.25rem;background:' . s($colour),
        'aria-hidden' => 'true',
    ]) . s($colour);

// The entities: their category, their colour, their cohorts (with the cohorts whose theme is Épure in their category).
echo $OUTPUT->heading(get_string('entitieslist', 'theme_epure'), 3);
$map = entity::cohort_map();
$entities = entity::all();
if (!$entities) {
    echo html_writer::tag('p', get_string('entitiesnone', 'theme_epure'));
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->caption = get_string('entitieslist', 'theme_epure');
    $table->captionhide = true;
    $table->head = [get_string('category'), get_string('brandcolor', 'theme_epure'), get_string('entitycohorts', 'theme_epure'),
        get_string('entitypriority', 'theme_epure'), get_string('actions')];
    foreach ($entities as $id => $settings) {
        $cohorts = array_keys(array_filter($map, fn($ids) => in_array($id, $ids, true)));
        $edit = html_writer::link(entity::url($id), get_string('entityedit', 'theme_epure', $categoryname($id)));
        $table->data[] = [s($categoryname($id)), $swatch((string) $settings['brandcolor']), s($cohortnames($cohorts)),
            (int) $settings['priority'], $edit];
    }
    echo html_writer::table($table);
}

// The categories whose theme is Épure, without an appearance yet.
$toset = entity::epure_without_appearance();
if ($toset) {
    echo $OUTPUT->heading(get_string('entitiestoset', 'theme_epure'), 3);
    echo html_writer::tag('p', get_string('entitiestoset_desc', 'theme_epure'));
    $items = array_map(fn($category) => html_writer::link(
        entity::url((int) $category->id),
        get_string('entitycreate', 'theme_epure', $categoryname((int) $category->id))
    ), $toset);
    echo html_writer::alist($items);
}

// The categories that keep another theme: to move to an entity of Épure, from the brand colour of their theme.
$others = entity::other_themes();
echo $OUTPUT->heading(get_string('entitiesmigrate', 'theme_epure'), 3);
if (!$others) {
    echo html_writer::tag('p', get_string('entitiesmigratenone', 'theme_epure'));
} else {
    echo html_writer::tag('p', get_string('entitiesmigrate_desc', 'theme_epure'));
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->caption = get_string('entitiesmigrate', 'theme_epure');
    $table->captionhide = true;
    $table->head = [get_string('category'), get_string('theme'), get_string('brandcolor', 'theme_epure'), get_string('actions')];
    foreach ($others as $category) {
        $name = $categoryname((int) $category->id);
        $action = entity::exists((int) $category->id)
            ? html_writer::link(entity::url((int) $category->id), get_string('entityedit', 'theme_epure', $name))
            : html_writer::link(
                new moodle_url(entity::url((int) $category->id), ['fromtheme' => 1]),
                get_string('entityfromtheme', 'theme_epure', $name)
            );
        $table->data[] = [s($name), s($category->theme), $swatch((string) ($category->brand ?? '')), $action];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
