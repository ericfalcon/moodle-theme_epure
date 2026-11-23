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

#[\PHPUnit\Framework\Attributes\CoversClass(footer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(company_style::class)]
/**
 * Tests for the footer of the pages.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\footer
 * @covers     \theme_epure\company_style
 */
final class footer_test extends \advanced_testcase {
    /**
     * The links of the settings come first; empty ones are found from Moodle; invalid ones are left out.
     */
    public function test_links(): void {
        $this->resetAfterTest();
        set_config('sitepolicy', '');
        set_config('sitepolicyhandler', '');
        set_config('showdataretentionsummary', 0, 'tool_dataprivacy');
        set_config('supportavailability', CONTACT_SUPPORT_ANYONE);
        set_config('footerlegalurl', 'https://example.org/legal', 'theme_epure');
        set_config('footerlinks', "Terms|https://example.org/terms\nBroken line\n|https://example.org/nolabel", 'theme_epure');
        set_config('footertext', '<p>1 rue des <strong>Tilleuls</strong></p>', 'theme_epure');

        $footer = footer::export();
        $this->assertStringContainsString('<strong>Tilleuls</strong>', $footer['text']);
        $links = array_column($footer['links'], 'url', 'label');
        $this->assertSame('https://example.org/legal', $links[get_string('footerlegal', 'theme_epure')]);
        $this->assertStringContainsString('/user/contactsitesupport.php', $links[get_string('footercontact', 'theme_epure')]);
        $this->assertSame('https://example.org/terms', $links['Terms']);
        $this->assertCount(3, $links);

        // The accessibility statement, once published, has its link.
        set_config('a11ystatus', 'full', 'theme_epure');
        $this->assertCount(4, footer::export()['links']);

        // Support closed: no contact link; the footer can be turned off.
        set_config('supportavailability', CONTACT_SUPPORT_DISABLED);
        $this->assertCount(3, footer::export()['links']);
        $this->assertTrue(footer::enabled());
        set_config('footer', '0', 'theme_epure');
        $this->assertFalse(footer::enabled());
    }

    /**
     * The settings of a company keep the footer values, the addresses cleaned.
     */
    public function test_company_values(): void {
        $this->resetAfterTest();
        company_style::save_settings(7, ['footertext' => ' Campus de Lyon ', 'footerlegalurl' => 'javascript:alert(1)',
            'footercontacturl' => 'https://example.org/contact']);
        $settings = company_style::settings(7);
        $this->assertSame('Campus de Lyon', $settings['footertext']);
        $this->assertSame('', $settings['footerlegalurl']);
        $this->assertSame('https://example.org/contact', $settings['footercontacturl']);
        // Without IOMAD or a company, the site values are used.
        set_config('footertext', 'Site', 'theme_epure');
        $this->assertSame('Site', company_style::footer_setting('footertext'));
    }

    /**
     * The text of the footer is written in the editor by an administrator, or by the manager of an IOMAD company: its
     * HTML is cleaned by Moodle (format_text cleans it with HTML Purifier), so no script reaches the page; its columns
     * (a row of Bootstrap) stay.
     */
    public function test_text_cleaned(): void {
        $this->resetAfterTest();
        set_config('footertext', '<div class="row"><div class="col-md-6"><strong>Campus</strong></div></div>'
            . '<script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">link</a>', 'theme_epure');
        $text = footer::export()['text'];
        $this->assertStringContainsString('<div class="row"><div class="col-md-6"><strong>Campus</strong></div></div>', $text);
        $this->assertStringNotContainsString('<script', $text);
        $this->assertStringNotContainsString('onerror', $text);
        $this->assertStringNotContainsString('javascript:', $text);
    }

    /**
     * The texts of the footer written in Markdown before 1.51.0, of the site and of the companies, become HTML once, at the
     * upgrade; the empty ones stay empty.
     */
    public function test_markdown_to_html(): void {
        $this->resetAfterTest();
        set_config('footertext', "1 rue des **Tilleuls**\n\n[Contact](https://example.org)", 'theme_epure');
        company_style::save_settings(7, ['footertext' => 'Campus *de Lyon*']);
        company_style::save_settings(8, ['brandcolor' => '#9B2335']);
        footer::markdown_to_html();
        $expected = '<p>1 rue des <strong>Tilleuls</strong></p>' . "\n\n" . '<p><a href="https://example.org">Contact</a></p>';
        $this->assertSame($expected, get_config('theme_epure', 'footertext'));
        $this->assertSame('<p>Campus <em>de Lyon</em></p>', company_style::settings(7)['footertext']);
        $this->assertSame('', company_style::settings(8)['footertext']);
        $this->assertSame('#9B2335', company_style::settings(8)['brandcolor']);
    }
}
