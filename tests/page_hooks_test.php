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

#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
/**
 * Tests for what Épure adds to the pages through the hooks of Moodle, in place of overrides of the renderer: the top of
 * the page and the links of the footer, on the pages of Épure only.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\hook_callbacks
 */
final class page_hooks_test extends \advanced_testcase {
    /**
     * The renderer of a page in a theme.
     *
     * @param string $theme Theme.
     * @return \core_renderer
     */
    protected function renderer(string $theme): \core_renderer {
        global $CFG, $PAGE;
        $CFG->theme = $theme;
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/my/');
        $PAGE->set_pagelayout('standard');
        return $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);
    }

    /**
     * On a page of Épure: the header of the printed pages at the top, the help of the keyboard shortcuts and the
     * accessibility statement (published, without the footer of Épure) in the footer; on a page of Boost, none.
     */
    public function test_hooks(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('a11ystatus', 'partial', 'theme_epure');
        set_config('footer', 0, 'theme_epure');

        $epure = $this->renderer('epure');
        $this->assertStringContainsString('epure-print-header', $epure->standard_top_of_body_html());
        $footer = $epure->standard_footer_html();
        $this->assertStringContainsString('epure-shortcuts-link', $footer);
        $this->assertTrue(accessibility_statement::enabled());
        $this->assertStringContainsString('epure-a11y-footer-link', $footer);

        $boost = $this->renderer('boost');
        $this->assertStringNotContainsString('epure-print-header', $boost->standard_top_of_body_html());
        $this->assertStringNotContainsString('epure-shortcuts-link', $boost->standard_footer_html());
    }
}
