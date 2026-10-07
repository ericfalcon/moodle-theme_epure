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

use theme_epure\busy;

/**
 * Renderer of the maintenance layout, used by the installation and upgrade pages (validation of a
 * plugin ZIP file, plugins check, upgrade): adds the « Moodle is working » indicator.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer_maintenance extends \core_renderer_maintenance {
    /**
     * Top of the page: the message shown while the server sends the page, as during an upgrade.
     *
     * @return string HTML.
     */
    public function standard_top_of_body_html() {
        return parent::standard_top_of_body_html() . busy::top_of_body($this->page);
    }

    /**
     * End of the page: the overlay shown once a step is started.
     *
     * @return string HTML.
     */
    public function standard_end_of_body_html() {
        return parent::standard_end_of_body_html() . busy::end_of_body($this->page);
    }
}
