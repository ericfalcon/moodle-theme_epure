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

#[\PHPUnit\Framework\Attributes\CoversClass(document_check::class)]
/**
 * Tests for the accessibility of the documents of an office suite and of the PDF files.
 *
 * @package    theme_epure
 * @category   test
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\document_check
 */
final class document_check_test extends \advanced_testcase {
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
     * The properties of a document of Office Open XML, with a title or not.
     *
     * @param string $title Title.
     * @return string
     */
    protected function core(string $title): string {
        return '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>' . $title . '</dc:title></cp:coreProperties>';
    }

    /**
     * A document of Word: images without a text alternative but the decorative and the described ones, no style of title
     * on a long text, a table of data without a header row, no title.
     */
    public function test_word(): void {
        $this->resetAfterTest();
        $w = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"';
        $images = '<wp:docPr id="1" name="Image 1"/><wp:docPr id="2" name="Image 2" descr="Le bouton Ajouter un travail"/>'
            . '<wp:docPr id="3" name="Image 3"><a:extLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            . '<a:ext><adec:decorative xmlns:adec="http://schemas.microsoft.com/office/drawing/2017/decorative" val="1"/>'
            . '</a:ext></a:extLst></wp:docPr>';
        $row = '<w:tr><w:tc><w:p/></w:tc><w:tc><w:p/></w:tc></w:tr>';
        $body = '<w:p><w:r><w:t>' . str_repeat('mot ', 450) . '</w:t></w:r></w:p>' . $images . '<w:tbl>' . $row . $row . '</w:tbl>';
        $file = $this->package('consignes.docx', [
            '[Content_Types].xml' => '<Types/>',
            'word/document.xml' => '<w:document ' . $w . '><w:body>' . $body . '</w:body></w:document>',
            'docProps/core.xml' => $this->core(''),
        ]);
        $this->assertSame('word', document_check::kind($file->get_mimetype()));
        $this->assertSame(
            ['docimages' => 1, 'docnoheadings' => 1, 'doctables' => 1, 'doctitle' => 1],
            document_check::check($file)
        );

        // With the styles of title (named « heading N » whatever the language of the style), in order, a header row and a
        // title: nothing.
        $styles = '<w:styles ' . $w . '><w:style w:type="paragraph" w:styleId="Titre1"><w:name w:val="heading 1"/></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Titre2"><w:name w:val="heading 2"/></w:style></w:styles>';
        $body = '<w:p><w:pPr><w:pStyle w:val="Titre1"/></w:pPr></w:p><w:p><w:pPr><w:pStyle w:val="Titre2"/></w:pPr></w:p>'
            . '<w:p><w:r><w:t>' . str_repeat('mot ', 450) . '</w:t></w:r></w:p>'
            . '<w:tbl><w:tr><w:trPr><w:tblHeader/></w:trPr><w:tc><w:p/></w:tc><w:tc><w:p/></w:tc></w:tr>' . $row . '</w:tbl>';
        $file = $this->package('regles.docx', [
            '[Content_Types].xml' => '<Types/>',
            'word/document.xml' => '<w:document ' . $w . '><w:body>' . $body . '</w:body></w:document>',
            'word/styles.xml' => $styles,
            'docProps/core.xml' => $this->core('Règles de sécurité'),
        ]);
        $this->assertSame([], document_check::check($file));
    }

    /**
     * A presentation: a slide without a title, an image without a text alternative.
     */
    public function test_slides(): void {
        $this->resetAfterTest();
        $ns = 'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"';
        $titled = '<p:sld ' . $ns . '><p:cSld><p:spTree><p:sp><p:nvSpPr><p:cNvPr id="1" name="Titre"/><p:cNvSpPr/>'
            . '<p:nvPr><p:ph type="title"/></p:nvPr></p:nvSpPr><p:txBody><a:p><a:r><a:t>Les risques</a:t></a:r></a:p>'
            . '</p:txBody></p:sp></p:spTree></p:cSld></p:sld>';
        $untitled = '<p:sld ' . $ns . '><p:cSld><p:spTree><p:pic><p:nvPicPr><p:cNvPr id="2" name="Image"/></p:nvPicPr>'
            . '</p:pic></p:spTree></p:cSld></p:sld>';
        $file = $this->package('cours.pptx', ['[Content_Types].xml' => '<Types/>', 'ppt/slides/slide1.xml' => $titled,
            'ppt/slides/slide2.xml' => $untitled, 'docProps/core.xml' => $this->core('Cours')]);
        $this->assertSame(['docimages' => 1, 'slidetitles' => 1], document_check::check($file));
    }

    /**
     * A workbook: a sheet left with its name by default, a chart without a text alternative.
     */
    public function test_sheets(): void {
        $this->resetAfterTest();
        $workbook = '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets>'
            . '<sheet name="Feuil1" sheetId="1"/><sheet name="Notes du groupe" sheetId="2"/></sheets></workbook>';
        $drawing = '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing"><xdr:twoCellAnchor>'
            . '<xdr:graphicFrame><xdr:nvGraphicFramePr><xdr:cNvPr id="2" name="Graphique 1"/></xdr:nvGraphicFramePr>'
            . '</xdr:graphicFrame></xdr:twoCellAnchor></xdr:wsDr>';
        $file = $this->package('notes.xlsm', ['[Content_Types].xml' => '<Types/>', 'xl/workbook.xml' => $workbook,
            'xl/drawings/drawing1.xml' => $drawing, 'docProps/core.xml' => $this->core('Notes')]);
        $this->assertSame('sheets', document_check::kind($file->get_mimetype()));
        $this->assertSame(['docimages' => 1, 'sheetnames' => 1], document_check::check($file));
    }

    /**
     * A text of LibreOffice: an image without a text alternative, a level of title skipped, no title.
     */
    public function test_opendocument(): void {
        $this->resetAfterTest();
        $content = '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
            . 'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" '
            . 'xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"><office:body><office:text>'
            . '<text:h text:outline-level="1">Partie</text:h><text:h text:outline-level="3">Détail</text:h>'
            . '<draw:frame><draw:image/></draw:frame><draw:frame><svg:title>Plan</svg:title><draw:image/></draw:frame>'
            . '</office:text></office:body></office:document-content>';
        $file = $this->package('guide.odt', ['mimetype' => 'application/vnd.oasis.opendocument.text', 'content.xml' => $content]);
        $this->assertSame(['docimages' => 1, 'doctitle' => 1, 'docheadings' => 1], document_check::check($file));
    }

    /**
     * A PDF file: its language, and whether it is only scanned images.
     */
    public function test_pdf(): void {
        $this->assertSame([], document_check::check_pdf("%PDF-1.7\n<< /Type /Catalog /Lang (fr-FR) >>\n/Font\n%%EOF"));
        $this->assertSame(
            ['pdflang' => 1, 'pdfscanned' => 1],
            document_check::check_pdf("%PDF-1.4\n<< /Type /Catalog >>\n<< /Subtype /Image >>\n%%EOF")
        );
        $this->assertNull(document_check::check_pdf('not a PDF'));
    }

    /**
     * The old binary formats, and a damaged file, cannot be read.
     */
    public function test_unreadable(): void {
        $this->resetAfterTest();
        $this->assertNull(document_check::kind('application/msword'));
        $file = get_file_storage()->create_file_from_string(
            ['contextid' => \context_system::instance()->id,
            'component' => 'theme_epure', 'filearea' => 'test', 'itemid' => 0, 'filepath' => '/', 'filename' => 'abime.docx'],
            'not a zip'
        );
        $this->assertNull(document_check::check($file));
    }
}
