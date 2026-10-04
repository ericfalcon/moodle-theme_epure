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
 * Dashboard of a learner: an overview at the top of the dashboard, above the blocks.
 *
 * It shows the course to resume (the last one visited and not completed, with its next activity),
 * the other courses in progress, the next deadlines of all the courses, and the courses completed,
 * with a link to the certificates when the platform issues some (IOMAD, Certificate, Custom certificate).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_dashboard {
    /** @var int Courses in progress shown besides the one to resume. */
    public const INPROGRESS = 4;

    /** @var int Deadlines shown. */
    public const DEADLINES = 5;

    /** @var int Completed courses shown. */
    public const COMPLETED = 3;

    /**
     * Whether a page is the dashboard that gets the overview.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        return $page->pagetype === 'my-index' && isloggedin() && !isguestuser()
            && company_style::learner_dashboard();
    }

    /**
     * Context of the template theme_epure/learner_dashboard.
     *
     * @param \renderer_base $output Renderer, for the generated course images.
     * @param int|null $userid User, the current one by default.
     * @return array|null Null when the user takes no course.
     */
    public static function export(\renderer_base $output, ?int $userid = null): ?array {
        global $USER, $DB;
        $userid = $userid ?? (int) $USER->id;

        $learning = [];
        foreach (mycourses::export($output, $userid)['sections'] as $section) {
            if ($section['role'] === mycourses::LEARNING) {
                $learning = $section['courses'];
            }
        }
        if (!$learning) {
            return null;
        }

        $inprogress = array_values(array_filter($learning, fn($card) => empty($card['completed'])));
        $completed = array_values(array_filter($learning, fn($card) => !empty($card['completed'])));

        // The course to resume: the last one visited, else the first one in the order of « My courses ».
        $resume = null;
        if ($inprogress) {
            $visited = $inprogress;
            usort($visited, fn($a, $b) => $b['lastaccess'] <=> $a['lastaccess']);
            $resume = $visited[0];
            $inprogress = array_values(array_filter($inprogress, fn($card) => $card['id'] !== $resume['id']));
        }

        // Date of completion of the completed courses, the most recent first.
        $dates = [];
        if ($completed) {
            [$insql, $params] = $DB->get_in_or_equal(array_column($completed, 'id'), SQL_PARAMS_NAMED);
            $dates = $DB->get_records_select_menu(
                'course_completions',
                "userid = :userid AND course $insql",
                $params + ['userid' => $userid],
                '',
                'course, timecompleted'
            );
        }
        foreach ($completed as &$card) {
            $card['timecompleted'] = (int) ($dates[$card['id']] ?? 0);
            $card['datecompleted'] = $card['timecompleted']
                ? userdate($card['timecompleted'], get_string('strftimedatefullshort', 'langconfig')) : '';
        }
        unset($card);
        usort($completed, fn($a, $b) => $b['timecompleted'] <=> $a['timecompleted']);

        $deadlines = self::deadlines($userid);
        return [
            'resume' => $resume,
            'inprogress' => array_slice($inprogress, 0, self::INPROGRESS),
            'hasinprogress' => !empty($inprogress),
            'moreinprogress' => count($inprogress) > self::INPROGRESS,
            'deadlines' => $deadlines,
            'hasdeadlines' => !empty($deadlines),
            'completed' => array_slice($completed, 0, self::COMPLETED),
            'completedcount' => count($completed),
            'hascompleted' => !empty($completed),
            'certificates' => self::certificates_url($userid),
            'mycoursesurl' => (new \moodle_url('/my/courses.php'))->out(false),
            'calendarurl' => (new \moodle_url('/calendar/view.php', ['view' => 'upcoming']))->out(false),
        ];
    }

    /**
     * Next deadlines of the user in all their courses: assignments due, quizzes closing…
     *
     * @param int $userid User.
     * @return array[] Each with name, course, date, url.
     */
    protected static function deadlines(int $userid): array {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');
        $deadlines = [];
        try {
            $user = \core_user::get_user($userid);
            $events = \core_calendar\local\api::get_action_events_by_timesort(time(), null, null, self::DEADLINES, true, $user);
            foreach ($events as $event) {
                $course = $event->get_course();
                $action = $event->get_action();
                $deadlines[] = [
                    'name' => format_string($event->get_name()),
                    'course' => $course ? format_string($course->get('fullname')) : '',
                    'date' => userdate(
                        $event->get_times()->get_sort_time()->getTimestamp(),
                        get_string('strftimedaydatetime', 'langconfig')
                    ),
                    'url' => $action && $action->get_url() ? $action->get_url()->out(false) : null,
                ];
            }
        } catch (\Throwable $e) {
            debugging('Épure: could not read the deadlines: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return $deadlines;
    }

    /**
     * Address of the certificates of the user, when the platform issued them some.
     *
     * @param int $userid User.
     * @return string|null
     */
    protected static function certificates_url(int $userid): ?string {
        global $DB;
        // IOMAD: certificates of the completions tracked by the company.
        if (
            company_style::iomad_installed() && ($company = company_style::current_company())
                && \iomad::has_capability('block/iomad_company_admin:downloadmycertificates', \context_system::instance())
                && $DB->record_exists_sql(
                    "SELECT 1 FROM {local_iomad_track} lit JOIN {local_iomad_track_certs} litc ON lit.id = litc.trackid
                      WHERE lit.userid = :userid AND lit.companyid = :companyid",
                    ['userid' => $userid, 'companyid' => $company->id]
                )
        ) {
            return (new \moodle_url(
                '/local/report_completion/index.php',
                ['certusers' => $userid, 'action' => 'downloadcerts', 'sesskey' => sesskey()]
            ))->out(false);
        }
        if (
            \core_component::get_component_directory('tool_certificate')
                && $DB->record_exists('tool_certificate_issues', ['userid' => $userid])
        ) {
            return (new \moodle_url('/admin/tool/certificate/my.php'))->out(false);
        }
        if (
            \core_component::get_component_directory('mod_customcert')
                && $DB->record_exists('customcert_issues', ['userid' => $userid])
        ) {
            return (new \moodle_url('/mod/customcert/my_certificates.php', ['userid' => $userid]))->out(false);
        }
        return null;
    }
}
