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

#[\PHPUnit\Framework\Attributes\CoversClass(shortcuts::class)]
/**
 * Tests for the help of the keyboard shortcuts.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\shortcuts
 */
final class shortcuts_test extends \advanced_testcase {
    /**
     * The actions listed, by group.
     *
     * @return string[][]
     */
    protected function actions(): array {
        return array_column(array_map(
            fn($group) => [$group['title'], array_column($group['items'], 'action')],
            shortcuts::export()['groups']
        ), 1, 0);
    }

    /**
     * A user with an account gets the shortcuts of the page, of the quick search and of the editor; the key « ? » can
     * be turned off in their preferences.
     */
    public function test_user(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $actions = $this->actions();
        $page = $actions[get_string('shortcuts_page', 'theme_epure')];
        $this->assertContains(get_string('shortcuts_help', 'theme_epure'), $page);
        $this->assertContains(get_string('shortcuts_quicksearch', 'theme_epure'), $page);
        $this->assertContains(get_string('shortcuts_display', 'theme_epure'), $page);
        $this->assertArrayHasKey(get_string('quicksearch', 'theme_epure'), $actions);
        $this->assertArrayHasKey(get_string('shortcuts_editor', 'theme_epure'), $actions);

        // On a Mac, Ctrl shows as ⌘; the arrows are one or the other, not together.
        $keys = array_merge(...array_map(fn($item) => $item['keys'], shortcuts::export()['groups'][0]['items']));
        $this->assertContains('⌘', array_column($keys, 'mac'));
        $move = shortcuts::export()['groups'][1]['items'][0]['keys'];
        $this->assertTrue($move[1]['either']);

        $this->assertTrue(shortcuts::export()['keyenabled']);
        set_user_preference(shortcuts::PREFERENCE, 0);
        $this->assertFalse(shortcuts::key_enabled());
        $this->assertTrue(\core_user::can_edit_preference(shortcuts::PREFERENCE, $user));
    }

    /**
     * Without the quick search and the panel « Aa », their shortcuts are not listed; a visitor has no editor.
     */
    public function test_turned_off(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->forcelogin = 0;
        set_config('quicksearch', 0, 'theme_epure');
        set_config('prefpanel', 0, 'theme_epure');
        $this->setUser($this->getDataGenerator()->create_user());
        $actions = $this->actions();
        $page = $actions[get_string('shortcuts_page', 'theme_epure')];
        $this->assertNotContains(get_string('shortcuts_quicksearch', 'theme_epure'), $page);
        $this->assertNotContains(get_string('shortcuts_display', 'theme_epure'), $page);
        $this->assertArrayNotHasKey(get_string('quicksearch', 'theme_epure'), $actions);

        $this->setUser(0);
        $this->assertArrayNotHasKey(get_string('shortcuts_editor', 'theme_epure'), $this->actions());
        $this->assertTrue(shortcuts::key_enabled());
    }
}
