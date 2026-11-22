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

#[\PHPUnit\Framework\Attributes\CoversClass(core_renderer::class)]
/**
 * Tests of the renderer of the pages.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\output\core_renderer
 */
final class core_renderer_test extends \advanced_testcase {
    /**
     * A page without a title gets its heading and the name of the site as its title (RGAA 8.5); a page with a title
     * keeps it.
     */
    public function test_page_title(): void {
        global $SITE;
        $this->resetAfterTest();
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_url('/theme/epure/test.php');
        $page->set_heading('File exchange');
        $output = new core_renderer($page, RENDERER_TARGET_GENERAL);
        $this->assertSame('File exchange | ' . $SITE->shortname, $output->page_title());

        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->set_url('/theme/epure/test.php');
        $output = new core_renderer($page, RENDERER_TARGET_GENERAL);
        $this->assertSame($SITE->shortname, $output->page_title());

        $page->set_title('Forum');
        $this->assertSame($page->title, $output->page_title());
        $this->assertStringStartsWith('Forum', $output->page_title());
    }
}
