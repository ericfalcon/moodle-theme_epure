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

#[\PHPUnit\Framework\Attributes\CoversClass(content_fix::class)]
/**
 * Tests for the frames, the links of images and the tables corrected from the guide « Accessibility of the course ».
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\content_fix
 */
final class content_fix_test extends \advanced_testcase {
    /** @var string A text with a frame without a title, a link made of an image, a table without a header. */
    private const HTML = '<p><iframe src="https://www.youtube.com/embed/abc" width="560"></iframe></p>'
        . '<p><a href="https://example.org/programme"><img src="@@PLUGINFILE@@/logo.png"></a></p>'
        . '<table class="t"><tr><td>Nom</td><td>Date</td></tr><tr><td>Lyon</td><td>12/03</td></tr></table>'
        . '<table role="presentation"><tr><td>Mise en page</td></tr></table>';

    /**
     * The page of the test and its part in the guide.
     *
     * @return array Course, page, parts, part.
     */
    private function page(): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'content' => self::HTML,
            'contentformat' => FORMAT_HTML]);
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        return [$course, $page, $parts, $part];
    }

    /**
     * The frame, the link and the table are found as the guide finds them, corrected from it, and nothing else of the
     * text changes; the image of the link is not offered as an image to describe; once corrected, nothing is left.
     */
    public function test_apply(): void {
        global $DB;
        [$course, $page, $parts, $part] = $this->page();
        $this->assertSame(1, $part['issues']['frames']);
        $this->assertSame(1, $part['issues']['imagelinks']);
        $this->assertSame(1, $part['issues']['tables']);
        $this->assertSame([], content_alt::images($part, $course));

        $items = content_fix::items($part, $course);
        $this->assertSame('https://www.youtube.com/embed/abc', $items['frames'][0]['src']);
        $this->assertSame('https://example.org/programme', $items['imagelinks'][0]['src']);
        $this->assertStringContainsString('/mod_page/content/', $items['imagelinks'][0]['image']);
        $this->assertSame('Nom | Date', $items['tables'][0]['src']);
        $this->assertCount(1, $items['tables']);

        $done = content_fix::apply(
            $course,
            $parts,
            [$items['frames'][0]['key'] => 'Vidéo : les gestes « utiles »'],
            [$items['imagelinks'][0]['key'] => 'Programme de la formation'],
            [$items['tables'][0]['key'] => true]
        );
        $this->assertSame(['frames' => 1, 'imagelinks' => 1, 'tables' => 1], $done);
        $this->assertSame(
            '<p><iframe src="https://www.youtube.com/embed/abc" width="560" title="Vidéo : les gestes « utiles »"></iframe></p>'
            . '<p><a href="https://example.org/programme"><img src="@@PLUGINFILE@@/logo.png" alt="Programme de la formation">'
            . '</a></p><table class="t"><tr><th scope="col">Nom</th><th scope="col">Date</th></tr><tr><td>Lyon</td>'
            . '<td>12/03</td></tr></table><table role="presentation"><tr><td>Mise en page</td></tr></table>',
            $DB->get_field('page', 'content', ['id' => $page->id])
        );
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        foreach (content_fix::KINDS as $kind) {
            $this->assertSame(0, $part['issues'][$kind] ?? 0, $kind);
        }
    }

    /**
     * Nothing given, an empty title, a key of a text changed meanwhile, a learner: nothing written.
     */
    public function test_apply_refused(): void {
        global $DB;
        [$course, $page, $parts, $part] = $this->page();
        $items = content_fix::items($part, $course);
        $nothing = ['frames' => 0, 'imagelinks' => 0, 'tables' => 0];

        $this->assertSame($nothing, content_fix::apply(
            $course,
            $parts,
            [$items['frames'][0]['key'] => '  '],
            [],
            [$items['tables'][0]['key'] => false]
        ));
        $DB->set_field('page', 'content', str_replace('embed/abc', 'embed/xyz', self::HTML), ['id' => $page->id]);
        $this->assertSame(0, content_fix::apply($course, $parts, [$items['frames'][0]['key'] => 'A'], [], [])['frames']);
        $DB->set_field('page', 'content', self::HTML, ['id' => $page->id]);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));
        $this->assertSame([], content_fix::items($part, $course)['frames']);
        $this->assertSame($nothing, content_fix::apply($course, $parts, [$items['frames'][0]['key'] => 'A'], [], []));
        $this->assertSame(self::HTML, $DB->get_field('page', 'content', ['id' => $page->id]));
    }
}
