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
 * What the guide « Accessibility of the course » corrects in the texts of the course, besides the images
 * (content_alt): the frames without a title get the title written in the guide, the links made only of images without
 * a text alternative get, on their image, where they lead, the tables without a header get their first row as header.
 * Found as course_accessibility::check_html finds them, so that once corrected they are no longer counted.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_fix {
    /** @var int Characters of a title or of a text alternative at most. */
    public const MAX_LENGTH = 250;

    /** @var string[] What the guide corrects, in their order on the page. */
    public const KINDS = ['frames', 'imagelinks', 'tables'];

    /**
     * The frames without a title of a text: each with its tag, its offset and its address.
     *
     * @param string $html Text.
     * @return array[] Each: tag, offset, src.
     */
    public static function frames(string $html): array {
        $frames = [];
        preg_match_all('~<iframe\b[^>]*>~i', $html, $tags, PREG_OFFSET_CAPTURE);
        foreach ($tags[0] as [$tag, $offset]) {
            if (trim(self::attribute($tag, 'title')) !== '') {
                continue;
            }
            $frames[] = ['tag' => $tag, 'offset' => $offset, 'src' => self::attribute($tag, 'src')];
        }
        return $frames;
    }

    /**
     * The links made only of images without a text alternative, of a text: a screen reader says « link » without where
     * it leads. Each with the whole link, its offset, its address and its first image.
     *
     * @param string $html Text.
     * @return array[] Each: tag (the whole link), offset, src (its address), img (the tag of its first image).
     */
    public static function imagelinks(string $html): array {
        $links = [];
        preg_match_all('~<a\b[^>]*>.*?</a\s*>~is', $html, $tags, PREG_OFFSET_CAPTURE);
        foreach ($tags[0] as [$tag, $offset]) {
            preg_match('~^<a\b[^>]*>~i', $tag, $open);
            if (
                !preg_match('~\shref\s*=~i', $open[0]) || trim(self::attribute($open[0], 'aria-label')) !== ''
                    || !preg_match_all('~<img\b[^>]*>~i', $tag, $images)
            ) {
                continue;
            }
            $text = self::text($tag);
            $described = array_filter($images[0], fn($img) => trim(self::attribute($img, 'alt')) !== '');
            if ($text !== '' || $described) {
                continue;
            }
            $links[] = ['tag' => $tag, 'offset' => $offset, 'src' => self::attribute($open[0], 'href'), 'img' => $images[0][0]];
        }
        return $links;
    }

    /**
     * The tables without any header of a text (but the tables of layout): each with the texts of its first row.
     *
     * @param string $html Text.
     * @return array[] Each: tag (the whole table), offset, src (the texts of its first row), cells (their number).
     */
    public static function tables(string $html): array {
        $tables = [];
        preg_match_all('~<table\b[^>]*>.*?</table\s*>~is', $html, $tags, PREG_OFFSET_CAPTURE);
        foreach ($tags[0] as [$tag, $offset]) {
            preg_match('~^<table\b[^>]*>~i', $tag, $open);
            $role = \core_text::strtolower(trim(self::attribute($open[0], 'role')));
            if (in_array($role, ['presentation', 'none'], true) || preg_match('~<th\b~i', $tag)) {
                continue;
            }
            if (
                !preg_match('~<tr\b[^>]*>(.*?)</tr\s*>~is', $tag, $row)
                    || !preg_match_all('~<td\b[^>]*>(.*?)</td\s*>~is', $row[1], $cells)
            ) {
                continue;
            }
            $texts = array_map(fn($cell) => self::text($cell), $cells[1]);
            $tables[] = ['tag' => $tag, 'offset' => $offset, 'src' => implode(' | ', $texts), 'cells' => count($texts)];
        }
        return $tables;
    }

    /**
     * What the guide can correct in a part, for a teacher who can change it: by kind, each with its key, what tells it
     * (an address, the first row of a table) and the place it is in.
     *
     * @param array $part Part of the guide.
     * @param \stdClass $course Course.
     * @return array[] By kind: each key, src, place, image (the address of the image of a link).
     */
    public static function items(array $part, \stdClass $course): array {
        $items = array_fill_keys(self::KINDS, []);
        foreach (content_alt::fields($part, $course) as $field) {
            if (!has_capability(content_alt::capability($field), $field['context'])) {
                continue;
            }
            $html = self::html($field);
            if ($html === null) {
                continue;
            }
            foreach (self::KINDS as $kind) {
                foreach (self::$kind($html) as $index => $item) {
                    $items[$kind][] = ['key' => self::key($field, $index, $item['src']), 'src' => $item['src'],
                        'place' => $field['name'],
                        'image' => isset($item['img']) ? content_alt::image_url($field, self::attribute($item['img'], 'src')) : ''];
                }
            }
        }
        return $items;
    }

    /**
     * Writes into the texts of the course what was given in the guide, for a teacher who can change them: each key
     * checked against the texts as they are now.
     *
     * @param \stdClass $course Course.
     * @param array $parts Parts of the guide.
     * @param array $titles Title of each frame, by key.
     * @param array $links Where each link of images leads, by key.
     * @param array $tables Tables whose first row is the header, by key.
     * @return int[] Corrected, by kind.
     */
    public static function apply(\stdClass $course, array $parts, array $titles, array $links, array $tables): array {
        global $DB;
        $done = array_fill_keys(self::KINDS, 0);
        $given = ['frames' => $titles, 'imagelinks' => $links, 'tables' => $tables];
        foreach ($parts as $part) {
            foreach (content_alt::fields($part, $course) as $field) {
                if (!has_capability(content_alt::capability($field), $field['context'])) {
                    continue;
                }
                $html = self::html($field);
                if ($html === null) {
                    continue;
                }
                $before = $html;
                // Kind by kind, each found again in the text as the one before left it: a link in a table stays found.
                foreach (self::KINDS as $kind) {
                    // From the last to the first: the offsets of those before stay right.
                    foreach (array_reverse(self::$kind($html), true) as $index => $item) {
                        $value = $given[$kind][self::key($field, $index, $item['src'])] ?? null;
                        $tag = $value === null ? null : self::correct($kind, $item, $value);
                        if ($tag !== null) {
                            $html = substr_replace($html, $tag, $item['offset'], strlen($item['tag']));
                            $done[$kind]++;
                        }
                    }
                }
                if ($html !== $before) {
                    $DB->set_field($field['table'], $field['field'], $html, ['id' => $field['id']]);
                }
            }
        }
        if (array_sum($done)) {
            rebuild_course_cache((int) $course->id, true);
            course_accessibility::forget((int) $course->id);
        }
        return $done;
    }

    /**
     * The corrected HTML of a frame, a link or a table, or null when nothing was given for it.
     *
     * @param string $kind Kind.
     * @param array $item Frame, link or table.
     * @param mixed $value What was given: a title, where the link leads, or whether the first row is the header.
     * @return string|null
     */
    protected static function correct(string $kind, array $item, $value): ?string {
        if ($kind === 'tables') {
            if (empty($value)) {
                return null;
            }
            // The cells of the first row become headers of their column.
            return preg_replace_callback('~<tr\b[^>]*>.*?</tr\s*>~is', fn($row) => preg_replace(
                ['~<td\b~i', '~</td(\s*)>~i'],
                ['<th scope="col"', '</th$1>'],
                $row[0]
            ), $item['tag'], 1);
        }
        $text = trim(\core_text::substr(clean_param((string) $value, PARAM_TEXT), 0, self::MAX_LENGTH));
        if ($text === '') {
            return null;
        }
        if ($kind === 'frames') {
            return self::with_attribute($item['tag'], 'title', $text);
        }
        // Where the link leads, as the text alternative of its first image; its other images, decorative.
        $first = true;
        return preg_replace_callback('~<img\b[^>]*>~i', function ($img) use (&$first, $text) {
            $alt = $first ? $text : '';
            $first = false;
            return self::with_attribute($img[0], 'alt', $alt);
        }, $item['tag']);
    }

    /**
     * A tag with an attribute set, the one it had taken away.
     *
     * @param string $tag Tag.
     * @param string $name Attribute.
     * @param string $value Value.
     * @return string
     */
    protected static function with_attribute(string $tag, string $name, string $value): string {
        $tag = preg_replace('~\s' . $name . '\s*=\s*(["\']).*?\1~is', '', $tag);
        $tag = preg_replace('~\s' . $name . '(?=[\s/>])~i', '', $tag);
        $end = str_ends_with(rtrim($tag), '/>') ? ' />' : '>';
        return preg_replace('~\s*/?>$~', '', rtrim($tag)) . ' ' . $name . '="' . s($value) . '"' . $end;
    }

    /**
     * The value of an attribute of a tag, empty when it has none.
     *
     * @param string $tag Tag.
     * @param string $name Attribute.
     * @return string
     */
    protected static function attribute(string $tag, string $name): string {
        if (!preg_match('~\s' . preg_quote($name, '~') . '\s*=\s*(["\'])(.*?)\1~is', $tag, $match)) {
            return '';
        }
        return html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5);
    }

    /**
     * The text of some HTML, its spaces gathered.
     *
     * @param string $html HTML.
     * @return string
     */
    protected static function text(string $html): string {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
    }

    /**
     * The HTML of a text, null when it is not in HTML.
     *
     * @param array $field Text.
     * @return string|null
     */
    protected static function html(array $field): ?string {
        global $DB;
        $fields = $field['field'] . ', ' . $field['formatfield'];
        $record = $DB->get_record($field['table'], ['id' => $field['id']], $fields, IGNORE_MISSING);
        if (!$record || !in_array((int) $record->{$field['formatfield']}, [(int) FORMAT_HTML, (int) FORMAT_MOODLE], true)) {
            return null;
        }
        return (string) $record->{$field['field']};
    }

    /**
     * The key of a frame, a link or a table: its text, its place among those of its kind, and a sign of what tells it.
     *
     * @param array $field Text.
     * @param int $index Place.
     * @param string $src Address, or texts of the first row.
     * @return string
     */
    protected static function key(array $field, int $index, string $src): string {
        // Letters, digits, - and _ only: the keys of a parameter of Moodle.
        return implode('-', [$field['table'], $field['field'], $field['id'], $index, substr(sha1($src), 0, 10)]);
    }
}
