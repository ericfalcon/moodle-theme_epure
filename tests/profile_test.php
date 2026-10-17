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

use core_user\output\myprofile\tree;

#[\PHPUnit\Framework\Attributes\CoversClass(profile::class)]
/**
 * Tests for the profile with Épure: summary of the own profile, cards grouped.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\profile
 * @covers     \theme_epure\output\core_user\myprofile_renderer
 */
final class profile_test extends \advanced_testcase {
    /**
     * The tree of a profile, as Moodle builds it, with Épure.
     *
     * @param \stdClass $user Owner of the profile.
     * @param bool $iscurrentuser Whether it is the profile of the current user.
     * @return tree
     */
    protected function tree(\stdClass $user, bool $iscurrentuser): tree {
        return \core_user\output\myprofile\manager::build_tree($user, $iscurrentuser);
    }

    /**
     * Prepares a page of the profile with Épure.
     */
    protected function page(): void {
        global $PAGE;
        set_config('theme', 'epure');
        $PAGE->set_url('/user/profile.php');
        $PAGE->set_context(\context_system::instance());
        profile::reset();
    }

    /**
     * On the own profile: the courses in progress and completed, the badges, the button to edit the profile.
     */
    public function test_summary(): void {
        global $PAGE;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['lastlogin' => time() - DAYSECS]);
        $generator->enrol_user($user->id, $generator->create_course()->id, 'student');
        $generator->enrol_user($user->id, $generator->create_course()->id, 'student');
        $this->setUser($user);
        $this->page();

        $html = $PAGE->get_renderer('core_user', 'myprofile')->render($this->tree($user, true));
        $this->assertStringContainsString('class="epure-profile-summary"', $html);
        $inprogress = preg_quote(get_string('profile_inprogress', 'theme_epure'), '~');
        $this->assertMatchesRegularExpression(
            '~<span class="epure-profile-figure">2</span>\s*<span class="epure-profile-figure-label">' . $inprogress . '~',
            $html
        );
        $this->assertStringContainsString(get_string('profile_badges', 'theme_epure'), $html);
        $this->assertStringContainsString('epure-profile-edit', $html);
        $this->assertStringContainsString('/user/edit.php', $html);
        $this->assertStringContainsString(get_string('profile_lastlogin', 'theme_epure', userdate($user->lastlogin)), $html);
        // The cards that matter less under « More information ».
        $this->assertStringContainsString('<details class="epure-profile-more">', $html);
        $this->assertGreaterThan(strpos($html, '<details'), strpos($html, get_string('loginactivity')));
        $this->assertLessThan(strpos($html, '<details'), strpos($html, get_string('userdetails')));
    }

    /**
     * On the profile of another user: no summary, the cards grouped all the same.
     */
    public function test_other(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $this->page();

        $html = $PAGE->get_renderer('core_user', 'myprofile')->render($this->tree($user, false));
        $this->assertStringNotContainsString('epure-profile-summary', $html);
        $this->assertStringContainsString(get_string('userdetails'), $html);
    }
}
