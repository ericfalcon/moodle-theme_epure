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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Tests of the backup and restore of the points of accessibility confirmed in a course.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_theme_epure_plugin
 * @covers     \restore_theme_epure_plugin
 */
final class backup_test extends \advanced_testcase {
    /**
     * A course with a page, a point confirmed for the course and one for the page, by a teacher.
     *
     * @return array course, page, teacher.
     */
    protected function course(): array {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        foreach ([[0, 'instructions'], [(int) $page->cmid, 'video:0123456789abcdef']] as [$cmid, $key]) {
            $DB->insert_record(course_accessibility::TABLE, ['courseid' => $course->id, 'cmid' => $cmid,
                'checkkey' => $key, 'userid' => $teacher->id, 'timecreated' => 1700000000]);
        }
        return [$course, $page, $teacher];
    }

    /**
     * Backs a course up and restores it as a new course.
     *
     * @param int $courseid Course.
     * @param bool $users Whether the users are backed up.
     * @return int The new course.
     */
    protected function backup_and_restore(int $courseid, bool $users): int {
        global $USER;
        [$type, $format, $interactive, $mode] = [\backup::TYPE_1COURSE, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL];
        $controller = new \backup_controller($type, $courseid, $format, $interactive, $mode, $USER->id);
        $controller->get_plan()->get_setting('users')->set_value($users);
        $controller->execute_plan();
        $file = $controller->get_results()['backup_destination'];
        $folder = 'epure-' . uniqid();
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), make_backup_temp_directory($folder));
        $controller->destroy();

        $category = (int) $this->getDataGenerator()->create_category()->id;
        $newid = (int) \restore_dbops::create_new_course('Restored', 'restored-' . uniqid(), $category);
        $target = \backup::TARGET_NEW_COURSE;
        $controller = new \restore_controller($folder, $newid, $interactive, $mode, $USER->id, $target);
        $controller->execute_precheck();
        $controller->execute_plan();
        $controller->destroy();
        return $newid;
    }

    /**
     * With the users: the points, their activity in the new course, the teacher who confirmed them.
     */
    public function test_restore_with_users(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, $page, $teacher] = $this->course();
        $newid = $this->backup_and_restore((int) $course->id, true);

        $points = $DB->get_records(course_accessibility::TABLE, ['courseid' => $newid], 'checkkey');
        $this->assertCount(2, $points);
        $module = $DB->get_field('modules', 'id', ['name' => 'page']);
        $newpage = $DB->get_field('course_modules', 'id', ['course' => $newid, 'module' => $module]);
        $bykey = array_column($points, null, 'checkkey');
        $this->assertEquals(0, $bykey['instructions']->cmid);
        $this->assertEquals($newpage, $bykey['video:0123456789abcdef']->cmid);
        $this->assertNotEquals($page->cmid, $newpage);
        $this->assertEquals($teacher->id, $bykey['instructions']->userid);
        $this->assertEquals(1700000000, $bykey['instructions']->timecreated);
        // The points of the course backed up are untouched.
        $this->assertSame(2, $DB->count_records(course_accessibility::TABLE, ['courseid' => $course->id]));
    }

    /**
     * Without the users: the points are kept, without who confirmed them.
     */
    public function test_restore_without_users(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course] = $this->course();
        $newid = $this->backup_and_restore((int) $course->id, false);

        $points = $DB->get_records(course_accessibility::TABLE, ['courseid' => $newid]);
        $this->assertCount(2, $points);
        foreach ($points as $point) {
            $this->assertEquals(0, $point->userid);
        }
    }
}
