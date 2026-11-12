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
 * The colours and the languages of the texts of the documents given in a course (Word, PowerPoint, LibreOffice Writer),
 * read with {@see document_check}: the texts whose colour does not stand out enough from their background (WCAG 1.4.3),
 * with the rules of {@see content_colours}; the passages in another language than the one marked on them (WCAG
 * 3.1.2), guessed with {@see content_language}, so to confirm. Only the formatting written on the text is read: a
 * colour that comes from a style or a template is left to the person.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document_style {
    /** @var array<string, string> The colours of highlighting of Word, as #RRGGBB. */
    public const HIGHLIGHTS = [
        'yellow' => '#FFFF00', 'green' => '#00FF00', 'cyan' => '#00FFFF', 'magenta' => '#FF00FF', 'blue' => '#0000FF',
        'red' => '#FF0000', 'darkBlue' => '#000080', 'darkCyan' => '#008080', 'darkGreen' => '#008000',
        'darkMagenta' => '#800080', 'darkRed' => '#800000', 'darkYellow' => '#808000', 'darkGray' => '#808080',
        'lightGray' => '#C0C0C0', 'black' => '#000000', 'white' => '#FFFFFF',
    ];

    /**
     * A colour written as RRGGBB (Office Open XML), or « auto », or nothing.
     *
     * @param string $value Value.
     * @return string|null #RRGGBB, none for « auto » or nothing.
     */
    protected static function hex(string $value): ?string {
        return preg_match('/^[0-9a-f]{6}$/i', $value) ? '#' . strtoupper($value) : null;
    }

    /**
     * Whether a passage is in another language than the one marked on it.
     *
     * @param string $text Passage.
     * @param string $marked Language marked on it (« fr-FR », « en »…), or nothing.
     * @return bool
     */
    protected static function other_language(string $text, string $marked): bool {
        $base = strtolower(substr($marked, 0, 2));
        if ($base === '' || !isset(content_language::WORDS[$base])) {
            return false;
        }
        $guess = content_language::guess($text);
        return $guess !== null && $guess !== $base;
    }

    /**
     * A document of Word: the paragraphs with a text too little contrasted, and those in another language.
     *
     * @param \DOMXPath $doc word/document.xml.
     * @param \DOMXPath|null $styles word/styles.xml.
     * @return array{doccontrast: int, doclang: int}
     */
    public static function word(\DOMXPath $doc, ?\DOMXPath $styles): array {
        $page = self::hex($doc->evaluate('string(//w:background/@w:color)')) ?? palette::SURFACE_LIGHT;
        $default = $styles ? (string) $styles->evaluate('string(//w:docDefaults//w:rPr/w:lang/@w:val)') : '';
        $found = ['doccontrast' => 0, 'doclang' => 0];
        foreach ($doc->query('//w:body//w:p') as $paragraph) {
            $low = false;
            $shading = self::hex($doc->evaluate('string(w:pPr/w:shd/@w:fill)', $paragraph))
                ?? self::hex($doc->evaluate('string(ancestor::w:tc[1]/w:tcPr/w:shd/@w:fill)', $paragraph));
            $marked = '';
            foreach ($doc->query('.//w:r[w:t]', $paragraph) as $run) {
                $marked = $marked ?: (string) $doc->evaluate('string(w:rPr/w:lang/@w:val)', $run);
                // A colour of the text written on it: « auto » adapts to the background, as Word shows it.
                $fg = self::hex($doc->evaluate('string(w:rPr/w:color/@w:val)', $run));
                if ($low || $fg === null || trim($doc->evaluate('string(w:t)', $run)) === '') {
                    continue;
                }
                $highlight = self::HIGHLIGHTS[$doc->evaluate('string(w:rPr/w:highlight/@w:val)', $run)] ?? null;
                $bg = $highlight ?? self::hex($doc->evaluate('string(w:rPr/w:shd/@w:fill)', $run)) ?? $shading ?? $page;
                $size = (int) $doc->evaluate('string(w:rPr/w:sz/@w:val)', $run);
                $bold = $doc->query('w:rPr/w:b[not(@w:val="0" or @w:val="false")]', $run)->length > 0;
                $low = content_colours::low($fg, $bg, $size >= 36 || ($bold && $size >= 28));
            }
            $found['doccontrast'] += $low ? 1 : 0;
            if (self::other_language($doc->evaluate('string(.)', $paragraph), $marked ?: $default)) {
                $found['doclang']++;
            }
        }
        return $found;
    }

    /**
     * A slide of PowerPoint: the shapes with a text too little contrasted on their own background (a colour of the text
     * and a background both written on the slide: those of a template are left to the person), and the paragraphs in
     * another language.
     *
     * @param \DOMXPath $slide ppt/slides/slideN.xml.
     * @return array{doccontrast: int, doclang: int}
     */
    public static function slide(\DOMXPath $slide): array {
        $background = self::hex($slide->evaluate('string(//p:cSld/p:bg/p:bgPr/a:solidFill/a:srgbClr/@val)'));
        $found = ['doccontrast' => 0, 'doclang' => 0];
        foreach ($slide->query('//p:sp[p:txBody]') as $shape) {
            $bg = self::hex($slide->evaluate('string(p:spPr/a:solidFill/a:srgbClr/@val)', $shape)) ?? $background;
            $low = false;
            foreach ($slide->query('p:txBody//a:r[normalize-space(a:t)]', $shape) as $run) {
                $fg = self::hex($slide->evaluate('string(a:rPr/a:solidFill/a:srgbClr/@val)', $run));
                if ($fg === null || $bg === null) {
                    continue;
                }
                $size = (int) $slide->evaluate('string(a:rPr/@sz)', $run);
                $bold = $slide->evaluate('string(a:rPr/@b)', $run) === '1';
                $low = $low || content_colours::low($fg, $bg, $size >= 1800 || ($bold && $size >= 1400));
            }
            $found['doccontrast'] += $low ? 1 : 0;
            foreach ($slide->query('p:txBody/a:p', $shape) as $paragraph) {
                $marked = (string) $slide->evaluate('string((a:r/a:rPr/@lang | a:endParaRPr/@lang)[1])', $paragraph);
                if (self::other_language($slide->evaluate('string(.)', $paragraph), $marked)) {
                    $found['doclang']++;
                }
            }
        }
        return $found;
    }

    /**
     * A text of LibreOffice Writer: the paragraphs with a text too little contrasted (the colours of its automatic
     * styles), and those in another language than the one of their style or of the document.
     *
     * @param \DOMXPath $content content.xml.
     * @param \DOMXPath|null $styles styles.xml.
     * @return array{doccontrast: int, doclang: int}
     */
    public static function odt(\DOMXPath $content, ?\DOMXPath $styles): array {
        // The automatic styles: colour of the text, of its highlight, of the background of the paragraph, language.
        $props = [];
        foreach ($content->query('//office:automatic-styles/style:style') as $style) {
            $name = $style->getAttributeNS(document_check::namespace('style'), 'name');
            $props[$name] = [
                'fg' => content_colours::colour((string) $content->evaluate('string(style:text-properties/@fo:color)', $style)),
                'bg' => content_colours::colour((string) $content->evaluate(
                    'string(style:text-properties/@fo:background-color | style:paragraph-properties/@fo:background-color)',
                    $style
                )),
                'lang' => (string) $content->evaluate('string(style:text-properties/@fo:language)', $style),
                'size' => (float) $content->evaluate('string(style:text-properties/@fo:font-size)', $style),
                'bold' => $content->evaluate('string(style:text-properties/@fo:font-weight)', $style) === 'bold',
            ];
        }
        $default = $styles ? (string) $styles->evaluate(
            'string(//style:default-style[@style:family="paragraph"]/style:text-properties/@fo:language)'
        ) : '';
        $styleof = fn(\DOMElement $element) => $props[$element->getAttributeNS(
            document_check::namespace('text'),
            'style-name'
        )] ?? [];
        $found = ['doccontrast' => 0, 'doclang' => 0];
        foreach ($content->query('//office:text//text:p | //office:text//text:h') as $paragraph) {
            $own = $styleof($paragraph);
            $low = false;
            // The paragraph itself, then each of its spans.
            foreach (array_merge([$paragraph], iterator_to_array($content->query('.//text:span', $paragraph))) as $element) {
                $style = $element === $paragraph ? $own : $styleof($element) + $own;
                $text = $element === $paragraph ? $content->evaluate('string(text())', $paragraph) : $element->textContent;
                if (empty($style['fg']) || trim($text) === '') {
                    continue;
                }
                $size = (float) ($style['size'] ?? 0);
                $low = $low || content_colours::low(
                    $style['fg'],
                    $style['bg'] ?? palette::SURFACE_LIGHT,
                    $size >= 18 || (!empty($style['bold']) && $size >= 14)
                );
            }
            $found['doccontrast'] += $low ? 1 : 0;
            if (self::other_language($paragraph->textContent, ($own['lang'] ?? '') ?: $default)) {
                $found['doclang']++;
            }
        }
        return $found;
    }
}
