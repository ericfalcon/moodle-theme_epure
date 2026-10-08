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

#[\PHPUnit\Framework\Attributes\CoversClass(mobile_app::class)]
/**
 * Tests for the Moodle app in the colours of the brand.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\mobile_app
 */
final class mobile_app_test extends \advanced_testcase {
    /**
     * The style sheet sets the primary colour, the header and the font of the app, in light and dark mode.
     */
    public function test_css(): void {
        $light = palette::derive('#1C6E73');
        $dark = palette::derive('#1C6E73', true);

        $css = mobile_app::css('#1C6E73', 'brand', 'lexend');
        $this->assertStringContainsString(':root{', $css);
        $this->assertStringContainsString('--primary:' . $light['fill'] . ';', $css);
        $this->assertStringContainsString('--core-header-toolbar-background:' . $light['fill'] . ';', $css);
        $this->assertStringContainsString('--core-header-toolbar-color:' . $light['on'] . ';', $css);
        $this->assertStringContainsString('--core-link-color:' . $light['text'] . ';', $css);
        $this->assertStringContainsString(':root.dark{--primary:' . $dark['fill'] . ';', $css);
        $this->assertStringContainsString('--ion-font-family:"Lexend"', $css);
        $this->assertStringContainsString("/theme/font.php/epure/theme/", $css);

        // Light header: the app keeps its own, with a line of the brand colour; no font for an uploaded one.
        $css = mobile_app::css('#1C6E73', 'light', 'custom');
        $this->assertStringNotContainsString('--core-header-toolbar-background', $css);
        $this->assertStringNotContainsString('@font-face', $css);
    }

    /**
     * The setting sets the style sheet of the app, with a new address at each change, and takes it out when turned off,
     * leaving a style sheet set by hand as it is.
     */
    public function test_refresh(): void {
        global $CFG;
        $this->resetAfterTest();
        mobile_app::refresh();
        $this->assertEmpty($CFG->mobilecssurl ?? '');

        set_config('mobileapp', 1, 'theme_epure');
        mobile_app::refresh();
        $this->assertTrue(mobile_app::is_ours($CFG->mobilecssurl));
        $this->assertStringContainsString(
            '/pluginfile.php/' . \context_system::instance()->id . '/theme_epure/mobileapp/',
            $CFG->mobilecssurl
        );

        set_config('mobileapp', 0, 'theme_epure');
        mobile_app::refresh();
        $this->assertSame('', $CFG->mobilecssurl);

        set_config('mobilecssurl', 'https://example.org/app.css');
        mobile_app::refresh();
        $this->assertSame('https://example.org/app.css', $CFG->mobilecssurl);
    }

    /**
     * The style sheet of the current user: the one of the site, nothing with another theme.
     */
    public function test_current_css(): void {
        global $CFG;
        $this->resetAfterTest();
        set_config('brandcolor', '#9B2335', 'theme_epure');
        $CFG->theme = 'epure';
        $this->assertStringContainsString('--primary:' . palette::derive('#9B2335')['fill'] . ';', mobile_app::current_css());
        $CFG->theme = 'boost';
        $this->assertSame('', mobile_app::current_css());
    }
}
