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

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('subjectattendance', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_course_login($cm->course, true, $cm);
require_capability('mod/subjectattendance:view', $context);

$attendance = $DB->get_record('subjectattendance', ['id' => $cm->instance], '*', MUST_EXIST);

$subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $attendance->id], 'id ASC', '*');
if (!$subjects) {
    throw new moodle_exception('nosubjects', 'subjectattendance');
}

if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
    $students = get_enrolled_users($context);
} else {
    $students = [$USER];
}

$subjectids = array_keys($subjects);
$studentids = array_map(fn($s) => $s->id, $students);

[$insqlsubj, $params1] = $DB->get_in_or_equal($subjectids, SQL_PARAMS_NAMED, 'subj');
[$insqlstud, $params2] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'stud');

$sql = "SELECT * FROM {subjectattendance_log} WHERE subjectid $insqlsubj AND userid $insqlstud";
$logs = $DB->get_records_sql($sql, $params1 + $params2);

$logmap = [];
foreach ($logs as $log) {
    $logmap[$log->userid][$log->subjectid] = $log->status;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subjectattendance_' . $attendance->id . '.csv"');
echo "\xEF\xBB\xBF";

$fp = fopen('php://output', 'w');

$header = array_merge(['Student ID', 'Student name'], array_map(fn($s) => $s->name, $subjects));
fputcsv($fp, $header, ',', '"', '\\');

foreach ($students as $student) {
    $row = [$student->id, fullname($student)];
    foreach ($subjects as $subject) {
        $status = $logmap[$student->id][$subject->id] ?? '-';
        $row[] = $status;
    }
    fputcsv($fp, $row, ',', '"', '\\');
}

fclose($fp);
exit;
