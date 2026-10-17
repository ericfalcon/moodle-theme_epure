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

use theme_epure\usertours\audience_filter;
use tool_usertours\tour;

#[\PHPUnit\Framework\Attributes\CoversClass(onboarding::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(audience_filter::class)]
/**
 * Tests for getting started by role: the user tours of the learner, the teacher and the manager.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\onboarding
 * @covers     \theme_epure\usertours\audience_filter
 */
final class onboarding_test extends \advanced_testcase {
    /**
     * The tours of the theme, by audience.
     *
     * @return tour[]
     */
    protected function tours(): array {
        $ids = json_decode((string) get_config('theme_epure', 'onboardingtours'), true) ?: [];
        return array_map(fn($id) => tour::instance((int) $id), $ids);
    }

    /**
     * The tours are created with the theme, only with Épure and for their audience, once; they go with the theme.
     */
    public function test_install_and_remove(): void {
        $this->resetAfterTest();
        // Created when the theme was installed.
        $tours = $this->tours();
        $this->assertEquals(['learner', 'teacher', 'manager'], array_keys($tours));
        foreach ($tours as $audience => $tour) {
            $this->assertEquals(['epure'], $tour->get_filter_values('theme'));
            $this->assertEquals([$audience], $tour->get_filter_values('epure_audience'));
            $this->assertEquals(onboarding::TOURS[$audience]['pathmatch'], $tour->get_pathmatch());
            $this->assertCount(count(onboarding::TOURS[$audience]['steps']), $tour->get_steps());
        }
        // Its strings exist in English and in French.
        foreach (onboarding::TOURS as $audience => $definition) {
            foreach (array_keys($definition['steps']) as $step) {
                foreach (['title', 'content'] as $part) {
                    $this->assertTrue(get_string_manager()->string_exists("tour_{$audience}_{$step}_{$part}", 'theme_epure'));
                }
            }
        }

        // Once only: an administrator may delete them.
        $count = \core\di::get(\moodle_database::class)->count_records('tool_usertours_tours');
        onboarding::install();
        $this->assertEquals($count, \core\di::get(\moodle_database::class)->count_records('tool_usertours_tours'));

        // Gone with the theme, even when one was deleted already.
        $tours['teacher']->remove();
        onboarding::remove();
        $this->assertFalse(get_config('theme_epure', 'onboardingtours'));
        $this->assertEquals($count - 3, \core\di::get(\moodle_database::class)->count_records('tool_usertours_tours'));

        onboarding::install();
        $this->assertCount(3, $this->tours());
        // Disabled in the automated tests, as Moodle's own tours, where they would cover the pages.
        foreach ($this->tours() as $tour) {
            $this->assertFalse($tour->is_enabled());
        }
    }

    /**
     * The audiences: the manager, the teacher of a course (or of any course outside them), the learner.
     */
    public function test_audiences(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $other = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $generator->enrol_user($teacher->id, $other->id, 'student');

        $coursepage = \context_course::instance($course->id);
        $otherpage = \context_course::instance($other->id);
        $dashboard = \context_user::instance($learner->id);

        $this->setUser($learner);
        $this->assertEquals(['learner'], onboarding::audiences($coursepage, (int) $learner->id));
        $this->assertEquals(['learner'], onboarding::audiences($dashboard, (int) $learner->id));

        $this->setUser($teacher);
        $this->assertEquals(['teacher'], onboarding::audiences($coursepage, (int) $teacher->id));
        $this->assertEquals(['learner'], onboarding::audiences($otherpage, (int) $teacher->id));
        $this->assertEquals(['teacher'], onboarding::audiences($dashboard, (int) $teacher->id));

        $this->setAdminUser();
        $this->assertContains('manager', onboarding::audiences($dashboard, (int) get_admin()->id));

        $this->setGuestUser();
        $this->assertEquals([], onboarding::audiences($dashboard, (int) guest_user()->id));
    }

    /**
     * The filter matches the tours of the audience of the user, and every tour without audience.
     */
    public function test_filter(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $context = \context_course::instance($course->id);
        $tours = $this->tours();

        $this->setUser($learner);
        $this->assertTrue(audience_filter::filter_matches($tours['learner'], $context));
        $this->assertFalse(audience_filter::filter_matches($tours['teacher'], $context));
        $this->assertFalse(audience_filter::filter_matches($tours['manager'], $context));
        $this->assertTrue(audience_filter::filter_matches(new tour(), $context));

        // Offered to the tours by the hook.
        $this->assertContains(audience_filter::class, \tool_usertours\helper::get_all_filters());
    }
}
