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

namespace theme_epure;

/**
 * Presentation of a course to the users who are not enrolled yet, on its enrolment page: what the
 * course is about, who teaches it, what it contains and its outline, before the enrolment options.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_presentation {
    /**
     * Whether a page is the enrolment page of a course, presented by Épure.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        return $page->pagetype === 'enrol-index' && (int) $page->course->id !== (int) SITEID && catalogue::enabled();
    }

    /**
     * Context of the template theme_epure/course_presentation.
     *
     * @param \stdClass $course Course.
     * @param \renderer_base $output Renderer.
     * @param string $header Header of the page, with the title and the breadcrumb.
     * @return array
     */
    public static function export(\stdClass $course, \renderer_base $output, string $header): array {
        global $CFG;
        require_once($CFG->dirroot . '/course/renderer.php');
        $element = new \core_course_list_element($course);
        $card = catalogue::card($element, $output);
        $context = \context_course::instance($course->id);

        $summary = '';
        if ($element->has_summary()) {
            $summary = (new \coursecat_helper())->get_course_formatted_summary($element, ['overflowdiv' => true]);
        }
        $contacts = [];
        foreach ($element->get_course_contacts() as $contact) {
            $contacts[$contact['user']->id]['name'] = $contact['username'];
            $contacts[$contact['user']->id]['roles'][] = $contact['rolename'];
            // The contacts of a course come without the fields of their picture.
            $user = \core_user::get_user($contact['user']->id);
            $contacts[$contact['user']->id]['picture'] = $user ? $output->user_picture(
                $user,
                ['size' => 48, 'link' => false, 'alttext' => false]
            ) : '';
        }
        $contacts = array_values(array_map(fn($contact) => [
            'name' => $contact['name'],
            'role' => implode(', ', array_unique($contact['roles'])),
            'picture' => $contact['picture'],
        ], $contacts));

        [$outline, $contents] = self::outline($course);
        $dates = [];
        if ($course->startdate) {
            $dates[] = ['label' => get_string('startdate'), 'date' => userdate($course->startdate, get_string('strftimedate'))];
        }
        if ($course->enddate) {
            $dates[] = ['label' => get_string('enddate'), 'date' => userdate($course->enddate, get_string('strftimedate'))];
        }
        $handler = \core_course\customfield\course_handler::create();
        return $card + [
            'header' => $header,
            'fullsummary' => $summary,
            'teachers' => $contacts,
            'hasteachers' => (bool) $contacts,
            'outline' => $outline,
            'hasoutline' => (bool) $outline,
            'contents' => $contents,
            'hascontents' => (bool) $contents,
            'dates' => $dates,
            'customfields' => $handler->display_custom_fields_data($element->get_custom_fields()),
            'shortname' => format_string($course->shortname, true, ['context' => $context]),
        ];
    }

    /**
     * Outline of a course: its sections with the number of their activities, and the activities by type.
     *
     * Hidden sections and activities are left out, as the visitor will not see them once enrolled.
     *
     * @param \stdClass $course Course.
     * @return array{0: array[], 1: array[]}
     */
    public static function outline(\stdClass $course): array {
        $modinfo = get_fast_modinfo($course, -1);
        $outline = [];
        $types = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            if (!$section->visible || (method_exists($section, 'is_delegated') && $section->is_delegated())) {
                continue;
            }
            $count = 0;
            foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                if (!$cm->visible || $cm->is_stealth() || $cm->deletioninprogress || empty($cm->url)) {
                    continue;
                }
                $count++;
                $types[$cm->modname] = ($types[$cm->modname] ?? 0) + 1;
            }
            $name = get_section_name($course, $section);
            // The general section without a name or a summary of its own is left out of the outline.
            if ($section->section == 0 && !$count && $section->name === null) {
                continue;
            }
            $label = $count == 1 ? 'catalogue_activities_one' : 'catalogue_activities';
            $outline[] = [
                'name' => $name,
                'count' => $count ? get_string($label, 'theme_epure', $count) : '',
            ];
        }
        arsort($types);
        $contents = [];
        foreach ($types as $modname => $count) {
            $contents[] = [
                'count' => $count,
                'name' => get_string($count > 1 ? 'modulenameplural' : 'modulename', 'mod_' . $modname),
            ];
        }
        return [$outline, $contents];
    }
}
