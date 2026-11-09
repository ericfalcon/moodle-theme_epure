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
 * « My summary »: the courses the user takes, their progress, the activities completed, the grades and the
 * certificates, on a page to print, or as a PDF with a code of verification. Their own data only. The documents they
 * issued are listed, to revoke or delete.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use theme_epure\learner_summary;
use theme_epure\learner_summary_document;

require_login(null, false);
if (isguestuser() || !learner_summary::enabled()) {
    redirect(new moodle_url('/my/'));
}

// The system context: a plain heading, without the picture of the user and their messages.
$PAGE->set_context(context_system::instance());
$url = learner_summary::url();
$userid = (int) $USER->id;

// The PDF with a code of verification: each download issues a document, kept with what it shows, for the time of
// validity the learner chose.
if (optional_param('format', '', PARAM_ALPHA) === 'pdf') {
    require_sesskey();
    $document = learner_summary_document::issue(
        $PAGE->get_renderer('core'),
        $userid,
        null,
        optional_param('validity', learner_summary_document::DEFAULT_VALIDITY, PARAM_INT)
    );
    send_file(
        learner_summary_document::pdf($document),
        learner_summary_document::filename($document),
        0,
        0,
        true,
        true,
        'application/pdf'
    );
}

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mysummary', 'theme_epure'));
$PAGE->set_heading(get_string('mysummary', 'theme_epure'));

// A document revoked or deleted: asked to confirm first, as it cannot be undone.
$action = optional_param('action', '', PARAM_ALPHA);
if (in_array($action, ['revoke', 'delete'], true)) {
    $id = required_param('id', PARAM_INT);
    $record = $DB->get_record(learner_summary_document::TABLE, ['id' => $id, 'userid' => $userid], '*', MUST_EXIST);
    $code = learner_summary_document::format_code($record->code);
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        $done = $action === 'revoke' ? learner_summary_document::revoke($userid, $id)
            : learner_summary_document::delete($userid, $id);
        redirect(
            new moodle_url($url, [], 'epure-summary-documents'),
            $done ? get_string('mysummary_' . $action . 'd', 'theme_epure', $code) : null,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('mysummary_' . $action . '_confirm', 'theme_epure', $code),
        new single_button(
            new moodle_url($url, ['action' => $action, 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
            get_string('mysummary_' . $action, 'theme_epure'),
            'post',
            single_button::BUTTON_DANGER
        ),
        new single_button(new moodle_url($url, [], 'epure-summary-documents'), get_string('cancel'), 'get')
    );
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'theme_epure/learner_summary',
    learner_summary::export($OUTPUT, $userid) + learner_summary_document::export_documents($userid)
);
echo $OUTPUT->footer();
