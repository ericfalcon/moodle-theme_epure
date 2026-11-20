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
 * Backup of the data of Épure kept for a course.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The points of accessibility of a course confirmed by its teachers in the guide « Accessibility of the course »
 * (subtitles of a video, accessible document…), backed up with the course: a course restored, duplicated or imported
 * keeps them. They tell about the content of the course; who confirmed each point is a data of a user, kept only when
 * the users are backed up. The scores of the course are not backed up: they are read again from the course restored.
 *
 * Moodle backs up the data of every theme, not only the theme of the course: the points are kept whatever the theme.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_theme_epure_plugin extends backup_theme_plugin {
    /**
     * The points confirmed, under the course.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $checks = new backup_nested_element('a11ychecks');
        $check = new backup_nested_element('a11ycheck', ['id'], ['cmid', 'checkkey', 'userid', 'timecreated']);
        $plugin->add_child($wrapper);
        $wrapper->add_child($checks);
        $checks->add_child($check);

        $users = (bool) $this->get_setting_value('users');
        $check->set_source_sql(
            'SELECT id, cmid, checkkey, ' . ($users ? 'userid' : '0 AS userid') . ', timecreated
               FROM {theme_epure_a11ycheck}
              WHERE courseid = ?',
            [backup::VAR_COURSEID]
        );
        if ($users) {
            $check->annotate_ids('user', 'userid');
        }
        return $plugin;
    }
}
