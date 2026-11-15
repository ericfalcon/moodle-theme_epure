<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * The page « To do » of a learner: the activities still to do with a deadline, in all the courses they take, grouped by
 * deadline (overdue, this week, later), filtered by course and by period. It goes on from « This week » of the
 * dashboard ({@see learner_dashboard::week()}), with the same activities and their state ({@see activity_state}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todo {
    /** @var string[] The groups by deadline, in their order. */
    public const PERIODS = ['late', 'week', 'later'];

    /** @var string[] The states of an activity still to do. */
    protected const PENDING = ['todo', 'progress', 'failed'];

    /**
     * Address of the page.
     *
     * @param array $params Filters: course, period.
     * @return \moodle_url
     */
    public static function url(array $params = []): \moodle_url {
        return new \moodle_url('/theme/epure/todo.php', array_filter($params));
    }

    /**
     * The activities still to do with a deadline, in the courses the user takes, the closest deadline first.
     *
     * @param \renderer_base $output Renderer.
     * @param int $userid User.
     * @param int|null $now Time, now by default.
     * @return array[] Each with cmid, name, url, icon, course, courseid, due, base, state and period.
     */
    public static function items(\renderer_base $output, int $userid, ?int $now = null): array {
        $now = $now ?? time();
        $week = usergetmidnight($now) + 7 * DAYSECS;
        $items = [];
        foreach (mycourses::export($output, $userid)['sections'] as $section) {
            if ($section['role'] !== mycourses::LEARNING) {
                continue;
            }
            foreach ($section['courses'] as $card) {
                foreach ($card['dated'] ?? [] as $item) {
                    if (!in_array($item['base'], self::PENDING, true)) {
                        continue;
                    }
                    $due = (int) $item['due'];
                    $items[] = $item + [
                        'course' => $card['name'],
                        'courseid' => (int) $card['id'],
                        'state' => activity_state::at($item['base'], $due, $now),
                        'period' => $due < $now ? 'late' : ($due < $week ? 'week' : 'later'),
                    ];
                }
            }
        }
        usort($items, fn($a, $b) => $a['due'] <=> $b['due']);
        return $items;
    }

    /**
     * Context of the template theme_epure/todo.
     *
     * @param \renderer_base $output Renderer.
     * @param int $userid User.
     * @param int $courseid Filter: a course, 0 for all.
     * @param string $period Filter: a period of {@see PERIODS}, empty for all.
     * @param int|null $now Time, now by default.
     * @return array
     */
    public static function export(
        \renderer_base $output,
        int $userid,
        int $courseid = 0,
        string $period = '',
        ?int $now = null
    ): array {
        $all = self::items($output, $userid, $now);
        $period = in_array($period, self::PERIODS, true) ? $period : '';

        // The courses of the filter: those with an activity to do, in the order of « My courses ».
        $courses = [['value' => 0, 'label' => get_string('todo_allcourses', 'theme_epure'), 'selected' => $courseid === 0]];
        foreach (array_unique(array_column($all, 'course', 'courseid')) as $id => $name) {
            $courses[] = ['value' => $id, 'label' => $name, 'selected' => $id === $courseid];
        }
        $periods = [['value' => '', 'label' => get_string('todo_allperiods', 'theme_epure'), 'selected' => $period === '']];
        foreach (self::PERIODS as $key) {
            $periods[] = ['value' => $key, 'label' => get_string('todo_' . $key, 'theme_epure'), 'selected' => $key === $period];
        }

        $shown = array_filter($all, fn($item) => (!$courseid || $item['courseid'] === $courseid)
            && ($period === '' || $item['period'] === $period));
        $groups = [];
        foreach (self::PERIODS as $key) {
            $items = array_values(array_filter($shown, fn($item) => $item['period'] === $key));
            if ($items) {
                $groups[] = [
                    'key' => $key,
                    'title' => get_string('todo_' . $key, 'theme_epure'),
                    'count' => count($items),
                    'items' => array_map(fn($item) => self::export_item($item), $items),
                ];
            }
        }
        $count = count($shown);
        return [
            'url' => self::url()->out(false),
            'courses' => $courses,
            'periods' => $periods,
            'filtered' => $courseid || $period !== '',
            'groups' => $groups,
            'hasitems' => $count > 0,
            'count' => $count === 1 ? get_string('todo_count_one', 'theme_epure') : get_string('todo_count', 'theme_epure', $count),
            'empty' => get_string($all ? 'todo_emptyfilter' : 'todo_empty', 'theme_epure'),
            'reseturl' => self::url()->out(false),
            'dashboardurl' => (new \moodle_url('/my/'))->out(false),
            'calendarurl' => (new \moodle_url('/calendar/view.php', ['view' => 'upcoming']))->out(false),
        ];
    }

    /**
     * An activity to do, for the template theme_epure/learner_week_item: its deadline as a date, and its state, except
     * to do (the deadline already tells it) and overdue (under its heading).
     *
     * @param array $item Activity, from {@see items()}.
     * @return array
     */
    protected static function export_item(array $item): array {
        $state = $item['state'];
        $export = in_array($state, ['todo', 'late'], true) ? null : activity_state::export($state, $item['due']);
        return [
            'name' => $item['name'],
            'course' => $item['course'],
            'url' => $item['url'],
            'icon' => $item['icon'],
            'faicon' => null,
            'time' => userdate($item['due'], get_string('strftimedatetimeshort', 'langconfig')),
            'state' => $export ? ['state' => $state, 'icon' => $export['icon'], 'text' => $export['label']] : null,
        ];
    }
}
