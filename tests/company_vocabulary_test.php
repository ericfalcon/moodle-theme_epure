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

use theme_epure\vocabulary\company;
use theme_epure\vocabulary\terms;


#[\PHPUnit\Framework\Attributes\CoversClass(vocabulary\company::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(string_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
/**
 * Tests for the vocabulary of IOMAD companies.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\vocabulary\company
 * @covers     \theme_epure\string_manager
 * @covers     \theme_epure\hook_callbacks
 */
final class company_vocabulary_test extends \advanced_testcase {
    /**
     * Saving the words of a company, and going back to the words of the platform.
     */
    public function test_save(): void {
        $this->resetAfterTest();
        $this->assertFalse(company::active());

        company::save(7, [terms::setting('en', 'company') => 'client', terms::setting('en', 'department') => '']);
        $this->assertTrue(company::active());
        $this->assertSame([7], company::ids());
        $this->assertSame(['company' => terms::presets()['en']['company']['client']], company::chosen(7, 'en'));
        $this->assertSame([], company::chosen(7, 'fr'));

        // Words for all companies (company 0), and a company keeping the word of the language pack.
        company::save(0, [terms::setting('en', 'company') => 'organisation', terms::setting('en', 'department') => 'department']);
        company::save(8, [terms::setting('en', 'company') => 'company']);
        $this->assertSame([0, 7, 8], company::ids());
        $this->assertSame('Edit organisation', company::rewriter(0, 'en')->rewrite('Edit company'));
        $this->assertSame('Edit client', company::rewriter(7, 'en')->rewrite('Edit company'));
        $this->assertNull(company::rewriter(8, 'en')->rewrite('Edit company'));

        // No choice left: the company uses the words of the platform.
        company::save(7, [terms::setting('en', 'company') => '']);
        $this->assertSame([0, 8], company::ids());
        $this->assertSame('Edit organisation', company::rewriter(7, 'en')->rewrite('Edit company'));
    }

    /**
     * The string manager applies the words of the company of the user, on top of the language pack.
     */
    public function test_string_manager(): void {
        global $CFG;
        $this->resetAfterTest();
        company::save(7, [terms::setting('en', 'course') => '', terms::setting('en', 'company') => 'client']);

        $manager = new class ($CFG->langotherroot, $CFG->langlocalroot, []) extends string_manager {
            /** @var int Company of the user in the test. */
            public int $fakecompany = 7;

            /**
             * Company of the user in the test.
             *
             * @return int
             */
            protected function company_id(): int {
                return $this->fakecompany;
            }
        };
        // Strings without the words of the company do not change.
        $this->assertSame('Add a new course', $manager->get_string('addnewcourse', 'moodle', null, 'en'));

        // A string of the theme names a company: the IOMAD strings are not installed on standard Moodle.
        $this->assertSame('Client', $manager->get_string('companyvocabcompany', 'theme_epure', null, 'en'));

        // A user without company sees the words of all companies, here those of the language pack.
        $manager->fakecompany = 0;
        $this->assertSame('Company', $manager->get_string('companyvocabcompany', 'theme_epure', null, 'en'));
        company::save(0, [terms::setting('en', 'company') => 'agency']);
        $this->assertSame('Agency', $manager->get_string('companyvocabcompany', 'theme_epure', null, 'en'));
    }

    /**
     * Without IOMAD, the string manager of Moodle is kept.
     */
    public function test_after_config_without_iomad(): void {
        global $CFG;
        if (file_exists($CFG->dirroot . '/local/iomad/lib/iomad.php')) {
            $this->markTestSkipped('IOMAD is installed on this site.');
        }
        $this->resetAfterTest();
        company::save(7, [terms::setting('en', 'company') => 'client']);
        hook_callbacks::after_config(new \core\hook\after_config());
        $this->assertArrayNotHasKey('customstringmanager', $CFG->config_php_settings);
        $this->assertTrue(hook_callbacks::string_manager_available());
    }
}
