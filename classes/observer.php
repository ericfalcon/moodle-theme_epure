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

use theme_epure\vocabulary\company_form;

/**
 * Event observers of theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Saves the vocabulary posted with IOMAD's company form, once IOMAD saved the company.
     *
     * @param \core\event\base $event block_iomad_company_admin\event\company_created or company_updated.
     */
    public static function company_saved(\core\event\base $event): void {
        $companyid = (int) $event->objectid;
        if (!$companyid || !iomad::installed()) {
            return;
        }
        // The fields are only shown to the users who can change the appearance of the company.
        $context = iomad::company_context($companyid);
        if (iomad::has_capability('block/iomad_company_admin:company_edit_appearance', $context)) {
            company_form::save_from_request($companyid);
        }
        // A company that left Épure gets its frame of certificates back.
        certificate_frame::clean_up_after_request();
    }

    /**
     * Before IOMAD issues the certificate of a completed course, gives back their frame to the companies that left
     * Épure.
     *
     * @param \core\event\course_completed $event Completion of a course.
     */
    public static function course_completed(\core\event\course_completed $event): void {
        if (certificate_frame::in_use()) {
            certificate_frame::clean_up();
        }
    }

    /**
     * Forgets the progress and the deadlines kept in the cache for a learner whose progress changed.
     *
     * @param \core\event\base $event Completion of an activity or a course, or a submission.
     */
    public static function learner_progress_changed(\core\event\base $event): void {
        $userid = (int) ($event->relateduserid ?: $event->userid);
        if (!$userid) {
            return;
        }
        $cache = \cache::make('theme_epure', 'learnerprogress');
        $cache->delete_many([$userid . '_' . (int) $event->courseid, 'deadlines_' . $userid]);
    }

    /**
     * Forgets the progress kept in the cache for all the learners of a course whose activities or completion changed.
     *
     * @param \core\event\base $event Change of an activity, a section or the course.
     */
    public static function course_changed(\core\event\base $event): void {
        if ($event->courseid) {
            \cache::make('theme_epure', 'learnerprogress')->set('course_' . (int) $event->courseid, uniqid('', true));
        }
    }
}
