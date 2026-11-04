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
 * « My summary » as a PDF document with a code of verification.
 *
 * Each document issued keeps, in the table theme_epure_summary, what it showed on the day it was drawn up (a snapshot of
 * the summary), its date and a code. The code, printed on the document with a QR code, opens the page of verification
 * of the platform, which shows the same data: whoever receives the document can check that the platform issued it, on
 * that date, with that content. The PDF is made with the PDF library of Moodle (TCPDF).
 *
 * The PDF is not tagged for the screen readers: the page « My summary » stays the accessible version, and says so.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_summary_document {
    /** @var string Table of the documents issued. */
    public const TABLE = 'theme_epure_summary';

    /** @var string Characters of the codes: no 0, O, 1, I nor L, which read alike. */
    public const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** @var int Characters of a code, written in groups of four (about 59 bits of chance). */
    public const LENGTH = 12;

    /**
     * Issues a document: the summary of the learner as it is now, kept with a new code.
     *
     * @param \renderer_base $output Renderer.
     * @param int $userid Learner.
     * @param string|null $code Code, for the tests; a new one by default.
     * @return \stdClass Record of the document: id, userid, code, timecreated, data (JSON), contenthash.
     */
    public static function issue(\renderer_base $output, int $userid, ?string $code = null): \stdClass {
        global $DB;
        $now = time();
        $data = self::snapshot(learner_summary::export($output, $userid, $now), $userid);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $record = (object) [
            'userid' => $userid,
            'code' => $code ?? self::new_code(),
            'timecreated' => $now,
            'data' => $json,
            'contenthash' => hash('sha256', $json),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);
        return $record;
    }

    /**
     * What a document shows: the summary without its links, with the name and the colour of the platform (of the
     * IOMAD company of the learner when they have one).
     *
     * @param array $summary Context of {@see learner_summary::export()}.
     * @param int $userid Learner.
     * @return array
     */
    protected static function snapshot(array $summary, int $userid): array {
        $brand = email::export($userid);
        $courses = array_map(fn(array $course) => [
            'name' => $course['name'],
            'category' => $course['category'],
            'hasprogress' => $course['hasprogress'],
            'progress' => $course['progress'],
            'completed' => $course['completed'],
            'datecompleted' => $course['datecompleted'],
            'grade' => $course['grade'],
            'activities' => array_map(
                fn(array $activity) => ['name' => $activity['name'], 'date' => $activity['date']],
                $course['activities']
            ),
        ], $summary['courses']);
        $certificates = array_map(fn($certificate) => [
            'name' => (string) (((array) $certificate)['name'] ?? ''),
            'date' => (string) (((array) $certificate)['date'] ?? ''),
        ], $summary['certificates']);
        return [
            'fullname' => $summary['fullname'],
            'date' => $summary['date'],
            'platform' => $brand['name'],
            'brand' => $brand['brand'],
            'brandtext' => $brand['brandtext'],
            'taken' => $summary['taken'],
            'completedcount' => $summary['completedcount'],
            'certificatecount' => $summary['certificatecount'],
            'courses' => $courses,
            'certificates' => $certificates,
        ];
    }

    /**
     * A new code, not used yet.
     *
     * @return string Code, without separators.
     */
    protected static function new_code(): string {
        global $DB;
        do {
            $code = '';
            for ($i = 0; $i < self::LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while ($DB->record_exists(self::TABLE, ['code' => $code]));
        return $code;
    }

    /**
     * A code as typed or read (lower case, spaces, hyphens), as kept.
     *
     * @param string $code Code.
     * @return string
     */
    public static function normalise(string $code): string {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
    }

    /**
     * A code in groups of four, easier to read and to type: ABCD-EFGH-JKMN.
     *
     * @param string $code Code.
     * @return string
     */
    public static function format_code(string $code): string {
        return implode('-', str_split(self::normalise($code), 4));
    }

    /**
     * The document of a code.
     *
     * @param string $code Code, as typed.
     * @return \stdClass|null
     */
    public static function find(string $code): ?\stdClass {
        global $DB;
        $code = self::normalise($code);
        if (strlen($code) !== self::LENGTH) {
            return null;
        }
        return $DB->get_record(self::TABLE, ['code' => $code]) ?: null;
    }

    /**
     * The page of verification of a code.
     *
     * @param string|null $code Code; the empty form without.
     * @return \moodle_url
     */
    public static function verify_url(?string $code = null): \moodle_url {
        return new \moodle_url('/theme/epure/verify.php', $code === null ? [] : ['code' => self::format_code($code)]);
    }

    /**
     * Context of the template theme_epure/summary_verify for a document.
     *
     * @param \stdClass $record Document.
     * @return array
     */
    public static function export(\stdClass $record): array {
        $data = json_decode($record->data, true) ?: [];
        foreach ($data['courses'] ?? [] as $i => $course) {
            $data['courses'][$i]['hasactivities'] = !empty($course['activities']);
        }
        return $data + [
            'code' => self::format_code($record->code),
            'issued' => userdate($record->timecreated, get_string('strftimedatetime', 'langconfig')),
            'hascourses' => !empty($data['courses']),
            'hascertificates' => !empty($data['certificates']),
        ];
    }

    /**
     * The PDF of a document.
     *
     * @param \stdClass $record Document.
     * @return string Content of the PDF.
     */
    public static function pdf(\stdClass $record): string {
        global $CFG;
        require_once($CFG->libdir . '/pdflib.php');
        $data = self::export($record);
        $code = $data['code'];
        $url = self::verify_url($record->code)->out(false);
        $footer = get_string('mysummary_pdf_footer', 'theme_epure', $code);

        // The code on every page, at the bottom, with the number of the page.
        $pdf = new class ('P', 'mm', 'A4') extends \pdf {
            /** @var string Text of the footer. */
            public $epurefooter = '';

            /**
             * Footer of each page: the code of verification and the number of the page.
             */
            public function Footer() { // phpcs:ignore moodle.NamingConventions.ValidFunctionName.LowercaseMethod
                $this->SetY(-12);
                $this->SetFont('helvetica', '', 8);
                $this->SetTextColor(95, 104, 115);
                $this->Cell(0, 6, $this->epurefooter, 0, 0, 'L');
                $this->Cell(0, 6, $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };
        $pdf->epurefooter = $footer;
        $pdf->SetCreator(format_string(get_site()->fullname));
        $pdf->SetAuthor($data['platform'] ?? '');
        $pdf->SetTitle(get_string('mysummary', 'theme_epure') . ' — ' . ($data['fullname'] ?? ''));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(18, 22, 18);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // A band of the colour of the brand, then the logo of the platform.
        [$r, $g, $b] = palette::to_rgb($data['brand'] ?? '#1C6E73');
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect(0, 0, 210, 6, 'F');
        if ($logo = certificate_template::logo()) {
            [$w, $h] = certificate_template::fit($logo->get_imageinfo() ?: [], 50, 16);
            $pdf->Image('@' . $logo->get_content(), 18, 10, $w, $h);
            $pdf->SetY(10 + $h + 4);
        } else {
            $pdf->SetY(12);
        }

        // The QR code of the page of verification, at the top right.
        $pdf->write2DBarcode($url, 'QRCODE,M', 167, 10, 25, 25, ['border' => false, 'padding' => 0]);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML(self::pdf_html($data, $url), true, false, true, false, '');
        return $pdf->Output('', 'S');
    }

    /**
     * Body of the PDF, in the HTML that TCPDF reads.
     *
     * @param array $data Context of {@see self::export()}.
     * @param string $url Address of the page of verification.
     * @return string
     */
    protected static function pdf_html(array $data, string $url): string {
        $brandtext = s($data['brandtext'] ?? '#1C6E73');
        $html = '<p style="color:' . $brandtext . ';font-size:11pt;font-weight:bold;">' . s($data['platform'] ?? '') . '</p>'
            . '<h1 style="font-size:18pt;">' . s(get_string('mysummary', 'theme_epure')) . '</h1>'
            . '<p style="font-size:12pt;font-weight:bold;">' . s($data['fullname'] ?? '') . '</p>'
            . '<p>' . s(get_string('mysummary_date', 'theme_epure', $data['date'] ?? '')) . '</p>'
            . '<p>' . implode(' · ', self::figures($data)) . '</p>';

        if (!empty($data['courses'])) {
            $html .= '<h2 style="font-size:13pt;color:' . $brandtext . ';">' . s(get_string('mysummary_taken', 'theme_epure'))
                . '</h2>';
            foreach ($data['courses'] as $course) {
                $facts = [];
                if (!empty($course['hasprogress'])) {
                    $facts[] = get_string('mysummary_progress', 'theme_epure', (int) $course['progress']);
                }
                if (!empty($course['datecompleted'])) {
                    $facts[] = get_string('mysummary_completedon', 'theme_epure', $course['datecompleted']);
                }
                if (!empty($course['grade'])) {
                    $facts[] = get_string('mysummary_grade', 'theme_epure', $course['grade']);
                }
                $html .= '<p><b>' . s($course['name']) . '</b>'
                    . (!empty($course['category']) ? ' <span style="color:#5f6873;">— ' . s($course['category']) . '</span>' : '')
                    . '<br>' . s(implode(' · ', $facts)) . '</p>';
                if (!empty($course['activities'])) {
                    $html .= '<table cellpadding="2" style="font-size:9pt;"><tr><th style="border-bottom:1px solid #c8cdd3;">'
                        . s(get_string('mysummary_activities', 'theme_epure')) . '</th><th style="border-bottom:1px solid #c8cdd3;'
                        . 'text-align:right;" width="25%"></th></tr>';
                    foreach ($course['activities'] as $activity) {
                        $html .= '<tr><td>' . s($activity['name']) . '</td><td style="text-align:right;" width="25%">'
                            . s($activity['date']) . '</td></tr>';
                    }
                    $html .= '</table>';
                }
            }
        } else {
            $html .= '<p>' . s(get_string('mysummary_none', 'theme_epure')) . '</p>';
        }

        if (!empty($data['certificates'])) {
            $html .= '<h2 style="font-size:13pt;color:' . $brandtext . ';">'
                . s(get_string('mysummary_certificates', 'theme_epure')) . '</h2><table cellpadding="2" style="font-size:9pt;">';
            foreach ($data['certificates'] as $certificate) {
                $html .= '<tr><td>' . s($certificate['name']) . '</td><td style="text-align:right;" width="25%">'
                    . s($certificate['date']) . '</td></tr>';
            }
            $html .= '</table>';
        }

        $html .= '<p style="font-size:9pt;color:#5f6873;">' . s(get_string('mysummary_note', 'theme_epure')) . '</p>'
            . '<p style="font-size:9pt;">' . s(get_string('mysummary_pdf_verify', 'theme_epure', [
                'code' => $data['code'],
                'date' => $data['issued'],
            ])) . '<br><a href="' . s($url) . '">' . s($url) . '</a></p>';
        return $html;
    }

    /**
     * The figures of a document, each « label: value » in the typography of the language.
     *
     * @param array $data Context of {@see self::export()}.
     * @return string[] HTML.
     */
    protected static function figures(array $data): array {
        $figures = [];
        foreach (
            ['mysummary_taken' => 'taken', 'mysummary_completed' => 'completedcount',
                'mysummary_certificates' => 'certificatecount'] as $label => $key
        ) {
            $figures[] = s(get_string('mysummary_figure', 'theme_epure', [
                'label' => get_string($label, 'theme_epure'),
                'value' => (int) ($data[$key] ?? 0),
            ]));
        }
        return $figures;
    }

    /**
     * Name of the file of the PDF.
     *
     * @param \stdClass $record Document.
     * @return string
     */
    public static function filename(\stdClass $record): string {
        return clean_filename(get_string('mysummary', 'theme_epure') . ' ' . userdate($record->timecreated, '%Y-%m-%d', 99, false)
            . ' ' . self::format_code($record->code) . '.pdf');
    }
}
