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

#[\PHPUnit\Framework\Attributes\CoversClass(mobile_nav::class)]
/**
 * Tests for the navigation bar of the phones.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\mobile_nav
 */
final class mobile_nav_test extends \advanced_testcase {
    /**
     * The bar is for logged in users, outside the login and the pages without navigation, unless turned off.
     */
    public function test_applies(): void {
        $this->resetAfterTest();
        $page = new \moodle_page();
        $page->set_pagelayout('standard');
        $this->assertFalse(mobile_nav::applies($page));

        $this->setGuestUser();
        $this->assertFalse(mobile_nav::applies($page));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertTrue(mobile_nav::applies($page));

        $popup = new \moodle_page();
        $popup->set_pagelayout('popup');
        $this->assertFalse(mobile_nav::applies($popup));

        set_config('mobilenav', '0', 'theme_epure');
        $this->assertFalse(mobile_nav::applies($page));
    }

    /**
     * The item of the current page is active, and the messages give the number of unread conversations.
     */
    public function test_export(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $other = $generator->create_user();
        $this->setUser($user);
        $conversation = \core_message\api::create_conversation(
            \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            [$user->id, $other->id]
        );
        \core_message\tests\helper::send_fake_message_to_conversation($other, $conversation->id);

        $page = new \moodle_page();
        $page->set_pagetype('my-courses');
        $items = array_column(mobile_nav::export($page)['items'], null, 'key');
        $this->assertEquals(['home', 'courses', 'catalogue', 'messages', 'profile'], array_keys($items));
        $this->assertTrue($items['courses']['active']);
        $this->assertFalse($items['home']['active']);
        $this->assertEquals(1, $items['messages']['count']);

        $page = new \moodle_page();
        $page->set_pagetype('course-index-category');
        $this->assertTrue(array_column(mobile_nav::export($page)['items'], null, 'key')['catalogue']['active']);
    }
}
