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
 * Course page of Épure: a banner at the top of the course, and the progress of each section.
 *
 * The banner shows the course image and its category around the heading of the page. For a
 * learner, it adds the progress, the next activity and the next deadline, with a button to
 * continue; for a teacher, the participants, the learners active this week, the submissions to
 * grade and direct links. Each section of the course shows how many of its activities the
 * learner has completed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_page {
    /**
     * Whether a page is the main page of a course (or of one of its sections) that gets the banner:
     * as chosen for the IOMAD company of the user, else in the theme settings.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        return str_starts_with((string) $page->pagetype, 'course-view-')
            && !empty($page->course->id) && (int) $page->course->id !== (int) SITEID
            && company_style::course_banner();
    }

    /**
     * Context of the template theme_epure/course_banner.
     *
     * @param \renderer_base $output Renderer, for the generated course image.
     * @param \stdClass $course Course.
     * @param string $header Header of the page, as Boost renders it (breadcrumb, heading, actions).
     * @param int|null $userid User, the current one by default.
     * @return array
     */
    public static function export(\renderer_base $output, \stdClass $course, string $header, ?int $userid = null): array {
        global $USER;
        $userid = $userid ?? (int) $USER->id;
        $user = \core_user::get_user($userid) ?: $USER;
        $context = \context_course::instance($course->id);
        $teaching = isloggedin() && !isguestuser() && mycourses::is_teaching($context, $userid);
        // A teacher who switched to the role of a learner previews the banner of a learner.
        $learning = !$teaching && isloggedin() && !isguestuser()
            && (is_enrolled($context, $userid, '', true) || is_role_switched($course->id));

        if ($teaching || $learning) {
            $data = mycourses::card($course, $context, $output, $user, $teaching);
        } else {
            // Visitors (guests, users who can view the course without being enrolled): the course itself only.
            $data = array_intersect_key(
                mycourses::card($course, $context, $output, $user, false),
                array_flip(['id', 'name', 'image', 'category', 'hidden'])
            );
        }
        $data['header'] = $header;
        $data['learning'] = $learning;
        // Without completion tracking nor deadline, a learner has nothing more than the course itself.
        $data['haslearnerpanel'] = $learning && (!empty($data['hasprogress']) || !empty($data['next']) || !empty($data['deadline']));
        $data['teaching'] = $teaching;
        return $data;
    }

    /**
     * Progress of each section of a course for a learner: the activities with completion they completed.
     *
     * @param \stdClass $course Course.
     * @param int $userid Learner.
     * @return array[] Sections with completion, each with id, done and total.
     */
    public static function sections(\stdClass $course, int $userid): array {
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return [];
        }
        $modinfo = get_fast_modinfo($course, $userid);
        $sections = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $done = $total = 0;
            foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                if (!$cm->uservisible || $cm->deletioninprogress || $completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
                    continue;
                }
                $total++;
                $state = $completion->get_data($cm, false, $userid)->completionstate;
                if ($state == COMPLETION_COMPLETE || $state == COMPLETION_COMPLETE_PASS) {
                    $done++;
                }
            }
            if ($total) {
                $sections[] = [
                    'id' => (int) $section->id,
                    'done' => $done,
                    'total' => $total,
                    'label' => get_string('coursesectionprogress', 'theme_epure', (object) ['done' => $done, 'total' => $total]),
                ];
            }
        }
        return $sections;
    }
}
