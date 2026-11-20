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

/**
 * Restore of the data of Épure kept for a course.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The points of accessibility confirmed by the teachers of a course, restored with it. Read with the course, before
 * its activities are restored: they are written at the end of the restore, each with its activity in the course
 * restored (a point of an activity not restored is left out) and its teacher when the users were restored (else none:
 * the guide says « Unknown user »). A point the course already has (a restore into an existing course) is kept once.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_theme_epure_plugin extends restore_theme_plugin {
    /** @var stdClass[] Points read from the backup, written at the end of the restore. */
    protected $epurechecks = [];

    /**
     * The points confirmed, under the course.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [new restore_path_element('a11ycheck', $this->get_pathfor('/a11ychecks/a11ycheck'))];
    }

    /**
     * Keeps a point until the activities are restored.
     *
     * @param array|stdClass $data The point, as backed up.
     */
    public function process_a11ycheck($data) {
        $this->epurechecks[] = (object) $data;
    }

    /**
     * Writes the points, once the activities of the course are restored.
     */
    public function after_restore_course() {
        global $DB;
        $courseid = (int) $this->task->get_courseid();
        $users = (bool) $this->get_setting_value('users');
        foreach ($this->epurechecks as $data) {
            $cmid = 0;
            if (!empty($data->cmid)) {
                $cmid = (int) $this->get_mappingid('course_module', $data->cmid);
                if (!$cmid) {
                    continue;
                }
            }
            $userid = $users && !empty($data->userid) ? (int) $this->get_mappingid('user', $data->userid) : 0;
            $point = ['courseid' => $courseid, 'cmid' => $cmid, 'checkkey' => (string) $data->checkkey];
            if ($DB->record_exists(\theme_epure\course_accessibility::TABLE, $point)) {
                continue;
            }
            $DB->insert_record(\theme_epure\course_accessibility::TABLE, $point + [
                'userid' => $userid,
                'timecreated' => (int) $data->timecreated,
            ]);
        }
        $this->epurechecks = [];
    }
}
