<?php
require_once('../../config.php');

require_login();
require_sesskey();

// получаем id модуля посещаемости
$attendanceid = required_param('attendanceid', PARAM_INT);

// получаем "сырые" данные массива из POST
$status_raw = $_POST['status'] ?? [];

// безопасная очистка двумерного массива
$status = [];
foreach ($status_raw as $userid => $subjects) {
    $userid = clean_param($userid, PARAM_INT);
    foreach ($subjects as $subjectid => $value) {
        $subjectid = clean_param($subjectid, PARAM_INT);

        // если выбран "-", сохраняем null
        if ($value === '') {
            $status[$userid][$subjectid] = null;
        } else {
            $status[$userid][$subjectid] = clean_param($value, PARAM_INT);
        }
    }
}

// обработка сохранения в БД
foreach ($status as $userid => $subjects) {
    foreach ($subjects as $subjectid => $value) {
        $log = $DB->get_record('subjectattendance_log', [
            'userid'    => $userid,
            'subjectid' => $subjectid
        ]);

        if ($log) {
            $log->status       = $value; // 0,1 или null
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

// получаем правильный cmid для редиректа
$cm = get_coursemodule_from_instance('subjectattendance', $attendanceid, 0, false, MUST_EXIST);

// редирект на страницу модуля с уведомлением
redirect(
    new moodle_url('/mod/subjectattendance/view.php', ['id' => $cm->id]),
    get_string('changessaved')
);
