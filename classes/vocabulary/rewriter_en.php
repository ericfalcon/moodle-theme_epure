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
 * Rewrites English strings: the word, its plural, and the article a or an before it.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rewriter_en extends rewriter {
    /** @var string Language of the strings. */
    protected const LANG = 'en';

    /**
     * Replaces a word in plain text.
     *
     * @param string $text Plain text.
     * @param string $concept Object.
     * @param array $source Word to replace (singular, plural, gender).
     * @param array $term New word (singular, plural, gender).
     * @param string $english English version of the whole string.
     * @return string
     */
    protected function rewrite_word(string $text, string $concept, array $source, array $term, string $english): string {
        $plural = \core_text::strtolower($source['plural']);
        // From the end, so that the offsets of the earlier matches stay valid.
        foreach (self::find($text, $source) as [$word, $offset]) {
            $before = substr($text, 0, $offset);
            $after = substr($text, $offset + strlen($word));
            $isplural = \core_text::strtolower($word) === $plural;

            // The phrase "of course" is not about a course.
            if (!$isplural && \core_text::strtolower($word) === 'course' && preg_match('/(?<![\p{L}])of\s+$/iu', $before)) {
                continue;
            }

            $new = self::match_case($word, $isplural ? $term['plural'] : $term['singular']);
            if (!$isplural && preg_match('/(?<![\p{L}])(an?)(\s+)$/iu', $before, $article, PREG_OFFSET_CAPTURE)) {
                $a = self::starts_with_vowel($new) ? 'an' : 'a';
                $before = substr($before, 0, $article[1][1]) . self::match_case($article[1][0], $a) . $article[2][0];
            }
            $text = $before . $new . $after;
        }
        return $text;
    }
}
