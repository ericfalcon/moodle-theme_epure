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

namespace theme_epure\vocabulary;

/**
 * The Épure fields added to the IOMAD company form (Create company and Edit company): appearance
 * (brand colour, header, font) and vocabulary.
 *
 * IOMAD's form has no extension point: the fields are rendered at the end of the page and moved
 * into the Appearance section by the AMD module theme_epure/company_vocabulary. They are posted
 * with IOMAD's form, prefixed with « epure_ » so that IOMAD ignores them, and saved when IOMAD
 * reports that the company was created or updated (see {@see \theme_epure\observer}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_form {
    /** @var string Prefix of the fields in IOMAD's form. */
    public const PREFIX = 'epure_';

    /** @var string Field telling that the vocabulary fields were posted. */
    public const MARKER = 'epure_vocab';

    /**
     * Context of the template theme_epure/company_vocabulary_fields.
     *
     * @param int $companyid Company edited, 0 for a new company.
     * @return array
     */
    public static function context(int $companyid): array {
        $values = $companyid ? company::values($companyid) : [];
        $platform = company::values(0);
        $presets = terms::presets();
        $translations = get_string_manager()->get_list_of_translations(true);
        $languages = [];
        foreach (manager::languages() as $lang) {
            $concepts = [];
            foreach (terms::IOMAD_CONCEPTS as $concept) {
                $name = terms::setting($lang, $concept);
                $current = self::posted($name, $values[$name] ?? '');
                $inherited = terms::chosen($lang, $concept, $platform)
                    ?? $presets[$lang][$concept][terms::DEFAULTS[$lang][$concept]];
                $options = [['value' => '', 'label' => get_string('vocabplatform', 'theme_epure', $inherited['plural']),
                    'selected' => $current === '']];
                foreach ($presets[$lang][$concept] as $key => $term) {
                    $options[] = ['value' => $key, 'label' => $term['singular'] . ' / ' . $term['plural'],
                        'selected' => $current === (string) $key];
                }
                $options[] = ['value' => terms::CUSTOM, 'label' => get_string('vocabcustom', 'theme_epure'),
                    'selected' => $current === terms::CUSTOM];
                $field = fn(string $suffix) => self::posted(
                    terms::setting($lang, $concept, $suffix),
                    $values[terms::setting($lang, $concept, $suffix)] ?? ''
                );
                $gender = $field('gender') ?: 'm';
                $concepts[] = [
                    'name' => self::PREFIX . $name,
                    'label' => get_string('vocab_' . $concept, 'theme_epure'),
                    'options' => $options,
                    'custom' => $current === terms::CUSTOM,
                    'singular' => $field('singular'),
                    'plural' => $field('plural'),
                    'hasgender' => $lang === 'fr',
                    'masculine' => $gender !== 'f',
                    'feminine' => $gender === 'f',
                ];
            }
            $languages[] = ['lang' => $lang, 'name' => $translations[$lang], 'concepts' => $concepts];
        }
        return ['marker' => self::MARKER, 'languages' => $languages] + self::appearance_context($companyid);
    }

    /**
     * Context of the Épure appearance fields: brand colour, colours of the logo, header, font, course banner.
     *
     * @param int $companyid Company edited, 0 for a new company.
     * @return array
     */
    protected static function appearance_context(int $companyid): array {
        $settings = $companyid ? \theme_epure\company_style::settings($companyid)
            : array_fill_keys(
                ['brandcolor', 'headerstyle', 'font', 'coursebanner', 'learnerdashboard', 'mobilenav', 'darkmode'],
                ''
            )
                + array_fill_keys(\theme_epure\company_style::FOOTER_FIELDS, '');
        $brand = self::posted('brandcolor', $settings['brandcolor']);
        $header = self::posted('headerstyle', $settings['headerstyle']);
        $font = self::posted('font', $settings['font']);
        $banner = self::posted('coursebanner', $settings['coursebanner']);
        $dashboard = self::posted('learnerdashboard', $settings['learnerdashboard']);
        $mobilenav = self::posted('mobilenav', $settings['mobilenav']);
        $darkmode = self::posted('darkmode', $settings['darkmode']);
        $logo = $companyid ? \theme_epure\company_style::logo_url($companyid) : null;

        $sitebrand = \theme_epure\palette::normalise(get_config('theme_epure', 'brandcolor'))
            ?? \theme_epure\palette::DEFAULT_BRAND;
        $siteheader = get_config('theme_epure', 'headerstyle') === 'brand'
            ? get_string('headerstylebrand', 'theme_epure') : get_string('headerstylelight', 'theme_epure');
        $sitefont = \theme_epure\fonts::get(get_config('theme_epure', 'font') ?: null)['family'];

        $option = fn(string $value, string $label, string $current) => ['value' => $value, 'label' => $label,
            'selected' => $value === $current];
        $headers = [$option('', get_string('companysite', 'theme_epure', $siteheader), $header),
            $option('light', get_string('headerstylelight', 'theme_epure'), $header),
            $option('brand', get_string('headerstylebrand', 'theme_epure'), $header)];
        $sitebanner = get_string(
            get_config('theme_epure', 'coursebanner') !== '0' ? 'coursebannershow' : 'coursebannerhide',
            'theme_epure'
        );
        $banners = [$option('', get_string('companysite', 'theme_epure', $sitebanner), $banner),
            $option('show', get_string('coursebannershow', 'theme_epure'), $banner),
            $option('hide', get_string('coursebannerhide', 'theme_epure'), $banner)];
        $sitedashboard = get_string(
            get_config('theme_epure', 'learnerdashboard') !== '0' ? 'coursebannershow' : 'coursebannerhide',
            'theme_epure'
        );
        $dashboards = [$option('', get_string('companysite', 'theme_epure', $sitedashboard), $dashboard),
            $option('show', get_string('coursebannershow', 'theme_epure'), $dashboard),
            $option('hide', get_string('coursebannerhide', 'theme_epure'), $dashboard)];
        $sitemobilenav = get_string(
            get_config('theme_epure', 'mobilenav') !== '0' ? 'coursebannershow' : 'coursebannerhide',
            'theme_epure'
        );
        $mobilenavs = [$option('', get_string('companysite', 'theme_epure', $sitemobilenav), $mobilenav),
            $option('show', get_string('coursebannershow', 'theme_epure'), $mobilenav),
            $option('hide', get_string('coursebannerhide', 'theme_epure'), $mobilenav)];
        $sitedark = (string) get_config('theme_epure', 'darkmode');
        $sitedark = get_string('darkmode' . (in_array($sitedark, ['auto', 'dark'], true) ? $sitedark : 'light'), 'theme_epure');
        $darkmodes = [$option('', get_string('companysite', 'theme_epure', $sitedark), $darkmode)];
        foreach (\theme_epure\company_style::DARK_MODES as $mode) {
            $darkmodes[] = $option($mode, get_string('darkmode' . $mode, 'theme_epure'), $darkmode);
        }
        $fonts = [$option('', get_string('companysite', 'theme_epure', $sitefont), $font)];
        foreach (\theme_epure\fonts::all() as $key => $definition) {
            $fonts[] = $option($key, $definition['family'], $font);
        }
        return [
            'logoonbrand' => self::logo_onbrand_manager($companyid),
            'brandcolor' => $brand,
            'sitebrandcolor' => $sitebrand,
            'headerstyles' => $headers,
            'fonts' => $fonts,
            'coursebanners' => $banners,
            'learnerdashboards' => $dashboards,
            'mobilenavs' => $mobilenavs,
            'darkmodes' => $darkmodes,
            'footer' => array_map(fn($name) => [
                'name' => $name,
                'label' => get_string($name === 'footerlinks' ? 'footerlinksetting' : $name, 'theme_epure'),
                'value' => self::posted($name, $settings[$name]),
                'multiline' => in_array($name, ['footertext', 'footerlinks'], true),
                // The value of the site, used while the company has none.
                'placeholder' => trim((string) get_config('theme_epure', $name)),
            ], \theme_epure\company_style::FOOTER_FIELDS),
            'logocolours' => [
                'id' => 'epure-company-logocolours',
                'inputid' => 'id_epure_brandcolor',
                'logourl' => $logo ? $logo->out(false) : null,
                'help' => get_string('logocolours_company_help', 'theme_epure'),
                'none' => get_string('logocolours_company_none', 'theme_epure'),
            ],
        ];
    }

    /**
     * The value posted for a field, when the form is displayed again after an error.
     *
     * @param string $name Setting name, without prefix.
     * @param string $default Saved value.
     * @return string
     */
    protected static function posted(string $name, string $default): string {
        return trim(optional_param(self::PREFIX . $name, $default, PARAM_TEXT));
    }

    /** @var array Options of the file area of the logo for the brand-coloured header. */
    protected const LOGO_OPTIONS = ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.svg', '.png', '.webp', '.jpg']];

    /**
     * File manager of the logo for the brand-coloured header, with the logo saved for the company.
     *
     * @param int $companyid Company edited, 0 for a new company.
     * @return array HTML of the file manager, and the draft area posted with the form.
     */
    protected static function logo_onbrand_manager(int $companyid): array {
        global $CFG, $PAGE;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/repository/lib.php');
        require_once($CFG->libdir . '/form/filemanager.php');
        $context = \context_system::instance();

        // Displayed again after an error: the draft area posted keeps the files chosen.
        $draftid = optional_param(self::PREFIX . 'logoonbrand', 0, PARAM_INT);
        if (!$draftid) {
            // Without a draft area, Moodle creates one and copies the saved logo into it.
            file_prepare_draft_area(
                $draftid,
                $context->id,
                'theme_epure',
                'companylogoonbrand',
                $companyid ?: null,
                self::LOGO_OPTIONS
            );
        }
        $options = (object) (self::LOGO_OPTIONS + [
            'itemid' => $draftid,
            'maxbytes' => 0,
            'context' => $context,
            'return_types' => FILE_INTERNAL,
            'target' => 'id_epure_logoonbrand',
            'mainfile' => false,
        ]);
        $manager = new \form_filemanager($options);
        return ['html' => $PAGE->get_renderer('core', 'files')->render($manager), 'draftid' => $draftid];
    }

    /**
     * Saves the vocabulary posted with IOMAD's company form.
     *
     * @param int $companyid Company created or updated.
     * @return bool Whether vocabulary fields were posted and saved.
     */
    public static function save_from_request(int $companyid): bool {
        if (!$companyid || !optional_param(self::MARKER, 0, PARAM_BOOL) || !confirm_sesskey()) {
            return false;
        }
        $values = [];
        foreach (terms::LANGUAGES as $lang) {
            foreach (terms::IOMAD_CONCEPTS as $concept) {
                $name = terms::setting($lang, $concept);
                $choice = optional_param(self::PREFIX . $name, '', PARAM_ALPHANUMEXT);
                if ($choice === '') {
                    continue;
                }
                $values[$name] = $choice;
                foreach (['singular', 'plural', 'gender'] as $suffix) {
                    $field = terms::setting($lang, $concept, $suffix);
                    $values[$field] = trim(optional_param(self::PREFIX . $field, '', PARAM_TEXT));
                }
            }
        }
        company::save($companyid, $values);
        if ($draftid = optional_param(self::PREFIX . 'logoonbrand', 0, PARAM_INT)) {
            file_save_draft_area_files(
                $draftid,
                \context_system::instance()->id,
                'theme_epure',
                'companylogoonbrand',
                $companyid,
                self::LOGO_OPTIONS
            );
        }
        \theme_epure\company_style::save_settings($companyid, [
            'brandcolor' => optional_param(self::PREFIX . 'brandcolor', '', PARAM_TEXT),
            'headerstyle' => optional_param(self::PREFIX . 'headerstyle', '', PARAM_ALPHA),
            'font' => optional_param(self::PREFIX . 'font', '', PARAM_ALPHANUMEXT),
            'coursebanner' => optional_param(self::PREFIX . 'coursebanner', '', PARAM_ALPHA),
            'learnerdashboard' => optional_param(self::PREFIX . 'learnerdashboard', '', PARAM_ALPHA),
            'mobilenav' => optional_param(self::PREFIX . 'mobilenav', '', PARAM_ALPHA),
            'darkmode' => optional_param(self::PREFIX . 'darkmode', '', PARAM_ALPHA),
        ] + array_combine(\theme_epure\company_style::FOOTER_FIELDS, array_map(
            fn($name) => optional_param(self::PREFIX . $name, '', PARAM_RAW),
            \theme_epure\company_style::FOOTER_FIELDS
        )));
        return true;
    }
}
