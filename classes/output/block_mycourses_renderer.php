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

/**
 * Renderer of IOMAD's « My courses » block (block_mycourses, up to IOMAD 5.0), replaced by Épure's page by role
 * ({@see iomad_mycourses}). This class is only loaded on IOMAD sites.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_mycourses_renderer extends \block_mycourses\output\renderer {
    use iomad_mycourses;

    /**
     * Renders IOMAD's « My courses » block.
     *
     * @param \block_mycourses\output\main $main The block.
     * @return string HTML.
     */
    public function render_main(\block_mycourses\output\main $main) {
        if (get_config('theme_epure', 'mycoursesbyrole') === '0') {
            return parent::render_main($main);
        }
        return $this->render_by_role($main->export_for_template($this));
    }
}
