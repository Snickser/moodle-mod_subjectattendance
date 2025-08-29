<?php
require_once('../../config.php');
$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
require_course_login($course);
$PAGE->set_url('/mod/subjectattendance/index.php', ['id'=>$courseid]);
$PAGE->set_title(get_string('modulenameplural', 'subjectattendance'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
$records = $DB->get_records('subjectattendance', ['course'=>$courseid]);
if ($records) {
    echo '<ul>';
    foreach ($records as $r) {
        $cm = get_coursemodule_from_instance('subjectattendance', $r->id);
        $link = new moodle_url('/mod/subjectattendance/view.php', ['id'=>$cm->id]);
        echo '<li><a href="'.s($link->out()).'">'.s($r->name).'</a></li>';
    }
    echo '</ul>';
} else { echo '<p>No instances</p>'; }
echo $OUTPUT->footer();
