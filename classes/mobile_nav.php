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
 * Navigation bar at the bottom of the screen on phones: dashboard, my courses, catalogue, messages and profile.
 *
 * Shown or hidden for the whole site, and with IOMAD for each company.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile_nav {
    /** @var string[] Layouts without the bar: pages outside the site's navigation. */
    protected const NO_BAR_LAYOUTS = ['login', 'maintenance', 'embedded', 'popup', 'frametop', 'print', 'redirect', 'secure'];

    /**
     * Whether a page gets the bar.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        return isloggedin() && !isguestuser() && !during_initial_install()
            && !in_array($page->pagelayout, self::NO_BAR_LAYOUTS, true) && company_style::mobile_nav();
    }

    /**
     * Context of the template theme_epure/mobile_nav.
     *
     * @param \moodle_page $page Page.
     * @return array
     */
    public static function export(\moodle_page $page): array {
        global $CFG, $USER;
        $pagetype = (string) $page->pagetype;
        $items = [];
        $add = function (string $key, string $label, string $url, string $icon, bool $active, int $count = 0) use (&$items) {
            $items[] = [
                'key' => $key,
                'label' => $label,
                'url' => (new \moodle_url($url))->out(false),
                'icon' => $icon,
                'active' => $active,
                'count' => $count,
                'hascount' => $count > 0,
                'countlabel' => $count > 0 ? get_string('mobilenav_unread', 'theme_epure', $count) : '',
            ];
        };

        if (empty($CFG->enabledashboard) && isset($CFG->enabledashboard)) {
            $add('home', get_string('sitehome'), '/?redirect=0', 'fa-house', $pagetype === 'site-index');
        } else {
            $add('home', get_string('myhome'), '/my/', 'fa-gauge', $pagetype === 'my-index');
        }
        $add('courses', get_string('mycourses'), '/my/courses.php', 'fa-graduation-cap', $pagetype === 'my-courses');
        $add(
            'catalogue',
            get_string('catalogue_short', 'theme_epure'),
            '/course/index.php',
            'fa-compass',
            str_starts_with($pagetype, 'course-index') || $pagetype === 'course-search' || $pagetype === 'enrol-index'
        );
        if (!empty($CFG->messaging)) {
            $unread = 0;
            try {
                $unread = (int) \core_message\api::count_unread_conversations($USER);
            } catch (\Throwable $e) {
                debugging('Épure: could not count the unread conversations: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
            $add(
                'messages',
                get_string('messages', 'message'),
                '/message/index.php',
                'fa-comment',
                str_starts_with($pagetype, 'message-'),
                $unread
            );
        }
        $add(
            'profile',
            get_string('profile'),
            '/user/profile.php',
            'fa-user',
            $pagetype === 'user-profile' || str_starts_with($pagetype, 'user-preferences')
        );
        return ['items' => $items];
    }
}
