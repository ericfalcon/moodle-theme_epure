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
 * The passages of a text written in another language than the course, without their language marked (WCAG 3.1.2,
 * RGAA 8.7): a screen reader reads them with the pronunciation of the course. Guessed from the commonest words of six
 * languages, so a point to confirm by the teacher, never a mistake. A passage whose language is marked (lang), or
 * whose language cannot be told for sure, is left alone.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_language {
    /** @var array<string, string[]> The commonest short words of each language, those that tell it from the others. */
    public const WORDS = [
        'fr' => ['le', 'les', 'des', 'du', 'et', 'est', 'une', 'dans', 'pour', 'qui', 'pas', 'sur', 'avec', 'ce', 'cette',
            'sont', 'nous', 'vous', 'il', 'elle', 'au', 'aux', 'par', 'plus', 'ne', 'mais', 'son', 'ses', 'leur', 'être',
            'ou', 'je', 'on', 'cela', 'comme'],
        'en' => ['the', 'and', 'is', 'are', 'of', 'to', 'that', 'it', 'for', 'with', 'as', 'on', 'be', 'this', 'was', 'by',
            'not', 'or', 'from', 'at', 'have', 'an', 'which', 'you', 'they', 'we', 'can', 'will', 'their', 'has', 'what',
            'when', 'how', 'your'],
        'es' => ['el', 'los', 'las', 'del', 'y', 'es', 'que', 'en', 'una', 'por', 'para', 'con', 'no', 'su', 'sus',
            'como', 'más', 'pero', 'lo', 'al', 'este', 'esta', 'son', 'muy', 'también', 'hay', 'cuando', 'porque'],
        'de' => ['der', 'die', 'das', 'und', 'ist', 'nicht', 'ein', 'eine', 'zu', 'mit', 'von', 'den', 'dem', 'auf', 'für',
            'sich', 'im', 'auch', 'es', 'sind', 'wir', 'sie', 'werden', 'kann', 'oder', 'wird', 'bei', 'nach', 'wie'],
        'it' => ['il', 'lo', 'gli', 'di', 'della', 'è', 'che', 'una', 'per', 'con', 'non', 'si', 'sono', 'come', 'più',
            'ma', 'nel', 'nella', 'anche', 'questo', 'questa', 'degli', 'delle', 'alla', 'sul', 'perché'],
        'pt' => ['os', 'do', 'da', 'é', 'que', 'um', 'uma', 'em', 'para', 'com', 'não', 'por', 'mais', 'como', 'mas', 'no',
            'na', 'dos', 'das', 'ao', 'são', 'também', 'você', 'muito', 'pelo', 'pela'],
    ];

    /** @var int Words from which a passage is judged: a shorter one cannot be told for sure. */
    public const MIN_WORDS = 12;

    /** @var int Common words of its language a passage holds at least, to be judged. */
    protected const MIN_HITS = 4;

    /**
     * The language of a passage, when it can be told for sure: its common words of one language at least four, and
     * twice as many as of any other.
     *
     * @param string $text Passage.
     * @return string|null Code of the language (fr, en, es, de, it, pt).
     */
    public static function guess(string $text): ?string {
        $words = preg_split('/[^\p{L}\']+/u', \core_text::strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < self::MIN_WORDS) {
            return null;
        }
        $scores = [];
        foreach (self::WORDS as $lang => $common) {
            $scores[$lang] = count(array_intersect($words, $common));
        }
        arsort($scores);
        $lang = array_key_first($scores);
        $first = $scores[$lang];
        $next = array_values($scores)[1] ?? 0;
        return $first >= self::MIN_HITS && $first >= 2 * $next ? $lang : null;
    }

    /**
     * The passages of a text in another language than the course, without their language marked.
     *
     * @param \DOMXPath $xpath Document of the text.
     * @param string $language Language of the course (« fr », « fr_ca », « en_us »…).
     * @return array[] Each a check of the guide: key, kind (lang), label (the start of the passage).
     */
    public static function passages(\DOMXPath $xpath, string $language): array {
        $base = substr(strtolower($language), 0, 2);
        if (!isset(self::WORDS[$base])) {
            return [];
        }
        $blocks = 'self::p or self::li or self::td or self::th or self::blockquote or self::figcaption or self::dd or self::dt'
            . ' or self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6';
        $checks = [];
        // The innermost blocks, whose language is not marked on them nor around them.
        foreach ($xpath->query('//*[' . $blocks . '][not(.//*[' . $blocks . '])][not(ancestor-or-self::*[@lang])]') as $block) {
            $text = trim(preg_replace('/\s+/u', ' ', $block->textContent));
            $lang = self::guess($text);
            if ($lang === null || $lang === $base) {
                continue;
            }
            $words = preg_split('/\s+/u', $text);
            $label = implode(' ', array_slice($words, 0, 8)) . (count($words) > 8 ? '…' : '');
            $checks[] = ['key' => 'lang:' . substr(sha1($text), 0, 16), 'kind' => 'lang', 'label' => $label];
        }
        return $checks;
    }
}
