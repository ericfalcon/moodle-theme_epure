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

#[\PHPUnit\Framework\Attributes\CoversClass(quick_search::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(external\quick_search::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(external\quick_search_public::class)]
/**
 * Tests for the quick search (Ctrl+K).
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\quick_search
 * @covers     \theme_epure\external\quick_search
 * @covers     \theme_epure\external\quick_search_public
 */
final class quick_search_test extends \advanced_testcase {
    /**
     * Groups of a search, by key, with the names of their items.
     *
     * @param string $query Query.
     * @return array<string, string[]>
     */
    protected function search(string $query): array {
        $result = external\quick_search::execute($query);
        $result = \core_external\external_api::clean_returnvalue(external\quick_search::execute_returns(), $result);
        return array_column(array_map(
            fn($group) => [$group['key'], array_column($group['items'], 'name')],
            $result['groups']
        ), 1, 0);
    }

    /**
     * A learner finds their courses and their activities, without accents nor case, and the other courses in the catalogue.
     */
    public function test_learner(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $mine = $generator->create_course(['fullname' => 'Sécurité au travail', 'shortname' => 'SECU']);
        $other = $generator->create_course(['fullname' => 'Sécurité incendie', 'shortname' => 'FIRE']);
        $generator->create_module('page', ['course' => $mine->id, 'name' => 'Les risques au poste']);
        $generator->create_module('page', ['course' => $other->id, 'name' => 'Les risques du feu']);
        $generator->create_module('page', ['course' => $mine->id, 'name' => 'Hidden risks', 'visible' => 0]);
        $learner = $generator->create_and_enrol($mine, 'student');
        $this->setUser($learner);

        $groups = $this->search('securite');
        $this->assertEquals(['Sécurité au travail'], $groups['mycourses']);
        // The catalogue is searched by Moodle's course search, which may care about accents, as the database does.
        $groups = $this->search('Sécurité');
        $this->assertEquals(['Sécurité au travail'], $groups['mycourses']);
        $this->assertEquals('Sécurité incendie', $groups['catalogue'][0]);
        $this->assertCount(2, $groups['catalogue']);
        $this->assertArrayNotHasKey('admin', $groups);

        $groups = $this->search('RISQUES poste');
        $this->assertEquals(['Les risques au poste'], $groups['activities']);

        // Too short, or turned off: nothing.
        $this->assertEquals([], $this->search('s'));
        set_config('quicksearch', '0', 'theme_epure');
        $this->assertEquals([], $this->search('securite'));
    }

    /**
     * Without activity icons, the activities found get a plain icon, as the other results.
     */
    public function test_activity_icons_hidden(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Les risques au poste']);
        $this->setUser($generator->create_and_enrol($course, 'student'));

        $activity = fn() => array_column(quick_search::search('risques'), 'items', 'key')['activities'][0];
        $this->assertArrayHasKey('image', $activity());
        set_config('activityicons', 'none', 'theme_epure');
        $this->assertArrayNotHasKey('image', $activity());
        $this->assertSame('fa-file-lines', $activity()['icon']);
    }

    /**
     * An administrator also finds the pages of the administration, those whose name matches first.
     */
    public function test_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $groups = $this->search('purge');
        $this->assertContains(get_string('purgecachespage', 'admin'), $groups['admin']);
        $this->assertEquals(get_string('quicksearch_alladmin', 'theme_epure', 'purge'), end($groups['admin']));
        // The administration is searched from three characters.
        $this->assertArrayNotHasKey('admin', $this->search('pu'));
    }

    /**
     * With Moodle's global search, whose button the quick search replaces, the results end with a link to it.
     */
    public function test_global_search(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->assertArrayNotHasKey('site', $this->search('purge'));
        set_config('enableglobalsearch', 1);
        $groups = $this->search('purge');
        $this->assertEquals([get_string('quicksearch_allsite', 'theme_epure', 'purge')], $groups['site']);
    }

    /**
     * Groups of a search of a visitor, by key, with the names of their items.
     *
     * @param string $query Query.
     * @return array<string, string[]>
     */
    protected function search_public(string $query): array {
        $result = external\quick_search_public::execute($query);
        $result = \core_external\external_api::clean_returnvalue(external\quick_search_public::execute_returns(), $result);
        return array_column(array_map(
            fn($group) => [$group['key'], array_column($group['items'], 'name')],
            $result['groups']
        ), 1, 0);
    }

    /**
     * A visitor, not logged in or a guest, finds the courses of the catalogue, the categories and the public pages;
     * never the hidden courses, nor anything on a site where logging in is required.
     */
    public function test_visitor(): void {
        global $CFG;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $category = $generator->create_category(['name' => 'Sécurité']);
        $generator->create_course(['fullname' => 'Sécurité au travail', 'category' => $category->id]);
        $generator->create_course(['fullname' => 'Sécurité cachée', 'category' => $category->id, 'visible' => 0]);
        $this->setUser(0);

        $this->assertTrue(quick_search::enabled());
        $this->assertTrue(quick_search::visitor());
        $groups = $this->search_public('Sécurité');
        $this->assertEquals(
            ['Sécurité au travail', get_string('quicksearch_allcourses', 'theme_epure', 'Sécurité')],
            $groups['catalogue']
        );
        $this->assertEquals(['Sécurité'], $groups['categories']);
        $this->assertEquals([get_string('login')], $this->search_public('log in')['public']);
        // The service of the users with an account is not theirs.
        $this->assertFalse(quick_search::member());

        // A guest too.
        $this->setGuestUser();
        $this->assertEquals(['Sécurité'], $this->search_public('securite')['categories']);

        // A user with an account uses the other service: the public one answers nothing.
        $this->setUser($generator->create_user());
        $this->assertFalse(quick_search::visitor());
        $this->assertEquals([], $this->search_public('Sécurité'));

        // Logging in required: nothing for the visitors.
        $this->setUser(0);
        $CFG->forcelogin = 1;
        $this->assertFalse(quick_search::enabled());
        $this->assertEquals([], $this->search_public('Sécurité'));
    }
}
