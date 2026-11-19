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

        $done = content_fix::apply($course, $parts, [
            'frames' => [$items['frames'][0]['key'] => 'Vidéo : les gestes « utiles »'],
            'imagelinks' => [$items['imagelinks'][0]['key'] => 'Programme de la formation'],
            'tables' => [$items['tables'][0]['key'] => true],
        ]);
        $expected = array_merge(array_fill_keys(content_fix::KINDS, 0), ['frames' => 1, 'imagelinks' => 1, 'tables' => 1]);
        $this->assertSame($expected, $done);
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
        $nothing = array_fill_keys(content_fix::KINDS, 0);
        $frame = ['frames' => [$items['frames'][0]['key'] => 'A']];

        $this->assertSame($nothing, content_fix::apply($course, $parts, [
            'frames' => [$items['frames'][0]['key'] => '  '],
            'tables' => [$items['tables'][0]['key'] => false],
        ]));
        $DB->set_field('page', 'content', str_replace('embed/abc', 'embed/xyz', self::HTML), ['id' => $page->id]);
        $this->assertSame(0, content_fix::apply($course, $parts, $frame)['frames']);
        $DB->set_field('page', 'content', self::HTML, ['id' => $page->id]);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));
        $this->assertSame([], content_fix::items($part, $course)['frames']);
        $this->assertSame($nothing, content_fix::apply($course, $parts, $frame));
        $this->assertSame(self::HTML, $DB->get_field('page', 'content', ['id' => $page->id]));
    }

    /**
     * A link « click here » alone in its paragraph gets its new text, a link of an icon without text its name; the
     * levels of the titles are put in order, their texts kept; a text too little contrasted loses its colours, its other
     * formatting kept. Once corrected, nothing is left, and a link « here » in a sentence is left alone.
     */
    public function test_apply_links_headings_contrast(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $html = '<h2>Plan</h2><h4 class="x">Détail</h4><h5>Fin</h5><h3>Suite</h3>'
            . '<p><a href="https://example.org/consignes.pdf">Cliquez ici</a></p>'
            . '<p>Le règlement est <a href="https://example.org/r">ici</a> pour tous.</p>'
            . '<p><a href="https://example.org/aide"><i class="fa fa-question"></i></a></p>'
            . '<p style="color: #FFFF00; font-weight: bold">Attention</p>';
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'content' => $html,
            'contentformat' => FORMAT_HTML]);
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        $this->assertSame(2, $part['issues']['links']);
        $this->assertSame(1, $part['issues']['headings']);
        $this->assertSame(1, $part['issues']['contrast']);

        $items = content_fix::items($part, $course);
        $this->assertSame(['Cliquez ici', ''], array_column($items['links'], 'text'));
        $this->assertSame([2, 4, 5, 3], $items['headings'][0]['levels']);
        $this->assertSame([2, 3, 4, 3], $items['headings'][0]['ordered']);
        $contrast = $items['contrast'][0];
        $this->assertSame(['#FFFF00', '#FFFFFF', 'Attention'], [$contrast['fg'], $contrast['bg'], $contrast['text']]);

        $done = content_fix::apply($course, $parts, [
            'links' => [$items['links'][0]['key'] => 'Consignes de sécurité (PDF)', $items['links'][1]['key'] => 'Aide'],
            'headings' => [$items['headings'][0]['key'] => true],
            'contrast' => [$items['contrast'][0]['key'] => true],
        ]);
        $expected = array_merge(array_fill_keys(content_fix::KINDS, 0), ['links' => 2, 'headings' => 1, 'contrast' => 1]);
        $this->assertSame($expected, $done);
        $this->assertSame(
            '<h2>Plan</h2><h3 class="x">Détail</h3><h4>Fin</h4><h3>Suite</h3>'
            . '<p><a href="https://example.org/consignes.pdf">Consignes de sécurité (PDF)</a></p>'
            . '<p>Le règlement est <a href="https://example.org/r">ici</a> pour tous.</p>'
            . '<p><a href="https://example.org/aide" aria-label="Aide"><i class="fa fa-question"></i></a></p>'
            . '<p style="font-weight: bold;">Attention</p>',
            $DB->get_field('page', 'content', ['id' => $page->id])
        );
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        foreach (content_fix::KINDS as $kind) {
            $this->assertSame(0, $part['issues'][$kind] ?? 0, $kind);
        }
    }

    /**
     * The levels of titles put in order: none more than one deeper than the one before, the first as it is.
     */
    public function test_ordered(): void {
        $this->assertSame([2, 3, 4, 3], content_fix::ordered([2, 4, 5, 3]));
        $this->assertSame([3, 4, 3, 4], content_fix::ordered([3, 6, 3, 5]));
        $this->assertSame([1, 2, 2], content_fix::ordered([1, 2, 2]));
        $this->assertSame([], content_fix::ordered([]));
    }

    /**
     * The empty titles taken away, the texts that scroll made still, the order of the key Tab put back: each for a text
     * ticked, nothing else changed; once corrected, nothing is left.
     */
    public function test_apply_structure(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $html = '<h2>Plan</h2><h3> </h3><p><marquee behavior="alternate">Info</marquee></p><a href="#a" tabindex="3">A</a>';
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'content' => $html,
            'contentformat' => FORMAT_HTML]);
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        $items = content_fix::items($part, $course);
        $this->assertSame(1, $items['emptyheadings'][0]['count']);
        $this->assertSame(1, $items['moving'][0]['count']);
        $this->assertSame(1, $items['tabindex'][0]['count']);

        $done = content_fix::apply($course, $parts, [
            'emptyheadings' => [$items['emptyheadings'][0]['key'] => true],
            'moving' => [$items['moving'][0]['key'] => true],
            'tabindex' => [$items['tabindex'][0]['key'] => true],
        ]);
        $expected = array_merge(array_fill_keys(content_fix::KINDS, 0), ['emptyheadings' => 1, 'moving' => 1, 'tabindex' => 1]);
        $this->assertSame($expected, $done);
        $content = $DB->get_field('page', 'content', ['id' => $page->id]);
        $this->assertSame('<h2>Plan</h2><p>Info</p><a href="#a" tabindex="0">A</a>', $content);
        $parts = course_accessibility::analyse($course);
        $part = array_values(array_filter($parts, fn($part) => $part['type'] === 'cm' && $part['id'] == $page->cmid))[0];
        foreach (['emptyheadings', 'moving', 'tabindex'] as $kind) {
            $this->assertSame(0, $part['issues'][$kind] ?? 0, $kind);
        }
    }
}
