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
 * Everything Épure uses of IOMAD, in one place: its API, its tables and the addresses of its pages.
 *
 * IOMAD 5.1 renamed its tables (company becomes local_iomad_companies, company_users local_iomad_company_users…),
 * moved its iomad class to local_iomad\iomad (lib/iomad.php is gone) and its company context to
 * local_iomad\custom_context\context_company. The rest of the theme asks this class, which knows both, so that a
 * change of IOMAD is handled here only.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad {
    /** @var array<string, string> Tables of IOMAD before 5.1, and their name from IOMAD 5.1. */
    public const TABLES = [
        'company' => 'local_iomad_companies',
        'company_users' => 'local_iomad_company_users',
        'department' => 'local_iomad_company_departments',
        'company_course' => 'local_iomad_company_courses',
        'companylicense' => 'local_iomad_company_licenses',
        'iomad_courses' => 'local_iomad_courses',
        'companycertificate' => 'local_iomad_company_certificates',
        'local_iomad_track' => 'local_iomad_tracks',
        'local_iomad_track_certs' => 'local_iomad_track_certs',
    ];

    /** @var array<string, string> Pages of IOMAD the theme links to. */
    public const PAGES = [
        'dashboard' => '/blocks/iomad_company_admin/index.php',
        'company' => '/blocks/iomad_company_admin/company_edit_form.php',
        'users' => '/blocks/iomad_company_admin/editusers.php',
        'courses' => '/blocks/iomad_company_admin/iomad_courses_form.php',
        'licenses' => '/blocks/iomad_company_admin/company_license_list.php',
        'completionreport' => '/local/report_completion/index.php',
    ];

    /** @var bool|null Whether IOMAD is installed, once known. */
    protected static ?bool $installed = null;

    /**
     * Whether IOMAD is installed: before 5.1, local/iomad/lib/iomad.php; from 5.1, the class local_iomad\iomad.
     *
     * @return bool
     */
    public static function installed(): bool {
        global $CFG;
        if (self::$installed === null) {
            self::$installed = file_exists($CFG->dirroot . '/local/iomad/lib/iomad.php')
                || (\core_component::get_component_directory('local_iomad') !== null && class_exists('\local_iomad\iomad'));
        }
        return self::$installed;
    }

    /**
     * Whether IOMAD is 5.1 or later (renamed tables and classes).
     *
     * @return bool
     */
    public static function renamed(): bool {
        global $CFG;
        return self::installed() && !file_exists($CFG->dirroot . '/local/iomad/lib/iomad.php');
    }

    /**
     * The class of IOMAD's API, loaded.
     *
     * @return string Class name.
     */
    protected static function api(): string {
        global $CFG;
        if (self::renamed()) {
            return '\local_iomad\iomad';
        }
        require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
        return '\iomad';
    }

    /**
     * Name of an IOMAD table in the version installed.
     *
     * @param string $name Name before IOMAD 5.1 (a key of TABLES).
     * @return string
     */
    public static function table(string $name): string {
        return self::renamed() ? (self::TABLES[$name] ?? $name) : $name;
    }

    /**
     * Company selected by or of the current user: the company an administrator works on, else the company of the
     * user.
     *
     * @return int Company id, 0 without.
     */
    public static function my_companyid(): int {
        if (!self::installed()) {
            return 0;
        }
        $api = self::api();
        return max(0, (int) $api::get_my_companyid(\context_system::instance(), false));
    }

    /**
     * Whether the current user has a capability, the IOMAD way (in the company).
     *
     * @param string $capability Capability.
     * @param \context $context Context.
     * @return bool
     */
    public static function has_capability(string $capability, \context $context): bool {
        $api = self::api();
        return (bool) $api::has_capability($capability, $context);
    }

    /**
     * The categories the current user may see, as IOMAD filters them for the companies.
     *
     * @param array $categories Categories.
     * @return array
     */
    public static function filter_categories(array $categories): array {
        $api = self::api();
        return $api::iomad_filter_categories($categories);
    }

    /**
     * Context of a company.
     *
     * @param int $companyid Company.
     * @return \context
     */
    public static function company_context(int $companyid): \context {
        if (self::renamed()) {
            return \local_iomad\custom_context\context_company::instance($companyid);
        }
        return \core\context\company::instance($companyid);
    }

    /**
     * A company.
     *
     * @param int $companyid Company.
     * @return \stdClass|null
     */
    public static function company(int $companyid): ?\stdClass {
        global $DB;
        if (!$companyid || !self::installed()) {
            return null;
        }
        return $DB->get_record(self::table('company'), ['id' => $companyid]) ?: null;
    }

    /**
     * Address of a page of IOMAD.
     *
     * @param string $page Key of PAGES.
     * @param array $params Parameters.
     * @return \moodle_url
     */
    public static function url(string $page, array $params = []): \moodle_url {
        return new \moodle_url(self::PAGES[$page], $params);
    }

    /**
     * Forgets what is known of IOMAD (for tests).
     */
    public static function reset(): void {
        self::$installed = null;
    }
}
