<?php

function subjectattendance_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        default: return null;
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
