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
$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('subjectattendance', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
require_course_login($cm->course, true, $cm);
require_capability('mod/subjectattendance:mark', $context);
$attendance = $DB->get_record('subjectattendance', ['id' => $cm->instance], MUST_EXIST);
$subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $attendance->id]);
$students = get_enrolled_users($context, 'mod/subjectattendance:view', 0, 'u.id,u.firstname,u.lastname');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subjectattendance_' . $attendance->id . '.csv"');
$fp = fopen('php://output', 'w');
$header = array_merge(['Student ID', 'Student name'], array_map(function ($s) {
    return $s->name;
}, $subjects));
fputcsv($fp, $header);
foreach ($students as $student) {
    $row = [$student->id, fullname($student)];
    foreach ($subjects as $subject) {
        $existing = $DB->get_record('subjectattendance_log', ['subjectid' => $subject->id, 'userid' => $student->id]);
        $status = $existing ? $existing->status : 0;
        $row[] = $status;
    }
    fputcsv($fp, $row);
}
fclose($fp);
exit;
