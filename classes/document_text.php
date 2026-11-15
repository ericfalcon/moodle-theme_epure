<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * The text of a document of an office suite, for the preview of the files (theme_epure/file_preview): the titles, the
 * paragraphs, the lists and the tables of a document of Word or LibreOffice Writer; the title and the texts of each
 * slide of PowerPoint or Impress; each sheet of Excel or Calc as a table. A version that reads with a screen reader, in
 * the font and the colours of the user, for whoever cannot open the file; the file stays to download for its layout.
 *
 * Read in the XML of the document, with the limits of {@see document_check}, and kept by content of file. Only text:
 * the HTML is written by the template theme_epure/document_text, which escapes all of it.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document_text extends document_check {
    /** @var int Blocks (titles, paragraphs, tables…) at most: the rest is in the file. */
    public const MAX_BLOCKS = 2000;

    /** @var int Rows of a table, or of a sheet, at most. */
    public const MAX_ROWS = 200;

    /** @var int Columns of a table, or of a sheet, at most. */
    public const MAX_COLUMNS = 30;

    /** @var int The level of the titles of the document under the title of the preview (h2): its title 1 is a h3. */
    protected const LEVEL_OFFSET = 2;

    /**
     * Whether a type of file has a text version: the documents of an office suite that can be read (not the PDF).
     *
     * @param string $mimetype Type of the file.
     * @return bool
     */
    public static function supported(string $mimetype): bool {
        $kind = self::kind($mimetype);
        return $kind !== null && $kind !== 'pdf';
    }

    /**
     * The text of a document, from the cache or read.
     *
     * @param \stored_file $file File.
     * @return array|null {blocks: array, truncated: bool}; null when it cannot be read.
     */
    public static function read(\stored_file $file): ?array {
        $kind = self::kind((string) $file->get_mimetype());
        if ($kind === null || $kind === 'pdf' || $file->get_filesize() > self::MAX_SIZE) {
            return null;
        }
        $cache = \cache::make('theme_epure', 'documenttext');
        $known = $cache->get($file->get_contenthash());
        if ($known !== false) {
            return $known === 'unreadable' ? null : $known;
        }
        try {
            $blocks = static::read_package($file, fn(callable $read, array $names) => match ($kind) {
                'word' => self::word($read),
                'slides' => self::slides($read),
                'sheets' => self::sheets($read),
                'odt' => self::odt($read),
                'odp' => self::odp($read),
                'ods' => self::ods($read),
            });
        } catch (\Throwable $e) {
            // Expected: a damaged or protected file; it stays to download.
            $blocks = null;
        }
        $text = $blocks === null ? null : [
            'blocks' => array_slice($blocks, 0, self::MAX_BLOCKS),
            'truncated' => count($blocks) > self::MAX_BLOCKS,
        ];
        $cache->set($file->get_contenthash(), $text ?? 'unreadable');
        return $text;
    }

    /**
     * A text without its runs of spaces, with its line breaks.
     *
     * @param string $text Text.
     * @return string
     */
    protected static function clean(string $text): string {
        $lines = array_map(fn($line) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $line)), explode("\n", $text));
        return trim(implode("\n", $lines));
    }

    /**
     * A title, at its level under the title of the preview.
     *
     * @param int $level Level in the document, from 1.
     * @param string $text Text.
     * @return array
     */
    protected static function heading(int $level, string $text): array {
        return ['type' => 'heading', 'level' => max(3, min(6, $level + self::LEVEL_OFFSET)), 'text' => $text];
    }

    /**
     * A table, its rows and columns limited; its first row as header when it has more than one.
     *
     * @param array $rows Rows of texts.
     * @param string $caption Caption, empty for none.
     * @return array|null
     */
    protected static function table(array $rows, string $caption = ''): ?array {
        $rows = array_values(array_filter($rows, fn($row) => implode('', $row) !== ''));
        if (!$rows) {
            return null;
        }
        $truncated = count($rows) > self::MAX_ROWS;
        $width = min(self::MAX_COLUMNS, max(array_map('count', $rows)));
        $cells = [];
        foreach (array_slice($rows, 0, self::MAX_ROWS) as $row) {
            $row = array_pad(array_slice(array_values($row), 0, $width), $width, '');
            $cells[] = ['cells' => array_map(fn($text) => ['text' => $text], $row)];
        }
        $header = count($cells) > 1 ? array_shift($cells) : null;
        return ['type' => 'table', 'caption' => $caption, 'header' => $header, 'rows' => $cells, 'truncated' => $truncated];
    }

    /**
     * The text of a paragraph of Word: its runs, the tabs as spaces, the line breaks kept.
     *
     * @param \DOMXPath $xpath Part.
     * @param \DOMNode $paragraph Paragraph (w:p).
     * @return string
     */
    protected static function word_paragraph(\DOMXPath $xpath, \DOMNode $paragraph): string {
        $text = '';
        foreach ($xpath->query('.//w:t | .//w:tab | .//w:br | .//w:cr', $paragraph) as $node) {
            $text .= match ($node->localName) {
                't' => $node->textContent,
                'tab' => ' ',
                default => "\n",
            };
        }
        return self::clean($text);
    }

    /**
     * The text alternatives of the images of a node, or that they have none.
     *
     * @param \DOMXPath $xpath Part.
     * @param \DOMNode $node Node.
     * @param string $query Properties of the images (wp:docPr, p:cNvPr…).
     * @return array[] Blocks of images.
     */
    protected static function images(\DOMXPath $xpath, \DOMNode $node, string $query): array {
        $images = [];
        foreach ($xpath->query($query, $node) as $props) {
            $decorative = $xpath->query('.//*[local-name()="decorative" and (@val="1" or @val="true")]', $props)->length;
            if (!$decorative) {
                $alt = $props->getAttribute('descr') ?: $props->getAttribute('title');
                $images[] = ['type' => 'image', 'alt' => self::clean($alt)];
            }
        }
        return $images;
    }

    /**
     * A document of Word: titles (by their level of outline or the name of their style), paragraphs, lists, tables and
     * the text alternatives of the images, in their order.
     *
     * @param callable $read Reads a part of the document.
     * @return array|null
     */
    protected static function word(callable $read): ?array {
        $doc = $read('word/document.xml');
        if (!$doc) {
            return null;
        }
        $levels = [];
        $styles = $read('word/styles.xml');
        if ($styles) {
            foreach ($styles->query('//w:style[@w:type="paragraph"]') as $style) {
                $id = $style->getAttributeNS(self::NS['w'], 'styleId');
                $outline = $styles->evaluate('string(w:pPr/w:outlineLvl/@w:val)', $style);
                $name = $styles->evaluate('string(w:name/@w:val)', $style);
                if ($outline !== '' && (int) $outline < 9) {
                    $levels[$id] = (int) $outline + 1;
                } else if (preg_match('~^(heading|title)\s*(\d?)$~i', $name, $m)) {
                    $levels[$id] = $m[2] === '' ? 1 : (int) $m[2];
                }
            }
        }
        $blocks = [];
        foreach ($doc->query('//w:body/*') as $node) {
            if (count($blocks) > self::MAX_BLOCKS) {
                break;
            }
            if ($node->localName === 'tbl') {
                $rows = [];
                foreach ($doc->query('w:tr', $node) as $row) {
                    $cells = [];
                    foreach ($doc->query('w:tc', $row) as $cell) {
                        $texts = [];
                        foreach ($doc->query('.//w:p', $cell) as $paragraph) {
                            $texts[] = self::word_paragraph($doc, $paragraph);
                        }
                        $cells[] = self::clean(implode("\n", $texts));
                    }
                    $rows[] = $cells;
                }
                if ($table = self::table($rows)) {
                    $blocks[] = $table;
                }
                continue;
            }
            if ($node->localName !== 'p') {
                continue;
            }
            array_push($blocks, ...self::images($doc, $node, './/wp:docPr'));
            $text = self::word_paragraph($doc, $node);
            if ($text === '') {
                continue;
            }
            $outline = $doc->evaluate('string(w:pPr/w:outlineLvl/@w:val)', $node);
            $style = $doc->evaluate('string(w:pPr/w:pStyle/@w:val)', $node);
            if ($outline !== '' && (int) $outline < 9) {
                $blocks[] = self::heading((int) $outline + 1, $text);
            } else if (isset($levels[$style])) {
                $blocks[] = self::heading($levels[$style], $text);
            } else if ($doc->query('w:pPr/w:numPr', $node)->length) {
                $blocks[] = ['type' => 'item', 'text' => $text];
            } else {
                $blocks[] = ['type' => 'paragraph', 'text' => $text];
            }
        }
        return $blocks;
    }

    /**
     * The targets of the relations of a part of Office Open XML, by their identifier, as paths in the archive.
     *
     * @param callable $read Reads a part of the document.
     * @param string $part Part (ppt/presentation.xml, xl/workbook.xml).
     * @return string[]
     */
    protected static function relations(callable $read, string $part): array {
        $folder = dirname($part);
        $rels = $read($folder . '/_rels/' . basename($part) . '.rels');
        $targets = [];
        foreach ($rels ? $rels->query('//rel:Relationship') : [] as $rel) {
            $target = $rel->getAttribute('Target');
            $targets[$rel->getAttribute('Id')] = str_starts_with($target, '/') ? ltrim($target, '/') : $folder . '/' . $target;
        }
        return $targets;
    }

    /**
     * A presentation of PowerPoint: each slide in its order, its title as a title, its texts as paragraphs, its tables
     * and the text alternatives of its images.
     *
     * @param callable $read Reads a part of the document.
     * @return array|null
     */
    protected static function slides(callable $read): ?array {
        $presentation = $read('ppt/presentation.xml');
        if (!$presentation) {
            return null;
        }
        $targets = self::relations($read, 'ppt/presentation.xml');
        $blocks = [];
        $number = 0;
        foreach ($presentation->query('//p:sldIdLst/p:sldId') as $id) {
            $slide = $read($targets[$id->getAttributeNS(self::NS['r'], 'id')] ?? '');
            if (!$slide || count($blocks) > self::MAX_BLOCKS) {
                continue;
            }
            $number++;
            $title = '';
            $texts = [];
            foreach ($slide->query('//p:sp') as $shape) {
                $type = $slide->evaluate('string(p:nvSpPr/p:nvPr/p:ph/@type)', $shape);
                $paragraphs = [];
                foreach ($slide->query('p:txBody/a:p', $shape) as $paragraph) {
                    $line = '';
                    foreach ($slide->query('.//a:t | .//a:br', $paragraph) as $node) {
                        $line .= $node->localName === 't' ? $node->textContent : "\n";
                    }
                    if (($line = self::clean($line)) !== '') {
                        $paragraphs[] = $line;
                    }
                }
                if (in_array($type, ['title', 'ctrTitle'], true) && $title === '') {
                    $title = implode(' ', $paragraphs);
                } else {
                    array_push($texts, ...$paragraphs);
                }
            }
            $label = get_string('preview_slide', 'theme_epure', $number);
            $blocks[] = self::heading(1, $title === '' ? $label : $label . ' – ' . $title);
            foreach ($texts as $text) {
                $blocks[] = ['type' => 'paragraph', 'text' => $text];
            }
            foreach ($slide->query('//a:tbl') as $table) {
                $rows = [];
                foreach ($slide->query('a:tr', $table) as $row) {
                    $cells = [];
                    foreach ($slide->query('a:tc', $row) as $cell) {
                        $cells[] = self::clean(implode("\n", array_map(
                            fn($p) => $p->textContent,
                            iterator_to_array($slide->query('.//a:p', $cell))
                        )));
                    }
                    $rows[] = $cells;
                }
                if ($block = self::table($rows)) {
                    $blocks[] = $block;
                }
            }
            array_push($blocks, ...self::images($slide, $slide->document, '//p:pic/p:nvPicPr/p:cNvPr'));
        }
        return $blocks;
    }

    /**
     * The index of a column of a reference of a cell (A1, AB12), from 0.
     *
     * @param string $reference Reference.
     * @return int
     */
    protected static function column(string $reference): int {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }
        return max(0, $index - 1);
    }

    /**
     * A workbook of Excel: each sheet in its order as a table, its name as caption and title; the values as shown by
     * their text (shared strings, inline strings, numbers and results of formulas as stored).
     *
     * @param callable $read Reads a part of the document.
     * @return array|null
     */
    protected static function sheets(callable $read): ?array {
        $workbook = $read('xl/workbook.xml');
        if (!$workbook) {
            return null;
        }
        $targets = self::relations($read, 'xl/workbook.xml');
        $shared = [];
        $strings = $read('xl/sharedStrings.xml');
        foreach ($strings ? $strings->query('//s:si') : [] as $item) {
            $shared[] = implode('', array_map(fn($t) => $t->textContent, iterator_to_array($strings->query('.//s:t', $item))));
        }
        $blocks = [];
        foreach ($workbook->query('//s:sheets/s:sheet') as $sheet) {
            $name = self::clean($sheet->getAttribute('name'));
            $part = $read($targets[$sheet->getAttributeNS(self::NS['r'], 'id')] ?? '');
            if (!$part) {
                continue;
            }
            $rows = [];
            foreach ($part->query('//s:sheetData/s:row') as $row) {
                if (count($rows) > self::MAX_ROWS) {
                    break;
                }
                $cells = [];
                foreach ($part->query('s:c', $row) as $cell) {
                    $column = self::column($cell->getAttribute('r'));
                    if ($column >= self::MAX_COLUMNS) {
                        continue;
                    }
                    $value = $part->evaluate('string(s:v)', $cell);
                    $text = match ($cell->getAttribute('t')) {
                        's' => $shared[(int) $value] ?? '',
                        'inlineStr' => $part->evaluate('string(s:is)', $cell),
                        'b' => $value === '1' ? 'TRUE' : 'FALSE',
                        default => $value,
                    };
                    $cells[$column] = self::clean($text);
                }
                if ($cells) {
                    $line = array_fill(0, max(array_keys($cells)) + 1, '');
                    $rows[] = array_replace($line, $cells);
                }
            }
            $blocks[] = self::heading(1, $name);
            if ($table = self::table($rows, $name)) {
                $blocks[] = $table;
            }
        }
        return $blocks;
    }

    /**
     * The text of a paragraph or a title of OpenDocument: the spaces (text:s), tabs and line breaks as text.
     *
     * @param \DOMNode $node Node.
     * @return string
     */
    protected static function odf_text(\DOMNode $node): string {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } else if ($child->localName === 'line-break') {
                $text .= "\n";
            } else if (in_array($child->localName, ['s', 'tab'], true)) {
                $text .= ' ';
            } else if ($child->localName !== 'note') {
                $text .= self::odf_text($child);
            }
        }
        return $text;
    }

    /**
     * The rows of a table of OpenDocument, the repeated rows and columns counted once past the limits.
     *
     * @param \DOMXPath $xpath Part.
     * @param \DOMNode $table Table.
     * @return array
     */
    protected static function odf_rows(\DOMXPath $xpath, \DOMNode $table): array {
        $rows = [];
        foreach ($xpath->query('.//table:table-row', $table) as $row) {
            $cells = [];
            foreach ($xpath->query('table:table-cell | table:covered-table-cell', $row) as $cell) {
                $texts = array_map(fn($p) => self::odf_text($p), iterator_to_array($xpath->query('text:p', $cell)));
                $repeat = min((int) ($cell->getAttributeNS(self::NS['table'], 'number-columns-repeated') ?: 1), self::MAX_COLUMNS);
                for ($i = 0; $i < $repeat; $i++) {
                    $cells[] = self::clean(implode("\n", $texts));
                }
            }
            // Empty cells repeated to the end of the sheet are not columns.
            while ($cells && end($cells) === '') {
                array_pop($cells);
            }
            $repeat = min((int) ($row->getAttributeNS(self::NS['table'], 'number-rows-repeated') ?: 1), self::MAX_ROWS);
            for ($i = 0; $i < $repeat && $cells; $i++) {
                $rows[] = $cells;
            }
            if (count($rows) > self::MAX_ROWS) {
                break;
            }
        }
        return $rows;
    }

    /**
     * The blocks of a container of OpenDocument (the body of a text, a frame of a slide): titles, paragraphs, lists,
     * tables and images, in their order.
     *
     * @param \DOMXPath $xpath Part.
     * @param \DOMNode $container Container.
     * @param array $blocks Blocks, added to.
     */
    protected static function odf_blocks(\DOMXPath $xpath, \DOMNode $container, array &$blocks): void {
        foreach ($container->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE || count($blocks) > self::MAX_BLOCKS) {
                continue;
            }
            switch ($node->localName) {
                case 'h':
                    $text = self::clean(self::odf_text($node));
                    if ($text !== '') {
                        $level = (int) ($node->getAttributeNS(self::NS['text'], 'outline-level') ?: 1);
                        $blocks[] = self::heading($level, $text);
                    }
                    break;
                case 'p':
                    foreach ($xpath->query('.//draw:frame[draw:image]', $node) as $frame) {
                        $alt = $xpath->evaluate('string(svg:title)', $frame) ?: $xpath->evaluate('string(svg:desc)', $frame);
                        $alt = self::clean($alt);
                        $blocks[] = ['type' => 'image', 'alt' => $alt];
                    }
                    $text = self::clean(self::odf_text($node));
                    if ($text !== '') {
                        $blocks[] = ['type' => 'paragraph', 'text' => $text];
                    }
                    break;
                case 'list':
                    foreach ($xpath->query('.//text:list-item/text:p | .//text:list-item/text:h', $node) as $item) {
                        $text = self::clean(self::odf_text($item));
                        if ($text !== '') {
                            $blocks[] = ['type' => 'item', 'text' => $text];
                        }
                    }
                    break;
                case 'table':
                    if ($table = self::table(self::odf_rows($xpath, $node))) {
                        $blocks[] = $table;
                    }
                    break;
                case 'section':
                case 'frame':
                case 'text-box':
                    self::odf_blocks($xpath, $node, $blocks);
                    break;
            }
        }
    }

    /**
     * A text of LibreOffice Writer (OpenDocument).
     *
     * @param callable $read Reads a part of the document.
     * @return array|null
     */
    protected static function odt(callable $read): ?array {
        $content = $read('content.xml');
        $body = $content?->query('//office:body/office:text')->item(0);
        if (!$body) {
            return null;
        }
        $blocks = [];
        self::odf_blocks($content, $body, $blocks);
        return $blocks;
    }

    /**
     * A presentation of LibreOffice Impress (OpenDocument): each slide, its title, its texts and tables.
     *
     * @param callable $read Reads a part of the document.
     * @return array|null
     */
    protected static function odp(callable $read): ?array {
        $content = $read('content.xml');
        if (!$content) {
            return null;
        }
        $blocks = [];
        $number = 0;
        foreach ($content->query('//office:presentation/draw:page') as $page) {
            $number++;
            $title = '';
            $inner = [];
            foreach ($content->query('draw:frame', $page) as $frame) {
                $class = $frame->getAttributeNS(self::NS['presentation'], 'class');
                if ($class === 'title' && $title === '') {
                    $title = self::clean($frame->textContent);
                } else if ($class !== 'notes') {
                    self::odf_blocks($content, $frame, $inner);
                }
            }
            $label = get_string('preview_slide', 'theme_epure', $number);
            $blocks[] = self::heading(1, $title === '' ? $label : $label . ' – ' . $title);
            array_push($blocks, ...$inner);
        }
        return $blocks;
    }

    /**
     * A spreadsheet of LibreOffice Calc (OpenDocument): each sheet as a table.
     *
     * @param callable $read Reads a part of the document.
     * @return array|null
     */
    protected static function ods(callable $read): ?array {
        $content = $read('content.xml');
        if (!$content) {
            return null;
        }
        $blocks = [];
        foreach ($content->query('//office:spreadsheet/table:table') as $sheet) {
            $name = self::clean($sheet->getAttributeNS(self::NS['table'], 'name'));
            $blocks[] = self::heading(1, $name);
            if ($table = self::table(self::odf_rows($content, $sheet), $name)) {
                $blocks[] = $table;
            }
        }
        return $blocks;
    }

    /**
     * The context of the template theme_epure/document_text: the items of a list grouped in it.
     *
     * @param array $text {blocks, truncated} of {@see read()}.
     * @return array
     */
    public static function export(array $text): array {
        $blocks = [];
        foreach ($text['blocks'] as $block) {
            if ($block['type'] === 'item') {
                $last = array_key_last($blocks);
                if ($last !== null && $blocks[$last]['type'] === 'list') {
                    $blocks[$last]['items'][] = ['text' => $block['text']];
                } else {
                    $blocks[] = ['type' => 'list', 'items' => [['text' => $block['text']]]];
                }
                continue;
            }
            $blocks[] = $block;
        }
        foreach ($blocks as &$block) {
            $block['is' . $block['type']] = true;
            if ($block['type'] === 'heading') {
                $block['tag'] = 'h' . $block['level'];
            } else if ($block['type'] === 'image') {
                $block['alttext'] = $block['alt'] === '' ? get_string('preview_imagenoalt', 'theme_epure')
                    : get_string('preview_image', 'theme_epure', $block['alt']);
            }
        }
        return ['blocks' => $blocks, 'truncated' => $text['truncated'], 'empty' => !$blocks];
    }
}
