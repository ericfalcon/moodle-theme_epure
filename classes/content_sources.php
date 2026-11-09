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
     * @var array<string, string> The modules of other plugins installed on most sites (the directory of Moodle, by
     * installs), read from their tables as they are published (db/install.xml), without their code: content (their
     * content read here), description (their description and files hold all the teacher writes, the rest being the
     * contributions of the learners, a certificate or a register), external (their content comes from elsewhere: a
     * video, a meeting, a package). A module that gives its content through {@see self::CALLBACK} is read that way.
     */
    public const KNOWN = [
        'attendance' => 'description',
        'attendanceregister' => 'description',
        'board' => 'description',
        'booking' => 'content',
        'certificatebeautiful' => 'description',
        'checklist' => 'description',
        'choicegroup' => 'description',
        'coursecertificate' => 'description',
        'customcert' => 'description',
        'dialogue' => 'description',
        'diary' => 'content',
        'facetoface' => 'content',
        'game' => 'content',
        'geogebra' => 'external',
        'hotpot' => 'external',
        'hsuforum' => 'description',
        'hvp' => 'description',
        'icontent' => 'content',
        'interactivevideo' => 'external',
        'jitsi' => 'external',
        'journal' => 'description',
        'mediagallery' => 'description',
        'msteams' => 'external',
        'offlinequiz' => 'description',
        'organizer' => 'description',
        'pdfannotator' => 'description',
        'pdfprotect' => 'description',
        'publication' => 'description',
        'pulse' => 'description',
        'questionnaire' => 'content',
        'realtimequiz' => 'content',
        'reengagement' => 'description',
        'scheduler' => 'content',
        'simplecertificate' => 'description',
        'stickynotes' => 'description',
        'studentquiz' => 'description',
        'subcourse' => 'description',
        'supervideo' => 'external',
        'threesixo' => 'description',
        'turnitintooltwo' => 'external',
        'unilabel' => 'content',
        'videotime' => 'external',
        'vpl' => 'description',
        'zoom' => 'external',
    ];

    /**
     * How the guide reads a module.
     *
     * @param string $modname Name of the module.
     * @return string content (its own content read), external (from elsewhere, to confirm), description (its
     *     description and its files are all it holds of the teacher), plugin (a module of another plugin that gives its
     *     content), unread (a module of another plugin that does not, and that Épure does not know).
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
        if (self::gives($modname)) {
            return 'plugin';
        }
        return self::KNOWN[$modname] ?? 'unread';
    }

    /**
     * Whether a module of another plugin gives its content to the guide, through {@see self::CALLBACK}.
     *
     * @param string $modname Name of the module.
     * @return bool
     */
    protected static function gives(string $modname): bool {
        return \core_component::get_component_directory('mod_' . $modname)
            && component_callback_exists('mod_' . $modname, self::CALLBACK);
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
     * @return array<string, string> Name of the module => its coverage: plugin, content, description, external or unread.
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
        // Read: those that give their content, and those Épure knows how to read.
        $names = ['plugin' => [], 'external' => [], 'unread' => []];
        foreach ($modules as $modname => $coverage) {
            $group = in_array($coverage, ['content', 'description'], true) ? 'plugin' : $coverage;
            $names[$group][] = get_string('modulename', $modname) . ' (mod_' . $modname . ')';
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
        if (!in_array($cm->modname, self::core_modules(), true) && self::gives($cm->modname)) {
            // A module of another plugin that gives its texts.
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
        if (isset(self::KNOWN[$cm->modname])) {
            self::known_texts($cm->modname, (int) $cm->id, $record, $add);
            return $texts;
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

    /**
     * The texts of an activity of a module of another plugin that Épure knows, read from its tables as published. A
     * table or a field missing in the version installed gives no text.
     *
     * @param string $modname Name of the module.
     * @param int $cmid Id of the activity.
     * @param \stdClass $record Its record.
     * @param callable $add Adds a text: (text, format, kind, name, url).
     */
    protected static function known_texts(string $modname, int $cmid, \stdClass $record, callable $add): void {
        $view = (new \moodle_url('/mod/' . $modname . '/view.php', ['id' => $cmid]))->out(false);
        $edit = (new \moodle_url('/course/modedit.php', ['update' => $cmid]))->out(false);
        $str = fn(string $key, $a = null) => get_string('courseaccessibility_' . $key, 'theme_epure', $a);
        $rows = function (string $table, array $conditions, string $sort = 'id') {
            global $DB;
            if (!$DB->get_manager()->table_exists($table)) {
                return [];
            }
            try {
                return $DB->get_records($table, $conditions, $sort);
            } catch (\dml_exception $e) {
                return [];
            }
        };
        $html = fn($text) => trim((string) $text) !== '' && str_starts_with(ltrim((string) $text), '<');
        switch ($modname) {
            case 'booking':
                foreach ($rows('booking_options', ['bookingid' => $record->id], 'sortorder, id') as $option) {
                    $add(
                        $option->description ?? '',
                        $option->descriptionformat ?? FORMAT_HTML,
                        'item',
                        format_string((string) ($option->text ?? '')),
                        $view
                    );
                }
                break;
            case 'diary':
                $n = 0;
                foreach ($rows('diary_prompts', ['diaryid' => $record->id], 'datestart, id') as $prompt) {
                    $n++;
                    $title = trim((string) ($prompt->title ?? ''));
                    $add(
                        $prompt->text ?? '',
                        $prompt->format ?? FORMAT_HTML,
                        'item',
                        $title !== '' ? format_string($title) : $str('diary_prompt', $n),
                        $view
                    );
                }
                break;
            case 'facetoface':
                $n = 0;
                foreach ($rows('facetoface_sessions', ['facetoface' => $record->id]) as $session) {
                    $n++;
                    $add($session->details ?? '', FORMAT_HTML, 'item', $str('facetoface_session', $n), $view);
                }
                break;
            case 'game':
                $add($record->toptext ?? '', FORMAT_HTML, 'item', $str('game_toptext'), $edit);
                $add($record->bottomtext ?? '', FORMAT_HTML, 'item', $str('game_bottomtext'), $edit);
                break;
            case 'hotpot':
                $add($record->entrytext ?? '', $record->entryformat ?? FORMAT_HTML, 'item', $str('hotpot_entrytext'), $edit);
                $add($record->exittext ?? '', $record->exitformat ?? FORMAT_HTML, 'item', $str('hotpot_exittext'), $edit);
                break;
            case 'icontent':
                foreach ($rows('icontent_pages', ['icontentid' => $record->id], 'pagenum, id') as $page) {
                    $add(
                        $page->pageicontent ?? '',
                        $page->pageicontentformat ?? FORMAT_HTML,
                        'lessonpage',
                        format_string((string) ($page->title ?? '')),
                        $view
                    );
                }
                break;
            case 'interactivevideo':
                $add($record->endscreentext ?? '', FORMAT_HTML, 'item', $str('interactivevideo_endscreen'), $edit);
                // The interactions written in the editor; the others are a link, a file or settings.
                foreach ($rows('interactivevideo_items', ['cmid' => $cmid], 'timestamp, id') as $item) {
                    if ($html($item->content ?? '')) {
                        $add($item->content, FORMAT_HTML, 'item', format_string((string) ($item->title ?? '')), $view);
                    }
                }
                break;
            case 'questionnaire':
                $survey = $rows('questionnaire_survey', ['id' => $record->sid ?? 0]);
                $survey = reset($survey);
                if ($survey) {
                    $add($survey->info ?? '', FORMAT_HTML, 'content', '', $edit);
                    $add($survey->thank_body ?? '', FORMAT_HTML, 'item', $str('questionnaire_thanks'), $edit);
                    $n = 0;
                    foreach ($rows('questionnaire_question', ['surveyid' => $survey->id], 'position, id') as $question) {
                        // Deleted: 1, or y in the older versions.
                        if (!empty($question->deleted) && $question->deleted !== 'n') {
                            continue;
                        }
                        $n++;
                        $name = trim((string) ($question->name ?? ''));
                        $add(
                            $question->content ?? '',
                            FORMAT_HTML,
                            'question',
                            $name !== '' ? format_string($name) : (string) $n,
                            $view
                        );
                    }
                }
                break;
            case 'realtimequiz':
                foreach ($rows('realtimequiz_question', ['quizid' => $record->id], 'questionnum, id') as $question) {
                    $add(
                        $question->questiontext ?? '',
                        $question->questiontextformat ?? FORMAT_HTML,
                        'question',
                        (string) ($question->questionnum ?? ''),
                        $view
                    );
                }
                break;
            case 'scheduler':
                $add(
                    $record->bookinginstructions ?? '',
                    $record->bookinginstructionsformat ?? FORMAT_HTML,
                    'instructions',
                    $str('scheduler_instructions'),
                    $edit
                );
                break;
            case 'unilabel':
                self::unilabel_texts($record, $rows, $add, $str, $edit);
                break;
            case 'videotime':
                $add(
                    $record->video_description ?? '',
                    $record->video_description_format ?? FORMAT_HTML,
                    'item',
                    $str('videotime_description'),
                    $edit
                );
                break;
        }
    }

    /**
     * The texts of a Unilabel, by its type: the segments of an accordion, the tiles of a grid, the captions of a
     * carousel, the images of an image board without a text alternative nor a title.
     *
     * @param \stdClass $record The Unilabel.
     * @param callable $rows Reads the records of a table.
     * @param callable $add Adds a text.
     * @param callable $str A string of the theme.
     * @param string $edit Link to change the activity.
     */
    protected static function unilabel_texts(\stdClass $record, callable $rows, callable $add, callable $str, string $edit): void {
        $type = (string) ($record->unilabeltype ?? '');
        $parent = $rows('unilabeltype_' . $type, ['unilabelid' => $record->id]);
        $parent = reset($parent);
        if (!$parent) {
            return;
        }
        switch ($type) {
            case 'accordion':
                foreach ($rows('unilabeltype_accordion_seg', ['accordionid' => $parent->id], 'sortorder, id') as $seg) {
                    $name = trim(html_to_text((string) ($seg->heading ?? ''), 0, false));
                    $add(($seg->heading ?? '') . ($seg->content ?? ''), FORMAT_HTML, 'item', $name, $edit);
                }
                break;
            case 'grid':
                foreach ($rows('unilabeltype_grid_tile', ['gridid' => $parent->id], 'sortorder, id') as $tile) {
                    $add($tile->content ?? '', FORMAT_HTML, 'item', format_string((string) ($tile->title ?? '')), $edit);
                }
                break;
            case 'carousel':
                $n = 0;
                foreach ($rows('unilabeltype_carousel_slide', ['carouselid' => $parent->id], 'sortorder, id') as $slide) {
                    $n++;
                    $add($slide->caption ?? '', FORMAT_HTML, 'item', $str('unilabel_slide', $n), $edit);
                }
                break;
            case 'imageboard':
                $n = 0;
                foreach ($rows('unilabeltype_imageboard_img', ['imageboardid' => $parent->id]) as $image) {
                    $n++;
                    if (trim((string) ($image->alt ?? '')) === '' && trim((string) ($image->title ?? '')) === '') {
                        // An image without a text alternative: counted as such.
                        $add('<img src="#">', FORMAT_HTML, 'item', $str('unilabel_image', $n), $edit);
                    }
                }
                break;
        }
    }
}
