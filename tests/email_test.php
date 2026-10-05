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

#[\PHPUnit\Framework\Attributes\CoversClass(email::class)]
/**
 * Tests for the e-mails in the colours of the brand.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\email
 * @covers     \theme_epure\output\core_renderer_cli
 */
final class email_test extends \advanced_testcase {
    /**
     * HTML body of an e-mail sent to a user with Épure as the theme of the site.
     *
     * @param \stdClass $user Recipient.
     * @return string
     */
    protected function send(\stdClass $user): string {
        global $PAGE;
        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->force_theme('epure');
        $sink = $this->redirectEmails();
        email_to_user(
            $user,
            \core_user::get_support_user(),
            'Due tomorrow',
            'Your assignment is due.',
            '<p>Your assignment is due.</p>'
        );
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        return quoted_printable_decode($messages[0]->body);
    }

    /**
     * The e-mails get the band of the brand colour and the footer, unless the branding is turned off.
     */
    public function test_branding(): void {
        $this->resetAfterTest();
        set_config('theme', 'epure');
        set_config('brandcolor', '#1C6E73', 'theme_epure');
        set_config('footertext', 'Clinique des Tilleuls', 'theme_epure');
        $user = $this->getDataGenerator()->create_user(['mailformat' => 1]);

        $body = $this->send($user);
        $this->assertStringContainsString('<p>Your assignment is due.</p>', $body);
        $this->assertStringContainsString('background-color:#1C6E73', $body);
        $this->assertStringContainsString('Clinique des Tilleuls', $body);
        $this->assertStringContainsString('/message/notificationpreferences.php', $body);

        set_config('emailbranding', '0', 'theme_epure');
        $body = $this->send($user);
        $this->assertStringContainsString('<p>Your assignment is due.</p>', $body);
        $this->assertStringNotContainsString('notificationpreferences', $body);
    }

    /**
     * Without IOMAD, a user has no company; the context uses the name and the colour of the site.
     */
    public function test_export(): void {
        $this->resetAfterTest();
        set_config('brandcolor', '#9B2335', 'theme_epure');
        $user = $this->getDataGenerator()->create_user();
        if (!company_style::iomad_installed()) {
            $this->assertNull(email::company_of((int) $user->id));
        }
        $data = email::export((int) $user->id);
        $this->assertEquals('#9B2335', $data['brand']);
        $this->assertEquals(format_string(get_site()->fullname), $data['name']);
    }
}
