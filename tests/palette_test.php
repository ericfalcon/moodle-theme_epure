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

#[\PHPUnit\Framework\Attributes\CoversClass(palette::class)]
/**
 * Tests for the accessible palette.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\palette
 */
final class palette_test extends \basic_testcase {
    /**
     * Valid and invalid colour inputs.
     *
     * @return array
     */
    public static function normalise_provider(): array {
        return [
            'long form' => ['#2559a8', '#2559A8'],
            'short form' => ['#72a', '#7722AA'],
            'without hash' => ['c0392b', '#C0392B'],
            'spaces' => ['  #ABCDEF ', '#ABCDEF'],
            'too short' => ['#12', null],
            'not hex' => ['#12345G', null],
            'colour name' => ['red', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('normalise_provider')]
    /**
     * Test colour normalisation.
     *
     * @dataProvider normalise_provider
     * @param string|null $input Raw value.
     * @param string|null $expected Normalised value.
     */
    public function test_normalise(?string $input, ?string $expected): void {
        $this->assertSame($expected, palette::normalise($input));
    }

    /**
     * Test the WCAG contrast ratio against known values.
     */
    public function test_contrast(): void {
        $this->assertEqualsWithDelta(21.0, palette::contrast('#000000', '#FFFFFF'), 0.01);
        $this->assertEqualsWithDelta(1.0, palette::contrast('#777777', '#777777'), 0.001);
        $this->assertEqualsWithDelta(4.48, palette::contrast('#777777', '#FFFFFF'), 0.01);
        $this->assertSame(palette::contrast('#2559A8', '#FFFFFF'), palette::contrast('#FFFFFF', '#2559A8'));
    }

    /**
     * Brand colours covering dark, light, saturated and near-neutral cases.
     *
     * @return array
     */
    public static function brand_provider(): array {
        return [
            'default blue' => ['#2559A8'],
            'bright yellow' => ['#F2C200'],
            'pale cyan' => ['#7FD4E8'],
            'burgundy' => ['#9B2335'],
            'near black' => ['#1A1A1A'],
            'orange' => ['#E8833A'],
            'white' => ['#FFFFFF'],
            'pure green' => ['#00FF00'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('brand_provider')]
    /**
     * Every derived palette meets the WCAG AA ratios, in light and dark mode.
     *
     * @dataProvider brand_provider
     * @param string $brand Brand colour.
     */
    public function test_derive_meets_aa(string $brand): void {
        foreach ([false, true] as $dark) {
            $p = palette::derive($brand, $dark);
            $surface = $dark ? palette::SURFACE_DARK : palette::SURFACE_LIGHT;

            // Text on the brand fill: large text and components at least, normal text whenever possible.
            $this->assertGreaterThanOrEqual(palette::AA_UI, palette::contrast($p['on'], $p['fill']));
            // Brand text and links, on the surface and on both tinted backgrounds.
            $this->assertGreaterThanOrEqual(palette::AA_TEXT, palette::contrast($p['text'], $surface));
            $this->assertGreaterThanOrEqual(palette::AA_TEXT, palette::contrast($p['text'], $p['soft']));
            $this->assertGreaterThanOrEqual(palette::AA_TEXT, palette::contrast($p['text'], $p['soft2']));
            if ($dark) {
                // Fills stand out from the dark surface.
                $this->assertGreaterThanOrEqual(palette::AA_UI, palette::contrast($p['fill'], $surface));
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('brand_provider')]
    /**
     * The veil over the login image keeps the text readable on any image, from black to white.
     *
     * @dataProvider brand_provider
     * @param string $brand Brand colour.
     */
    public function test_login_overlay_keeps_text_readable(string $brand): void {
        $p = palette::derive($brand);
        $this->assertGreaterThanOrEqual(0.7, $p['overlayalpha']);
        $this->assertLessThanOrEqual(0.95, $p['overlayalpha']);
        if ($p['oncontrast'] >= palette::AA_TEXT + 0.2) {
            foreach (['#000000', '#FFFFFF', '#808080'] as $image) {
                $veiled = palette::mix($p['fill'], $image, 1 - $p['overlayalpha']);
                $this->assertGreaterThanOrEqual(palette::AA_TEXT, palette::contrast($p['on'], $veiled));
            }
        }
    }

    /**
     * A dark brand colour is used as is; a light one is adjusted for text.
     */
    public function test_derive_adjustment(): void {
        $blue = palette::derive('#2559A8');
        $this->assertSame('#2559A8', $blue['fill']);
        $this->assertSame('#FFFFFF', $blue['on']);

        $yellow = palette::derive('#F2C200');
        $this->assertSame('#F2C200', $yellow['fill']);
        $this->assertSame(palette::INK, $yellow['on']);
        $this->assertTrue($yellow['textadjusted']);
        $this->assertNotSame('#F2C200', $yellow['text']);
    }

    /**
     * An invalid colour falls back to the default brand colour.
     */
    public function test_derive_invalid_falls_back(): void {
        $this->assertSame(palette::DEFAULT_BRAND, palette::derive('not a colour')['brand']);
        $this->assertSame(palette::DEFAULT_BRAND, palette::derive(null)['brand']);
    }
}
