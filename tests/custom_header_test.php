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

#[\PHPUnit\Framework\Attributes\CoversClass(custom_header::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(palette::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(admin\palette_preview::class)]
/**
 * Tests for the header painted by the custom SCSS, and the header in a gradient.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\custom_header
 * @covers     \theme_epure\palette
 * @covers     \theme_epure\admin\palette_preview
 */
final class custom_header_test extends \advanced_testcase {
    /**
     * The rules that paint the header are found, with their colours; those of an element inside it, or without a
     * colour, are not.
     */
    public function test_rules(): void {
        $scss = "/* The header { } */\n.navbar {\n  background: linear-gradient(90deg, #43ABB1, #B1C800) !important;\n}\n"
            . "html:not(.epure-dark) .navbar.fixed-top { background-color: rgb(10, 20, 30); }\n"
            . ".navbar .nav-link { background: #ff0000; }\n.navbar { background: none; }\n.card { background: #fff; }";
        $rules = custom_header::rules($scss);
        $this->assertCount(2, $rules);
        $this->assertSame('.navbar', $rules[0]['selector']);
        $this->assertSame(['#43ABB1', '#B1C800'], $rules[0]['colours']);
        $this->assertSame('html:not(.epure-dark) .navbar.fixed-top', $rules[1]['selector']);
        $this->assertSame(['#0A141E'], $rules[1]['colours']);
        $this->assertSame([], custom_header::rules(''));
    }

    /**
     * The text chosen reads on the least favourable colour: dark on the turquoise and lime gradient, white on a dark
     * blue one; the SCSS calls the mixin under the selector of the rule.
     */
    public function test_adapt(): void {
        $this->assertSame(['on' => palette::INK, 'contrast' => 6.77], palette::on_stops(['#43ABB1', '#B1C800']));
        $this->assertSame(palette::SURFACE_LIGHT, palette::on_stops(['#1E3A8A', '#4A1F8C'])['on']);
        $scss = custom_header::adapt_scss(".navbar { background: linear-gradient(90deg, #43ABB1, #B1C800); }");
        $this->assertSame(".navbar { @include epure-header-text(#111418, false); }\n", $scss);
        $this->assertSame('', custom_header::adapt_scss('.card { color: red; }'));
    }

    /**
     * The setting says what it found, and warns when no text reaches 4.5:1.
     */
    public function test_report(): void {
        $report = custom_header::report('.navbar { background: linear-gradient(90deg, #43ABB1, #B1C800); }');
        $this->assertStringContainsString('#43ABB1 → #B1C800', $report);
        $this->assertStringContainsString(format_float(6.77, 1), $report);
        // Mid grey and white: neither white nor the dark ink reaches 4.5:1 on both.
        $this->assertStringContainsString(
            get_string('customheaderlow', 'theme_epure', (object) ['selector' => '.navbar', 'colours' => '#FFFFFF → #767676',
                'text' => get_string('customheaderdark', 'theme_epure'),
                'contrast' => format_float(palette::on_stops(['#FFFFFF', '#767676'])['contrast'], 1)]),
            custom_header::report('.navbar { background: linear-gradient(#fff, #767676); }')
        );
        $this->assertSame('', custom_header::report(''));
    }

    /**
     * A background the theme cannot know for sure (transparent, a variable, a nested rule) gives a partial check, never a
     * contrast; the opaque colours read still adapt the text.
     */
    public function test_unsure(): void {
        $rules = custom_header::rules(".navbar { background: rgba(255, 255, 255, .4); }\n"
            . ".navbar.fixed-top { background-color: var(--brand); }\n"
            . "body .navbar { background: #43ABB1; .nav-link { color: red; } }\n"
            . ".navbar { background: linear-gradient(#43ABB1, rgb(177 200 0 / 100%)); }");
        $unsure = array_map(fn($rule) => $rule['selector'] . ': ' . implode(' ', $rule['unsure']), $rules);
        $this->assertContains('body .navbar: nested', $unsure);
        $this->assertContains('.navbar.fixed-top: variable', $unsure);
        $this->assertContains('.navbar: transparent', $unsure);
        // An opaque colour written as rgb() with a slash and 100 %: sure.
        $this->assertContains('.navbar: ', $unsure);

        $report = custom_header::report(".navbar { background: rgba(0, 0, 0, .5); }");
        $this->assertStringContainsString(get_string('customheader_transparent', 'theme_epure'), $report);
        $this->assertStringNotContainsString(':1', $report);
        // The colour read in a transparent background still adapts the text; a variable alone does not.
        $adapted = custom_header::adapt_scss('.navbar { background: rgba(0, 0, 0, .5); }');
        $this->assertStringContainsString('epure-header-text', $adapted);
        $this->assertSame('', custom_header::adapt_scss('.navbar { background: var(--brand); }'));
    }

    /**
     * The palette shown under the colours of the settings: the light and dark modes, and the header when it is coloured.
     */
    public function test_preview(): void {
        $groups = palette::preview('#43ABB1', '#B1C800', 'gradient');
        $this->assertCount(3, $groups);
        $this->assertSame(get_string('palette_light', 'theme_epure'), $groups[0]['name']);
        $this->assertSame('#43ABB1', $groups[0]['colours'][0]['hex']);
        $this->assertSame(['#43ABB1', '#B1C800', '#111418'], array_column($groups[2]['colours'], 'hex'));
        // A white header has no colours of its own; without an accent, the brand colour.
        $groups = palette::preview('#2559A8', null, 'light');
        $this->assertCount(2, $groups);
        $this->assertSame($groups[0]['colours'][0]['hex'], $groups[0]['colours'][6]['hex']);
        $this->assertStringContainsString('background-color: #2559A8', admin\palette_preview::html($groups));
    }

    /**
     * The header in a gradient from the brand colour to the accent colour: its text reads at 4.5:1 on both ends, moved
     * as little as needed.
     */
    public function test_header_gradient(): void {
        $g = palette::header_gradient('#43ABB1', '#B1C800');
        $this->assertSame(['start' => '#43ABB1', 'end' => '#B1C800', 'on' => palette::INK], $g);
        // A light and a mid colour: the ends are moved for the text to read on both.
        $g = palette::header_gradient('#FFD400', '#2559A8');
        foreach ([$g['start'], $g['end']] as $stop) {
            $this->assertGreaterThanOrEqual(palette::AA_TEXT, palette::contrast($g['on'], $stop));
        }
        // Without an accent colour, a plain brand colour.
        $g = palette::header_gradient('#2559A8', null);
        $this->assertSame($g['start'], $g['end']);
    }
}
