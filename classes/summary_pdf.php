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
 * The PDF of « My summary », tagged for the screen readers (PDF/UA-1, ISO 14289-1): its titles, paragraphs, tables,
 * links and the QR code with its text are in the structure of the document, in the language of the page; the band of
 * the brand and the footer of each page are decorations (artifacts), which the screen readers skip. Made with
 * tc-lib-pdf (thirdparty/tclib), the successor of TCPDF, which needs PHP 8.2: with an older PHP, the PDF is made with
 * the TCPDF of Moodle, not tagged ({@see learner_summary_document::pdf()}).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary_pdf {
    /** @var string Font of the document: DejaVu Sans, for the letters of almost every language. */
    public const FONT = 'dejavusans';

    /**
     * Whether the PDF can be tagged: PHP 8.2 or later, and the library in the theme.
     *
     * @return bool
     */
    public static function available(): bool {
        return PHP_VERSION_ID >= 80200 && is_readable(self::autoload());
    }

    /**
     * The autoloader of the library.
     *
     * @return string
     */
    protected static function autoload(): string {
        global $CFG;
        return $CFG->dirroot . '/theme/epure/thirdparty/tclib/vendor/autoload.php';
    }

    /**
     * The language of the document, as PDF writes it (BCP 47): « fr », « en-US »…
     *
     * @param string $lang Language of Moodle (fr, en_us…).
     * @return string
     */
    public static function language(string $lang): string {
        $parts = explode('_', str_replace('-', '_', $lang));
        return strtolower($parts[0]) . (isset($parts[1]) ? '-' . strtoupper($parts[1]) : '');
    }

    /**
     * Makes the PDF.
     *
     * @param array $document title, author, creator, lang (of Moodle), brand (#RRGGBB), footer (code of verification),
     *     html (the body, in HTML), qrurl (address the QR code leads to), qralt (its text for the screen readers), logo
     *     (content of the image of the logo, or empty), logowidth and logoheight (in millimetres).
     * @return string Content of the PDF.
     */
    public static function render(array $document): string {
        require_once(self::autoload());
        // The folder of the cache of TCPDF, when the PDF library of Moodle was loaded in this request: the library uses
        // it too, and needs it to exist.
        if (defined('K_PATH_CACHE')) {
            check_dir_exists(constant('K_PATH_CACHE'));
        }
        // The images are read from a folder of this request only, which the library is allowed to read.
        $dir = make_request_directory();
        $qr = (new \Com\Tecnick\Barcode\Barcode())
            ->getBarcodeObj('QRCODE,M', $document['qrurl'], -4, -4, 'black', [0, 0, 0, 0])
            ->getPngData(false);
        file_put_contents($dir . '/qr.png', $qr);
        $logo = '';
        if (!empty($document['logo'])) {
            $info = getimagesizefromstring($document['logo']);
            $extension = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? '';
            if ($extension) {
                $logo = $dir . '/logo.' . $extension;
                file_put_contents($logo, $document['logo']);
            }
        }

        // The band of the brand and the footer of each page, as decorations.
        $options = [
            'unit' => \Com\Tecnick\Pdf\Page\Unit::Millimeter,
            'isunicode' => true,
            'subsetfont' => true,
            'compress' => true,
            'mode' => \Com\Tecnick\Pdf\PdfConformance::Pdfua1,
            'fileOptions' => ['allowedPaths' => [$dir]],
        ];
        $pdf = new class (...$options) extends \Com\Tecnick\Pdf\Tcpdf {
            /** @var string Code of verification, in the footer. */
            public string $epurefooter = '';
            /** @var string Colour of the band. */
            public string $epurebrand = '#1C6E73';
            /** @var array|null Font of the footer. */
            protected ?array $epurefont = null;

            /**
             * The decorations of a page: the band of the brand at the top, the code and the number of the page at the
             * bottom. The library names this method, in camel case.
             *
             * @param int $pid Page, the current one by default.
             * @return string
             */
            public function defaultPageContent(int $pid = -1): string { // phpcs:ignore moodle.NamingConventions
                if ($pid < 0) {
                    $pid = $this->page->getPageId();
                }
                $this->epurefont ??= $this->font->insert($this->pon, summary_pdf::FONT, '', 8);
                $page = $this->page->getPage($pid);
                $style = ['all' => ['fillColor' => $this->epurebrand, 'lineWidth' => 0]];
                return $this->beginArtifact('Layout')
                    . $this->graph->getRect(0, 0, $page['width'], 6, 'F', $style)
                    . $this->endArtifact()
                    . $this->beginArtifact('Pagination', 'Footer')
                    . $this->graph->getStartTransform() . $this->epurefont['out'] . $this->color->getPdfColor('#5f6873')
                    . $this->getTextCell($this->epurefooter . ' · ' . ($pid + 1), 18, $page['height'] - 14, $page['width'] - 36, 6)
                    . $this->graph->getStopTransform()
                    . $this->endArtifact();
            }
        };
        $pdf->epurefooter = (string) $document['footer'];
        $pdf->epurebrand = (string) $document['brand'];
        $pdf->setTitle((string) $document['title']);
        $pdf->setAuthor((string) $document['author']);
        $pdf->setCreator((string) $document['creator']);
        $pdf->setLanguage(self::language((string) $document['lang']));
        $pdf->enableDefaultPageContent(true);
        $font = $pdf->font->insert($pdf->pon, self::FONT, '', 10);
        $pdf->addPage();
        $pdf->page->addContent($font['out']);

        // The logo of the platform, which its name follows (a decoration), and the QR code of the page of verification
        // at the top right, described.
        $top = 12;
        if ($logo) {
            $size = 'width="' . round($document['logowidth'] * 3.78) . '" height="' . round($document['logoheight'] * 3.78) . '"';
            $pdf->addHTMLCell('<img src="' . $logo . '" alt="" ' . $size . ' />', 18, 10, 120);
            $top = 10 + (float) $document['logoheight'] + 4;
        }
        $qrimage = '<img src="' . $dir . '/qr.png" alt="' . s($document['qralt']) . '" width="94" height="94" />';
        $pdf->addHTMLCell($qrimage, 167, 10, 25);
        $pdf->addHTMLCell((string) $document['html'], 18, max($top, 38), 174);
        return $pdf->getOutPDFString();
    }
}
