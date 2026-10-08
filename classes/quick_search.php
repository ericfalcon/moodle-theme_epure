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
 * Quick search (Ctrl+K): the courses, the activities and the administration pages matching a query.
 *
 * The links of the menus of the page (primary navigation, user menu, tabs of the course) are searched
 * in the browser; this class searches what is not on the page.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quick_search {
    /** @var int Results of each group. */
    public const LIMIT = 6;

    /** @var int Courses of the user whose activities are searched. */
    public const ACTIVITY_COURSES = 30;

    /**
     * Whether the quick search is offered to the current user.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return get_config('theme_epure', 'quicksearch') !== '0' && isloggedin() && !isguestuser();
    }

    /**
     * Text without case nor accents, to compare.
     *
     * @param string $text Text.
     * @return string
     */
    public static function normalise(string $text): string {
        return \core_text::strtolower(\core_text::specialtoascii(trim($text)));
    }

    /**
     * Whether a text contains every word of the query.
     *
     * @param string $text Text, normalised.
     * @param string[] $words Words of the query, normalised.
     * @return bool
     */
    protected static function matches(string $text, array $words): bool {
        foreach ($words as $word) {
            if (!str_contains($text, $word)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Groups of results for a query.
     *
     * @param string $query Query, two characters or more.
     * @return array[] Groups: key, label and items (name, url, detail, icon or image).
     */
    public static function search(string $query): array {
        $words = array_filter(explode(' ', self::normalise($query)));
        if (\core_text::strlen(implode('', $words)) < 2) {
            return [];
        }
        $groups = [];
        [$mycourses, $activities, $enrolled] = self::my_courses($words);
        $groups[] = ['key' => 'mycourses', 'label' => get_string('mycourses'), 'items' => $mycourses];
        $groups[] = ['key' => 'activities', 'label' => get_string('quicksearch_activities', 'theme_epure'),
            'items' => $activities];
        $groups[] = ['key' => 'catalogue', 'label' => get_string('catalogue', 'theme_epure'),
            'items' => self::catalogue($query, $enrolled)];
        // The administration tree is long to build: from three characters only.
        if (has_capability('moodle/site:config', \context_system::instance()) && \core_text::strlen(implode('', $words)) >= 3) {
            $groups[] = ['key' => 'admin', 'label' => get_string('administrationsite'), 'items' => self::admin($query)];
        }
        // Moodle's global search, whose button the quick search replaces in the header.
        $context = \context_system::instance();
        if (\core_search\manager::is_global_search_enabled() && has_capability('moodle/search:query', $context)) {
            $groups[] = ['key' => 'site', 'label' => get_string('globalsearch', 'search'), 'items' => [[
                'id' => 0,
                'name' => get_string('quicksearch_allsite', 'theme_epure', $query),
                'url' => (new \moodle_url('/search/index.php', ['q' => $query]))->out(false),
                'detail' => '',
                'icon' => 'fa-magnifying-glass',
            ]]];
        }
        return array_values(array_filter($groups, fn($group) => $group['items']));
    }

    /**
     * The courses of the user, and the activities of their courses, matching the words.
     *
     * @param string[] $words Words of the query, normalised.
     * @return array{0: array[], 1: array[], 2: int[]} Courses and activities found, and all the courses of the user.
     */
    protected static function my_courses(array $words): array {
        global $USER;
        $courses = enrol_get_my_courses(['fullname', 'shortname', 'visible']);
        $found = [];
        $activities = [];
        $searched = 0;
        // Without activity icons, a plain icon marks the activities, as the other results.
        $icons = activity_icons::current() !== 'none';
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $name = format_string($course->fullname, true, ['context' => $context]);
            if (
                count($found) < self::LIMIT
                    && self::matches(self::normalise($name . ' ' . $course->shortname), $words)
            ) {
                $found[] = [
                    'id' => (int) $course->id,
                    'name' => $name,
                    'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                    'detail' => format_string($course->shortname, true, ['context' => $context]),
                    'icon' => 'fa-graduation-cap',
                ];
            }
            if (count($activities) >= self::LIMIT || $searched++ >= self::ACTIVITY_COURSES) {
                continue;
            }
            foreach (get_fast_modinfo($course, $USER->id)->get_cms() as $cm) {
                if (!$cm->uservisible || empty($cm->url) || $cm->is_stealth()) {
                    continue;
                }
                $cmname = $cm->get_formatted_name();
                if (self::matches(self::normalise($cmname), $words)) {
                    $activities[] = [
                        'id' => (int) $cm->id,
                        'name' => $cmname,
                        'url' => $cm->url->out(false),
                        'detail' => $name,
                    ] + ($icons ? ['image' => $cm->get_icon_url()->out(false)] : ['icon' => 'fa-file-lines']);
                    if (count($activities) >= self::LIMIT) {
                        break;
                    }
                }
            }
        }
        return [$found, $activities, array_map('intval', array_keys($courses))];
    }

    /**
     * The other courses of the catalogue matching the query, with Moodle's course search.
     *
     * @param string $query Query.
     * @param int[] $exclude Courses of the user, found in their own group.
     * @return array[]
     */
    protected static function catalogue(string $query, array $exclude): array {
        $items = [];
        $courses = \core_course_category::search_courses(['search' => $query], ['limit' => self::LIMIT + count($exclude)]);
        foreach ($courses as $course) {
            if (in_array((int) $course->id, $exclude, true) || count($items) >= self::LIMIT) {
                continue;
            }
            $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
            $items[] = [
                'id' => (int) $course->id,
                'name' => $course->get_formatted_fullname(),
                'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'detail' => $category ? $category->get_formatted_name() : '',
                'icon' => 'fa-compass',
            ];
        }
        if ($items) {
            $items[] = [
                'id' => 0,
                'name' => get_string('quicksearch_allcourses', 'theme_epure', $query),
                'url' => (new \moodle_url('/course/search.php', ['search' => $query]))->out(false),
                'detail' => '',
                'icon' => 'fa-magnifying-glass',
            ];
        }
        return $items;
    }

    /**
     * The pages of the administration whose name or settings match the query, as in Moodle's admin search.
     *
     * @param string $query Query.
     * @return array[]
     */
    protected static function admin(string $query): array {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $query = \core_text::strtolower(trim($query));
        $root = admin_get_root();
        $pages = [];
        foreach ($root->search($query) as $found) {
            $page = $found->page;
            if (!$page->is_hidden() && ($page instanceof \admin_externalpage || $page instanceof \admin_settingpage)) {
                $pages[] = $page;
            }
        }
        // The pages whose name has the query first, then the pages where only a setting has it.
        $words = array_filter(explode(' ', self::normalise($query)));
        usort($pages, fn($a, $b) => (int) !self::matches(self::normalise((string) $a->visiblename), $words)
            <=> (int) !self::matches(self::normalise((string) $b->visiblename), $words));
        $items = [];
        foreach (array_slice($pages, 0, self::LIMIT) as $page) {
            $url = $page instanceof \admin_externalpage ? new \moodle_url($page->url)
                : new \moodle_url('/admin/settings.php', ['section' => $page->name]);
            $located = $root->locate($page->name, true);
            // Path from the root of the administration, without the root nor the page.
            $path = $located ? array_slice(array_reverse($located->visiblepath), 1, -1) : [];
            $items[] = [
                'id' => 0,
                'name' => (string) $page->visiblename,
                'url' => $url->out(false),
                'detail' => implode(' › ', array_map('strval', $path)),
                'icon' => 'fa-gear',
            ];
        }
        if ($items) {
            $items[] = [
                'id' => 0,
                'name' => get_string('quicksearch_alladmin', 'theme_epure', $query),
                'url' => (new \moodle_url('/admin/search.php', ['query' => $query]))->out(false),
                'detail' => '',
                'icon' => 'fa-magnifying-glass',
            ];
        }
        return $items;
    }
}
