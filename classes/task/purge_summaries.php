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

namespace theme_epure\task;

use theme_epure\learner_summary_document;

/**
 * Erases the content of the « My summary » documents whose time of validity is over: only their code and their dates
 * are kept, for the page of verification to say they expired.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge_summaries extends \core\task\scheduled_task {
    /**
     * Name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_purgesummaries', 'theme_epure');
    }

    /**
     * Erases the content of the documents expired.
     */
    public function execute(): void {
        $count = learner_summary_document::purge_expired();
        mtrace('Documents « My summary » expired, content erased: ' . $count);
    }
}
