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

#[\PHPUnit\Framework\Attributes\CoversClass(activity_page::class)]
/**
 * Tests for the strip and the navigation of the pages of the activities.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\activity_page
 */
final class activity_page_test extends \advanced_testcase {
    /**
     * Page of an activity of a course.
     *
     * @param \stdClass $course Course.
     * @param \stdClass $module Activity.
     * @return \moodle_page
     */
    protected function activity_page(\stdClass $course, \stdClass $module): \moodle_page {
        $page = new \moodle_page();
        $page->set_cm(get_fast_modinfo($course)->get_cm($module->cmid), $course);
        $page->set_pagelayout('incourse');
        return $page;
    }

    /**
     * The strip and the navigation are on the pages of the activities, unless turned off.
     */
    public function test_applies(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->assertTrue(activity_page::applies($this->activity_page($course, $page)));

        $settings = $this->activity_page($course, $page);
        $settings->set_pagelayout('admin');
        $this->assertFalse(activity_page::applies($settings));

        $coursepage = new \moodle_page();
        $coursepage->set_course($course);
        $coursepage->set_pagelayout('course');
        $this->assertFalse(activity_page::applies($coursepage));

        set_config('activitynav', '0', 'theme_epure');
        $this->assertFalse(activity_page::applies($this->activity_page($course, $page)));
    }

    /**
     * The previous and next activities skip labels and hidden activities; the last one leads back to the course.
     */
    public function test_navigation(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 2], ['createsections' => true]);
        $first = $generator->create_module('page', ['course' => $course->id, 'name' => 'First', 'section' => 1]);
        $generator->create_module('label', ['course' => $course->id, 'section' => 1]);
        $hidden = $generator->create_module('page', ['course' => $course->id, 'name' => 'Hidden', 'section' => 1,
            'visible' => 0]);
        $second = $generator->create_module('page', ['course' => $course->id, 'name' => 'Second', 'section' => 2]);
        $learner = $generator->create_and_enrol($course, 'student');
        $this->setUser($learner);
        $modinfo = get_fast_modinfo($course);

        $navigation = activity_page::navigation($modinfo->get_cm($first->cmid));
        $this->assertNull($navigation['previous']);
        $this->assertEquals('Second', $navigation['next']['name']);
        $this->assertStringContainsString('id=' . $second->cmid, $navigation['next']['url']);

        $navigation = activity_page::navigation($modinfo->get_cm($second->cmid));
        $this->assertEquals('First', $navigation['previous']['name']);
        $this->assertNull($navigation['next']);
        $this->assertStringContainsString('/course/view.php', $navigation['courseurl']);

        // A teacher also sees the hidden activity.
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));
        $navigation = activity_page::navigation(get_fast_modinfo($course)->get_cm($first->cmid));
        $this->assertEquals('Hidden', $navigation['next']['name']);
        $this->assertNotEmpty($hidden);
    }

    /**
     * The strip gives the position of the activity and, to a learner, their progress.
     */
    public function test_strip(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Safety', 'enablecompletion' => 1]);
        $modules = [];
        foreach (['One', 'Two'] as $name) {
            $modules[] = $generator->create_module('page', ['course' => $course->id, 'name' => $name,
                'completion' => COMPLETION_TRACKING_MANUAL]);
        }
        $learner = $generator->create_and_enrol($course, 'student');
        $this->setUser($learner);
        $cm = get_fast_modinfo($course)->get_cm($modules[1]->cmid);
        (new \completion_info($course))->update_state(
            get_fast_modinfo($course)->get_cm($modules[0]->cmid),
            COMPLETION_COMPLETE,
            $learner->id
        );

        $strip = activity_page::strip($cm, (int) $learner->id);
        $this->assertEquals('Safety', $strip['course']);
        $this->assertEquals(
            get_string('activityposition', 'theme_epure', (object) ['position' => 2, 'total' => 2]),
            $strip['position']
        );
        $this->assertTrue($strip['hasprogress']);
        $this->assertEquals(50, $strip['progress']);
        $this->assertFalse($strip['focus']);

        set_user_preference('theme_epure_focus', 1, $learner);
        $this->assertTrue(activity_page::strip($cm, (int) $learner->id)['focus']);

        // A teacher sees no progress.
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $this->assertFalse(activity_page::strip($cm, (int) $teacher->id)['hasprogress']);
    }
}
