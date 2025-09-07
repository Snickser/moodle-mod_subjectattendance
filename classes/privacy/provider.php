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
 * Privacy API implementation for mod_subjectattendance.
 *
 * @package    mod_subjectattendance
 * @copyright  2025 Alex Orlov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subjectattendance\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\core_user_data_provider;

/**
 * Privacy provider for mod_subjectattendance.
 * @package mod_subjectattendance
 */
class provider implements core_user_data_provider, \core_privacy\local\metadata\provider {
    /**
     * Returns meta data about this plugin's data.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'subjectattendance_log',
            [
                'subjectid' => 'privacy:metadata:log:subjectid',
                'userid'    => 'privacy:metadata:log:userid',
                'status'    => 'privacy:metadata:log:status',
            ],
            'privacy:metadata:log'
        );
        return $collection;
    }

    /**
     * Get all contexts where a user has data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contexts = [];
        $logs = $DB->get_records('subjectattendance_log', ['userid' => $userid]);

        foreach ($logs as $log) {
            $contexts[$log->subjectid] = \context_course::instance($log->subjectid);
        }

        return new contextlist(array_values($contexts));
    }

    /**
     * Export all user data for the specified contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            $logs = $DB->get_records('subjectattendance_log', [
                'userid' => $userid,
                'subjectid' => $context->instanceid,
            ]);

            if (!empty($logs)) {
                $exportdata = [];
                foreach ($logs as $log) {
                    $exportdata[] = [
                        'subjectid' => $log->subjectid,
                        'status'    => $log->status,
                    ];
                }

                writer::with_context($context)->export_data([], $exportdata);
            }
        }
    }

    /**
     * Delete all user data for this plugin in a context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $DB->delete_records('subjectattendance_log', ['subjectid' => $context->instanceid]);
    }

    /**
     * Delete user data for the given approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            $DB->delete_records('subjectattendance_log', [
                'userid' => $userid,
                'subjectid' => $context->instanceid,
            ]);
        }
    }
}
