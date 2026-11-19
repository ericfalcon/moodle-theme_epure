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
 * Computes again the score of accessibility of a course that changed, a few minutes after its last change
 * ({@see accessibility_scores::queue()}). Nothing when the course was deleted meanwhile or the guide is off.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_accessibility_score extends \core\task\adhoc_task {
    /**
     * Name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_a11yscore', 'theme_epure');
    }

    /**
     * Reads the course.
     */
    public function execute(): void {
        global $DB;
        $courseid = (int) ($this->get_custom_data()->courseid ?? 0);
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || !accessibility_scores::active()) {
            return;
        }
        accessibility_scores::store($course, time());
        mtrace('Score of accessibility of the course ' . $courseid . ' computed again.');
    }
}
