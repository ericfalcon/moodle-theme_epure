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
 * Visitors who are not logged in, and guests, search what they can see without an account: the courses of the
 * catalogue, the categories and the public pages (log in, create an account, accessibility statement), never on a
 * site where logging in is required. With IOMAD, the courses and the categories belong to companies, and a visitor
 * belongs to none: they search the public pages only.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quick_search {
    /** @var int Results of each group. */
    public const LIMIT = 6;

    /** @var int Courses of the user whose activities are searched, those they opened last; all on the page of results. */
    public const ACTIVITY_COURSES = 30;

    /** @var int Results of each group on the page of all the results. */
    public const RESULTS = 100;

    /**
     * Whether the quick search is offered to the current user.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return company_style::enabled('quicksearch') && (self::member() || self::visitor());
    }

    /**
     * Whether the current user is logged in with an account of their own.
     *
     * @return bool
     */
    public static function member(): bool {
        return isloggedin() && !isguestuser();
    }

    /**
     * Whether the current user is a visitor (not logged in, or a guest) on a site open without logging in.
     *
     * @return bool
     */
    public static function visitor(): bool {
        global $CFG;
        return !self::member() && empty($CFG->forcelogin);
    }

    /**
     * Groups of results for a visitor: the courses of the catalogue, the categories, the public pages.
     *
     * @param string $query Query, two characters or more.
     * @return array[] Groups, as {@see search()}.
     */
    public static function search_public(string $query): array {
        $words = array_filter(explode(' ', self::normalise($query)));
        if (\core_text::strlen(implode('', $words)) < 2) {
            return [];
        }
        $groups = [];
        // With IOMAD, the courses and the categories are those of a company, and a visitor belongs to none (IOMAD's
        // lists of categories fail without a company).
        if (!iomad::installed()) {
            $groups[] = ['key' => 'catalogue', 'label' => get_string('catalogue', 'theme_epure'),
                'items' => self::catalogue($query, [])];
            $groups[] = ['key' => 'categories', 'label' => get_string('categories'), 'items' => self::categories($words)];
        }
        $groups[] = ['key' => 'public', 'label' => get_string('quicksearch_pages', 'theme_epure'),
            'items' => self::public_pages($words)];
        return array_values(array_filter($groups, fn($group) => $group['items']));
    }

    /**
     * The categories the user can see whose name, or the name of a parent, matches the words.
     *
     * @param string[] $words Words of the query, normalised.
     * @return array[]
     */
    protected static function categories(array $words): array {
        $items = [];
        foreach (\core_course_category::make_categories_list() as $id => $path) {
            if (!self::matches(self::normalise($path), $words)) {
                continue;
            }
            $parts = explode(' / ', $path);
            $items[] = [
                'id' => (int) $id,
                'name' => (string) array_pop($parts),
                'url' => (new \moodle_url('/course/index.php', ['categoryid' => $id]))->out(false),
                'detail' => implode(' › ', $parts),
                'icon' => 'fa-folder-open',
            ];
            if (count($items) >= self::LIMIT) {
                break;
            }
        }
        return $items;
    }

    /**
     * The public pages whose name matches the words: log in, create an account when new accounts are open,
     * the accessibility statement once published.
     *
     * @param string[] $words Words of the query, normalised.
     * @return array[]
     */
    protected static function public_pages(array $words): array {
        global $CFG;
        require_once($CFG->libdir . '/authlib.php');
        $pages = [];
        if (!isloggedin() || isguestuser()) {
            $pages[] = [get_string('login'), get_login_url(), 'fa-right-to-bracket'];
        }
        if (!isloggedin() && signup_is_enabled()) {
            $pages[] = [get_string('startsignup'), $CFG->wwwroot . '/login/signup.php', 'fa-user-plus'];
        }
        $pages[] = [get_string('courses'), (new \moodle_url('/course/index.php'))->out(false), 'fa-compass'];
        if (accessibility_statement::enabled()) {
            $pages[] = [get_string('a11ystatement', 'theme_epure'), accessibility_statement::url()->out(false),
                'fa-universal-access'];
        }
        $items = [];
        foreach ($pages as [$name, $url, $icon]) {
            if (self::matches(self::normalise($name), $words)) {
                $items[] = ['id' => 0, 'name' => $name, 'url' => (string) $url, 'detail' => '', 'icon' => $icon];
            }
        }
        return $items;
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
        [$mycourses, $activities, $enrolled, $more] = self::mine($words, self::LIMIT, self::ACTIVITY_COURSES);
        // More results in the courses of the user than shown: the page of all of them.
        if ($more) {
            $activities[] = [
                'id' => 0,
                'name' => get_string('quicksearch_allmine', 'theme_epure', $query),
                'url' => self::results_url($query)->out(false),
                'detail' => '',
                'icon' => 'fa-magnifying-glass',
            ];
        }
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
     * How well a name matches the words, the best first: 0 the name is the query, 1 it starts with it, 2 each word
     * starts a word of the name, 3 the name contains each word.
     *
     * @param string $name Name, normalised.
     * @param string[] $words Words of the query, normalised.
     * @return int
     */
    public static function rank(string $name, array $words): int {
        $query = implode(' ', $words);
        if ($name === $query) {
            return 0;
        }
        if (str_starts_with($name, $query)) {
            return 1;
        }
        $starts = preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $word) {
            $found = false;
            foreach ($starts as $start) {
                if (str_starts_with($start, $word)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return 3;
            }
        }
        return 2;
    }

    /**
     * The courses of the user, the courses they opened last first.
     *
     * @return \stdClass[]
     */
    protected static function courses_by_access(): array {
        global $DB, $USER;
        $courses = enrol_get_my_courses(['fullname', 'shortname', 'visible']);
        $access = $DB->get_records_menu('user_lastaccess', ['userid' => $USER->id], '', 'courseid, timeaccess');
        $order = array_flip(array_keys($courses));
        uasort($courses, fn($a, $b) => ((int) ($access[$b->id] ?? 0) <=> (int) ($access[$a->id] ?? 0))
            ?: $order[$a->id] <=> $order[$b->id]);
        return $courses;
    }

    /**
     * The courses of the user, and the activities of their courses, matching the words, the best first: by how well
     * their name matches, then the courses the user opened last.
     *
     * @param string[] $words Words of the query, normalised.
     * @param int $limit Results of each kind.
     * @param int $courselimit Courses whose activities are searched, those opened last; 0 for all.
     * @return array{0: array[], 1: array[], 2: int[], 3: bool} Courses and activities found, all the courses of the user,
     *     and whether more results are left than shown.
     */
    public static function mine(array $words, int $limit, int $courselimit): array {
        global $USER;
        $courses = self::courses_by_access();
        $found = [];
        $activities = [];
        $more = $courselimit && count($courses) > $courselimit;
        $searched = 0;
        // Without activity icons, a plain icon marks the activities, as the other results.
        $icons = activity_icons::current() !== 'none';
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $name = format_string($course->fullname, true, ['context' => $context]);
            $normal = self::normalise($name . ' ' . $course->shortname);
            if (self::matches($normal, $words)) {
                $found[] = [
                    'rank' => self::rank(self::normalise($name), $words),
                    'order' => count($found),
                    'id' => (int) $course->id,
                    'name' => $name,
                    'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                    'detail' => format_string($course->shortname, true, ['context' => $context]),
                    'icon' => 'fa-graduation-cap',
                ];
            }
            if ($courselimit && $searched++ >= $courselimit) {
                continue;
            }
            foreach (get_fast_modinfo($course, $USER->id)->get_cms() as $cm) {
                if (!$cm->uservisible || empty($cm->url) || $cm->is_stealth()) {
                    continue;
                }
                $cmname = $cm->get_formatted_name();
                $normal = self::normalise($cmname);
                if (self::matches($normal, $words)) {
                    $activities[] = [
                        'rank' => self::rank($normal, $words),
                        'order' => count($activities),
                        'id' => (int) $cm->id,
                        'name' => $cmname,
                        'url' => $cm->url->out(false),
                        'detail' => $name,
                    ] + ($icons ? ['image' => $cm->get_icon_url()->out(false)] : ['icon' => 'fa-file-lines']);
                }
            }
        }
        $best = function (array $items) use ($limit): array {
            usort($items, fn($a, $b) => [$a['rank'], $a['order']] <=> [$b['rank'], $b['order']]);
            return array_map(function (array $item) {
                unset($item['rank'], $item['order']);
                return $item;
            }, array_slice($items, 0, $limit));
        };
        $more = $more || count($found) > $limit || count($activities) > $limit;
        return [$best($found), $best($activities), array_map('intval', array_keys($courses)), $more];
    }

    /**
     * The page of all the results in the courses of the user.
     *
     * @param string $query Query.
     * @return \moodle_url
     */
    public static function results_url(string $query = ''): \moodle_url {
        return new \moodle_url('/theme/epure/search.php', $query === '' ? [] : ['q' => $query]);
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
            if (
                !$page->is_hidden() && $page->check_access()
                    && ($page instanceof \admin_externalpage || $page instanceof \admin_settingpage)
            ) {
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
