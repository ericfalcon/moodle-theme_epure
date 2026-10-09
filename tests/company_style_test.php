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
            'coursebanner' => 'hide', 'learnerdashboard' => 'show', 'mobilenav' => 'hide', 'darkmode' => 'auto',
            'activityicons' => 'moodle']);
        $this->assertEquals(
            ['brandcolor' => '#1C6E73', 'headerstyle' => 'brand', 'font' => 'lexend', 'coursebanner' => 'hide',
                'learnerdashboard' => 'show', 'mobilenav' => 'hide', 'darkmode' => 'auto', 'activityicons' => 'moodle'],
            array_filter(company_style::settings(12))
        );
        $this->assertSame('#1C6E73', company_style::brand_colour($company));
        $css = company_style::css($company);
        $this->assertStringContainsString('--epure-brand:#1C6E73', $css);
        $this->assertStringContainsString('--epure-header-toggler-filter:', $css);
        $this->assertStringContainsString('font-family: "Lexend"', $css);

        // Invalid values are ignored; nothing left means the settings of the site.
        company_style::save_settings(12, ['brandcolor' => 'rouge', 'headerstyle' => 'pink', 'font' => 'comic',
            'coursebanner' => 'maybe', 'learnerdashboard' => 'never', 'mobilenav' => 'often', 'darkmode' => 'dim']);
        $this->assertSame([], array_filter(company_style::settings(12)));
        $this->assertFalse(get_config('theme_epure', 'companystyle_12'));
        $this->assertSame('#36195F', company_style::brand_colour($company));
    }

    /**
     * The other settings of the site a company can set instead: accent colour, corners, switches, choices and the texts
     * of the login page. Invalid values are ignored.
     */
    public function test_more_company_settings(): void {
        $this->resetAfterTest();
        company_style::save_settings(12, ['accentcolor' => '0e7c66', 'radius' => 'sharp', 'quicksearch' => 'hide',
            'catalogue' => 'show', 'emailbranding' => 'hide', 'breadcrumb' => 'desktop', 'loginlayout' => 'centered',
            'mobileblocks' => 'hidden', 'mobiledashboard' => 'overview', 'logintagline' => ' Bienvenue ',
            'logintext' => 'Formations']);
        $this->assertEquals(
            ['accentcolor' => '#0E7C66', 'catalogue' => 'show', 'quicksearch' => 'hide', 'emailbranding' => 'hide',
                'radius' => 'sharp', 'loginlayout' => 'centered', 'breadcrumb' => 'desktop', 'mobileblocks' => 'hidden',
                'mobiledashboard' => 'overview', 'logintagline' => 'Bienvenue', 'logintext' => 'Formations'],
            array_filter(company_style::settings(12))
        );

        company_style::save_settings(12, ['accentcolor' => 'vert', 'radius' => 'square', 'quicksearch' => 'maybe',
            'breadcrumb' => 'top', 'loginlayout' => 'left']);
        $this->assertSame([], array_filter(company_style::settings(12)));
    }

    /**
     * The accent colour of the company, else its brand colour, and its corners in the custom properties of the theme
     * and of Bootstrap.
     */
    public function test_css_accent_and_radius(): void {
        $this->resetAfterTest();
        $company = (object) ['id' => 12, 'headingcolor' => '#36195F'];
        $brand = palette::derive('#36195F');
        $this->assertStringContainsString("--epure-accent:{$brand['fill']};", company_style::css($company));
        $this->assertStringNotContainsString('--epure-radius:', company_style::css($company));

        company_style::save_settings(12, ['accentcolor' => '#0E7C66', 'radius' => 'sharp']);
        $css = company_style::css($company);
        $accent = palette::derive('#0E7C66');
        $this->assertStringContainsString("--epure-accent:{$accent['fill']};", $css);
        $this->assertStringContainsString("--epure-dark-accent:" . palette::derive('#0E7C66', true)['fill'] . ';', $css);
        $this->assertStringContainsString('--epure-radius:0.25rem;', $css);
        $this->assertStringContainsString('--bs-border-radius:0.25rem;', $css);
        // Sharp corners: the pills are rounded rectangles too.
        $this->assertStringContainsString('--epure-radius-pill:0.25rem;', $css);

        // An accent colour alone, without brand colour.
        $this->assertStringContainsString("--epure-accent:{$accent['fill']};", company_style::css((object) ['id' => 12]));
    }

    /**
     * Without a company, the settings of the site (or their defaults); with one, its own choices first.
     */
    public function test_settings_of_the_page(): void {
        $this->resetAfterTest();
        $this->assertTrue(company_style::enabled('catalogue'));
        set_config('catalogue', '0', 'theme_epure');
        $this->assertFalse(company_style::enabled('catalogue'));
        $this->assertSame('show', company_style::choice('breadcrumb'));
        set_config('loginlayout', 'centered', 'theme_epure');
        $this->assertSame('centered', company_style::choice('loginlayout'));
        set_config('logintagline', ' Site ', 'theme_epure');
        $this->assertSame('Site', company_style::footer_setting('logintagline'));

        // The company of the page (set as current_company() would find it).
        company_style::save_settings(12, ['catalogue' => 'show', 'loginlayout' => 'split', 'logintagline' => 'Clinique']);
        $property = new \ReflectionProperty(company_style::class, 'company');
        $property->setValue(null, (object) ['id' => 12]);
        try {
            $this->assertTrue(company_style::enabled('catalogue'));
            $this->assertSame('split', company_style::choice('loginlayout'));
            $this->assertSame('Clinique', company_style::footer_setting('logintagline'));
            // What the company leaves empty stays the setting of the site.
            $this->assertSame('show', company_style::choice('breadcrumb'));
            $this->assertTrue(company_style::enabled('quicksearch'));
        } finally {
            company_style::reset();
        }
    }
}
