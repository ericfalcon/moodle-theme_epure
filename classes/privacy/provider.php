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

namespace theme_epure\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use theme_epure\course_accessibility;
use theme_epure\learner_summary_document;

/**
 * Privacy provider for theme_epure.
 *
 * The theme stores the display preferences of each user (button « Aa »), as user preferences; the « My summary »
 * documents issued as PDF, with what each showed, in the context of the learner; and the points of accessibility a
 * teacher confirmed in the guide of a course, in the context of the course.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describes the data stored by the theme.
     *
     * @param collection $collection Collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        foreach (array_keys(\theme_epure\a11y::definitions()) as $name) {
            $collection->add_user_preference($name, 'privacy:metadata:preference:' . substr($name, strlen('theme_epure_')));
        }
        $collection->add_database_table(learner_summary_document::TABLE, [
            'userid' => 'privacy:metadata:summary:userid',
            'code' => 'privacy:metadata:summary:code',
            'timecreated' => 'privacy:metadata:summary:timecreated',
            'data' => 'privacy:metadata:summary:data',
            'contenthash' => 'privacy:metadata:summary:contenthash',
            'timeexpires' => 'privacy:metadata:summary:timeexpires',
            'timerevoked' => 'privacy:metadata:summary:timerevoked',
        ], 'privacy:metadata:summary');
        $collection->add_database_table(course_accessibility::TABLE, [
            'courseid' => 'privacy:metadata:a11ycheck:courseid',
            'checkkey' => 'privacy:metadata:a11ycheck:checkkey',
            'userid' => 'privacy:metadata:a11ycheck:userid',
            'timecreated' => 'privacy:metadata:a11ycheck:timecreated',
        ], 'privacy:metadata:a11ycheck');
        return $collection;
    }

    /**
     * Exports the display preferences of a user.
     *
     * @param int $userid User id.
     */
    public static function export_user_preferences(int $userid) {
        foreach (array_keys(\theme_epure\a11y::definitions()) as $name) {
            $value = get_user_preferences($name, null, $userid);
            if ($value !== null) {
                writer::export_user_preference(
                    'theme_epure',
                    $name,
                    $value,
                    get_string('privacy:metadata:preference:' . substr($name, strlen('theme_epure_')), 'theme_epure')
                );
            }
        }
    }

    /**
     * Contexts of the documents of a user, their own context, and of their confirmations, the contexts of the courses.
     *
     * @param int $userid User.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            'SELECT ctx.id
               FROM {context} ctx
               JOIN {' . learner_summary_document::TABLE . '} s ON s.userid = ctx.instanceid AND ctx.contextlevel = :level
              WHERE s.userid = :userid',
            ['level' => CONTEXT_USER, 'userid' => $userid]
        );
        $contextlist->add_from_sql(
            'SELECT ctx.id
               FROM {context} ctx
               JOIN {' . course_accessibility::TABLE . '} c ON c.courseid = ctx.instanceid AND ctx.contextlevel = :level
              WHERE c.userid = :userid',
            ['level' => CONTEXT_COURSE, 'userid' => $userid]
        );
        return $contextlist;
    }

    /**
     * Users with documents in a context, the user of a user context; with confirmations, the teachers of a course.
     *
     * @param userlist $userlist Users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel === CONTEXT_COURSE) {
            $userlist->add_from_sql(
                'userid',
                'SELECT userid FROM {' . course_accessibility::TABLE . '} WHERE courseid = :courseid',
                ['courseid' => $context->instanceid]
            );
            return;
        }
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {' . learner_summary_document::TABLE . '} WHERE userid = :userid',
            ['userid' => $context->instanceid]
        );
    }

    /**
     * Exports the documents of a user: when issued, their code and what they showed.
     *
     * @param approved_contextlist $contextlist Contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                self::export_confirmations($context, $userid);
                continue;
            }
            if ($context->contextlevel !== CONTEXT_USER || (int) $context->instanceid !== $userid) {
                continue;
            }
            $documents = [];
            foreach ($DB->get_records(learner_summary_document::TABLE, ['userid' => $userid], 'timecreated') as $record) {
                $documents[] = [
                    'code' => learner_summary_document::format_code($record->code),
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                    'timeexpires' => empty($record->timeexpires) ? null
                        : \core_privacy\local\request\transform::datetime($record->timeexpires),
                    'timerevoked' => empty($record->timerevoked) ? null
                        : \core_privacy\local\request\transform::datetime($record->timerevoked),
                    'content' => json_decode($record->data),
                ];
            }
            if ($documents) {
                writer::with_context($context)->export_data(
                    [get_string('mysummary', 'theme_epure')],
                    (object) ['documents' => $documents]
                );
            }
        }
    }

    /**
     * Exports the points of accessibility a teacher confirmed in a course.
     *
     * @param \context $context Context of the course.
     * @param int $userid User.
     */
    protected static function export_confirmations(\context $context, int $userid): void {
        global $DB;
        $confirmations = [];
        $records = $DB->get_records(
            course_accessibility::TABLE,
            ['courseid' => $context->instanceid, 'userid' => $userid],
            'timecreated'
        );
        foreach ($records as $record) {
            $confirmations[] = [
                'cmid' => (int) $record->cmid,
                'point' => $record->checkkey,
                'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
            ];
        }
        if ($confirmations) {
            writer::with_context($context)->export_data(
                [get_string('courseaccessibility', 'theme_epure')],
                (object) ['confirmations' => $confirmations]
            );
        }
    }

    /**
     * Deletes the documents of the user of a context.
     *
     * @param \context $context Context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel === CONTEXT_USER) {
            $DB->delete_records(learner_summary_document::TABLE, ['userid' => $context->instanceid]);
        } else if ($context->contextlevel === CONTEXT_COURSE) {
            $DB->delete_records(course_accessibility::TABLE, ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Deletes the documents of a user.
     *
     * @param approved_contextlist $contextlist Contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && (int) $context->instanceid === $userid) {
                $DB->delete_records(learner_summary_document::TABLE, ['userid' => $userid]);
            } else if ($context->contextlevel === CONTEXT_COURSE) {
                $DB->delete_records(course_accessibility::TABLE, ['courseid' => $context->instanceid, 'userid' => $userid]);
            }
        }
    }

    /**
     * Deletes the documents of users in a context.
     *
     * @param approved_userlist $userlist Users.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel === CONTEXT_COURSE && $userlist->get_userids()) {
            [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $DB->delete_records_select(
                course_accessibility::TABLE,
                "courseid = :courseid AND userid $insql",
                $params + ['courseid' => $context->instanceid]
            );
            return;
        }
        if ($context->contextlevel === CONTEXT_USER && in_array((int) $context->instanceid, $userlist->get_userids())) {
            $DB->delete_records(learner_summary_document::TABLE, ['userid' => $context->instanceid]);
        }
    }
}
