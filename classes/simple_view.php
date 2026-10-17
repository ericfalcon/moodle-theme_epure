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
 * Simplified view of the learner: for whoever manages no activities, the navigation of the courses and of the
 * activities reduced to their main pages (the others under « More »), a shorter user menu, the drawer of the blocks
 * closed when a page opens and a discreet help button. Everything is grouped, nothing removed: every page keeps its
 * address and stays reachable with the keyboard.
 *
 * Also, with Épure, whatever the view: the badges of a course are in its navigation only when it has some, or for
 * whoever can create them.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simple_view {
    /** @var string[] Pages of a course kept in sight in its navigation. */
    public const COURSE_NODES = ['coursehome', 'grades', 'badgesview'];

    /** @var string[] Pages of a course kept in sight for an activity: the activity itself. */
    public const MODULE_NODES = ['modulepage'];

    /** @var string[] Addresses of the standard items of the user menu that go: calendar, private files, reports. */
    public const USER_MENU_GONE = ['/calendar/view.php', '/user/files.php', '/reportbuilder/index.php'];

    /**
     * Whether the simplified view applies to the current user on a page.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        global $USER;
        if (company_style::choice('simpleview') !== 'on' || !isloggedin() || isguestuser() || is_siteadmin()) {
            return false;
        }
        // In a course, what the user can do there; elsewhere, whether they manage activities in any course.
        $coursecontext = $page->context->get_course_context(false);
        if ($coursecontext && $coursecontext->instanceid != SITEID) {
            return !has_capability('moodle/course:manageactivities', $coursecontext);
        }
        return !self::manages_activities((int) $USER->id);
    }

    /**
     * Whether a user manages activities in at least one course.
     *
     * @param int $userid User.
     * @return bool
     */
    protected static function manages_activities(int $userid): bool {
        static $manages = [];
        if (!isset($manages[$userid])) {
            $manages[$userid] = (bool) get_user_capability_course('moodle/course:manageactivities', $userid, false, '', '', 1);
        }
        return $manages[$userid];
    }

    /**
     * Arranges the page before its header: the navigation, the user menu, the drawer of the blocks.
     *
     * @param \moodle_page $page Page.
     */
    public static function prepare(\moodle_page $page): void {
        global $CFG, $USER;
        $simple = self::applies($page);
        if ($page->has_secondary_navigation()) {
            self::arrange_secondary($page, $simple);
        }
        if (!$simple) {
            return;
        }
        $CFG->customusermenuitems = self::user_menu_items((string) ($CFG->customusermenuitems ?? ''));
        // Closed when a page opens: the learner opens it when needed (not saved, the preference stays as it is).
        $USER->preference['drawer-open-block'] = '0';
        $page->add_body_class('epure-simpleview');
    }

    /**
     * Secondary navigation: the badges of a course only when it has some; in the simplified view, the pages that
     * are not kept in sight under « More ».
     *
     * @param \moodle_page $page Page.
     * @param bool $simple Whether the simplified view applies.
     */
    protected static function arrange_secondary(\moodle_page $page, bool $simple): void {
        $nav = $page->secondarynav;
        $course = $page->course;
        $badges = $nav->get('badgesview');
        // Not for whoever can create badges there: the page is where they create the first one.
        if (
            $badges && $course && $course->id != SITEID && !self::has_badges((int) $course->id)
                && !has_capability('moodle/badges:createbadge', \context_course::instance($course->id))
        ) {
            $badges->remove();
        }
        if (!$simple) {
            return;
        }
        $kept = $page->cm ? self::MODULE_NODES : self::COURSE_NODES;
        foreach ($nav->children as $child) {
            if (!in_array($child->key, $kept, true)) {
                $child->set_force_into_more_menu(true);
            }
        }
    }

    /**
     * Whether a course has badges its learners can earn: active ones.
     *
     * @param int $courseid Course.
     * @return bool
     */
    public static function has_badges(int $courseid): bool {
        global $CFG, $DB;
        if (empty($CFG->enablebadges)) {
            return false;
        }
        require_once($CFG->libdir . '/badgeslib.php');
        [$insql, $params] = $DB->get_in_or_equal([BADGE_STATUS_ACTIVE, BADGE_STATUS_ACTIVE_LOCKED], SQL_PARAMS_NAMED);
        return $DB->record_exists_select(
            'badge',
            "courseid = :courseid AND type = :type AND status $insql",
            $params + ['courseid' => $courseid, 'type' => BADGE_TYPE_COURSE]
        );
    }

    /**
     * The items of the user menu without the calendar, the private files and the reports; the items added by the
     * administrator stay.
     *
     * @param string $items Items, one per line, as in the setting customusermenuitems.
     * @return string
     */
    public static function user_menu_items(string $items): string {
        $lines = array_filter(preg_split('/\R/', $items), function (string $line): bool {
            $url = trim(explode('|', $line)[1] ?? '');
            foreach (self::USER_MENU_GONE as $gone) {
                if ($url === $gone || str_starts_with($url, $gone . '?')) {
                    return false;
                }
            }
            return true;
        });
        return implode("\n", $lines);
    }
}
