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
$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_course_login($course);
$PAGE->set_url('/mod/subjectattendance/index.php', ['id' => $courseid]);
$PAGE->set_title(get_string('modulenameplural', 'subjectattendance'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
$records = $DB->get_records('subjectattendance', ['course' => $courseid]);
if ($records) {
    echo '<ul>';
    foreach ($records as $r) {
        $cm = get_coursemodule_from_instance('subjectattendance', $r->id);
        $link = new moodle_url('/mod/subjectattendance/view.php', ['id' => $cm->id]);
        echo '<li><a href="' . s($link->out()) . '">' . s($r->name) . '</a></li>';
    }
    echo '</ul>';
} else {
    echo '<p>No instances</p>';
}
echo $OUTPUT->footer();
