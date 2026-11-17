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
 * Tests of the management dashboard.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\management
 */
final class management_test extends \advanced_testcase {
    /**
     * The change from the period before, in words.
     */
    public function test_change(): void {
        $this->assertSame(get_string('management_change_same', 'theme_epure'), management::change(4, 4));
        $this->assertSame(get_string('management_change_new', 'theme_epure', 3), management::change(3, 0));
        $this->assertSame(get_string('management_change_unknown', 'theme_epure'), management::change(3, null));
        $this->assertSame(
            get_string('management_change_up', 'theme_epure', (object) ['percent' => 50, 'previous' => 4]),
            management::change(6, 4)
        );
        $this->assertSame(
            get_string('management_change_down', 'theme_epure', (object) ['percent' => 25, 'previous' => 4]),
            management::change(3, 4)
        );
    }

    /**
     * The indicators of the period and of the period before, the courses most followed, the completion.
     */
    public function test_export(): void {
        global $DB;
        $this->resetAfterTest();
        $now = time();
        $generator = $this->getDataGenerator();
        $tracked = $generator->create_course(['enablecompletion' => 1, 'fullname' => 'Safety at work']);
        $other = $generator->create_course(['fullname' => 'Managing a team']);
        $learners = [];
        for ($i = 0; $i < 4; $i++) {
            $learners[] = $generator->create_user();
            $generator->enrol_user($learners[$i]->id, $tracked->id, 'student');
        }
        $generator->enrol_user($learners[0]->id, $other->id, 'student');
        // One user created in the period before.
        $DB->set_field('user', 'timecreated', $now - 40 * DAYSECS, ['id' => $learners[3]->id]);
        // In the tracked course: one learner completed, one started, two not started.
        $DB->insert_record('course_completions', ['userid' => $learners[0]->id, 'course' => $tracked->id,
            'timeenrolled' => $now - DAYSECS, 'timestarted' => $now - DAYSECS, 'timecompleted' => $now - HOURSECS]);
        $DB->insert_record('course_completions', ['userid' => $learners[1]->id, 'course' => $tracked->id,
            'timeenrolled' => $now - DAYSECS, 'timestarted' => $now - DAYSECS, 'timecompleted' => null]);
        // Two learners came into the tracked course this period, one into the other.
        foreach ([[$learners[0], $tracked], [$learners[1], $tracked], [$learners[0], $other]] as [$user, $course]) {
            $DB->insert_record('user_lastaccess', ['userid' => $user->id, 'courseid' => $course->id,
                'timeaccess' => $now - HOURSECS]);
        }

        // Now, once the users are created: their creation is in the period.
        $export = management::export(time());
        $indicators = array_column($export['indicators'], null, 'key');
        $this->assertSame(['active', 'newusers', 'enrolments', 'completions'], array_keys($indicators));
        $this->assertSame(1, $indicators['completions']['value']);
        $this->assertSame(0, $indicators['completions']['previous']);
        $this->assertSame('up', $indicators['completions']['trend']);
        // The four learners and the admin of the test site; one learner created in the period before.
        $this->assertSame(1, $indicators['newusers']['previous']);
        $this->assertGreaterThanOrEqual(3, $indicators['newusers']['value']);

        $this->assertSame('Safety at work', $export['courses'][0]['name']);
        $this->assertSame(4, $export['courses'][0]['enrolled']);
        $this->assertSame(2, $export['courses'][0]['active']);
        $this->assertSame(1, $export['courses'][0]['completed']);
        $this->assertSame('Managing a team', $export['courses'][1]['name']);

        // Only the tracked course counts for the completion.
        $completion = $export['completion'];
        $this->assertSame(4, $completion['total']);
        $parts = array_column($completion['parts'], 'percent', 'key');
        $this->assertSame(['completed' => 25, 'progress' => 25, 'notstarted' => 50], $parts);
    }

    /**
     * A site without course nor learner: the dashboard says so.
     */
    public function test_empty(): void {
        $this->resetAfterTest();
        $export = management::export();
        $this->assertFalse($export['hascourses']);
        $this->assertFalse($export['completion']['hasdata']);
        $this->assertCount(4, $export['indicators']);
    }

    /**
     * With IOMAD, the dashboard of a company: its users only, in its courses only; its managers see it, its learners
     * and the managers of other companies do not.
     */
    public function test_company(): void {
        global $DB;
        if (!iomad::installed()) {
            $this->markTestSkipped('IOMAD is not installed.');
        }
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $companies = [];
        foreach (['Clinique du Parc', 'Hôtel du Lac'] as $name) {
            $companies[] = (int) $DB->insert_record(iomad::table('company'), ['name' => $name,
                'shortname' => substr(md5($name), 0, 8), 'city' => 'Lyon', 'country' => 'FR']);
        }
        $course = $generator->create_course(['enablecompletion' => 1, 'fullname' => 'Safety at work']);
        $other = $generator->create_course(['fullname' => 'Hotel course']);
        $DB->insert_record(iomad::table('company_course'), ['companyid' => $companies[0], 'courseid' => $course->id,
            'departmentid' => 0]);
        $users = [];
        foreach (['learner' => [0, 0], 'manager' => [0, 1], 'outsider' => [1, 0], 'othermanager' => [1, 1]] as $name => $row) {
            $users[$name] = $generator->create_user();
            $DB->insert_record(iomad::table('company_users'), ['companyid' => $companies[$row[0]],
                'userid' => $users[$name]->id, 'departmentid' => 0, 'managertype' => $row[1], 'suspended' => 0]);
        }
        // The learner of the company and an outsider in the course of the company, the outsider in another course.
        $generator->enrol_user($users['learner']->id, $course->id, 'student');
        $generator->enrol_user($users['outsider']->id, $course->id, 'student');
        $generator->enrol_user($users['outsider']->id, $other->id, 'student');

        $export = management::export(time(), $companies[0]);
        $this->assertSame('Clinique du Parc', $export['company']);
        $indicators = array_column($export['indicators'], null, 'key');
        // The two users of the company were created in the period; the enrolment of the learner only.
        $this->assertSame(2, $indicators['newusers']['value']);
        $this->assertSame(1, $indicators['enrolments']['value']);
        $this->assertSame(['Safety at work'], array_column($export['courses'], 'name'));
        $this->assertSame(1, $export['courses'][0]['enrolled']);
        $this->assertSame(1, $export['completion']['total']);

        $this->assertTrue(management::visible_to($companies[0], (int) $users['manager']->id));
        $this->assertFalse(management::visible_to($companies[0], (int) $users['learner']->id));
        $this->assertFalse(management::visible_to($companies[0], (int) $users['othermanager']->id));
        $this->assertFalse(management::visible_to(0, (int) $users['manager']->id));
        // Those who see all the companies: the rule reads the capabilities of the user logged in, as the page does.
        $this->setAdminUser();
        $this->assertTrue(management::visible_to($companies[0], get_admin()->id));
    }
}
