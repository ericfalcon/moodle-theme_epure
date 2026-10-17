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

#[\PHPUnit\Framework\Attributes\CoversClass(admin_home::class)]
/**
 * Tests for the home of the site administration in cards.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\admin_home
 */
final class admin_home_test extends \advanced_testcase {
    /**
     * The cards are on the home of the administration, for whoever configures the site, not on its search.
     */
    public function test_applies(): void {
        $this->resetAfterTest();
        $page = new \moodle_page();
        $page->set_pagetype('admin-search');

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(admin_home::applies($page));

        $this->setAdminUser();
        $this->assertTrue(admin_home::applies($page));
        $_GET['query'] = 'theme';
        $this->assertFalse(admin_home::applies($page));
        unset($_GET['query']);

        $page = new \moodle_page();
        $page->set_pagetype('admin-category');
        $this->assertFalse(admin_home::applies($page));
    }

    /**
     * Each card has the pages of its tasks that exist, leading to them; the pages that do not exist are left out.
     */
    public function test_export(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $cards = admin_home::export()['cards'];
        $names = array_column($cards, 'name');
        $this->assertContains(get_string('users', 'admin'), $names);
        $this->assertContains(get_string('appearance', 'admin'), $names);
        foreach ($cards as $card) {
            $this->assertNotEmpty($card['tasks']);
            $this->assertStringStartsWith('http', $card['url']);
            foreach ($card['tasks'] as $task) {
                $this->assertNotEmpty($task['name']);
                $this->assertStringStartsWith('http', $task['url']);
            }
        }
        $users = $cards[array_search(get_string('users', 'admin'), $names)];
        $this->assertStringContainsString('/admin/user.php', $users['tasks'][0]['url']);
        // The settings of Épure, a page of settings, in the card of the appearance.
        $appearance = $cards[array_search(get_string('appearance', 'admin'), $names)];
        $this->assertStringContainsString('section=themesettingepure', $appearance['tasks'][0]['url']);
    }
}
