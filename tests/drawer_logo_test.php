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

#[\PHPUnit\Framework\Attributes\CoversClass(output\core_renderer::class)]
/**
 * Tests for the logo of the drawer of the navigation on phones.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\output\core_renderer
 */
final class drawer_logo_test extends \advanced_testcase {
    /**
     * A logo of the theme, stored as the setting stores it.
     *
     * @param string $setting Setting.
     */
    protected function logo(string $setting): void {
        get_file_storage()->create_file_from_string(['contextid' => \context_system::instance()->id,
            'component' => 'theme_epure', 'filearea' => $setting, 'itemid' => 0, 'filepath' => '/',
            'filename' => $setting . '.svg'], '<svg xmlns="http://www.w3.org/2000/svg"/>');
        set_config($setting, '/' . $setting . '.svg', 'theme_epure');
    }

    /**
     * The renderer of Épure for a page.
     *
     * @return output\core_renderer
     */
    protected function renderer(): output\core_renderer {
        global $CFG, $PAGE;
        $CFG->theme = 'epure';
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/my/');
        return $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
    }

    /**
     * With a brand-coloured header and its own version of the logo, the white drawer gets the logo for a light
     * background; with a light header, the logo is the same everywhere and nothing is added.
     */
    public function test_drawer_logo(): void {
        $this->resetAfterTest();
        $this->logo('logo');
        $this->logo('logoonbrand');

        set_config('headerstyle', 'brand', 'theme_epure');
        $renderer = $this->renderer();
        $this->assertStringContainsString('/logoonbrand/', $renderer->get_compact_logo_url()->out(false));
        $this->assertStringContainsString('/logo/', $renderer->light_logo_url()->out(false));
        $this->assertStringContainsString('epure-drawer-logo', $renderer->standard_head_html());

        set_config('headerstyle', 'light', 'theme_epure');
        $renderer = $this->renderer();
        $this->assertNull($renderer->light_logo_url());
        $this->assertStringNotContainsString('epure-drawer-logo', $renderer->standard_head_html());
    }
}
