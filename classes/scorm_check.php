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
 * What a SCORM package or an IMS content package holds, read from its files as Moodle unzipped them (area content),
 * without running any of its scripts. Its authoring tool first: Articulate Storyline and Rise keep their slides and blocks as data,
 * read here; a package of plain HTML pages is read as a page of the course; the others (Captivate, iSpring…) are only
 * named, for the advice. Then the mistakes found: images and buttons without a useful text alternative (none, or the
 * name of a file or of a shape, that a screen reader would say), narrations without captions; and the PDF files and
 * documents of the package, as those of the course. The keyboard and the interactions stay for a person to confirm.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scorm_check {
    /** @var string[] The authoring tools recognised, in the order they are looked for. */
    public const TOOLS = ['rise', 'storyline', 'captivate', 'ispring', 'html', 'unknown'];

    /** @var int Bytes of data read in a package at most: a larger package is read as far as this. */
    public const MAX_BYTES = 30 * 1024 * 1024;

    /** @var string A text alternative that says nothing: the name of a file, or the default name of a shape. */
    protected const MEANINGLESS = '/(\.(png|jpe?g|gif|svg|webp|swf|bmp)$)|^(rectangle|rounded rectangle|groupe?|group|polygone?|'
        . 'polygon|ellipse|oval|ovale|image|picture|forme|shape|zone de texte|text box|freeform|forme libre|line|ligne|'
        . 'connecteur|connector|cercle|circle|triangle)( ?\d+)?$/i';

    /**
     * Reads a package.
     *
     * @param \stored_file[] $files Its files, by path from its root (« html5/data/js/data.js »).
     * @return array tool, issues (scormimages, scormaudio and those of its documents, by count), checks (key, kind, label)
     */
    public static function read(array $files): array {
        $result = ['tool' => self::tool($files), 'issues' => [], 'checks' => []];
        $budget = self::MAX_BYTES;
        if ($result['tool'] === 'rise') {
            self::read_rise($files, $result, $budget);
        }
        // Storyline, as a package or as blocks of a Rise course (each in a folder of its own).
        foreach (self::storyline_roots($files) as $root) {
            self::read_storyline($files, $root, $result, $budget);
        }
        if ($result['tool'] === 'html') {
            self::read_html($files, $result, $budget);
        }
        return $result;
    }

    /**
     * The files of the package of an activity SCORM or IMS content package, by path, as Moodle unzipped it.
     *
     * @param \context_module $context Context of the activity.
     * @param string $component mod_scorm or mod_imscp.
     * @return \stored_file[]
     */
    public static function files(\context_module $context, string $component = 'mod_scorm'): array {
        $files = [];
        // Any item: SCORM keeps its package in the item 0, an IMS content package in the item of its revision.
        $area = get_file_storage()->get_area_files($context->id, $component, 'content', false, 'filepath, filename', false);
        foreach ($area as $file) {
            $files[ltrim($file->get_filepath(), '/') . $file->get_filename()] = $file;
        }
        return $files;
    }

    /**
     * The authoring tool of a package.
     *
     * @param \stored_file[] $files Files by path.
     * @return string One of {@see TOOLS}.
     */
    public static function tool(array $files): string {
        $paths = array_keys($files);
        if (preg_grep('~^scormcontent/locales/[^/]+\.js$~', $paths)) {
            return 'rise';
        }
        if (isset($files['html5/data/js/data.js']) || isset($files['story.html'])) {
            return 'storyline';
        }
        if (preg_grep('~(^|/)(CPM\.js|captivate\.css)$~i', $paths) || preg_grep('~(^|/)assets/js/CPXHRLoader\.js$~', $paths)) {
            return 'captivate';
        }
        if (preg_grep('~(^|/)(data/player\.js|ispring[^/]*\.js)$~i', $paths)) {
            return 'ispring';
        }
        return preg_grep('~\.html?$~i', $paths) ? 'html' : 'unknown';
    }

    /**
     * The folders of the Storyline outputs of a package: its root, or the blocks of a Rise course.
     *
     * @param \stored_file[] $files Files by path.
     * @return string[] Folders, '' for the root.
     */
    protected static function storyline_roots(array $files): array {
        $roots = [];
        foreach (array_keys($files) as $path) {
            if (preg_match('~^(.*?)html5/data/js/data\.js$~', $path, $match)) {
                $roots[] = $match[1];
            }
        }
        return $roots;
    }

    /**
     * Reads the content of a file within the budget of the package.
     *
     * @param \stored_file $file File.
     * @param int $budget Bytes left to read, lowered.
     * @return string|null Its content, or null past the budget.
     */
    protected static function content(\stored_file $file, int &$budget): ?string {
        $size = (int) $file->get_filesize();
        if ($size > $budget) {
            return null;
        }
        $budget -= $size;
        return $file->get_content();
    }

    /**
     * Whether a text alternative says nothing.
     *
     * @param string $alt Text alternative.
     * @return bool
     */
    public static function meaningless(string $alt): bool {
        $alt = trim(preg_replace('/\s+/u', ' ', str_replace(['\\r\\n', '\\n'], ' ', $alt)));
        return $alt === '' || (bool) preg_match(self::MEANINGLESS, $alt);
    }

    /**
     * The data of a file of a Storyline output: window.globalProvideData('slide', '…JSON…').
     *
     * @param string $script Content of the file.
     * @return array|null kind and data, or null.
     */
    public static function storyline_data(string $script): ?array {
        if (!preg_match("/^\\s*window\\.globalProvideData\\('(\\w+)',\\s*'(.*)'\\);?\\s*$/s", $script, $match)) {
            return null;
        }
        // The literal of JavaScript between single quotes, made a string of JSON: its quotes escaped, its \' unescaped.
        $escape = function (array $m): string {
            if (!isset($m[1]) || $m[1] === '') {
                return '\\"';
            }
            return $m[1] === "'" ? "'" : '\\' . $m[1];
        };
        $literal = preg_replace_callback('/\\\\(.)|"/s', $escape, $match[2]);
        $json = json_decode('"' . $literal . '"');
        $data = is_string($json) ? json_decode($json, true) : null;
        return is_array($data) ? ['kind' => $match[1], 'data' => $data] : null;
    }

    /**
     * Reads the slides of a Storyline output: its images and buttons seen by the screen readers, and its sounds.
     *
     * @param \stored_file[] $files Files by path.
     * @param string $root Folder of the output.
     * @param array $result Result, completed.
     * @param int $budget Bytes left to read.
     */
    protected static function read_storyline(array $files, string $root, array &$result, int &$budget): void {
        $prefix = $root . 'html5/data/js/';
        $unnamed = [];
        $sounds = [];
        // The files of the assets (several assets may play the same sound file), from the data of the output.
        $assets = [];
        $content = isset($files[$prefix . 'data.js']) ? self::content($files[$prefix . 'data.js'], $budget) : null;
        $data = $content === null ? null : self::storyline_data($content);
        foreach ($data['data']['assetLib'] ?? [] as $asset) {
            if (isset($asset['id'], $asset['url'])) {
                $assets[(string) $asset['id']] = (string) $asset['url'];
            }
        }
        foreach ($files as $path => $file) {
            if (!str_starts_with($path, $prefix) || !str_ends_with($path, '.js')) {
                continue;
            }
            $content = self::content($file, $budget);
            $data = $content === null ? null : self::storyline_data($content);
            if (!$data || $data['kind'] !== 'slide') {
                continue;
            }
            $walk = function (array $node) use (&$walk, &$unnamed, &$sounds, $assets, $path): void {
                $type = $node['accType'] ?? null;
                if (in_array($type, ['image', 'button', 'checkbox', 'radio', 'hotspot'], true)) {
                    $state = $node['data']['accState'] ?? 0;
                    $alt = (string) ($node['data']['vectorData']['altText'] ?? '');
                    // Seen by the screen readers (not hidden from them) and named by nothing useful.
                    if ((int) $state === 0 && self::meaningless($alt)) {
                        // Once by name in the package (the same picture on several slides is one to fix), the states
                        // of a button (normal, hover…) as one; each object without any name.
                        $name = preg_replace('/(_over|_hover|_down|_visited)(?=\.\w+$)/i', '', $alt);
                        $unnamed[$alt !== '' ? $name : $path . '#' . ($node['id'] ?? count($unnamed))] = true;
                    }
                }
                // Each sound once (by its asset), wherever it is played: captioned if it is somewhere.
                if (($node['kind'] ?? '') === 'audio' && isset($node['assetId'])) {
                    $captioned = false;
                    foreach ($node as $name => $value) {
                        $captioned = $captioned || (stripos((string) $name, 'caption') !== false && !empty($value));
                    }
                    $asset = $assets[(string) $node['assetId']] ?? (string) $node['assetId'];
                    $sounds[$asset] = ($sounds[$asset] ?? false) || $captioned;
                }
                foreach ($node as $value) {
                    if (is_array($value)) {
                        $walk($value);
                    }
                }
            };
            $walk($data['data']);
        }
        self::add($result, 'scormimages', count($unnamed));
        self::add($result, 'scormaudio', count(array_filter($sounds, fn(bool $captioned): bool => !$captioned)));
    }

    /**
     * Reads a Rise course: its images (but those of decoration: cover, backgrounds, avatars of quotes), its sounds,
     * whose transcript is to confirm, its embedded contents, to confirm, and its texts.
     *
     * @param \stored_file[] $files Files by path.
     * @param array $result Result, completed.
     * @param int $budget Bytes left to read.
     */
    protected static function read_rise(array $files, array &$result, int &$budget): void {
        foreach ($files as $path => $file) {
            if (!preg_match('~^scormcontent/locales/[^/]+\.js$~', $path)) {
                continue;
            }
            $content = self::content($file, $budget);
            $pattern = '/__resolveJsonp\(\s*"[^"]*"\s*,\s*"([A-Za-z0-9+\/=]+)"\s*\)/';
            if ($content === null || !preg_match($pattern, $content, $match)) {
                continue;
            }
            $data = json_decode((string) base64_decode($match[1], true), true);
            if (!is_array($data['course'] ?? null)) {
                continue;
            }
            $html = '';
            // The images of decoration: the cover, the backgrounds, the avatars of the quotes (beside their name).
            $walk = function (array $node, bool $decoration) use (&$walk, &$result, &$html): void {
                foreach (['image' => 'scormimages'] as $media => $issue) {
                    $image = $node[$media] ?? null;
                    $decorative = $decoration || !empty($image['decorative']);
                    if (is_array($image) && !empty($image['key']) && !$decorative) {
                        $alt = (string) ($image['altText'] ?? $image['alt'] ?? $node['altText'] ?? $node['alt'] ?? '');
                        if (trim($alt) === '') {
                            self::add($result, $issue, 1);
                        }
                    }
                }
                if (is_array($node['audio'] ?? null) && !empty($node['audio']['key'])) {
                    $name = (string) ($node['audio']['originalUrl'] ?? $node['audio']['key']);
                    $result['checks'][] = ['key' => 'transcript:' . substr(sha1($name), 0, 16), 'kind' => 'transcript',
                        'label' => $name];
                }
                if (is_array($node['embed'] ?? null)) {
                    $src = (string) ($node['embed']['src'] ?? $node['embed']['url'] ?? '');
                    // Through a service of embedding (embed.ly), the address of the content itself.
                    parse_str((string) parse_url($src, PHP_URL_QUERY), $query);
                    $address = $src;
                    foreach (['url', 'src'] as $param) {
                        if (is_string($query[$param] ?? null)) {
                            $address = $query[$param];
                            break;
                        }
                    }
                    // Its site and its path, shortened: two contents of the same site (two Genially) told apart.
                    $host = preg_replace('/^www\./', '', (string) (parse_url($address, PHP_URL_HOST) ?: $address));
                    $label = $host . rtrim((string) parse_url($address, PHP_URL_PATH), '/');
                    $label = \core_text::strlen($label) > 48 ? \core_text::substr($label, 0, 47) . '…' : $label;
                    $result['checks'][] = ['key' => 'embedded:' . substr(sha1($address), 0, 16), 'kind' => 'embedded',
                        'label' => $label];
                }
                foreach (['heading', 'paragraph', 'description', 'title'] as $field) {
                    if (is_string($node[$field] ?? null) && str_contains($node[$field], '<')) {
                        $html .= $node[$field] . "\n";
                    }
                }
                foreach ($node as $childkey => $value) {
                    if (is_array($value)) {
                        $walk($value, $decoration || in_array($childkey, ['coverImage', 'background', 'avatar'], true));
                    }
                }
            };
            $walk($data['course'], false);
            // What the texts of the blocks hold, as a text of the course: its vague links, tables. Not the contrasts: Rise
            // shows them on backgrounds of its own, which a text alone does not tell.
            $found = course_accessibility::check_html($html)['issues'];
            foreach (['links', 'tables', 'moving'] as $issue) {
                self::add($result, $issue, (int) ($found[$issue] ?? 0));
            }
        }
        self::read_documents($files, 'scormcontent/assets/', $result, $budget);
    }

    /**
     * Reads a package of HTML pages: the pages it declares, as texts of the course.
     *
     * @param \stored_file[] $files Files by path.
     * @param array $result Result, completed.
     * @param int $budget Bytes left to read.
     */
    protected static function read_html(array $files, array &$result, int &$budget): void {
        $issues = [];
        foreach ($files as $path => $file) {
            if (!preg_match('~\.html?$~i', $path)) {
                continue;
            }
            $content = self::content($file, $budget);
            if ($content === null) {
                continue;
            }
            $body = preg_match('~<body[^>]*>(.*)</body>~is', $content, $match) ? $match[1] : $content;
            foreach (course_accessibility::check_html($body)['issues'] as $issue => $count) {
                $issues[$issue] = ($issues[$issue] ?? 0) + $count;
            }
        }
        // The images of the pages, as those of the course; the rest of the mistakes of a text.
        self::add($result, 'scormimages', (int) ($issues['images'] ?? 0) + (int) ($issues['imagelinks'] ?? 0));
        unset($issues['images'], $issues['imagelinks'], $issues['noheadings'], $issues['duplicateids']);
        foreach ($issues as $issue => $count) {
            self::add($result, $issue, (int) $count);
        }
        self::read_documents($files, '', $result, $budget);
    }

    /**
     * Reads the PDF files and the documents of an office suite of a package, as those of the course.
     *
     * @param \stored_file[] $files Files by path.
     * @param string $prefix Folder where they are.
     * @param array $result Result, completed.
     * @param int $budget Bytes left to read.
     */
    protected static function read_documents(array $files, string $prefix, array &$result, int &$budget): void {
        foreach ($files as $path => $file) {
            if (!str_starts_with($path, $prefix) || !document_check::kind($file->get_mimetype())) {
                continue;
            }
            $size = (int) $file->get_filesize();
            if ($size > $budget) {
                continue;
            }
            $budget -= $size;
            $found = document_check::check($file) ?? [];
            // A PDF file without structure (tags), as in the course.
            if ($file->get_mimetype() === 'application/pdf' && course_accessibility::pdf_tagged($file) === false) {
                $found['pdf'] = 1;
            }
            unset($found['doclang']);
            foreach (array_filter($found) as $issue => $count) {
                self::add($result, $issue, (int) $count);
            }
        }
    }

    /**
     * Counts a mistake.
     *
     * @param array $result Result.
     * @param string $issue Mistake.
     * @param int $count How many.
     */
    protected static function add(array &$result, string $issue, int $count): void {
        if ($count > 0) {
            $result['issues'][$issue] = ($result['issues'][$issue] ?? 0) + $count;
        }
    }
}
