<?php
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
header('Content-Disposition: attachment; filename="subjectattendance_'.$attendance->id.'.csv"');
$fp = fopen('php://output', 'w');
$header = array_merge(['Student ID','Student name'], array_map(function($s){ return $s->name; }, $subjects));
fputcsv($fp, $header);
foreach ($students as $student) {
    $row = [$student->id, fullname($student)];
    foreach ($subjects as $subject) {
        $existing = $DB->get_record('subjectattendance_log', ['subjectid'=>$subject->id, 'userid'=>$student->id]);
        $status = $existing ? $existing->status : 0;
        $row[] = $status;
    }
    fputcsv($fp, $row);
}
fclose($fp);
exit;
