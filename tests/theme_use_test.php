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

#[\PHPUnit\Framework\Attributes\CoversClass(theme_use::class)]
/**
 * Tests for the theme in use: what Épure adds outside its pages only applies with it.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\theme_use
 */
final class theme_use_test extends \advanced_testcase {
    /**
     * Épure, and not the other themes.
     */
    public function test_is_epure(): void {
        theme_use::reset();
        $this->assertTrue(theme_use::is_epure('epure'));
        $this->assertFalse(theme_use::is_epure('boost'));
        $this->assertFalse(theme_use::is_epure('classic'));
        $this->assertFalse(theme_use::is_epure(''));
        $this->assertFalse(theme_use::is_epure(null));
        $this->assertFalse(theme_use::is_epure('nosuchtheme'));
    }

    /**
     * The theme of the session, then the theme of the user (IOMAD sets it to the theme of their company), then the
     * theme of the site.
     */
    public function test_current(): void {
        global $SESSION, $USER;
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_config('theme', 'epure');
        set_config('allowuserthemes', 1);
        $this->assertTrue(theme_use::current());

        $USER->theme = 'classic';
        $this->assertSame('classic', theme_use::current_name());
        $this->assertFalse(theme_use::current());

        set_config('allowuserthemes', 0);
        $this->assertTrue(theme_use::current());

        $SESSION->theme = 'boost';
        $this->assertFalse(theme_use::current());
        unset($SESSION->theme);
    }

    /**
     * A company uses its theme, or the theme of the site when it has none.
     */
    public function test_company(): void {
        $this->resetAfterTest();
        set_config('theme', 'epure');
        $this->assertTrue(theme_use::company((object) ['theme' => 'epure']));
        $this->assertTrue(theme_use::company((object) ['theme' => '']));
        $this->assertFalse(theme_use::company((object) ['theme' => 'boost']));
        set_config('theme', 'boost');
        $this->assertFalse(theme_use::company((object) ['theme' => '']));
    }
}
