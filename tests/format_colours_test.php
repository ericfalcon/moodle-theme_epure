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

/**
 * Tests of the colour of the active tab of the course format Section tabs.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\format_colours
 */
final class format_colours_test extends \advanced_testcase {
    /**
     * No CSS for a course in another format, nor for a course of Section tabs without a colour of its own.
     */
    public function test_none(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'topics']);
        $this->assertSame('', format_colours::css($course));
        $this->assertSame('', format_colours::css(null));
        if (!\core_component::get_component_directory('format_sectiontabs')) {
            return;
        }
        $course = $this->getDataGenerator()->create_course(['format' => 'sectiontabs']);
        $this->assertSame('', format_colours::css(get_course($course->id)));
    }

    /**
     * The colour of the course, moved as little as needed for its text to reach 4.5:1, in light mode and under the cream
     * overlay.
     */
    public function test_readable(): void {
        if (!\core_component::get_component_directory('format_sectiontabs')) {
            $this->markTestSkipped('The course format Section tabs is not installed.');
        }
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'sectiontabs', 'gtabsactivecolor' => '#d35400']);
        $css = format_colours::css(get_course($course->id));
        $this->assertMatchesRegularExpression(
            '/^\.gtabs[^{]*\{ --gtabs-active: (#[0-9A-F]{6}) !important; --gtabs-active-text: (#[0-9A-F]{6}) !important; \}'
                . ' html\.epure-a11y-scheme-cream :is\([^)]*\) \{ --gtabs-active: (#[0-9A-F]{6}) !important;'
                . ' --gtabs-active-text: (#[0-9A-F]{6}) !important; \}$/',
            $css
        );
        preg_match_all('/#[0-9A-F]{6}/', $css, $colours);
        [$fill, $on, $creamfill, $creamon] = $colours[0];
        $this->assertGreaterThanOrEqual(4.5, palette::contrast($fill, $on));
        $this->assertGreaterThanOrEqual(4.5, palette::contrast(palette::under_cream($creamfill), palette::under_cream($creamon)));
        // Kept close to the colour of the course.
        $this->assertLessThan(1.2, palette::contrast($fill, '#D35400'));
    }
}
