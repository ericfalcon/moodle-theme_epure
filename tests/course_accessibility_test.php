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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use theme_epure\privacy\provider;

#[\PHPUnit\Framework\Attributes\CoversClass(course_accessibility::class)]
/**
 * Tests for the guide « Accessibility of the course ».
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\course_accessibility
 * @covers     \theme_epure\privacy\provider
 */
final class course_accessibility_test extends \core_privacy\tests\provider_testcase {
    /**
     * The mistakes of a text, with the rules of the check of the forms, without false alerts on what is right.
     */
    public function test_check_html(): void {
        $found = course_accessibility::check_html(
            '<h2>A</h2><h4>B</h4><img src="a.png"><img src="b.png" alt="">'
            . '<table><tr><td>1</td></tr></table><table role="presentation"><tr><td>1</td></tr></table>'
            . '<table><tr><th>H</th></tr></table>'
            . '<p><a href="#">ici</a></p><p>Le règlement est <a href="#">ici</a>.</p><a href="#"></a>'
            . '<a href="#"><img src="c.png" alt="Accueil"></a>'
            . '<iframe src="https://www.youtube.com/embed/abc"></iframe><iframe src="https://example.org" title="Carte"></iframe>'
            . '<video src="v.mp4"></video><video src="w.mp4"><track kind="captions" src="w.vtt"></video>'
            . '<audio src="https://example.org/a.mp3"></audio><a href="https://vimeo.com/1">La vidéo de sécurité</a>'
        );
        $this->assertSame(
            ['images' => 1, 'imagelinks' => 0, 'links' => 2, 'tables' => 1, 'headings' => 1, 'noheadings' => 0, 'frames' => 1,
                'videos' => 1],
            $found['issues']
        );
        $this->assertEqualsCanonicalizing(['captions', 'captions', 'transcript'], array_column($found['checks'], 'kind'));
        $this->assertContains('youtube.com', array_column($found['checks'], 'label'));
        $this->assertContains('vimeo.com', array_column($found['checks'], 'label'));

        $clean = course_accessibility::check_html('<h2>Titre</h2><h3>Sous-titre</h3><p>Texte <a href="#">du règlement</a>.</p>');
        $this->assertSame(0, array_sum($clean['issues']));
        $this->assertSame([], $clean['checks']);
    }

    /**
     * An image used as a link without a text alternative is one mistake of its own, not an image and a link; a long
     * text without any title is one too, as copied from another site.
     */
    public function test_check_html_imagelinks_noheadings(): void {
        $html = '<p><strong><a href="https://docs.moodle.org/4x/fr/Fichier:a.png"><img src="a.png" alt=""></a></strong></p>'
            . '<p><a href="https://docs.moodle.org/4x/fr/Fichier:b.png"><img src="b.png"></a></p>'
            . '<p><a href="https://example.org/plan"><img src="c.png" alt="Plan du site"></a></p>'
            . '<p>' . str_repeat('mot ', 450) . '</p>';
        $found = course_accessibility::check_html($html)['issues'];
        $this->assertSame(2, $found['imagelinks']);
        $this->assertSame(0, $found['images']);
        $this->assertSame(0, $found['links']);
        $this->assertSame(1, $found['noheadings']);

        // With titles, or short: no long text without titles.
        $long = course_accessibility::check_html('<h3>Partie</h3><p>' . str_repeat('mot ', 450) . '</p>')['issues'];
        $this->assertSame(0, $long['noheadings']);
        $this->assertSame(0, course_accessibility::check_html('<p>Court.</p>')['issues']['noheadings']);
    }

    /**
     * The documents of an office suite, with macros or not, are to confirm; the other files are not documents.
     */
    public function test_is_document(): void {
        foreach (['doc', 'docx', 'docm', 'xls', 'xlsx', 'xlsm', 'ppt', 'pptx', 'pptm', 'odt', 'ods', 'odp', 'rtf'] as $extension) {
            $this->assertTrue(course_accessibility::is_document(mimeinfo('type', 'a.' . $extension)), $extension);
        }
        foreach (['pdf', 'png', 'mp4', 'zip', 'txt'] as $extension) {
            $this->assertFalse(course_accessibility::is_document(mimeinfo('type', 'a.' . $extension)), $extension);
        }
    }

    /**
     * A PDF file has a structure when its root of tags is written plainly or in a compressed stream of objects.
     */
    public function test_pdf_content_tagged(): void {
        $plain = "%PDF-1.7\n1 0 obj << /Type /Catalog /StructTreeRoot 2 0 R /MarkInfo << /Marked true >> >> endobj\n%%EOF";
        $this->assertTrue(course_accessibility::pdf_content_tagged($plain));

        $objects = gzcompress('1 0 << /Type /Catalog /StructTreeRoot 2 0 R >>');
        $compressed = "%PDF-1.5\n5 0 obj << /Type /ObjStm /N 1 /First 4 /Filter /FlateDecode /Length " . strlen($objects)
            . " >>\nstream\n" . $objects . "\nendstream\nendobj\n%%EOF";
        $this->assertTrue(course_accessibility::pdf_content_tagged($compressed));

        $untagged = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n%%EOF";
        $this->assertFalse(course_accessibility::pdf_content_tagged($untagged));
        $this->assertNull(course_accessibility::pdf_content_tagged("%PDF-1.4\ntrailer << /Encrypt 5 0 R >>\n%%EOF"));
        $this->assertNull(course_accessibility::pdf_content_tagged('not a PDF'));
    }

    /**
     * The images of an H5P content without a text alternative, but the decorative ones; its videos, to confirm.
     */
    public function test_read_h5p_content(): void {
        $part = ['type' => 'cm', 'id' => 1, 'section' => 0, 'issues' => [], 'checks' => []];
        course_accessibility::read_h5p_content($part, ['content' => [
            ['params' => ['file' => ['path' => 'a.png', 'mime' => 'image/png'], 'alt' => '']],
            ['params' => ['file' => ['path' => 'b.png', 'mime' => 'image/png'], 'alt' => 'Un casque']],
            ['params' => ['file' => ['path' => 'c.png', 'mime' => 'image/png'], 'decorative' => true]],
            ['params' => ['files' => [['path' => 'v.mp4', 'mime' => 'video/mp4']]]],
        ]]);
        $this->assertSame(['h5pimages' => 1], $part['issues']);
        $this->assertSame(['captions'], array_column($part['checks'], 'kind'));
    }

    /**
     * A course with Épure and the guide on.
     *
     * @return array Course, teacher and activities.
     */
    protected function course(): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('courseaccessibility', 1, 'theme_epure');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['summary' => '<p>Bienvenue.</p>', 'summaryformat' => FORMAT_HTML]);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $page = $generator->create_module('page', ['course' => $course->id, 'name' => 'Document 2',
            'content' => '<img src="a.png"><iframe src="https://www.youtube.com/embed/abc"></iframe>']);
        for ($i = 0; $i < 2; $i++) {
            $generator->create_module('page', ['course' => $course->id, 'name' => 'Consignes de sécurité',
                'content' => '<p>Bien.</p>']);
        }
        $quiz = $generator->create_module('quiz', ['course' => $course->id, 'name' => 'Évaluation finale', 'timelimit' => 1800]);
        $resource = $generator->create_module('resource', ['course' => $course->id, 'name' => 'Fiche de sécurité']);
        $fs = get_file_storage();
        $context = \context_module::instance($resource->cmid);
        $fs->delete_area_files($context->id, 'mod_resource', 'content');
        $fs->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_resource', 'filearea' => 'content',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'fiche.pdf'], "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF");
        course_accessibility::forget((int) $course->id);
        return [get_course($course->id), $teacher, $page, $quiz, $resource];
    }

    /**
     * Each part of a course is read: the texts, the names, the files, the timed quizzes; and the figures of the course.
     */
    public function test_analyse(): void {
        [$course, , $page, $quiz, $resource] = $this->course();
        $parts = array_column(array_filter(
            course_accessibility::analyse($course),
            fn($part) => $part['type'] === 'cm'
        ), null, 'id');

        $this->assertSame(['images' => 1, 'frames' => 1, 'name' => 1], $parts[$page->cmid]['issues']);
        $this->assertSame(['captions'], array_column($parts[$page->cmid]['checks'], 'kind'));
        $this->assertSame(['deadlines'], array_column($parts[$quiz->cmid]['checks'], 'kind'));
        $this->assertSame(['pdf' => 1, 'pdflang' => 1], $parts[$resource->cmid]['issues']);
        $duplicates = array_filter($parts, fn($part) => isset($part['issues']['duplicate']));
        $this->assertCount(2, $duplicates);

        $figures = course_accessibility::figures(course_accessibility::analyse($course), []);
        // Images, frame, name, two duplicates, PDF untagged and without a language;
        // the video, the time of the quiz, the instructions and the colours.
        $this->assertSame(7, $figures['issues']);
        $this->assertSame(4, $figures['toconfirm']);
        $this->assertSame(['issues' => 3, 'toconfirm' => 1], $figures['cms'][$page->cmid]);
    }

    /**
     * The mistakes of a book are given chapter by chapter, with a link to change each.
     */
    public function test_places(): void {
        [$course] = $this->course();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id, 'name' => 'Créer un devoir']);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $generator->create_chapter(['bookid' => $book->id, 'title' => 'Présentation', 'content' => '<p>Bien.</p>']);
        $chapter = $generator->create_chapter(['bookid' => $book->id, 'title' => 'Paramètres du devoir',
            'content' => '<p><a href="https://docs.moodle.org/x"><img src="a.png" alt=""></a></p>']);
        course_accessibility::forget((int) $course->id);

        $part = array_column(course_accessibility::analyse($course), null, 'id')[$book->cmid];
        $this->assertCount(1, $part['places']);
        $this->assertSame('chapter', $part['places'][0]['kind']);
        $this->assertSame('Paramètres du devoir', $part['places'][0]['name']);
        $this->assertSame(['imagelinks' => 1], $part['places'][0]['issues']);
        $this->assertStringContainsString('id=' . $chapter->id, $part['places'][0]['url']);

        $data = course_accessibility::export($course, $GLOBALS['PAGE']->get_renderer('core'));
        $parts = array_merge(...array_column($data['sections'], 'parts'));
        $exported = array_column($parts, null, 'id')['epure-a11y-cm-' . $book->cmid];
        $this->assertSame(
            get_string('courseaccessibility_place_chapter', 'theme_epure', 'Paramètres du devoir'),
            $exported['places'][0]['label']
        );
        // Each mistake has the link of its chapter: no link to the settings of the book.
        $this->assertSame([], $exported['links']);
        $this->assertTrue($exported['ok']);
    }

    /**
     * The confirmations are saved for all the teachers, unchecked only where they were shown; the guide is for the
     * teachers of the course, when it is on.
     */
    public function test_save_and_edit_mode(): void {
        global $DB;
        [$course, $teacher, $page, $quiz] = $this->course();
        $this->setUser($teacher);
        $context = \context_course::instance($course->id);
        $this->assertTrue(course_accessibility::can_use($context));

        course_accessibility::save($course, (int) $teacher->id, [$quiz->cmid . '|deadlines', '0|colour', '9|invented'], []);
        $this->assertSame(2, $DB->count_records(course_accessibility::TABLE, ['courseid' => $course->id]));

        // Unchecked, but not shown checked (another teacher confirmed it meanwhile): it stays.
        course_accessibility::save($course, (int) $teacher->id, [], ['0|colour']);
        $this->assertTrue($DB->record_exists(course_accessibility::TABLE, ['checkkey' => 'deadlines']));
        $this->assertFalse($DB->record_exists(course_accessibility::TABLE, ['checkkey' => 'colour']));

        $editmode = course_accessibility::edit_mode($course);
        $this->assertSame(
            get_string('courseaccessibility_issuesn', 'theme_epure', 7) . ', '
                . get_string('courseaccessibility_toconfirmn', 'theme_epure', 3),
            $editmode['text']
        );
        $cms = array_column($editmode['cms'], null, 'cmid');
        $this->assertArrayNotHasKey($quiz->cmid, $cms);
        $this->assertSame(get_string('courseaccessibility_pointsn', 'theme_epure', 4), $cms[$page->cmid]['text']);

        $data = course_accessibility::export($course, $GLOBALS['PAGE']->get_renderer('core'));
        $this->assertSame($editmode['text'], $data['summary']);
        $this->assertNotEmpty($data['sections']);

        // Off for the site: no guide.
        set_config('courseaccessibility', 0, 'theme_epure');
        $this->assertFalse(course_accessibility::can_use($context));
        $this->assertNull(course_accessibility::edit_mode($course));

        // A learner never.
        set_config('courseaccessibility', 1, 'theme_epure');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));
        $this->assertFalse(course_accessibility::can_use($context));
    }

    /**
     * What is read is kept until the course changes.
     */
    public function test_cache(): void {
        [$course] = $this->course();
        $before = course_accessibility::figures(course_accessibility::analyse($course), []);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'name' => 'Plan du site',
            'content' => '<img src="plan.png">']);
        $after = course_accessibility::figures(course_accessibility::analyse($course), []);
        $this->assertSame($before['issues'] + 1, $after['issues']);
    }

    /**
     * The confirmations of a teacher are described, exported and deleted with the privacy API, and with the activity,
     * the course or the user.
     */
    public function test_privacy(): void {
        global $DB;
        [$course, $teacher, , $quiz] = $this->course();
        $other = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        course_accessibility::save($course, (int) $teacher->id, ['0|colour'], []);
        course_accessibility::save($course, (int) $other->id, [$quiz->cmid . '|deadlines'], []);
        $context = \context_course::instance($course->id);

        $contexts = array_map('intval', provider::get_contexts_for_userid((int) $teacher->id)->get_contextids());
        $this->assertContains((int) $context->id, $contexts);
        $userlist = new userlist($context, 'theme_epure');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$teacher->id, $other->id], $userlist->get_userids());

        provider::export_user_data(new approved_contextlist($teacher, 'theme_epure', [$context->id]));
        $exported = writer::with_context($context)->get_data([get_string('courseaccessibility', 'theme_epure')]);
        $this->assertSame('colour', $exported->confirmations[0]['point']);

        provider::delete_data_for_users(new approved_userlist($context, 'theme_epure', [$teacher->id]));
        $this->assertFalse($DB->record_exists(course_accessibility::TABLE, ['userid' => $teacher->id]));
        $this->assertTrue($DB->record_exists(course_accessibility::TABLE, ['userid' => $other->id]));

        // The activity deleted: its confirmations go with it.
        course_delete_module($quiz->cmid);
        $this->assertFalse($DB->record_exists(course_accessibility::TABLE, ['courseid' => $course->id]));
    }
}
