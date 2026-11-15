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
 * The activities added to a course since the previous visit of the user: a badge « New » beside their name on the
 * page of the course, and their number on the card of the course.
 *
 * The previous visit is the last access to the course before the current session, which Moodle reads at the login
 * ($USER->lastcourseaccess, from the table user_lastaccess): the same on each device, nothing kept in the browser,
 * no table of the theme. The badges stay for the whole session. A course never visited before shows none: all would be
 * new. Only the activities the user can open (not the text and media areas), visible on the page of the course.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class new_activities {
    /**
     * The previous visit of the user to a course, before the current session: null if none.
     *
     * @param int $courseid Course.
     * @return int|null
     */
    public static function since(int $courseid): ?int {
        global $USER;
        $time = (int) ($USER->lastcourseaccess[$courseid] ?? 0);
        return $time > 0 ? $time : null;
    }

    /**
     * The activities added to a course since a time, that the user can open, in the order of the course.
     *
     * @param \stdClass $course Course.
     * @param int|null $since Time, null for none (no activity is new).
     * @param int|null $userid User, the current one by default.
     * @return int[] Their modules.
     */
    public static function cmids(\stdClass $course, ?int $since, ?int $userid = null): array {
        if ($since === null) {
            return [];
        }
        $cmids = [];
        foreach (get_fast_modinfo($course, (int) $userid)->get_cms() as $cm) {
            if (
                (int) $cm->added > $since && !$cm->deletioninprogress && $cm->uservisible && $cm->url
                    && $cm->is_visible_on_course_page()
            ) {
                $cmids[] = (int) $cm->id;
            }
        }
        return $cmids;
    }

    /**
     * The activities added to a course since the previous visit of the current user.
     *
     * @param \stdClass $course Course.
     * @return int[] Their modules.
     */
    public static function course(\stdClass $course): array {
        return self::cmids($course, self::since((int) $course->id));
    }

    /**
     * The text of the number of new activities of a course, for its card: null when there is none.
     *
     * @param int $count Number.
     * @return string|null
     */
    public static function count_text(int $count): ?string {
        if ($count < 1) {
            return null;
        }
        return $count === 1 ? get_string('new_one', 'theme_epure') : get_string('new_many', 'theme_epure', $count);
    }
}
