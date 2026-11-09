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
 * The learner keeps control of what they issued: they choose how long a document can be verified (three months, a year
 * or without limit), and they can revoke a document or delete it. A document revoked or expired keeps only its code and
 * its dates: the page of verification says it was issued, and no longer shows its content.
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

    /** @var int[] Times of validity the learner chooses from, in days; 0 for without limit. */
    public const VALIDITY = [90, 365, 0];

    /** @var int Time of validity chosen by default, in days. */
    public const DEFAULT_VALIDITY = 365;

    /**
     * Issues a document: the summary of the learner as it is now, kept with a new code.
     *
     * @param \renderer_base $output Renderer.
     * @param int $userid Learner.
     * @param string|null $code Code, for the tests; a new one by default.
     * @param int $days Days the document can be verified, one of {@see self::VALIDITY}; 0 for without limit.
     * @return \stdClass Record of the document: id, userid, code, timecreated, data (JSON), contenthash, timeexpires,
     *     timerevoked.
     */
    public static function issue(
        \renderer_base $output,
        int $userid,
        ?string $code = null,
        int $days = self::DEFAULT_VALIDITY
    ): \stdClass {
        global $DB;
        $now = time();
        $days = in_array($days, self::VALIDITY, true) ? $days : self::DEFAULT_VALIDITY;
        $data = self::snapshot(learner_summary::export($output, $userid, $now), $userid);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $record = (object) [
            'userid' => $userid,
            'code' => $code ?? self::new_code(),
            'timecreated' => $now,
            'data' => $json,
            'contenthash' => hash('sha256', $json),
            'timeexpires' => $days ? $now + $days * DAYSECS : 0,
            'timerevoked' => 0,
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
     * The state of a document: valid (its content shown to whoever has its code), revoked by the learner, or expired.
     *
     * @param \stdClass $record Document.
     * @param int|null $now Time, now by default.
     * @return string valid, revoked or expired.
     */
    public static function state(\stdClass $record, ?int $now = null): string {
        if (!empty($record->timerevoked)) {
            return 'revoked';
        }
        if (!empty($record->timeexpires) && $record->timeexpires <= ($now ?? time())) {
            return 'expired';
        }
        return 'valid';
    }

    /**
     * The documents a learner issued, the latest first.
     *
     * @param int $userid Learner.
     * @return \stdClass[]
     */
    public static function documents(int $userid): array {
        global $DB;
        return array_values($DB->get_records(
            self::TABLE,
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            'id, userid, code, timecreated, timeexpires, timerevoked'
        ));
    }

    /**
     * Revokes a document of a learner: its content is erased, its code and its dates kept, for the page of verification
     * to say it was issued and revoked.
     *
     * @param int $userid Learner.
     * @param int $id Document.
     * @return bool Whether a document of the learner was revoked.
     */
    public static function revoke(int $userid, int $id): bool {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $id, 'userid' => $userid]);
        if (!$record || !empty($record->timerevoked)) {
            return false;
        }
        $DB->update_record(self::TABLE, (object) ['id' => $record->id, 'timerevoked' => time(), 'data' => '{}']);
        return true;
    }

    /**
     * Deletes a document of a learner: its code no longer exists on the platform.
     *
     * @param int $userid Learner.
     * @param int $id Document.
     * @return bool Whether a document of the learner was deleted.
     */
    public static function delete(int $userid, int $id): bool {
        global $DB;
        if (!$DB->record_exists(self::TABLE, ['id' => $id, 'userid' => $userid])) {
            return false;
        }
        $DB->delete_records(self::TABLE, ['id' => $id, 'userid' => $userid]);
        return true;
    }

    /**
     * Erases the content of the documents expired, as of those revoked (scheduled task).
     *
     * @param int|null $now Time, now by default.
     * @return int Documents erased.
     */
    public static function purge_expired(?int $now = null): int {
        global $DB;
        $select = 'timeexpires > 0 AND timeexpires <= :now AND ' . $DB->sql_compare_text('data', 2) . ' <> :empty';
        $params = ['now' => $now ?? time(), 'empty' => '{}'];
        $ids = $DB->get_fieldset_select(self::TABLE, 'id', $select, $params);
        if ($ids) {
            [$in, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->set_field_select(self::TABLE, 'data', '{}', 'id ' . $in, $inparams);
        }
        return count($ids);
    }

    /**
     * Until when a document can be verified: « until 9 November 2027 », or « without limit ».
     *
     * @param \stdClass $record Document.
     * @return string
     */
    public static function until(\stdClass $record): string {
        if (empty($record->timeexpires)) {
            return get_string('mysummary_validity_none', 'theme_epure');
        }
        $date = userdate($record->timeexpires, get_string('strftimedate', 'langconfig'));
        return get_string('mysummary_until_date', 'theme_epure', $date);
    }

    /**
     * Context of the part « Documents issued » of the page « My summary »: the form to issue a document, with what its
     * recipient will see and its time of validity, and the documents issued, each with its state and its actions.
     *
     * @param int $userid Learner.
     * @return array
     */
    public static function export_documents(int $userid): array {
        $action = learner_summary::url()->out(false);
        $validity = array_map(fn(int $days) => [
            'value' => $days,
            'label' => get_string('mysummary_validity_' . $days, 'theme_epure'),
            'checked' => $days === self::DEFAULT_VALIDITY,
            'id' => 'epure-summary-validity-' . $days,
        ], self::VALIDITY);
        $documents = array_map(function (\stdClass $record) use ($action) {
            $state = self::state($record);
            return [
                'id' => (int) $record->id,
                'code' => self::format_code($record->code),
                'issued' => userdate($record->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
                'until' => self::until($record),
                'state' => get_string('mysummary_state_' . $state, 'theme_epure', $state === 'revoked'
                    ? userdate($record->timerevoked, get_string('strftimedate', 'langconfig')) : null),
                'statekey' => $state,
                'canrevoke' => $state === 'valid',
                'verifyurl' => self::verify_url($record->code)->out(false),
                'revokeurl' => (new \moodle_url($action, ['action' => 'revoke', 'id' => $record->id]))->out(false),
                'deleteurl' => (new \moodle_url($action, ['action' => 'delete', 'id' => $record->id]))->out(false),
            ];
        }, self::documents($userid));
        return [
            'pdfaction' => $action,
            'sesskey' => sesskey(),
            'validity' => $validity,
            'documents' => $documents,
            'hasdocuments' => !empty($documents),
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
            'until' => self::until($record),
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
            ])) . '<br><a href="' . s($url) . '">' . s($url) . '</a><br>'
            . s(get_string('mysummary_pdf_until', 'theme_epure', $data['until'])) . '</p>';
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
