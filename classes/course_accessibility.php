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
 * Accessibility of a whole course, for its teachers: each part of the course (its presentation, the summary of each
 * section, each activity and resource) is read for what can be checked without a person, and lists what only a person
 * can confirm.
 *
 * Read without a person: in the texts (descriptions, pages, chapters of books, pages of lessons, questions of quizzes),
 * the same mistakes as the check of the forms (theme_epure/content_check: images without a text alternative, tables
 * without headers, links that do not say where they go, levels of titles skipped), and frames without a title, videos
 * without subtitles; the PDF files without structure (not tagged); the images of the H5P contents without a text
 * alternative; the names of activities that say nothing (« Document 2 ») or that two activities share.
 *
 * To confirm by a teacher: the subtitles of the videos that cannot be read (YouTube, Vimeo, video files), the
 * transcript of the audios, the accessibility of the documents of an office suite, the extra time the timed quizzes and
 * the assignments with a deadline can give, and for the whole course, the instructions and the use of colours. Their
 * confirmations are shared by the teachers of the course, with who confirmed and when.
 *
 * What is read is kept in the cache, by course, until the course changes; what a PDF file is, by its content.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_accessibility {
    /** @var string Table of the confirmations. */
    public const TABLE = 'theme_epure_a11ycheck';

    /** @var string[] Mistakes found without a person, in their order on the page. */
    public const ISSUES = ['images', 'imagelinks', 'links', 'tables', 'headings', 'noheadings', 'frames', 'videos', 'pdf',
        'pdfscanned', 'pdflang', 'docimages', 'docnoheadings', 'docheadings', 'doctables', 'slidetitles', 'sheetnames',
        'doctitle', 'h5pimages', 'name', 'duplicate'];

    /** @var int Words from which a text without any title is hard to go through with a screen reader. */
    protected const LONG_TEXT = 400;

    /** @var string[] Texts of links that do not say where they go (as theme_epure/content_check). */
    protected const VAGUE_LINKS = ['ici', 'cliquez ici', 'cliquer ici', 'ce lien', 'lien', 'en savoir plus', 'plus', 'suite',
        'lire la suite', 'here', 'click here', 'this link', 'link', 'more', 'read more', 'learn more'];

    /** @var string Blocks that give a link its context: the sentence, the item of a list, the cell, the caption. */
    protected const BLOCKS = 'self::p or self::li or self::td or self::th or self::dd or self::dt or self::figcaption'
        . ' or self::blockquote or self::caption or self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6';

    /** @var string Hosts of videos whose subtitles cannot be read from the course. */
    protected const VIDEO_HOSTS = '~^https?://([a-z0-9-]+\.)*(youtube\.com|youtu\.be|youtube-nocookie\.com|vimeo\.com'
        . '|dailymotion\.com|dai\.ly|peertube[a-z0-9.-]*|tube[a-z0-9.-]*)(/|$)~i';

    /** @var string Names of activities that say nothing of what they hold, in French and English, with a number or not. */
    protected const VAGUE_NAME = '~^(activit[ée]|activity|document|doc|fichier|file|ressource|resource|lien|link|page|url'
        . '|dossier|folder|test|quiz|devoir|assignment|forum|sans titre|untitled|nouveau|nouvelle|new|vid[ée]o|cours'
        . '|course|module|support|pdf)\s*(n°\s*)?[0-9]*$~iu';

    /** @var int Size above which a PDF file is not read: its structure is then to confirm. */
    protected const PDF_MAX_SIZE = 31457280;

    /**
     * Whether a user is one of the teachers who can use the guide of a course, when it is on.
     *
     * @param \context_course $context Context of the course.
     * @return bool
     */
    public static function can_use(\context_course $context): bool {
        return self::enabled() && (int) $context->instanceid !== (int) SITEID
            && has_capability('moodle/course:update', $context);
    }

    /**
     * Whether the guide is on: chosen in the settings of the site, or for the IOMAD company of the user. Off by
     * default: not all organisations must meet the criteria of the RGAA.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return get_config('theme_epure', 'courseaccessibility') !== false && company_style::enabled('courseaccessibility');
    }

    /**
     * Adds the guide to the menu of a course (« More »), for its teachers, with Épure.
     *
     * @param \navigation_node $navigation Node of the course.
     * @param \stdClass $course Course.
     * @param \context $context Context of the course.
     */
    public static function extend_navigation(\navigation_node $navigation, \stdClass $course, \context $context): void {
        if (
            !$context instanceof \context_course || during_initial_install() || !hook_callbacks::epure_page()
                || !self::can_use($context)
        ) {
            return;
        }
        $navigation->add(
            get_string('courseaccessibility', 'theme_epure'),
            self::url((int) $course->id),
            \navigation_node::TYPE_SETTING,
            null,
            'epureaccessibility',
            new \pix_icon('e/accessibility_checker', '')
        );
    }

    /**
     * Context of the template theme_epure/checklist: the reminder, by theme, each point with whether the guide of a
     * course finds it without a person, and a link back: to the guide of the course it was opened from, for its
     * teachers, else to the dashboard, or to the home page for a visitor.
     *
     * @param int $courseid Course whose guide opened the reminder, 0 for none.
     * @return array
     */
    public static function checklist(int $courseid = 0): array {
        $themes = [
            'texts' => ['headings' => true, 'instructions' => false, 'plain' => false],
            'images' => ['alt' => true, 'colour' => false],
            'links' => ['links' => true, 'frames' => true],
            'tables' => ['headers' => true, 'layout' => false],
            'media' => ['captions' => true, 'transcript' => false, 'autoplay' => false],
            'documents' => ['pdf' => true, 'office' => true, 'names' => true, 'time' => false],
        ];
        global $DB;
        $data = ['themes' => []];
        $course = $courseid ? $DB->get_record('course', ['id' => $courseid]) : null;
        if ($course && (int) $course->id !== (int) SITEID && self::can_use(\context_course::instance($course->id))) {
            $data['back'] = ['url' => self::url((int) $course->id)->out(false), 'text' => get_string(
                'checklist_backguide',
                'theme_epure',
                format_string($course->fullname, true, ['context' => \context_course::instance($course->id)])
            )];
        } else if (isloggedin() && !isguestuser()) {
            $data['back'] = ['url' => (new \moodle_url('/my/'))->out(false), 'text' => get_string(
                'checklist_backdashboard',
                'theme_epure'
            )];
        } else {
            $data['back'] = ['url' => (new \moodle_url('/'))->out(false), 'text' => get_string(
                'checklist_backhome',
                'theme_epure'
            )];
        }
        foreach ($themes as $theme => $points) {
            $items = [];
            foreach ($points as $point => $auto) {
                $items[] = [
                    'id' => 'epure-checklist-' . $point,
                    'text' => get_string('checklist_' . $point, 'theme_epure'),
                    'auto' => $auto,
                ];
            }
            $data['themes'][] = ['title' => get_string('checklist_theme_' . $theme, 'theme_epure'), 'items' => $items];
        }
        return $data;
    }

    /**
     * Address of the guide of a course, at a part of it or not.
     *
     * @param int $courseid Course.
     * @param int|null $cmid Activity to go to.
     * @return \moodle_url
     */
    public static function url(int $courseid, ?int $cmid = null): \moodle_url {
        $url = new \moodle_url('/theme/epure/courseaccessibility.php', ['id' => $courseid]);
        if ($cmid) {
            $url->set_anchor('epure-a11y-cm-' . $cmid);
        }
        return $url;
    }

    /**
     * What is read of a course without a person, from the cache or read again: its parts, in the order of the course.
     *
     * Each part: type (course, section or cm), id (0, the id of the section or of the activity), section (number of
     * its section), issues (count by mistake of self::ISSUES), checks (what to confirm: key, kind, label).
     *
     * @param \stdClass $course Course.
     * @return array
     */
    public static function analyse(\stdClass $course): array {
        $cache = \cache::make('theme_epure', 'courseaccessibility');
        $parts = $cache->get((int) $course->id);
        if ($parts === false) {
            $parts = self::read($course);
            $cache->set((int) $course->id, $parts);
        }
        return $parts;
    }

    /**
     * Forgets what was read of a course, which changed.
     *
     * @param int $courseid Course.
     */
    public static function forget(int $courseid): void {
        if ($courseid > 0) {
            \cache::make('theme_epure', 'courseaccessibility')->delete($courseid);
        }
    }

    /**
     * Reads a course.
     *
     * @param \stdClass $course Course.
     * @return array Parts, as {@see analyse()}.
     */
    protected static function read(\stdClass $course): array {
        global $DB;
        $modinfo = get_fast_modinfo($course, -1);
        $coursecontext = \context_course::instance($course->id);

        // The course, with what is to confirm for all of it.
        $parts = ['course' => self::part('course', 0, 0)];
        self::add_html($parts['course'], (string) $course->summary, (int) ($course->summaryformat ?? FORMAT_HTML));
        $parts['course']['checks'][] = ['key' => 'instructions', 'kind' => 'instructions', 'label' => ''];
        $parts['course']['checks'][] = ['key' => 'colour', 'kind' => 'colour', 'label' => ''];

        // The sections, each followed by its activities.
        $cms = [];
        $sectionparts = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $key = 'section_' . $section->id;
            $parts[$key] = self::part('section', (int) $section->id, (int) $section->section);
            self::add_html($parts[$key], (string) $section->summary, (int) $section->summaryformat);
            $sectionparts[(int) $section->id] = $key;
            foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                // Deleted, or never shown on the course page (the question banks of Moodle 5, whose questions are read
                // with the quizzes that use them).
                $display = !defined('FEATURE_CAN_DISPLAY') || plugin_supports('mod', $cm->modname, FEATURE_CAN_DISPLAY, true);
                if ($cm->deletioninprogress || !$display) {
                    continue;
                }
                $parts['cm_' . $cm->id] = self::part('cm', (int) $cm->id, (int) $section->section);
                $cms[(int) $cm->id] = $cm;
            }
        }

        self::read_texts($parts, $cms);
        self::read_names($parts, $cms);
        self::read_files($parts, $cms, $coursecontext, $sectionparts);
        self::read_h5p($parts, $cms);

        // Without what was only useful to read.
        foreach ($parts as &$part) {
            $part['checks'] = array_values(array_column($part['checks'], null, 'key'));
        }
        unset($part);
        return array_values($parts);
    }

    /**
     * A part of the course, before it is read.
     *
     * @param string $type course, section or cm.
     * @param int $id Id of the section or of the activity, 0 for the course.
     * @param int $section Number of its section.
     * @return array
     */
    protected static function part(string $type, int $id, int $section): array {
        return ['type' => $type, 'id' => $id, 'section' => $section, 'issues' => [], 'checks' => [], 'places' => []];
    }

    /**
     * Adds what a text of a part holds.
     *
     * @param array $part Part.
     * @param string $html Text.
     * @param int $format Format of the text: the plain texts are not read, Markdown is read once made HTML.
     * @param array|null $place Where the text is, in an activity of several texts: kind (chapter, page, question),
     *     name and address to change it.
     */
    protected static function add_html(array &$part, string $html, int $format = FORMAT_HTML, ?array $place = null): void {
        // Plain text has no structure to read; Markdown has (titles, links, images, tables): read once made HTML, as
        // Moodle shows it.
        if (trim($html) === '' || $format === (int) FORMAT_PLAIN) {
            return;
        }
        if ($format === (int) FORMAT_MARKDOWN) {
            // An image written ![](image.png) has an empty alternative, which Markdown cannot mean as decorative: an
            // image without a text alternative.
            $html = (string) preg_replace('~(<img\b[^>]*?)\s+alt=""~i', '$1', markdown_to_html($html));
        }
        $found = self::check_html($html);
        foreach ($found['issues'] as $issue => $count) {
            self::add_issue($part, $issue, $count);
        }
        foreach ($found['checks'] as $check) {
            $part['checks'][] = $check;
        }
        // Where the mistakes are, in an activity of several texts (a chapter, a page of a lesson, a question).
        $issues = array_filter($found['issues']);
        if ($place !== null && $issues) {
            $part['places'][] = $place + ['issues' => $issues];
        }
    }

    /**
     * Adds a mistake found in a part.
     *
     * @param array $part Part.
     * @param string $issue Mistake, of self::ISSUES.
     * @param int $count How many times.
     */
    protected static function add_issue(array &$part, string $issue, int $count = 1): void {
        if ($count > 0) {
            $part['issues'][$issue] = ($part['issues'][$issue] ?? 0) + $count;
        }
    }

    /**
     * What a text holds: the mistakes of theme_epure/content_check (with the same rules), frames without a title,
     * videos without subtitles; and the videos and audios whose subtitles or transcript a person has to confirm.
     *
     * @param string $html Text.
     * @return array issues (count by mistake), checks (key, kind and label of each).
     */
    public static function check_html(string $html): array {
        $found = ['images' => 0, 'imagelinks' => 0, 'links' => 0, 'tables' => 0, 'headings' => 0, 'noheadings' => 0,
            'frames' => 0, 'videos' => 0];
        $checks = [];
        if (trim($html) === '') {
            return ['issues' => $found, 'checks' => []];
        }
        $doc = new \DOMDocument();
        $errors = libxml_use_internal_errors(true);
        $doc->loadHTML('<html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($errors);
        $xpath = new \DOMXPath($doc);
        $text = fn(\DOMNode $node): string => trim(preg_replace('/\s+/u', ' ', $node->textContent));

        // A link made only of images without a text alternative: a screen reader says « link », without where it
        // leads (WCAG 2.4.4, RGAA 6.2). Its images are counted with it, not as images.
        $imagelink = fn(\DOMElement $link): bool => $xpath->query('.//img', $link)->length && $text($link) === ''
            && trim($link->getAttribute('aria-label')) === '' && !$xpath->query('.//img[@alt and @alt != ""]', $link)->length;
        // An image without alt; alt="" marks a decorative one (WCAG H67).
        foreach ($xpath->query('//img[not(@alt)]') as $img) {
            $link = $xpath->query('ancestor::a[@href][1]', $img)->item(0);
            if (!$link || !$imagelink($link)) {
                $found['images']++;
            }
        }
        // A table without any header, but a table of layout.
        foreach ($xpath->query('//table') as $table) {
            $role = strtolower(trim($table->getAttribute('role')));
            if (!in_array($role, ['presentation', 'none'], true) && !$xpath->query('.//th', $table)->length) {
                $found['tables']++;
            }
        }
        // A link without text, or whose short text is alone in its block (WCAG 2.4.4, RGAA 6.1).
        foreach ($xpath->query('//a[@href]') as $link) {
            if ($imagelink($link)) {
                $found['imagelinks']++;
                continue;
            }
            $label = $link->getAttribute('aria-label') !== '' ? trim($link->getAttribute('aria-label')) : $text($link);
            $label = preg_replace('/[.…:!]+$/u', '', \core_text::strtolower($label));
            if ($label === '' && !$xpath->query('.//img[@alt and @alt != ""]', $link)->length) {
                $found['links']++;
                continue;
            }
            $block = $xpath->query('ancestor::*[' . self::BLOCKS . '][1]', $link)->item(0);
            if (in_array($label, self::VAGUE_LINKS, true) && (!$block || $text($block) === $text($link))) {
                $found['links']++;
            }
            // A video hosted elsewhere, which the media filter shows as a player.
            $href = trim($link->getAttribute('href'));
            if (preg_match(self::VIDEO_HOSTS, $href)) {
                $checks[] = self::media_check('captions', $href);
            }
        }
        // A level of title skipped.
        $previous = 0;
        $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');
        foreach ($headings as $heading) {
            $level = (int) substr($heading->nodeName, 1);
            if ($previous && $level > $previous + 1) {
                $found['headings']++;
            }
            $previous = $level;
        }
        // A long text without any title (WCAG 1.3.1, RGAA 9.1): its sections cannot be reached one by one.
        $body = $xpath->query('//body')->item(0);
        if (!$headings->length && $body && count(preg_split('/\s+/u', $text($body), -1, PREG_SPLIT_NO_EMPTY)) >= self::LONG_TEXT) {
            $found['noheadings'] = 1;
        }
        // A frame without a title (WCAG 4.1.2, RGAA 2.1); a video hosted elsewhere in it.
        foreach ($xpath->query('//iframe') as $frame) {
            if (trim($frame->getAttribute('title')) === '') {
                $found['frames']++;
            }
            $src = trim($frame->getAttribute('src'));
            if (preg_match(self::VIDEO_HOSTS, $src)) {
                $checks[] = self::media_check('captions', $src);
            }
        }
        // A video without a track of subtitles (WCAG 1.2.2); an audio, whose transcript is to confirm.
        foreach ($xpath->query('//video') as $video) {
            if (!$xpath->query('.//track[@kind="captions" or @kind="subtitles"]', $video)->length) {
                $found['videos']++;
            }
        }
        foreach ($xpath->query('//audio') as $audio) {
            $source = $audio->getAttribute('src') ?: ($xpath->query('.//source/@src', $audio)->item(0)?->nodeValue ?? '');
            $checks[] = self::media_check('transcript', (string) $source);
        }
        return ['issues' => $found, 'checks' => $checks];
    }

    /**
     * What to confirm of a video or an audio, with an address.
     *
     * @param string $kind captions or transcript.
     * @param string $address Address of the media.
     * @return array
     */
    protected static function media_check(string $kind, string $address): array {
        $label = rawurldecode((string) (parse_url($address, PHP_URL_HOST) ?: basename((string) parse_url($address, PHP_URL_PATH))));
        $label = preg_replace('/^www\./', '', $label);
        return ['key' => $kind . ':' . substr(sha1($address), 0, 16), 'kind' => $kind, 'label' => $label];
    }

    /**
     * Reads the texts of the activities: descriptions, contents of the pages, chapters of the books, pages of the
     * lessons, questions of the quizzes; and the quizzes and assignments with a time limit or a deadline.
     *
     * @param array $parts Parts.
     * @param \cm_info[] $cms Activities.
     */
    protected static function read_texts(array &$parts, array $cms): void {
        global $DB;
        $bymod = [];
        foreach ($cms as $cm) {
            $bymod[$cm->modname][(int) $cm->instance] = $cm;
        }
        foreach ($bymod as $modname => $instances) {
            // The module of another plugin may keep its activities in a table of another name: its record is then
            // missing, its description too, but its content is still asked of it.
            $records = $DB->get_manager()->table_exists($modname)
                ? $DB->get_records_list($modname, 'id', array_keys($instances)) : [];
            foreach ($instances as $instance => $cm) {
                $record = $records[$instance] ?? null;
                $part = &$parts['cm_' . $cm->id];
                if ($record && isset($record->intro)) {
                    self::add_html($part, (string) $record->intro, (int) ($record->introformat ?? FORMAT_HTML), [
                        'kind' => 'intro',
                        'name' => '',
                        'url' => (new \moodle_url('/course/modedit.php', ['update' => $cm->id]))->out(false),
                    ]);
                }
                // The texts the teachers write in the activity, where the module keeps them (content_sources).
                foreach (content_sources::texts($cm, $record) as $text) {
                    self::add_html($part, $text['text'], $text['format'], [
                        'kind' => $text['kind'],
                        'name' => $text['name'],
                        'url' => $text['url'],
                    ]);
                }
                // What Épure cannot read: the content of a package or an external tool, the content of a module of
                // another plugin that does not give it. A point to confirm by a person.
                $coverage = content_sources::coverage($modname);
                if (in_array($coverage, ['external', 'unread', 'visual'], true)) {
                    $part['checks'][] = ['key' => $coverage, 'kind' => $coverage, 'label' => (string) $cm->name];
                }
                if (!$record) {
                    unset($part);
                    continue;
                }
                switch ($modname) {
                    case 'assign':
                        if (!empty($record->duedate) || !empty($record->cutoffdate)) {
                            $part['checks'][] = ['key' => 'deadlines', 'kind' => 'deadlines', 'label' => ''];
                        }
                        break;
                    case 'quiz':
                        if (!empty($record->timelimit) || !empty($record->timeclose)) {
                            $part['checks'][] = ['key' => 'deadlines', 'kind' => 'deadlines', 'label' => ''];
                        }
                        foreach (self::quiz_questions($record, $cm) as $question) {
                            self::add_html($part, (string) $question->questiontext, (int) $question->questiontextformat, [
                                'kind' => 'question',
                                'name' => format_string((string) ($question->name ?? '')),
                                'url' => (new \moodle_url('/question/bank/editquestion/question.php', [
                                    'id' => $question->questionid ?? $question->id,
                                    'cmid' => $cm->id,
                                ]))->out(false),
                            ]);
                        }
                        break;
                    case 'book':
                        $chapters = $DB->get_records(
                            'book_chapters',
                            ['bookid' => $record->id],
                            'pagenum',
                            'id, title, content, contentformat'
                        );
                        foreach ($chapters as $chapter) {
                            self::add_html($part, (string) $chapter->content, (int) $chapter->contentformat, [
                                'kind' => 'chapter',
                                'name' => format_string($chapter->title),
                                'url' => (new \moodle_url('/mod/book/edit.php', ['cmid' => $cm->id,
                                    'id' => $chapter->id]))->out(false),
                            ]);
                        }
                        break;
                    case 'lesson':
                        $pages = $DB->get_records(
                            'lesson_pages',
                            ['lessonid' => $record->id],
                            '',
                            'id, title, contents, contentsformat'
                        );
                        foreach ($pages as $page) {
                            self::add_html($part, (string) $page->contents, (int) $page->contentsformat, [
                                'kind' => 'lessonpage',
                                'name' => format_string($page->title),
                                'url' => (new \moodle_url('/mod/lesson/editpage.php', ['id' => $cm->id, 'pageid' => $page->id,
                                    'edit' => 1]))->out(false),
                            ]);
                        }
                        break;
                }
                unset($part);
            }
        }
    }

    /**
     * The questions of a quiz, with their text.
     *
     * @param \stdClass $quiz Quiz.
     * @param \cm_info $cm Its activity.
     * @return \stdClass[]
     */
    protected static function quiz_questions(\stdClass $quiz, \cm_info $cm): array {
        if (!class_exists(\mod_quiz\question\bank\qbank_helper::class)) {
            return [];
        }
        try {
            $slots = \mod_quiz\question\bank\qbank_helper::get_question_structure($quiz->id, \context_module::instance($cm->id));
        } catch (\Throwable $e) {
            // Expected: a quiz whose questions are missing (a question bank deleted); its other parts are still read.
            debugging($e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
        return array_filter($slots, fn($slot) => isset($slot->questiontext));
    }

    /**
     * Reads the names of the activities: a name that says nothing, a name that two activities share.
     *
     * @param array $parts Parts.
     * @param \cm_info[] $cms Activities.
     */
    protected static function read_names(array &$parts, array $cms): void {
        $names = [];
        foreach ($cms as $cm) {
            // An activity shown in the page only (a text and media area) has no name of its own.
            if (!$cm->has_view() && plugin_supports('mod', $cm->modname, FEATURE_NO_VIEW_LINK, false)) {
                continue;
            }
            $name = trim(preg_replace('/[\s\p{P}]+/u', ' ', \core_text::strtolower(strip_tags($cm->name))));
            if (preg_match(self::VAGUE_NAME, $name)) {
                self::add_issue($parts['cm_' . $cm->id], 'name');
            }
            $names[$name][] = (int) $cm->id;
        }
        foreach ($names as $name => $ids) {
            if ($name !== '' && count($ids) > 1) {
                foreach ($ids as $id) {
                    self::add_issue($parts['cm_' . $id], 'duplicate');
                }
            }
        }
    }

    /**
     * Reads the files that the teachers give in the course: PDF files without structure; documents of an office suite,
     * videos without a file of subtitles beside them, audios, to confirm.
     *
     * @param array $parts Parts.
     * @param \cm_info[] $cms Activities.
     * @param \context_course $coursecontext Context of the course.
     * @param array $sectionparts Keys of the parts of the sections, by id.
     */
    protected static function read_files(array &$parts, array $cms, \context_course $coursecontext, array $sectionparts): void {
        global $DB;
        $bycontext = [(int) $coursecontext->id => null];
        foreach ($cms as $cm) {
            $bycontext[(int) $cm->context->id] = 'cm_' . $cm->id;
        }
        [$insql, $params] = $DB->get_in_or_equal(array_keys($bycontext), SQL_PARAMS_NAMED);
        // The files of the teachers only: never those of the learners (posts, submissions, entries), nor the files of
        // the packages unzipped (SCORM, IMS).
        $files = $DB->get_records_sql(
            "SELECT id, contextid, component, filearea, itemid, filepath, filename, mimetype, contenthash, filesize
               FROM {files}
              WHERE contextid $insql AND filename <> '.'
                    AND ((component = 'course' AND filearea IN ('summary', 'section'))
                         OR (component LIKE 'mod\\_%' AND filearea IN ('intro', 'introattachment'))
                         OR (component IN ('mod_page', 'mod_resource', 'mod_folder') AND filearea = 'content')
                         OR (component = 'mod_book' AND filearea = 'chapter')
                         OR (component = 'mod_lesson' AND filearea = 'page_contents'))
           ORDER BY filepath, filename",
            $params
        );
        // The files of subtitles, by place: a video beside one has them.
        $subtitles = [];
        foreach ($files as $file) {
            if (preg_match('/\.(vtt|srt)$/i', $file->filename)) {
                $subtitles[self::place($file)] = true;
            }
        }
        $fs = get_file_storage();
        foreach ($files as $file) {
            if ($file->component === 'course') {
                $key = $file->filearea === 'section' ? ($sectionparts[(int) $file->itemid] ?? null) : 'course';
            } else {
                $key = $bycontext[(int) $file->contextid] ?? null;
            }
            if ($key === null || !isset($parts[$key])) {
                continue;
            }
            $mimetype = (string) $file->mimetype;
            $check = ['key' => '', 'kind' => '', 'label' => $file->filename];
            // What the documents and the PDF files hold, given file by file, with a link to change where they are.
            $found = [];
            if ($mimetype === 'application/pdf') {
                $stored = $fs->get_file_by_id($file->id);
                $tagged = self::pdf_tagged($stored);
                if ($tagged === false) {
                    $found['pdf'] = 1;
                } else if ($tagged === null) {
                    $check['kind'] = 'pdf';
                }
                $found += $stored ? (document_check::check($stored) ?? []) : [];
            } else if (self::is_document($mimetype)) {
                $check['kind'] = 'document';
                $stored = document_check::kind($mimetype) ? $fs->get_file_by_id($file->id) : false;
                $found = $stored ? (document_check::check($stored) ?? []) : [];
            }
            foreach ($found as $issue => $count) {
                self::add_issue($parts[$key], $issue, $count);
            }
            if ($found) {
                $parts[$key]['places'][] = ['kind' => 'file', 'name' => $file->filename, 'issues' => $found,
                    'url' => self::edit_url($parts[$key], $coursecontext)];
            } else if (str_starts_with($mimetype, 'video/') && empty($subtitles[self::place($file)])) {
                $check['kind'] = 'captions';
            } else if (str_starts_with($mimetype, 'audio/')) {
                $check['kind'] = 'transcript';
            }
            if ($check['kind'] !== '') {
                // By content: the file replaced by another is to confirm again.
                $check['key'] = $check['kind'] . ':' . substr($file->contenthash, 0, 16);
                $parts[$key]['checks'][] = $check;
            }
        }
    }

    /**
     * Address to change a part of the course: the settings of the course, of a section or of an activity.
     *
     * @param array $part Part.
     * @param \context_course $coursecontext Context of the course.
     * @return string
     */
    protected static function edit_url(array $part, \context_course $coursecontext): string {
        $url = match ($part['type']) {
            'cm' => new \moodle_url('/course/modedit.php', ['update' => $part['id']]),
            'section' => new \moodle_url('/course/editsection.php', ['id' => $part['id']]),
            default => new \moodle_url('/course/edit.php', ['id' => $coursecontext->instanceid]),
        };
        return $url->out(false);
    }

    /**
     * Place of a file: its area and its folder, with the files beside it.
     *
     * @param \stdClass $file Record of the file.
     * @return string
     */
    protected static function place(\stdClass $file): string {
        return implode('/', [$file->contextid, $file->component, $file->filearea, $file->itemid, $file->filepath]);
    }

    /**
     * Whether a type of file is a document of an office suite (Word, PowerPoint, Excel, LibreOffice…).
     *
     * @param string $mimetype Type.
     * @return bool
     */
    public static function is_document(string $mimetype): bool {
        return in_array($mimetype, ['application/msword', 'application/vnd.ms-powerpoint', 'application/vnd.ms-excel',
                'application/rtf', 'text/rtf'], true)
            || str_starts_with($mimetype, 'application/vnd.openxmlformats-officedocument.')
            || str_starts_with($mimetype, 'application/vnd.oasis.opendocument.')
            // With macros (.docm, .xlsm, .pptm) and their templates.
            || (bool) preg_match('~^application/vnd\.ms-(word|excel|powerpoint)\.~', $mimetype);
    }

    /**
     * Whether a PDF file has a structure (tagged), that a screen reader reads: kept by content.
     *
     * @param \stored_file|false $file File.
     * @return bool|null Null when it cannot be read: protected by a password, or too large.
     */
    public static function pdf_tagged($file): ?bool {
        if (!$file) {
            return null;
        }
        $cache = \cache::make('theme_epure', 'pdftagged');
        $known = $cache->get($file->get_contenthash());
        if ($known !== false) {
            return $known === 'unknown' ? null : $known === 'tagged';
        }
        $tagged = $file->get_filesize() > self::PDF_MAX_SIZE ? null : self::pdf_content_tagged($file->get_content());
        $cache->set($file->get_contenthash(), $tagged === null ? 'unknown' : ($tagged ? 'tagged' : 'untagged'));
        return $tagged;
    }

    /**
     * Whether the content of a PDF file has a structure: a root of the tree of its tags (StructTreeRoot), written
     * plainly or in a compressed stream of objects.
     *
     * @param string $pdf Content of the file.
     * @return bool|null Null when it cannot be read.
     */
    public static function pdf_content_tagged(string $pdf): ?bool {
        if (!str_starts_with(ltrim(substr($pdf, 0, 1024)), '%PDF-')) {
            return null;
        }
        if (str_contains($pdf, '/StructTreeRoot')) {
            return true;
        }
        // Encrypted: its streams cannot be read.
        if (preg_match('~/Encrypt[\s/<\d]~', $pdf)) {
            return null;
        }
        $budget = document_check::MAX_STREAM;
        $offset = 0;
        while (($position = strpos($pdf, '/ObjStm', $offset)) !== false) {
            $offset = $position + 7;
            if (!preg_match('~stream\r?\n~', $pdf, $match, PREG_OFFSET_CAPTURE, $offset)) {
                break;
            }
            $start = $match[0][1] + strlen($match[0][0]);
            $end = strpos($pdf, 'endstream', $start);
            if ($end === false) {
                break;
            }
            $offset = $end;
            // Uncompressed at most to what is left of the budget: a small stream can unfold into gigabytes.
            $objects = $budget > 0 ? @gzuncompress(substr($pdf, $start, $end - $start), $budget) : false;
            if ($objects !== false) {
                if (str_contains($objects, '/StructTreeRoot')) {
                    return true;
                }
                $budget -= strlen($objects);
            }
        }
        return false;
    }

    /**
     * Reads the H5P contents: the images without a text alternative, the videos whose subtitles are to confirm.
     *
     * @param array $parts Parts.
     * @param \cm_info[] $cms Activities.
     */
    protected static function read_h5p(array &$parts, array $cms): void {
        global $DB;
        $contexts = [];
        foreach ($cms as $cm) {
            if ($cm->modname === 'h5pactivity') {
                $contexts[(int) $cm->context->id] = 'cm_' . $cm->id;
            }
        }
        if (!$contexts || !$DB->get_manager()->table_exists('h5p')) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal(array_keys($contexts), SQL_PARAMS_NAMED);
        // The content is read once shown (deployed) the first time.
        $records = $DB->get_records_sql(
            "SELECT h.id, f.contextid, h.jsoncontent
               FROM {h5p} h
               JOIN {files} f ON f.pathnamehash = h.pathnamehash
              WHERE f.contextid $insql AND f.component = 'mod_h5pactivity' AND f.filearea = 'package'",
            $params
        );
        foreach ($records as $record) {
            $key = $contexts[(int) $record->contextid];
            $content = json_decode((string) $record->jsoncontent, true);
            if (is_array($content)) {
                self::read_h5p_content($parts[$key], $content);
            }
        }
    }

    /**
     * Reads the parameters of an H5P content, at all depths.
     *
     * @param array $part Part of the activity.
     * @param array $content Parameters.
     */
    public static function read_h5p_content(array &$part, array $content): void {
        $walk = function (array $node) use (&$walk, &$part): void {
            $mime = (string) ($node['file']['mime'] ?? '');
            if (str_starts_with($mime, 'image/') && trim((string) ($node['alt'] ?? '')) === '' && empty($node['decorative'])) {
                self::add_issue($part, 'h5pimages');
            }
            foreach ($node['files'] ?? [] as $file) {
                if (is_array($file) && str_starts_with((string) ($file['mime'] ?? ''), 'video/')) {
                    $path = (string) ($file['path'] ?? '');
                    $part['checks'][] = self::media_check('captions', $path) + ['h5p' => true];
                    break;
                }
            }
            foreach ($node as $value) {
                if (is_array($value)) {
                    $walk($value);
                }
            }
        };
        $walk($content);
    }

    /**
     * The confirmations of a course, by part and key: who and when.
     *
     * @param int $courseid Course.
     * @return array By « cmid|key ».
     */
    public static function confirmations(int $courseid): array {
        global $DB;
        $confirmations = [];
        foreach ($DB->get_records(self::TABLE, ['courseid' => $courseid]) as $record) {
            $confirmations[$record->cmid . '|' . $record->checkkey] = $record;
        }
        return $confirmations;
    }

    /**
     * The id of the activity a confirmation of a part belongs to: 0 for the course and its sections.
     *
     * @param array $part Part.
     * @return int
     */
    protected static function cmid(array $part): int {
        return $part['type'] === 'cm' ? (int) $part['id'] : 0;
    }

    /**
     * Figures of a course: points to fix, points to confirm, parts with nothing left, parts; and the same for each
     * activity.
     *
     * @param array $parts Parts, as {@see analyse()}.
     * @param array $confirmations Confirmations, as {@see confirmations()}.
     * @return array issues, toconfirm, done, total, cms (by id: issues, toconfirm).
     */
    public static function figures(array $parts, array $confirmations): array {
        $figures = ['issues' => 0, 'toconfirm' => 0, 'done' => 0, 'total' => 0, 'cms' => []];
        foreach ($parts as $part) {
            $issues = array_sum($part['issues']);
            $toconfirm = 0;
            foreach ($part['checks'] as $check) {
                if (!isset($confirmations[self::cmid($part) . '|' . $check['key']])) {
                    $toconfirm++;
                }
            }
            // A section without a summary has nothing to read: it is not counted.
            if ($part['type'] === 'section' && !$issues && !$part['checks']) {
                continue;
            }
            $figures['issues'] += $issues;
            $figures['toconfirm'] += $toconfirm;
            $figures['total']++;
            $figures['done'] += ($issues + $toconfirm) ? 0 : 1;
            if ($part['type'] === 'cm') {
                $figures['cms'][(int) $part['id']] = ['issues' => $issues, 'toconfirm' => $toconfirm];
            }
        }
        return $figures;
    }

    /**
     * Saves the confirmations of a form of the guide: each box checked is confirmed by the user, each box shown
     * checked and now unchecked is no longer; the others, confirmed by another teacher meanwhile, stay.
     *
     * @param \stdClass $course Course.
     * @param int $userid User who confirms.
     * @param string[] $checked Boxes checked, as « cmid|key ».
     * @param string[] $shown Boxes that were checked when the form was shown.
     */
    public static function save(\stdClass $course, int $userid, array $checked, array $shown): void {
        global $DB;
        $valid = [];
        foreach (self::analyse($course) as $part) {
            foreach ($part['checks'] as $check) {
                $valid[self::cmid($part) . '|' . $check['key']] = [self::cmid($part), $check['key']];
            }
        }
        $confirmations = self::confirmations((int) $course->id);
        $checked = array_flip($checked);
        foreach ($valid as $id => [$cmid, $key]) {
            if (isset($checked[$id]) && !isset($confirmations[$id])) {
                $DB->insert_record(self::TABLE, ['courseid' => $course->id, 'cmid' => $cmid, 'checkkey' => $key,
                    'userid' => $userid, 'timecreated' => time()]);
            } else if (!isset($checked[$id]) && isset($confirmations[$id]) && in_array($id, $shown, true)) {
                $DB->delete_records(self::TABLE, ['id' => $confirmations[$id]->id]);
            }
        }
    }

    /**
     * Context of the template theme_epure/course_accessibility.
     *
     * @param \stdClass $course Course.
     * @param \renderer_base $output Renderer.
     * @return array
     */
    public static function export(\stdClass $course, \renderer_base $output): array {
        global $DB;
        $parts = self::analyse($course);
        $confirmations = self::confirmations((int) $course->id);
        $figures = self::figures($parts, $confirmations);
        $modinfo = get_fast_modinfo($course, -1);
        $context = \context_course::instance($course->id);
        $format = course_get_format($course);
        $users = [];
        $userids = array_unique(array_map(fn($record) => (int) $record->userid, $confirmations));
        if ($userids) {
            $fields = 'id, ' . implode(', ', \core_user\fields::get_name_fields());
            $users = $DB->get_records_list('user', 'id', $userids, '', $fields);
        }

        $sections = [];
        $current = null;
        foreach ($parts as $part) {
            if ($part['type'] !== 'cm') {
                if ($part['type'] === 'section') {
                    $section = $modinfo->get_section_info_by_id($part['id']);
                    if (!$section) {
                        continue;
                    }
                    $current = count($sections);
                    $sections[$current] = [
                        'name' => $format->get_section_name($section),
                        'id' => 'epure-a11y-section-' . $section->id,
                        'parts' => [],
                    ];
                    if (!array_sum($part['issues']) && !$part['checks']) {
                        continue;
                    }
                    $name = get_string('courseaccessibility_sectionsummary', 'theme_epure');
                    $edit = new \moodle_url('/course/editsection.php', ['id' => $section->id]);
                } else {
                    $current = count($sections);
                    $sections[$current] = [
                        'name' => get_string('courseaccessibility_course', 'theme_epure'),
                        'id' => 'epure-a11y-course',
                        'parts' => [],
                    ];
                    $name = format_string($course->fullname, true, ['context' => $context]);
                    $edit = new \moodle_url('/course/edit.php', ['id' => $course->id]);
                }
                $sections[$current]['parts'][] = self::export_part(
                    $part,
                    $name,
                    '',
                    '',
                    [$edit],
                    $confirmations,
                    $users,
                    $output
                );
                continue;
            }
            $cm = $modinfo->get_cm($part['id']);
            $links = [new \moodle_url('/course/modedit.php', ['update' => $cm->id])];
            $content = ['quiz' => '/mod/quiz/edit.php', 'book' => '/mod/book/view.php', 'lesson' => '/mod/lesson/edit.php'];
            if (isset($content[$cm->modname])) {
                $links[] = new \moodle_url($content[$cm->modname], [$cm->modname === 'quiz' ? 'cmid' : 'id' => $cm->id]);
            }
            $sections[$current]['parts'][] = self::export_part(
                $part,
                $cm->get_formatted_name(),
                $cm->get_icon_url()->out(false),
                get_string('modulename', $cm->modname),
                $links,
                $confirmations,
                $users,
                $output,
                $cm
            );
        }
        $sections = array_values(array_filter($sections, fn($section) => $section['parts']));

        return [
            'reporttitle' => get_string('courseaccessibility_report', 'theme_epure', (object) [
                'course' => format_string($course->fullname, true, ['context' => $context]),
                'date' => userdate(time(), get_string('strftimedatefullshort', 'core_langconfig')),
            ]),
            'action' => self::url((int) $course->id)->out(false),
            'sesskey' => sesskey(),
            'courseurl' => course_get_url($course)->out(false),
            'checklisturl' => (new \moodle_url('/theme/epure/checklist.php', ['course' => $course->id]))->out(false),
            'summary' => self::summary_text($figures),
            'done' => get_string('courseaccessibility_done', 'theme_epure', (object) ['done' => $figures['done'],
                'total' => $figures['total']]),
            'percent' => $figures['total'] ? (int) round(100 * $figures['done'] / $figures['total']) : 100,
            'hastoconfirm' => $figures['toconfirm'] > 0 || array_filter($confirmations),
            'sections' => $sections,
        ];
    }

    /**
     * Context of a part of the course.
     *
     * @param array $part Part.
     * @param string $name Its name.
     * @param string $icon Address of its icon, for an activity.
     * @param string $type Type of activity.
     * @param \moodle_url[] $links Links to change it.
     * @param array $confirmations Confirmations.
     * @param array $users Users who confirmed.
     * @param \renderer_base $output Renderer.
     * @param \cm_info|null $cm Activity.
     * @return array
     */
    protected static function export_part(
        array $part,
        string $name,
        string $icon,
        string $type,
        array $links,
        array $confirmations,
        array $users,
        \renderer_base $output,
        ?\cm_info $cm = null
    ): array {
        $cmid = self::cmid($part);
        $issues = [];
        foreach (self::ISSUES as $issue) {
            if (!empty($part['issues'][$issue])) {
                $text = get_string('courseaccessibility_issue_' . $issue, 'theme_epure', $part['issues'][$issue]);
                // The first sentence says what is wrong, the rest how to fix it: the cause stands out.
                $sentences = preg_split('/(?<=[^.]\.)\s+/u', $text, 2);
                $issues[] = ['text' => $text, 'cause' => $sentences[0], 'advice' => $sentences[1] ?? ''];
            }
        }
        // Where they are, in an activity of several texts: each chapter, page or question with its mistakes, and a link
        // to change it.
        $places = [];
        foreach ($part['places'] ?? [] as $place) {
            $found = [];
            foreach (self::ISSUES as $issue) {
                if (!empty($place['issues'][$issue])) {
                    $found[] = get_string('courseaccessibility_short_' . $issue, 'theme_epure', $place['issues'][$issue]);
                }
            }
            $label = get_string('courseaccessibility_place_' . $place['kind'], 'theme_epure', $place['name']);
            $places[] = [
                'label' => $label,
                'sep' => get_string('labelsep', 'langconfig'),
                'issues' => implode(' · ', $found),
                'url' => $place['url'],
                'linktext' => get_string('courseaccessibility_editplace', 'theme_epure', $label),
            ];
        }
        $checks = [];
        $toconfirm = 0;
        foreach ($part['checks'] as $index => $check) {
            $id = $cmid . '|' . $check['key'];
            $confirmation = $confirmations[$id] ?? null;
            $toconfirm += $confirmation ? 0 : 1;
            $data = [
                'name' => $id,
                'id' => 'epure-a11y-check-' . $part['type'] . '-' . $part['id'] . '-' . $index,
                'label' => get_string('courseaccessibility_check_' . $check['kind'], 'theme_epure', s($check['label'])),
                'help' => get_string('courseaccessibility_check_' . $check['kind'] . '_help', 'theme_epure'),
                'checked' => (bool) $confirmation,
            ];
            if ($confirmation) {
                $user = $users[$confirmation->userid] ?? null;
                $data['confirmed'] = get_string('courseaccessibility_confirmedby', 'theme_epure', (object) [
                    'name' => $user ? fullname($user) : get_string('unknownuser'),
                    'date' => userdate($confirmation->timecreated, get_string('strftimedatefullshort', 'core_langconfig')),
                ]);
            }
            if ($check['kind'] === 'deadlines' && $cm && in_array($cm->modname, ['quiz', 'assign'], true)) {
                $data['link'] = (new \moodle_url('/mod/' . $cm->modname . '/overrides.php', ['cmid' => $cm->id,
                    'mode' => 'user']))->out(false);
                $data['linktext'] = get_string('courseaccessibility_overrides', 'theme_epure');
            }
            $checks[] = $data;
        }
        $count = array_sum($part['issues']);
        if (!$count && !$toconfirm) {
            $state = ['class' => 'ok', 'icon' => 'fa-check', 'text' => get_string('courseaccessibility_state_ok', 'theme_epure')];
        } else {
            $state = [
                'class' => $count ? 'issues' : 'toconfirm',
                'icon' => $count ? 'fa-triangle-exclamation' : 'fa-circle-question',
                'text' => self::state_text($count, $toconfirm),
            ];
        }
        // The links to change the activity, only for what its places do not already lead to: the mistakes found
        // in a chapter, a page or a question each have their own link, the settings have nothing to change.
        $covered = [];
        foreach ($part['places'] as $place) {
            foreach ($place['issues'] as $issue => $found) {
                $covered[$issue] = ($covered[$issue] ?? 0) + $found;
            }
        }
        $elsewhere = array_filter($part['issues'], fn($found, $issue) => $found > ($covered[$issue] ?? 0), ARRAY_FILTER_USE_BOTH);
        if ($part['places'] && !$elsewhere && !$toconfirm) {
            $links = [];
        }
        $editlinks = [];
        foreach ($links as $i => $link) {
            $editlinks[] = ['url' => $link->out(false), 'text' => get_string($i ? 'courseaccessibility_editcontent'
                : 'courseaccessibility_edit', 'theme_epure', $name)];
        }
        return [
            'id' => $part['type'] === 'cm' ? 'epure-a11y-cm-' . $part['id'] : 'epure-a11y-' . $part['type'] . '-' . $part['id'],
            'name' => $name,
            'icon' => $icon,
            'type' => $type,
            'state' => $state,
            'issues' => $issues,
            'hasissues' => (bool) $issues,
            'places' => $places,
            'hasplaces' => (bool) $places,
            'checks' => $checks,
            'haschecks' => (bool) $checks,
            'links' => $editlinks,
            // Nothing left, or each mistake with its own link: no link to change the whole activity.
            'ok' => $state['class'] === 'ok' || !$editlinks,
        ];
    }

    /**
     * What is left of a part, in words: « 2 points to fix, 1 to confirm ».
     *
     * @param int $issues Points to fix.
     * @param int $toconfirm Points to confirm.
     * @return string
     */
    public static function state_text(int $issues, int $toconfirm): string {
        $texts = [];
        if ($issues) {
            $texts[] = get_string('courseaccessibility_issues' . ($issues > 1 ? 'n' : '1'), 'theme_epure', $issues);
        }
        if ($toconfirm) {
            $texts[] = get_string('courseaccessibility_toconfirm' . ($toconfirm > 1 ? 'n' : '1'), 'theme_epure', $toconfirm);
        }
        return implode(', ', $texts);
    }

    /**
     * The figures of a course in a sentence.
     *
     * @param array $figures Figures, as {@see figures()}.
     * @return string
     */
    public static function summary_text(array $figures): string {
        if (!$figures['issues'] && !$figures['toconfirm']) {
            return get_string('courseaccessibility_nothing', 'theme_epure');
        }
        return self::state_text($figures['issues'], $figures['toconfirm']);
    }

    /**
     * What the course page in edit mode shows of the guide: the figures under the bar of the edit mode, and the points
     * of each activity beside its name.
     *
     * @param \stdClass $course Course.
     * @return array|null text, url, label, cms (cmid, text, url); null for who cannot use the guide.
     */
    public static function edit_mode(\stdClass $course): ?array {
        $context = \context_course::instance($course->id);
        if (!self::can_use($context)) {
            return null;
        }
        $figures = self::figures(self::analyse($course), self::confirmations((int) $course->id));
        $cms = [];
        foreach ($figures['cms'] as $cmid => $cm) {
            $count = $cm['issues'] + $cm['toconfirm'];
            if ($count) {
                $cms[] = [
                    'cmid' => $cmid,
                    'text' => get_string('courseaccessibility_points' . ($count > 1 ? 'n' : '1'), 'theme_epure', $count),
                    'url' => self::url((int) $course->id, $cmid)->out(false),
                ];
            }
        }
        return [
            'text' => self::summary_text($figures),
            'clean' => !$figures['issues'] && !$figures['toconfirm'],
            'url' => self::url((int) $course->id)->out(false),
            'cms' => $cms,
        ];
    }
}
