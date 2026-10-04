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
 * Appearance of the current IOMAD company.
 *
 * IOMAD stores, for each company, a header colour, a main colour, a link colour,
 * custom CSS and a logo. Épure applies them on top of its own settings: the company
 * colour becomes the brand colour, and the accessible palette is computed for it.
 * On a standard Moodle site, without IOMAD, nothing changes.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_style {
    /** @var \stdClass|false|null Company record of the current user, cached for the request. */
    protected static $company = null;

    /**
     * Whether IOMAD is installed.
     *
     * @return bool
     */
    public static function iomad_installed(): bool {
        global $CFG;
        return file_exists($CFG->dirroot . '/local/iomad/lib/iomad.php');
    }

    /**
     * Logo of a company, stored by IOMAD as core_admin/logo<id> and logocompact<id>.
     *
     * @param int $companyid Company.
     * @param string[] $settings Settings to try, in order: logo, logocompact.
     * @return \moodle_url|null
     */
    public static function logo_url(int $companyid, array $settings = ['logo', 'logocompact']): ?\moodle_url {
        foreach ($settings as $setting) {
            $file = (string) get_config('core_admin', $setting . $companyid);
            if ($file !== '') {
                return \moodle_url::make_pluginfile_url(
                    \context_system::instance()->id,
                    'core_admin',
                    $setting . $companyid,
                    '300x200/',
                    theme_get_revision(),
                    $file
                );
            }
        }
        return null;
    }

    /**
     * Company of the current user, or null on a standard Moodle site or without a company.
     *
     * @return \stdClass|null
     */
    public static function current_company(): ?\stdClass {
        global $CFG, $DB;
        if (self::$company === null) {
            self::$company = false;
            if (self::iomad_installed() && isloggedin() && !during_initial_install()) {
                require_once($CFG->dirroot . '/local/iomad/lib/iomad.php');
                $companyid = (int) \iomad::get_my_companyid(\context_system::instance(), false);
                if ($companyid > 0) {
                    self::$company = $DB->get_record('company', ['id' => $companyid]) ?: false;
                }
            }
        }
        return self::$company ?: null;
    }

    /**
     * Forgets the cached company (used by tests and after a company switch).
     */
    public static function reset(): void {
        self::$company = null;
    }

    /**
     * Brand colour of a company: its header colour, or else its link colour.
     *
     * Colours that are not hex codes are ignored, because the palette is computed from hex values.
     *
     * @param \stdClass $company Company record.
     * @return string|null
     */
    public static function brand_colour(\stdClass $company): ?string {
        return palette::normalise($company->headingcolor ?? null) ?? palette::normalise($company->linkcolor ?? null);
    }

    /**
     * CSS applying a company's appearance on top of the theme.
     *
     * @param \stdClass $company Company record.
     * @return string CSS, empty when the company sets nothing.
     */
    public static function css(\stdClass $company): string {
        $css = '';
        if ($brand = self::brand_colour($company)) {
            $p = palette::derive($brand);
            $light = $p['on'] === palette::SURFACE_LIGHT;
            $rgb = implode(', ', palette::to_rgb($p['fill']));
            $css .= ':root{' .
                "--epure-brand:{$p['fill']};" .
                "--epure-brand-hover:{$p['hover']};" .
                "--epure-on-brand:{$p['on']};" .
                "--epure-brand-text:{$p['text']};" .
                "--epure-brand-soft:{$p['soft']};" .
                "--epure-brand-soft-2:{$p['soft2']};" .
                "--epure-focus:{$p['text']};" .
                "--epure-login-overlay:rgba({$rgb}, {$p['overlayalpha']});" .
                '--epure-header-hover:' . ($light ? 'rgba(0, 0, 0, .18)' : 'rgba(255, 255, 255, .3)') . ';' .
                '}';
            // Colours compiled into Bootstrap rules, which do not use the custom properties.
            $css .= 'a{color:var(--epure-brand-text);}' .
                '.btn-link{color:var(--epure-brand-text);}' .
                '.text-primary{color:var(--epure-brand-text) !important;}' .
                '.bg-primary,.badge-primary{background-color:var(--epure-brand) !important;color:var(--epure-on-brand);}' .
                '.navbar.fixed-top .navbar-toggler-icon{filter:' . ($light ? 'brightness(0) invert(1)' : 'none') . ';}';
        }
        if (!empty($company->customcss)) {
            // The custom CSS of the company, as IOMAD's own theme applies it. A closing style
            // tag would end the style element early, so it is neutralised.
            $css .= str_ireplace('</style', '<\/style', (string) $company->customcss);
        }
        return $css;
    }
}
