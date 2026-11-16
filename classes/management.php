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
 * Management dashboard of the platform, for those who see the reports of the site: the indicators of the last 30 days
 * compared with the 30 days before (active users, new users, new enrolments, courses completed), the courses most
 * followed, and how far the learners are in the courses that track their completion.
 *
 * Read from the tables of Moodle at each display: the logins from the standard log (when it is on), the rest from the
 * users, the enrolments and the completions. Every figure is said in words; the bars only repeat them.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class management {
    /** @var int Length of a period, in days. */
    public const DAYS = 30;

    /** @var int Courses listed among the most followed. */
    public const TOP = 5;

    /** @var string Capability to see the dashboard. */
    public const CAPABILITY = 'moodle/site:viewreports';

    /**
     * Whether the standard log of Moodle records the events, to count the users who logged in.
     *
     * @return bool
     */
    public static function has_log(): bool {
        $stores = explode(',', (string) get_config('tool_log', 'enabled_stores'));
        return in_array('logstore_standard', $stores, true);
    }

    /**
     * The indicators of a period compared with the period before.
     *
     * @param int $now Time of reference.
     * @return array[] Each with key, label, value, previous and the words of the change.
     */
    public static function indicators(int $now): array {
        global $DB, $CFG;
        $length = self::DAYS * DAYSECS;
        // The period ends with now, included: what happens at this very second counts.
        $periods = [[$now - $length, $now + 1], [$now - 2 * $length, $now - $length]];
        $count = function (string $sql, array $params) use ($DB, $periods): array {
            $values = [];
            foreach ($periods as [$from, $to]) {
                $values[] = (int) $DB->count_records_sql($sql, $params + ['since' => $from, 'until' => $to]);
            }
            return $values;
        };
        $indicators = [];
        // Users who logged in, from the standard log; without it, only the last access of each user is known.
        if (self::has_log()) {
            $indicators['active'] = $count(
                "SELECT COUNT(DISTINCT userid) FROM {logstore_standard_log}
                  WHERE eventname = :event AND timecreated >= :since AND timecreated < :until",
                ['event' => '\\core\\event\\user_loggedin']
            );
        } else {
            $indicators['active'] = [(int) $DB->count_records_select(
                'user',
                'deleted = 0 AND id <> :guest AND lastaccess >= :since',
                ['guest' => $CFG->siteguest, 'since' => $now - $length]
            ), null];
        }
        $indicators['newusers'] = $count(
            "SELECT COUNT(1) FROM {user}
              WHERE deleted = 0 AND id <> :guest AND timecreated >= :since AND timecreated < :until",
            ['guest' => $CFG->siteguest]
        );
        $indicators['enrolments'] = $count(
            "SELECT COUNT(1) FROM {user_enrolments} WHERE timecreated >= :since AND timecreated < :until",
            []
        );
        $indicators['completions'] = $count(
            "SELECT COUNT(1) FROM {course_completions} WHERE timecompleted >= :since AND timecompleted < :until",
            []
        );
        $export = [];
        foreach ($indicators as $key => [$value, $previous]) {
            $trend = $previous === null || $value === $previous ? 'same' : ($value > $previous ? 'up' : 'down');
            $export[] = [
                'key' => $key,
                'label' => get_string('management_' . $key, 'theme_epure'),
                'value' => $value,
                'previous' => $previous,
                'change' => self::change($value, $previous),
                'trend' => $trend,
                'icon' => ['up' => 'fa-arrow-up', 'down' => 'fa-arrow-down', 'same' => 'fa-minus'][$trend],
            ];
        }
        return $export;
    }

    /**
     * The change from the period before, in words: in percent when the period before had some, in number otherwise.
     *
     * @param int $value Value of the period.
     * @param int|null $previous Value of the period before, null when it is not known.
     * @return string
     */
    public static function change(int $value, ?int $previous): string {
        if ($previous === null) {
            return get_string('management_change_unknown', 'theme_epure');
        }
        if ($value === $previous) {
            return get_string('management_change_same', 'theme_epure');
        }
        if ($previous === 0) {
            return get_string('management_change_new', 'theme_epure', $value);
        }
        $percent = (int) round(100 * abs($value - $previous) / $previous);
        return get_string(
            $value > $previous ? 'management_change_up' : 'management_change_down',
            'theme_epure',
            (object) ['percent' => $percent, 'previous' => $previous]
        );
    }

    /**
     * The courses most followed over the period: by users enrolled who came into the course, then by users enrolled.
     *
     * @param int $now Time of reference.
     * @return array[] Each with name, url, enrolled, active and completed.
     */
    public static function courses(int $now): array {
        global $DB;
        $since = $now - self::DAYS * DAYSECS;
        $records = $DB->get_records_sql(
            "SELECT c.id, c.fullname,
                    (SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid
                      WHERE e.courseid = c.id AND ue.status = 0 AND e.status = 0) AS enrolled,
                    (SELECT COUNT(1) FROM {user_lastaccess} la
                      WHERE la.courseid = c.id AND la.timeaccess >= :since AND EXISTS (
                            SELECT 1 FROM {user_enrolments} ue2 JOIN {enrol} e2 ON e2.id = ue2.enrolid
                             WHERE e2.courseid = c.id AND ue2.userid = la.userid AND ue2.status = 0 AND e2.status = 0))
                        AS active,
                    (SELECT COUNT(1) FROM {course_completions} cc WHERE cc.course = c.id AND cc.timecompleted >= :since2)
                        AS completed
               FROM {course} c
              WHERE c.id <> :site
           ORDER BY active DESC, enrolled DESC, c.fullname ASC",
            ['since' => $since, 'since2' => $since, 'site' => SITEID],
            0,
            self::TOP
        );
        $courses = [];
        foreach ($records as $record) {
            if (!$record->enrolled && !$record->active) {
                continue;
            }
            $context = \context_course::instance($record->id);
            $courses[] = [
                'name' => format_string($record->fullname, true, ['context' => $context]),
                'url' => (new \moodle_url('/course/view.php', ['id' => $record->id]))->out(false),
                'enrolled' => (int) $record->enrolled,
                'active' => (int) $record->active,
                'completed' => (int) $record->completed,
            ];
        }
        return $courses;
    }

    /**
     * How far the learners enrolled are in the courses that track their completion: completed, in progress (an activity
     * done, or the course started), not started.
     *
     * @return array With total and the three parts, each with key, label, count and percent.
     */
    public static function completion(): array {
        global $DB;
        $record = $DB->get_record_sql(
            "SELECT COUNT(1) AS total,
                    SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN (cc.timecompleted IS NULL OR cc.timecompleted = 0)
                              AND (cc.timestarted > 0 OR EXISTS (
                                  SELECT 1 FROM {course_modules_completion} cmc
                                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                                 WHERE cm.course = enrolled.courseid AND cmc.userid = enrolled.userid
                                   AND cmc.completionstate > 0)) THEN 1 ELSE 0 END) AS progress
               FROM (SELECT DISTINCT e.courseid, ue.userid
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid AND e.status = 0
                       JOIN {course} c ON c.id = e.courseid AND c.enablecompletion = 1
                      WHERE ue.status = 0) enrolled
          LEFT JOIN {course_completions} cc ON cc.course = enrolled.courseid AND cc.userid = enrolled.userid",
            []
        );
        $total = (int) $record->total;
        $counts = ['completed' => (int) $record->completed, 'progress' => (int) $record->progress];
        $counts['notstarted'] = max(0, $total - $counts['completed'] - $counts['progress']);
        $parts = [];
        foreach ($counts as $key => $count) {
            $percent = $total ? (int) round(100 * $count / $total) : 0;
            $parts[] = [
                'key' => $key,
                'text' => get_string('management_part', 'theme_epure', (object) [
                    'label' => get_string('management_completion_' . $key, 'theme_epure'),
                    'count' => $count,
                    'percent' => $percent,
                ]),
                'percent' => $percent,
            ];
        }
        return [
            'total' => $total,
            'totaltext' => get_string(
                $total === 1 ? 'management_completion_total_one' : 'management_completion_total',
                'theme_epure',
                $total
            ),
            'parts' => $parts,
            'hasdata' => $total > 0,
        ];
    }

    /**
     * Context of the template theme_epure/management.
     *
     * @param int|null $now Time of reference, now by default.
     * @return array
     */
    public static function export(?int $now = null): array {
        $now = $now ?? time();
        $courses = self::courses($now);
        return [
            'days' => self::DAYS,
            'indicators' => self::indicators($now),
            'courses' => $courses,
            'hascourses' => !empty($courses),
            'completion' => self::completion(),
            'nolog' => !self::has_log(),
        ];
    }
}
