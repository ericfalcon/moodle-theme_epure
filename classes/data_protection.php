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
 * Page « Épure and personal data »: what the theme keeps on the users, where, how long and what each one can do about
 * it; then, point by point of the GDPR (with the references of the CNIL in France, the general principles elsewhere),
 * who answers it: the theme, Moodle, the organisation or the plugins added; and an extract for the record of
 * processing activities. To give to the data protection officer; an aid, never a legal advice.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_protection {
    /** @var string[] What the theme keeps, in the order of the page. */
    public const DATA = ['preferences', 'cookie', 'browser', 'confirmations', 'documents', 'caches', 'scores'];

    /** @var string[] Who answers the points, in the order of the page. */
    public const LEVELS = ['theme', 'moodle', 'organisation', 'plugins'];

    /** @var string[] The frameworks that can be shown. */
    public const MODELS = ['fr', 'eu', 'world'];

    /**
     * @var array<string, array{level: string, gdpr: string[], principle: string, cnil?: bool}> The points, by key: who
     * answers them, the articles of the GDPR, the principle they meet and whether a recommendation of the CNIL applies.
     */
    public const ITEMS = [
        'minimisation' => ['level' => 'theme', 'gdpr' => ['5.1.c', '25'], 'principle' => 'minimisation'],
        'nothirdparty' => ['level' => 'theme', 'gdpr' => ['5.1.f', '44'], 'principle' => 'transfers'],
        'cookie' => ['level' => 'theme', 'gdpr' => [], 'principle' => 'transparency', 'cnil' => true],
        'rights' => ['level' => 'theme', 'gdpr' => ['15', '17', '20'], 'principle' => 'rights'],
        'retention' => ['level' => 'theme', 'gdpr' => ['5.1.e'], 'principle' => 'retention'],
        'learnercontrol' => ['level' => 'theme', 'gdpr' => ['17', '25'], 'principle' => 'rights'],
        'security' => ['level' => 'theme', 'gdpr' => ['32'], 'principle' => 'security'],
        'backup' => ['level' => 'theme', 'gdpr' => ['5.1.c'], 'principle' => 'minimisation'],
        'requests' => ['level' => 'moodle', 'gdpr' => ['12', '15', '17', '20'], 'principle' => 'rights'],
        'policies' => ['level' => 'moodle', 'gdpr' => ['7', '13'], 'principle' => 'transparency'],
        'logs' => ['level' => 'moodle', 'gdpr' => ['5.1.e'], 'principle' => 'retention'],
        'registry' => ['level' => 'moodle', 'gdpr' => ['5.1.e', '30'], 'principle' => 'retention'],
        'record' => ['level' => 'organisation', 'gdpr' => ['30'], 'principle' => 'accountability'],
        'legalbasis' => ['level' => 'organisation', 'gdpr' => ['6', '13', '14'], 'principle' => 'transparency'],
        'processors' => ['level' => 'organisation', 'gdpr' => ['28', '44'], 'principle' => 'transfers'],
        'officer' => ['level' => 'organisation', 'gdpr' => ['33', '34', '37'], 'principle' => 'accountability'],
        'impact' => ['level' => 'organisation', 'gdpr' => ['35'], 'principle' => 'accountability'],
        'plugins' => ['level' => 'plugins', 'gdpr' => ['28'], 'principle' => 'accountability'],
    ];

    /** @var string[] The fields of the extract for the record of processing activities, in their order. */
    public const RECORD = ['name', 'purposes', 'people', 'categories', 'recipients', 'transfers', 'durations', 'measures'];

    /**
     * URL of the page.
     *
     * @param string|null $model Framework to show, the one of the country of the site if none.
     * @return \moodle_url
     */
    public static function url(?string $model = null): \moodle_url {
        return new \moodle_url('/theme/epure/dataprotection.php', $model === null ? [] : ['model' => $model]);
    }

    /**
     * The references of a point in a framework.
     *
     * @param string $key Point.
     * @param string $model Framework: fr, eu or world.
     * @return string
     */
    public static function references(string $key, string $model): string {
        $item = self::ITEMS[$key];
        if ($model === 'world') {
            return get_string('dataprot_principle_' . $item['principle'], 'theme_epure');
        }
        $parts = [];
        if ($item['gdpr']) {
            $parts[] = get_string('dataprot_ref_gdpr', 'theme_epure', implode(', ', $item['gdpr']));
        }
        if (!empty($item['cnil'])) {
            $parts[] = get_string('dataprot_ref_eprivacy', 'theme_epure');
            if ($model === 'fr') {
                $parts[] = get_string('dataprot_ref_cnil', 'theme_epure');
            }
        }
        return implode(' · ', $parts);
    }

    /**
     * Context of the template theme_epure/data_protection.
     *
     * @param string|null $model Framework to show, the one of the country of the site if none.
     * @return array
     */
    public static function export(?string $model = null): array {
        $sitemodel = accessibility_statement::model();
        $model = in_array($model, self::MODELS, true) ? $model : $sitemodel;
        $data = [];
        foreach (self::DATA as $key) {
            $data[] = [
                'what' => get_string('dataprot_data_' . $key, 'theme_epure'),
                'where' => get_string('dataprot_data_' . $key . '_where', 'theme_epure'),
                'how' => get_string('dataprot_data_' . $key . '_how', 'theme_epure'),
            ];
        }
        $levels = [];
        foreach (self::LEVELS as $level) {
            $items = [];
            foreach (self::ITEMS as $key => $item) {
                if ($item['level'] === $level) {
                    $items[] = [
                        'title' => get_string('dataprot_' . $key, 'theme_epure'),
                        'text' => get_string('dataprot_' . $key . '_text', 'theme_epure'),
                        'references' => self::references($key, $model),
                    ];
                }
            }
            $levels[] = [
                'level' => $level,
                'heading' => get_string('dataprot_level_' . $level, 'theme_epure'),
                'intro' => get_string('dataprot_level_' . $level . '_text', 'theme_epure'),
                'items' => $items,
            ];
        }
        $record = [];
        foreach (self::RECORD as $field) {
            $record[] = [
                'label' => get_string('dataprot_record_' . $field, 'theme_epure'),
                'value' => get_string('dataprot_record_' . $field . '_value', 'theme_epure'),
            ];
        }
        $models = [];
        foreach (self::MODELS as $choice) {
            $label = get_string('dataprot_model_' . $choice, 'theme_epure');
            $models[] = [
                'label' => $choice === $sitemodel ? get_string('coverage_model_site', 'theme_epure', $label) : $label,
                'url' => self::url($choice)->out(false),
                'current' => $choice === $model,
            ];
        }
        return [
            'framework' => get_string('dataprot_framework_' . $model, 'theme_epure'),
            'models' => $models,
            'data' => $data,
            'levels' => $levels,
            'record' => $record,
            'registryurl' => (new \moodle_url('/admin/tool/dataprivacy/pluginregistry.php'))->out(false),
        ];
    }
}
