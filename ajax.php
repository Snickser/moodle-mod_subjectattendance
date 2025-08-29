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

require_once('../../config.php');
$cmid = required_param('cmid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$status = required_param('status', PARAM_INT);
$cm = get_coursemodule_from_id('subjectattendance', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
require_course_login($cm->course, true, $cm);
require_sesskey();
require_capability('mod/subjectattendance:mark', $context);
header('Content-Type: application/json');
$record = $DB->get_record('subjectattendance_log', ['subjectid' => $subjectid, 'userid' => $userid]);
if ($record) {
    $record->status = $status;
    $record->timemodified = time();
    $DB->update_record('subjectattendance_log', $record);
} else {
    $DB->insert_record('subjectattendance_log', (object)[
        'subjectid' => $subjectid, 'userid' => $userid, 'status' => $status, 'timemodified' => time(),
    ]);
}
echo json_encode(['success' => true]);
