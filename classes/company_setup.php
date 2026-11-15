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
 * Getting started with Épure for an IOMAD company: the sections of Épure of IOMAD's company form, one after the other,
 * as they are in the form (identity: the logos and the colours; the login page; the footer; the vocabulary), with the
 * steps and the buttons Previous and Next, then IOMAD's button to save the company (theme_epure/company_vocabulary).
 * The same fields, posted with the company in one go. Offered on IOMAD's dashboard to whoever can change the
 * appearance of a company that uses Épure, until it is saved this way or dismissed.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_setup {
    /** @var string[] The sections of Épure of the company form shown as steps, in their order. */
    public const STEPS = ['brand', 'login', 'footer', 'vocabulary'];

    /** @var string Parameter of the company form: the getting started. */
    public const PARAM = 'epuresetup';

    /** @var string Field posted with the company form at the end of the getting started. */
    public const DONE_FIELD = 'epure_setupdone';

    /**
     * Configuration of the theme: the getting started of a company is finished or dismissed (not a setting).
     *
     * @param int $companyid Company.
     * @return string
     */
    protected static function done_name(int $companyid): string {
        return 'setupdone_company' . $companyid;
    }

    /**
     * Whether the user can change the appearance of a company that uses Épure.
     *
     * @param \stdClass|null $company Company.
     * @return bool
     */
    public static function allowed(?\stdClass $company): bool {
        return $company && ($company->theme ?? '') === 'epure' && iomad::has_capability(
            'block/iomad_company_admin:company_edit_appearance',
            iomad::company_context((int) $company->id)
        );
    }

    /**
     * Whether the getting started of a company is offered: on its dashboard, until finished or dismissed.
     *
     * @param \stdClass|null $company Company.
     * @return bool
     */
    public static function offered(?\stdClass $company): bool {
        return self::allowed($company) && !get_config('theme_epure', self::done_name((int) $company->id));
    }

    /**
     * Marks the getting started of a company as finished or dismissed.
     *
     * @param int $companyid Company.
     */
    public static function finish(int $companyid): void {
        set_config(self::done_name($companyid), 1, 'theme_epure');
    }

    /**
     * Address of the getting started of the current company: IOMAD's company form, guided.
     *
     * @return \moodle_url
     */
    public static function url(): \moodle_url {
        return iomad::url('company', [self::PARAM => 1]);
    }

    /**
     * Address to dismiss the getting started of a company.
     *
     * @param int $companyid Company.
     * @return \moodle_url
     */
    public static function dismiss_url(int $companyid): \moodle_url {
        return new \moodle_url('/theme/epure/company_setup.php', ['id' => $companyid, 'sesskey' => sesskey()]);
    }

    /**
     * Context of the offer on IOMAD's dashboard: null when not offered.
     *
     * @param \stdClass|null $company Company.
     * @return array|null
     */
    public static function offer(?\stdClass $company): ?array {
        if (!self::offered($company)) {
            return null;
        }
        return [
            'name' => format_string($company->name),
            'url' => self::url()->out(false),
            'dismissurl' => self::dismiss_url((int) $company->id)->out(false),
        ];
    }
}
