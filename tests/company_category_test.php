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

#[\PHPUnit\Framework\Attributes\CoversClass(company_style::class)]
/**
 * Tests for the access to the course category of a company, on the IOMAD dashboard.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\company_style
 */
final class company_category_test extends \advanced_testcase {
    /**
     * The course category of the company leads to its management for managers, else to its courses.
     */
    public function test_company_category(): void {
        $this->resetAfterTest();
        $this->assertNull(company_style::company_category((object) ['category' => 0]));
        $this->assertNull(company_style::company_category((object) ['category' => 99999]));

        $category = $this->getDataGenerator()->create_category(['name' => 'Clinique des Tilleuls']);
        $this->setAdminUser();
        $link = company_style::company_category((object) ['category' => $category->id]);
        $this->assertStringContainsString('/course/management.php?categoryid=' . $category->id, $link['url']);
        $this->assertSame('Clinique des Tilleuls', $link['name']);

        $this->setUser($this->getDataGenerator()->create_user());
        $link = company_style::company_category((object) ['category' => $category->id]);
        $this->assertStringContainsString('/course/index.php?categoryid=' . $category->id, $link['url']);
    }
}
