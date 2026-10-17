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

#[\PHPUnit\Framework\Attributes\CoversClass(fonts::class)]
/**
 * Tests for the fonts bundled with the theme.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\fonts
 */
final class fonts_test extends \advanced_testcase {
    /**
     * Every font has its files: one for a variable font, one per weight otherwise.
     */
    public function test_files(): void {
        global $CFG;
        foreach (array_keys(fonts::all()) as $key) {
            $css = fonts::font_face_css($key, fn($file) => $file);
            preg_match_all("/url\\('([^']+)'\\)/", $css, $matches);
            $font = fonts::get($key);
            $this->assertCount($font['variable'] ? 1 : count($font['weights']), $matches[1], $key);
            foreach ($matches[1] as $file) {
                $this->assertFileExists($CFG->dirroot . '/theme/epure/fonts/' . $file, $key);
            }
        }
    }

    /**
     * A variable font: one rule for the range of its weights, which covers those the theme uses.
     */
    public function test_variable(): void {
        $css = fonts::font_face_css('inter', fn($file) => $file);
        $this->assertStringContainsString('font-weight: 100 900;', $css);
        $this->assertStringContainsString("url('inter-latin-wght-normal.woff2')", $css);
        foreach (fonts::all() as $key => $font) {
            if ($font['variable']) {
                [$min, $max] = $font['weights'];
                $this->assertLessThanOrEqual(400, $min, $key);
                $this->assertGreaterThanOrEqual(700, $max, $key);
            }
        }
        $css = fonts::font_face_css('lato', fn($file) => $file);
        $this->assertStringContainsString('font-weight: 700;', $css);
        $this->assertStringContainsString("url('lato-latin-400-normal.woff2')", $css);
    }
}
