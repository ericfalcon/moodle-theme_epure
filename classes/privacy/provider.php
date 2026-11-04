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
use theme_epure\learner_summary_document;

/**
 * Privacy provider for theme_epure.
 *
 * The theme stores the display preferences of each user (button « Aa »), as user preferences, and the « My summary »
 * documents issued as PDF, with what each showed, in the context of the learner.
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
        ], 'privacy:metadata:summary');
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
     * Contexts of the documents of a user: their own context.
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
        return $contextlist;
    }

    /**
     * Users with documents in a context: the user of a user context.
     *
     * @param userlist $userlist Users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
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
            if ($context->contextlevel !== CONTEXT_USER || (int) $context->instanceid !== $userid) {
                continue;
            }
            $documents = [];
            foreach ($DB->get_records(learner_summary_document::TABLE, ['userid' => $userid], 'timecreated') as $record) {
                $documents[] = [
                    'code' => learner_summary_document::format_code($record->code),
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
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
     * Deletes the documents of the user of a context.
     *
     * @param \context $context Context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel === CONTEXT_USER) {
            $DB->delete_records(learner_summary_document::TABLE, ['userid' => $context->instanceid]);
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
        if ($context->contextlevel === CONTEXT_USER && in_array((int) $context->instanceid, $userlist->get_userids())) {
            $DB->delete_records(learner_summary_document::TABLE, ['userid' => $context->instanceid]);
        }
    }
}
