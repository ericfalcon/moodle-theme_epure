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

#[\PHPUnit\Framework\Attributes\CoversClass(catalogue::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(course_presentation::class)]
/**
 * Tests for the catalogue of the courses and the presentation of a course on its enrolment page.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\catalogue
 * @covers     \theme_epure\course_presentation
 */
final class catalogue_test extends \advanced_testcase {
    /**
     * The catalogue lists the courses of a category and of its subcategories, with the subcategories as chips.
     */
    public function test_export(): void {
        global $PAGE;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $top = $generator->create_category(['name' => 'Health']);
        $sub = $generator->create_category(['name' => 'First aid', 'parent' => $top->id]);
        $generator->create_course(['category' => $top->id, 'fullname' => 'Safety', 'summary' => '<p>The risks.</p>']);
        $generator->create_course(['category' => $sub->id, 'fullname' => 'Defibrillator']);
        $generator->create_course(['category' => $sub->id, 'fullname' => 'Hidden', 'visible' => 0]);
        $this->setUser($generator->create_user());

        $data = catalogue::export(\core_course_category::get($top->id), $PAGE->get_renderer('core'));
        $this->assertEquals(['Safety', 'Defibrillator'], array_column($data['courses'], 'name'));
        $this->assertEquals('The risks.', $data['courses'][0]['summary']);
        $this->assertEquals(get_string('catalogue_count', 'theme_epure', 2), $data['count']);
        $this->assertEquals('First aid', $data['categories'][0]['name']);
        $this->assertEquals(1, $data['categories'][0]['count']);
        $this->assertEquals('', $data['paging']);

        $data = catalogue::export(\core_course_category::get($sub->id), $PAGE->get_renderer('core'));
        $this->assertEquals(['Defibrillator'], array_column($data['courses'], 'name'));
        $this->assertEquals('Health', $data['parent']['name']);
        $this->assertFalse($data['hascategories']);
    }

    /**
     * A card says whether the user is enrolled, or how they can enter the course.
     */
    public function test_card_access(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $this->setUser($user);
        $output = $PAGE->get_renderer('core');
        $this->assertEquals('', catalogue::card(get_course($course->id), $output)['accessclass']);

        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['courseid' => $course->id, 'enrol' => 'guest']);
        $this->assertEquals('guest', catalogue::card(get_course($course->id), $output)['accessclass']);

        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['courseid' => $course->id, 'enrol' => 'self']);
        $DB->set_field('enrol', 'customint6', 1, ['courseid' => $course->id, 'enrol' => 'self']);
        $this->assertEquals('self', catalogue::card(get_course($course->id), $output)['accessclass']);

        $generator->enrol_user($user->id, $course->id, 'student');
        $card = catalogue::card(get_course($course->id), $output);
        $this->assertTrue($card['enrolled']);
        $this->assertEquals(get_string('catalogue_access_enrolled', 'theme_epure'), $card['access']);
    }

    /**
     * The presentation gives the outline of the course, without the hidden activities, and its teachers.
     */
    public function test_presentation(): void {
        global $PAGE;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            ['numsections' => 2, 'summary' => '<p>About safety.</p>'],
            ['createsections' => true]
        );
        $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $generator->create_module('quiz', ['course' => $course->id, 'section' => 2]);
        $generator->create_module('quiz', ['course' => $course->id, 'section' => 2, 'visible' => 0]);
        $generator->create_module('label', ['course' => $course->id, 'section' => 2]);
        $teacher = $generator->create_user(['firstname' => 'Tom', 'lastname' => 'Teacher']);
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($generator->create_user());

        $data = course_presentation::export(get_course($course->id), $PAGE->get_renderer('core'), '');
        $this->assertEquals([
            get_string('catalogue_activities', 'theme_epure', 2),
            get_string('catalogue_activities_one', 'theme_epure', 1),
        ], [$data['outline'][0]['count'], $data['outline'][1]['count']]);
        $this->assertStringContainsString('About safety.', $data['fullsummary']);
        $this->assertEquals('Tom Teacher', $data['teachers'][0]['name']);
        $this->assertEquals([2, 1], array_column($data['contents'], 'count'));
    }

    /**
     * The presentation is on the enrolment page of the courses, unless the catalogue is turned off.
     */
    public function test_applies(): void {
        $this->resetAfterTest();
        $page = new \moodle_page();
        $page->set_course($this->getDataGenerator()->create_course());
        $page->set_pagetype('enrol-index');
        $this->assertTrue(course_presentation::applies($page));
        set_config('catalogue', '0', 'theme_epure');
        $this->assertFalse(course_presentation::applies($page));
    }
}
