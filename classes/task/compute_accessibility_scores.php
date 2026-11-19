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

namespace theme_epure\task;

use theme_epure\accessibility_scores;

/**
 * Reads the courses whose score of accessibility of the week is not known, or which changed, for the management
 * dashboard ({@see accessibility_scores}). Nothing when the guide « Accessibility of the course » is off.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compute_accessibility_scores extends \core\task\scheduled_task {
    /**
     * Name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_a11yscores', 'theme_epure');
    }

    /**
     * Reads the courses.
     */
    public function execute(): void {
        if (get_config('theme_epure', 'courseaccessibility') === '0') {
            mtrace('The guide « Accessibility of the course » is off: no score.');
            return;
        }
        mtrace('Courses read for their score of accessibility: ' . accessibility_scores::compute());
    }
}
