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

namespace theme_epure\output\core;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/renderer.php');

use theme_epure\catalogue;

/**
 * Renderer of the lists of courses: the catalogue of Épure, with the courses as cards.
 *
 * The theme setting « Catalogue of the courses » brings back Moodle's lists.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends \core_course_renderer {
    /**
     * Page of a course category (course/index.php): its courses and the courses of its subcategories as cards.
     *
     * The action bar of Moodle stays at the top, with the menu of the categories, the search and the actions.
     *
     * @param int|\stdClass|\core_course_category $category Category.
     * @return string HTML.
     */
    public function course_category($category) {
        if (!catalogue::enabled()) {
            return parent::course_category($category);
        }
        if (empty($category)) {
            $coursecat = \core_course_category::user_top();
        } else if ($category instanceof \core_course_category) {
            $coursecat = $category;
        } else {
            $coursecat = \core_course_category::get(is_object($category) ? $category->id : $category);
        }
        $this->page->set_title(get_string($coursecat->id ? 'fulllistofcourses' : 'categories'));
        $actionbar = new \core_course\output\category_action_bar($this->page, $coursecat);
        return $this->render_from_template('core_course/category_actionbar', $actionbar->export_for_template($this))
            . $this->render_from_template(
                'theme_epure/catalogue',
                catalogue::export($coursecat, $this->output, optional_param('page', 0, PARAM_INT))
            );
    }

    /**
     * A course in a list of courses: as a card in the expanded lists (search, available courses).
     *
     * The collapsed lists, such as the tree of the categories on the front page, keep Moodle's display.
     *
     * @param \coursecat_helper $chelper Display options.
     * @param \core_course_list_element|\stdClass $course Course.
     * @param string $additionalclasses Extra classes.
     * @return string HTML.
     */
    protected function coursecat_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
        if (!catalogue::enabled() || $chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            return parent::coursecat_coursebox($chelper, $course, $additionalclasses);
        }
        return $this->render_from_template('theme_epure/catalogue_card', catalogue::card($course, $this->output));
    }

    /**
     * Box of a course on its enrolment page: left out, as the presentation of Épure is above.
     *
     * @param \stdClass $course Course.
     * @return string HTML.
     */
    public function course_info_box(\stdClass $course) {
        if (\theme_epure\course_presentation::applies($this->page)) {
            return '';
        }
        return parent::course_info_box($course);
    }
}
