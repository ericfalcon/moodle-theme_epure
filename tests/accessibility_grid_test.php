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
 * Tests of the grids of the referentials: every criterion of the RGAA, of the EN 301 549 and of the WCAG has who answers
 * it, its state and its remark, in both languages.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\accessibility_grid
 */
final class accessibility_grid_test extends \advanced_testcase {
    /**
     * The 106 criteria of the RGAA, each in the grid; the 50 clauses 9 of the EN 301 549 (WCAG 2.1); the 56 success
     * criteria of the WCAG 2.2 A and AA, 4.1.1 included.
     */
    public function test_rows(): void {
        $rgaa = accessibility_grid::rows('fr');
        $this->assertCount(106, $rgaa);
        $this->assertSame(array_keys(accessibility_grid::RGAA), array_column($rgaa, 'number'));
        $this->assertCount(13, array_unique(array_column($rgaa, 'topic')));
        $this->assertCount(50, accessibility_grid::rows('eu'));
        $this->assertStringStartsWith('9.', accessibility_grid::rows('eu')[0]['number']);
        $this->assertCount(56, accessibility_grid::rows('world'));
        foreach (['fr', 'eu', 'world'] as $model) {
            foreach (accessibility_grid::rows($model) as $row) {
                $this->assertContains($row['owner'], accessibility_grid::OWNERS, $row['number']);
                $this->assertContains($row['state'], accessibility_grid::STATES, $row['number']);
                $this->assertNotSame('', $row['title'], $row['number']);
            }
        }
    }

    /**
     * Every string of the grids exists, in English and in French.
     */
    public function test_strings(): void {
        $keys = [];
        $wcag = array_map(fn($row) => array_slice($row, 1), accessibility_grid::WCAG);
        foreach (array_merge(accessibility_grid::RGAA, $wcag) as $row) {
            $keys[] = 'coverage_note_' . $row[2];
        }
        foreach (accessibility_grid::BEYOND as $criterion => $note) {
            $keys[] = 'coverage_note_' . $note;
            $keys[] = 'coverage_wcag_' . str_replace('.', '_', $criterion);
        }
        foreach (array_keys(accessibility_grid::WCAG) as $criterion) {
            $keys[] = 'coverage_wcag_' . str_replace('.', '_', $criterion);
        }
        foreach (array_keys(accessibility_grid::STATEMENT) as $key) {
            $keys[] = 'coverage_statement_' . $key;
            $keys[] = 'coverage_statement_' . $key . '_text';
        }
        $manager = get_string_manager();
        foreach (array_unique($keys) as $key) {
            foreach (['en', 'fr'] as $lang) {
                $this->assertTrue($manager->string_exists($key, 'theme_epure'), $key);
                $this->assertNotSame('', $manager->get_string($key, 'theme_epure', null, $lang), "$key ($lang)");
            }
        }
    }

    /**
     * The counts and the rate: the criteria conform among those verified, the lowest if those to verify were not.
     */
    public function test_counts(): void {
        $rows = [['state' => 'c'], ['state' => 'c'], ['state' => 'c'], ['state' => 'nc'], ['state' => 'v'], ['state' => 'na']];
        $counts = accessibility_grid::counts($rows);
        $this->assertSame(3, $counts['c']);
        $this->assertSame(1, $counts['na']);
        $this->assertSame(75, $counts['rate']);
        $this->assertSame(60, $counts['lowest']);
        $export = accessibility_grid::export('eu');
        $this->assertTrue($export['hasstatementneeds']);
        $this->assertFalse($export['hasbeyond']);
        $this->assertTrue(accessibility_grid::export('world')['hasbeyond']);
    }
}
