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

#[\PHPUnit\Framework\Attributes\CoversClass(certificate_template::class)]
/**
 * Tests for the template of certificate in the colours of the brand.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\certificate_template
 */
final class certificate_template_test extends \advanced_testcase {
    /**
     * The template has the frame of the brand as background, the title in the colour of the brand, the name of the
     * learner and of the course, the date of issue and the QR code; each click makes a new one.
     */
    public function test_create(): void {
        global $DB;
        if (!certificate_template::available()) {
            $this->markTestSkipped('The plugin Certificate (tool_certificate) is not installed.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('brandcolor', '#0f766e', 'theme_epure');

        $before = $DB->count_records('tool_certificate_templates');
        $template = certificate_template::create();
        $name = get_string('certtemplate_name', 'theme_epure', format_string(get_site()->fullname));
        $this->assertSame($name, $template->get_name());
        $pages = $DB->get_records('tool_certificate_pages', ['templateid' => $template->get_id()]);
        $this->assertCount(1, $pages);
        $elements = $DB->get_records('tool_certificate_elements', ['pageid' => reset($pages)->id], 'sequence');
        $types = array_column($elements, 'element');
        foreach (['image', 'text', 'userfield', 'program', 'date', 'code'] as $type) {
            $this->assertContains($type, $types);
        }

        // The frame: a background image, with its file.
        $frame = reset($elements);
        $this->assertSame('image', $frame->element);
        $this->assertTrue(json_decode($frame->data)->isbackground);
        $context = \context_system::instance();
        $files = get_file_storage()->get_area_files($context->id, 'tool_certificate', 'element', $frame->id, 'id', false);
        $this->assertCount(1, $files);
        $this->assertSame('image/png', reset($files)->get_mimetype());

        // The title in the colour of the brand, readable on white.
        $titles = array_filter($elements, fn($element) => $element->data === get_string('certtemplate_title', 'theme_epure'));
        $title = reset($titles);
        $this->assertSame(palette::derive('#0f766e')['text'], $title->colour);

        // No logo: no image besides the frame.
        $this->assertNull(certificate_template::logo());
        $this->assertCount(1, array_filter($types, fn($type) => $type === 'image'));

        // A second click makes a second template.
        certificate_template::create();
        $this->assertSame($before + 2, $DB->count_records('tool_certificate_templates'));
    }

    /**
     * A logo keeps its proportions in its box, whatever its shape, in whole millimetres.
     */
    public function test_fit(): void {
        $this->assertSame([24, 24], certificate_template::fit(['width' => 512, 'height' => 512], 60, 24));
        $this->assertSame([60, 15], certificate_template::fit(['width' => 800, 'height' => 200], 60, 24));
        $this->assertSame([12, 24], certificate_template::fit(['width' => 100, 'height' => 200], 60, 24));
        $this->assertSame([24, 24], certificate_template::fit([], 60, 24));
    }
}
