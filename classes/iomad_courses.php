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
 * Category of each course on IOMAD's page « Manage IOMAD course settings ».
 *
 * The table of the page belongs to IOMAD; the theme adds a column « Category » after the column of
 * the course, from the categories of the courses the user can manage, given to the page as JSON.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_courses {
    /** @var string Page type of IOMAD's page of the courses. */
    public const PAGETYPE = 'blocks-iomad_company_admin-iomad_courses_form';

    /**
     * Whether the page is IOMAD's page of the courses.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        global $DB;
        return $page->pagetype === self::PAGETYPE && $DB->get_manager()->table_exists('iomad_courses');
    }

    /**
     * Categories of the courses the user can see on the page.
     *
     * The administrators who see all the companies get all the IOMAD courses; the other managers the
     * courses of their company and the shared courses, as IOMAD lists them.
     *
     * @return array{categories: array<int, string>, courses: array<int, int>} Paths of the categories by id,
     *     and category of each course by id.
     */
    public static function data(): array {
        global $CFG, $DB;
        $sql = "SELECT c.id, c.category FROM {iomad_courses} ic JOIN {course} c ON c.id = ic.courseid";
        $params = [];
        if (!has_capability('block/iomad_company_admin:company_view_all', \context_system::instance())) {
            require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
            $sql .= " WHERE ic.shared = 1 OR c.id IN (SELECT courseid FROM {company_course} WHERE companyid = :companyid)";
            $params['companyid'] = (int) \iomad::get_my_companyid(\context_system::instance(), false);
        }
        $courses = array_map('intval', $DB->get_records_sql_menu($sql, $params));
        if (!$courses) {
            return ['categories' => [], 'courses' => []];
        }

        // Path of each category, from the names of the categories above it.
        $names = [];
        $paths = [];
        foreach ($DB->get_records('course_categories', null, '', 'id, name, path') as $category) {
            $names[$category->id] = format_string(
                $category->name,
                true,
                ['context' => \context_coursecat::instance($category->id, IGNORE_MISSING) ?: \context_system::instance()]
            );
            $paths[$category->id] = $category->path;
        }
        $categories = [];
        foreach (array_unique($courses) as $categoryid) {
            if (!isset($paths[$categoryid])) {
                continue;
            }
            $ids = array_filter(explode('/', $paths[$categoryid]));
            $categories[$categoryid] = implode(' / ', array_map(fn($id) => $names[$id] ?? '', $ids));
        }
        return ['categories' => $categories, 'courses' => $courses];
    }
}
