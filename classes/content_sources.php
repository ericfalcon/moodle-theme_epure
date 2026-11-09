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
 * Where the activities keep the texts their teachers write, for the guide « Accessibility of the course ».
 *
 * Every activity gets its description and its files read by the guide. Beyond them:
 * - the modules of Moodle whose own content is written by the teachers have it read here (page, assignment, workshop,
 *   glossary, feedback, database) or by {@see course_accessibility} (the questions of a quiz, the chapters of a book, the
 *   pages of a lesson, each with its own place);
 * - the modules whose content comes from elsewhere (SCORM and IMS packages, external tools) get a point to confirm;
 * - the modules of other plugins have their content read when they give it, through the callback
 *   {@see self::CALLBACK} of their lib.php, else a point to confirm says that Épure does not read it.
 * The contributions of the learners (posts, entries, records, submissions) are never read: they are not the course.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_sources {
    /** @var string Callback of the lib.php of a module: function mod_{name}_epure_accessibility_texts(cm_info $cm): array. */
    public const CALLBACK = 'epure_accessibility_texts';

    /** @var string[] Modules of Moodle whose own content is read by the guide, beyond the description and the files. */
    public const CONTENT = ['page', 'assign', 'quiz', 'book', 'lesson', 'workshop', 'glossary', 'feedback', 'data'];

    /** @var string[] Modules of Moodle whose content comes from elsewhere: a package or an external tool. */
    public const EXTERNAL = ['scorm', 'imscp', 'lti'];

    /**
     * How the guide reads a module.
     *
     * @param string $modname Name of the module.
     * @return string content (its own content read), external (from elsewhere, to confirm), description (its
     *     description and its files are all it holds of the teacher), plugin (a module of another plugin that gives its
     *     content), unread (a module of another plugin that does not).
     */
    public static function coverage(string $modname): string {
        if (in_array($modname, self::CONTENT, true)) {
            return 'content';
        }
        if (in_array($modname, self::EXTERNAL, true)) {
            return 'external';
        }
        if (in_array($modname, self::core_modules(), true)) {
            return 'description';
        }
        return \core_component::get_component_directory('mod_' . $modname)
            && component_callback_exists('mod_' . $modname, self::CALLBACK) ? 'plugin' : 'unread';
    }

    /**
     * The modules of Moodle itself, in this version.
     *
     * @return string[]
     */
    public static function core_modules(): array {
        return \core_plugin_manager::standard_plugins_list('mod') ?: [];
    }

    /**
     * The modules of other plugins installed on the site, each with how the guide reads it.
     *
     * @return array<string, string> Name of the module => plugin or unread.
     */
    public static function third_party(): array {
        $modules = [];
        foreach (array_keys(\core_component::get_plugin_list('mod')) as $modname) {
            if (!in_array($modname, self::core_modules(), true)) {
                $modules[$modname] = self::coverage($modname);
            }
        }
        ksort($modules);
        return $modules;
    }

    /**
     * What the setting of the guide says of the modules of other plugins installed: which have their content read, and
     * which only their description and their files.
     *
     * @return string Plain text.
     */
    public static function report(): string {
        $modules = self::third_party();
        if (!$modules) {
            return get_string('courseaccessibility_modules_none', 'theme_epure');
        }
        $names = ['plugin' => [], 'unread' => []];
        foreach ($modules as $modname => $coverage) {
            $names[$coverage][] = get_string('modulename', $modname) . ' (mod_' . $modname . ')';
        }
        $lines = [];
        foreach ($names as $coverage => $list) {
            if ($list) {
                $lines[] = get_string('courseaccessibility_modules_' . $coverage, 'theme_epure', implode(', ', $list));
            }
        }
        return implode(' ', $lines);
    }

    /**
     * The texts of an activity beyond its description, where the teachers write them.
     *
     * @param \cm_info $cm Activity.
     * @param \stdClass|null $record Its record, when its table has the name of the module.
     * @return array<int, array{text: string, format: int, kind: string, name: string, url: string}>
     */
    public static function texts(\cm_info $cm, ?\stdClass $record): array {
        global $DB;
        $edit = (new \moodle_url('/course/modedit.php', ['update' => $cm->id]))->out(false);
        $texts = [];
        $add = function (?string $text, $format, string $kind, string $name, string $url) use (&$texts): void {
            if (trim((string) $text) !== '') {
                $texts[] = ['text' => (string) $text, 'format' => (int) $format, 'kind' => $kind, 'name' => $name,
                    'url' => $url];
            }
        };
        if (!in_array($cm->modname, self::core_modules(), true)) {
            // A module of another plugin: the texts it gives, if it gives them.
            $given = component_callback('mod_' . $cm->modname, self::CALLBACK, [$cm], []);
            foreach (is_array($given) ? $given : [] as $text) {
                $add(
                    $text['text'] ?? '',
                    $text['format'] ?? FORMAT_HTML,
                    'content',
                    (string) ($text['name'] ?? ''),
                    (string) ($text['url'] ?? $edit)
                );
            }
            return $texts;
        }
        if (!$record) {
            return [];
        }
        switch ($cm->modname) {
            case 'page':
                $add($record->content, $record->contentformat, 'content', '', $edit);
                break;
            case 'assign':
                $add($record->activity ?? '', $record->activityformat ?? FORMAT_HTML, 'content', '', $edit);
                break;
            case 'workshop':
                // The instructions for the submission and for the assessment, and the conclusion.
                foreach (['instructauthors', 'instructreviewers', 'conclusion'] as $field) {
                    $add(
                        $record->$field ?? '',
                        $record->{$field . 'format'} ?? FORMAT_HTML,
                        'instructions',
                        get_string('courseaccessibility_workshop_' . $field, 'theme_epure'),
                        $edit
                    );
                }
                break;
            case 'glossary':
                // The entries of the teachers; those of the learners are their contributions.
                $entries = $DB->get_records(
                    'glossary_entries',
                    ['glossaryid' => $record->id, 'teacherentry' => 1],
                    'concept',
                    'id, concept, definition, definitionformat'
                );
                foreach ($entries as $entry) {
                    $add(
                        $entry->definition,
                        $entry->definitionformat,
                        'entry',
                        format_string($entry->concept),
                        (new \moodle_url('/mod/glossary/edit.php', ['cmid' => $cm->id, 'id' => $entry->id]))->out(false)
                    );
                }
                break;
            case 'feedback':
                // The texts and images of the form (items « Text and media »).
                $items = $DB->get_records(
                    'feedback_item',
                    ['feedback' => $record->id, 'typ' => 'label'],
                    'position',
                    'id, name, presentation'
                );
                foreach ($items as $item) {
                    $name = trim((string) $item->name) !== '' ? format_string((string) $item->name)
                        : get_string('courseaccessibility_feedback_label', 'theme_epure');
                    $add(
                        $item->presentation,
                        FORMAT_HTML,
                        'item',
                        $name,
                        (new \moodle_url('/mod/feedback/edit.php', ['id' => $cm->id]))->out(false)
                    );
                }
                break;
            case 'data':
                // The templates written by the teachers, which show the records.
                foreach (
                    ['listtemplateheader', 'listtemplate', 'listtemplatefooter', 'singletemplate', 'addtemplate',
                        'asearchtemplate'] as $template
                ) {
                    $add(
                        $record->$template ?? '',
                        FORMAT_HTML,
                        'template',
                        get_string('courseaccessibility_data_' . $template, 'theme_epure'),
                        (new \moodle_url('/mod/data/templates.php', ['id' => $cm->id, 'mode' => $template]))->out(false)
                    );
                }
                break;
        }
        return $texts;
    }
}
