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
 * Each company can have its own Épure appearance, set in its IOMAD form (Edit company ›
 * Appearance › Épure): brand colour, header colour and font, stored in the theme settings as
 * companystyle_<id> (JSON). IOMAD's own settings are used too: its heading colour (or link
 * colour) is the brand colour when the company has no Épure brand colour, and its logo and
 * custom CSS are applied. The accessible palette is computed for the company colour.
 * On a standard Moodle site, without IOMAD, nothing changes.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_style {
    /** @var \stdClass|false|null Company record of the current user, cached for the request. */
    protected static $company = null;

    /** @var string[] Header styles. */
    public const HEADER_STYLES = ['light', 'brand'];

    /** @var string[] Dark mode: never, following the device, always. */
    public const DARK_MODES = ['light', 'auto', 'dark'];

    /** @var string[] Choices for the course banner and the overview of the learner on the dashboard. */
    public const COURSE_BANNERS = ['show', 'hide'];

    /** @var string[] Footer settings a company can set instead of those of the site. */
    public const FOOTER_FIELDS = ['footertext', 'footerlegalurl', 'footerprivacyurl', 'footercontacturl', 'footerlinks'];

    /**
     * Épure appearance of a company.
     *
     * @param int $companyid Company.
     * @return array<string, string> brandcolor, headerstyle, font, coursebanner, learnerdashboard, mobilenav, darkmode,
     *     activityicons and the
     *     footer fields ({@see self::FOOTER_FIELDS}). Empty values when the company uses the site setting.
     */
    public static function settings(int $companyid): array {
        $json = get_config('theme_epure', 'companystyle_' . $companyid);
        $values = $json ? json_decode($json, true) : [];
        return self::clean(is_array($values) ? $values : []);
    }

    /**
     * Saves the Épure appearance of a company.
     *
     * @param int $companyid Company.
     * @param array $values Values of {@see self::settings()}; empty values mean the site setting.
     */
    public static function save_settings(int $companyid, array $values): void {
        $settings = self::clean($values);
        if (array_filter($settings)) {
            set_config('companystyle_' . $companyid, json_encode($settings), 'theme_epure');
        } else {
            unset_config('companystyle_' . $companyid, 'theme_epure');
        }
        self::reset();
    }

    /**
     * Keeps the valid settings of a company only.
     *
     * @param array $values Values.
     * @return array<string, string>
     */
    protected static function clean(array $values): array {
        $choice = fn(string $name, array $choices) => in_array($values[$name] ?? '', $choices, true) ? $values[$name] : '';
        $settings = [
            'brandcolor' => (string) (palette::normalise($values['brandcolor'] ?? null) ?? ''),
            'headerstyle' => $choice('headerstyle', self::HEADER_STYLES),
            'font' => isset(fonts::all()[$values['font'] ?? '']) ? $values['font'] : '',
            'coursebanner' => $choice('coursebanner', self::COURSE_BANNERS),
            'learnerdashboard' => $choice('learnerdashboard', self::COURSE_BANNERS),
            'mobilenav' => $choice('mobilenav', self::COURSE_BANNERS),
            'darkmode' => $choice('darkmode', self::DARK_MODES),
            'activityicons' => $choice('activityicons', activity_icons::CHOICES),
        ];
        foreach (self::FOOTER_FIELDS as $name) {
            $value = trim((string) ($values[$name] ?? ''));
            $settings[$name] = str_ends_with($name, 'url') ? clean_param($value, PARAM_URL) : $value;
        }
        return $settings;
    }

    /**
     * A footer setting: the one of the company of the user when it has one, else the one of the site.
     *
     * @param string $name One of {@see self::FOOTER_FIELDS}.
     * @return string
     */
    public static function footer_setting(string $name): string {
        $company = self::current_company();
        if ($company && ($value = self::settings((int) $company->id)[$name] ?? '') !== '') {
            return $value;
        }
        return trim((string) get_config('theme_epure', $name));
    }

    /**
     * Header style of the current page: the one of the company of the user, else the site setting.
     *
     * @return string light or brand.
     */
    public static function header_style(): string {
        $company = self::current_company();
        if ($company && ($style = self::settings((int) $company->id)['headerstyle'])) {
            return $style;
        }
        return get_config('theme_epure', 'headerstyle') === 'brand' ? 'brand' : 'light';
    }

    /**
     * Whether the courses show the banner: the choice of the company of the user, else the site setting.
     *
     * @return bool
     */
    public static function course_banner(): bool {
        $company = self::current_company();
        if ($company && ($choice = self::settings((int) $company->id)['coursebanner'])) {
            return $choice === 'show';
        }
        return get_config('theme_epure', 'coursebanner') !== '0';
    }

    /**
     * Whether the dashboard shows the overview of the learner: the choice of the company of the user, else the site setting.
     *
     * @return bool
     */
    public static function learner_dashboard(): bool {
        $company = self::current_company();
        if ($company && ($choice = self::settings((int) $company->id)['learnerdashboard'])) {
            return $choice === 'show';
        }
        return get_config('theme_epure', 'learnerdashboard') !== '0';
    }

    /**
     * Whether phones show the navigation bar at the bottom of the screen: the choice of the company of the user,
     * else the site setting.
     *
     * @return bool
     */
    public static function mobile_nav(): bool {
        $company = self::current_company();
        if ($company && ($choice = self::settings((int) $company->id)['mobilenav'])) {
            return $choice === 'show';
        }
        return get_config('theme_epure', 'mobilenav') !== '0';
    }

    /**
     * Dark mode of the pages when the user did not choose: the one of the company of the user, else the site setting.
     *
     * @return string light (never dark), auto (dark when the device asks for it) or dark (always).
     */
    public static function dark_mode(): string {
        $company = self::current_company();
        if ($company && ($mode = self::settings((int) $company->id)['darkmode'])) {
            return $mode;
        }
        $mode = (string) get_config('theme_epure', 'darkmode');
        return in_array($mode, self::DARK_MODES, true) ? $mode : 'light';
    }

    /**
     * Brand colour of the current page: the one of the company of the user, else the one of the site.
     *
     * @return string Hex colour.
     */
    public static function page_brand(): string {
        $company = self::current_company();
        return ($company ? self::brand_colour($company) : null)
            ?? palette::normalise(get_config('theme_epure', 'brandcolor'))
            ?? palette::DEFAULT_BRAND;
    }

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
     * Logo of a company for the brand-coloured header, set in its Épure appearance.
     *
     * @param int $companyid Company.
     * @return \moodle_url|null
     */
    public static function logo_onbrand_url(int $companyid): ?\moodle_url {
        $context = \context_system::instance();
        $files = get_file_storage()->get_area_files(
            $context->id,
            'theme_epure',
            'companylogoonbrand',
            $companyid,
            'timemodified DESC',
            false
        );
        if (!$file = reset($files)) {
            return null;
        }
        // The time of the file in the address makes browsers fetch a new logo, as the file is cached for long.
        return \moodle_url::make_pluginfile_url(
            $context->id,
            'theme_epure',
            'companylogoonbrand',
            $companyid,
            '/' . $file->get_timemodified() . '/',
            $file->get_filename()
        );
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
     * Brand colour of a company: its Épure brand colour, else its IOMAD heading colour, else its link colour.
     *
     * Colours that are not hex codes are ignored, because the palette is computed from hex values.
     *
     * @param \stdClass $company Company record.
     * @return string|null
     */
    public static function brand_colour(\stdClass $company): ?string {
        $own = self::settings((int) ($company->id ?? 0))['brandcolor'];
        return ($own !== '' ? $own : null)
            ?? palette::normalise($company->headingcolor ?? null)
            ?? palette::normalise($company->linkcolor ?? null);
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
            $d = palette::derive($brand, true);
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
                "--epure-header-bg:{$p['fill']};" .
                "--epure-header-on:{$p['on']};" .
                "--epure-dark-brand:{$d['fill']};" .
                "--epure-dark-brand-hover:{$d['hover']};" .
                "--epure-dark-on-brand:{$d['on']};" .
                "--epure-dark-brand-text:{$d['text']};" .
                "--epure-dark-brand-soft:{$d['soft']};" .
                "--epure-dark-brand-soft-2:{$d['soft2']};" .
                "--epure-login-overlay:rgba({$rgb}, {$p['overlayalpha']});" .
                '--epure-header-hover:' . ($light ? 'rgba(0, 0, 0, .18)' : 'rgba(255, 255, 255, .3)') . ';' .
                '--epure-header-toggler-filter:' . ($light ? 'brightness(0) invert(1)' : 'none') . ';' .
                '--epure-header-divider:' . ($light ? 'rgba(255, 255, 255, .35)' : 'rgba(0, 0, 0, .2)') . ';' .
                '}';
            // Colours compiled into Bootstrap rules, which do not use the custom properties.
            $css .= 'a{color:var(--epure-brand-text);}' .
                '.btn-link{color:var(--epure-brand-text);}' .
                '.text-primary{color:var(--epure-brand-text) !important;}' .
                '.bg-primary,.badge-primary{background-color:var(--epure-brand) !important;color:var(--epure-on-brand);}' .
                '.btn-primary{background-color:var(--epure-brand);border-color:var(--epure-brand);color:var(--epure-on-brand);}' .
                '.btn-primary:hover,.btn-primary:focus{background-color:var(--epure-brand-hover);' .
                'border-color:var(--epure-brand-hover);color:var(--epure-on-brand);}';
        }
        // The font of the company: its faces, then the font of the text.
        if ($font = self::settings((int) ($company->id ?? 0))['font']) {
            global $PAGE;
            $css .= fonts::font_face_css($font, fn($file) => $PAGE->theme->font_url($file, 'theme')->out(false));
            $css .= 'body,.tooltip,.popover{font-family:' . fonts::stack($font) . ';}';
        }
        if (!empty($company->customcss)) {
            // The custom CSS of the company, as IOMAD's own theme applies it. A closing style
            // tag would end the style element early, so it is neutralised.
            $css .= str_ireplace('</style', '<\/style', (string) $company->customcss);
        }
        return $css;
    }

    /**
     * Direct access to the course category of a company, with its subcategories, for those who may see it.
     *
     * Those who manage courses in it get the management page, the others the list of its courses.
     *
     * @param object $company Company, with its course category.
     * @return array|null Address, label and name of the category, or null.
     */
    public static function company_category(object $company): ?array {
        $category = empty($company->category) ? null : \core_course_category::get((int) $company->category, IGNORE_MISSING);
        if (!$category) {
            return null;
        }
        $context = \context_coursecat::instance($category->id);
        if (has_any_capability(['moodle/category:manage', 'moodle/course:create', 'moodle/course:update'], $context)) {
            $url = new \moodle_url('/course/management.php', ['categoryid' => $category->id]);
        } else {
            $url = new \moodle_url('/course/index.php', ['categoryid' => $category->id]);
        }
        $name = $category->get_formatted_name();
        return ['url' => $url->out(false), 'name' => $name, 'label' => get_string(
            'iomadcompanycategory',
            'theme_epure',
            // The core words for course categories, so that the vocabulary of the platform applies.
            (object) ['categories' => get_string('coursecategories'), 'name' => $name]
        )];
    }
}
