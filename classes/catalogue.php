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

use core_course\external\course_summary_exporter;

/**
 * Catalogue of the courses: the courses of a category and of its subcategories as cards.
 *
 * The courses are read with Moodle's course category API, which IOMAD restricts to the courses of
 * the company of the user; the subcategories are filtered as IOMAD does in its course renderer.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue {
    /** @var int Courses on a page of the catalogue. */
    public const PERPAGE = 24;

    /**
     * Whether the catalogue of Épure replaces Moodle's list of courses.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return get_config('theme_epure', 'catalogue') !== '0';
    }

    /**
     * Context of the template theme_epure/catalogue.
     *
     * @param \core_course_category $category Category, or the top of the categories.
     * @param \renderer_base $output Renderer.
     * @param int $page Page of the courses, from 0.
     * @return array
     */
    public static function export(\core_course_category $category, \renderer_base $output, int $page = 0): array {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/course/renderer.php');
        $options = ['recursive' => true, 'summary' => true, 'coursecontacts' => true, 'sort' => ['sortorder' => 1]];
        $total = $category->get_courses_count($options);
        $page = max(0, min($page, (int) ceil($total / self::PERPAGE) - 1));
        $courses = $category->get_courses($options + ['offset' => $page * self::PERPAGE, 'limit' => self::PERPAGE]);

        $baseurl = new \moodle_url('/course/index.php', $category->id ? ['categoryid' => $category->id] : []);
        $chelper = new \coursecat_helper();
        $parent = $category->id ? $category->get_parent_coursecat() : null;
        return [
            'description' => $category->id ? (string) $chelper->get_category_formatted_description($category) : '',
            'categories' => self::subcategories($category),
            'hascategories' => (bool) $category->get_children_count(),
            'parent' => $parent && $parent->is_uservisible() ? [
                'name' => $parent->id ? $parent->get_formatted_name() : get_string('catalogue', 'theme_epure'),
                'url' => (new \moodle_url('/course/index.php', $parent->id ? ['categoryid' => $parent->id] : []))->out(false),
            ] : null,
            'count' => get_string($total == 1 ? 'catalogue_count_one' : 'catalogue_count', 'theme_epure', $total),
            'courses' => array_values(array_map(fn($course) => self::card($course, $output), $courses)),
            'hascourses' => $total > 0,
            'paging' => $total > self::PERPAGE ? $OUTPUT->paging_bar($total, $page, self::PERPAGE, $baseurl) : '',
        ];
    }

    /**
     * Subcategories of a category the user can see, with the number of their courses.
     *
     * @param \core_course_category $category Category.
     * @return array[]
     */
    public static function subcategories(\core_course_category $category): array {
        global $CFG;
        $children = $category->get_children();
        if (file_exists($CFG->dirroot . '/local/iomad/lib/iomad.php') && !is_siteadmin()) {
            require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
            $children = \iomad::iomad_filter_categories($children);
        }
        $list = [];
        foreach ($children as $child) {
            $count = $child->get_courses_count(['recursive' => true]);
            $list[] = [
                'name' => $child->get_formatted_name(),
                'url' => (new \moodle_url('/course/index.php', ['categoryid' => $child->id]))->out(false),
                'count' => $count,
                'hidden' => !$child->visible,
            ];
        }
        return $list;
    }

    /**
     * Data of the card of a course in the catalogue.
     *
     * @param \core_course_list_element|\stdClass $course Course.
     * @param \renderer_base $output Renderer.
     * @return array
     */
    public static function card($course, \renderer_base $output): array {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/course/renderer.php');
        if (!$course instanceof \core_course_list_element) {
            $course = new \core_course_list_element($course);
        }
        $context = \context_course::instance($course->id);
        $image = course_summary_exporter::get_course_image($course) ?: $output->get_generated_image_for_id($course->id);
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
        $summary = '';
        if ($course->has_summary()) {
            $summary = (new \coursecat_helper())->get_course_formatted_summary($course, ['noclean' => true, 'para' => false]);
            $summary = shorten_text(trim(html_to_text($summary, 0, false)), 180);
        }
        $contacts = array_map(fn($contact) => $contact['username'], $course->get_course_contacts());

        $enrolled = isloggedin() && !isguestuser() && is_enrolled($context, $USER, '', true);
        $access = $enrolled ? 'enrolled' : self::access($course->id);
        return [
            'id' => (int) $course->id,
            'name' => $course->get_formatted_fullname(),
            'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'image' => $image instanceof \moodle_url ? $image->out(false) : (string) $image,
            'category' => $category ? $category->get_formatted_name() : '',
            'summary' => $summary,
            'contacts' => implode(', ', array_unique($contacts)),
            'hidden' => empty($course->visible),
            'access' => $access ? get_string('catalogue_access_' . $access, 'theme_epure') : '',
            'accessclass' => $access,
            'enrolled' => $enrolled,
        ];
    }

    /**
     * How a user who is not enrolled can enter a course: by enrolling themselves, as a guest, or neither.
     *
     * @param int $courseid Course.
     * @return string self, guest, or empty.
     */
    public static function access(int $courseid): string {
        $guest = false;
        foreach (enrol_get_instances($courseid, true) as $instance) {
            if (!enrol_is_enabled($instance->enrol)) {
                continue;
            }
            // The self enrolment counts when it takes new enrolments now, as Moodle checks it.
            if ($instance->enrol === 'self' && enrol_get_plugin('self')->can_self_enrol($instance, false) === true) {
                return 'self';
            }
            $guest = $guest || $instance->enrol === 'guest';
        }
        return $guest ? 'guest' : '';
    }
}
