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

/**
 * Tests of the entities: categories with an appearance, for their courses and the members of their cohorts.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\entity
 */
final class entity_test extends \advanced_testcase {
    /**
     * Forgets the entities read by another test.
     */
    protected function setUp(): void {
        parent::setUp();
        entity::reset();
    }

    /**
     * A cohort in the context of a category, or of the system.
     *
     * @param string $name Name.
     * @param \context $context Context.
     * @param string $theme Theme of the cohort.
     * @return int Cohort.
     */
    protected function cohort(string $name, \context $context, string $theme = ''): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $id = (int) cohort_add_cohort((object) ['name' => $name, 'contextid' => $context->id]);
        if ($theme !== '') {
            // Set directly: Moodle keeps the theme of a cohort only when the cohort themes are allowed.
            $DB->set_field('cohort', 'theme', $theme, ['id' => $id]);
        }
        return $id;
    }

    /**
     * The settings of an entity: those of a company, without the e-mails, the login page and the font of a company; the
     * cohorts as ids.
     */
    public function test_clean(): void {
        $settings = entity::clean(['brandcolor' => '0F766E', 'headerstyle' => 'brand', 'emailbranding' => 'hide',
            'logintext' => 'Hello', 'font' => company_style::COMPANY_FONT, 'cohorts' => ['3', 3, 'x', 1], 'radius' => 'nope']);
        $this->assertSame('#0F766E', $settings['brandcolor']);
        $this->assertSame('brand', $settings['headerstyle']);
        $this->assertSame('', $settings['font']);
        $this->assertSame('', $settings['radius']);
        $this->assertSame([1, 3], $settings['cohorts']);
        foreach (entity::NOT_FOR_ENTITIES as $name) {
            $this->assertArrayNotHasKey($name, $settings);
        }
    }

    /**
     * In a course or a category, the nearest category with an appearance: the category of the course, else one of its
     * parents; a subcategory with its own appearance keeps it.
     */
    public function test_of_context(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $faculty = $generator->create_category(['name' => 'Faculty']);
        $department = $generator->create_category(['name' => 'Department', 'parent' => $faculty->id]);
        $school = $generator->create_category(['name' => 'School', 'parent' => $faculty->id]);
        $other = $generator->create_category(['name' => 'Other']);
        $incourse = $generator->create_course(['category' => $department->id]);
        $inschool = $generator->create_course(['category' => $school->id]);
        $outside = $generator->create_course(['category' => $other->id]);
        $page = $generator->create_module('page', ['course' => $incourse->id]);

        $this->assertSame(0, entity::of_context(\context_course::instance($incourse->id)), 'No entity yet.');
        entity::save($faculty->id, ['brandcolor' => '#0f766e']);
        entity::save($school->id, ['brandcolor' => '#7c3aed']);
        $this->assertTrue(entity::exists($faculty->id));
        $this->assertSame((int) $faculty->id, entity::of_context(\context_course::instance($incourse->id)));
        $this->assertSame((int) $faculty->id, entity::of_context(\context_module::instance($page->cmid)));
        $this->assertSame((int) $faculty->id, entity::of_context(\context_coursecat::instance($department->id)));
        $this->assertSame((int) $school->id, entity::of_context(\context_course::instance($inschool->id)));
        $this->assertSame(0, entity::of_context(\context_course::instance($outside->id)));
        $this->assertSame(0, entity::of_context(\context_system::instance()));
    }

    /**
     * Out of the courses, the entity of the cohorts of the user: chosen in its appearance, or whose theme is Épure in its
     * category; none for the cohorts of two entities, or of none.
     */
    public function test_of_user(): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $iae = $generator->create_category(['name' => 'IAE']);
        $letters = $generator->create_category(['name' => 'Letters']);
        $history = $generator->create_category(['name' => 'History', 'parent' => $letters->id]);
        $chosen = $this->cohort('IAE students', \context_system::instance());
        $themed = $this->cohort('History students', \context_coursecat::instance($history->id), 'epure');
        $boost = $this->cohort('Boost students', \context_coursecat::instance($iae->id), 'boost');
        entity::save($iae->id, ['brandcolor' => '#0f766e', 'cohorts' => [$chosen]]);
        entity::save($letters->id, ['brandcolor' => '#7c3aed']);

        $map = entity::cohort_map();
        $this->assertSame([(int) $iae->id], $map[$chosen]);
        $this->assertSame([(int) $letters->id], $map[$themed], 'The cohort whose theme is Épure, in a subcategory.');
        $this->assertArrayNotHasKey($boost, $map);

        $alice = $generator->create_user();
        $bob = $generator->create_user();
        $carol = $generator->create_user();
        cohort_add_member($chosen, $alice->id);
        cohort_add_member($themed, $bob->id);
        cohort_add_member($chosen, $carol->id);
        cohort_add_member($themed, $carol->id);
        $this->assertSame((int) $iae->id, entity::of_user((int) $alice->id));
        $this->assertSame((int) $letters->id, entity::of_user((int) $bob->id));
        $this->assertSame(0, entity::of_user((int) $carol->id), 'Two entities: the site.');
        $this->assertSame(0, entity::of_user((int) $generator->create_user()->id));
    }

    /**
     * The entity of the page: that of its category, else that of the cohorts of the user; its settings and its colours
     * replace those of the site.
     */
    public function test_current(): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $iae = $generator->create_category(['name' => 'IAE']);
        $other = $generator->create_category(['name' => 'Other']);
        $course = $generator->create_course(['category' => $iae->id]);
        $outside = $generator->create_course(['category' => $other->id]);
        $cohort = $this->cohort('IAE students', \context_system::instance());
        entity::save($iae->id, ['brandcolor' => '#0f766e', 'headerstyle' => 'brand', 'darkmode' => 'dark', 'cohorts' => [$cohort],
            'catalogue' => 'hide', 'footertext' => 'IAE']);
        $learner = $generator->create_user();
        $guest = $generator->create_user();
        cohort_add_member($cohort, $learner->id);

        // In a course of the entity, for anyone.
        $this->setUser($guest);
        $PAGE->set_context(\context_course::instance($course->id));
        $this->assertSame((int) $iae->id, entity::current()->id);
        $this->assertSame('#0F766E', company_style::page_brand());
        $this->assertSame('brand', company_style::header_style());
        $this->assertSame('dark', company_style::dark_mode());
        $this->assertFalse(company_style::enabled('catalogue'));
        $this->assertSame('IAE', company_style::footer_setting('footertext'));
        $this->assertStringContainsString('--epure-brand:#0F766E', entity::current_css());

        // Out of the entity, for someone out of its cohorts: the site.
        entity::reset();
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_course::instance($outside->id));
        $this->assertNull(entity::current());
        $this->assertSame('', entity::current_css());
        $this->assertTrue(company_style::enabled('catalogue'));

        // A member of its cohorts, out of its courses.
        $this->setUser($learner);
        entity::reset();
        $this->assertSame((int) $iae->id, entity::current()->id);
        entity::reset();
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $this->assertSame((int) $iae->id, entity::current()->id);
    }

    /**
     * The categories under another theme, with its brand colour, those under Épure without an appearance, and the settings
     * of Moodle that keep their themes from applying.
     */
    public function test_migration(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $copy = $generator->create_category(['name' => 'Copy']);
        $epure = $generator->create_category(['name' => 'Under Épure']);
        $DB->set_field('course_categories', 'theme', 'boost', ['id' => $copy->id]);
        $DB->set_field('course_categories', 'theme', 'epure', ['id' => $epure->id]);
        set_config('brandcolor', '#7c3aed', 'theme_boost');
        set_config('allowcategorythemes', 0);

        $others = entity::other_themes();
        $this->assertSame([(int) $copy->id], array_map(fn($category) => (int) $category->id, $others));
        $this->assertSame('#7C3AED', $others[0]->brand);
        $this->assertSame([(int) $epure->id], array_map(fn($category) => (int) $category->id, entity::epure_without_appearance()));
        $this->assertSame(['allowcategorythemes'], entity::blocked_settings());
        set_config('allowcategorythemes', 1);
        $this->assertSame([], entity::blocked_settings());

        entity::save($epure->id, ['brandcolor' => '#0f766e']);
        $this->assertSame([], entity::epure_without_appearance());
    }

    /**
     * The appearance of a category deleted is deleted with it.
     */
    public function test_category_deleted(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        entity::save($category->id, ['brandcolor' => '#0f766e']);
        $this->assertNotFalse(get_config('theme_epure', entity::PREFIX . $category->id));
        \core_course_category::get($category->id)->delete_full(false);
        entity::reset();
        $this->assertFalse(get_config('theme_epure', entity::PREFIX . $category->id));
        $this->assertFalse(entity::exists((int) $category->id));
    }
}
