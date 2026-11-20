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
 * Guide « Accessibility of the course », for its teachers: what each part of the course holds that some learners
 * cannot use (read without a person), what only a person can confirm (confirmed for all the teachers of the course),
 * and a report to print.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use theme_epure\course_accessibility;

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_login($course, false);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);
if ((int) $course->id === (int) SITEID || !course_accessibility::enabled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

// The report as a file (CSV, Excel, ODS, PDF, HTML).
$dataformat = optional_param('download', '', PARAM_ALPHA);
if ($dataformat !== '') {
    $PAGE->set_url(course_accessibility::url((int) $course->id));
    course_accessibility::download($course, $dataformat, $PAGE->get_renderer('core'));
    die();
}

// The confirmations of the form: each box checked, and those shown checked, that a teacher may uncheck.
if (data_submitted() && confirm_sesskey()) {
    course_accessibility::save(
        $course,
        (int) $USER->id,
        optional_param_array('check', [], PARAM_RAW_TRIMMED),
        optional_param_array('shown', [], PARAM_RAW_TRIMMED)
    );
    // The text alternatives written in the guide, into their images.
    $described = \theme_epure\content_alt::apply(
        $course,
        course_accessibility::analyse($course),
        optional_param_array('alt', [], PARAM_TEXT),
        optional_param_array('decorative', [], PARAM_BOOL)
    );
    // The titles of the frames, where the links lead, the headers of the tables, the levels of the titles, the colours
    // taken away, into their texts.
    $fixed = \theme_epure\content_fix::apply($course, course_accessibility::analyse($course), [
        'frames' => optional_param_array('frametitle', [], PARAM_TEXT),
        'imagelinks' => optional_param_array('linkalt', [], PARAM_TEXT),
        'tables' => optional_param_array('tablehead', [], PARAM_BOOL),
        'links' => optional_param_array('linktext', [], PARAM_TEXT),
        'headings' => optional_param_array('headings', [], PARAM_BOOL),
        'contrast' => optional_param_array('uncolour', [], PARAM_BOOL),
        'emptyheadings' => optional_param_array('emptyheadings', [], PARAM_BOOL),
        'moving' => optional_param_array('unmove', [], PARAM_BOOL),
        'tabindex' => optional_param_array('taborder', [], PARAM_BOOL),
    ]);
    // The names changed in the guide, into their activities.
    $renamed = course_accessibility::rename($course, optional_param_array('rename', [], PARAM_TEXT));
    // The instructions written in the guide, into the activities that had none.
    $written = course_accessibility::write_instructions($course, optional_param_array('instructions', [], PARAM_TEXT));
    // The changes saved, or only the points confirmed.
    $changed = $described || array_filter($fixed) || $renamed || $written;
    redirect(
        course_accessibility::url((int) $course->id),
        get_string($changed ? 'courseaccessibility_saved' : 'courseaccessibility_savedconfirmations', 'theme_epure')
            . ($described ? ' ' . get_string('courseaccessibility_alt_saved', 'theme_epure', $described) : '')
            . implode('', array_map(
                fn($kind) => $fixed[$kind]
                    ? ' ' . get_string('courseaccessibility_fix_' . $kind . '_saved', 'theme_epure', $fixed[$kind]) : '',
                \theme_epure\content_fix::KINDS
            ))
            . ($renamed ? ' ' . get_string('courseaccessibility_renamed', 'theme_epure', $renamed) : '')
            . ($written ? ' ' . get_string('courseaccessibility_written', 'theme_epure', $written) : ''),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_url(course_accessibility::url((int) $course->id));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('courseaccessibility', 'theme_epure') . moodle_page::TITLE_SEPARATOR
    . format_string($course->fullname, true, ['context' => $context]));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->set_secondary_active_tab('epureaccessibility');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('courseaccessibility', 'theme_epure'));
echo $OUTPUT->render_from_template('theme_epure/course_accessibility', course_accessibility::export($course, $OUTPUT));
echo $OUTPUT->footer();
