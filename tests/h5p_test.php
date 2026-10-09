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

#[\PHPUnit\Framework\Attributes\CoversClass(h5p::class)]
/**
 * Tests for the H5P contents in the colour of the brand, and for the colours of the activity icons.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\h5p
 * @covers     \theme_epure\activity_icons
 */
final class h5p_test extends \advanced_testcase {
    /**
     * The H5P variables and the older content types take the brand colour readable on white, with readable text.
     */
    public function test_css(): void {
        $css = h5p::css('#331E6D');
        $this->assertStringContainsString('--h5p-theme-main-cta-base:#331E6D;', $css);
        $this->assertStringContainsString('--h5p-theme-contrast-cta:#FFFFFF;', $css);
        $this->assertStringContainsString('.h5p-joubelui-button,', $css);
        $this->assertStringNotContainsStringIgnoringCase('#1a73d9', $css);
        // Course presentations and audio of before the H5P theme variables.
        $this->assertStringContainsString('.h5p-progressbar .h5p-progressbar-part-show{background:#', $css);
        $this->assertStringContainsString('.h5p-audio-inner:not(.h5p-audio-transparent) .h5p-audio-minimal-button{', $css);

        // A light brand colour is darkened for the buttons, which keep a readable text.
        $css = h5p::css('#F5C518');
        preg_match('/--h5p-theme-main-cta-base:(#[0-9A-F]{6});/', $css, $main);
        preg_match('/--h5p-theme-contrast-cta:(#[0-9A-F]{6});/', $css, $on);
        $this->assertGreaterThanOrEqual(4.5, palette::contrast($main[1], '#FFFFFF'));
        $this->assertGreaterThanOrEqual(4.5, palette::contrast($main[1], $on[1]));
    }

    /**
     * The style sheet follows the brand colour of the site.
     */
    public function test_stylesheet_url(): void {
        $this->resetAfterTest();
        set_config('brandcolor', '#1C6E73', 'theme_epure');
        $url = h5p::stylesheet_url();
        $this->assertStringEndsWith('/theme/epure/h5p.php', $url->get_path(false));
        $this->assertSame('1C6E73', $url->get_param('brand'));
    }

    /**
     * The activity icons take the brand colour by default, or Moodle's colours: no filter is written then.
     */
    public function test_activity_icons(): void {
        $this->resetAfterTest();
        set_config('brandcolor', '#1C6E73', 'theme_epure');
        $this->assertSame('brand', activity_icons::current());
        $svg = activity_icons::filters();
        $this->assertStringContainsString('id="epure-icons-light"', $svg);
        $this->assertStringContainsString('id="epure-icons-dark"', $svg);

        set_config('activityicons', 'moodle', 'theme_epure');
        $this->assertSame('moodle', activity_icons::current());
        $this->assertSame('', activity_icons::filters());

        // Hidden: no filters either; an unknown value falls back to the brand.
        set_config('activityicons', 'none', 'theme_epure');
        $this->assertSame('none', activity_icons::current());
        $this->assertSame('', activity_icons::filters());
        set_config('activityicons', 'other', 'theme_epure');
        $this->assertSame('brand', activity_icons::current());
    }
}
