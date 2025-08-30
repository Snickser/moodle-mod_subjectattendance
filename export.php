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
require_capability('mod/subjectattendance:view', $context);

$attendance = $DB->get_record('subjectattendance', ['id' => $cm->instance], '*', MUST_EXIST);

// получаем предметы с полями id и name
$subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $attendance->id], 'id ASC', '*');

// проверяем, что есть хотя бы один предмет
if (!$subjects) {
    throw new moodle_exception('nosubjects', 'subjectattendance');
}

if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
    $students = get_enrolled_users($context);
} else {
    $students[] = $USER;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subjectattendance_' . $attendance->id . '.csv"');
echo "\xEF\xBB\xBF"; // BOM для Excel

$fp = fopen('php://output', 'w');

// заголовки
$header = array_merge(['Student ID', 'Student name'], array_map(fn($s) => $s->name, $subjects));
fputcsv($fp, $header);

// строки студентов
foreach ($students as $student) {
    $row = [$student->id, fullname($student)];
    foreach ($subjects as $subject) {
        if (!isset($subject->id)) {
            continue; // пропускаем некорректные записи
        }
        $existing = $DB->get_record('subjectattendance_log', [
            'subjectid' => $subject->id,
            'userid'    => $student->id,
        ]);
        $status = ($existing && isset($existing->status)) ? $existing->status : '-';
        $row[] = $status;
    }
    fputcsv($fp, $row);
}

fclose($fp);
exit;
