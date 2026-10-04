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

/**
 * Upgrade steps for theme_epure.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the theme.
 *
 * @param int $oldversion Version installed before the upgrade.
 * @return bool
 */
function xmldb_theme_epure_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026100600) {
        // The vocabulary of the platform moves from the companion plugin local_epure to the theme.
        $table = new xmldb_table('theme_epure_vocab');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('lang', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('stringid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('langcomponent', XMLDB_INDEX_NOTUNIQUE, ['lang', 'component']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Strings written by local_epure 0.2 and 0.3: the theme now keeps track of them.
        if ($dbman->table_exists('local_epure_vocab') && !$DB->record_exists('theme_epure_vocab', [])) {
            $records = $DB->get_recordset('local_epure_vocab');
            foreach ($records as $record) {
                unset($record->id);
                $DB->insert_record('theme_epure_vocab', $record);
            }
            $records->close();
        }

        // Words chosen in local_epure 0.2 and 0.3.
        foreach (get_config('local_epure') as $name => $value) {
            if (
                preg_match('/^vocab_(fr|en)_(course|student|teacher)(_singular|_plural|_gender)?$/', $name)
                    && get_config('theme_epure', $name) === false
            ) {
                set_config($name, $value, 'theme_epure');
            }
        }

        upgrade_plugin_savepoint(true, 2026100600, 'theme', 'epure');
    }

    if ($oldversion < 2026100700) {
        // The words for companies and departments move from local_epure to the theme, which no longer
        // needs the companion plugin. Words chosen for each company (local_epure 0.3) are kept.
        $local = get_config('local_epure');
        foreach (['companies', 'companyrev'] as $name) {
            if (isset($local->$name) && get_config('theme_epure', $name) === false) {
                set_config($name, $local->$name, 'theme_epure');
            }
        }
        $platform = [];
        $iomadwords = false;
        foreach ($local as $name => $value) {
            if (preg_match('/^company_\d+$/', $name) && get_config('theme_epure', $name) === false) {
                set_config($name, $value, 'theme_epure');
            } else if (preg_match('/^vocab_(fr|en)_(company|department)(_singular|_plural|_gender)?$/', $name, $m)) {
                // Words chosen for the whole platform: they become the words of all companies (company 0).
                $platform[$name] = $value;
                $iomadwords = $iomadwords || ($m[3] === '' && $value !== \theme_epure\vocabulary\terms::DEFAULTS[$m[1]][$m[2]]);
            }
        }
        if ($iomadwords && get_config('theme_epure', 'company_0') === false) {
            \theme_epure\vocabulary\company::save(0, $platform);
        }

        // Version 0.3 of local_epure wrote those platform words in the language pack: applying the vocabulary of
        // the theme again, which leaves them to the string manager, removes them.
        if ($iomadwords) {
            foreach (\theme_epure\vocabulary\manager::languages() as $lang) {
                \theme_epure\vocabulary\manager::apply($lang);
            }
        }

        upgrade_plugin_savepoint(true, 2026100700, 'theme', 'epure');
    }

    return true;
}
