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

use theme_epure\output\core_renderer;
use theme_epure\output\core_renderer_maintenance;

#[\PHPUnit\Framework\Attributes\CoversClass(busy::class)]
/**
 * Tests for the « Moodle is working » indicator of the install and upgrade pages.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\busy
 */
final class busy_test extends \advanced_testcase {
    /**
     * Data: page types, and whether they get the indicator.
     *
     * @return array
     */
    public static function pages_provider(): array {
        return [
            'upgrade' => ['admin-index', true],
            'new settings' => ['admin-upgradesettings', true],
            'plugins' => ['admin-plugins', true],
            'install from a ZIP file' => ['admin-tool-installaddon-index', true],
            'course' => ['course-view-topics', false],
            'settings page' => ['admin-setting-themesettingepure', false],
        ];
    }

    /**
     * The indicator is only added to the pages that install or upgrade Moodle and its plugins, in the normal
     * layout and in the maintenance layout (validation of a ZIP file, plugins check, upgrade).
     *
     * @dataProvider pages_provider
     * @param string $pagetype Page type.
     * @param bool $expected Whether the page gets the indicator.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages_provider')]
    public function test_pages(string $pagetype, bool $expected): void {
        $this->resetAfterTest();
        $renderers = [];
        foreach (['standard' => core_renderer::class, 'maintenance' => core_renderer_maintenance::class] as $layout => $class) {
            $page = new \moodle_page();
            $page->set_context(\context_system::instance());
            $page->set_url('/admin/index.php');
            $page->set_pagetype($pagetype);
            $page->set_pagelayout($layout);
            $renderers[] = new $class($page, $layout === 'maintenance' ? RENDERER_TARGET_MAINTENANCE : RENDERER_TARGET_GENERAL);
        }
        foreach ($renderers as $renderer) {
            // Once a step is started: the overlay and its script.
            $html = $renderer->standard_end_of_body_html();
            $this->assertSame($expected, str_contains($html, 'id="epure-busy"'));
            // While the page is sent: the message, removed once the page is complete.
            $top = $renderer->standard_top_of_body_html();
            $this->assertSame($expected, str_contains($top, 'id="epure-busy-loading"'));
            if ($expected) {
                $this->assertStringContainsString(get_string('busy', 'theme_epure'), $html);
                $this->assertStringContainsString("getElementById('epure-busy')", $html);
                $this->assertStringContainsString(get_string('busy_detail', 'theme_epure'), $top);
                $this->assertStringContainsString('DOMContentLoaded', $top);
            }
        }
    }
}
