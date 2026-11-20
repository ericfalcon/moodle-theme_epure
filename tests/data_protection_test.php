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
 * Tests of the page « Épure and personal data ».
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\data_protection
 */
final class data_protection_test extends \advanced_testcase {
    /**
     * Every data, point and field of the record has its words, every point a known level.
     */
    public function test_strings(): void {
        $manager = get_string_manager();
        foreach (data_protection::DATA as $key) {
            foreach (['', '_where', '_how'] as $suffix) {
                $this->assertTrue($manager->string_exists('dataprot_data_' . $key . $suffix, 'theme_epure'), $key);
            }
        }
        foreach (data_protection::ITEMS as $key => $item) {
            $this->assertContains($item['level'], data_protection::LEVELS, $key);
            $this->assertTrue($manager->string_exists('dataprot_' . $key, 'theme_epure'), $key);
            $this->assertTrue($manager->string_exists('dataprot_' . $key . '_text', 'theme_epure'), $key);
            $this->assertTrue($manager->string_exists('dataprot_principle_' . $item['principle'], 'theme_epure'), $key);
            foreach (data_protection::MODELS as $model) {
                $this->assertNotSame('', data_protection::references($key, $model), $key . ' ' . $model);
            }
        }
        foreach (data_protection::RECORD as $field) {
            $this->assertTrue($manager->string_exists('dataprot_record_' . $field . '_value', 'theme_epure'), $field);
        }
    }

    /**
     * The references follow the framework: the articles of the GDPR, the CNIL in France only, the principles elsewhere.
     */
    public function test_references(): void {
        $this->assertSame('GDPR, art. 15, 17, 20', data_protection::references('rights', 'eu'));
        $cookie = data_protection::references('cookie', 'fr');
        $this->assertStringContainsString(get_string('dataprot_ref_cnil', 'theme_epure'), $cookie);
        $this->assertStringNotContainsString('CNIL', data_protection::references('cookie', 'eu'));
        $principle = get_string('dataprot_principle_security', 'theme_epure');
        $this->assertSame($principle, data_protection::references('security', 'world'));
    }

    /**
     * Every data the theme declares to the privacy API is on the page: the tables and the preferences.
     */
    public function test_declared_data(): void {
        $collection = privacy\provider::get_metadata(new \core_privacy\local\metadata\collection('theme_epure'));
        $tables = [];
        $preferences = 0;
        foreach ($collection->get_collection() as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $tables[] = $item->get_name();
            } else if ($item instanceof \core_privacy\local\metadata\types\user_preference) {
                $preferences++;
            }
        }
        // The preferences, the confirmations and the documents: nothing else is kept on the users.
        $this->assertEqualsCanonicalizing([course_accessibility::TABLE, learner_summary_document::TABLE], $tables);
        $this->assertGreaterThan(0, $preferences);
        $this->assertContains('preferences', data_protection::DATA);
        $this->assertContains('confirmations', data_protection::DATA);
        $this->assertContains('documents', data_protection::DATA);
    }

    /**
     * The page shows the framework of the country of the site, unless another one is asked.
     */
    public function test_export(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->country = 'FR';
        $page = data_protection::export();
        $this->assertSame(get_string('dataprot_framework_fr', 'theme_epure'), $page['framework']);
        $this->assertCount(count(data_protection::DATA), $page['data']);
        $this->assertCount(count(data_protection::RECORD), $page['record']);
        $this->assertCount(count(data_protection::LEVELS), $page['levels']);
        $page = data_protection::export('world');
        $this->assertSame(get_string('dataprot_framework_world', 'theme_epure'), $page['framework']);
        $current = array_values(array_filter($page['models'], fn(array $model): bool => $model['current']));
        $this->assertSame(get_string('dataprot_model_world', 'theme_epure'), $current[0]['label']);
    }
}
