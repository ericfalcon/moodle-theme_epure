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

#[\PHPUnit\Framework\Attributes\CoversClass(iomad::class)]
/**
 * Tests for what Épure uses of IOMAD.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\iomad
 */
final class iomad_test extends \advanced_testcase {
    /**
     * The tables are found by their name in the version of IOMAD installed, and exist; without IOMAD, nothing.
     */
    public function test_tables(): void {
        global $DB;
        iomad::reset();
        if (!iomad::installed()) {
            $this->assertFalse(iomad::renamed());
            $this->assertSame('company_users', iomad::table('company_users'));
            $this->assertSame(0, iomad::my_companyid());
            $this->assertNull(iomad::company(1));
            return;
        }
        foreach (array_keys(iomad::TABLES) as $name) {
            $this->assertTrue($DB->get_manager()->table_exists(iomad::table($name)), $name);
        }
        $this->assertSame(iomad::renamed() ? 'local_iomad_companies' : 'company', iomad::table('company'));
    }

    /**
     * The pages of IOMAD the theme links to.
     */
    public function test_url(): void {
        global $CFG;
        $this->assertSame('/blocks/iomad_company_admin/index.php', iomad::url('dashboard')->out_as_local_url(false));
        if (iomad::installed()) {
            foreach (iomad::PAGES as $path) {
                $this->assertFileExists($CFG->dirroot . $path);
            }
        }
    }
}
