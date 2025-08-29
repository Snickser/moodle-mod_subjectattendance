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

function subjectattendance_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}
function subjectattendance_add_instance($data, $mform = null) {
    global $DB;
    $data->timecreated = time();
    $id = $DB->insert_record('subjectattendance', $data);
    if (!empty($data->subjectslist)) {
        $lines = preg_split('/\r?\n/', trim($data->subjectslist));
        foreach ($lines as $line) {
            if ($line !== '') {
                $DB->insert_record('subjectattendance_subjects', (object)['attendanceid' => $id, 'name' => $line]);
            }
        }
    }
    return $id;
}
function subjectattendance_update_instance($data, $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    $DB->update_record('subjectattendance', $data);
    $DB->delete_records('subjectattendance_subjects', ['attendanceid' => $data->id]);
    if (!empty($data->subjectslist)) {
        $lines = preg_split('/\r?\n/', trim($data->subjectslist));
        foreach ($lines as $line) {
            if ($line !== '') {
                $DB->insert_record('subjectattendance_subjects', (object)['attendanceid' => $data->id, 'name' => $line]);
            }
        }
    }
    return true;
}
function subjectattendance_delete_instance($id) {
    global $DB;
    $DB->delete_records('subjectattendance_log', ['subjectid' => $id]);
    $DB->delete_records('subjectattendance_subjects', ['attendanceid' => $id]);
    $DB->delete_records('subjectattendance', ['id' => $id]);
    return true;
}
