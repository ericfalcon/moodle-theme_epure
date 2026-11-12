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
 * The images without a text alternative that a teacher can describe from the guide « Accessibility of the course »,
 * without opening each activity: in the description of an activity, the content of a page, the chapters of a book,
 * the summary of the course and of its sections, written in HTML. The text alternative is written into the image
 * itself, in its text, and nothing else of the text changes. Each image is told by its place among the images without
 * a text alternative of its text and by its address: a text changed meanwhile is left alone.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_alt {
    /** @var int Characters of a text alternative at most. */
    public const MAX_LENGTH = 250;

    /**
     * The texts of a part of the course that the guide can change: each with its table, field, record, format, context
     * and files.
     *
     * @param array $part Part of the guide (type course, section or cm, and id).
     * @param \stdClass $course Course.
     * @return array[] Each: table, field, id, formatfield, context, component, filearea, itemid, name (of the place).
     */
    public static function fields(array $part, \stdClass $course): array {
        global $DB;
        if ($part['type'] === 'course') {
            return [self::field(
                'course',
                'summary',
                (int) $course->id,
                \context_course::instance($course->id),
                'course',
                'summary',
                0
            )];
        }
        if ($part['type'] === 'section') {
            return [self::field(
                'course_sections',
                'summary',
                (int) $part['id'],
                \context_course::instance($course->id),
                'course',
                'section',
                (int) $part['id']
            )];
        }
        $cm = get_fast_modinfo($course, -1)->get_cm($part['id']);
        $context = \context_module::instance($cm->id);
        $fields = [];
        if ($DB->get_manager()->field_exists($cm->modname, 'intro')) {
            $fields[] = self::field($cm->modname, 'intro', (int) $cm->instance, $context, 'mod_' . $cm->modname, 'intro', 0);
        }
        if ($cm->modname === 'page') {
            $revision = (int) $DB->get_field('page', 'revision', ['id' => $cm->instance]);
            $fields[] = self::field('page', 'content', (int) $cm->instance, $context, 'mod_page', 'content', $revision);
        }
        if ($cm->modname === 'book') {
            foreach ($DB->get_records('book_chapters', ['bookid' => $cm->instance], 'pagenum', 'id, title') as $chapter) {
                $fields[] = self::field(
                    'book_chapters',
                    'content',
                    (int) $chapter->id,
                    $context,
                    'mod_book',
                    'chapter',
                    (int) $chapter->id,
                    format_string($chapter->title, true, ['context' => $context])
                );
            }
        }
        return $fields;
    }

    /**
     * A text that the guide can change.
     *
     * @param string $table Table.
     * @param string $field Field of the text.
     * @param int $id Record.
     * @param \context $context Context of its files.
     * @param string $component Component of its files.
     * @param string $filearea Area of its files.
     * @param int $itemid Item of its files.
     * @param string $name Name of its place, in an activity of several texts.
     * @return array
     */
    protected static function field(
        string $table,
        string $field,
        int $id,
        \context $context,
        string $component,
        string $filearea,
        int $itemid,
        string $name = ''
    ): array {
        return ['table' => $table, 'field' => $field, 'id' => $id, 'formatfield' => $field . 'format', 'context' => $context,
            'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid, 'name' => $name];
    }

    /**
     * The tags of the images without a text alternative of a text, in their order: each with its offset and its address.
     *
     * @param string $html Text.
     * @return array[] Each: tag, offset, src.
     */
    public static function missing(string $html): array {
        $images = [];
        preg_match_all('~<img\b[^>]*>~i', $html, $tags, PREG_OFFSET_CAPTURE);
        foreach ($tags[0] as [$tag, $offset]) {
            if (preg_match('~\salt\s*=~i', $tag)) {
                continue;
            }
            preg_match('~\ssrc\s*=\s*(["\'])(.*?)\1~is', $tag, $src);
            $images[] = ['tag' => $tag, 'offset' => $offset, 'src' => html_entity_decode($src[2] ?? '', ENT_QUOTES)];
        }
        return $images;
    }

    /**
     * The images without a text alternative of a part, that the guide can describe: each with its key, its name, the
     * address to show it and the place it is in.
     *
     * @param array $part Part of the guide.
     * @param \stdClass $course Course.
     * @return array[] Each: key, filename, url, place.
     */
    public static function images(array $part, \stdClass $course): array {
        global $DB;
        $images = [];
        foreach (self::fields($part, $course) as $field) {
            $record = $DB->get_record(
                $field['table'],
                ['id' => $field['id']],
                $field['field'] . ', ' . $field['formatfield'],
                IGNORE_MISSING
            );
            $format = $record ? (int) $record->{$field['formatfield']} : null;
            if (!in_array($format, [(int) FORMAT_HTML, (int) FORMAT_MOODLE], true)) {
                continue;
            }
            foreach (self::missing((string) $record->{$field['field']}) as $index => $image) {
                $filename = rawurldecode(basename((string) parse_url($image['src'], PHP_URL_PATH)));
                $url = str_starts_with($image['src'], '@@PLUGINFILE@@/')
                    ? \moodle_url::make_pluginfile_url(
                        $field['context']->id,
                        $field['component'],
                        $field['filearea'],
                        $field['component'] === 'mod_page' || $field['filearea'] === 'section' || $field['filearea'] === 'chapter'
                        ? $field['itemid'] : null,
                        '/',
                        rawurldecode(substr($image['src'], 15))
                    )->out(false)
                    : (preg_match('~^https?://~i', $image['src']) ? $image['src'] : '');
                $images[] = [
                    'key' => self::key($field, $index, $image['src']),
                    'filename' => $filename !== '' ? $filename : get_string('courseaccessibility_alt_image', 'theme_epure'),
                    'url' => $url,
                    'place' => $field['name'],
                ];
            }
        }
        return $images;
    }

    /**
     * The key of an image: its text, its place among the images without a text alternative, and a sign of its address.
     *
     * @param array $field Text.
     * @param int $index Place.
     * @param string $src Address.
     * @return string
     */
    protected static function key(array $field, int $index, string $src): string {
        // Letters, digits, - and _ only: the keys of a parameter of Moodle.
        return implode('-', [$field['table'], $field['field'], $field['id'], $index, substr(sha1($src), 0, 10)]);
    }

    /**
     * Writes the text alternatives given in the guide into their images, for a teacher who can change the parts: each
     * key checked against the images of the course as they are now. An image marked decorative gets an empty text
     * alternative.
     *
     * @param \stdClass $course Course.
     * @param array $parts Parts of the guide.
     * @param array<string, string> $alts Text alternative by key.
     * @param array<string, bool> $decorative Decorative images, by key.
     * @return int Images described.
     */
    public static function apply(\stdClass $course, array $parts, array $alts, array $decorative): int {
        global $DB;
        $done = 0;
        foreach ($parts as $part) {
            foreach (self::fields($part, $course) as $field) {
                if (!has_capability(self::capability($field), $field['context'])) {
                    continue;
                }
                $record = $DB->get_record($field['table'], ['id' => $field['id']], 'id, ' . $field['field'], IGNORE_MISSING);
                if (!$record) {
                    continue;
                }
                $html = (string) $record->{$field['field']};
                $changed = false;
                // From the last image to the first: the offsets of those before stay right.
                foreach (array_reverse(self::missing($html), true) as $index => $image) {
                    $key = self::key($field, $index, $image['src']);
                    $alt = trim(\core_text::substr(clean_param($alts[$key] ?? '', PARAM_TEXT), 0, self::MAX_LENGTH));
                    if (empty($decorative[$key]) && $alt === '') {
                        continue;
                    }
                    $tag = preg_replace('~\s*/?>$~', '', $image['tag']) . ' alt="' . s(empty($decorative[$key]) ? $alt : '')
                        . '"' . (str_ends_with(rtrim($image['tag']), '/>') ? ' />' : '>');
                    $html = substr_replace($html, $tag, $image['offset'], strlen($image['tag']));
                    $changed = true;
                    $done++;
                }
                if ($changed) {
                    $DB->set_field($field['table'], $field['field'], $html, ['id' => $field['id']]);
                }
            }
        }
        if ($done) {
            rebuild_course_cache((int) $course->id, true);
            course_accessibility::forget((int) $course->id);
        }
        return $done;
    }

    /**
     * The capability to change a text.
     *
     * @param array $field Text.
     * @return string
     */
    protected static function capability(array $field): string {
        return $field['context'] instanceof \context_module ? 'moodle/course:manageactivities' : 'moodle/course:update';
    }
}
