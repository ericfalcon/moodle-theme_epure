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
 * Renders IOMAD's « My courses » block as Épure's page by role: the courses the user teaches and the courses the
 * user takes in two sections, like with Moodle's block, plus the courses the user can start (« Available
 * courses » of IOMAD) and IOMAD's button to download the certificates.
 *
 * The block is block_mycourses up to IOMAD 5.0 and block_iomad_mycourses from IOMAD 5.1, with the same data: both
 * renderers of the theme use this trait.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait iomad_mycourses {
    /**
     * The block as the page by role, from the data of IOMAD's block.
     *
     * @param array $data Data of IOMAD's block (main::export_for_template()).
     * @return string HTML.
     */
    protected function render_by_role(array $data): string {
        $available = [];
        foreach ($data['availableview']['courses'] ?? [] as $course) {
            $available[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'url' => $course->url instanceof \moodle_url ? $course->url->out(false) : (string) $course->url,
                'image' => $course->image instanceof \moodle_url ? $course->image->out(false) : (string) $course->image,
                'coursecategory' => $course->coursecategory ?? '',
            ];
        }
        $this->page->requires->js_call_amd('theme_epure/mycourses', 'init');
        return $this->render_from_template('theme_epure/mycourses', mycourses::export($this, null, [
            'available' => $available,
            'downloadcerts' => !empty($data['downloadcerts']) ? $data['downloadcertslink'] : null,
        ]));
    }
}
