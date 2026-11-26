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
 * Tests of the live preview of the colours: the colours of the preview, and its web service.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\palette::preview_vars
 * @covers     \theme_epure\external\palette_preview
 */
final class palette_live_test extends \advanced_testcase {
    /**
     * The colours of the preview are those the theme computes: the button and its text at 4.5:1, the header of each
     * style.
     */
    public function test_preview_vars(): void {
        $vars = palette::preview_vars('#0f766e', '#b1c800', 'brand');
        foreach (['light', 'dark'] as $mode) {
            $this->assertGreaterThanOrEqual(4.5, palette::contrast($vars["pl-{$mode}-brand"], $vars["pl-{$mode}-on-brand"]));
            $this->assertGreaterThanOrEqual(4.5, palette::contrast($vars["pl-{$mode}-header"], $vars["pl-{$mode}-header-on"]));
            $this->assertGreaterThanOrEqual(4.5, palette::contrast($vars["pl-{$mode}-text"], $vars["pl-{$mode}-surface"]));
        }
        $this->assertSame(palette::derive('#0f766e')['fill'], palette::preview_vars('#0f766e', null, 'brand')['pl-light-header']);
        $gradient = palette::preview_vars('#0f766e', '#b1c800', 'gradient');
        $this->assertNotSame($gradient['pl-light-header'], $gradient['pl-light-header-end']);
        $this->assertSame('#FFFFFF', palette::preview_vars('#0f766e', null, 'light')['pl-light-header']);
    }

    /**
     * The web service: the colours typed, else those of the site; an invalid colour is the one of the site.
     */
    public function test_web_service(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_config('brandcolor', '#2559a8', 'theme_epure');
        $result = \core_external\external_api::clean_returnvalue(
            external\palette_preview::execute_returns(),
            external\palette_preview::execute('#0f766e', '', 'brand')
        );
        $vars = array_column($result['vars'], 'value', 'name');
        $this->assertSame(palette::derive('#0f766e')['fill'], $vars['pl-light-brand']);
        $this->assertStringContainsString('#0F766E', $result['swatches']);

        $site = \core_external\external_api::clean_returnvalue(
            external\palette_preview::execute_returns(),
            external\palette_preview::execute('not a colour', '', 'nope')
        );
        $this->assertSame(palette::derive('#2559a8')['fill'], array_column($site['vars'], 'value', 'name')['pl-light-brand']);
    }
}
