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

namespace theme_epure\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service of the preview of the files (theme_epure/file_preview): the text of the document of an office suite of
 * a resource, as HTML written by the template theme_epure/document_text.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document_text extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Module of the resource'),
        ]);
    }

    /**
     * The text of the document of the resource.
     *
     * @param int $cmid Module of the resource.
     * @return array
     */
    public static function execute(int $cmid): array {
        global $PAGE;
        ['cmid' => $cmid] = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/resource:view', $context);
        [, $cm] = get_course_and_cm_from_cmid($cmid, 'resource');
        if (!$cm->uservisible) {
            throw new \moodle_exception('activityiscurrentlyhidden');
        }
        $file = \theme_epure\file_preview::main_file($cm);
        $text = $file ? \theme_epure\document_text::read($file) : null;
        if ($text === null) {
            return ['html' => ''];
        }
        $output = $PAGE->get_renderer('core');
        return ['html' => $output->render_from_template('theme_epure/document_text', \theme_epure\document_text::export($text))];
    }

    /**
     * Result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'Text of the document, as HTML; empty when it cannot be read'),
        ]);
    }
}
