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
 * Home of the site administration in cards: Users, Courses, Appearance, Plugins, Reports, Server and, with IOMAD,
 * IOMAD, each with its common tasks, above Moodle's tree of the administration, which stays as it is.
 *
 * The tasks are pages of the administration tree, found by their name: a page that does not exist in a version of
 * Moodle, or that the user cannot open, is left out, and a card without tasks too.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_home {
    /** @var array[] The cards: category of the administration tree, icon and pages of its common tasks. */
    public const CARDS = [
        ['category' => 'users', 'icon' => 'fa-users',
            'pages' => ['editusers', 'addnewuser', 'tooluploaduser', 'cohorts', 'assignroles']],
        ['category' => 'courses', 'icon' => 'fa-graduation-cap',
            'pages' => ['coursemgmt', 'addnewcourse', 'restorecourse', 'tooluploadcourse']],
        ['category' => 'appearance', 'icon' => 'fa-palette',
            'pages' => ['themesettingepure', 'theme_epure_vocabulary', 'themeselector', 'navigation', 'tool_usertours/tours']],
        ['category' => 'modules', 'icon' => 'fa-puzzle-piece',
            'pages' => ['pluginsoverview', 'tool_installaddon_index', 'manageenrols', 'manageauths']],
        ['category' => 'reports', 'icon' => 'fa-chart-column',
            'pages' => ['reportlog', 'reportloglive', 'customreports', 'reportconfiglog']],
        ['category' => 'server', 'icon' => 'fa-server',
            'pages' => ['adminnotifications', 'scheduledtasks', 'maintenancemode', 'environment']],
    ];

    /** @var string[] Pages of IOMAD in its card, by string of block_iomad_company_admin ({@see iomad::PAGES}). */
    public const IOMAD = ['dashboard' => 'dashboard', 'company' => 'editcompany', 'users' => 'edit_users_title',
        'licenses' => 'licensemanagement'];

    /**
     * Whether the page is the home of the site administration (without a search).
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        return $page->pagetype === 'admin-search' && optional_param('query', '', PARAM_RAW) === ''
            && has_capability('moodle/site:config', \context_system::instance());
    }

    /**
     * Context of the template theme_epure/admin_home.
     *
     * @return array
     */
    public static function export(): array {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $root = admin_get_root();
        $cards = [];
        foreach (self::CARDS as $card) {
            $category = $root->locate($card['category']);
            if (!$category || !$category->check_access() || $category->is_hidden()) {
                continue;
            }
            $tasks = [];
            foreach ($card['pages'] as $name) {
                $page = $root->locate($name);
                if ($page && !$page->is_hidden() && $page->check_access()) {
                    $tasks[] = ['name' => (string) $page->visiblename, 'url' => self::url($page)->out(false)];
                }
            }
            if ($tasks) {
                $cards[] = [
                    'name' => (string) $category->visiblename,
                    'url' => (new \moodle_url('/admin/category.php', ['category' => $category->name]))->out(false),
                    'icon' => $card['icon'],
                    'tasks' => $tasks,
                ];
            }
        }
        if (iomad::installed()) {
            $tasks = [];
            foreach (self::IOMAD as $page => $string) {
                if (get_string_manager()->string_exists($string, 'block_iomad_company_admin')) {
                    $tasks[] = ['name' => get_string($string, 'block_iomad_company_admin'),
                        'url' => iomad::url($page)->out(false)];
                }
            }
            $cards[] = ['name' => 'IOMAD', 'url' => iomad::url('dashboard')->out(false), 'icon' => 'fa-building',
                'tasks' => $tasks];
        }
        return ['cards' => $cards];
    }

    /**
     * Address of a page of the administration tree.
     *
     * @param \part_of_admin_tree $page Page.
     * @return \moodle_url
     */
    protected static function url($page): \moodle_url {
        if (!empty($page->url)) {
            return new \moodle_url($page->url);
        }
        if ($page instanceof \admin_category) {
            return new \moodle_url('/admin/category.php', ['category' => $page->name]);
        }
        return new \moodle_url('/admin/settings.php', ['section' => $page->name]);
    }
}
