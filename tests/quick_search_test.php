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
/**
 * Tests for the quick search (Ctrl+K).
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\quick_search
 * @covers     \theme_epure\external\quick_search
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
}
