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

#[\PHPUnit\Framework\Attributes\CoversClass(mycourses::class)]
/**
 * Tests of « My courses » for a learner of several IOMAD companies.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\mycourses
 * @covers     \theme_epure\iomad
 * @covers     \theme_epure\hook_callbacks
 */
final class iomad_companies_test extends \advanced_testcase {
    /** @var int[] Companies A and B. */
    protected array $companies = [];

    /** @var int[] Courses of company A and of company B. */
    protected array $courses = [];

    /** @var \stdClass Learner of both companies, working in B. */
    protected \stdClass $user;

    /**
     * Two companies, a course in each, a learner of both enrolled in both courses, working in company B.
     */
    protected function setUp(): void {
        global $DB, $SESSION;
        parent::setUp();
        if (!company_style::iomad_installed()) {
            $this->markTestSkipped('IOMAD is not installed.');
        }
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->user = $generator->create_user();
        foreach (['A', 'B'] as $name) {
            $category = $generator->create_category();
            $this->companies[$name] = (int) $DB->insert_record(iomad::table('company'), ['name' => 'Company ' . $name,
                'shortname' => $name, 'city' => 'Lyon', 'country' => 'FR', 'category' => $category->id]);
            $DB->insert_record(iomad::table('company_users'), ['companyid' => $this->companies[$name],
                'userid' => $this->user->id, 'departmentid' => 0, 'managertype' => 0, 'suspended' => 0]);
            $course = $generator->create_course(['fullname' => 'Course ' . $name]);
            $this->courses[$name] = (int) $course->id;
            $DB->delete_records(iomad::table('company_course'), ['courseid' => $course->id]);
            $DB->insert_record(iomad::table('company_course'), ['companyid' => $this->companies[$name],
                'courseid' => $course->id, 'departmentid' => 0]);
            $generator->enrol_user($this->user->id, $course->id, 'student');
        }
        $this->setUser($this->user);
        $SESSION->currenteditingcompany = $this->companies['B'];
    }

    /**
     * The names of the courses on « My courses », and their company.
     *
     * @return array<string, string|null>
     */
    protected function shown(): array {
        global $PAGE;
        $shown = [];
        foreach (mycourses::export($PAGE->get_renderer('core'))['sections'] as $section) {
            foreach ($section['courses'] as $card) {
                $shown[$card['name']] = $card['company'] ?? null;
            }
        }
        return $shown;
    }

    /**
     * By default, as IOMAD: the courses of the company the learner works in.
     */
    public function test_company_only(): void {
        $this->assertSame(['Course B' => null], $this->shown());
    }

    /**
     * With the setting, all the courses, those of the other company with its name; a course opens in its company.
     */
    public function test_all_companies(): void {
        global $SESSION;
        set_config('iomadallcourses', 1, 'theme_epure');
        $shown = $this->shown();
        ksort($shown);
        $this->assertSame(['Course A' => 'Company A', 'Course B' => null], $shown);
        $this->assertSame($this->companies['A'], iomad::course_company((int) $this->user->id, $this->courses['A']));

        $open = function (int $courseid): void {
            $_SERVER['SCRIPT_NAME'] = '/course/view.php';
            $_GET['id'] = $courseid;
            (new \ReflectionMethod(hook_callbacks::class, 'follow_course_company'))->invoke(null);
            unset($_GET['id']);
        };
        $open($this->courses['A']);
        $this->assertSame($this->companies['A'], (int) $SESSION->currenteditingcompany);
        $open($this->courses['B']);
        $this->assertSame($this->companies['B'], (int) $SESSION->currenteditingcompany);

        // Not for a manager, who chooses the company in the IOMAD dashboard.
        global $DB;
        $DB->set_field(iomad::table('company_users'), 'managertype', 1, ['userid' => $this->user->id]);
        $open($this->courses['A']);
        $this->assertSame($this->companies['B'], (int) $SESSION->currenteditingcompany);
    }
}
