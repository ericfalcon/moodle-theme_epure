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
 * Tests for the preview of the files of a course.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\file_preview
 * @covers     \theme_epure\external\view_resource
 */
final class file_preview_test extends \advanced_testcase {
    /**
     * The kinds of files with a preview.
     */
    public function test_kind(): void {
        $this->assertSame('pdf', file_preview::kind('application/pdf'));
        $this->assertSame('image', file_preview::kind('image/PNG'));
        $this->assertSame('video', file_preview::kind('video/mp4'));
        $this->assertSame('audio', file_preview::kind('audio/mpeg'));
        $this->assertSame('text', file_preview::kind('text/plain'));
        $this->assertNull(file_preview::kind('text/csv'));
        $word = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $this->assertSame('document', file_preview::kind($word));
        $this->assertNull(file_preview::kind('application/msword'));
    }

    /**
     * Only the resources shown in Moodle's page, with a file that has a preview and that the user can see, in the order
     * of the course.
     */
    public function test_items(): void {
        global $CFG;
        require_once($CFG->libdir . '/resourcelib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $resource = fn(string $name, string $file, int $display, int $visible = 1) => $generator->create_module(
            'resource',
            ['course' => $course->id, 'name' => $name, 'defaultfilename' => $file, 'display' => $display, 'visible' => $visible]
        );
        $notes = $resource('Notes', 'notes.txt', RESOURCELIB_DISPLAY_OPEN);
        $map = $resource('Map', 'map.png', RESOURCELIB_DISPLAY_EMBED);
        $resource('Figures', 'figures.csv', RESOURCELIB_DISPLAY_OPEN);
        $resource('Forced', 'forced.txt', RESOURCELIB_DISPLAY_DOWNLOAD);
        $resource('Window', 'window.txt', RESOURCELIB_DISPLAY_NEW);
        $resource('Hidden', 'hidden.txt', RESOURCELIB_DISPLAY_OPEN, 0);
        // Automatic, often the default: Moodle shows the file in its page (open, or embed), but an archive it downloads.
        $auto = $resource('Auto', 'auto.txt', RESOURCELIB_DISPLAY_AUTO);
        $resource('Archive', 'archive.zip', RESOURCELIB_DISPLAY_AUTO);

        $this->setUser($learner);
        $items = file_preview::items($course);
        $this->assertSame(['Notes', 'Map', 'Auto'], array_column($items, 'name'));
        $this->assertSame([(int) $notes->cmid, (int) $map->cmid, (int) $auto->cmid], array_column($items, 'cmid'));
        $this->assertSame(['text', 'image', 'text'], array_column($items, 'kind'));
        $this->assertStringContainsString('/mod_resource/content/', $items[0]['url']);
        $this->assertStringEndsWith('/notes.txt', $items[0]['url']);
        $this->assertStringContainsString('forcedownload=1', $items[0]['download']);

        $this->setAdminUser();
        $this->assertCount(4, file_preview::items($course), 'The hidden resource for whoever can see it.');
    }

    /**
     * A file opened in the preview counts as a view of its resource: event and completion.
     */
    public function test_viewed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $learner = $generator->create_and_enrol($course, 'student');
        $resource = $generator->create_module('resource', ['course' => $course->id, 'defaultfilename' => 'notes.txt'], [
            'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_REQUIRED,
        ]);
        $this->setUser($learner);
        $sink = $this->redirectEvents();
        $result = \core_external\external_api::clean_returnvalue(
            external\view_resource::execute_returns(),
            external\view_resource::execute((int) $resource->cmid)
        );
        $this->assertTrue($result['status']);
        $events = array_filter($sink->get_events(), fn($event) => $event instanceof \mod_resource\event\course_module_viewed);
        $this->assertCount(1, $events);
        $sink->close();
        $cm = get_fast_modinfo($course)->get_cm($resource->cmid);
        $state = (new \completion_info($course))->get_data($cm, false, $learner->id)->completionstate;
        $this->assertSame(COMPLETION_COMPLETE, (int) $state);
    }
}
