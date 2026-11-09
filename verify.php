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

/**
 * Verification of a « My summary » document: whoever has its code (printed on the PDF, or read from its QR code) sees
 * what the platform issued, on which date, to compare with the document. Open without logging in, as the codes of the
 * certificates of Moodle.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- The page is open to whoever has the code of a document.
require(__DIR__ . '/../../config.php');

use theme_epure\learner_summary_document;

$code = trim(optional_param('code', '', PARAM_ALPHANUMEXT));

$PAGE->set_context(context_system::instance());
$PAGE->set_url(learner_summary_document::verify_url($code === '' ? null : $code));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mysummary_verify', 'theme_epure'));
$PAGE->set_heading(get_string('mysummary_verify', 'theme_epure'));

$context = [
    'action' => learner_summary_document::verify_url()->out(false),
    'code' => $code === '' ? '' : learner_summary_document::format_code($code),
    'searched' => $code !== '',
];
$document = $code === '' ? null : learner_summary_document::find($code);
$state = $document ? learner_summary_document::state($document) : '';
if ($document && $state !== 'valid') {
    // Revoked by the learner, or expired: that it was issued, and when, but not its content.
    $context['closed'] = get_string('mysummary_verify_' . $state, 'theme_epure', [
        'platform' => format_string(get_site()->fullname),
        'date' => userdate($document->timecreated, get_string('strftimedatetime', 'langconfig')),
        'end' => userdate(
            $state === 'revoked' ? $document->timerevoked : $document->timeexpires,
            get_string('strftimedate', 'langconfig')
        ),
    ]);
} else if ($document) {
    $context['document'] = learner_summary_document::export($document);
    $context['found'] = get_string('mysummary_verify_found', 'theme_epure', [
        'platform' => $context['document']['platform'] ?? '',
        'date' => $context['document']['issued'],
        'until' => $context['document']['until'],
    ]);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_epure/summary_verify', $context);
echo $OUTPUT->footer();
