<?php
require_once('../../config.php');
require_login();

$id = required_param('id', PARAM_INT); // course module id
$cm = get_coursemodule_from_id('subjectattendance', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$attendance = $DB->get_record('subjectattendance', ['id' => $cm->instance], '*', MUST_EXIST);

$context = context_module::instance($cm->id);
require_capability('mod/subjectattendance:view', $context);

$PAGE->set_url('/mod/subjectattendance/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname.': '.$attendance->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($attendance->name));

// получаем список предметов
$subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $attendance->id], 'id');
$students = get_enrolled_users($context, 'mod/subjectattendance:view');

// строим таблицу
$table = new html_table();
$table->head = array_merge([get_string('fullname')], array_map(function($s){ return $s->name; }, $subjects));

foreach ($students as $student) {
    $row = [fullname($student)];

    foreach ($subjects as $subject) {
        // ищем запись в логах только по subjectid и userid
        $log = $DB->get_record('subjectattendance_log', [
            'subjectid' => $subject->id,
            'userid'    => $student->id
        ]);

        if ($log) {
            $status = $log->status; // 0 = отсутствовал, 1 = присутствовал
            $row[] = $status ? '✔' : '✖';
        } else {
            $row[] = '-';
        }
    }
    $table->data[] = $row;
}

echo html_writer::table($table);
echo $OUTPUT->footer();
