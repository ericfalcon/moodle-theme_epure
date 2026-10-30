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
 * A template of certificate in the colours of the brand, for the plugin Certificate (tool_certificate).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use theme_epure\certificate_frame;
use theme_epure\certificate_template;
use theme_epure\palette;

admin_externalpage_setup('theme_epure_certificate');

$available = certificate_template::available();
if ($available && optional_param('create', 0, PARAM_BOOL) && confirm_sesskey()) {
    $template = certificate_template::create();
    redirect(
        $template->edit_url(),
        get_string('certtemplate_created', 'theme_epure'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certtemplate', 'theme_epure'));
echo html_writer::div(get_string('certtemplate_intro', 'theme_epure'), 'epure-certtemplate-intro');
if (!$available) {
    echo $OUTPUT->notification(get_string('certtemplate_unavailable', 'theme_epure'), 'info', false);
} else {
    $brand = palette::normalise(get_config('theme_epure', 'brandcolor'));
    echo html_writer::img(certificate_frame::preview($brand), get_string('certtemplate_preview', 'theme_epure'), [
        'class' => 'epure-certtemplate-preview',
        'width' => 351,
        'height' => 248,
    ]);
    if (!certificate_template::logo()) {
        echo $OUTPUT->notification(get_string('certtemplate_nologo', 'theme_epure'), 'info', false);
    }
    echo $OUTPUT->single_button(
        new moodle_url(certificate_template::url(), ['create' => 1]),
        get_string('certtemplate_create', 'theme_epure'),
        'post',
        ['primary' => true]
    );
}
echo $OUTPUT->footer();
