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
 * Pages of the activities: where the learner is in the course, and how to go on.
 *
 * At the top, a strip with the course, the position of the activity (« Activity 3 of 12 »), the
 * progress of the learner and the reading mode; at the bottom, the previous and next activities
 * as cards. With a course index, Moodle 4 shows no links to the previous and next activities.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_page {
    /**
     * Whether a page is the page of an activity that gets the strip and the navigation.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        try {
            return $page->context->contextlevel == CONTEXT_MODULE && $page->pagelayout === 'incourse'
                && $page->cm && !$page->cm->is_stealth() && (int) $page->course->id !== (int) SITEID
                // In the format « Single activity », the activity is the course: no strip nor previous and next.
                && $page->course->format !== 'singleactivity'
                && company_style::enabled('activitynav');
        } catch (\Throwable $e) {
            // Expected: the page has no context or course yet (pages that set them later, errors).
            return false;
        }
    }

    /**
     * The activities of the course the user can open, in the order of the course page (those of a subsection at the
     * place of the subsection), and where the current one is.
     *
     * @param \cm_info $current Current activity.
     * @return array{0: \cm_info[], 1: int|false} The activities, and the position of the current one.
     */
    public static function sequence(\cm_info $current): array {
        $activities = [];
        foreach (course_structure::cms(get_fast_modinfo($current->course)) as $cm) {
            // Labels and other modules without a page are left out, as in Moodle's own navigation.
            if ($cm->uservisible && !$cm->is_stealth() && !empty($cm->url)) {
                $activities[] = $cm;
            }
        }
        return [$activities, array_search((int) $current->id, array_map(fn($cm) => (int) $cm->id, $activities), true)];
    }

    /**
     * Context of the template theme_epure/activity_navigation.
     *
     * @param \cm_info $current Current activity.
     * @return array|null Null when there is nothing to show.
     */
    public static function navigation(\cm_info $current): ?array {
        [$activities, $position] = self::sequence($current);
        if ($position === false) {
            return null;
        }
        $card = function (?\cm_info $cm) {
            if (!$cm) {
                return null;
            }
            return [
                'name' => $cm->get_formatted_name(),
                'url' => (new \moodle_url($cm->url, ['forceview' => 1]))->out(false),
                'icon' => $cm->get_icon_url()->out(false),
                'section' => course_structure::place_name($cm),
            ];
        };
        // Back to the section of the course page the activity is in: the section holding its subsection, if any.
        [$section] = course_structure::sections_of($current);
        return [
            'previous' => $card($activities[$position - 1] ?? null),
            'next' => $card($activities[$position + 1] ?? null),
            'courseurl' => course_get_url($current->course, $section ? $section->section : $current->sectionnum)->out(false),
        ];
    }

    /**
     * Whether the page is the page of one section of a course (course/section.php), where Moodle shows no previous and
     * next sections when the course index is there: on a phone, the index is in a drawer, and the section a dead end.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function section_applies(\moodle_page $page): bool {
        try {
            return str_starts_with((string) $page->pagetype, 'course-view-section-') && !empty($page->course->id)
                && (int) $page->course->id !== (int) SITEID && company_style::enabled('activitynav');
        } catch (\Throwable $e) {
            // Expected: the page has no course yet.
            return false;
        }
    }

    /**
     * Context of the template theme_epure/activity_navigation for the page of a section: the previous and next sections
     * the user can see (the subsections, shown inside their section, left out), and after the last one, back to the course.
     *
     * @param \stdClass $course Course.
     * @param int $sectionid Section shown.
     * @return array|null Null when the section is not in the course.
     */
    public static function section_navigation(\stdClass $course, int $sectionid): ?array {
        $modinfo = get_fast_modinfo($course);
        $sections = array_values(array_filter(
            $modinfo->get_section_info_all(),
            fn(\section_info $section) => $section->uservisible && !$section->is_delegated()
        ));
        $ids = array_map(fn(\section_info $section) => (int) $section->id, $sections);
        $position = array_search($sectionid, $ids, true);
        if ($position === false) {
            return null;
        }
        $format = course_get_format($course);
        $card = fn(?\section_info $section) => $section ? [
            'name' => $format->get_section_name($section),
            'url' => (new \moodle_url('/course/section.php', ['id' => $section->id]))->out(false),
            'icon' => '',
            'section' => '',
        ] : null;
        return [
            'previous' => $card($sections[$position - 1] ?? null),
            'next' => $card($sections[$position + 1] ?? null),
            'courseurl' => course_get_url($course, $sections[$position]->section)->out(false),
            'navlabel' => get_string('sectionnavigation', 'theme_epure'),
            'endlabel' => get_string('sectionend', 'theme_epure'),
        ];
    }

    /**
     * Context of the template theme_epure/activity_strip.
     *
     * @param \cm_info $current Current activity.
     * @param int $userid User.
     * @return array
     */
    public static function strip(\cm_info $current, int $userid): array {
        [$activities, $position] = self::sequence($current);
        $course = $current->get_course();
        $context = \context_course::instance($course->id);
        $progress = null;
        $learning = !mycourses::is_teaching($context, $userid)
            && (is_enrolled($context, $userid, '', true) || is_role_switched($course->id));
        if ($learning && (new \completion_info($course))->is_enabled()) {
            $percentage = \core_completion\progress::get_course_progress_percentage($course, $userid);
            $progress = $percentage === null ? null : (int) floor($percentage);
        }
        return [
            'course' => format_string($course->fullname, true, ['context' => $context]),
            'courseurl' => course_get_url($course)->out(false),
            'position' => $position === false ? '' : get_string(
                'activityposition',
                'theme_epure',
                (object) ['position' => $position + 1, 'total' => count($activities)]
            ),
            'hasprogress' => $progress !== null,
            'progress' => (int) $progress,
            'focus' => (bool) get_user_preferences('theme_epure_focus', 0, $userid),
        ];
    }
}
