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
 * Tests for the text of the documents of an office suite in the preview of the files.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\document_text
 * @covers     \theme_epure\external\document_text
 */
final class document_text_test extends \advanced_testcase {
    /** @var string Namespaces of Word. */
    protected const W = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
        . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"';

    /**
     * A file stored in the context of the system, from its parts.
     *
     * @param string $filename Name of the file.
     * @param array $parts Parts of the archive, by name: their content.
     * @return \stored_file
     */
    protected function package(string $filename, array $parts): \stored_file {
        $path = make_request_directory() . '/' . $filename;
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        foreach ($parts as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        return get_file_storage()->create_file_from_pathname(
            ['contextid' => \context_system::instance()->id,
            'component' => 'theme_epure', 'filearea' => 'test', 'itemid' => 0, 'filepath' => '/', 'filename' => $filename],
            $path
        );
    }

    /**
     * A document of Word with a title, a paragraph, a list, an image and a table.
     *
     * @return array Its parts.
     */
    public static function word_parts(): array {
        $styles = '<w:styles ' . self::W . '><w:style w:type="paragraph" w:styleId="Titre1"><w:name w:val="heading 1"/>'
            . '</w:style></w:styles>';
        $row = fn($a, $b) => '<w:tr><w:tc><w:p><w:r><w:t>' . $a . '</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>' . $b
            . '</w:t></w:r></w:p></w:tc></w:tr>';
        $body = '<w:p><w:pPr><w:pStyle w:val="Titre1"/></w:pPr><w:r><w:t>Consignes de sécurité</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t xml:space="preserve">Lisez-les   avant</w:t></w:r><w:r><w:br/><w:t>la formation.</w:t></w:r></w:p>'
            . '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Casque</w:t></w:r></w:p>'
            . '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Gants</w:t></w:r></w:p>'
            . '<w:p><w:r><w:drawing><wp:inline><wp:docPr id="1" name="Image 1" descr="Plan des sorties"/></wp:inline>'
            . '</w:drawing></w:r></w:p>'
            . '<w:tbl>' . $row('Salle', 'Sortie') . $row('A', 'Nord') . '</w:tbl>';
        return [
            '[Content_Types].xml' => '<Types/>',
            'word/document.xml' => '<w:document ' . self::W . '><w:body>' . $body . '</w:body></w:document>',
            'word/styles.xml' => $styles,
        ];
    }

    /**
     * Word: titles under the title of the preview, paragraphs with their line breaks, lists, images, tables; escaped.
     */
    public function test_word(): void {
        $this->resetAfterTest();
        $file = $this->package('consignes.docx', self::word_parts());
        $this->assertTrue(document_text::supported($file->get_mimetype()));
        $this->assertSame('document', file_preview::kind($file->get_mimetype()));
        $text = document_text::read($file);
        $this->assertFalse($text['truncated']);
        $types = array_column($text['blocks'], 'type');
        $this->assertSame(['heading', 'paragraph', 'item', 'item', 'image', 'table'], $types);
        $this->assertSame(['type' => 'heading', 'level' => 3, 'text' => 'Consignes de sécurité'], $text['blocks'][0]);
        $this->assertSame("Lisez-les avant\nla formation.", $text['blocks'][1]['text']);
        $this->assertSame('Plan des sorties', $text['blocks'][4]['alt']);
        $table = $text['blocks'][5];
        $this->assertSame(['Salle', 'Sortie'], array_column($table['header']['cells'], 'text'));
        $this->assertSame(['A', 'Nord'], array_column($table['rows'][0]['cells'], 'text'));

        $export = document_text::export($text);
        $this->assertSame('list', $export['blocks'][2]['type']);
        $this->assertSame(['Casque', 'Gants'], array_column($export['blocks'][2]['items'], 'text'));
        $html = $this->get_renderer()->render_from_template('theme_epure/document_text', $export);
        $this->assertStringContainsString('<h3 class="epure-doctext-heading">Consignes de sécurité</h3>', $html);
        $this->assertStringContainsString('<th scope="col">Salle</th>', $html);

        // The text of a document is never HTML.
        $parts = self::word_parts();
        $parts['word/document.xml'] = '<w:document ' . self::W . '><w:body><w:p><w:r><w:t>&lt;script&gt;alert(1)&lt;/script&gt;'
            . '</w:t></w:r></w:p></w:body></w:document>';
        $text = document_text::read($this->package('piege.docx', $parts));
        $html = $this->get_renderer()->render_from_template('theme_epure/document_text', document_text::export($text));
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * PowerPoint: the slides in the order of the presentation, their title, texts and tables.
     */
    public function test_slides(): void {
        $this->resetAfterTest();
        $ns = 'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $presentation = '<p:presentation ' . $ns . '><p:sldIdLst><p:sldId id="256" r:id="rId3"/><p:sldId id="257" r:id="rId2"/>'
            . '</p:sldIdLst></p:presentation>';
        $rels = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId2" Target="slides/slide1.xml"/><Relationship Id="rId3" Target="slides/slide2.xml"/>'
            . '</Relationships>';
        $shape = fn($type, $text) => '<p:sp><p:nvSpPr><p:cNvPr id="1" name="x"/><p:cNvSpPr/><p:nvPr>'
            . ($type ? '<p:ph type="' . $type . '"/>' : '') . '</p:nvPr></p:nvSpPr><p:txBody><a:p><a:r><a:t>' . $text
            . '</a:t></a:r></a:p></p:txBody></p:sp>';
        $slide = fn($content) => '<p:sld ' . $ns . '><p:cSld><p:spTree>' . $content . '</p:spTree></p:cSld></p:sld>';
        $file = $this->package('cours.pptx', [
            '[Content_Types].xml' => '<Types/>',
            'ppt/presentation.xml' => $presentation,
            'ppt/_rels/presentation.xml.rels' => $rels,
            'ppt/slides/slide1.xml' => $slide($shape('title', 'Les risques') . $shape('', 'Chute, bruit')),
            'ppt/slides/slide2.xml' => $slide($shape('ctrTitle', 'Bienvenue')),
        ]);
        $blocks = document_text::read($file)['blocks'];
        $this->assertSame(get_string('preview_slide', 'theme_epure', 1) . ' – Bienvenue', $blocks[0]['text']);
        $this->assertSame(get_string('preview_slide', 'theme_epure', 2) . ' – Les risques', $blocks[1]['text']);
        $this->assertSame('Chute, bruit', $blocks[2]['text']);
    }

    /**
     * Excel: each sheet as a table, with shared and inline strings, numbers, and the empty cells in their place.
     */
    public function test_sheets(): void {
        $this->resetAfterTest();
        $s = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
        $workbook = '<workbook ' . $s . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Notes" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $rels = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Target="/xl/worksheets/sheet1.xml"/></Relationships>';
        $shared = '<sst ' . $s . '><si><t>Nom</t></si><si><t>Note</t></si><si><r><t>Ca</t></r><r><t>mille</t></r></si></sst>';
        $sheet = '<worksheet ' . $s . '><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="C1" t="s"><v>1</v></c></row>'
            . '<row r="2"><c r="A2" t="s"><v>2</v></c><c r="B2" t="inlineStr"><is><t>B</t></is></c><c r="C2"><v>15.5</v></c></row>'
            . '</sheetData></worksheet>';
        $file = $this->package('notes.xlsx', ['[Content_Types].xml' => '<Types/>', 'xl/workbook.xml' => $workbook,
            'xl/_rels/workbook.xml.rels' => $rels, 'xl/sharedStrings.xml' => $shared, 'xl/worksheets/sheet1.xml' => $sheet]);
        $blocks = document_text::read($file)['blocks'];
        $this->assertSame('Notes', $blocks[0]['text']);
        $this->assertSame('Notes', $blocks[1]['caption']);
        $this->assertSame(['Nom', '', 'Note'], array_column($blocks[1]['header']['cells'], 'text'));
        $this->assertSame(['Camille', 'B', '15.5'], array_column($blocks[1]['rows'][0]['cells'], 'text'));
    }

    /**
     * LibreOffice: a text (titles, paragraphs, a list, a table), a presentation and a spreadsheet.
     */
    public function test_opendocument(): void {
        $this->resetAfterTest();
        $ns = 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
            . 'xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" '
            . 'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" '
            . 'xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0"';
        $table = '<table:table table:name="T"><table:table-row><table:table-cell><text:p>Salle</text:p></table:table-cell>'
            . '<table:table-cell><text:p>Sortie</text:p></table:table-cell><table:table-cell table:number-columns-repeated="1000"/>'
            . '</table:table-row><table:table-row><table:table-cell><text:p>A</text:p></table:table-cell><table:table-cell>'
            . '<text:p>Nord</text:p></table:table-cell></table:table-row>'
            . '<table:table-row table:number-rows-repeated="1000000"><table:table-cell/></table:table-row></table:table>';
        $odt = '<office:document-content ' . $ns . '><office:body><office:text><text:h text:outline-level="2">Partie</text:h>'
            . '<text:p>Un<text:s/>texte<text:line-break/>suivi.</text:p><text:list><text:list-item><text:p>Casque</text:p>'
            . '</text:list-item></text:list>' . $table . '</office:text></office:body></office:document-content>';
        $blocks = document_text::read($this->package('guide.odt', ['content.xml' => $odt]))['blocks'];
        $this->assertSame(['heading', 'paragraph', 'item', 'table'], array_column($blocks, 'type'));
        $this->assertSame(4, $blocks[0]['level']);
        $this->assertSame("Un texte\nsuivi.", $blocks[1]['text']);
        $this->assertSame(['Salle', 'Sortie'], array_column($blocks[3]['header']['cells'], 'text'));
        $this->assertCount(1, $blocks[3]['rows'], 'The empty rows repeated to the end of the sheet are not rows.');

        $odp = '<office:document-content ' . $ns . '><office:body><office:presentation><draw:page draw:name="p1">'
            . '<draw:frame presentation:class="title"><draw:text-box><text:p>Bienvenue</text:p></draw:text-box></draw:frame>'
            . '<draw:frame presentation:class="outline"><draw:text-box><text:p>Programme</text:p></draw:text-box></draw:frame>'
            . '</draw:page></office:presentation></office:body></office:document-content>';
        $blocks = document_text::read($this->package('cours.odp', ['content.xml' => $odp]))['blocks'];
        $this->assertSame(get_string('preview_slide', 'theme_epure', 1) . ' – Bienvenue', $blocks[0]['text']);
        $this->assertSame('Programme', $blocks[1]['text']);

        $ods = '<office:document-content ' . $ns . '><office:body><office:spreadsheet>' . $table
            . '</office:spreadsheet></office:body></office:document-content>';
        $blocks = document_text::read($this->package('notes.ods', ['content.xml' => $ods]))['blocks'];
        $this->assertSame('T', $blocks[0]['text']);
        $this->assertSame('table', $blocks[1]['type']);
    }

    /**
     * A damaged file cannot be read; the web service gives the text of the document of a resource to a learner.
     */
    public function test_web_service(): void {
        $this->resetAfterTest();
        $this->assertNull(document_text::read(get_file_storage()->create_file_from_string(
            ['contextid' => \context_system::instance()->id, 'component' => 'theme_epure', 'filearea' => 'test',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'abime.docx'],
            'not a zip'
        )));

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $learner = $generator->create_and_enrol($course, 'student');
        $source = $this->package('consignes.docx', self::word_parts());
        $draft = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_storedfile(
            ['contextid' => \context_user::instance(get_admin()->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draft, 'filepath' => '/', 'filename' => 'consignes.docx'],
            $source
        );
        $resource = $generator->create_module('resource', ['course' => $course->id, 'files' => $draft, 'display' => 5]);

        $this->setUser($learner);
        $items = file_preview::items($course);
        $this->assertSame('document', $items[0]['kind']);
        $result = \core_external\external_api::clean_returnvalue(
            external\document_text::execute_returns(),
            external\document_text::execute((int) $resource->cmid)
        );
        $this->assertStringContainsString('Consignes de sécurité', $result['html']);
    }

    /**
     * The text of a PDF (smalot/pdfparser): each page with its title, its lines joined into paragraphs; a PDF without
     * text says so; a damaged one cannot be read.
     */
    public function test_pdf(): void {
        global $CFG;
        require_once($CFG->libdir . '/pdflib.php');
        $this->resetAfterTest();
        $pdf = new \pdf();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->AddPage();
        $long = 'Lisez ces règles avant la formation. Elles s’appliquent à tous les stagiaires, dans tous les ateliers '
            . 'du centre, sans exception, et le formateur vérifie qu’elles sont connues de chacun avant de commencer.';
        $pdf->writeHTML('<p>' . $long . '</p><p>Fin de la page une.</p>');
        $pdf->AddPage();
        $pdf->writeHTML('<p>Deuxième page : le plan des sorties.</p>');
        $blocks = document_text::pdf($pdf->Output('', 'S'));

        $pages = array_values(array_filter($blocks, fn($block) => $block['type'] === 'heading'));
        $this->assertCount(2, $pages);
        $this->assertSame(get_string('preview_page', 'theme_epure', 2), $pages[1]['text']);
        $texts = array_column(array_filter($blocks, fn($block) => $block['type'] === 'paragraph'), 'text');
        // The lines of the long paragraph joined into one.
        $this->assertContains($long, $texts);
        $this->assertContains('Deuxième page : le plan des sorties.', $texts);

        // A PDF without text: the template says so.
        $export = document_text::export(['blocks' => [], 'truncated' => false, 'pdf' => true]);
        $this->assertTrue($export['nopdftext']);
        $this->assertFalse($export['empty']);
        $html = $this->get_renderer()->render_from_template('theme_epure/document_text', $export);
        $this->assertStringContainsString(get_string('preview_pdfnotext', 'theme_epure'), $html);

        // The lines of a page: an item of a list apart, a short line ending its paragraph.
        $this->assertSame([
            ['type' => 'paragraph', 'text' => 'Une ligne longue qui continue sur la suivante sans point'
                . ' et qui se termine ici.'],
            ['type' => 'item', 'text' => 'Premier point'],
            ['type' => 'paragraph', 'text' => 'Court'],
        ], (new \ReflectionMethod(document_text::class, 'pdf_paragraphs'))->invoke(null, [
            'Une ligne longue qui continue sur la suivante sans point',
            'et qui se termine ici.',
            '• Premier point',
            'Court',
        ]));
    }

    /**
     * The renderer of the pages.
     *
     * @return \renderer_base
     */
    protected function get_renderer(): \renderer_base {
        global $PAGE;
        return $PAGE->get_renderer('core');
    }
}
