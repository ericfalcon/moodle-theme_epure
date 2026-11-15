<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * Tests for the activities added since the previous visit of a learner.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\new_activities
 */
final class new_activities_test extends \advanced_testcase {
    /**
     * The activities added since the previous visit, that the learner can open; none for a course never visited.
     */
    public function test_course(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $old = $generator->create_module('page', ['course' => $course->id, 'name' => 'Welcome']);
        $new = $generator->create_module('page', ['course' => $course->id, 'name' => 'Fire procedure']);
        $generator->create_module('label', ['course' => $course->id]);
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Hidden', 'visible' => 0]);
        $DB->set_field('course_modules', 'added', time() - 30 * DAYSECS, ['id' => $old->cmid]);
        rebuild_course_cache($course->id, true);

        $this->setUser($learner);
        $this->assertNull(new_activities::since((int) $course->id));
        $this->assertSame([], new_activities::course($course), 'Never visited: nothing is new.');

        // The access before the session, as Moodle reads it at the login.
        $USER->lastcourseaccess[$course->id] = time() - 7 * DAYSECS;
        $this->assertSame([(int) $new->cmid], new_activities::course($course));
        $this->assertSame([], new_activities::cmids($course, time() + 10));
    }

    /**
     * The text of the card.
     */
    public function test_count_text(): void {
        $this->assertNull(new_activities::count_text(0));
        $this->assertSame(get_string('new_one', 'theme_epure'), new_activities::count_text(1));
        $this->assertSame(get_string('new_many', 'theme_epure', 3), new_activities::count_text(3));
    }
}
