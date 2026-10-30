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
 * « My summary »: a page a learner prints or saves as PDF, with the courses they take, their progress, the activities
 * they completed with their date, their grade in each course and their certificates.
 *
 * It shows what the platform recorded, the data of the learner only; it does not prove on its own attendance nor the
 * achievement of the objectives. A fuller record, for the managers, would belong to a plugin of reports.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_summary {
    /**
     * Whether the summary is offered: the setting of the site, with Épure as the theme of the user.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return (bool) get_config('theme_epure', 'mysummary') && theme_use::is_epure(theme_use::current_name());
    }

    /**
     * Address of the page.
     *
     * @return \moodle_url
     */
    public static function url(): \moodle_url {
        return new \moodle_url('/theme/epure/summary.php');
    }

    /**
     * Context of the template theme_epure/learner_summary.
     *
     * @param \renderer_base $output Renderer, for the generated course images of « My courses ».
     * @param int $userid Learner, the current user: the summary is theirs only.
     * @param int|null $now Time of the summary, now by default.
     * @return array
     */
    public static function export(\renderer_base $output, int $userid, ?int $now = null): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/grade/querylib.php');
        require_once($CFG->libdir . '/gradelib.php');
        $now = $now ?? time();
        $user = \core_user::get_user($userid, '*', MUST_EXIST);

        $learning = [];
        foreach (mycourses::export($output, $userid)['sections'] as $section) {
            if ($section['role'] === mycourses::LEARNING) {
                $learning = $section['courses'];
            }
        }
        $ids = array_column($learning, 'id');
        $completions = [];
        $finished = [];
        if ($ids) {
            [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $completions = $DB->get_records_select_menu(
                'course_completions',
                "userid = :userid AND course $insql AND timecompleted IS NOT NULL",
                $params + ['userid' => $userid],
                '',
                'course, timecompleted'
            );
            // The activities completed, with their date, in all the courses at once.
            $records = $DB->get_recordset_sql(
                "SELECT cmc.coursemoduleid, cmc.timemodified, cm.course
                   FROM {course_modules_completion} cmc
                   JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                  WHERE cmc.userid = :userid AND cmc.completionstate IN (:complete, :pass) AND cm.course $insql",
                $params + ['userid' => $userid, 'complete' => COMPLETION_COMPLETE, 'pass' => COMPLETION_COMPLETE_PASS]
            );
            foreach ($records as $record) {
                $finished[(int) $record->course][(int) $record->coursemoduleid] = (int) $record->timemodified;
            }
            $records->close();
        }

        $date = fn(int $time) => userdate($time, get_string('strftimedatefullshort', 'langconfig'));
        $courses = [];
        foreach ($learning as $card) {
            $courseid = (int) $card['id'];
            $modinfo = get_fast_modinfo($courseid, $userid);
            $activities = [];
            foreach ($finished[$courseid] ?? [] as $cmid => $time) {
                $cm = $modinfo->cms[$cmid] ?? null;
                if ($cm && $cm->uservisible && !$cm->deletioninprogress) {
                    $activities[] = ['name' => $cm->get_formatted_name(), 'time' => $time, 'date' => $time ? $date($time) : ''];
                }
            }
            usort($activities, fn($a, $b) => $a['time'] <=> $b['time']);
            $timecompleted = (int) ($completions[$courseid] ?? 0);
            $courses[] = [
                'name' => $card['name'],
                'url' => $card['url'],
                'category' => $card['category'] ?? '',
                'hasprogress' => !empty($card['hasprogress']),
                'progress' => (int) ($card['progress'] ?? 0),
                'completed' => !empty($card['completed']),
                'datecompleted' => $timecompleted ? $date($timecompleted) : '',
                'grade' => self::grade($modinfo->get_course(), $userid),
                'activities' => $activities,
                'hasactivities' => !empty($activities),
            ];
        }

        $certificates = achievements::certificates($userid, 0);
        return [
            'fullname' => fullname($user),
            'date' => $date($now),
            'courses' => $courses,
            'hascourses' => !empty($courses),
            'taken' => count($courses),
            'completedcount' => count(array_filter($courses, fn($course) => $course['completed'])),
            'certificates' => $certificates,
            'hascertificates' => !empty($certificates),
            'certificatecount' => count($certificates),
        ];
    }

    /**
     * Grade of a learner in a course, as their report of the grades shows it: none when the course hides the grades
     * from the learners, or the grade or the total are hidden from them.
     *
     * @param \stdClass $course Course.
     * @param int $userid Learner.
     * @return string Empty without a grade.
     */
    protected static function grade(\stdClass $course, int $userid): string {
        $context = \context_course::instance($course->id);
        if (empty($course->showgrades) || !has_capability('moodle/grade:view', $context, $userid)) {
            return '';
        }
        try {
            $grade = grade_get_course_grade($userid, $course->id);
        } catch (\Throwable $e) {
            debugging('Épure could not read the grade of a course: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return '';
        }
        if (!$grade || $grade->grade === null || $grade->grade === false) {
            return '';
        }
        $hidden = !empty($grade->hidden) || !empty($grade->item->hidden);
        if ($hidden && !has_capability('moodle/grade:viewhidden', $context, $userid)) {
            return '';
        }
        return (string) ($grade->str_long_grade ?? $grade->str_grade);
    }
}
