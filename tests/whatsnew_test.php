<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * Tests for « What is new in Épure », on the home of the administration.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\whatsnew
 */
final class whatsnew_test extends \advanced_testcase {
    /**
     * The release of a version number, for a site updated from a version before the release kept at each update.
     */
    public function test_release_of(): void {
        $this->assertNull(whatsnew::release_of(2026100000));
        $this->assertSame('1.0.0', whatsnew::release_of(2026110400));
        $this->assertSame('1.22.0', whatsnew::release_of(2026111900));
        $this->assertSame('1.22.0', whatsnew::release_of(2026111950));
    }

    /**
     * After an update: the notes of the versions since the one installed before, the most recent first, until the
     * administrator closes them; a new site, or a user who is not an administrator, gets none.
     */
    public function test_export(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        whatsnew::installed();
        $this->assertNull(whatsnew::export());

        unset_config('currentrelease', 'theme_epure');
        whatsnew::upgraded(2026111900);
        $this->assertSame('1.22.0', get_config('theme_epure', 'whatsnewbase'));
        $this->assertSame(whatsnew::current(), get_config('theme_epure', 'currentrelease'));
        $export = whatsnew::export();
        $this->assertNotNull($export);
        $versions = array_column($export['notes'], 'version');
        $this->assertNotContains('1.22.0', $versions);
        // The most recent versions, up to whatsnew::MAX, and a link to the rest when there are more.
        $all = whatsnew::notes('1.22.0', whatsnew::current());
        $this->assertContains(whatsnew::current(), $versions);
        $this->assertCount(min(count($all), whatsnew::MAX), $versions);
        $this->assertSame(count($all) > whatsnew::MAX, $export['more']);
        $sorted = $versions;
        usort($sorted, fn($a, $b) => version_compare($b, $a));
        $this->assertSame($sorted, $versions);
        foreach ($export['notes'] as $note) {
            $this->assertStringNotContainsString('**', $note['text']);
            $this->assertStringNotContainsString('upgrade the site', $note['text']);
        }

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertNull(whatsnew::export());

        $this->setAdminUser();
        whatsnew::dismiss();
        $this->assertNull(whatsnew::export());

        // The next update tells only what is new since then.
        whatsnew::upgraded(2026112100);
        $this->assertSame(whatsnew::current(), get_config('theme_epure', 'currentrelease'));
    }
}
