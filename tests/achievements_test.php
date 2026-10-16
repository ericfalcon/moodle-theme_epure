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

#[\PHPUnit\Framework\Attributes\CoversClass(achievements::class)]
/**
 * Tests for the achievements of a learner: badges and certificates, on the dashboard and the own profile.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\achievements
 */
final class achievements_test extends \advanced_testcase {
    /**
     * Issues badges to a user.
     *
     * @param \stdClass $user User.
     * @param int $count Number of badges.
     * @param int|null $courseid Course of the badges, site badges without.
     * @return int[] Ids of the badges, the first issued first.
     */
    protected function issue_badges(\stdClass $user, int $count, ?int $courseid = null): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/badgeslib.php');
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $record = $generator->create_badge(['name' => "Badge $i" . ($courseid ? ' course' : ''),
                'type' => $courseid ? BADGE_TYPE_COURSE : BADGE_TYPE_SITE, 'courseid' => $courseid,
                'status' => BADGE_STATUS_ACTIVE]);
            (new \badge($record->id))->issue($user->id, true);
            // Issued one minute apart, the last the latest.
            $DB->set_field(
                'badge_issued',
                'dateissued',
                time() - DAYSECS + $i * MINSECS,
                ['badgeid' => $record->id, 'userid' => $user->id]
            );
            $ids[] = $record->id;
        }
        return $ids;
    }

    /**
     * The latest badges first, public or not, with their image in the context of the site or the course; the count of
     * the others.
     */
    public function test_badges(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->issue_badges($user, 7);
        $courseids = $this->issue_badges($user, 1, $course->id);
        $DB->set_field('badge_issued', 'dateissued', time(), ['badgeid' => $courseids[0]]);
        // Not public.
        $DB->set_field('badge_issued', 'visible', 0, ['userid' => $user->id]);

        $data = achievements::export($user->id);
        $this->assertTrue($data['hasbadges']);
        $this->assertCount(achievements::BADGES, $data['badges']);
        $this->assertSame(
            ['Badge 1 course', 'Badge 7', 'Badge 6', 'Badge 5', 'Badge 4', 'Badge 3'],
            array_column($data['badges'], 'name')
        );
        $this->assertSame(2, $data['morebadges']);
        $this->assertStringContainsString('/' . \context_course::instance($course->id)->id . '/badges/badgeimage/'
            . $courseids[0] . '/f3', $data['badges'][0]['image']);
        $this->assertStringContainsString(
            '/' . \context_system::instance()->id . '/badges/badgeimage/',
            $data['badges'][1]['image']
        );
        $this->assertStringContainsString('/badges/badge.php?hash=', $data['badges'][0]['url']);
        $this->assertNotEmpty($data['badgesurl']);
        $this->assertFalse($data['hascertificates']);
    }

    /**
     * Nothing without badges nor certificates, or with the badges turned off; the link to the certificates alone keeps
     * the card.
     */
    public function test_empty(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $this->assertNull(achievements::export($user->id));
        $this->assertSame('#', achievements::export($user->id, '#')['certificatesurl']);

        $this->issue_badges($user, 1);
        $this->assertCount(1, achievements::export($user->id)['badges']);
        set_config('enablebadges', 0);
        $this->assertNull(achievements::export($user->id));
    }

    /**
     * On their own profile, with Épure, the gallery replaces the list of the public badges of Moodle; not on the
     * profile of the others, nor with another theme.
     */
    public function test_myprofile(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->issue_badges($user, 2);
        $this->setUser($user);
        set_config('theme', 'epure');
        $PAGE->set_url('/user/profile.php');

        $tree = $this->profile($user, true);
        $this->assertArrayHasKey('epureachievements', $tree->nodes);
        $this->assertArrayNotHasKey('localbadges', $tree->nodes);
        $this->assertStringContainsString('epure-achievements', $tree->nodes['epureachievements']->content);

        $tree = $this->profile($other, false);
        $this->assertArrayNotHasKey('epureachievements', $tree->nodes);

        $page = new \moodle_page();
        $page->set_url('/user/profile.php');
        $page->force_theme('boost');
        $PAGE = $page;
        $tree = $this->profile($user, true);
        $this->assertArrayNotHasKey('epureachievements', $tree->nodes);
        $this->assertArrayHasKey('localbadges', $tree->nodes);
    }

    /**
     * Profile of a user with the badges of Moodle (public, as Moodle shows them) and the achievements of Épure.
     *
     * @param \stdClass $user Owner of the profile.
     * @param bool $iscurrentuser Whether it is the profile of the current user.
     * @return tree
     */
    protected function profile(\stdClass $user, bool $iscurrentuser): tree {
        global $CFG;
        require_once($CFG->libdir . '/myprofilelib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/badges/lib.php');
        $tree = new tree();
        core_myprofile_navigation($tree, $user, $iscurrentuser, null);
        core_badges_myprofile_navigation($tree, $user, $iscurrentuser, null);
        achievements::myprofile($tree, $user, $iscurrentuser, null);
        return $tree;
    }
}
