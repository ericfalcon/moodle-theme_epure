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

use theme_epure\vocabulary\manager;
use theme_epure\vocabulary\terms;

#[\PHPUnit\Framework\Attributes\CoversClass(vocabulary\manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vocabulary\terms::class)]
/**
 * Tests for applying the vocabulary through the language customisation tool.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\vocabulary\manager
 * @covers     \theme_epure\vocabulary\terms
 */
final class vocabulary_manager_test extends \advanced_testcase {
    /**
     * Applying writes the strings, keeps the administrator's customisations, and reverting removes only the plugin's strings.
     */
    public function test_apply_and_revert(): void {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');
        $this->resetAfterTest();
        $sm = get_string_manager();

        set_config(terms::setting('en', 'course'), 'program', 'theme_epure');
        set_config(terms::setting('en', 'student'), terms::CUSTOM, 'theme_epure');
        set_config(terms::setting('en', 'student', 'singular'), 'apprentice', 'theme_epure');
        set_config(terms::setting('en', 'student', 'plural'), 'apprentices', 'theme_epure');

        $stats = manager::apply('en');
        $this->assertGreaterThan(100, $stats['total']);
        $this->assertSame($stats['total'], manager::count('en'));
        $this->assertSame('My programs', $sm->get_string('mycourses', 'moodle', null, 'en'));
        $this->assertSame('Apprentice', $sm->get_string('defaultcoursestudent', 'moodle', null, 'en'));
        $this->assertNotEmpty(manager::examples('en'));

        // The administrator customises a string in the language customisation tool.
        $this->customise('en', 'core', 'mycourses', 'My trainings');

        // Applying again keeps it.
        $stats = manager::apply('en');
        $this->assertSame(0, $stats['changed']);
        $this->assertSame(1, $stats['kept']);
        $this->assertSame('My trainings', $sm->get_string('mycourses', 'moodle', null, 'en'));

        // Back to Moodle's wording: the plugin's strings go, the administrator's one stays.
        set_config(terms::setting('en', 'course'), 'course', 'theme_epure');
        set_config(terms::setting('en', 'student'), 'student', 'theme_epure');
        manager::apply('en');
        $this->assertSame(0, manager::count('en'));
        $this->assertSame('Add a new course', $sm->get_string('addnewcourse', 'moodle', null, 'en'));
        $this->assertSame('Student', $sm->get_string('defaultcoursestudent', 'moodle', null, 'en'));
        $this->assertSame('My trainings', $sm->get_string('mycourses', 'moodle', null, 'en'));
    }

    /**
     * A custom word without singular, or the word of the language pack, changes nothing.
     */
    public function test_chosen(): void {
        $this->resetAfterTest();
        set_config(terms::setting('fr', 'course'), terms::CUSTOM, 'theme_epure');
        $this->assertNull(terms::chosen('fr', 'course'));
        set_config(terms::setting('fr', 'course', 'singular'), 'unité', 'theme_epure');
        set_config(terms::setting('fr', 'course', 'gender'), 'f', 'theme_epure');
        $this->assertSame(['singular' => 'unité', 'plural' => 'unité', 'gender' => 'f'], terms::chosen('fr', 'course'));
        set_config(terms::setting('fr', 'course'), 'cours', 'theme_epure');
        $this->assertNull(terms::chosen('fr', 'course'));
        $this->assertSame([], terms::chosen_all('fr'));
    }

    /**
     * Customises a string as the language customisation tool does, and saves it to the language pack.
     *
     * @param string $lang Language.
     * @param string $component Component.
     * @param string $stringid String identifier.
     * @param string $text New text.
     */
    private function customise(string $lang, string $component, string $stringid, string $text): void {
        global $DB;
        $componentid = $DB->get_field('tool_customlang_components', 'id', ['name' => $component]);
        $conditions = ['lang' => $lang, 'componentid' => $componentid, 'stringid' => $stringid];
        $DB->set_field('tool_customlang', 'local', $text, $conditions);
        $DB->set_field('tool_customlang', 'modified', 1, $conditions);
        \tool_customlang_utils::checkin($lang);
    }
}
