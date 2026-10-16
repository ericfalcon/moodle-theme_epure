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
 * the other courses in progress, the week in all the courses (deadlines and sessions), and the courses completed,
 * with a link to the certificates when the platform issues some (IOMAD, Certificate, Custom certificate).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_dashboard {
    /** @var int Courses in progress shown besides the one to resume. */
    public const INPROGRESS = 4;

    /** @var int Items of the week shown. */
    public const WEEK = 12;

    /** @var int Activities overdue shown. */
    public const LATE = 5;

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

        // The course to resume: the one whose activity to continue is due the soonest, in the next two days (with
        // deadlines in several courses, the others show theirs in the list of the courses in progress); else the last
        // one visited, else the first one in the order of « My courses ».
        $resume = null;
        if ($inprogress) {
            $visited = $inprogress;
            usort($visited, fn($a, $b) => $b['lastaccess'] <=> $a['lastaccess']);
            $urgent = array_filter($visited, fn($card) => !empty($card['urgent']));
            usort($urgent, fn($a, $b) => $a['urgent'] <=> $b['urgent']);
            $resume = $urgent[0] ?? $visited[0];
            // Brought up by its deadline rather than by the last visit: « Next deadline » rather than « Pick up where
            // you left off ».
            $heading = $resume['id'] === $visited[0]['id'] ? 'learnerresume' : 'learnerresumedeadline';
            $resume['heading'] = get_string($heading, 'theme_epure');
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

        $week = self::week($learning, $userid);
        return [
            'resume' => $resume,
            'inprogress' => array_slice($inprogress, 0, self::INPROGRESS),
            'hasinprogress' => !empty($inprogress),
            'moreinprogress' => count($inprogress) > self::INPROGRESS,
            'week' => $week,
            'completed' => array_slice($completed, 0, self::COMPLETED),
            'completedcount' => count($completed),
            'hascompleted' => !empty($completed),
            'achievements' => achievements::export($userid, self::certificates_url($userid)),
            'mycoursesurl' => (new \moodle_url('/my/courses.php'))->out(false),
            'catalogueurl' => (new \moodle_url('/course/index.php'))->out(false),
            'calendarurl' => (new \moodle_url('/calendar/view.php', ['view' => 'upcoming']))->out(false),
        ];
    }

    /**
     * The week of the user, from today to the same day next week, in all the courses they take: the deadlines of the
     * activities with their state ({@see activity_state}), the other events of the calendar (sessions of the courses,
     * of their groups, their own events), grouped by day; and before them the activities overdue, still to do.
     *
     * @param array[] $learning Cards of the courses the user takes, from mycourses::export().
     * @param int $userid User.
     * @param int|null $now Time, now by default.
     * @return array With days (each with label and items), late (items), haslate and hasweek.
     */
    public static function week(array $learning, int $userid, ?int $now = null): array {
        $now = $now ?? time();
        $today = usergetmidnight($now);
        $end = $today + 7 * DAYSECS;
        $entries = [];
        $late = [];
        $finished = [];
        foreach ($learning as $card) {
            foreach ($card['finished'] ?? [] as $cmid => $base) {
                $finished[$cmid] = $base;
            }
            foreach ($card['dated'] ?? [] as $item) {
                $state = activity_state::at($item['base'], $item['due'], $now);
                $entry = ['cmid' => $item['cmid'], 'name' => $item['name'], 'course' => $card['name'], 'url' => $item['url'],
                    'icon' => $item['icon'], 'time' => $item['due'], 'state' => $state];
                if ($state === 'late') {
                    $late[] = $entry;
                } else if ($item['due'] < $end) {
                    $entries[$item['cmid'] . '_' . $item['due']] = $entry;
                }
            }
        }
        $names = array_column($learning, 'name', 'id');
        foreach (self::events($userid, array_keys($names), $today, $end) as $event) {
            $key = $event['cmid'] . '_' . $event['time'];
            if ($event['cmid'] && isset($entries[$key])) {
                // The deadline of an activity left to do, with its name in the calendar (« Report is due »).
                $entries[$key]['name'] = $event['name'];
                continue;
            }
            // An event that has ended, or the deadline of an activity left to do, already overdue.
            if (($event['timeend'] < $now && !isset($finished[$event['cmid']])) || $event['time'] >= $end) {
                continue;
            }
            $event['course'] = $names[$event['courseid']] ?? '';
            $event['state'] = $finished[$event['cmid']] ?? null;
            $entries[] = $event;
        }

        usort($entries, fn($a, $b) => $a['time'] <=> $b['time']);
        $entries = array_slice($entries, 0, self::WEEK);
        $days = [];
        foreach ($entries as $entry) {
            $day = usergetmidnight($entry['time']);
            if (!isset($days[$day])) {
                if ($day === $today) {
                    $label = get_string('today', 'calendar');
                } else if ($day === usergetmidnight($today + DAYSECS + 2 * HOURSECS)) {
                    $label = get_string('tomorrow', 'calendar');
                } else {
                    $label = userdate($day, get_string('strftimedayshort', 'langconfig'));
                }
                $days[$day] = ['label' => $label, 'items' => []];
            }
            $days[$day]['items'][] = self::export_entry($entry);
        }
        // The most recently overdue first.
        usort($late, fn($a, $b) => $b['time'] <=> $a['time']);
        $late = array_map(fn($entry) => self::export_entry($entry, true), array_slice($late, 0, self::LATE));
        return ['days' => array_values($days), 'late' => $late, 'haslate' => !empty($late),
            'hasweek' => !empty($days) || !empty($late)];
    }

    /**
     * An item of the week, for the template.
     *
     * @param array $entry Item, with name, course, url, icon, time and state.
     * @param bool $withdate Whether its date is shown, for the activities overdue.
     * @return array
     */
    protected static function export_entry(array $entry, bool $withdate = false): array {
        $state = $entry['state'] ?? null;
        $export = $state ? activity_state::export($state) : null;
        return [
            'name' => $entry['name'],
            'course' => $entry['course'],
            'url' => $entry['url'],
            'icon' => $entry['icon'],
            'faicon' => $entry['faicon'] ?? null,
            'time' => userdate($entry['time'], get_string($withdate ? 'strftimedatetimeshort' : 'strftimetime', 'langconfig')),
            // The state of an activity, except to do (the time already tells when to do it) and overdue (under its heading).
            'state' => $export && !in_array($state, ['todo', 'late'], true)
                ? ['state' => $state, 'icon' => $export['icon'], 'text' => $export['label']] : null,
        ];
    }

    /**
     * The events of the calendar of the user in a time range, in the courses they take, their groups there, theirs and
     * those of the site. Kept a few minutes in the cache learnerprogress, like the progress of the courses.
     *
     * @param int $userid User.
     * @param int[] $courseids Courses the user takes.
     * @param int $from Start of the range.
     * @param int $to End of the range.
     * @return array[] Each with cmid (0 for an event of no activity), courseid, name, url, icon or faicon, time and timeend.
     */
    protected static function events(int $userid, array $courseids, int $from, int $to): array {
        $cache = \cache::make('theme_epure', 'learnerprogress');
        $key = 'week_' . $userid;
        $version = $from . '_' . implode(',', $courseids) . '_' . current_language();
        $data = $cache->get($key);
        if (!is_array($data) || ($data['version'] ?? null) !== $version) {
            $data = ['version' => $version, 'events' => self::read_events($userid, $courseids, $from, $to)];
            $cache->set($key, $data);
        }
        return $data['events'];
    }

    /**
     * Reads the events of the calendar of the user in a time range.
     *
     * @param int $userid User.
     * @param int[] $courseids Courses the user takes.
     * @param int $from Start of the range.
     * @param int $to End of the range.
     * @return array[] As events().
     */
    protected static function read_events(int $userid, array $courseids, int $from, int $to): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');
        $events = [];
        try {
            $groups = [];
            if ($courseids) {
                [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
                $groups = $DB->get_fieldset_sql(
                    "SELECT g.id FROM {groups} g JOIN {groups_members} gm ON gm.groupid = g.id
                      WHERE gm.userid = :userid AND g.courseid $insql",
                    $params + ['userid' => $userid]
                );
            }
            $records = calendar_get_events($from, $to - 1, [$userid], $groups ?: false, array_merge([SITEID], $courseids));
            foreach ($records as $record) {
                $cmid = 0;
                $url = null;
                $icon = null;
                if (!empty($record->modulename) && !empty($record->instance) && $record->courseid) {
                    $cm = get_fast_modinfo($record->courseid, $userid)->get_instances_of($record->modulename)[$record->instance]
                        ?? null;
                    if (!$cm || !$cm->uservisible) {
                        continue;
                    }
                    $cmid = (int) $cm->id;
                    $url = $cm->url ? $cm->url->out(false) : null;
                    $icon = $cm->get_icon_url()->out(false);
                } else {
                    $url = (new \moodle_url('/calendar/view.php', ['view' => 'day', 'time' => $record->timestart]))->out(false)
                        . '#event_' . $record->id;
                }
                $context = $record->courseid ? \context_course::instance($record->courseid, IGNORE_MISSING) : null;
                $events[] = [
                    'cmid' => $cmid,
                    'courseid' => (int) $record->courseid,
                    'name' => format_string($record->name, true, ['context' => $context ?: \context_system::instance()]),
                    'url' => $url,
                    'icon' => $icon,
                    'faicon' => $icon ? null : 'fa-regular fa-calendar',
                    'time' => (int) $record->timestart,
                    'timeend' => (int) $record->timestart + (int) $record->timeduration,
                ];
            }
        } catch (\Throwable $e) {
            debugging('Épure: could not read the events of the week: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return $events;
    }

    /**
     * Address of the certificates of the user, when the platform issued them some.
     *
     * @param int $userid User.
     * @return string|null
     */
    public static function certificates_url(int $userid): ?string {
        global $DB;
        // IOMAD: certificates of the completions tracked by the company.
        if (
            iomad::installed() && ($company = company_style::current_company())
                && iomad::has_capability('block/iomad_company_admin:downloadmycertificates', \context_system::instance())
                && $DB->record_exists_sql(
                    'SELECT 1 FROM {' . iomad::table('local_iomad_track') . '} lit
                       JOIN {' . iomad::table('local_iomad_track_certs') . '} litc ON lit.id = litc.trackid
                      WHERE lit.userid = :userid AND lit.companyid = :companyid',
                    ['userid' => $userid, 'companyid' => $company->id]
                )
        ) {
            return iomad::url('completionreport', ['certusers' => $userid, 'action' => 'downloadcerts', 'sesskey' => sesskey()])
                ->out(false);
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
