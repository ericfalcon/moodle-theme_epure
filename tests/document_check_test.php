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
     * The colours and the languages written on the texts of a document of Word, a presentation and a text of
     * LibreOffice: a text too little contrasted is a mistake, a passage in another language than the one marked on it
     * is to confirm; the colour « auto » on a dark shading, a white text on a dark shape, a passage marked in its
     * language, are right.
     */
    public function test_colours_languages(): void {
        $this->resetAfterTest();
        $english = 'The safety rules of the site are what you have to read first, and they are the same for all of us.';
        $french = 'Les règles de sécurité du chantier sont à lire en premier, et elles sont les mêmes pour tous.';
        // Word: a yellow text on white, an English paragraph in a French document; « auto » on a dark shading, an
        // English paragraph marked English.
        $w = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
        $run = fn(string $text, string $props = '') => '<w:r><w:rPr>' . $props . '</w:rPr><w:t>' . $text . '</w:t></w:r>';
        $body = '<w:p>' . $run('Attention', '<w:color w:val="FFFF00"/>') . '</w:p>'
            . '<w:p>' . $run($english) . '</w:p>'
            . '<w:p><w:pPr><w:shd w:fill="1E3A8A"/></w:pPr>' . $run('Texte', '<w:color w:val="auto"/>') . '</w:p>'
            . '<w:p>' . $run($english, '<w:lang w:val="en-GB"/>') . '</w:p>'
            . '<w:p>' . $run($french) . '</w:p>';
        $styles = '<w:styles ' . $w . '><w:docDefaults><w:rPrDefault><w:rPr><w:lang w:val="fr-FR"/></w:rPr></w:rPrDefault>'
            . '</w:docDefaults></w:styles>';
        $file = $this->package('couleurs.docx', [
            '[Content_Types].xml' => '<Types/>',
            'word/document.xml' => '<w:document ' . $w . '><w:body>' . $body . '</w:body></w:document>',
            'word/styles.xml' => $styles,
            'docProps/core.xml' => $this->core('Couleurs'),
        ]);
        $this->assertSame(['doccontrast' => 1, 'doclang' => 1], document_check::check($file));

        // PowerPoint: a light grey text on a white shape, an English paragraph marked French; a white text on a dark
        // shape.
        $ns = 'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"';
        $shape = fn(string $fill, string $colour, string $text) => '<p:sp><p:spPr><a:solidFill><a:srgbClr val="' . $fill
            . '"/></a:solidFill></p:spPr><p:txBody><a:p><a:r><a:rPr lang="fr-FR"><a:solidFill><a:srgbClr val="' . $colour
            . '"/></a:solidFill></a:rPr><a:t>' . $text . '</a:t></a:r></a:p></p:txBody></p:sp>';
        $title = '<p:sp><p:nvSpPr><p:cNvPr id="1" name="Titre"/><p:cNvSpPr/><p:nvPr><p:ph type="title"/></p:nvPr></p:nvSpPr>'
            . '<p:txBody><a:p><a:r><a:t>Les risques</a:t></a:r></a:p></p:txBody></p:sp>';
        $slide = '<p:sld ' . $ns . '><p:cSld><p:spTree>' . $title . $shape('FFFFFF', 'D3D3D3', 'Note')
            . $shape('FFFFFF', '111418', $english) . $shape('1E3A8A', 'FFFFFF', 'Blanc') . '</p:spTree></p:cSld></p:sld>';
        $file = $this->package('couleurs.pptx', ['[Content_Types].xml' => '<Types/>', 'ppt/slides/slide1.xml' => $slide,
            'docProps/core.xml' => $this->core('Cours')]);
        $this->assertSame(['doccontrast' => 1, 'doclang' => 1], document_check::check($file));

        // LibreOffice Writer: a span in light grey, an English paragraph in a French document.
        $o = 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
            . 'xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" '
            . 'xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"';
        $content = '<office:document-content ' . $o . '><office:automatic-styles><style:style style:name="T1" '
            . 'style:family="text"><style:text-properties fo:color="#d3d3d3"/></style:style></office:automatic-styles>'
            . '<office:body><office:text><text:h text:outline-level="1">Titre</text:h><text:p>Un <text:span text:style-name="T1">'
            . 'mot</text:span></text:p><text:p>' . $english . '</text:p><text:p>' . $french . '</text:p></office:text>'
            . '</office:body></office:document-content>';
        $styles = '<office:document-styles ' . $o . '><office:styles><style:default-style style:family="paragraph">'
            . '<style:text-properties fo:language="fr" fo:country="FR"/></style:default-style></office:styles>'
            . '</office:document-styles>';
        $meta = '<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/"><office:meta><dc:title>Texte</dc:title></office:meta>'
            . '</office:document-meta>';
        $file = $this->package('couleurs.odt', ['mimetype' => 'application/vnd.oasis.opendocument.text',
            'content.xml' => $content, 'styles.xml' => $styles, 'meta.xml' => $meta]);
        $this->assertSame(['doccontrast' => 1, 'doclang' => 1], document_check::check($file));
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

        // A language in a compressed stream of objects is found.
        $stream = gzcompress('<< /Lang (fr-FR) >> /Font');
        $this->assertSame([], document_check::check_pdf("%PDF-1.5\n<< /Type /ObjStm >>\nstream\n{$stream}endstream\n%%EOF"));
        // A stream that unfolds beyond the limit (a « zip bomb » of a few kilobytes) is not uncompressed: the PDF is read
        // without it, at no cost.
        $bomb = gzcompress(str_repeat('0', 2 * document_check::MAX_STREAM) . '/Lang (fr-FR)', 9);
        $this->assertLessThan(100000, strlen($bomb));
        $this->assertSame(
            ['pdflang' => 1],
            document_check::check_pdf("%PDF-1.5\n<< /Type /ObjStm >>\nstream\n{$bomb}endstream\n%%EOF")
        );
        $this->assertFalse(course_accessibility::pdf_content_tagged(
            "%PDF-1.5\n<< /Type /ObjStm >>\nstream\n" . gzcompress(str_repeat('0', 2 * document_check::MAX_STREAM)
            . '/StructTreeRoot', 9) . "endstream\n%%EOF"
        ));
    }

    /**
     * An archive of too many entries is not read; the XML read in one document stays within a budget, whatever the size
     * the parts declare.
     */
    public function test_limits(): void {
        $this->resetAfterTest();
        // The limits made small, by a class that redefines them.
        $small = new class extends document_check {
            /** @var int Entries at most. */
            protected const MAX_ENTRIES = 5;
            /** @var int XML read at most in one document. */
            protected const MAX_READ = 2000;
        };
        $slide = '<p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">' . str_repeat('<a/>', 300)
            . '</p:sld>';
        $parts = ['docProps/core.xml' => $this->core('Rules')];
        for ($i = 1; $i <= 6; $i++) {
            $parts['ppt/slides/slide' . $i . '.xml'] = $slide;
        }
        // Too many entries: not read.
        $this->assertNull($small::check($this->package('many.pptx', $parts)));
        // Within the entries, the parts beyond the budget are left out, without an error.
        // Within the entries, the parts beyond the budget are left out, without an error: the properties and one slide
        // are read (four slides without a title in full).
        $few = array_slice($parts, 0, 5, true);
        $this->assertSame(['slidetitles' => 1], $small::check($this->package('heavy.pptx', $few)));
        \cache::make('theme_epure', 'documentcheck')->purge();
        $this->assertSame(['slidetitles' => 4], document_check::check($this->package('heavy2.pptx', $few)));
    }

    /**
     * A file replaced by another of the same name is read again: what is found is kept by content, not by name.
     */
    public function test_replaced(): void {
        $this->resetAfterTest();
        $body = ['word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body><w:p><w:r><w:t>Rules</w:t></w:r></w:p></w:body></w:document>'];
        $first = $this->package('rules.docx', $body + ['docProps/core.xml' => $this->core('')]);
        $this->assertSame(['doctitle' => 1], document_check::check($first));
        $first->delete();
        $second = $this->package('rules.docx', $body + ['docProps/core.xml' => $this->core('Rules')]);
        $this->assertSame([], document_check::check($second));
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
