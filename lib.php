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
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ADMINISTRATION;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

// Добавление нового экземпляра модуля
function subjectattendance_add_instance($data, $mform = null) {
    global $DB;
    $data->timecreated  = time();
    $data->timemodified = time();
    $data->excluderoles = implode(',', $data->excluderoles);
    $id = $DB->insert_record('subjectattendance', $data);

    if (!empty($data->subjectslist)) {
        $lines = preg_split('/\r?\n/', trim($data->subjectslist));
        foreach ($lines as $line) {
            if ($line !== '') {
                $DB->insert_record('subjectattendance_subjects', (object)[
                    'attendanceid' => $id,
                    'name' => $line,
                ]);
            }
        }
    }
    return $id;
}

// Обновление существующего экземпляра модуля
function subjectattendance_update_instance($data, $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    $data->excluderoles = implode(',', $data->excluderoles);
    $DB->update_record('subjectattendance', $data);

    // старые предметы
    $oldsubjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $data->id]);
    $oldnames = [];
    foreach ($oldsubjects as $s) {
        $oldnames[$s->name] = $s->id;
    }

    // новые строки из формы
    $lines = preg_split('/\r?\n/', trim($data->subjectslist));
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, fn($line) => $line !== '');

    $newsubjectids = [];

    foreach ($lines as $line) {
        if (isset($oldnames[$line])) {
            // существующий предмет, оставляем id
            $newsubjectids[] = $oldnames[$line];
            unset($oldnames[$line]); // чтобы потом удалить только старые неиспользуемые
        } else {
            // новый предмет
            $newsubjectids[] = $DB->insert_record('subjectattendance_subjects', (object)[
                'attendanceid' => $data->id,
                'name' => $line,
            ]);
        }
    }

    // удаляем только предметы, которые были удалены пользователем
    if (!empty($oldnames)) {
        $DB->delete_records_list('subjectattendance_subjects', 'id', array_values($oldnames));
        // ⚠ старые логи останутся, можно их удалить отдельно или оставить
    }

    return true;
}

// Удаление экземпляра модуля
function subjectattendance_delete_instance($id) {
    global $DB;

    // Получаем все предметы модуля
    $subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $id]);
    $subjectids = array_keys($subjects);

    // Удаляем все логи для этих предметов
    if ($subjectids) {
        [$insql, $params] = $DB->get_in_or_equal($subjectids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('subjectattendance_log', "subjectid $insql", $params);
    }

    // Удаляем предметы и сам модуль
    $DB->delete_records('subjectattendance_subjects', ['attendanceid' => $id]);
    $DB->delete_records('subjectattendance', ['id' => $id]);

    return true;
}
