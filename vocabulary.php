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
 * Vocabulary of the platform: the words used for courses, students and teachers.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Shows the progress of the first preparation of a language.
define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use theme_epure\form\vocabulary_form;
use theme_epure\vocabulary\company;
use theme_epure\vocabulary\manager;
use theme_epure\vocabulary\terms;

admin_externalpage_setup('theme_epure_vocabulary');

// With IOMAD, the words for companies and departments of all companies are chosen here too; each
// company can choose its own in its form (Edit company › Appearance).
$iomad = \theme_epure\company_style::iomad_installed();
$concepts = $iomad ? array_merge(terms::CONCEPTS, terms::IOMAD_CONCEPTS) : terms::CONCEPTS;
$values = array_merge(
    array_filter((array) get_config('theme_epure'), fn($name) => str_starts_with($name, 'vocab_'), ARRAY_FILTER_USE_KEY),
    $iomad ? company::values(0) : []
);

$pageurl = new moodle_url('/theme/epure/vocabulary.php');
$form = new vocabulary_form($pageurl, ['concepts' => $concepts, 'values' => $values]);
$translations = get_string_manager()->get_list_of_translations(true);

if ($data = $form->get_data()) {
    $iomadvalues = [];
    foreach (vocabulary_form::values_from($data, $concepts) as $name => $value) {
        if (preg_match('/^vocab_[a-z]+_(' . implode('|', terms::IOMAD_CONCEPTS) . ')(_|$)/', $name)) {
            $iomadvalues[$name] = $value;
        } else {
            set_config($name, $value, 'theme_epure');
        }
    }
    if ($iomad) {
        // Applied when the strings are loaded, by the string manager, not in the language pack.
        company::save(0, $iomadvalues);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('vocabulary', 'theme_epure'));
    foreach (manager::languages() as $lang) {
        echo $OUTPUT->heading($translations[$lang], 3);
        $progress = new progress_bar('theme_epure_vocab_' . $lang, 500, true);
        $stats = manager::apply($lang, $progress);
        $message = $stats['total']
            ? get_string('vocabapplied', 'theme_epure', (object) $stats)
            : get_string('vocabnone', 'theme_epure');
        echo $OUTPUT->notification($message, 'success', false);
        if ($stats['kept']) {
            echo $OUTPUT->notification(get_string('vocabkept', 'theme_epure', $stats['kept']), 'info', false);
        }
    }
    echo $OUTPUT->continue_button($pageurl);
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('vocabulary', 'theme_epure'));
echo html_writer::tag('p', get_string('vocabintro', 'theme_epure'));
if ($iomad && !\theme_epure\hook_callbacks::string_manager_available()) {
    echo $OUTPUT->notification(get_string('companyvocabunavailable', 'theme_epure'), 'warning', false);
}
if (core_plugin_manager::instance()->get_plugin_info('local_epure')) {
    // The former companion plugin applies its own copy of the words: it must be uninstalled.
    echo $OUTPUT->notification(get_string('localepureinstalled', 'theme_epure'), 'warning', false);
}

$languages = manager::languages();
if (!$languages) {
    echo $OUTPUT->notification(get_string('vocabnolanguage', 'theme_epure'), 'warning', false);
}
foreach ($languages as $lang) {
    $examples = manager::examples($lang);
    if (!$examples) {
        continue;
    }
    $table = new html_table();
    $table->caption = get_string('vocabexamples', 'theme_epure', (object) [
        'lang' => $translations[$lang],
        'count' => manager::count($lang),
    ]);
    $table->head = [get_string('vocabbefore', 'theme_epure'), get_string('vocabafter', 'theme_epure')];
    $table->attributes['class'] = 'generaltable w-auto';
    foreach ($examples as $example) {
        $table->data[] = [s($example['before']), s($example['after'])];
    }
    echo html_writer::table($table);
}

$form->display();
echo $OUTPUT->footer();
