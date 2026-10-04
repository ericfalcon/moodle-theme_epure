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
 * Applies the chosen vocabulary to every string of Moodle and of its plugins.
 *
 * The rewritten strings are written with Moodle's own language customisation tool
 * (tool_customlang), in the local language pack (for example fr_local): no core file and
 * no config.php is modified, and the strings appear in Site administration › Language ›
 * Language customisation like any other customisation.
 *
 * The strings written by the plugin are recorded. A string the administrator customised
 * is never overwritten, and reverting removes only the strings the plugin wrote.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Languages installed on the site whose vocabulary can be changed.
     *
     * @return string[]
     */
    public static function languages(): array {
        $installed = get_string_manager()->get_list_of_translations(true);
        return array_values(array_filter(terms::LANGUAGES, fn($lang) => isset($installed[$lang])));
    }

    /**
     * Applies the vocabulary chosen for a language, or removes it when the default words are chosen.
     *
     * @param string $lang Language.
     * @param \progress_bar|null $progress Progress bar for the first preparation of the language, which takes a while.
     * @return array{changed: int, total: int, kept: int} Strings changed by this call, strings rewritten in all,
     *     and strings left alone because the administrator customised them.
     */
    public static function apply(string $lang, ?\progress_bar $progress = null): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');
        \core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        // Loads every string of the language pack into the customisation tool, as its Open language pack button does.
        \tool_customlang_utils::checkout($lang, $progress);

        $rewriter = rewriter::for_language($lang, terms::chosen_all($lang));
        $applied = [];
        foreach ($DB->get_records('theme_epure_vocab', ['lang' => $lang]) as $record) {
            $applied[$record->component . '/' . $record->stringid] = $record;
        }

        $stats = ['changed' => 0, 'total' => 0, 'kept' => 0];
        $now = time();
        $transaction = $DB->start_delegated_transaction();
        $rows = $DB->get_recordset_sql(
            "SELECT s.id, s.stringid, s.original, s.master, s.local, c.name AS component
               FROM {tool_customlang} s
               JOIN {tool_customlang_components} c ON c.id = s.componentid
              WHERE s.lang = ?",
            [$lang]
        );
        foreach ($rows as $row) {
            $key = $row->component . '/' . $row->stringid;
            $record = $applied[$key] ?? null;
            unset($applied[$key]);

            $source = self::source($lang, $row);
            $new = ($rewriter && $source !== null) ? $rewriter->rewrite($source, (string) $row->original) : null;

            // A string the administrator customised stays as it is.
            if ($row->local !== null && ($record === null || $row->local !== $record->value)) {
                if ($record !== null) {
                    $DB->delete_records('theme_epure_vocab', ['id' => $record->id]);
                }
                if ($new !== null) {
                    $stats['kept']++;
                }
                continue;
            }

            if ($new === null) {
                if ($record !== null) {
                    $DB->update_record('tool_customlang', [
                        'id' => $row->id, 'local' => null, 'modified' => 1, 'timecustomized' => null,
                    ]);
                    $DB->delete_records('theme_epure_vocab', ['id' => $record->id]);
                    $stats['changed']++;
                }
                continue;
            }

            $stats['total']++;
            if ($row->local !== $new) {
                $DB->update_record('tool_customlang', [
                    'id' => $row->id, 'local' => $new, 'modified' => 1, 'timecustomized' => $now,
                ]);
                $stats['changed']++;
            }
            if ($record === null) {
                $DB->insert_record('theme_epure_vocab', [
                    'lang' => $lang, 'component' => $row->component, 'stringid' => $row->stringid,
                    'value' => $new, 'timemodified' => $now,
                ]);
            } else if ($record->value !== $new) {
                $DB->update_record('theme_epure_vocab', ['id' => $record->id, 'value' => $new, 'timemodified' => $now]);
            }
        }
        $rows->close();

        // Strings of plugins that are no longer installed.
        foreach ($applied as $record) {
            $DB->delete_records('theme_epure_vocab', ['id' => $record->id]);
        }
        $transaction->allow_commit();

        if ($stats['changed']) {
            // Writes the local language pack, as the Save to language pack button does.
            \tool_customlang_utils::checkin($lang);
        }
        return $stats;
    }

    /**
     * Removes every string written by the vocabulary, in all languages, keeping those the administrator changed since.
     */
    public static function revert(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');
        $langs = $DB->get_fieldset_sql('SELECT DISTINCT lang FROM {theme_epure_vocab}');
        foreach ($langs as $lang) {
            $rows = $DB->get_recordset_sql(
                "SELECT s.id, s.local, v.value
                   FROM {theme_epure_vocab} v
                   JOIN {tool_customlang_components} c ON c.name = v.component
                   JOIN {tool_customlang} s ON s.componentid = c.id AND s.stringid = v.stringid AND s.lang = v.lang
                  WHERE v.lang = ?",
                [$lang]
            );
            foreach ($rows as $row) {
                if ($row->local === $row->value) {
                    $DB->update_record('tool_customlang', [
                        'id' => $row->id, 'local' => null, 'modified' => 1, 'timecustomized' => null,
                    ]);
                }
            }
            $rows->close();
            $DB->delete_records('theme_epure_vocab', ['lang' => $lang]);
            \tool_customlang_utils::checkin($lang);
        }
    }

    /**
     * The text the vocabulary is applied to: the string of the language pack.
     *
     * @param string $lang Language.
     * @param \stdClass $row Row of the customisation tool.
     * @return string|null Null when the string is not translated (it then shows in English).
     */
    private static function source(string $lang, \stdClass $row): ?string {
        if ($row->master !== null) {
            return $row->master;
        }
        return $lang === 'en' ? $row->original : null;
    }

    /**
     * Number of strings currently rewritten in a language.
     *
     * @param string $lang Language.
     * @return int
     */
    public static function count(string $lang): int {
        global $DB;
        return $DB->count_records('theme_epure_vocab', ['lang' => $lang]);
    }

    /**
     * Examples of rewritten strings, taken among the most visible ones.
     *
     * @param string $lang Language.
     * @param int $limit Maximum number of examples.
     * @return array<int, array{before: string, after: string}>
     */
    public static function examples(string $lang, int $limit = 12): array {
        global $DB;
        $visible = ['mycourses', 'courses', 'fulllistofcourses', 'coursecategory', 'defaultcoursestudent',
            'defaultcourseteacher', 'noneditingteacher', 'participants', 'enrolledusers', 'nocourses',
            'coursehidden', 'courseoverview', 'searchcourses', 'addnewcourse', 'student', 'teacher'];
        [$insql, $params] = $DB->get_in_or_equal($visible);
        $params = array_merge([$lang, $lang], $params);
        $records = $DB->get_records_sql(
            "SELECT v.id, v.value, s.master, s.original, v.stringid
               FROM {theme_epure_vocab} v
               JOIN {tool_customlang_components} c ON c.name = v.component
               JOIN {tool_customlang} s ON s.componentid = c.id AND s.stringid = v.stringid AND s.lang = ?
              WHERE v.lang = ? AND v.stringid $insql
           ORDER BY v.component, v.stringid",
            $params
        );
        $examples = [];
        $seen = [];
        foreach ($records as $record) {
            $before = $record->master ?? $record->original;
            if (isset($seen[$before])) {
                continue;
            }
            $seen[$before] = true;
            $examples[] = ['before' => $before, 'after' => $record->value];
            if (count($examples) >= $limit) {
                break;
            }
        }
        return $examples;
    }
}
