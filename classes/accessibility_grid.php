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
 * The whole grid of the referential of the country of the site, criterion by criterion: the 106 criteria of the RGAA
 * 4.1.2 in France, the clauses 9 of the EN 301 549 (the success criteria of the WCAG 2.1 A and AA) in the European
 * Union, the success criteria of the WCAG 2.2 A and AA elsewhere. For each, who answers it (the theme, the guide
 * « Accessibility of the course », the authors of the contents, Moodle) and its state in the reference pre-audit of
 * Épure: a Moodle with Épure and a course written with care, the sample of pages of the RGAA. Indicative, never an
 * audit: the audit is made on the site, with its contents and its plugins.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accessibility_grid {
    /** @var string[] Who answers a criterion. */
    public const OWNERS = ['theme', 'guide', 'content', 'moodle', 'none'];

    /** @var string[] States in the reference pre-audit: conform, not conform, to verify, not applicable. */
    public const STATES = ['c', 'nc', 'v', 'na'];

    /** @var string Version of Épure, of Moodle and pages of the reference pre-audit. */
    public const PREAUDIT = ['epure' => '1.52.0', 'moodle' => '4.5.15', 'pages' => 44, 'date' => '2026-11-23'];

    /**
     * @var array<string, array{0: string, 1: string, 2: string}> The criteria of the RGAA 4.1.2: who answers each, its
     * state in the reference pre-audit, and the remark that explains them (string coverage_note_…).
     */
    public const RGAA = [
        '1.1' => ['guide', 'c', 'images'], '1.2' => ['theme', 'c', 'decorative'],
        '1.3' => ['content', 'c', 'altrelevance'], '1.4' => ['moodle', 'na', 'captcha'],
        '1.5' => ['moodle', 'na', 'captcha'], '1.6' => ['content', 'na', 'longdesc'],
        '1.7' => ['content', 'na', 'longdesc'], '1.8' => ['moodle', 'c', 'textimages'],
        '1.9' => ['content', 'na', 'figcaption'], '2.1' => ['guide', 'c', 'frames'], '2.2' => ['guide', 'c', 'frames'],
        '3.1' => ['theme', 'c', 'colour'], '3.2' => ['theme', 'c', 'contrast'], '3.3' => ['theme', 'c', 'uicontrast'],
        '4.1' => ['guide', 'na', 'media'], '4.2' => ['content', 'na', 'media'], '4.3' => ['guide', 'na', 'media'],
        '4.4' => ['content', 'na', 'media'], '4.5' => ['content', 'na', 'media'], '4.6' => ['content', 'na', 'media'],
        '4.7' => ['content', 'na', 'media'], '4.8' => ['content', 'v', 'interactive'],
        '4.9' => ['content', 'v', 'interactive'], '4.10' => ['moodle', 'c', 'autoplay'],
        '4.11' => ['moodle', 'c', 'player'], '4.12' => ['content', 'v', 'interactive'],
        '4.13' => ['content', 'v', 'interactive'], '5.1' => ['theme', 'c', 'complextables'],
        '5.2' => ['theme', 'c', 'complextables'], '5.3' => ['moodle', 'c', 'layouttables'],
        '5.4' => ['theme', 'c', 'tablecaptions'], '5.5' => ['theme', 'c', 'tablecaptions'],
        '5.6' => ['theme', 'c', 'tableheaders'], '5.7' => ['moodle', 'c', 'tableheaders'],
        '5.8' => ['moodle', 'c', 'layouttables'], '6.1' => ['theme', 'c', 'links'], '6.2' => ['theme', 'c', 'links'],
        '7.1' => ['theme', 'c', 'scripts'], '7.2' => ['moodle', 'c', 'scripts'], '7.3' => ['moodle', 'v', 'filepicker'],
        '7.4' => ['theme', 'c', 'contextchange'], '7.5' => ['theme', 'c', 'status'],
        '8.1' => ['moodle', 'c', 'doctype'], '8.2' => ['theme', 'v', 'validity'], '8.3' => ['moodle', 'c', 'lang'],
        '8.4' => ['moodle', 'c', 'lang'], '8.5' => ['theme', 'c', 'pagetitle'], '8.6' => ['theme', 'c', 'pagetitle'],
        '8.7' => ['guide', 'c', 'langparts'], '8.8' => ['guide', 'c', 'langparts'],
        '8.9' => ['moodle', 'v', 'presentationtags'], '8.10' => ['none', 'na', 'direction'],
        '9.1' => ['theme', 'c', 'headings'], '9.2' => ['theme', 'c', 'structure'], '9.3' => ['guide', 'c', 'lists'],
        '9.4' => ['content', 'na', 'quotes'], '10.1' => ['theme', 'c', 'css'], '10.2' => ['moodle', 'c', 'css'],
        '10.3' => ['moodle', 'c', 'css'], '10.4' => ['theme', 'c', 'zoom'], '10.5' => ['theme', 'c', 'css'],
        '10.6' => ['theme', 'c', 'linksvisible'], '10.7' => ['theme', 'c', 'focus'],
        '10.8' => ['moodle', 'c', 'hidden'], '10.9' => ['moodle', 'c', 'sensory'],
        '10.10' => ['moodle', 'c', 'sensory'], '10.11' => ['theme', 'v', 'reflow'],
        '10.12' => ['theme', 'c', 'spacing'], '10.13' => ['moodle', 'c', 'hover'], '10.14' => ['moodle', 'c', 'hover'],
        '11.1' => ['theme', 'c', 'formlabels'], '11.2' => ['moodle', 'c', 'formlabels'],
        '11.3' => ['moodle', 'c', 'formlabels'], '11.4' => ['moodle', 'c', 'formlabels'],
        '11.5' => ['moodle', 'c', 'groups'], '11.6' => ['moodle', 'v', 'filepicker'],
        '11.7' => ['moodle', 'c', 'groups'], '11.8' => ['moodle', 'c', 'groups'], '11.9' => ['theme', 'c', 'buttons'],
        '11.10' => ['theme', 'c', 'required'], '11.11' => ['moodle', 'c', 'errors'],
        '11.12' => ['moodle', 'c', 'confirm'], '11.13' => ['theme', 'c', 'autocomplete'],
        '12.1' => ['theme', 'c', 'navsystems'], '12.2' => ['theme', 'c', 'navsystems'],
        '12.3' => ['none', 'na', 'sitemap'], '12.4' => ['none', 'na', 'sitemap'], '12.5' => ['theme', 'c', 'search'],
        '12.6' => ['theme', 'c', 'regions'], '12.7' => ['theme', 'c', 'skiplink'], '12.8' => ['theme', 'c', 'taborder'],
        '12.9' => ['moodle', 'c', 'keyboardtrap'], '12.10' => ['theme', 'c', 'shortcuts'],
        '12.11' => ['moodle', 'c', 'keyboardreach'], '13.1' => ['theme', 'c', 'timelimits'],
        '13.2' => ['theme', 'c', 'newwindows'], '13.3' => ['guide', 'c', 'documents'],
        '13.4' => ['content', 'c', 'documents'], '13.5' => ['moodle', 'c', 'cryptic'],
        '13.6' => ['moodle', 'c', 'cryptic'], '13.7' => ['theme', 'c', 'flashes'], '13.8' => ['theme', 'c', 'motion'],
        '13.9' => ['moodle', 'c', 'orientation'], '13.10' => ['moodle', 'c', 'gestures'],
        '13.11' => ['moodle', 'c', 'gestures'], '13.12' => ['none', 'na', 'motionactuation'],
    ];

    /**
     * @var array<string, array{0: string, 1: string, 2: string, 3: string}> The success criteria of the WCAG 2.2, levels A
     * and AA: their level, who answers each, its state in the reference pre-audit, and its remark.
     */
    public const WCAG = [
        '1.1.1' => ['A', 'guide', 'c', 'images'], '1.2.1' => ['A', 'guide', 'na', 'media'],
        '1.2.2' => ['A', 'guide', 'na', 'media'], '1.2.3' => ['A', 'content', 'na', 'media'],
        '1.2.4' => ['AA', 'moodle', 'na', 'live'], '1.2.5' => ['AA', 'content', 'na', 'media'],
        '1.3.1' => ['A', 'theme', 'c', 'structure'], '1.3.2' => ['A', 'moodle', 'c', 'css'],
        '1.3.3' => ['A', 'moodle', 'c', 'sensory'], '1.3.4' => ['AA', 'moodle', 'c', 'orientation'],
        '1.3.5' => ['AA', 'theme', 'c', 'autocomplete'], '1.4.1' => ['A', 'theme', 'c', 'colour'],
        '1.4.2' => ['A', 'moodle', 'c', 'autoplay'], '1.4.3' => ['AA', 'theme', 'c', 'contrast'],
        '1.4.4' => ['AA', 'theme', 'c', 'zoom'], '1.4.5' => ['AA', 'moodle', 'c', 'textimages'],
        '1.4.10' => ['AA', 'theme', 'v', 'reflow'], '1.4.11' => ['AA', 'theme', 'c', 'uicontrast'],
        '1.4.12' => ['AA', 'theme', 'c', 'spacing'], '1.4.13' => ['AA', 'moodle', 'c', 'hover'],
        '2.1.1' => ['A', 'moodle', 'v', 'filepicker'], '2.1.2' => ['A', 'moodle', 'c', 'keyboardtrap'],
        '2.1.4' => ['A', 'theme', 'c', 'shortcuts'], '2.2.1' => ['A', 'theme', 'c', 'timelimits'],
        '2.2.2' => ['A', 'theme', 'c', 'motion'], '2.3.1' => ['A', 'theme', 'c', 'flashes'],
        '2.4.1' => ['A', 'theme', 'c', 'skiplink'], '2.4.2' => ['A', 'theme', 'c', 'pagetitle'],
        '2.4.3' => ['A', 'theme', 'c', 'taborder'], '2.4.4' => ['A', 'theme', 'c', 'links'],
        '2.4.5' => ['AA', 'theme', 'c', 'navsystems'], '2.4.6' => ['AA', 'theme', 'c', 'headings'],
        '2.4.7' => ['AA', 'theme', 'c', 'focus'], '2.4.11' => ['AA', 'theme', 'c', 'focusobscured'],
        '2.5.1' => ['A', 'moodle', 'c', 'gestures'], '2.5.2' => ['A', 'moodle', 'c', 'gestures'],
        '2.5.3' => ['A', 'theme', 'c', 'labelinname'], '2.5.4' => ['A', 'none', 'na', 'motionactuation'],
        '2.5.7' => ['AA', 'moodle', 'c', 'dragging'], '2.5.8' => ['AA', 'theme', 'c', 'targetsize'],
        '3.1.1' => ['A', 'moodle', 'c', 'lang'], '3.1.2' => ['AA', 'guide', 'c', 'langparts'],
        '3.2.1' => ['A', 'moodle', 'c', 'contextchange'], '3.2.2' => ['A', 'theme', 'c', 'contextchange'],
        '3.2.3' => ['AA', 'theme', 'c', 'navsystems'], '3.2.4' => ['AA', 'moodle', 'c', 'consistent'],
        '3.2.6' => ['A', 'theme', 'c', 'help'], '3.3.1' => ['A', 'moodle', 'c', 'errors'],
        '3.3.2' => ['A', 'theme', 'c', 'required'], '3.3.3' => ['AA', 'moodle', 'c', 'errors'],
        '3.3.4' => ['AA', 'moodle', 'c', 'confirm'], '3.3.7' => ['A', 'moodle', 'c', 'redundant'],
        '3.3.8' => ['AA', 'moodle', 'c', 'authentication'], '4.1.1' => ['A', 'moodle', 'na', 'parsing'],
        '4.1.2' => ['A', 'theme', 'c', 'scripts'], '4.1.3' => ['AA', 'theme', 'c', 'status'],
    ];

    /** @var array<string, string> Success criteria of the level AAA that Épure meets as well: their remark. */
    public const BEYOND = ['1.4.6' => 'aaacontrast', '2.2.6' => 'aaatimeouts', '2.3.3' => 'aaamotion', '2.4.9' => 'aaalinks',
        '2.4.12' => 'aaafocus', '2.4.13' => 'aaafocusappearance', '3.1.4' => 'aaaabbreviations'];

    /** @var string[] Success criteria new in the WCAG 2.2, outside the EN 301 549 V3.2.1 (WCAG 2.1). */
    public const WCAG22 = ['2.4.11', '2.5.7', '2.5.8', '3.2.6', '3.3.7', '3.3.8'];

    /**
     * @var array<string, string> What the accessibility statement of a public body of the European Union must give
     * (directive 2016/2102, implementing decision 2018/1523), and who gives it: the theme, or the organisation.
     */
    public const STATEMENT = ['status' => 'organisation', 'nonaccessible' => 'organisation', 'disproportionate' => 'organisation',
        'alternatives' => 'organisation', 'preparation' => 'organisation', 'feedback' => 'theme', 'enforcement' => 'theme',
        'publication' => 'theme'];

    /**
     * The criteria of the RGAA 4.1.2, by theme, as the DINUM publishes them (Licence Ouverte).
     *
     * @return array[] Each theme: number, title, criteria (number, title, wcag).
     */
    public static function rgaa_topics(): array {
        global $CFG;
        $data = json_decode((string) file_get_contents($CFG->dirroot . '/theme/epure/data/rgaa.json'), true);
        return $data['topics'] ?? [];
    }

    /**
     * The rows of the grid of a referential.
     *
     * @param string $model fr (RGAA), eu (EN 301 549) or world (WCAG 2.2).
     * @return array[] Each: number, title, wcag, owner, state, note, and the theme of the RGAA (topic) for fr.
     */
    public static function rows(string $model): array {
        $rows = [];
        if ($model === 'fr') {
            foreach (self::rgaa_topics() as $topic) {
                foreach ($topic['criteria'] as $criterion) {
                    [$owner, $state, $note] = self::RGAA[$criterion['number']];
                    $rows[] = ['number' => $criterion['number'], 'title' => $criterion['title'], 'topic' => $topic['title'],
                        'wcag' => implode(', ', $criterion['wcag']), 'owner' => $owner, 'state' => $state, 'note' => $note];
                }
            }
            return $rows;
        }
        foreach (self::WCAG as $criterion => [$level, $owner, $state, $note]) {
            if ($model === 'eu' && in_array($criterion, self::WCAG22, true)) {
                continue;
            }
            $rows[] = [
                'number' => $model === 'eu' ? '9.' . $criterion : $criterion,
                'title' => get_string('coverage_wcag_' . str_replace('.', '_', $criterion), 'theme_epure') . ' (' . $level . ')',
                'topic' => '', 'wcag' => $criterion, 'owner' => $owner, 'state' => $state, 'note' => $note,
            ];
        }
        return $rows;
    }

    /**
     * The count of the rows of a grid by state, and the rate of conformity of the reference pre-audit: the criteria
     * conform among the criteria applicable and verified, and the rate if all those to verify were not conform.
     *
     * @param array[] $rows Rows ({@see self::rows()}).
     * @return array c, nc, v, na, rate, lowest (percentages, whole).
     */
    public static function counts(array $rows): array {
        $counts = array_fill_keys(self::STATES, 0);
        foreach ($rows as $row) {
            $counts[$row['state']]++;
        }
        $checked = $counts['c'] + $counts['nc'];
        $applicable = $checked + $counts['v'];
        $counts['rate'] = $checked ? (int) floor(100 * $counts['c'] / $checked) : 0;
        $counts['lowest'] = $applicable ? (int) floor(100 * $counts['c'] / $applicable) : 0;
        return $counts;
    }

    /**
     * Context of the grid in the template theme_epure/coverage.
     *
     * @param string $model fr, eu or world.
     * @return array
     */
    public static function export(string $model): array {
        $rows = self::rows($model);
        $owner = fn(string $key): string => get_string('coverage_owner_' . $key, 'theme_epure');
        // By theme of the RGAA, each in its group of rows; a single group for the WCAG and the EN 301 549.
        $groups = [];
        foreach ($rows as $row) {
            if (!$groups || end($groups)['topic'] !== $row['topic']) {
                $groups[] = ['topic' => $row['topic'], 'rows' => []];
            }
            $groups[count($groups) - 1]['rows'][] = [
                'number' => $row['number'],
                'title' => $row['title'],
                'wcag' => $model === 'fr' ? $row['wcag'] : '',
                'owner' => $owner($row['owner']),
                'state' => $row['state'],
                'statelabel' => get_string('coverage_state_' . $row['state'], 'theme_epure'),
                'note' => get_string('coverage_note_' . $row['note'], 'theme_epure'),
            ];
        }
        $counts = self::counts($rows);
        $beyond = [];
        if ($model === 'world') {
            foreach (self::BEYOND as $criterion => $note) {
                $beyond[] = ['number' => $criterion,
                    'title' => get_string('coverage_wcag_' . str_replace('.', '_', $criterion), 'theme_epure') . ' (AAA)',
                    'note' => get_string('coverage_note_' . $note, 'theme_epure')];
            }
        }
        $statement = [];
        if ($model === 'eu') {
            foreach (self::STATEMENT as $key => $who) {
                $statement[] = ['title' => get_string('coverage_statement_' . $key, 'theme_epure'),
                    'text' => get_string('coverage_statement_' . $key . '_text', 'theme_epure'),
                    'who' => get_string('coverage_statement_who_' . $who, 'theme_epure')];
            }
        }
        return [
            'gridtitle' => get_string('coverage_grid_' . $model, 'theme_epure', count($rows)),
            'gridintro' => get_string('coverage_grid_intro', 'theme_epure', (object) self::PREAUDIT),
            'gridcounts' => get_string('coverage_grid_counts', 'theme_epure', (object) $counts),
            'groups' => $groups,
            'hasbeyond' => (bool) $beyond,
            'beyond' => $beyond,
            'hasstatementneeds' => (bool) $statement,
            'statementneeds' => $statement,
            'downloadurl' => accessibility_coverage::url($model)->out(false),
        ];
    }
}
