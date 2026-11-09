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
        // A site open without logging in (required by default with IOMAD and from Moodle 5.2).
        $CFG->forcelogin = 0;
        $generator = $this->getDataGenerator();
        $category = $generator->create_category(['name' => 'Sécurité']);
        $generator->create_course(['fullname' => 'Sécurité au travail', 'category' => $category->id]);
        $generator->create_course(['fullname' => 'Sécurité cachée', 'category' => $category->id, 'visible' => 0]);
        $this->setUser(0);

        $this->assertTrue(quick_search::enabled());
        $this->assertTrue(quick_search::visitor());
        $this->assertEquals([get_string('login')], $this->search_public('log in')['public']);
        if (iomad::installed()) {
            // With IOMAD, the courses and the categories are those of a company: the public pages only.
            $this->assertEquals([], $this->search_public('Sécurité'));
            return;
        }
        $groups = $this->search_public('Sécurité');
        $this->assertEquals(
            ['Sécurité au travail', get_string('quicksearch_allcourses', 'theme_epure', 'Sécurité')],
            $groups['catalogue']
        );
        $this->assertEquals(['Sécurité'], $groups['categories']);
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

    /**
     * The names that match best come first: the name itself, then a name that starts with the query, then a name
     * whose words start with the words of the query, then a name that only contains them.
     */
    public function test_rank(): void {
        $this->assertSame(0, quick_search::rank('risk', ['risk']));
        $this->assertSame(1, quick_search::rank('risk assessment', ['risk']));
        $this->assertSame(2, quick_search::rank('fire risk', ['risk']));
        $this->assertSame(3, quick_search::rank('asterisk', ['risk']));

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Safety']);
        foreach (['Asterisk', 'Fire risk', 'Risk assessment', 'Risk'] as $name) {
            $generator->create_module('page', ['course' => $course->id, 'name' => $name]);
        }
        $this->setUser($generator->create_and_enrol($course, 'student'));
        $this->assertSame(['Risk', 'Risk assessment', 'Fire risk', 'Asterisk'], $this->search('risk')['activities']);
    }

    /**
     * A learner in more courses than the quick search reads: the activities of the courses they opened last are found,
     * and a link leads to the page of all the results, which reads all their courses.
     */
    public function test_many_courses(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $learner = $generator->create_user();
        $count = quick_search::ACTIVITY_COURSES + 5;
        for ($i = 1; $i <= $count; $i++) {
            $course = $generator->create_course(['fullname' => 'Course ' . $i]);
            $generator->enrol_user($learner->id, $course->id, 'student');
            // The first course opened long ago, the others recently.
            $DB->insert_record('user_lastaccess', ['userid' => $learner->id, 'courseid' => $course->id,
                'timeaccess' => $i === 1 ? 1000 : 2000 + $i]);
            if ($i === 1 || $i === $count) {
                $generator->create_module('page', ['course' => $course->id, 'name' => 'Logbook ' . $i]);
            }
        }
        $this->setUser($learner);

        // The quick search: the course opened recently, and the link to all the results.
        $this->assertSame(
            ['Logbook ' . $count, get_string('quicksearch_allmine', 'theme_epure', 'logbook')],
            $this->search('logbook')['activities']
        );
        // The page of all the results: both.
        [, $activities] = quick_search::mine(['logbook'], quick_search::RESULTS, 0);
        $this->assertEqualsCanonicalizing(['Logbook 1', 'Logbook ' . $count], array_column($activities, 'name'));
    }
}
