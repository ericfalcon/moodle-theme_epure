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

namespace theme_epure\output;

use theme_epure\mycourses;

/**
 * Renderer of the « My courses » block (block_myoverview), replaced by Épure's page by role.
 *
 * The courses the user teaches and the courses the user takes are shown in two sections, with
 * different cards. The theme setting « My courses by role » brings back Moodle's own block.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_myoverview_renderer extends \block_myoverview\output\renderer {
    /**
     * Renders the « My courses » block.
     *
     * @param \block_myoverview\output\main $main The block.
     * @return string HTML.
     */
    public function render_main(\block_myoverview\output\main $main) {
        if (!\theme_epure\company_style::enabled('mycoursesbyrole')) {
            return parent::render_main($main);
        }
        $this->page->requires->js_call_amd('theme_epure/mycourses', 'init');
        return $this->render_from_template('theme_epure/mycourses', mycourses::export($this));
    }
}
