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

$id = required_param('id', PARAM_INT); // course module id
$cm = get_coursemodule_from_id('subjectattendance', $id, 0, false, MUST_EXIST);

require_login($cm->course, false, $cm);

$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$attendance = $DB->get_record('subjectattendance', ['id' => $cm->instance], '*', MUST_EXIST);

$context = context_module::instance($cm->id);
require_capability('mod/subjectattendance:view', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/subjectattendance/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . $attendance->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($attendance->name));

// --- стили селектов ---
echo '<style>
.attendance-select { width: 56px; font-weight: bold; text-align: center; color: #000; }
.attendance-select.present { background-color: #c8e6c9; } /* зелёный */
.attendance-select.partial { background-color: #90caf9; } /* синий */
.attendance-select.absent  { background-color: #ffcdd2; } /* красный */
.attendance-select.none    { background-color: #fff9c4; } /* жёлтый */
</style>';

// получаем список предметов
$subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $attendance->id], '', 'id, name');

if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
// --- фильтр по группе ---
// список всех групп пользователя в курсе
    if (is_siteadmin()) {
        $allgroups = groups_get_all_groups($course->id);
    } else {
        $allgroups = groups_get_all_groups($course->id, $USER->id);
    }
    $selectedgroup = optional_param('group', 0, PARAM_INT); // 0 = все группы

// если выбранная группа недоступна — используем текущую группу пользователя
    if ($selectedgroup && !array_key_exists($selectedgroup, $allgroups)) {
        $selectedgroup = groups_get_course_group($course, true);
    }

// форма выбора группы
    if ($allgroups) {
        $groupoptions = [0 => get_string('allgroups', 'subjectattendance')];
        foreach ($allgroups as $gid => $g) {
            $groupoptions[$gid] = $g->name;
        }
        echo '<form method="get">';
        echo '<input type="hidden" name="id" value="' . $cm->id . '">';
        echo html_writer::select($groupoptions, 'group', $selectedgroup, false, ['onchange' => 'this.form.submit();']);
        echo '</form><br>';
    }

// получаем студентов по выбранной группе
    if ($selectedgroup && $selectedgroup != 0) {
        $students = get_enrolled_users($context, '', $selectedgroup);
    } else {
        $students = get_enrolled_users($context);
    }

// --- форма для сохранения ---
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'save.php']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attendanceid', 'value' => $attendance->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'cmid', 'value' => $cm->id]);
} else {
    $students[] = $USER;
}

// --- строим таблицу ---
$table = new html_table();
$table->head = array_merge([get_string('fullname')], array_map(fn($s) => $s->name, $subjects));
$table->attributes['class'] = 'generaltable';

foreach ($students as $student) {
    $row = [fullname($student)];

    foreach ($subjects as $subject) {
        $log = $DB->get_record('subjectattendance_log', [
            'subjectid' => $subject->id,
            'userid'    => $student->id,
        ]);

        $status = $log ? $log->status : null;
        $name = "status[{$student->id}][{$subject->id}]";

        // класс по значению
        $class = 'attendance-select';
        if ($status == 2) {
            $class .= ' present';
        } else if ($status == 1) {
            $class .= ' partial';
        } else if ($status == 0) {
            $class .= ' absent';
        } else {
            $class .= ' none';
        }

        // селект с тремя вариантами
        $options = [
            ''  => '',
            0   => '✖',
            1   => '⭘',
            2   => '✔',
        ];
        if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
            $row[] = html_writer::select($options, $name, $status === null ? '' : (string)$status, null, ['class' => $class]);
        } else {
            $row[] = html_writer::tag('p', $options[$status], ['class' => $class]);
        }
    }

    $table->data[] = $row;
}

echo html_writer::table($table);

if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
    echo '<div style="text-align: right; margin-top: 10px;">';
    echo '<input type="submit" value="' . get_string('save') . '" class="btn btn-primary">';
    echo '</div>';
    echo '</form>';

// JS для динамической смены цвета
    echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".attendance-select").forEach(function(select) {
        // установка цвета при загрузке
        let value = select.value;
        select.classList.remove("present","partial","absent","none");
        if (value === "2") select.classList.add("present");
        else if (value === "1") select.classList.add("partial");
        else if (value === "0") select.classList.add("absent");
        else select.classList.add("none");

        // динамическое изменение при выборе
        select.addEventListener("change", function() {
            let val = this.value;
            this.classList.remove("present","partial","absent","none");
            if (val === "2") this.classList.add("present");
            else if (val === "1") this.classList.add("partial");
            else if (val === "0") this.classList.add("absent");
            else this.classList.add("none");
        });
    });
});
</script>';
}

echo '<p><a href="export.php?id=' . $cm->id . '">Export CSV</a></p>';

echo $OUTPUT->footer();
