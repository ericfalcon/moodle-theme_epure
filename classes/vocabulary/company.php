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
 * Vocabulary of IOMAD: the words used for « company » and « department ».
 *
 * They are chosen for all companies (the platform, stored as company 0) and, if needed, for each
 * company. Language packs are shared by the whole site, so the words of a company cannot be
 * written in them: they are applied when the strings are loaded, by {@see \theme_epure\string_manager},
 * for the users of the company and for the administrator who selected it.
 *
 * The choices are stored in the plugin settings, as company_<id> (JSON, with the setting names of
 * the theme vocabulary, see {@see terms::setting()}), and the list of companies with their own
 * words as companies.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company {
    /** @var array<int, array<string, ?rewriter>> Rewriters of the request, by company and language. */
    private static array $rewriters = [];

    /**
     * Whether words were chosen, for all companies or for one.
     *
     * @return bool
     */
    public static function active(): bool {
        return (string) get_config('theme_epure', 'companies') !== '';
    }

    /**
     * Identifiers of the companies that have their own words (0 for all companies).
     *
     * @return int[]
     */
    public static function ids(): array {
        $ids = (string) get_config('theme_epure', 'companies');
        return $ids === '' ? [] : array_map('intval', explode(',', $ids));
    }

    /**
     * The settings of a company.
     *
     * @param int $companyid Company, 0 for all companies.
     * @return array<string, string> Setting name => value, see {@see terms::setting()}.
     */
    public static function values(int $companyid): array {
        $json = get_config('theme_epure', 'company_' . $companyid);
        $values = $json ? json_decode($json, true) : null;
        return is_array($values) ? $values : [];
    }

    /**
     * The words chosen by a company in a language, for the objects whose wording changes.
     *
     * @param int $companyid Company, 0 for all companies.
     * @param string $lang Language.
     * @return array<string, array<string, string>> object => term.
     */
    public static function chosen(int $companyid, string $lang): array {
        $values = self::values($companyid);
        $targets = [];
        foreach (terms::IOMAD_CONCEPTS as $concept) {
            // For a company, the word of the language pack is a choice: it can differ from the word of the platform.
            if ($values && ($term = terms::chosen($lang, $concept, $values, $companyid === 0))) {
                $targets[$concept] = $term;
            }
        }
        return $targets;
    }

    /**
     * Saves the settings of a company, or removes them when the words of the platform are kept.
     *
     * @param int $companyid Company, 0 for all companies.
     * @param array $values Setting name => value.
     */
    public static function save(int $companyid, array $values): void {
        $ids = array_diff(self::ids(), [$companyid]);
        unset_config('company_' . $companyid, 'theme_epure');
        $keep = false;
        foreach (terms::LANGUAGES as $lang) {
            foreach (terms::IOMAD_CONCEPTS as $concept) {
                $keep = $keep || terms::chosen($lang, $concept, $values, $companyid === 0) !== null;
            }
        }
        if ($keep) {
            set_config('company_' . $companyid, json_encode($values), 'theme_epure');
            $ids[] = $companyid;
        }
        sort($ids);
        set_config('companies', implode(',', array_unique($ids)), 'theme_epure');
        set_config('companyrev', time(), 'theme_epure');
        self::$rewriters = [];
        // Also invalidates the strings cached by the browsers.
        get_string_manager()->reset_caches();
    }

    /**
     * Revision of the company words, part of the cache keys.
     *
     * @return int
     */
    public static function revision(): int {
        return (int) get_config('theme_epure', 'companyrev');
    }

    /**
     * The rewriter for a company and a language: the words of the company, else those of all companies.
     *
     * @param int $companyid Company, 0 for a user without company.
     * @param string $lang Language.
     * @return rewriter|null Null when the words of the language pack are kept.
     */
    public static function rewriter(int $companyid, string $lang): ?rewriter {
        if (!array_key_exists($lang, self::$rewriters[$companyid] ?? [])) {
            $targets = [];
            if (in_array($lang, terms::LANGUAGES, true)) {
                $targets = self::chosen($companyid, $lang) + ($companyid ? self::chosen(0, $lang) : []);
            }
            self::$rewriters[$companyid][$lang] = $targets ? rewriter::for_language($lang, $targets) : null;
        }
        return self::$rewriters[$companyid][$lang];
    }

    /**
     * Forgets the rewriters of the request (for tests).
     */
    public static function reset(): void {
        self::$rewriters = [];
    }
}
