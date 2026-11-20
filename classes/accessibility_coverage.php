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
 * Page « What Épure covers »: for each point of the accessibility referential of the country of the site (RGAA in
 * France, EN 301 549 in the European Union, WCAG elsewhere), who answers it: the theme itself, the guide « Accessibility
 * of the course » that checks the courses, the authors of the contents, or Moodle and its plugins. To show to those who
 * choose or audit the platform; the correspondences are indicative, never an audit.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accessibility_coverage {
    /** @var string[] Who answers the points, in the order of the page. */
    public const LEVELS = ['theme', 'guide', 'content', 'moodle', 'extra'];

    /** @var string[] The referentials that can be shown. */
    public const MODELS = ['fr', 'eu', 'world'];

    /** @var string Version of the RGAA of the correspondences. */
    public const RGAA = '4.1.2';

    /**
     * @var array<string, array{level: string, wcag: string[], rgaa: string[]}> The points, by key: who answers them, the
     * success criteria of the WCAG and the criteria of the RGAA they meet. Without any criterion, a good practice.
     */
    public const ITEMS = [
        'skiplinks' => ['level' => 'theme', 'wcag' => ['2.4.1'], 'rgaa' => ['12.7']],
        'landmarks' => ['level' => 'theme', 'wcag' => ['1.3.1'], 'rgaa' => ['9.2', '12.6']],
        'focus' => ['level' => 'theme', 'wcag' => ['2.4.7', '2.4.11'], 'rgaa' => ['10.7']],
        'themecolours' => ['level' => 'theme', 'wcag' => ['1.4.3', '1.4.11'], 'rgaa' => ['3.2', '3.3']],
        'zoom' => ['level' => 'theme', 'wcag' => ['1.4.4', '1.4.10'], 'rgaa' => ['10.4', '10.11']],
        'textspacing' => ['level' => 'theme', 'wcag' => ['1.4.12'], 'rgaa' => ['10.12']],
        'motion' => ['level' => 'theme', 'wcag' => ['2.2.2'], 'rgaa' => ['13.8']],
        'ariarepairs' => ['level' => 'theme', 'wcag' => ['4.1.2'], 'rgaa' => ['7.1']],
        'statusmessages' => ['level' => 'theme', 'wcag' => ['4.1.3'], 'rgaa' => ['7.5']],
        'shortcuts' => ['level' => 'theme', 'wcag' => ['2.1.4'], 'rgaa' => ['12.10']],
        'targetsize' => ['level' => 'theme', 'wcag' => ['2.5.8'], 'rgaa' => []],
        'statement' => ['level' => 'theme', 'wcag' => [], 'rgaa' => []],
        'images' => ['level' => 'guide', 'wcag' => ['1.1.1'], 'rgaa' => ['1.1', '1.2', '1.3']],
        'links' => ['level' => 'guide', 'wcag' => ['2.4.4'], 'rgaa' => ['6.1']],
        'headings' => ['level' => 'guide', 'wcag' => ['1.3.1', '2.4.6'], 'rgaa' => ['9.1']],
        'tables' => ['level' => 'guide', 'wcag' => ['1.3.1'], 'rgaa' => ['5.4', '5.6', '5.7']],
        'contrast' => ['level' => 'guide', 'wcag' => ['1.4.3'], 'rgaa' => ['3.2']],
        'frames' => ['level' => 'guide', 'wcag' => ['4.1.2'], 'rgaa' => ['2.1']],
        'videos' => ['level' => 'guide', 'wcag' => ['1.2.2', '1.2.3'], 'rgaa' => ['4.1', '4.3']],
        'language' => ['level' => 'guide', 'wcag' => ['3.1.2'], 'rgaa' => ['8.7']],
        'moving' => ['level' => 'guide', 'wcag' => ['2.2.2'], 'rgaa' => ['13.8']],
        'tabindex' => ['level' => 'guide', 'wcag' => ['2.4.3'], 'rgaa' => ['12.8']],
        'documents' => ['level' => 'guide', 'wcag' => ['1.1.1', '1.3.1'], 'rgaa' => ['13.3']],
        'instructions' => ['level' => 'guide', 'wcag' => [], 'rgaa' => []],
        'colouronly' => ['level' => 'content', 'wcag' => ['1.4.1'], 'rgaa' => ['3.1']],
        'audiodescription' => ['level' => 'content', 'wcag' => ['1.2.5'], 'rgaa' => ['4.5']],
        'sequence' => ['level' => 'content', 'wcag' => ['1.3.2'], 'rgaa' => ['10.3']],
        'timelimits' => ['level' => 'content', 'wcag' => ['2.2.1'], 'rgaa' => ['13.1']],
        'pagetitle' => ['level' => 'moodle', 'wcag' => ['2.4.2'], 'rgaa' => ['8.5', '8.6']],
        'pagelanguage' => ['level' => 'moodle', 'wcag' => ['3.1.1'], 'rgaa' => ['8.3']],
        'forms' => ['level' => 'moodle', 'wcag' => ['1.3.1', '3.3.1', '3.3.2'], 'rgaa' => ['11.1', '11.2', '11.10']],
        'dragging' => ['level' => 'moodle', 'wcag' => ['2.5.7'], 'rgaa' => []],
        'plugins' => ['level' => 'moodle', 'wcag' => [], 'rgaa' => []],
        'readaloud' => ['level' => 'extra', 'wcag' => [], 'rgaa' => []],
        'profiles' => ['level' => 'extra', 'wcag' => [], 'rgaa' => []],
    ];

    /** @var array<string, string> Level (A, AA) of each success criterion of the WCAG used above. */
    public const WCAG_LEVELS = [
        '1.1.1' => 'A', '1.2.2' => 'A', '1.2.3' => 'A', '1.2.5' => 'AA', '1.3.1' => 'A', '1.3.2' => 'A', '1.4.1' => 'A',
        '1.4.3' => 'AA', '1.4.4' => 'AA', '1.4.10' => 'AA', '1.4.11' => 'AA', '1.4.12' => 'AA', '2.1.4' => 'A', '2.2.1' => 'A',
        '2.2.2' => 'A', '2.4.1' => 'A', '2.4.2' => 'A', '2.4.3' => 'A', '2.4.4' => 'A', '2.4.6' => 'AA', '2.4.7' => 'AA',
        '2.4.11' => 'AA', '2.5.7' => 'AA', '2.5.8' => 'AA', '3.1.1' => 'A', '3.1.2' => 'AA', '3.3.1' => 'A',
        '3.3.2' => 'A', '4.1.2' => 'A', '4.1.3' => 'AA',
    ];

    /** @var string[] Success criteria new in the WCAG 2.2: not yet in the RGAA 4.1 nor in the EN 301 549 V3.2.1. */
    public const WCAG22 = ['2.4.11', '2.5.7', '2.5.8'];

    /**
     * URL of the page.
     *
     * @param string|null $model Referential to show, the one of the country of the site if none.
     * @return \moodle_url
     */
    public static function url(?string $model = null): \moodle_url {
        return new \moodle_url('/theme/epure/coverage.php', $model === null ? [] : ['model' => $model]);
    }

    /**
     * The references of a point in a referential.
     *
     * @param string $key Point.
     * @param string $model Referential: fr, eu or world.
     * @return string The criteria, or '' for a good practice beyond the referential.
     */
    public static function references(string $key, string $model): string {
        $item = self::ITEMS[$key];
        if ($key === 'statement') {
            // Asked by the law, not by a criterion: in France and in the European Union.
            return $model === 'world' ? '' : get_string('coverage_ref_law_' . $model, 'theme_epure');
        }
        if ($model === 'world') {
            $level = fn(string $criterion): string => $criterion . ' (' . self::WCAG_LEVELS[$criterion] . ')';
            $criteria = array_map($level, $item['wcag']);
            return $criteria ? get_string('coverage_ref_wcag', 'theme_epure', implode(', ', $criteria)) : '';
        }
        $older = array_values(array_diff($item['wcag'], self::WCAG22));
        $newer = array_values(array_intersect($item['wcag'], self::WCAG22));
        $parts = [];
        if ($model === 'fr' && $item['rgaa']) {
            $parts[] = get_string('coverage_ref_rgaa', 'theme_epure', (object) [
                'version' => self::RGAA,
                'criteria' => implode(', ', $item['rgaa']),
                'wcag' => implode(', ', $older),
            ]);
        } else if ($model === 'eu' && $older) {
            $clauses = array_map(fn(string $criterion): string => '9.' . $criterion, $older);
            $parts[] = get_string('coverage_ref_en', 'theme_epure', implode(', ', $clauses));
        }
        if ($newer) {
            $parts[] = get_string('coverage_ref_wcag22_' . $model, 'theme_epure', implode(', ', $newer));
        }
        return implode(' · ', $parts);
    }

    /**
     * Context of the template theme_epure/coverage.
     *
     * @param string|null $model Referential to show, the one of the country of the site if none.
     * @return array
     */
    public static function export(?string $model = null): array {
        $sitemodel = accessibility_statement::model();
        $model = in_array($model, self::MODELS, true) ? $model : $sitemodel;
        $levels = [];
        foreach (self::LEVELS as $level) {
            $items = [];
            foreach (self::ITEMS as $key => $item) {
                if ($item['level'] !== $level) {
                    continue;
                }
                $references = self::references($key, $model);
                $items[] = [
                    'key' => $key,
                    'title' => get_string('coverage_' . $key, 'theme_epure'),
                    'text' => get_string('coverage_' . $key . '_text', 'theme_epure'),
                    'references' => $references !== '' ? $references : get_string('coverage_ref_none', 'theme_epure'),
                ];
            }
            $levels[] = [
                'level' => $level,
                'heading' => get_string('coverage_level_' . $level, 'theme_epure'),
                'intro' => get_string('coverage_level_' . $level . '_text', 'theme_epure'),
                'items' => $items,
            ];
        }
        $models = [];
        foreach (self::MODELS as $choice) {
            $label = get_string('coverage_model_' . $choice, 'theme_epure');
            $models[] = [
                'label' => $choice === $sitemodel ? get_string('coverage_model_site', 'theme_epure', $label) : $label,
                'url' => self::url($choice)->out(false),
                'current' => $choice === $model,
            ];
        }
        return [
            'referential' => get_string('coverage_referential_' . $model, 'theme_epure'),
            'models' => $models,
            'levels' => $levels,
            'statementurl' => accessibility_statement::url()->out(false),
        ];
    }
}
