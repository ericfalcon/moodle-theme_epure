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
 * Tests of the scores of accessibility of the courses, week by week, for the management dashboard.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\accessibility_scores
 * @covers     \theme_epure\task\compute_accessibility_scores
 * @covers     \theme_epure\task\refresh_accessibility_score
 */
final class accessibility_scores_test extends \advanced_testcase {
    /**
     * A course with an image without a description (a blocking mistake), in a category.
     *
     * @param int $category Category.
     * @return \stdClass
     */
    protected function course_with_mistake(int $category): \stdClass {
        $course = $this->getDataGenerator()->create_course(['category' => $category, 'fullname' => 'With a mistake',
            'summary' => '<p>Rules of safety.</p>']);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id,
            'content' => '<p>Rules</p><img src="https://example.com/a.png">']);
        return get_course($course->id);
    }

    /**
     * The week starts on Monday at midnight UTC, whatever the time of the week.
     */
    public function test_week(): void {
        // Monday 16 November 2026, 00:00 UTC.
        $monday = 1794787200;
        $this->assertSame('Monday 00:00', gmdate('l H:i', $monday));
        $this->assertSame($monday, accessibility_scores::week($monday));
        $this->assertSame($monday, accessibility_scores::week($monday + 3 * DAYSECS + 5 * HOURSECS));
        $this->assertSame($monday, accessibility_scores::week($monday + WEEKSECS - 1));
        $this->assertSame($monday + WEEKSECS, accessibility_scores::week($monday + WEEKSECS));
    }

    /**
     * The task reads each course once a week, again when it changes; the score is the one of its guide.
     */
    public function test_compute(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        $mistake = $this->course_with_mistake((int) $category->id);
        $clean = get_course($this->getDataGenerator()->create_course(['fullname' => 'Clean', 'summary' => '<p>Clean.</p>'])->id);
        $now = 1794787200 + 2 * DAYSECS;

        $this->assertSame(2, accessibility_scores::compute($now));
        $record = $DB->get_record(accessibility_scores::TABLE, ['courseid' => $mistake->id]);
        $figures = course_accessibility::figures(course_accessibility::analyse($mistake), []);
        $this->assertSame($figures['score'], (int) $record->score);
        $this->assertLessThan(100, (int) $record->score);
        $this->assertSame(1, (int) $record->blocking);
        $this->assertSame(accessibility_scores::week($now), (int) $record->weekstart);
        $this->assertSame(100, (int) $DB->get_field(accessibility_scores::TABLE, 'score', ['courseid' => $clean->id]));

        // Nothing changed this week: nothing read again.
        $this->assertSame(0, accessibility_scores::compute($now + HOURSECS));
        // A course changed: read again, in the same row of the week.
        rebuild_course_cache($clean->id);
        $this->assertSame(1, accessibility_scores::compute($now + 2 * HOURSECS));
        $this->assertSame(2, $DB->count_records(accessibility_scores::TABLE));
        // A new week: every course is read again, the weeks before kept.
        $this->assertSame(2, accessibility_scores::compute($now + WEEKSECS));
        $this->assertSame(4, $DB->count_records(accessibility_scores::TABLE));

        // After a year, the oldest week is forgotten.
        accessibility_scores::compute($now + accessibility_scores::WEEKS * WEEKSECS);
        $this->assertSame(0, $DB->count_records(accessibility_scores::TABLE, ['weekstart' => accessibility_scores::week($now)]));

        // A course deleted: its scores too.
        delete_course($mistake->id, false);
        $this->assertSame(0, $DB->count_records(accessibility_scores::TABLE, ['courseid' => $mistake->id]));
    }

    /**
     * A course changed: one ad hoc task, a few minutes after its last change, computes its score again, in the row of
     * the week; none when the guide is off.
     */
    public function test_refresh_after_a_change(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('courseaccessibility', '1', 'theme_epure');
        $category = $this->getDataGenerator()->create_category();
        $course = $this->course_with_mistake((int) $category->id);
        $tasks = fn() => \core\task\manager::get_adhoc_tasks(task\refresh_accessibility_score::class);
        // The page and the course created: one task for the course, not one per change.
        $this->assertCount(1, $tasks());
        $task = current($tasks());
        $this->assertSame((int) $course->id, (int) $task->get_custom_data()->courseid);
        $this->assertGreaterThan(time(), $task->get_next_run_time());

        accessibility_scores::compute();
        $before = (int) $DB->get_field(accessibility_scores::TABLE, 'score', ['courseid' => $course->id]);
        // The mistake corrected (the image described), the course changed: its score is computed again.
        $content = '<p>Rules</p><img src="https://example.com/a.png" alt="Gloves on">';
        $DB->set_field('page', 'content', $content, ['course' => $course->id]);
        course_accessibility::forget((int) $course->id);
        $this->assertCount(1, $tasks());
        ob_start();
        current($tasks())->execute();
        ob_end_clean();
        $this->assertSame(1, $DB->count_records(accessibility_scores::TABLE, ['courseid' => $course->id]));
        $this->assertGreaterThan($before, (int) $DB->get_field(accessibility_scores::TABLE, 'score', ['courseid' => $course->id]));

        // A course deleted meanwhile: nothing.
        $DB->delete_records('task_adhoc');
        delete_course($course->id, false);
        $task = new task\refresh_accessibility_score();
        $task->set_custom_data(['courseid' => $course->id]);
        $task->execute();
        $this->assertSame(0, $DB->count_records(accessibility_scores::TABLE, ['courseid' => $course->id]));

        // The guide off: no task.
        set_config('courseaccessibility', '0', 'theme_epure');
        $other = $this->getDataGenerator()->create_course();
        course_accessibility::forget((int) $other->id);
        $this->assertCount(0, $tasks());
    }

    /**
     * A run stops after its time, the other courses waiting for the next one; at least one course is read.
     */
    public function test_time_limit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        foreach (range(1, 3) as $i) {
            $this->getDataGenerator()->create_course();
        }
        $this->assertSame(1, accessibility_scores::compute(1794787200, -1));
        $this->assertSame(2, accessibility_scores::compute(1794787200));
    }

    /**
     * The dashboard: the average, the lowest courses, by category, the scope of a category, the trend.
     */
    public function test_export(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $health = $this->getDataGenerator()->create_category(['name' => 'Health']);
        $mistake = $this->course_with_mistake((int) $health->id);
        $this->getDataGenerator()->create_course(['fullname' => 'Clean', 'summary' => '<p>Clean.</p>']);
        $now = 1794787200 + DAYSECS;
        accessibility_scores::compute($now);
        $score = (int) $DB->get_field(accessibility_scores::TABLE, 'score', ['courseid' => $mistake->id]);

        $export = accessibility_scores::export($now);
        $this->assertTrue($export['hasdata']);
        $this->assertSame((int) round(($score + 100) / 2), $export['score']);
        $this->assertSame(get_string('a11yscores_blocking', 'theme_epure', 1), $export['blockingtext']);
        // Only the courses below 100 % are to look at.
        $this->assertCount(1, $export['courses']);
        $this->assertSame('With a mistake', $export['courses'][0]['name']);
        $this->assertStringContainsString('courseaccessibility.php', $export['courses'][0]['url']);
        $this->assertSame('Health', $export['categories'][0]['name']);
        $this->assertTrue($export['hascategories']);
        // One week only: no trend yet.
        $this->assertFalse($export['hasweeks']);

        // The category only.
        $export = accessibility_scores::export($now, (int) $health->id);
        $this->assertSame($score, $export['score']);
        $this->assertSame(get_string('a11yscores_courses_one', 'theme_epure'), $export['coursestext']);
        $selected = array_values(array_filter($export['categorychoices'], fn($choice) => $choice['selected']));
        $this->assertSame((int) $health->id, $selected[0]['value']);

        // The mistake corrected the week after: the trend goes up.
        $DB->set_field('page', 'content', '<p>Rules</p>', ['course' => $mistake->id]);
        rebuild_course_cache($mistake->id);
        course_accessibility::forget((int) $mistake->id);
        accessibility_scores::compute($now + WEEKSECS);
        $export = accessibility_scores::export($now + WEEKSECS);
        $this->assertTrue($export['hasweeks']);
        $after = (int) $DB->get_field(accessibility_scores::TABLE, 'score', ['courseid' => $mistake->id,
            'weekstart' => accessibility_scores::week($now + WEEKSECS)]);
        $this->assertGreaterThan($score, $after);
        $before = (int) round(($score + 100) / 2);
        $this->assertSame((int) round(($after + 100) / 2), $export['weeks'][0]['score']);
        $this->assertSame($before, $export['weeks'][1]['score']);
        $this->assertSame(get_string('a11yscores_change_up', 'theme_epure', (object) [
            'points' => $export['weeks'][0]['score'] - $before,
            'weeks' => 1,
        ]), $export['change']);
        // No blocking mistake left.
        $this->assertSame(get_string('a11yscores_blocking', 'theme_epure', 0), $export['blockingtext']);
    }

    /**
     * Nothing read yet: the dashboard says when it will be; the guide off: the task reads nothing.
     */
    public function test_none_and_off(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->create_course();
        $this->assertFalse(accessibility_scores::export(time())['hasdata']);

        set_config('courseaccessibility', '0', 'theme_epure');
        $task = new task\compute_accessibility_scores();
        ob_start();
        $task->execute();
        ob_end_clean();
        $this->assertSame(0, $DB->count_records(accessibility_scores::TABLE));
        $this->assertNull(management::export(time())['accessibility']);
        $this->assertFalse(accessibility_scores::active());

        // The site off, an IOMAD company that turned the guide on for itself: the scores are computed, its dashboard
        // shows them, and so does the one of the platform.
        set_config('companystyle_5', json_encode(['courseaccessibility' => 'show']), 'theme_epure');
        $this->assertTrue(accessibility_scores::active());
        $this->assertTrue(accessibility_scores::shown(5));
        $this->assertTrue(accessibility_scores::shown(0));
        $this->assertFalse(accessibility_scores::shown(6));
        unset_config('companystyle_5', 'theme_epure');
        $this->assertFalse(accessibility_scores::active());

        set_config('courseaccessibility', '1', 'theme_epure');
        ob_start();
        $task->execute();
        ob_end_clean();
        $this->assertSame(1, $DB->count_records(accessibility_scores::TABLE));
        $this->assertTrue(management::export(time())['accessibility']['hasdata']);
    }
}
