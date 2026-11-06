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
 * Accessibility of the documents of an office suite given in a course (RGAA 13.3), read in their XML: the documents of
 * Word, PowerPoint and Excel (Office Open XML: .docx, .pptx, .xlsx, with macros or not) and of LibreOffice (OpenDocument:
 * .odt, .odp, .ods), and the PDF files.
 *
 * Read without a person: the images without a text alternative (neither a description nor marked decorative), the long
 * texts without any style of title and the levels of titles skipped, the tables without a repeated header row, the
 * slides without a title, the sheets left with their name by default, the title of the document in its properties;
 * for a PDF, its language and whether it is only scanned images. What a person still has to confirm (the quality of
 * the descriptions, the order of reading, the contrasts) stays to confirm in the guide.
 *
 * The old binary formats (.doc, .xls, .ppt) cannot be read. What is found is kept by content of file.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document_check {
    /** @var int Size above which a file is not opened. */
    public const MAX_SIZE = 31457280;

    /** @var int Size above which a part of a document (its XML) is not read. */
    protected const MAX_PART = 20971520;

    /** @var int Words from which a text without any title is hard to go through with a screen reader. */
    protected const LONG_TEXT = 400;

    /** @var string Names that the office suites give to the sheets by default. */
    protected const DEFAULT_SHEETS = '~^(sheet|feuil|feuille|tabelle|hoja|foglio|folha|blad|arkusz)\s*\d+$~iu';

    /** @var array<string, string> Namespaces of the XML of the documents. */
    protected const NS = [
        'w' => 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
        'wp' => 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
        'a' => 'http://schemas.openxmlformats.org/drawingml/2006/main',
        'p' => 'http://schemas.openxmlformats.org/presentationml/2006/main',
        'xdr' => 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing',
        's' => 'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
        'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
        'table' => 'urn:oasis:names:tc:opendocument:xmlns:table:1.0',
        'draw' => 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0',
        'svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
        'presentation' => 'urn:oasis:names:tc:opendocument:xmlns:presentation:1.0',
    ];

    /**
     * The kind of a type of file that can be read: word, slides, sheets (Office Open XML), odt, odp, ods (OpenDocument),
     * pdf; null for the others (the old binary formats among them).
     *
     * @param string $mimetype Type of the file.
     * @return string|null
     */
    public static function kind(string $mimetype): ?string {
        if ($mimetype === 'application/pdf') {
            return 'pdf';
        }
        $kinds = [
            '~^application/vnd\.openxmlformats-officedocument\.wordprocessingml\.~' => 'word',
            '~^application/vnd\.ms-word\.(document|template)\.macroenabled~i' => 'word',
            '~^application/vnd\.openxmlformats-officedocument\.presentationml\.~' => 'slides',
            '~^application/vnd\.ms-powerpoint\.(presentation|slideshow|template)\.macroenabled~i' => 'slides',
            '~^application/vnd\.openxmlformats-officedocument\.spreadsheetml\.~' => 'sheets',
            '~^application/vnd\.ms-excel\.(sheet|template)\.macroenabled~i' => 'sheets',
            '~^application/vnd\.oasis\.opendocument\.text~' => 'odt',
            '~^application/vnd\.oasis\.opendocument\.presentation~' => 'odp',
            '~^application/vnd\.oasis\.opendocument\.spreadsheet~' => 'ods',
        ];
        foreach ($kinds as $pattern => $kind) {
            if (preg_match($pattern, $mimetype)) {
                return $kind;
            }
        }
        return null;
    }

    /**
     * What is found in a file, from the cache or read: the count of each mistake.
     *
     * @param \stored_file $file File.
     * @return array|null Counts by mistake (empty: nothing found); null when it cannot be read.
     */
    public static function check(\stored_file $file): ?array {
        $kind = self::kind((string) $file->get_mimetype());
        if ($kind === null || $file->get_filesize() > self::MAX_SIZE) {
            return null;
        }
        $cache = \cache::make('theme_epure', 'documentcheck');
        $known = $cache->get($file->get_contenthash());
        if ($known !== false) {
            return $known === 'unreadable' ? null : $known;
        }
        try {
            $found = $kind === 'pdf' ? self::check_pdf($file->get_content()) : self::check_package($file, $kind);
        } catch (\Throwable $e) {
            // Expected: a damaged or protected file; it stays to confirm by a person.
            $found = null;
        }
        $cache->set($file->get_contenthash(), $found ?? 'unreadable');
        return $found;
    }

    /**
     * Reads a document of an office suite, a ZIP archive of XML.
     *
     * @param \stored_file $file File.
     * @param string $kind word, slides, sheets, odt, odp or ods.
     * @return array|null
     */
    protected static function check_package(\stored_file $file, string $kind): ?array {
        $path = $file->copy_content_to_temp('theme_epure');
        if ($path === false) {
            return null;
        }
        $zip = new \ZipArchive();
        try {
            if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
                return null;
            }
            $parts = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat && str_ends_with($stat['name'], '.xml') && $stat['size'] <= self::MAX_PART) {
                    $parts[$stat['name']] = fn() => (string) $zip->getFromIndex($stat['index']);
                }
            }
            $read = fn(string $name): ?\DOMXPath => isset($parts[$name]) ? self::xpath($parts[$name]()) : null;
            $found = match ($kind) {
                'word' => self::check_word($read),
                'slides' => self::check_slides($read, array_keys($parts)),
                'sheets' => self::check_sheets($read, array_keys($parts)),
                default => self::check_opendocument($read, $kind),
            };
            $zip->close();
            return $found === null ? null : array_filter($found);
        } finally {
            @unlink($path);
        }
    }

    /**
     * An XML parsed, with the namespaces of the documents.
     *
     * @param string $xml XML.
     * @return \DOMXPath|null
     */
    protected static function xpath(string $xml): ?\DOMXPath {
        if ($xml === '') {
            return null;
        }
        $doc = new \DOMDocument();
        $errors = libxml_use_internal_errors(true);
        // Without the network and the entities: a document is never trusted.
        $loaded = $doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        libxml_clear_errors();
        libxml_use_internal_errors($errors);
        if (!$loaded) {
            return null;
        }
        $xpath = new \DOMXPath($doc);
        foreach (self::NS as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }
        return $xpath;
    }

    /**
     * Whether the properties of a document of Office Open XML have no title.
     *
     * @param callable $read Reads a part of the document.
     * @return int 1 without a title.
     */
    protected static function no_title(callable $read): int {
        $core = $read('docProps/core.xml');
        return $core && trim((string) $core->evaluate('string(//dc:title)')) !== '' ? 0 : 1;
    }

    /**
     * Counts the images of a part of Office Open XML without a text alternative: neither a description nor a title,
     * nor marked decorative (Office 2019 and after).
     *
     * @param \DOMXPath $xpath Part.
     * @param string $query Elements of the properties of the images (cNvPr, docPr).
     * @return int
     */
    protected static function images_without_alt(\DOMXPath $xpath, string $query): int {
        $count = 0;
        foreach ($xpath->query($query) as $props) {
            $text = trim($props->getAttribute('descr') . $props->getAttribute('title'));
            $decorative = $xpath->query('.//*[local-name()="decorative" and (@val="1" or @val="true")]', $props)->length;
            if ($text === '' && !$decorative) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Counts the words of a text, in any language.
     *
     * @param string $text Text.
     * @return int
     */
    protected static function words(string $text): int {
        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Counts the levels of titles skipped in a list of levels.
     *
     * @param int[] $levels Levels, in their order.
     * @return int
     */
    protected static function skipped(array $levels): int {
        $count = 0;
        $previous = 0;
        foreach ($levels as $level) {
            if ($previous && $level > $previous + 1) {
                $count++;
            }
            $previous = $level;
        }
        return $count;
    }

    /**
     * Reads a document of Word.
     *
     * @param callable $read Reads a part of the document.
     * @return array|null
     */
    protected static function check_word(callable $read): ?array {
        $doc = $read('word/document.xml');
        if (!$doc) {
            return null;
        }
        // The styles of title: by their level of outline, or by their name (« heading 1 », whatever the language).
        $levels = [];
        $styles = $read('word/styles.xml');
        if ($styles) {
            foreach ($styles->query('//w:style[@w:type="paragraph"]') as $style) {
                $id = $style->getAttributeNS(self::NS['w'], 'styleId');
                $outline = $styles->evaluate('string(w:pPr/w:outlineLvl/@w:val)', $style);
                $name = $styles->evaluate('string(w:name/@w:val)', $style);
                if ($outline !== '' && (int) $outline < 9) {
                    $levels[$id] = (int) $outline + 1;
                } else if (preg_match('~^heading\s*(\d)$~i', $name, $m)) {
                    $levels[$id] = (int) $m[1];
                }
            }
        }
        $headings = [];
        foreach ($doc->query('//w:body//w:p') as $paragraph) {
            $outline = $doc->evaluate('string(w:pPr/w:outlineLvl/@w:val)', $paragraph);
            $style = $doc->evaluate('string(w:pPr/w:pStyle/@w:val)', $paragraph);
            if ($outline !== '' && (int) $outline < 9) {
                $headings[] = (int) $outline + 1;
            } else if (isset($levels[$style])) {
                $headings[] = $levels[$style];
            }
        }
        $words = self::words($doc->evaluate('string(//w:body)'));
        // A table of data repeats its first row as a header; a table of layout has no header to give, but Word cannot
        // tell them apart: a table of one row or one column is taken for layout.
        $tables = 0;
        foreach ($doc->query('//w:tbl') as $table) {
            $rows = $doc->query('w:tr', $table);
            $columns = $rows->length ? $doc->query('w:tc', $rows->item(0))->length : 0;
            if ($rows->length > 1 && $columns > 1 && !$doc->query('w:tr[1]/w:trPr/w:tblHeader', $table)->length) {
                $tables++;
            }
        }
        return [
            'docimages' => self::images_without_alt($doc, '//wp:docPr'),
            'docnoheadings' => !$headings && $words >= self::LONG_TEXT ? 1 : 0,
            'docheadings' => self::skipped($headings),
            'doctables' => $tables,
            'doctitle' => self::no_title($read),
        ];
    }

    /**
     * Reads a presentation of PowerPoint.
     *
     * @param callable $read Reads a part of the document.
     * @param string[] $names Parts of the document.
     * @return array|null
     */
    protected static function check_slides(callable $read, array $names): ?array {
        $slides = preg_grep('~^ppt/slides/slide\d+\.xml$~', $names);
        if (!$slides) {
            return null;
        }
        $found = ['docimages' => 0, 'slidetitles' => 0, 'doctitle' => self::no_title($read)];
        foreach ($slides as $name) {
            $slide = $read($name);
            if (!$slide) {
                continue;
            }
            $found['docimages'] += self::images_without_alt($slide, '//p:pic/p:nvPicPr/p:cNvPr');
            // A title: the placeholder of the title of the slide, with a text.
            $title = $slide->evaluate('string(//p:sp[p:nvSpPr/p:nvPr/p:ph[@type="title" or @type="ctrTitle"]]//a:t)');
            if (trim($title) === '') {
                $found['slidetitles']++;
            }
        }
        return $found;
    }

    /**
     * Reads a workbook of Excel.
     *
     * @param callable $read Reads a part of the document.
     * @param string[] $names Parts of the document.
     * @return array|null
     */
    protected static function check_sheets(callable $read, array $names): ?array {
        $workbook = $read('xl/workbook.xml');
        if (!$workbook) {
            return null;
        }
        $found = ['docimages' => 0, 'sheetnames' => 0, 'doctitle' => self::no_title($read)];
        foreach ($workbook->query('//s:sheets/s:sheet') as $sheet) {
            if (preg_match(self::DEFAULT_SHEETS, trim($sheet->getAttribute('name')))) {
                $found['sheetnames']++;
            }
        }
        // The images and the charts of the drawings of the sheets.
        foreach (preg_grep('~^xl/drawings/drawing\d+\.xml$~', $names) as $name) {
            $drawing = $read($name);
            if ($drawing) {
                $found['docimages'] += self::images_without_alt($drawing, '//xdr:pic/xdr:nvPicPr/xdr:cNvPr | '
                    . '//xdr:graphicFrame/xdr:nvGraphicFramePr/xdr:cNvPr');
            }
        }
        return $found;
    }

    /**
     * Reads a document of LibreOffice (OpenDocument).
     *
     * @param callable $read Reads a part of the document.
     * @param string $kind odt, odp or ods.
     * @return array|null
     */
    protected static function check_opendocument(callable $read, string $kind): ?array {
        $content = $read('content.xml');
        if (!$content) {
            return null;
        }
        $meta = $read('meta.xml');
        $found = [
            // An image (draw:image in a frame) without a title nor a description.
            'docimages' => $content->query('//draw:frame[draw:image][not(normalize-space(svg:title)) '
                . 'and not(normalize-space(svg:desc))]')->length,
            'doctitle' => $meta && trim((string) $meta->evaluate('string(//dc:title)')) !== '' ? 0 : 1,
        ];
        if ($kind === 'odt') {
            $levels = [];
            foreach ($content->query('//office:text//text:h') as $heading) {
                $levels[] = max(1, (int) ($heading->getAttributeNS(self::NS['text'], 'outline-level') ?: 1));
            }
            $words = self::words($content->evaluate('string(//office:text)'));
            $found['docnoheadings'] = !$levels && $words >= self::LONG_TEXT ? 1 : 0;
            $found['docheadings'] = self::skipped($levels);
        } else if ($kind === 'odp') {
            $found['slidetitles'] = 0;
            foreach ($content->query('//draw:page') as $page) {
                $title = $content->evaluate('string(.//draw:frame[@presentation:class="title"])', $page);
                if (trim($title) === '') {
                    $found['slidetitles']++;
                }
            }
        } else {
            $found['sheetnames'] = 0;
            foreach ($content->query('//office:spreadsheet/table:table') as $sheet) {
                if (preg_match(self::DEFAULT_SHEETS, trim($sheet->getAttributeNS(self::NS['table'], 'name')))) {
                    $found['sheetnames']++;
                }
            }
        }
        return $found;
    }

    /**
     * Reads a PDF file: its language (written plainly or in a compressed stream of objects), and whether it is only
     * scanned images, without any font and so without any text.
     *
     * @param string $pdf Content of the file.
     * @return array|null
     */
    public static function check_pdf(string $pdf): ?array {
        if (!str_starts_with(ltrim(substr($pdf, 0, 1024)), '%PDF-') || preg_match('~/Encrypt[\s/<\d]~', $pdf)) {
            return null;
        }
        $text = $pdf;
        $offset = 0;
        while (($position = strpos($pdf, '/ObjStm', $offset)) !== false) {
            $offset = $position + 7;
            if (!preg_match('~stream\r?\n~', $pdf, $match, PREG_OFFSET_CAPTURE, $offset)) {
                break;
            }
            $start = $match[0][1] + strlen($match[0][0]);
            $end = strpos($pdf, 'endstream', $start);
            if ($end === false) {
                break;
            }
            $offset = $end;
            $objects = @gzuncompress(substr($pdf, $start, $end - $start));
            if ($objects !== false) {
                $text .= $objects;
            }
        }
        $images = preg_match('~/Subtype\s*/Image~', $text);
        return array_filter([
            'pdflang' => preg_match('~/Lang\s*\(\s*[A-Za-z]~', $text) || preg_match('~/Lang\s*<~', $text) ? 0 : 1,
            'pdfscanned' => $images && !preg_match('~/Font\b~', $text) ? 1 : 0,
        ]);
    }
}
