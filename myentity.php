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
 * My entity: for a user in the cohorts of several entities (theme_epure\entity), the one whose appearance and words
 * they see out of the courses, or the one chosen by the priorities of the entities.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_epure\entity;

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('guestsarenotallowed');
}
$url = new moodle_url('/theme/epure/myentity.php');
$PAGE->set_url($url);
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_pagelayout('standard');
$title = get_string('myentity', 'theme_epure');
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));
$PAGE->navbar->add(get_string('preferences'), new moodle_url('/user/preferences.php'));
$PAGE->navbar->add($title, $url);

$entities = entity::available() ? entity::of_user_all((int) $USER->id) : [];
$names = [];
foreach ($entities as $categoryid) {
    $category = core_course_category::get($categoryid, IGNORE_MISSING, true);
    if ($category) {
        $names[$categoryid] = $category->get_formatted_name();
    }
}

if (data_submitted() && confirm_sesskey() && count($names) > 1) {
    $choice = optional_param('entity', 0, PARAM_INT);
    if ($choice && isset($names[$choice])) {
        set_user_preference(entity::PREFERENCE, $choice);
    } else {
        unset_user_preference(entity::PREFERENCE);
    }
    redirect($url, get_string('myentitysaved', 'theme_epure'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
if (count($names) < 2) {
    echo html_writer::tag('p', get_string('myentitynone', 'theme_epure'));
    echo $OUTPUT->footer();
    exit;
}
echo html_writer::tag('p', get_string('myentity_desc', 'theme_epure'));
$chosen = (int) get_user_preferences(entity::PREFERENCE, 0);
$chosen = isset($names[$chosen]) ? $chosen : 0;

$radio = function (int $value, string $label) use ($chosen): string {
    $id = 'epure-myentity-' . $value;
    $attributes = ['type' => 'radio', 'name' => 'entity', 'value' => $value, 'id' => $id, 'class' => 'form-check-input'];
    if ($value === $chosen) {
        $attributes['checked'] = 'checked';
    }
    return html_writer::div(
        html_writer::empty_tag('input', $attributes)
            . html_writer::label($label, $id, false, ['class' => 'form-check-label']),
        'form-check mb-2'
    );
};
$options = $radio(0, get_string('myentityauto', 'theme_epure'));
foreach ($names as $categoryid => $name) {
    $options .= $radio((int) $categoryid, $name);
}
$fieldset = html_writer::tag('fieldset', html_writer::tag('legend', get_string('myentitychoose', 'theme_epure'), [
    'class' => 'h5',
]) . $options, ['class' => 'mb-3']);
echo html_writer::tag('form', $fieldset
    . html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()])
    . html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary']), [
    'method' => 'post',
    'action' => $url->out(false),
]);
echo $OUTPUT->footer();
