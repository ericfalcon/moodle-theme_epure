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
#[\PHPUnit\Framework\Attributes\CoversClass(error_page::class)]
/**
 * Tests for the error pages.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\error_page
 */
final class error_page_test extends \advanced_testcase {
    /**
     * Each error code of Moodle gets its kind; unknown ones are general errors.
     */
    public function test_kind(): void {
        $this->assertSame('notfound', error_page::kind('invalidrecord'));
        $this->assertSame('notfound', error_page::kind('invalidcourseid'));
        $this->assertSame('denied', error_page::kind('nopermissions'));
        $this->assertSame('denied', error_page::kind('accessdenied'));
        $this->assertSame('unavailable', error_page::kind('requireloginerror'));
        $this->assertSame('unavailable', error_page::kind('coursehidden'));
        $this->assertSame('session', error_page::kind('invalidsesskey'));
        $this->assertSame('general', error_page::kind('generalexceptionmessage'));
        $this->assertSame('general', error_page::kind(''));
    }

    /**
     * The card says what happened, keeps Moodle's message and leads to the dashboard, or to the login page.
     */
    public function test_export(): void {
        $this->resetAfterTest();
        $card = error_page::export('denied', '<p class="errormessage">Nope</p>');
        $this->assertSame('fa-lock', $card['icon']);
        $this->assertSame(get_string('error_denied', 'theme_epure'), $card['title']);
        $this->assertSame('<p class="errormessage">Nope</p>', $card['message']);
        $this->assertSame([get_string('login')], array_column($card['actions'], 'label'));

        $this->setUser($this->getDataGenerator()->create_user());
        $card = error_page::export('notfound', '');
        $this->assertSame('fa-compass', $card['icon']);
        $this->assertSame([get_string('myhome')], array_column($card['actions'], 'label'));

        $this->setGuestUser();
        $this->assertFalse(error_page::export('general', '')['hasactions']);
    }

    /**
     * The « page not found » page of Moodle is recognised, on Moodle 4.5 and 5.x; other pages are not.
     */
    public function test_is_not_found_page(): void {
        $urls = ['/error/index.php' => true, '/error' => true, '/course/view.php' => false, '/mod/page/error.php' => false];
        foreach ($urls as $url => $found) {
            $page = new \moodle_page();
            $page->set_url($url);
            $this->assertSame($found, error_page::is_not_found_page($page), $url);
        }
        $this->assertFalse(error_page::is_not_found_page(new \moodle_page()));
    }

    /**
     * Error pages show the card of the theme instead of Moodle's red box; other boxes are left alone.
     */
    public function test_renderer(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/course/view.php');
        $PAGE->set_context(\context_system::instance());
        $output = new output\core_renderer($PAGE, RENDERER_TARGET_GENERAL);
        $box = $output->box('<p class="errormessage">Oops</p>', 'errorbox alert alert-danger', null, ['data-rel' => 'fatalerror']);
        $this->assertStringContainsString('epure-error-card', $box);
        $this->assertStringContainsString('data-kind="general"', $box);
        $this->assertStringContainsString('<p class="errormessage">Oops</p>', $box);
        $this->assertStringNotContainsString('alert-danger', $box);
        $this->assertStringNotContainsString('epure-error-card', $output->box('Hello'));
        $this->assertStringNotContainsString('epure-error-card', $output->notification('Wrong', 'error'));

        $PAGE = new \moodle_page();
        $PAGE->set_url('/error/index.php');
        $PAGE->set_context(\context_system::instance());
        $output = new output\core_renderer($PAGE, RENDERER_TARGET_GENERAL);
        $this->assertStringContainsString('data-kind="notfound"', $output->notification('Missing', 'error'));
        $this->assertStringNotContainsString('epure-error-card', $output->notification('Saved', 'success'));
    }
}
