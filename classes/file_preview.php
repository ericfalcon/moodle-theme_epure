<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * Preview of the files of a course: the file of a resource (PDF, image, video, sound, text) opens in a dialog on the
 * page of the course, without leaving it, with the previous and next files of the page (theme_epure/file_preview).
 *
 * Only the resources that Moodle shows in its own page (« Embed », « Open », « In frame », and « Automatic » when it
 * comes to one of them): « Force download », « New window » and « In pop-up » keep their behaviour, as do the other
 * kinds of files. Opening a file in the preview counts as a view of the resource, as on its page (events, completion).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_preview {
    /** @var int[] Displays of a resource that open in Moodle's page (resourcelib: embed, frame, open). */
    public const DISPLAYS = [1, 2, 5];

    /**
     * Kind of preview of a type of file, null when it has none: PDF in the reader of the browser, image, video and
     * sound with the players of the browser, plain text.
     *
     * @param string $mimetype Type of the file.
     * @return string|null pdf, image, video, audio or text.
     */
    public static function kind(string $mimetype): ?string {
        $mimetype = strtolower($mimetype);
        if ($mimetype === 'application/pdf') {
            return 'pdf';
        }
        if (in_array($mimetype, ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml', 'image/avif'], true)) {
            return 'image';
        }
        if (in_array($mimetype, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
            return 'video';
        }
        $sounds = ['audio/mp3', 'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/webm'];
        if (in_array($mimetype, $sounds, true)) {
            return 'audio';
        }
        if ($mimetype === 'text/plain') {
            return 'text';
        }
        return null;
    }

    /**
     * Whether the preview applies to a page: the page of a course, out of the edit mode, for a user who is logged in.
     *
     * @param \moodle_page $page Page.
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        return str_starts_with((string) $page->pagetype, 'course-view-') && !empty($page->course->id)
            && (int) $page->course->id !== (int) SITEID && isloggedin() && !isguestuser() && !$page->user_is_editing();
    }

    /**
     * The files of the resources of a course that open in the preview, in the order of the course.
     *
     * @param \stdClass $course Course.
     * @return array[] One per resource: cmid, name, filename, kind, url, download.
     */
    public static function items(\stdClass $course): array {
        global $DB;
        $cms = [];
        foreach (get_fast_modinfo($course)->get_instances_of('resource') as $cm) {
            $display = (int) ($cm->customdata['display'] ?? 0);
            if ($cm->uservisible && $cm->is_visible_on_course_page() && in_array($display, self::DISPLAYS, true)) {
                $cms[$cm->id] = $cm;
            }
        }
        if (!$cms) {
            return [];
        }
        // The main file of each resource, as in its page: the first by sort order. One query for the whole course.
        [$insql, $params] = $DB->get_in_or_equal(array_keys($cms), SQL_PARAMS_NAMED);
        $params += ['level' => CONTEXT_MODULE, 'component' => 'mod_resource', 'filearea' => 'content', 'dot' => '.'];
        $records = $DB->get_recordset_sql(
            "SELECT f.id, f.contextid, f.filepath, f.filename, f.mimetype, f.sortorder, ctx.instanceid AS cmid, r.revision
               FROM {files} f
               JOIN {context} ctx ON ctx.id = f.contextid AND ctx.contextlevel = :level
               JOIN {course_modules} cm ON cm.id = ctx.instanceid
               JOIN {resource} r ON r.id = cm.instance
              WHERE ctx.instanceid $insql AND f.component = :component AND f.filearea = :filearea AND f.itemid = 0
                    AND f.filename <> :dot
           ORDER BY f.sortorder DESC, f.id ASC",
            $params
        );
        $files = [];
        foreach ($records as $file) {
            $files[$file->cmid] ??= $file;
        }
        $records->close();
        $items = [];
        foreach ($cms as $cmid => $cm) {
            $file = $files[$cmid] ?? null;
            $kind = $file ? self::kind((string) $file->mimetype) : null;
            if ($kind === null) {
                continue;
            }
            $url = \moodle_url::make_pluginfile_url(
                $file->contextid,
                'mod_resource',
                'content',
                $file->revision,
                $file->filepath,
                $file->filename
            );
            $items[] = [
                'cmid' => (int) $cmid,
                'name' => format_string($cm->name, true, ['context' => $cm->context]),
                'filename' => $file->filename,
                'kind' => $kind,
                'url' => $url->out(false),
                'download' => (new \moodle_url($url, ['forcedownload' => 1]))->out(false),
            ];
        }
        return $items;
    }

    /**
     * A view of a resource from the preview, as on its page: event and completion.
     *
     * @param int $cmid Module of the resource.
     */
    public static function viewed(int $cmid): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/resource/lib.php');
        require_once($CFG->libdir . '/completionlib.php');
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'resource');
        $context = \context_module::instance($cm->id);
        require_capability('mod/resource:view', $context);
        resource_view($DB->get_record('resource', ['id' => $cm->instance], '*', MUST_EXIST), $course, $cm, $context);
    }
}
