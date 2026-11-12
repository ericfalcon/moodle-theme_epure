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

#[\PHPUnit\Framework\Attributes\CoversClass(content_alt::class)]
/**
 * Tests for the text alternatives written from the guide « Accessibility of the course ».
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\content_alt
 */
final class content_alt_test extends \advanced_testcase {
    /**
     * The images without a text alternative of a page are found, described or marked decorative from the guide, and
     * nothing else of the text changes; an image already described is left alone.
     */
    public function test_apply(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $html = '<p class="x">Plan <img src="@@PLUGINFILE@@/plan.png" width="20"> et <img src="https://example.org/b.png" alt="B">'
            . ' puis <img src="@@PLUGINFILE@@/deco.png" /></p>';
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'content' => $html,
            'contentformat' => FORMAT_HTML]);
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        $this->assertSame(2, $part['issues']['images']);

        $images = content_alt::images($part, $course);
        $this->assertSame(['plan.png', 'deco.png'], array_column($images, 'filename'));
        $this->assertStringContainsString('/mod_page/content/', $images[0]['url']);

        $done = content_alt::apply($course, $parts, [$images[0]['key'] => 'Plan du site « nord »'], [$images[1]['key'] => true]);
        $this->assertSame(2, $done);
        $content = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertSame('<p class="x">Plan <img src="@@PLUGINFILE@@/plan.png" width="20" alt="Plan du site « nord »"> et '
            . '<img src="https://example.org/b.png" alt="B"> puis <img src="@@PLUGINFILE@@/deco.png" alt="" /></p>', $content);
        // The guide reads the course again: nothing left to describe.
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        $this->assertSame(0, $part['issues']['images'] ?? 0);
    }

    /**
     * A key of an image whose text changed meanwhile, an empty text alternative, a teacher without the right to change
     * the activity: nothing written.
     */
    public function test_apply_refused(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id,
            'content' => '<p><img src="@@PLUGINFILE@@/a.png"></p>', 'contentformat' => FORMAT_HTML]);
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        $key = content_alt::images($part, $course)[0]['key'];

        $this->assertSame(0, content_alt::apply($course, $parts, [$key => '   '], []));
        // Another image now in its place.
        $DB->set_field('page', 'content', '<p><img src="@@PLUGINFILE@@/other.png"></p>', ['id' => $page->id]);
        $this->assertSame(0, content_alt::apply($course, $parts, [$key => 'A'], []));
        $DB->set_field('page', 'content', '<p><img src="@@PLUGINFILE@@/a.png"></p>', ['id' => $page->id]);
        // A learner of the course.
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));
        $this->assertSame(0, content_alt::apply($course, $parts, [$key => 'A'], []));
        $this->assertSame('<p><img src="@@PLUGINFILE@@/a.png"></p>', $DB->get_field('page', 'content', ['id' => $page->id]));
    }
}
