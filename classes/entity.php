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
 * Entities of a site without IOMAD: a course category with an Épure appearance of its own (colours, logo, header,
 * footer…), as a category theme or a cohort theme, without a copy of the theme.
 *
 * Moodle chooses the theme of a page in the order of $CFG->themeorder: course, category, session, user, cohort, site.
 * Épure follows it for its appearances. In a course or a category, the entity is the nearest category, the page's or one
 * of its parents, that has an appearance. Elsewhere, and in a course out of every entity, it is the entity of the cohorts
 * of the user: the cohorts chosen in its appearance, and those whose theme is Épure in its category; a user in the
 * cohorts of two entities, or of none, sees the site, as Moodle does with two cohort themes.
 *
 * The appearance is stored in the theme settings as categorystyle_<category> (JSON), with the settings that an IOMAD
 * company can set ({@see company_style}) and the cohorts. Its logos are files of the system context, item = category.
 * With IOMAD, the companies are the entities: the entities of categories are not offered there.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity {
    /** @var string Prefix of the setting that holds the appearance of a category. */
    public const PREFIX = 'categorystyle_';

    /** @var string[] File areas of the logos of an entity: for a light header, for the brand-coloured header. */
    public const FILE_AREAS = ['entitylogo', 'entitylogoonbrand'];

    /** @var string[] Extensions of the logos. */
    public const FILE_TYPES = ['svg', 'png', 'webp', 'jpg', 'jpeg'];

    /** @var string[] Settings of a company that an entity has not: e-mails, login page and the font uploaded for a company. */
    public const NOT_FOR_ENTITIES = ['emailbranding', 'fontname'];

    /** @var string Parameter of the address of the login page of an entity: /login/index.php?entity=<category>. */
    public const LOGIN_PARAM = 'entity';

    /** @var string User preference: the entity chosen by a user in the cohorts of several entities. */
    public const PREFERENCE = 'theme_epure_entity';

    /** @var int Highest priority of an entity. */
    public const MAX_PRIORITY = 9;

    /** @var array<int, array>|null Appearances of the site, by category, cached for the request. */
    protected static ?array $all = null;

    /** @var \stdClass|false|null Entity of the current page, cached for the request. */
    protected static $current = null;

    /** @var string What the cached entities were read for: the revision of the appearances, the page and the user. */
    protected static string $cachekey = '';

    /** @var array<int, int> Entity of the cohorts of each user (0: none), cached for the request. */
    protected static array $users = [];

    /** @var array<int, int[]> Entities of the cohorts of each user, cached for the request. */
    protected static array $userentities = [];

    /**
     * Whether the entities apply: on Moodle, not on IOMAD, whose companies are the entities.
     *
     * @return bool
     */
    public static function available(): bool {
        return !during_initial_install() && !iomad::installed();
    }

    /**
     * The appearances of the site, by category.
     *
     * @return array<int, array> Settings of each ({@see self::settings()}).
     */
    public static function all(): array {
        self::check_cache();
        if (self::$all === null) {
            self::$all = [];
            $config = (array) get_config('theme_epure');
            foreach ($config as $name => $json) {
                if (str_starts_with($name, self::PREFIX) && ($id = (int) substr($name, strlen(self::PREFIX)))) {
                    $values = json_decode((string) $json, true);
                    self::$all[$id] = self::clean(is_array($values) ? $values : []);
                }
            }
            ksort(self::$all);
        }
        return self::$all;
    }

    /**
     * Whether a category is an entity: it has an appearance.
     *
     * @param int $categoryid Category.
     * @return bool
     */
    public static function exists(int $categoryid): bool {
        return isset(self::all()[$categoryid]);
    }

    /**
     * Appearance of a category: empty values where it keeps the setting of the site.
     *
     * @param int $categoryid Category.
     * @return array<string, mixed> The settings of a company ({@see company_style::settings()}) and cohorts (int[]).
     */
    public static function settings(int $categoryid): array {
        return self::all()[$categoryid] ?? self::clean([]);
    }

    /**
     * Keeps the valid settings of an entity.
     *
     * @param array $values Values.
     * @return array<string, mixed>
     */
    public static function clean(array $values): array {
        $settings = company_style::clean($values);
        foreach (self::NOT_FOR_ENTITIES as $name) {
            unset($settings[$name]);
        }
        // A font uploaded for a company has no place here: the fonts of the theme and the one of the site.
        if ($settings['font'] === company_style::COMPANY_FONT) {
            $settings['font'] = '';
        }
        $cohorts = array_values(array_unique(array_filter(array_map('intval', (array) ($values['cohorts'] ?? [])))));
        sort($cohorts);
        $settings['cohorts'] = $cohorts;
        // CSS added to the pages of the entity, set by the administrators of the site only (theme_epure\form\entity_form).
        $settings['customcss'] = trim((string) ($values['customcss'] ?? ''));
        // For a user in the cohorts of several entities: the one with the highest priority, when it is the only one.
        $settings['priority'] = max(0, min(self::MAX_PRIORITY, (int) ($values['priority'] ?? 0)));
        $settings['vocabulary'] = self::clean_vocabulary((array) ($values['vocabulary'] ?? []));
        return $settings;
    }

    /**
     * Keeps the valid words of an entity: for each language and each of course, student and teacher, a word of the list
     * or one typed (cleaned as those of the companies), empty for the word of the site.
     *
     * @param array $values Setting names of the vocabulary ({@see vocabulary\terms::setting()}) => values.
     * @return array<string, string> Only the words chosen.
     */
    public static function clean_vocabulary(array $values): array {
        $clean = [];
        $presets = vocabulary\terms::presets();
        foreach (vocabulary\terms::LANGUAGES as $lang) {
            foreach (vocabulary\terms::CONCEPTS as $concept) {
                $name = vocabulary\terms::setting($lang, $concept);
                $choice = (string) ($values[$name] ?? '');
                if ($choice === vocabulary\terms::CUSTOM) {
                    $field = vocabulary\terms::setting($lang, $concept, 'singular');
                    $singular = vocabulary\terms::clean_word((string) ($values[$field] ?? ''));
                    if ($singular === '') {
                        continue;
                    }
                    $clean[$name] = $choice;
                    $clean[vocabulary\terms::setting($lang, $concept, 'singular')] = $singular;
                    $clean[vocabulary\terms::setting($lang, $concept, 'plural')] = vocabulary\terms::clean_word(
                        (string) ($values[vocabulary\terms::setting($lang, $concept, 'plural')] ?? '')
                    );
                    $clean[vocabulary\terms::setting($lang, $concept, 'gender')] =
                        ($values[vocabulary\terms::setting($lang, $concept, 'gender')] ?? '') === 'f' ? 'f' : 'm';
                } else if ($choice !== '' && isset($presets[$lang][$concept][$choice])) {
                    $clean[$name] = $choice;
                }
            }
        }
        return $clean;
    }

    /**
     * Whether an entity has words of its own: the string manager of the theme is then needed
     * ({@see hook_callbacks::after_config()}).
     *
     * @return bool
     */
    public static function vocabulary_active(): bool {
        return get_config('theme_epure', 'entityvocab') === '1';
    }

    /**
     * After a change of the words of an entity: whether some entity has words, and the strings to load again.
     */
    protected static function vocabulary_changed(): void {
        self::$all = null;
        $any = (bool) array_filter(array_column(self::all(), 'vocabulary'));
        set_config('entityvocab', $any ? '1' : '0', 'theme_epure');
        set_config('companyrev', time(), 'theme_epure');
        vocabulary\company::reset();
        get_string_manager()->reset_caches();
    }

    /**
     * Saves the appearance of a category, which makes it an entity.
     *
     * @param int $categoryid Category.
     * @param array $values Values of {@see self::settings()}.
     */
    public static function save(int $categoryid, array $values): void {
        $before = self::settings($categoryid)['vocabulary'];
        $settings = self::clean($values);
        set_config(self::PREFIX . $categoryid, json_encode($settings), 'theme_epure');
        set_config('entityrev', uniqid('', true), 'theme_epure');
        self::reset();
        if ($settings['vocabulary'] !== $before) {
            self::vocabulary_changed();
        }
    }

    /**
     * Removes the appearance of a category, and its logos: the category is no longer an entity.
     *
     * @param int $categoryid Category.
     */
    public static function delete(int $categoryid): void {
        $words = (bool) self::settings($categoryid)['vocabulary'];
        unset_config(self::PREFIX . $categoryid, 'theme_epure');
        set_config('entityrev', uniqid('', true), 'theme_epure');
        $fs = get_file_storage();
        foreach (self::FILE_AREAS as $area) {
            $fs->delete_area_files(\context_system::instance()->id, 'theme_epure', $area, $categoryid);
        }
        self::reset();
        if ($words) {
            self::vocabulary_changed();
        }
    }

    /**
     * Whether a file may be kept and served as a logo of an entity.
     *
     * @param string $filename File name.
     * @return bool
     */
    public static function allowed_file(string $filename): bool {
        return in_array(\core_text::strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::FILE_TYPES, true);
    }

    /**
     * Address of a logo of an entity.
     *
     * @param int $categoryid Category.
     * @param string $area One of {@see self::FILE_AREAS}.
     * @return \moodle_url|null
     */
    public static function logo_url(int $categoryid, string $area = 'entitylogo'): ?\moodle_url {
        $context = \context_system::instance();
        $files = get_file_storage()->get_area_files($context->id, 'theme_epure', $area, $categoryid, 'timemodified DESC', false);
        if (!$file = reset($files)) {
            return null;
        }
        return \moodle_url::make_pluginfile_url(
            $context->id,
            'theme_epure',
            $area,
            $categoryid,
            '/' . $file->get_timemodified() . '/',
            $file->get_filename()
        );
    }

    /**
     * The nearest entity of a context: its category or one of the parents, for a course, an activity or a category.
     *
     * @param \context $context Context.
     * @return int Category of the entity, 0 out of every entity.
     */
    public static function of_context(\context $context): int {
        if (!self::all()) {
            return 0;
        }
        // The path of the context, from the system to the context itself: the nearest category is the last.
        $ids = array_reverse(array_map('intval', array_filter(explode('/', (string) $context->path))));
        foreach ($ids as $id) {
            $parent = \context::instance_by_id($id, IGNORE_MISSING);
            if ($parent && (int) $parent->contextlevel === CONTEXT_COURSECAT && self::exists((int) $parent->instanceid)) {
                return (int) $parent->instanceid;
            }
        }
        return 0;
    }

    /**
     * The cohorts that lead to each entity: those chosen in its appearance, and those whose theme is Épure in the context
     * of its category or of one of its subcategories.
     *
     * @return array<int, int[]> Categories of the entities of each cohort.
     */
    public static function cohort_map(): array {
        global $DB;
        $map = [];
        foreach (self::all() as $categoryid => $settings) {
            foreach ($settings['cohorts'] as $cohortid) {
                $map[$cohortid][] = $categoryid;
            }
        }
        if (self::all()) {
            $records = $DB->get_records_sql(
                "SELECT c.id, c.theme, ctx.id AS contextid
                   FROM {cohort} c
                   JOIN {context} ctx ON ctx.id = c.contextid AND ctx.contextlevel = :level
                  WHERE c.theme <> ''",
                ['level' => CONTEXT_COURSECAT]
            );
            foreach ($records as $cohort) {
                if (theme_use::is_epure($cohort->theme)) {
                    $context = \context::instance_by_id((int) $cohort->contextid, IGNORE_MISSING);
                    if ($context && ($categoryid = self::of_context($context))) {
                        $map[(int) $cohort->id][] = $categoryid;
                    }
                }
            }
        }
        return array_map(fn($ids) => array_values(array_unique($ids)), $map);
    }

    /**
     * The entities the cohorts of a user lead to.
     *
     * @param int $userid User.
     * @return int[] Categories of the entities, in the order of their categories.
     */
    public static function of_user_all(int $userid): array {
        global $DB;
        self::check_cache();
        if (!array_key_exists($userid, self::$userentities)) {
            $found = [];
            $map = $userid ? self::cohort_map() : [];
            if ($map) {
                [$insql, $params] = $DB->get_in_or_equal(array_keys($map), SQL_PARAMS_NAMED);
                $cohorts = $DB->get_fieldset_select(
                    'cohort_members',
                    'cohortid',
                    "userid = :userid AND cohortid $insql",
                    ['userid' => $userid] + $params
                );
                $entities = [];
                foreach ($cohorts as $cohortid) {
                    foreach ($map[(int) $cohortid] as $categoryid) {
                        $entities[$categoryid] = true;
                    }
                }
                ksort($entities);
                $found = array_map('intval', array_keys($entities));
            }
            self::$userentities[$userid] = $found;
        }
        return self::$userentities[$userid];
    }

    /**
     * The entity of the cohorts of a user: the only one they lead to; among several, the one the user chose
     * ({@see self::PREFERENCE}), else the one with the highest priority when no other has it.
     *
     * @param int $userid User.
     * @return int Category of the entity, 0 when the cohorts of the user lead to none, or to several without a choice.
     */
    public static function of_user(int $userid): int {
        self::check_cache();
        if (!array_key_exists($userid, self::$users)) {
            $entities = self::of_user_all($userid);
            $entity = 0;
            if (count($entities) === 1) {
                $entity = $entities[0];
            } else if ($entities) {
                $chosen = (int) get_user_preferences(self::PREFERENCE, 0, $userid);
                if (in_array($chosen, $entities, true)) {
                    $entity = $chosen;
                } else {
                    $priorities = [];
                    foreach ($entities as $categoryid) {
                        $priorities[$categoryid] = (int) self::settings($categoryid)['priority'];
                    }
                    $highest = max($priorities);
                    $first = array_keys($priorities, $highest, true);
                    $entity = count($first) === 1 ? (int) $first[0] : 0;
                }
            }
            self::$users[$userid] = $entity;
        }
        return self::$users[$userid];
    }

    /**
     * The entity of the current page: that of its category in a course or a category, else that of the cohorts of the
     * user.
     *
     * @return \stdClass|null Category of the entity (id, name), null out of every entity.
     */
    public static function current(): ?\stdClass {
        global $PAGE, $USER;
        self::check_cache();
        if (self::$current === null) {
            self::$current = false;
            if (self::available() && self::all()) {
                $categoryid = 0;
                try {
                    $context = $PAGE->context;
                } catch (\Throwable $e) {
                    $context = null;
                }
                if ($context) {
                    $categoryid = self::of_context($context);
                }
                if (!$categoryid && isloggedin() && !isguestuser()) {
                    $categoryid = self::of_user((int) $USER->id);
                }
                // The login page of an entity (its address with ?entity=), kept in the session, as IOMAD keeps the
                // company of its login page: before the login, and after it for a user out of every entity.
                if (!$categoryid) {
                    $categoryid = self::of_session();
                }
                $category = $categoryid ? \core_course_category::get($categoryid, IGNORE_MISSING, true) : null;
                self::$current = $category ? (object) ['id' => (int) $category->id, 'name' => $category->name] : false;
            }
        }
        return self::$current ?: null;
    }

    /**
     * The entity whose login page was opened (its address with ?entity=), kept in the session.
     *
     * @return int Category of the entity, 0 when none.
     */
    public static function of_session(): int {
        global $SCRIPT, $SESSION;
        // The parameter is read on the login page only: other pages can have a parameter of the same name.
        $param = $SCRIPT === '/login/index.php' ? optional_param(self::LOGIN_PARAM, 0, PARAM_INT) : 0;
        if ($param && self::exists($param)) {
            $SESSION->theme_epure_entity = $param;
        }
        $id = (int) ($SESSION->theme_epure_entity ?? 0);
        return $id && self::exists($id) ? $id : 0;
    }

    /**
     * Address of the login page of an entity: its appearance and the texts of its login page before the login.
     *
     * @param int $categoryid Category of the entity.
     * @return \moodle_url
     */
    public static function login_url(int $categoryid): \moodle_url {
        return new \moodle_url('/login/index.php', [self::LOGIN_PARAM => $categoryid]);
    }

    /**
     * Value of a setting of the entity of the current page, empty when there is none or when it keeps the site setting.
     *
     * @param string $name Setting.
     * @return string
     */
    public static function value(string $name): string {
        $entity = self::current();
        $value = $entity ? (self::settings($entity->id)[$name] ?? '') : '';
        return is_string($value) ? $value : '';
    }

    /**
     * CSS of the appearance of the entity of the current page, on top of the theme.
     *
     * @return string Empty out of every entity, or when it sets nothing.
     */
    public static function current_css(): string {
        $entity = self::current();
        if (!$entity) {
            return '';
        }
        $settings = self::settings($entity->id);
        $css = company_style::appearance_css($settings, $settings['brandcolor'] ?: null);
        // The CSS of the entity, as that of an IOMAD company, made safe (css_filter): it cannot load anything from
        // another site, nor end the style element early.
        return $css . css_filter::clean($settings['customcss']);
    }

    /**
     * The categories whose theme is another than Épure: to move to an entity of Épure, which needs no copy of a theme.
     *
     * @return \stdClass[] Categories (id, name, theme), with the brand colour of their theme when it has one (brand).
     */
    public static function other_themes(): array {
        global $DB;
        $list = [];
        foreach ($DB->get_records_select('course_categories', "theme <> ''", null, 'sortorder', 'id, name, theme') as $category) {
            if (!theme_use::is_epure($category->theme)) {
                $category->brand = self::theme_brand((string) $category->theme);
                $list[] = $category;
            }
        }
        return $list;
    }

    /**
     * The categories whose theme is Épure and that have no appearance yet: entities to set.
     *
     * @return \stdClass[] Categories (id, name, theme).
     */
    public static function epure_without_appearance(): array {
        global $DB;
        $list = [];
        foreach ($DB->get_records_select('course_categories', "theme <> ''", null, 'sortorder', 'id, name, theme') as $category) {
            if (theme_use::is_epure($category->theme) && !self::exists((int) $category->id)) {
                $list[] = $category;
            }
        }
        return $list;
    }

    /**
     * Brand colour of another theme, to start the appearance of an entity: the setting brandcolor, which Boost and most of
     * its children have.
     *
     * @param string $theme Theme name.
     * @return string|null Hex colour.
     */
    public static function theme_brand(string $theme): ?string {
        $value = get_config('theme_' . clean_param($theme, PARAM_PLUGIN), 'brandcolor');
        return palette::normalise(is_string($value) ? $value : null);
    }

    /**
     * What keeps the category and cohort themes from applying: the settings of Moodle that allow them, off while some
     * categories or cohorts have Épure as their theme.
     *
     * @return string[] Names of the settings of Moodle (allowcategorythemes, allowcohortthemes) to turn on.
     */
    public static function blocked_settings(): array {
        global $CFG, $DB;
        $blocked = [];
        $uses = fn(string $table) => array_filter(
            $DB->get_fieldset_select($table, 'DISTINCT theme', "theme <> ''"),
            fn($theme) => theme_use::is_epure($theme)
        );
        if (empty($CFG->allowcategorythemes) && $uses('course_categories')) {
            $blocked[] = 'allowcategorythemes';
        }
        if (empty($CFG->allowcohortthemes) && $uses('cohort')) {
            $blocked[] = 'allowcohortthemes';
        }
        return $blocked;
    }

    /**
     * The cohorts that may lead to the entity of a category: those of the system, of the category, of its parents and of
     * its subcategories.
     *
     * @param int $categoryid Category.
     * @return array<int, string> Names, by cohort.
     */
    public static function cohort_options(int $categoryid): array {
        global $DB;
        $context = \context_coursecat::instance($categoryid);
        $ids = array_map('intval', array_filter(explode('/', (string) $context->path)));
        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params['path'] = $DB->sql_like_escape($context->path) . '/%';
        $records = $DB->get_records_sql(
            "SELECT c.id, c.name
               FROM {cohort} c
               JOIN {context} ctx ON ctx.id = c.contextid
              WHERE c.contextid $insql OR " . $DB->sql_like('ctx.path', ':path', false, false) . "
           ORDER BY c.name",
            $params
        );
        $options = [];
        foreach ($records as $cohort) {
            $options[(int) $cohort->id] = format_string($cohort->name, true, ['context' => $context]);
        }
        return $options;
    }

    /**
     * Address of the form of the appearance of a category.
     *
     * @param int $categoryid Category.
     * @return \moodle_url
     */
    public static function url(int $categoryid): \moodle_url {
        return new \moodle_url('/theme/epure/entity.php', ['category' => $categoryid]);
    }

    /**
     * Whether the user may set the appearance of a category: those who manage it.
     *
     * @param int $categoryid Category.
     * @return bool
     */
    public static function can_edit(int $categoryid): bool {
        return has_capability('moodle/category:manage', \context_coursecat::instance($categoryid));
    }

    /**
     * Adds « Appearance of the entity » to the settings of a category, for those who manage it.
     *
     * @param \navigation_node $navigation Settings of the category.
     * @param \context_coursecat $context Context of the category.
     */
    public static function extend_navigation(\navigation_node $navigation, \context_coursecat $context): void {
        if (!self::available() || !self::can_edit((int) $context->instanceid)) {
            return;
        }
        $navigation->add(
            get_string('entityappearance', 'theme_epure'),
            self::url((int) $context->instanceid),
            \navigation_node::TYPE_SETTING,
            null,
            'theme_epure_entity',
            new \pix_icon('i/settings', '')
        );
    }

    /**
     * Forgets what was read when the appearances, the page or the user changed since: a change saved elsewhere (another
     * request, a test that restored the settings) is never hidden by what this request read before.
     */
    protected static function check_cache(): void {
        global $PAGE, $USER;
        $key = get_config('theme_epure', 'entityrev') . '/' . (isset($PAGE) ? spl_object_id($PAGE) : 0) . '/'
            . (int) ($USER->id ?? 0);
        if ($key !== self::$cachekey) {
            self::$cachekey = $key;
            self::$all = null;
            self::$current = null;
            self::$users = [];
            self::$userentities = [];
        }
    }

    /**
     * Forgets what was read for the request (used by tests and after a change).
     */
    public static function reset(): void {
        self::$all = null;
        self::$current = null;
        self::$users = [];
        self::$userentities = [];
        self::$cachekey = '';
    }
}
