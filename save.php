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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_subjectattendance
 * @copyright   2025 Alex Orlov <snickser@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();
require_sesskey();

$attendanceid = required_param('attendanceid', PARAM_INT);
$cmid         = required_param('cmid', PARAM_INT);

$statusraw = $_POST['status'] ?? [];
$status = [];

foreach ($statusraw as $userid => $subjects) {
    $userid = clean_param($userid, PARAM_INT);
    foreach ($subjects as $subjectid => $value) {
        $subjectid = clean_param($subjectid, PARAM_INT);
        $status[$userid][$subjectid] = ($value === '') ? null : clean_param($value, PARAM_INT);
    }
}

$cm = get_coursemodule_from_id('subjectattendance', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$courseid = $cm->course;

require_capability('mod/subjectattendance:mark', $context);

$accessallgroups = has_capability('moodle/site:accessallgroups', $context);

$allowedgroups = $accessallgroups ? [] : groups_get_all_groups($courseid, $USER->id);
$allowedgroupids = array_keys($allowedgroups);

foreach ($status as $userid => $subjects) {
    if (!$accessallgroups) {
        $studentgroups = groups_get_all_groups($courseid, $userid);
        $studentgroupids = array_keys($studentgroups);

        if (empty(array_intersect($allowedgroupids, $studentgroupids))) {
            continue;
        }
    }

    foreach ($subjects as $subjectid => $value) {
        $log = $DB->get_record('subjectattendance_log', ['userid' => $userid, 'subjectid' => $subjectid]);

        if ($log) {
            $log->status = $value;
            $log->timemodified = time();
            $DB->update_record('subjectattendance_log', $log);
        } else {
            $DB->insert_record('subjectattendance_log', [
                'userid'       => $userid,
                'subjectid'    => $subjectid,
                'status'       => $value,
                'timecreated'  => time(),
                'timemodified' => time(),
            ]);
        }
    }
}

redirect(
    new moodle_url('/mod/subjectattendance/view.php', ['id' => $cm->id]),
    get_string('changessaved')
);
