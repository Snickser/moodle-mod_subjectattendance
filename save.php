<?php
require_once('../../config.php');

require_login();
require_sesskey();

// получаем параметры
$attendanceid = required_param('attendanceid', PARAM_INT);
$cmid         = required_param('cmid', PARAM_INT);

// получаем "сырые" данные из POST
$status_raw = $_POST['status'] ?? [];
$status = [];

// безопасная очистка двумерного массива
foreach ($status_raw as $userid => $subjects) {
    $userid = clean_param($userid, PARAM_INT);
    foreach ($subjects as $subjectid => $value) {
        $subjectid = clean_param($subjectid, PARAM_INT);
        $status[$userid][$subjectid] = ($value === '') ? null : clean_param($value, PARAM_INT);
    }
}

// получаем cm и context
$cm = get_coursemodule_from_id('subjectattendance', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$courseid = $cm->course;

// проверка capability
require_capability('mod/subjectattendance:mark', $context);

// проверка доступа ко всем группам
$accessallgroups = has_capability('moodle/site:accessallgroups', $context);

// если обычный преподаватель — получаем доступные группы
$allowedgroups = $accessallgroups ? [] : groups_get_all_groups($courseid, $USER->id);
$allowedgroupids = array_keys($allowedgroups);

// сохраняем данные
foreach ($status as $userid => $subjects) {

    // если пользователь не админ, проверяем группы студента
    if (!$accessallgroups) {
        $studentgroups = groups_get_all_groups($courseid, $userid);
        $studentgroupids = array_keys($studentgroups);

        if (empty(array_intersect($allowedgroupids, $studentgroupids))) {
            continue; // студент не в ваших группах — пропускаем
        }
    }

    foreach ($subjects as $subjectid => $value) {
        $log = $DB->get_record('subjectattendance_log', ['userid' => $userid, 'subjectid' => $subjectid]);

        if ($log) {
            $log->status = $value; // 0,1 или null
            $log->timemodified = time();
            $DB->update_record('subjectattendance_log', $log);
        } else {
            $DB->insert_record('subjectattendance_log', [
                'userid'       => $userid,
                'subjectid'    => $subjectid,
                'status'       => $value, // null для "-"
                'timecreated'  => time(),
                'timemodified' => time()
            ]);
        }
    }
}

// редирект обратно на view.php с уведомлением
redirect(
    new moodle_url('/mod/subjectattendance/view.php', ['id' => $cm->id]),
    get_string('changessaved')
);
