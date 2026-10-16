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

#[\PHPUnit\Framework\Attributes\CoversNothing]
/**
 * Tests for the empty states of Épure: a clear sentence and the next step.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class empty_state_test extends \advanced_testcase {
    /**
     * Renders a template with Épure.
     *
     * @param string $template Template.
     * @param array $context Context.
     * @return string HTML.
     */
    protected function render(string $template, array $context): string {
        global $PAGE;
        set_config('theme', 'epure');
        $PAGE->set_url('/my/');
        return $PAGE->get_renderer('core')->render_from_template($template, $context);
    }

    /**
     * Without courses: the next step is to create one when the user can, else the catalogue; the sentence of the
     * template that uses it is kept.
     */
    public function test_no_courses(): void {
        $this->resetAfterTest();
        $html = $this->render('core_course/no-courses', ['nocoursesimg' => '#']);
        $this->assertStringContainsString('class="epure-empty"', $html);
        $this->assertStringContainsString('data-region="empty-message"', $html);
        $this->assertStringContainsString(get_string('nocourses'), $html);
        $this->assertStringContainsString('/course/index.php', $html);

        $html = $this->render('core_course/no-courses', ['newcourseurl' => 'https://example.com/course/edit.php']);
        $this->assertStringContainsString('nocourseslink', $html);
        $this->assertStringNotContainsString('/course/index.php', $html);

        $html = $this->render('block_starredcourses/no-courses', []);
        $this->assertStringContainsString(get_string('nocourses', 'block_starredcourses'), $html);
    }

    /**
     * Nothing to do in the timeline: the region its JavaScript shows, with the calendar as the next step.
     */
    public function test_timeline(): void {
        $this->resetAfterTest();
        $html = $this->render('block_timeline/no-events', ['urls' => ['noevents' => '#']]);
        $this->assertStringContainsString('class="hidden" data-region="no-events-empty-message"', $html);
        $this->assertStringContainsString(get_string('noevents', 'block_timeline'), $html);
        $this->assertStringContainsString('/calendar/view.php?view=upcoming', $html);
    }

    /**
     * My courses without courses: the catalogue.
     */
    public function test_mycourses(): void {
        $this->resetAfterTest();
        $html = $this->render('theme_epure/mycourses', ['hascourses' => false, 'catalogueurl' => 'https://example.com/cat']);
        $this->assertStringContainsString(get_string('empty_mycourses', 'theme_epure'), $html);
        $this->assertStringContainsString('href="https://example.com/cat"', $html);
    }
}
