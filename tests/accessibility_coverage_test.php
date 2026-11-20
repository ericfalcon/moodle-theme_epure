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
 * Tests of the page « What Épure covers in accessibility ».
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\accessibility_coverage
 */
final class accessibility_coverage_test extends \advanced_testcase {
    /**
     * Every point has its words, a known level and, for its criteria, a known level of the WCAG.
     */
    public function test_items(): void {
        $manager = get_string_manager();
        foreach (accessibility_coverage::ITEMS as $key => $item) {
            $this->assertContains($item['level'], accessibility_coverage::LEVELS, $key);
            $this->assertTrue($manager->string_exists('coverage_' . $key, 'theme_epure'), $key);
            $this->assertTrue($manager->string_exists('coverage_' . $key . '_text', 'theme_epure'), $key);
            foreach ($item['wcag'] as $criterion) {
                $this->assertArrayHasKey($criterion, accessibility_coverage::WCAG_LEVELS, $key);
            }
            // A criterion of the RGAA always comes from a criterion of the WCAG it holds.
            $older = array_diff($item['wcag'], accessibility_coverage::WCAG22);
            $this->assertTrue(!$item['rgaa'] || $older, $key);
        }
    }

    /**
     * The criteria are given in the referential asked, the new ones of the WCAG 2.2 marked beyond it.
     */
    public function test_references(): void {
        $this->assertSame('RGAA 4.1.2: 12.7 (WCAG 2.4.1)', accessibility_coverage::references('skiplinks', 'fr'));
        $this->assertSame('EN 301 549: 9.2.4.1', accessibility_coverage::references('skiplinks', 'eu'));
        $this->assertSame('WCAG 2.2: 2.4.1 (A)', accessibility_coverage::references('skiplinks', 'world'));
        $focus = accessibility_coverage::references('focus', 'fr');
        $this->assertSame('RGAA 4.1.2: 10.7 (WCAG 2.4.7) · WCAG 2.2: 2.4.11, beyond the RGAA', $focus);
        $this->assertSame('WCAG 2.2: 2.5.8, beyond the EN 301 549', accessibility_coverage::references('targetsize', 'eu'));
        $this->assertSame('', accessibility_coverage::references('readaloud', 'world'));
        $law = get_string('coverage_ref_law_fr', 'theme_epure');
        $this->assertSame($law, accessibility_coverage::references('statement', 'fr'));
        $this->assertSame('', accessibility_coverage::references('statement', 'world'));
    }

    /**
     * The page shows the referential of the country of the site, unless another one is asked.
     */
    public function test_export(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->country = 'BE';
        $page = accessibility_coverage::export();
        $this->assertSame(get_string('coverage_referential_eu', 'theme_epure'), $page['referential']);
        $this->assertCount(count(accessibility_coverage::LEVELS), $page['levels']);
        $this->assertSame(count(accessibility_coverage::ITEMS), array_sum(array_map(
            fn(array $level): int => count($level['items']),
            $page['levels']
        )));
        $current = array_values(array_filter($page['models'], fn(array $model): bool => $model['current']));
        $this->assertCount(1, $current);
        $this->assertStringContainsString(get_string('coverage_model_eu', 'theme_epure'), $current[0]['label']);

        $page = accessibility_coverage::export('fr');
        $this->assertSame(get_string('coverage_referential_fr', 'theme_epure'), $page['referential']);
        $this->assertSame(get_string('coverage_ref_none', 'theme_epure'), $page['levels'][4]['items'][0]['references']);
        $page = accessibility_coverage::export('xx');
        $this->assertSame(get_string('coverage_referential_eu', 'theme_epure'), $page['referential']);
    }
}
