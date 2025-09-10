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

use mod_subjectattendance\notifications;

require_once('../../config.php');
require_once("$CFG->dirroot/mod/subjectattendance/lib.php");

require_login();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$required = ['sesskey', 'studentid', 'subjectid', 'cmid', 'attendanceid', 'status'];
foreach ($required as $r) {
    if (!isset($data[$r])) {
        echo json_encode(['success' => false, 'error' => "Missing parameter: $r"]);
        exit;
    }
}

if (!confirm_sesskey($data['sesskey'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid sesskey']);
    exit;
}

$studentid    = clean_param($data['studentid'], PARAM_INT);
$subjectid    = clean_param($data['subjectid'], PARAM_INT);
$cmid         = clean_param($data['cmid'], PARAM_INT);
$attendanceid = clean_param($data['attendanceid'], PARAM_INT);
$status       = ($data['status'] === '') ? null : clean_param($data['status'], PARAM_INT);

$cm = get_coursemodule_from_id('subjectattendance', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
require_capability('mod/subjectattendance:mark', $context);

$attendance = $DB->get_record('subjectattendance', ['id' => $cm->instance], '*', MUST_EXIST);
$field = $DB->get_record('subjectattendance_subjects', ['id' => $subjectid]);

$courseid = $cm->course;
$accessallgroups = has_capability('moodle/site:accessallgroups', $context);

if (!$accessallgroups) {
    $allowedgroups = groups_get_all_groups($courseid, $USER->id);
    $allowedgroupids = array_keys($allowedgroups);
    $studentgroups = groups_get_all_groups($courseid, $studentid);
    $studentgroupids = array_keys($studentgroups);

    if (empty(array_intersect($allowedgroupids, $studentgroupids))) {
        echo json_encode(['success' => false, 'error' => 'Access denied for this student']);
        exit;
    }
}

$log = $DB->get_record('subjectattendance_log', ['userid' => $studentid, 'subjectid' => $subjectid]);

if ($log) {
    $log->status = $status;
    $log->timemodified = time();
    $DB->update_record('subjectattendance_log', $log);
} else if ($status !== null) {
    $DB->insert_record('subjectattendance_log', [
        'userid'       => $studentid,
        'subjectid'    => $subjectid,
        'status'       => $status,
        'timecreated'  => time(),
        'timemodified' => time(),
    ]);
}

$event = \mod_subjectattendance\event\attendance_marked::create([
    'context' => $context,
    'objectid' => $attendanceid,
    'userid' => $USER->id,
    'relateduserid' => $studentid,
    'other' => [
        'subjectid' => $subjectid,
        'status'    => $status ? $status : 0,
    ],
]);
$event->trigger();

// Notify user.
$options = ['' => '', 0 => '❌', 1 => '⚠️', 2 => '✅'];

if (
    ($attendance->notify == 1 && $status !== null) ||
    ($attendance->notify == 3 && $status === 0) ||
    ($attendance->notify == 2 && $status <= 2 && $status !== null)
) {
    notifications::notify(
        $studentid,
        $cm->id,
        $field->name,
        $options[$status],
    );
}

echo json_encode(['success' => true]);
