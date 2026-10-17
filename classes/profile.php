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

use core_user\output\myprofile\tree;

/**
 * The profile with Épure: on the own profile of a user, a summary at the top (their courses, badges and
 * certificates as figures, their previous login, « Edit profile » in sight); on every profile, the cards that matter
 * first and the others under « More information », none removed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile {
    /** @var string[] Categories of the profile shown first; the others are grouped under « More information ». */
    public const PRIMARY = ['contact', 'epureachievements', 'badges', 'coursedetails', 'administration'];

    /** @var int|null User whose own profile is being shown, when the summary applies. */
    protected static ?int $own = null;

    /**
     * Notes the own profile of the user, with Épure, from the navigation of the profile.
     *
     * @param tree $tree Profile.
     * @param \stdClass $user Owner of the profile.
     * @param bool $iscurrentuser Whether it is the profile of the current user.
     * @param \stdClass|null $course Course of the profile, none for the profile of the site.
     */
    public static function myprofile(tree $tree, \stdClass $user, bool $iscurrentuser, ?\stdClass $course): void {
        self::$own = $iscurrentuser && !$course && !isguestuser($user) && hook_callbacks::epure_page() ? (int) $user->id : null;
    }

    /**
     * Context of the template theme_epure/profile_summary, on the own profile of the user.
     *
     * @param \renderer_base $output Renderer.
     * @param \moodle_url|null $editurl Address to edit the profile, when the user can.
     * @return array|null Null on the profile of another user, or of a course.
     */
    public static function summary(\renderer_base $output, ?\moodle_url $editurl): ?array {
        global $CFG, $USER;
        if (!self::$own || self::$own !== (int) $USER->id) {
            return null;
        }
        $userid = self::$own;
        $figures = [];
        $figure = fn(int $value, string $label, string $url) => ['value' => $value,
            'label' => get_string($label, 'theme_epure'), 'url' => (new \moodle_url($url))->out(false)];

        $learning = [];
        $teaching = [];
        foreach (mycourses::export($output, $userid)['sections'] as $section) {
            if ($section['role'] === mycourses::LEARNING) {
                $learning = $section['courses'];
            } else if ($section['role'] === mycourses::TEACHING) {
                $teaching = $section['courses'];
            }
        }
        if ($learning) {
            $completed = count(array_filter($learning, fn($card) => !empty($card['completed'])));
            $figures[] = $figure(count($learning) - $completed, 'profile_inprogress', '/my/courses.php');
            $figures[] = $figure($completed, 'profile_completed', '/my/courses.php');
        }
        if ($teaching) {
            $figures[] = $figure(count($teaching), 'profile_teaching', '/my/courses.php');
        }
        if (!empty($CFG->enablebadges)) {
            $figures[] = $figure(achievements::badges($userid)['total'], 'profile_badges', '/badges/mybadges.php');
        }
        $certificatesurl = learner_dashboard::certificates_url($userid);
        $certificates = count(achievements::certificates($userid, 0));
        if ($certificatesurl || $certificates) {
            $figures[] = ['value' => $certificates, 'label' => get_string('profile_certificates', 'theme_epure'),
                'url' => $certificatesurl];
        }

        // The login before this one: the current one is now.
        $lastlogin = (int) ($USER->lastlogin ?? 0);
        return [
            'figures' => $figures,
            'hasfigures' => !empty($figures),
            'lastlogin' => $lastlogin ? get_string('profile_lastlogin', 'theme_epure', userdate($lastlogin)) : '',
            'editurl' => $editurl ? $editurl->out(false) : null,
        ];
    }

    /**
     * Forgets the own profile (for tests).
     */
    public static function reset(): void {
        self::$own = null;
    }
}
