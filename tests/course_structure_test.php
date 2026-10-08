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

#[\PHPUnit\Framework\Attributes\CoversClass(course_structure::class)]
/**
 * Tests for the order of the activities of a course with subsections.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\course_structure
 */
final class course_structure_test extends \advanced_testcase {
    /**
     * A course with, in its first section, an activity, a subsection with an activity, and another activity; and an
     * activity in its second section.
     *
     * @return array{0: \stdClass, 1: array<string, \stdClass>} The course, and its activities by name.
     */
    protected function create_course(): array {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablecompletion = true;
        \core\plugininfo\mod::enable_plugin('subsection', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 2, 'enablecompletion' => 1]);
        $page = fn(string $name, int $section) => $generator->create_module('page', ['course' => $course->id,
            'section' => $section, 'name' => $name, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $cms = ['A' => $page('A', 1)];
        $cms['Part'] = $generator->create_module('subsection', ['course' => $course->id, 'section' => 1, 'name' => 'Part']);
        $delegated = get_fast_modinfo($course)->get_cm($cms['Part']->cmid)->get_delegated_section_info();
        $cms['B'] = $page('B', $delegated->section);
        $cms['C'] = $page('C', 1);
        $cms['D'] = $page('D', 2);
        return [$course, $cms];
    }

    /**
     * The activities of a subsection come at the place of the subsection, which is left out.
     */
    public function test_cms(): void {
        [$course] = $this->create_course();
        $names = array_map(fn($cm) => $cm->name, array_values(course_structure::cms(get_fast_modinfo($course))));
        $this->assertSame(['A', 'B', 'C', 'D'], $names);
    }

    /**
     * An activity of a subsection is placed in its section, then its subsection.
     */
    public function test_place_name(): void {
        [$course, $cms] = $this->create_course();
        $modinfo = get_fast_modinfo($course);
        [$section, $subsection] = course_structure::sections_of($modinfo->get_cm($cms['B']->cmid));
        $this->assertSame(1, (int) $section->section);
        $this->assertSame('Part', $subsection->name);
        $this->assertStringEndsWith(' › Part', course_structure::place_name($modinfo->get_cm($cms['B']->cmid)));
        $this->assertSame([$modinfo->get_section_info(1), null], course_structure::sections_of($modinfo->get_cm($cms['A']->cmid)));
    }

    /**
     * The previous and next activities, the next activity to do and the progress of the sections follow the order of
     * the course page.
     */
    public function test_course_order(): void {
        global $PAGE;
        [$course, $cms] = $this->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);
        $modinfo = get_fast_modinfo($course);

        $navigation = activity_page::navigation($modinfo->get_cm($cms['B']->cmid));
        $this->assertSame('A', $navigation['previous']['name']);
        $this->assertSame('C', $navigation['next']['name']);
        $navigation = activity_page::navigation($modinfo->get_cm($cms['C']->cmid));
        $this->assertSame('B', $navigation['previous']['name']);
        $this->assertSame('D', $navigation['next']['name']);

        $completion = new \completion_info($course);
        $completion->update_state($modinfo->get_cm($cms['A']->cmid), COMPLETION_COMPLETE, $user->id);
        $card = mycourses::export($PAGE->get_renderer('core'))['sections'][0]['courses'][0];
        $this->assertSame('B', $card['next']['name']);

        // The first section counts the activity of its subsection; the subsection has its own progress.
        $sections = array_column(course_page::sections($course, (int) $user->id), null, 'id');
        $first = $modinfo->get_section_info(1);
        $part = $modinfo->get_cm($cms['Part']->cmid)->get_delegated_section_info();
        $this->assertSame([1, 3], [$sections[$first->id]['done'], $sections[$first->id]['total']]);
        $this->assertSame([0, 1], [$sections[$part->id]['done'], $sections[$part->id]['total']]);
    }
}
