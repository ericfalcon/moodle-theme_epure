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

#[\PHPUnit\Framework\Attributes\CoversClass(company_style::class)]
/**
 * Tests for the appearance of IOMAD companies.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\company_style
 */
final class company_style_test extends \advanced_testcase {
    /**
     * The header colour of the company is its brand colour; the link colour is the fallback.
     */
    public function test_brand_colour(): void {
        $this->assertSame('#36195F', company_style::brand_colour((object) ['headingcolor' => '#36195f', 'linkcolor' => '#ff0000']));
        $this->assertSame('#FF0000', company_style::brand_colour((object) ['headingcolor' => '', 'linkcolor' => '#f00']));
        $this->assertNull(company_style::brand_colour((object) ['headingcolor' => 'purple', 'linkcolor' => null]));
        $this->assertNull(company_style::brand_colour((object) []));
    }

    /**
     * The company colour drives the custom properties of the accessible palette.
     */
    public function test_css_with_colour(): void {
        $css = company_style::css((object) ['headingcolor' => '#36195F', 'customcss' => '']);
        $p = palette::derive('#36195F');
        $this->assertStringContainsString("--epure-brand:{$p['fill']};", $css);
        $this->assertStringContainsString("--epure-on-brand:{$p['on']};", $css);
        $this->assertStringContainsString("--epure-brand-text:{$p['text']};", $css);
        $this->assertStringContainsString('--epure-header-hover:rgba(0, 0, 0, .18);', $css);
        $this->assertStringContainsString('a{color:var(--epure-brand-text);}', $css);
    }

    /**
     * Under a light company colour, the header text is dark and the hover lightens instead.
     */
    public function test_css_with_light_colour(): void {
        $css = company_style::css((object) ['headingcolor' => '#F2C200']);
        $this->assertStringContainsString('--epure-on-brand:' . palette::INK . ';', $css);
        $this->assertStringContainsString('--epure-header-hover:rgba(255, 255, 255, .3);', $css);
    }

    /**
     * Without colour nor custom CSS, nothing is added.
     */
    public function test_css_empty(): void {
        $this->assertSame('', company_style::css((object) ['headingcolor' => '', 'linkcolor' => '', 'customcss' => '']));
    }

    /**
     * The custom CSS of the company is added, without being able to close the style element.
     */
    public function test_custom_css(): void {
        $css = company_style::css((object) ['customcss' => '.x{color:red}</style><script>alert(1)</script>']);
        $this->assertStringContainsString('.x{color:red}', $css);
        $this->assertStringNotContainsString('</style', $css);
    }

    /**
     * The Épure settings of a company: its brand colour comes before IOMAD's heading colour, and its font is applied.
     */
    public function test_company_settings(): void {
        $this->resetAfterTest();
        $company = (object) ['id' => 12, 'headingcolor' => '#36195F', 'linkcolor' => '', 'customcss' => ''];
        $this->assertSame('#36195F', company_style::brand_colour($company));

        company_style::save_settings(12, ['brandcolor' => '1c6e73', 'headerstyle' => 'brand', 'font' => 'lexend',
            'coursebanner' => 'hide', 'learnerdashboard' => 'show', 'darkmode' => 'auto']);
        $this->assertSame(
            ['brandcolor' => '#1C6E73', 'headerstyle' => 'brand', 'font' => 'lexend', 'coursebanner' => 'hide',
                'learnerdashboard' => 'show', 'darkmode' => 'auto'],
            array_diff_key(company_style::settings(12), array_flip(company_style::FOOTER_FIELDS))
        );
        $this->assertSame('#1C6E73', company_style::brand_colour($company));
        $css = company_style::css($company);
        $this->assertStringContainsString('--epure-brand:#1C6E73', $css);
        $this->assertStringContainsString('--epure-header-toggler-filter:', $css);
        $this->assertStringContainsString('font-family: "Lexend"', $css);

        // Invalid values are ignored; nothing left means the settings of the site.
        company_style::save_settings(12, ['brandcolor' => 'rouge', 'headerstyle' => 'pink', 'font' => 'comic',
            'coursebanner' => 'maybe', 'learnerdashboard' => 'never', 'darkmode' => 'dim']);
        $this->assertSame(
            ['brandcolor' => '', 'headerstyle' => '', 'font' => '', 'coursebanner' => '', 'learnerdashboard' => '',
                'darkmode' => ''] + array_fill_keys(company_style::FOOTER_FIELDS, ''),
            company_style::settings(12)
        );
        $this->assertFalse(get_config('theme_epure', 'companystyle_12'));
        $this->assertSame('#36195F', company_style::brand_colour($company));
    }
}
