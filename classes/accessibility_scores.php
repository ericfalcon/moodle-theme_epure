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
 * The scores of accessibility of the courses ({@see course_accessibility}), kept week by week for the management
 * dashboard: the score of the whole platform, of a category or of an IOMAD company, the courses to look at first, and
 * how the score goes over the weeks.
 *
 * A course changed is read again by an ad hoc task, a few minutes after its last change; a scheduled task reads at night
 * the courses not read this week. Each keeps one row per course and per week: its score, and its mistakes by severity;
 * a course read again in the same week replaces its row. Reading every course at each display would be too slow on a
 * large platform.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accessibility_scores {
    /** @var string Table of the scores. */
    public const TABLE = 'theme_epure_a11yscore';

    /** @var int Weeks kept: a year. */
    public const WEEKS = 53;

    /** @var int Weeks of the trend shown on the dashboard. */
    public const TREND = 12;

    /** @var int Courses and categories listed, the lowest scores first. */
    public const TOP = 5;

    /** @var int Seconds after the last change of a course before its score is computed again. */
    public const DELAY = 300;

    /** @var int Seconds a run of the task reads courses for at most; the others wait for the next night. */
    public const TIMELIMIT = 600;

    /**
     * The start of the week of a time: Monday at midnight (UTC), the same everywhere on the platform.
     *
     * @param int $time Time.
     * @return int
     */
    public static function week(int $time): int {
        // The 1 January 1970 was a Thursday: Mondays fall 4 days after the multiples of a week.
        $offset = 4 * DAYSECS;
        return $time - (($time - $offset) % WEEKSECS + WEEKSECS) % WEEKSECS;
    }

    /**
     * Reads the courses whose score of the week is not known yet, or whose content changed since: those never read
     * first, then the oldest. Stops after a time, the others waiting for the next run. Forgets the weeks of more than
     * a year, and the courses deleted.
     *
     * @param int|null $now Time of reference, now by default.
     * @param int $timelimit Seconds to read for at most.
     * @return int Courses read.
     */
    public static function compute(?int $now = null, int $timelimit = self::TIMELIMIT): int {
        global $DB;
        $now = $now ?? time();
        $week = self::week($now);
        $DB->delete_records_select(self::TABLE, 'weekstart < :oldest', ['oldest' => $week - (self::WEEKS - 1) * WEEKSECS]);
        $DB->delete_records_select(self::TABLE, 'courseid NOT IN (SELECT id FROM {course})');
        $courses = $DB->get_records_sql(
            "SELECT c.*, s.id AS scoreid
               FROM {course} c
          LEFT JOIN {" . self::TABLE . "} s ON s.courseid = c.id AND s.weekstart = :week
              WHERE c.id <> :site AND (s.id IS NULL OR s.cacherev <> c.cacherev)
           ORDER BY CASE WHEN s.id IS NULL THEN 0 ELSE 1 END, s.timecomputed ASC, c.id ASC",
            ['week' => $week, 'site' => SITEID]
        );
        $start = microtime(true);
        $read = 0;
        foreach ($courses as $course) {
            if ($read && microtime(true) - $start > $timelimit) {
                break;
            }
            unset($course->scoreid);
            self::store($course, $now);
            $read++;
        }
        return $read;
    }

    /**
     * Reads a course and keeps its score of the week, in place of the one already kept this week.
     *
     * @param \stdClass $course Course.
     * @param int $now Time of reference.
     */
    public static function store(\stdClass $course, int $now): void {
        global $DB;
        $week = self::week($now);
        $record = self::read($course) + ['weekstart' => $week, 'timecomputed' => $now];
        $id = $DB->get_field(self::TABLE, 'id', ['courseid' => $course->id, 'weekstart' => $week]);
        if ($id) {
            $DB->update_record(self::TABLE, (object) (['id' => $id] + $record));
        } else {
            $DB->insert_record(self::TABLE, (object) $record);
        }
    }

    /**
     * Whether the scores are computed: the guide « Accessibility of the course » is on for the site, or for an IOMAD
     * company that turned it on for itself (the site leaving it off, as by default).
     *
     * @return bool
     */
    public static function active(): bool {
        if (get_config('theme_epure', 'courseaccessibility') === '1') {
            return true;
        }
        foreach ((array) get_config('theme_epure') as $name => $value) {
            if (str_starts_with((string) $name, 'companystyle_')) {
                $settings = json_decode((string) $value, true);
                if (is_array($settings) && ($settings['courseaccessibility'] ?? '') === 'show') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Whether the dashboard shows the scores: of the whole platform when they are computed, of an IOMAD company when its
     * guide is on (its own choice, else the one of the site).
     *
     * @param int $companyid IOMAD company, 0 for the whole platform.
     * @return bool
     */
    public static function shown(int $companyid): bool {
        if (!$companyid) {
            return self::active();
        }
        $own = (string) (company_style::settings($companyid)['courseaccessibility'] ?? '');
        return $own !== '' ? $own === 'show' : get_config('theme_epure', 'courseaccessibility') === '1';
    }

    /**
     * Asks for the score of a course to be computed again by an ad hoc task, a few minutes after its last change: the
     * changes made one after the other are read once, by the cron, and the teacher does not wait. Nothing when the guide
     * is off everywhere ({@see self::active()}).
     *
     * @param int $courseid Course changed.
     */
    public static function queue(int $courseid): void {
        if ($courseid <= SITEID || !self::active()) {
            return;
        }
        $task = new task\refresh_accessibility_score();
        $task->set_custom_data(['courseid' => $courseid]);
        // The end of the next slot of five minutes: the changes and the activities of the learners within one slot
        // find the task already at that time, and do not write it again.
        $task->set_next_run_time((intdiv(time(), self::DELAY) + 2) * self::DELAY);
        \core\task\manager::reschedule_or_queue_adhoc_task($task);
    }

    /**
     * The score of a course and its mistakes by severity, as its guide shows them.
     *
     * @param \stdClass $course Course.
     * @return array courseid, cacherev, score, parts, blocking, serious and minor.
     */
    public static function read(\stdClass $course): array {
        $figures = course_accessibility::figures(
            course_accessibility::analyse($course),
            course_accessibility::confirmations((int) $course->id)
        );
        return [
            'courseid' => (int) $course->id,
            'cacherev' => (int) $course->cacherev,
            'score' => (int) $figures['score'],
            'parts' => (int) $figures['total'],
            'blocking' => (int) $figures['levels']['blocking'],
            'serious' => (int) $figures['levels']['serious'],
            'minor' => (int) $figures['levels']['minor'],
        ];
    }

    /**
     * The condition on the courses of a category (with its subcategories) and of an IOMAD company.
     *
     * @param string $column Column of the course.
     * @param int $categoryid Category, 0 for all.
     * @param int $companyid IOMAD company, 0 for the whole platform.
     * @return array SQL to add after a WHERE, and its parameters.
     */
    protected static function scope(string $column, int $categoryid, int $companyid): array {
        global $DB;
        $sql = '';
        $params = [];
        if ($categoryid) {
            $path = (string) $DB->get_field('course_categories', 'path', ['id' => $categoryid]);
            $sql .= " AND {$column} IN (SELECT sc.id FROM {course} sc JOIN {course_categories} scc ON scc.id = sc.category
                                         WHERE scc.id = :scopecategory OR " . $DB->sql_like('scc.path', ':scopepath') . ')';
            $params += ['scopecategory' => $categoryid, 'scopepath' => $DB->sql_like_escape($path) . '/%'];
        }
        if ($companyid) {
            $sql .= " AND {$column} IN (SELECT scc.courseid FROM {" . iomad::table('company_course') . "} scc
                                         WHERE scc.companyid = :scopecompany)";
            $params += ['scopecompany' => $companyid];
        }
        return [$sql, $params];
    }

    /**
     * The last score known of each course of a scope, with its course and its category.
     *
     * @param int $categoryid Category, 0 for all.
     * @param int $companyid IOMAD company, 0 for the whole platform.
     * @return \stdClass[] By course: courseid, fullname, category, score, blocking, serious, minor, timecomputed.
     */
    public static function latest(int $categoryid = 0, int $companyid = 0): array {
        global $DB;
        [$scope, $params] = self::scope('s.courseid', $categoryid, $companyid);
        return $DB->get_records_sql(
            "SELECT s.courseid, c.fullname, c.category, s.score, s.blocking, s.serious, s.minor, s.timecomputed
               FROM {" . self::TABLE . "} s
               JOIN {course} c ON c.id = s.courseid
              WHERE s.weekstart = (SELECT MAX(w.weekstart) FROM {" . self::TABLE . "} w WHERE w.courseid = s.courseid){$scope}
           ORDER BY s.score ASC, s.blocking DESC, c.fullname ASC",
            $params
        );
    }

    /**
     * The last score known of some courses, for the table of the courses a teacher teaches.
     *
     * @param int[] $courseids Courses.
     * @return \stdClass[] By course: courseid, score, blocking, serious, minor, timecomputed; none for a course not read.
     */
    public static function of_courses(array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$in, $params] = $DB->get_in_or_equal(array_map('intval', $courseids), SQL_PARAMS_NAMED);
        return $DB->get_records_sql(
            "SELECT s.courseid, s.score, s.blocking, s.serious, s.minor, s.timecomputed
               FROM {" . self::TABLE . "} s
              WHERE s.courseid {$in}
                AND s.weekstart = (SELECT MAX(w.weekstart) FROM {" . self::TABLE . "} w WHERE w.courseid = s.courseid)",
            $params
        );
    }

    /**
     * The average score of the courses of a scope, week by week, the last weeks first.
     *
     * @param int $now Time of reference.
     * @param int $categoryid Category, 0 for all.
     * @param int $companyid IOMAD company, 0 for the whole platform.
     * @return array[] Each with week, score (average) and courses.
     */
    public static function trend(int $now, int $categoryid = 0, int $companyid = 0): array {
        global $DB;
        [$scope, $params] = self::scope('s.courseid', $categoryid, $companyid);
        $records = $DB->get_records_sql(
            "SELECT s.weekstart AS weekof, AVG(s.score) AS score, COUNT(1) AS courses
               FROM {" . self::TABLE . "} s
              WHERE s.weekstart > :since{$scope}
           GROUP BY s.weekstart
           ORDER BY s.weekstart DESC",
            ['since' => self::week($now) - self::TREND * WEEKSECS] + $params
        );
        return array_values(array_map(fn($r) => [
            'week' => (int) $r->weekof,
            // Rounded down, as the scores of the courses (the margin keeps 100.0 read as 99.999… at 100).
            'score' => (int) floor((float) $r->score + 1e-9),
            'courses' => (int) $r->courses,
        ], $records));
    }

    /**
     * Context of the section « Accessibility of the courses » of the management dashboard.
     *
     * @param int $now Time of reference.
     * @param int $categoryid Category, 0 for all.
     * @param int $companyid IOMAD company, 0 for the whole platform.
     * @return array
     */
    public static function export(int $now, int $categoryid = 0, int $companyid = 0): array {
        $latest = self::latest($categoryid, $companyid);
        $count = count($latest);
        // Rounded down, as the score of a course: 100 % only when every course is at 100 %.
        $average = $count ? intdiv(array_sum(array_column($latest, 'score')), $count) : 0;
        $blocking = count(array_filter($latest, fn($r) => (int) $r->blocking > 0));
        $levels = [];
        foreach (array_keys(course_accessibility::WEIGHTS) as $level) {
            $levels[] = [
                'level' => $level,
                'text' => get_string(
                    'courseaccessibility_levelcount_' . $level,
                    'theme_epure',
                    array_sum(array_column($latest, $level))
                ),
            ];
        }

        // The courses with the lowest scores, to look at first.
        $courses = [];
        foreach (array_slice($latest, 0, self::TOP) as $record) {
            if ((int) $record->score >= 100) {
                break;
            }
            $context = \context_course::instance($record->courseid);
            $courses[] = [
                'name' => format_string($record->fullname, true, ['context' => $context]),
                'url' => course_accessibility::url((int) $record->courseid)->out(false),
                'score' => (int) $record->score,
                'blocking' => (int) $record->blocking,
            ];
        }

        // The categories, the lowest averages first.
        $bycategory = [];
        foreach ($latest as $record) {
            $bycategory[(int) $record->category][] = $record;
        }
        $categories = [];
        foreach ($bycategory as $id => $records) {
            $category = \core_course_category::get($id, IGNORE_MISSING, true);
            if (!$category) {
                continue;
            }
            $categories[] = [
                'name' => $category->get_formatted_name(),
                'courses' => count($records),
                'score' => intdiv(array_sum(array_column($records, 'score')), count($records)),
                'blocking' => count(array_filter($records, fn($r) => (int) $r->blocking > 0)),
            ];
        }
        usort($categories, fn($a, $b) => [$a['score'], $a['name']] <=> [$b['score'], $b['name']]);

        // The weeks, the last first, with the change of the score since the oldest one shown.
        $weeks = [];
        foreach (self::trend($now, $categoryid, $companyid) as $week) {
            $weeks[] = $week + [
                'date' => userdate($week['week'], get_string('strftimedatefullshort', 'core_langconfig')),
            ];
        }
        $change = '';
        if (count($weeks) > 1) {
            $points = $weeks[0]['score'] - $weeks[count($weeks) - 1]['score'];
            $key = 'same';
            if ($points) {
                $key = $points > 0 ? 'up' : 'down';
            }
            $change = get_string('a11yscores_change_' . $key, 'theme_epure', (object) [
                'points' => abs($points),
                'weeks' => count($weeks) - 1,
            ]);
        }
        $last = $latest ? max(array_column($latest, 'timecomputed')) : 0;
        $watch = !$companyid && has_capability('moodle/site:config', \context_system::instance());

        return [
            'hasdata' => $count > 0,
            'score' => $average,
            'scoretext' => get_string('a11yscores_score', 'theme_epure', $average),
            'coursestext' => get_string($count === 1 ? 'a11yscores_courses_one' : 'a11yscores_courses', 'theme_epure', $count),
            'blockingtext' => get_string('a11yscores_blocking', 'theme_epure', $blocking),
            'levels' => $levels,
            'courses' => $courses,
            'hascourses' => !empty($courses),
            'categories' => array_slice($categories, 0, self::TOP),
            'hascategories' => count($categories) > 1,
            'weeks' => $weeks,
            'hasweeks' => count($weeks) > 1,
            'change' => $change,
            'computed' => $last ? get_string('a11yscores_computed', 'theme_epure', userdate($last)) : '',
            'categorychoices' => self::category_choices($categoryid),
            'download' => $count ? self::download_selector($categoryid, $companyid) : '',
            // How the computing goes: for the platform, to whoever configures it.
            'health' => $watch ? self::health($now) : null,
        ];
    }

    /**
     * How the computing of the scores goes, for whoever watches the platform: the courses read this week out of all,
     * those changed and waiting to be read again, the last run of the reading of the night; and what to look at: the
     * cron that no longer runs, the reading of the night turned off or failing, the courses whose reading fails. A task
     * that fails is tried again later, up to a day later: a score may then stay old.
     *
     * @param int $now Time of reference.
     * @return array lines (texts), alerts (texts), hasalerts.
     */
    public static function health(int $now): array {
        global $DB;
        $week = self::week($now);
        $total = $DB->count_records_select('course', 'id <> :site', ['site' => SITEID]);
        $read = $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {" . self::TABLE . "} s
               JOIN {course} c ON c.id = s.courseid
              WHERE s.weekstart = :week AND s.cacherev = c.cacherev",
            ['week' => $week]
        );
        $refresh = '\\' . task\refresh_accessibility_score::class;
        $waiting = $DB->count_records('task_adhoc', ['classname' => $refresh]);
        $failing = $DB->count_records_select('task_adhoc', 'classname = :classname AND faildelay > 0', ['classname' => $refresh]);
        $nightly = \core\task\manager::get_scheduled_task(task\compute_accessibility_scores::class);
        $lastrun = $nightly ? (int) $nightly->get_last_run_time() : 0;
        $cron = (int) get_config('tool_task', 'lastcronstart');

        $lines = [
            get_string('a11yhealth_read', 'theme_epure', (object) ['read' => $read, 'total' => $total]),
            get_string('a11yhealth_waiting', 'theme_epure', $waiting),
            $lastrun ? get_string('a11yhealth_lastrun', 'theme_epure', userdate($lastrun))
                : get_string('a11yhealth_neverrun', 'theme_epure'),
        ];
        $alerts = [];
        if (!$cron || $now - $cron > DAYSECS) {
            $alerts[] = get_string('a11yhealth_cron', 'theme_epure', $cron ? userdate($cron) : get_string('never'));
        }
        if ($nightly && $nightly->get_disabled()) {
            $alerts[] = get_string('a11yhealth_disabled', 'theme_epure');
        } else if ($nightly && $nightly->get_fail_delay() > 0) {
            $alerts[] = get_string('a11yhealth_nightlyfails', 'theme_epure');
        }
        if ($failing) {
            $alerts[] = get_string('a11yhealth_failing', 'theme_epure', $failing);
        }
        return ['lines' => $lines, 'alerts' => $alerts, 'hasalerts' => (bool) $alerts,
            'tasksurl' => (new \moodle_url('/admin/tool/task/scheduledtasks.php'))->out(false)];
    }

    /**
     * A course of the download of the section, in the order of its columns: course, category, score, blocking, serious,
     * minor mistakes, and when it was read.
     *
     * @param \stdClass|null $record Course with its score ({@see self::latest()}); null for the names of the columns.
     * @return array
     */
    public static function download_row(?\stdClass $record): array {
        $keys = ['course', 'category', 'score', 'blocking', 'serious', 'minor', 'read'];
        if (!$record) {
            return array_fill_keys($keys, '');
        }
        $category = \core_course_category::get((int) $record->category, IGNORE_MISSING, true);
        return array_combine($keys, [
            format_string($record->fullname, true, ['context' => \context_course::instance($record->courseid)]),
            $category ? $category->get_nested_name(false) : '',
            (int) $record->score,
            (int) $record->blocking,
            (int) $record->serious,
            (int) $record->minor,
            userdate((int) $record->timecomputed, get_string('strftimedatetimeshort', 'core_langconfig')),
        ]);
    }

    /**
     * The choice of the format of the download of the section, for its scope.
     *
     * @param int $categoryid Category, 0 for all.
     * @param int $companyid IOMAD company, 0 for the whole platform.
     * @return string HTML.
     */
    protected static function download_selector(int $categoryid, int $companyid): string {
        global $PAGE;
        return $PAGE->get_renderer('core')->download_dataformat_selector(
            get_string('a11yscores_download', 'theme_epure'),
            new \moodle_url('/theme/epure/a11yscores_download.php'),
            'dataformat',
            array_filter(['categoryid' => $categoryid, 'companyid' => $companyid])
        );
    }

    /**
     * The choices of the list of the categories: all, then each category by its path.
     *
     * @param int $categoryid Category chosen, 0 for all.
     * @return array[] Each with value, name and selected.
     */
    public static function category_choices(int $categoryid): array {
        $choices = [['value' => 0, 'name' => get_string('a11yscores_allcategories', 'theme_epure'),
            'selected' => $categoryid === 0]];
        foreach (\core_course_category::make_categories_list() as $id => $name) {
            $choices[] = ['value' => (int) $id, 'name' => $name, 'selected' => (int) $id === $categoryid];
        }
        return $choices;
    }
}
