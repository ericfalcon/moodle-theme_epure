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
 * Key figures of an IOMAD company, at the top of IOMAD's dashboard.
 *
 * Users (and those active this week), courses, licences used out of those allocated, and courses
 * completed over the last 30 days. Each figure leads to the page of IOMAD where it is managed.
 * The figures are read from IOMAD's own tables, for the company selected in the dashboard only.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_figures {
    /** @var int Period of the completions counted, in days. */
    public const COMPLETION_DAYS = 30;

    /**
     * Context of the key figures of a company, for the template theme_epure/iomad_dashboard.
     *
     * @param int $companyid Company.
     * @return array[] Figures, each with label, value, detail and url.
     */
    public static function export(int $companyid): array {
        global $DB;
        $dbman = $DB->get_manager();
        $now = time();
        $figures = [];
        $url = fn(string $path, array $params = []) => (new \moodle_url($path, $params))->out(false);

        // Users of the company who are not suspended, and those who came this week.
        $users = $DB->get_record_sql(
            "SELECT COUNT(1) AS total, SUM(CASE WHEN u.lastaccess > :since THEN 1 ELSE 0 END) AS active
               FROM {company_users} cu
               JOIN {user} u ON u.id = cu.userid AND u.deleted = 0 AND u.suspended = 0
              WHERE cu.companyid = :companyid AND cu.suspended = 0",
            ['companyid' => $companyid, 'since' => $now - WEEKSECS]
        );
        $figures[] = [
            'key' => 'users',
            'label' => get_string('users'),
            'value' => (int) $users->total,
            'detail' => get_string('iomadfigures_active', 'theme_epure', (int) $users->active),
            'url' => $url('/blocks/iomad_company_admin/editusers.php'),
        ];

        // Courses of the company.
        $figures[] = [
            'key' => 'courses',
            'label' => get_string('courses'),
            'value' => $DB->count_records('company_course', ['companyid' => $companyid]),
            'detail' => '',
            'url' => $url('/blocks/iomad_company_admin/iomad_courses_form.php'),
        ];

        // Licences still valid: places used out of those allocated.
        $licences = $DB->get_record_sql(
            "SELECT COUNT(1) AS total, SUM(allocation) AS allocated, SUM(used) AS used
               FROM {companylicense}
              WHERE companyid = :companyid AND (expirydate = 0 OR expirydate > :now)",
            ['companyid' => $companyid, 'now' => $now]
        );
        if ((int) $licences->total) {
            $figures[] = [
                'key' => 'licences',
                'label' => get_string('licensemanagement', 'block_iomad_company_admin'),
                'value' => (int) $licences->used,
                'detail' => get_string('iomadfigures_allocated', 'theme_epure', (int) $licences->allocated),
                'url' => $url('/blocks/iomad_company_admin/company_license_list.php'),
            ];
        }

        // Courses completed over the last days.
        if ($dbman->table_exists('local_iomad_track')) {
            $figures[] = [
                'key' => 'completions',
                'label' => get_string('iomadfigures_completions', 'theme_epure'),
                'value' => $DB->count_records_select(
                    'local_iomad_track',
                    'companyid = :companyid AND timecompleted > :since',
                    ['companyid' => $companyid, 'since' => $now - self::COMPLETION_DAYS * DAYSECS]
                ),
                'detail' => get_string('iomadfigures_days', 'theme_epure', self::COMPLETION_DAYS),
                'url' => $url('/local/report_completion/index.php'),
            ];
        }
        return $figures;
    }
}
