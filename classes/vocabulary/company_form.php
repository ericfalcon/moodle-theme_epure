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
     * Context of the Épure appearance fields: brand colour, colours of the logo, header, font.
     *
     * @param int $companyid Company edited, 0 for a new company.
     * @return array
     */
    protected static function appearance_context(int $companyid): array {
        $settings = $companyid ? \theme_epure\company_style::settings($companyid)
            : ['brandcolor' => '', 'headerstyle' => '', 'font' => ''];
        $brand = self::posted('brandcolor', $settings['brandcolor']);
        $header = self::posted('headerstyle', $settings['headerstyle']);
        $font = self::posted('font', $settings['font']);
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
        $fonts = [$option('', get_string('companysite', 'theme_epure', $sitefont), $font)];
        foreach (\theme_epure\fonts::all() as $key => $definition) {
            $fonts[] = $option($key, $definition['family'], $font);
        }
        return [
            'brandcolor' => $brand,
            'sitebrandcolor' => $sitebrand,
            'headerstyles' => $headers,
            'fonts' => $fonts,
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
        \theme_epure\company_style::save_settings($companyid, [
            'brandcolor' => optional_param(self::PREFIX . 'brandcolor', '', PARAM_TEXT),
            'headerstyle' => optional_param(self::PREFIX . 'headerstyle', '', PARAM_ALPHA),
            'font' => optional_param(self::PREFIX . 'font', '', PARAM_ALPHANUMEXT),
        ]);
        return true;
    }
}
