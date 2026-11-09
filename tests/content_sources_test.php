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

#[\PHPUnit\Framework\Attributes\CoversClass(content_sources::class)]
/**
 * Tests for where the activities keep the texts of their teachers, for the guide « Accessibility of the course ».
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\content_sources
 */
final class content_sources_test extends \advanced_testcase {
    /**
     * Every module of Moodle has a way to be read: its content, its description and files, or a point to confirm; the
     * modules of other plugins are found.
     */
    public function test_coverage(): void {
        foreach (content_sources::core_modules() as $modname) {
            $this->assertContains(content_sources::coverage($modname), ['content', 'external', 'description'], $modname);
        }
        $this->assertSame('content', content_sources::coverage('workshop'));
        $this->assertSame('external', content_sources::coverage('scorm'));
        $this->assertSame('description', content_sources::coverage('label'));
        $this->assertSame('unread', content_sources::coverage('notamodule'));
        foreach (content_sources::third_party() as $modname => $coverage) {
            $this->assertNotContains($modname, content_sources::core_modules());
            $this->assertContains($coverage, ['plugin', 'unread']);
        }
        $this->assertNotSame('', content_sources::report());
    }

    /**
     * The texts of the teachers are read where the modules keep them: the instructions of a workshop, the entries of the
     * teachers in a glossary (not those of the learners), the texts of a feedback, the templates of a database.
     */
    public function test_texts(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $workshop = $generator->create_module('workshop', ['course' => $course->id]);
        $DB->set_field('workshop', 'instructauthors', '<p><img src="https://example.com/a.png"></p>', ['id' => $workshop->id]);
        $glossary = $generator->create_module('glossary', ['course' => $course->id]);
        $glossarygenerator = $generator->get_plugin_generator('mod_glossary');
        $glossarygenerator->create_content($glossary, ['concept' => 'Risk', 'definition' => '<p>Danger</p>']);
        $DB->set_field('glossary_entries', 'teacherentry', 1, ['concept' => 'Risk']);
        $learner = $generator->create_and_enrol($course, 'student');
        $glossarygenerator->create_content($glossary, ['concept' => 'Mine', 'definition' => '<p>Mine</p>',
            'userid' => $learner->id]);
        $DB->set_field('glossary_entries', 'teacherentry', 0, ['concept' => 'Mine']);
        $feedback = $generator->create_module('feedback', ['course' => $course->id]);
        $DB->insert_record('feedback_item', ['feedback' => $feedback->id, 'template' => 0, 'name' => '', 'label' => '',
            'presentation' => '<p>Welcome</p>', 'typ' => 'label', 'hasvalue' => 0, 'position' => 1, 'required' => 0,
            'dependitem' => 0, 'dependvalue' => '', 'options' => '']);
        $data = $generator->create_module('data', ['course' => $course->id]);
        $DB->set_field('data', 'singletemplate', '<table><tr><td>[[Name]]</td></tr></table>', ['id' => $data->id]);

        $modinfo = get_fast_modinfo($course);
        $texts = fn($module, $table) => content_sources::texts(
            $modinfo->get_cm($module->cmid),
            $DB->get_record($table, ['id' => $module->id])
        );

        $this->assertContains(
            get_string('courseaccessibility_workshop_instructauthors', 'theme_epure'),
            array_column($texts($workshop, 'workshop'), 'name')
        );
        $this->assertSame(['Risk'], array_column($texts($glossary, 'glossary'), 'name'));
        $this->assertSame(['item'], array_column($texts($feedback, 'feedback'), 'kind'));
        $this->assertSame(
            get_string('courseaccessibility_data_singletemplate', 'theme_epure'),
            $texts($data, 'data')[0]['name']
        );

        // In the guide: the image of the instructions of the workshop, without a text alternative.
        course_accessibility::forget((int) $course->id);
        $parts = array_column(course_accessibility::analyse($course), null, 'id');
        $this->assertSame(1, $parts[$workshop->cmid]['issues']['images'] ?? 0);
    }

    /**
     * A SCORM package gets a point to confirm: its content comes from elsewhere.
     */
    public function test_external(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $scorm = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id, 'name' => 'Package']);
        course_accessibility::forget((int) $course->id);
        $parts = array_column(course_accessibility::analyse($course), null, 'id');
        $checks = array_column($parts[$scorm->cmid]['checks'], 'label', 'kind');
        $this->assertSame('Package', $checks['external'] ?? null);
    }
}
