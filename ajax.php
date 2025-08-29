<?php
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
$record = $DB->get_record('subjectattendance_log', ['subjectid'=>$subjectid, 'userid'=>$userid]);
if ($record) {
    $record->status = $status; $record->timemodified = time();
    $DB->update_record('subjectattendance_log', $record);
} else {
    $DB->insert_record('subjectattendance_log', (object)[
        'subjectid'=>$subjectid,'userid'=>$userid,'status'=>$status,'timemodified'=>time()
    ]);
}
echo json_encode(['success' => true]);
