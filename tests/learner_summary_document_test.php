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

#[\PHPUnit\Framework\Attributes\CoversClass(learner_summary_document::class)]
/**
 * Tests for « My summary » as a PDF with a code of verification.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\learner_summary_document
 * @covers     \theme_epure\privacy\provider
 */
final class learner_summary_document_test extends \core_privacy\tests\provider_testcase {
    /**
     * A learner enrolled in a course, with Épure as the theme.
     *
     * @return \stdClass Learner.
     */
    protected function learner(): \stdClass {
        global $PAGE;
        $this->resetAfterTest();
        set_config('theme', 'epure');
        set_config('mysummary', 1, 'theme_epure');
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('epure');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Safety at work', 'enablecompletion' => 1]);
        $user = $generator->create_user(['firstname' => 'Lea', 'lastname' => 'Learner']);
        $generator->enrol_user($user->id, $course->id, 'student');
        return $user;
    }

    /**
     * A document keeps what the summary showed, with a new code of twelve characters of the alphabet, and its PDF
     * carries the code and the QR code.
     */
    public function test_issue(): void {
        global $PAGE;
        $user = $this->learner();
        $document = learner_summary_document::issue($PAGE->get_renderer('core'), (int) $user->id);

        $this->assertMatchesRegularExpression('/^[' . learner_summary_document::ALPHABET . ']{12}$/', $document->code);
        $this->assertSame(hash('sha256', $document->data), $document->contenthash);
        $data = json_decode($document->data, true);
        $this->assertSame('Lea Learner', $data['fullname']);
        $this->assertSame('Safety at work', $data['courses'][0]['name']);
        $this->assertArrayNotHasKey('url', $data['courses'][0]);

        // A second document has its own code.
        $other = learner_summary_document::issue($PAGE->get_renderer('core'), (int) $user->id);
        $this->assertNotSame($document->code, $other->code);

        $pdf = learner_summary_document::pdf($document);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringEndsWith('.pdf', learner_summary_document::filename($document));
        $this->assertStringContainsString(
            learner_summary_document::format_code($document->code),
            learner_summary_document::filename($document)
        );
    }

    /**
     * A code is found whatever its case, its spaces and hyphens; an unknown or malformed code finds nothing.
     */
    public function test_find(): void {
        global $PAGE;
        $user = $this->learner();
        learner_summary_document::issue($PAGE->get_renderer('core'), (int) $user->id, 'ABCDEFGHJKMN');

        $this->assertSame('ABCD-EFGH-JKMN', learner_summary_document::format_code('abcdefghjkmn'));
        foreach (['ABCDEFGHJKMN', 'abcd-efgh-jkmn', ' ABCD EFGH JKMN '] as $typed) {
            $this->assertNotNull(learner_summary_document::find($typed), $typed);
        }
        foreach (['ABCD-EFGH-JKMP', 'ABCD', '', 'ABCD-EFGH-JKMN-ABCD'] as $typed) {
            $this->assertNull(learner_summary_document::find($typed), $typed);
        }

        // What the page of verification shows: the document as issued.
        $data = learner_summary_document::export(learner_summary_document::find('ABCDEFGHJKMN'));
        $this->assertSame('ABCD-EFGH-JKMN', $data['code']);
        $this->assertSame('Lea Learner', $data['fullname']);
        $this->assertTrue($data['hascourses']);
        $this->assertStringContainsString('code=ABCD-EFGH-JKMN', learner_summary_document::verify_url('abcdefghjkmn')->out(false));
    }

    /**
     * The documents of a user are described, exported and deleted with the privacy API, and when the user is deleted.
     */
    public function test_privacy(): void {
        global $DB, $PAGE;
        $user = $this->learner();
        $other = $this->getDataGenerator()->create_user();
        learner_summary_document::issue($PAGE->get_renderer('core'), (int) $user->id, 'ABCDEFGHJKMN');
        learner_summary_document::issue($PAGE->get_renderer('core'), (int) $other->id, 'NMKJHGFEDCBA');
        $context = \context_user::instance($user->id);

        $contexts = provider::get_contexts_for_userid((int) $user->id)->get_contextids();
        $this->assertEquals([$context->id], array_values($contexts));
        $userlist = new userlist($context, 'theme_epure');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$user->id], $userlist->get_userids());

        provider::export_user_data(new approved_contextlist($user, 'theme_epure', [$context->id]));
        $exported = writer::with_context($context)->get_data([get_string('mysummary', 'theme_epure')]);
        $this->assertSame('ABCD-EFGH-JKMN', $exported->documents[0]['code']);

        provider::delete_data_for_users(new approved_userlist($context, 'theme_epure', [$user->id]));
        $this->assertFalse($DB->record_exists(learner_summary_document::TABLE, ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists(learner_summary_document::TABLE, ['userid' => $other->id]));

        // A user deleted: their documents go with them.
        delete_user($other);
        $this->assertFalse($DB->record_exists(learner_summary_document::TABLE, ['userid' => $other->id]));
    }

    /**
     * The learner chooses how long a document can be verified: three months, a year (by default) or without limit; an
     * other value is the default. A document expired is no longer valid, and the task erases its content.
     */
    public function test_validity(): void {
        global $PAGE, $DB;
        $user = $this->learner();
        $output = $PAGE->get_renderer('core');
        $now = time();

        $short = learner_summary_document::issue($output, (int) $user->id, null, 90);
        $this->assertEqualsWithDelta($now + 90 * DAYSECS, (int) $short->timeexpires, 5);
        $default = learner_summary_document::issue($output, (int) $user->id, null, 12);
        $this->assertEqualsWithDelta($now + 365 * DAYSECS, (int) $default->timeexpires, 5);
        $forever = learner_summary_document::issue($output, (int) $user->id, null, 0);
        $this->assertSame(0, (int) $forever->timeexpires);
        $this->assertSame(get_string('mysummary_validity_none', 'theme_epure'), learner_summary_document::until($forever));

        $this->assertSame('valid', learner_summary_document::state($short));
        $this->assertSame('expired', learner_summary_document::state($short, $now + 91 * DAYSECS));
        $this->assertSame('valid', learner_summary_document::state($forever, $now + 3650 * DAYSECS));

        // The task, a hundred days later: only the content of the document of three months is erased.
        $this->assertSame(1, learner_summary_document::purge_expired($now + 100 * DAYSECS));
        $this->assertSame('{}', $DB->get_field(learner_summary_document::TABLE, 'data', ['id' => $short->id]));
        $this->assertNotSame('{}', $DB->get_field(learner_summary_document::TABLE, 'data', ['id' => $default->id]));
        $this->assertSame(0, learner_summary_document::purge_expired($now + 100 * DAYSECS));
    }

    /**
     * The learner revokes a document (its content erased, its code kept) or deletes it (its code gone); never the
     * document of another learner. The page lists their documents, each with its state.
     */
    public function test_revoke_delete(): void {
        global $PAGE, $DB;
        $user = $this->learner();
        $other = $this->getDataGenerator()->create_user();
        $output = $PAGE->get_renderer('core');
        $first = learner_summary_document::issue($output, (int) $user->id);
        $second = learner_summary_document::issue($output, (int) $user->id);

        $this->assertFalse(learner_summary_document::revoke((int) $other->id, (int) $first->id));
        $this->assertTrue(learner_summary_document::revoke((int) $user->id, (int) $first->id));
        $this->assertFalse(learner_summary_document::revoke((int) $user->id, (int) $first->id));
        $revoked = learner_summary_document::find($first->code);
        $this->assertSame('revoked', learner_summary_document::state($revoked));
        $this->assertSame('{}', $revoked->data);

        $this->setUser($user);
        $page = learner_summary_document::export_documents((int) $user->id);
        $this->assertCount(2, $page['documents']);
        $this->assertSame([true, false], array_column($page['documents'], 'canrevoke'));
        // A year chosen by default.
        $this->assertSame([false, true, false], array_column($page['validity'], 'checked'));

        $this->assertFalse(learner_summary_document::delete((int) $other->id, (int) $second->id));
        $this->assertTrue(learner_summary_document::delete((int) $user->id, (int) $second->id));
        $this->assertNull(learner_summary_document::find($second->code));
        $this->assertTrue($DB->record_exists(learner_summary_document::TABLE, ['id' => $first->id]));
    }

    /**
     * An address that tried too many codes not found on the page of verification is stopped for a while.
     */
    public function test_verify_limit(): void {
        $this->resetAfterTest();
        for ($i = 0; $i < learner_summary_document::VERIFY_LIMIT; $i++) {
            $this->assertFalse(learner_summary_document::verify_blocked());
            learner_summary_document::verify_failed();
        }
        $this->assertTrue(learner_summary_document::verify_blocked());
        \cache::make('theme_epure', 'verifyattempts')->purge();
        $this->assertFalse(learner_summary_document::verify_blocked());
    }
}
