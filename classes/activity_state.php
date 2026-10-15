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
     * States of the activities of a course, for its page, in the order of the page (subsections at their place).
     *
     * @param \stdClass $course Course.
     * @param int $userid Learner.
     * @return array[] Each with cmid, sections (ids of its section and subsection) and the keys of export().
     */
    public static function course(\stdClass $course, int $userid): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return [];
        }
        $states = [];
        foreach (course_structure::cms(get_fast_modinfo($course, $userid)) as $cm) {
            if (!$base = self::base($cm, $completion, $userid)) {
                continue;
            }
            $deadline = in_array($base, ['todo', 'progress'], true) ? self::deadline($cm, $userid) : null;
            $state = self::at($base, $deadline);
            $sections = [];
            foreach (course_structure::sections_of($cm) as $section) {
                if ($section) {
                    $sections[] = (int) $section->id;
                }
            }
            $states[] = ['cmid' => (int) $cm->id, 'sections' => $sections] + self::export($state, $deadline);
        }
        return $states;
    }
}
