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
 * State of an activity for a learner, the same on the course page, in the course index and on the dashboard.
 *
 * Moodle tells « To do » or « Done ». The learner also needs to know what is late, what is due soon, what they started,
 * what they passed or failed, what is locked and what is optional: one state per activity, with its label, icon and
 * colour, in this order of importance.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_state {
    /** @var array<string, string> Icon of each state (Font Awesome), as in the course index of Moodle for to do and done. */
    public const ICONS = [
        'locked' => 'fa fa-lock',
        'late' => 'fa fa-triangle-exclamation',
        'soon' => 'fa-regular fa-clock',
        'failed' => 'fa fa-circle-xmark',
        'passed' => 'fa fa-circle-check',
        'done' => 'fa fa-circle-check',
        'progress' => 'fa fa-circle-half-stroke',
        'todo' => 'fa-regular fa-circle',
        'optional' => 'fa fa-minus',
    ];

    /** @var int How long before its deadline an activity is due soon, in seconds. */
    public const SOON = 2 * DAYSECS;

    /** @var string[] Dates of the activities (see \core\activity_dates) after which they are late. */
    protected const DEADLINES = ['duedate', 'deadline', 'timeclose', 'timeavailableto', 'submissionend', 'assessmentend'];

    /**
     * State of an activity, without its deadline.
     *
     * @param \cm_info $cm Activity, as seen by the learner.
     * @param \completion_info $completion Completion of the course.
     * @param int $userid Learner.
     * @return string|null Key of ICONS, or null for an activity that shows no state (hidden, label, no completion).
     */
    public static function base(\cm_info $cm, \completion_info $completion, int $userid): ?string {
        if ($cm->deletioninprogress || !$cm->url || !$completion->is_enabled()) {
            return null;
        }
        if (!$cm->uservisible) {
            return $cm->is_visible_on_course_page() && $cm->availableinfo ? 'locked' : null;
        }
        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
            return 'optional';
        }
        $state = (int) $completion->get_data($cm, false, $userid)->completionstate;
        if ($state === COMPLETION_COMPLETE_FAIL) {
            return 'failed';
        }
        if ($state === COMPLETION_COMPLETE_PASS) {
            return 'passed';
        }
        if ($state === COMPLETION_COMPLETE) {
            return 'done';
        }
        // Started: one of its conditions is met (viewed, for example), not all.
        $details = \core_completion\cm_completion_details::get_instance($cm, $userid)->get_details();
        foreach ($details as $detail) {
            if (in_array((int) ($detail->status ?? 0), [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
                return 'progress';
            }
        }
        return 'todo';
    }

    /**
     * Deadline of an activity for the learner: its first closing date (due date, closing of a quiz…), else the date
     * its completion is expected.
     *
     * @param \cm_info $cm Activity.
     * @param int $userid Learner.
     * @return int|null Timestamp.
     */
    public static function deadline(\cm_info $cm, int $userid): ?int {
        $deadline = null;
        try {
            foreach (\core\activity_dates::get_dates_for_module($cm, $userid) as $date) {
                if (in_array($date['dataid'] ?? '', self::DEADLINES, true) && !empty($date['timestamp'])) {
                    $deadline = min($deadline ?? PHP_INT_MAX, (int) $date['timestamp']);
                }
            }
        } catch (\Throwable $e) {
            debugging('Épure could not read the dates of an activity: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return $deadline ?? ($cm->completionexpected ? (int) $cm->completionexpected : null);
    }

    /**
     * State at a time: an activity to do or started is late after its deadline, due soon before.
     *
     * @param string $base State without the deadline.
     * @param int|null $deadline Deadline.
     * @param int|null $now Time, now by default.
     * @return string
     */
    public static function at(string $base, ?int $deadline, ?int $now = null): string {
        $now = $now ?? time();
        if (!$deadline || !in_array($base, ['todo', 'progress'], true)) {
            return $base;
        }
        if ($deadline < $now) {
            return 'late';
        }
        return $deadline - $now <= self::SOON ? 'soon' : $base;
    }

    /**
     * Whether a state tells more than Moodle does: to do, done and locked are already shown by its completion button,
     * its availability and its course index.
     *
     * @param string $state Key of ICONS.
     * @return bool
     */
    public static function notable(string $state): bool {
        return !in_array($state, ['todo', 'done', 'locked'], true);
    }

    /**
     * Label, icon and text of a state, for the templates and the scripts.
     *
     * @param string $state Key of ICONS.
     * @param int|null $deadline Deadline, shown with the states that depend on it.
     * @return array With state, label, icon and text (the label, with the date when it matters).
     */
    public static function export(string $state, ?int $deadline = null): array {
        $label = match ($state) {
            'todo' => get_string('todo', 'completion'),
            'done' => get_string('done', 'completion'),
            'locked' => get_string('locked', 'course'),
            default => get_string('state_' . $state, 'theme_epure'),
        };
        $text = $label;
        if ($deadline && in_array($state, ['late', 'soon'], true)) {
            $text = get_string('state_withdate', 'theme_epure', (object) ['state' => $label,
                'date' => userdate($deadline, get_string('strftimedateshort', 'langconfig'))]);
        }
        return ['state' => $state, 'label' => $label, 'icon' => self::ICONS[$state], 'text' => $text,
            'notable' => self::notable($state)];
    }

    /**
     * All the states, without dates, for a page whose activities change state while it is shown.
     *
     * @return array[] Keyed by state, with the keys of export().
     */
    public static function all(): array {
        $states = [];
        foreach (array_keys(self::ICONS) as $state) {
            $states[$state] = self::export($state);
        }
        return $states;
    }

    /** @var string[] States of an activity still to do: the first one of the course is the activity to continue. */
    public const PENDING = ['late', 'soon', 'failed', 'progress', 'todo'];

    /**
     * The activities of a course that show a state for the learner, in the order of the course page (subsections at
     * their place).
     *
     * @param \stdClass $course Course.
     * @param int $userid Learner.
     * @return array[] Each with cm, base (state without the deadline), due (deadline of an activity to do or started)
     *     and sections (ids of its section and subsection).
     */
    public static function items(\stdClass $course, int $userid): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return [];
        }
        $items = [];
        foreach (course_structure::cms(get_fast_modinfo($course, $userid)) as $cm) {
            if (!$base = self::base($cm, $completion, $userid)) {
                continue;
            }
            $sections = [];
            foreach (course_structure::sections_of($cm) as $section) {
                if ($section) {
                    $sections[] = (int) $section->id;
                }
            }
            $due = in_array($base, ['todo', 'progress'], true) ? self::deadline($cm, $userid) : null;
            $items[] = ['cm' => $cm, 'base' => $base, 'due' => $due, 'sections' => $sections];
        }
        return $items;
    }

    /**
     * The activity to continue, the same on the dashboard, the banner of the course and its page: an activity due in
     * the next two days comes first (the closest deadline); else the activity started and not finished; else the one
     * after the last activity completed; else the first one still to do.
     *
     * @param array[] $items Activities, from items().
     * @param int|null $now Time, now by default.
     * @return int|null Key of the activity in $items, or null when nothing is left to do.
     */
    public static function next(array $items, ?int $now = null): ?int {
        $now = $now ?? time();
        $pending = array_filter($items, fn($item) => in_array($item['base'], ['todo', 'progress', 'failed'], true));
        if (!$pending) {
            return null;
        }
        $soon = array_filter($pending, fn($item) => $item['due'] && $item['due'] >= $now && $item['due'] - $now <= self::SOON);
        if ($soon) {
            uasort($soon, fn($a, $b) => $a['due'] <=> $b['due']);
            return array_key_first($soon);
        }
        foreach ($pending as $key => $item) {
            if ($item['base'] === 'progress') {
                return $key;
            }
        }
        $last = null;
        foreach ($items as $key => $item) {
            if (in_array($item['base'], ['done', 'passed'], true)) {
                $last = $key;
            }
        }
        foreach ($pending as $key => $item) {
            if ($last === null || $key > $last) {
                return $key;
            }
        }
        return array_key_first($pending);
    }

    /**
     * What is left to do in a course: the number of activities still to do, and the closest deadline to come.
     *
     * @param array[] $items Activities, from items().
     * @param int|null $now Time, now by default.
     * @return array With remaining and due (timestamp or null).
     */
    public static function remaining(array $items, ?int $now = null): array {
        $now = $now ?? time();
        $remaining = 0;
        $due = null;
        foreach ($items as $item) {
            if (!in_array($item['base'], ['todo', 'progress', 'failed'], true)) {
                continue;
            }
            $remaining++;
            if ($item['due'] && $item['due'] >= $now) {
                $due = min($due ?? PHP_INT_MAX, $item['due']);
            }
        }
        return ['remaining' => $remaining, 'due' => $due];
    }

    /**
     * Sentence of what is left to do: « 3 activities left · due tomorrow ».
     *
     * @param int $remaining Activities still to do.
     * @param int|null $due Closest deadline to come.
     * @param int|null $now Time, now by default.
     * @return string Empty when nothing is left.
     */
    public static function summary(int $remaining, ?int $due, ?int $now = null): string {
        if (!$remaining) {
            return '';
        }
        $now = $now ?? time();
        $parts = [$remaining === 1 ? get_string('continue_remaining_one', 'theme_epure')
            : get_string('continue_remaining', 'theme_epure', $remaining)];
        if ($due && $due >= $now) {
            $days = (int) round((usergetmidnight($due) - usergetmidnight($now)) / DAYSECS);
            $date = userdate($due, get_string('strftimedateshort', 'langconfig'));
            $parts[] = match (true) {
                $days <= 0 => get_string('continue_duetoday', 'theme_epure'),
                $days === 1 => get_string('continue_duetomorrow', 'theme_epure'),
                $days <= 7 => get_string('continue_duedays', 'theme_epure', $days),
                default => get_string('continue_duedate', 'theme_epure', $date),
            };
        }
        return implode(' · ', $parts);
    }

    /**
     * Detail of the progress of a course: « 8 completed · 2 in progress · 1 overdue · 1 not passed ».
     *
     * @param int $completed Activities completed.
     * @param array[] $pending Activities still to do, each as [base state, deadline].
     * @param int|null $now Time, now by default.
     * @return string Empty when nothing is completed nor started.
     */
    public static function detail(int $completed, array $pending, ?int $now = null): string {
        $counts = ['done' => $completed, 'progress' => 0, 'late' => 0, 'failed' => 0];
        foreach ($pending as [$base, $due]) {
            // Due soon is still in progress, or to do: only late changes the count.
            $state = self::at($base, $due, $now) === 'late' ? 'late' : $base;
            if (isset($counts[$state])) {
                $counts[$state]++;
            }
        }
        if (!$counts['done'] && !$counts['progress'] && !$counts['late'] && !$counts['failed']) {
            return '';
        }
        $parts = [];
        foreach ($counts as $state => $count) {
            if ($count) {
                $parts[] = get_string('detail_' . $state . ($count === 1 ? '_one' : ''), 'theme_epure', $count);
            }
        }
        return implode(' · ', $parts);
    }

    /**
     * States of the activities of a course, for its page, in the order of the page (subsections at their place).
     *
     * @param array[] $items Activities, from items().
     * @return array[] Each with cmid, sections (ids of its section and subsection) and the keys of export().
     */
    public static function course(array $items): array {
        $states = [];
        foreach ($items as $item) {
            $states[] = ['cmid' => (int) $item['cm']->id, 'sections' => $item['sections']]
                + self::export(self::at($item['base'], $item['due']), $item['due']);
        }
        return $states;
    }
}
