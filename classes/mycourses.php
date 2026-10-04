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

use core_course\external\course_summary_exporter;

/**
 * Data of the « My courses » page of Épure: the courses of the user split by role.
 *
 * In the courses the user teaches (they can see all the grades), the card shows the participants,
 * the assignments to grade, the learners active this week, and links to the participants, the
 * grades and the settings. In the courses the user takes, the card shows the progress, the next
 * activity to do (« Continue »), the next deadline, and whether the course is completed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mycourses {
    /** @var string Role of a user who teaches a course. */
    public const TEACHING = 'teaching';

    /** @var string Role of a user who takes a course. */
    public const LEARNING = 'learning';

    /**
     * Whether the user teaches a course: they can see the grades of all the participants.
     *
     * @param \context_course $context Course context.
     * @param int $userid User.
     * @return bool
     */
    public static function is_teaching(\context_course $context, int $userid): bool {
        return has_capability('moodle/grade:viewall', $context, $userid);
    }

    /**
     * Context of the template theme_epure/mycourses.
     *
     * @param \renderer_base $output Renderer, for the generated course images.
     * @param int|null $userid User, the current one by default.
     * @param array $iomad With IOMAD's « My courses » block: available (courses the user can start,
     *     each with id, fullname, url, image) and downloadcerts (address to download the certificates).
     * @return array
     */
    public static function export(\renderer_base $output, ?int $userid = null, array $iomad = []): array {
        global $USER, $DB;
        $userid = $userid ?? (int) $USER->id;
        $user = \core_user::get_user($userid);

        $courses = enrol_get_all_users_courses($userid, true, null);
        $favourites = [];
        $service = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($userid));
        foreach ($service->find_favourites_by_type('core_course', 'courses') as $favourite) {
            $favourites[(int) $favourite->itemid] = true;
        }
        $lastaccess = $DB->get_records_menu('user_lastaccess', ['userid' => $userid], '', 'courseid, timeaccess');

        $sections = [self::TEACHING => [], self::LEARNING => []];
        foreach ($courses as $course) {
            // Courses the user hid from « My courses » stay hidden.
            if (get_user_preferences('block_myoverview_hidden_course_' . $course->id, 0, $userid)) {
                continue;
            }
            $context = \context_course::instance($course->id);
            $teaching = self::is_teaching($context, $userid);
            $card = self::card($course, $context, $output, $user, $teaching);
            $card['favourite'] = isset($favourites[$course->id]);
            $card['lastaccess'] = (int) ($lastaccess[$course->id] ?? 0);
            $sections[$teaching ? self::TEACHING : self::LEARNING][] = $card;
        }

        // Starred courses first, then the most recently visited, then by name.
        $sort = fn($a, $b) => [$b['favourite'], $b['lastaccess'], $a['sortname']]
            <=> [$a['favourite'], $a['lastaccess'], $b['sortname']];
        $coursesword = get_string('courses');
        $result = [];
        foreach ([self::TEACHING, self::LEARNING] as $role) {
            if (!$sections[$role]) {
                continue;
            }
            usort($sections[$role], $sort);
            $result[] = [
                'role' => $role,
                'title' => get_string('mycourses_' . $role, 'theme_epure', $coursesword),
                'courses' => $sections[$role],
                'count' => count($sections[$role]),
            ];
        }
        // IOMAD: the courses the user can start (company courses, licences), not enrolled yet.
        $available = [];
        foreach ($iomad['available'] ?? [] as $course) {
            $course = (object) $course;
            $name = format_string($course->fullname);
            $available[] = [
                'id' => (int) $course->id,
                'name' => $name,
                'sortname' => \core_text::strtolower($name),
                'url' => (string) $course->url,
                'image' => (string) $course->image,
                'category' => (string) ($course->coursecategory ?? ''),
                'status' => 'available',
                'available' => true,
                'rolename' => get_string('mycourses_availablebadge', 'theme_epure'),
            ];
        }
        if ($available) {
            $result[] = [
                'role' => 'available',
                'title' => get_string('mycourses_available', 'theme_epure', $coursesword),
                'courses' => $available,
                'count' => count($available),
            ];
        }

        return [
            'downloadcerts' => $iomad['downloadcerts'] ?? null,
            'sections' => $result,
            'showheadings' => count($result) > 1,
            'hascourses' => !empty($result),
            'filters' => [
                ['value' => 'all', 'label' => get_string('all', 'block_myoverview'), 'selected' => true],
                ['value' => 'inprogress', 'label' => get_string('inprogress', 'block_myoverview'), 'selected' => false],
                ['value' => 'future', 'label' => get_string('future', 'block_myoverview'), 'selected' => false],
                ['value' => 'past', 'label' => get_string('past', 'block_myoverview'), 'selected' => false],
            ],
        ];
    }

    /**
     * Data of the card of a course.
     *
     * @param \stdClass $course Course.
     * @param \context_course $context Its context.
     * @param \renderer_base $output Renderer.
     * @param \stdClass $user User.
     * @param bool $teaching Whether the user teaches the course.
     * @return array
     */
    public static function card(
        \stdClass $course,
        \context_course $context,
        \renderer_base $output,
        \stdClass $user,
        bool $teaching
    ): array {
        $image = course_summary_exporter::get_course_image($course) ?: $output->get_generated_image_for_id($course->id);
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
        $name = format_string($course->fullname, true, ['context' => $context]);
        $card = [
            'id' => (int) $course->id,
            'name' => $name,
            'sortname' => \core_text::strtolower($name),
            'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'image' => $image instanceof \moodle_url ? $image->out(false) : (string) $image,
            'category' => $category ? $category->get_formatted_name() : '',
            'status' => course_classify_for_timeline($course, $user),
            'hidden' => empty($course->visible),
            'teaching' => $teaching,
            'rolename' => $teaching ? get_string('defaultcourseteacher') : get_string('defaultcoursestudent'),
        ];
        return $card + ($teaching ? self::teacher_data($course, $context) : self::learner_data($course, $user));
    }

    /**
     * Figures and links of a course the user teaches.
     *
     * @param \stdClass $course Course.
     * @param \context_course $context Its context.
     * @return array
     */
    protected static function teacher_data(\stdClass $course, \context_course $context): array {
        global $CFG, $DB;
        // Learners: the users whose progress is followed in the course.
        $learners = get_enrolled_sql($context, 'moodle/course:isincompletionreports', 0, true);
        $participants = (int) $DB->count_records_sql("SELECT COUNT(1) FROM ($learners[0]) l", $learners[1]);
        $active = (int) $DB->count_records_sql(
            "SELECT COUNT(1) FROM {user_lastaccess} la JOIN ($learners[0]) l ON l.id = la.userid
              WHERE la.courseid = :courseid AND la.timeaccess > :since",
            $learners[1] + ['courseid' => $course->id, 'since' => time() - WEEKSECS]
        );

        // Submissions waiting for a grade, in the assignments the user can grade.
        $tograde = 0;
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_instances_of('assign') as $cm) {
            if (!$cm->uservisible || !has_capability('mod/assign:grade', $cm->context)) {
                continue;
            }
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
            try {
                $tograde += (new \assign($cm->context, $cm, $course))->count_submissions_need_grading();
            } catch (\Throwable $e) {
                debugging('Épure: could not count the submissions to grade: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        $link = fn(string $label, string $path, string $icon) => [
            'label' => $label,
            'url' => (new \moodle_url($path, ['id' => $course->id]))->out(false),
            'icon' => $icon,
        ];
        $links = [
            $link(get_string('participants'), '/user/index.php', 'fa-users'),
            $link(get_string('grades'), '/grade/report/index.php', 'fa-table'),
        ];
        if (has_capability('moodle/course:update', $context)) {
            $links[] = $link(get_string('settings'), '/course/edit.php', 'fa-gear');
        }
        return [
            'participants' => $participants,
            'active' => $active,
            'tograde' => $tograde,
            'hastograde' => $tograde > 0,
            'gradeurl' => (new \moodle_url('/mod/assign/index.php', ['id' => $course->id]))->out(false),
            'links' => $links,
        ];
    }

    /**
     * Progress, next activity and next deadline of a course the user takes.
     *
     * @param \stdClass $course Course.
     * @param \stdClass $user User.
     * @return array
     */
    protected static function learner_data(\stdClass $course, \stdClass $user): array {
        global $CFG;
        $completion = new \completion_info($course);
        $progress = null;
        $completed = false;
        if ($completion->is_enabled()) {
            $percentage = \core_completion\progress::get_course_progress_percentage($course, $user->id);
            $progress = $percentage === null ? null : (int) floor($percentage);
            $completed = $completion->is_course_complete($user->id);
        }

        // The next activity to do: the first visible one, in the course order, not completed yet.
        $next = null;
        $modinfo = get_fast_modinfo($course, $user->id);
        if ($completion->is_enabled() && !$completed) {
            foreach ($modinfo->get_section_info_all() as $section) {
                foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if (
                        !$cm->uservisible || !$cm->url || $cm->deletioninprogress
                            || $completion->is_enabled($cm) == COMPLETION_TRACKING_NONE
                    ) {
                        continue;
                    }
                    $state = $completion->get_data($cm, false, $user->id)->completionstate;
                    if ($state == COMPLETION_INCOMPLETE || $state == COMPLETION_COMPLETE_FAIL) {
                        $next = ['name' => $cm->get_formatted_name(), 'url' => $cm->url->out(false)];
                        break 2;
                    }
                }
            }
        }

        // The next deadline of the course (assignment due, quiz closing…).
        $deadline = null;
        require_once($CFG->dirroot . '/calendar/lib.php');
        try {
            $events = \core_calendar\local\api::get_action_events_by_course($course, time(), null, null, 1);
            if ($event = reset($events)) {
                $deadline = [
                    'name' => format_string($event->get_name()),
                    'date' => userdate(
                        $event->get_times()->get_sort_time()->getTimestamp(),
                        get_string('strftimedatefullshort', 'langconfig')
                    ),
                    'url' => $event->get_action() && $event->get_action()->get_url()
                        ? $event->get_action()->get_url()->out(false) : null,
                ];
            }
        } catch (\Throwable $e) {
            debugging('Épure: could not read the deadlines: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        if ($completed) {
            $action = get_string('mycourses_review', 'theme_epure');
        } else if ($progress) {
            $action = get_string('mycourses_continue', 'theme_epure');
        } else {
            $action = get_string('mycourses_start', 'theme_epure');
        }
        return [
            'hasprogress' => $progress !== null,
            'progress' => (int) $progress,
            'completed' => $completed,
            'next' => $next,
            'deadline' => $deadline,
            'action' => $action,
            'actionurl' => $next['url'] ?? (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
        ];
    }
}
