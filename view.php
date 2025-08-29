<?php
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
$PAGE->set_title($course->shortname.': '.$attendance->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($attendance->name));

// получаем список предметов
$subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $attendance->id], '', 'id, name');

// получаем список студентов
$students = get_enrolled_users($context, 'mod/subjectattendance:view');

// добавляем стили для селектов
echo '<style>
.attendance-select {
    width: 60px;
    font-weight: bold;
    text-align: center;
    color: #000;
}
.attendance-select.present { background-color: #c8e6c9; } /* зелёный */
.attendance-select.absent  { background-color: #ffcdd2; } /* красный */
.attendance-select.none    { background-color: #fff9c4; } /* жёлтый */
</style>';

// начинаем форму
echo '<form method="post" action="save.php">';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
echo '<input type="hidden" name="attendanceid" value="'.$attendance->id.'">';

// строим таблицу
$table = new html_table();
$table->head = array_merge([get_string('fullname')], array_map(function($s){ return $s->name; }, $subjects));
$table->attributes['class'] = 'generaltable';

foreach ($students as $student) {
    $row = [fullname($student)];

    foreach ($subjects as $subject) {
        $log = $DB->get_record('subjectattendance_log', [
            'subjectid' => $subject->id,
            'userid'    => $student->id
        ]);

$status = $log ? $log->status : null; // null = '-' по умолчанию
$name = "status[{$student->id}][{$subject->id}]";

// определяем класс
if ($status === null) {
    $class = 'attendance-select none';
} elseif ($status) {
    $class = 'attendance-select present';
} else {
    $class = 'attendance-select absent';
}

// селект с тремя вариантами
$options = [
    ''  => '',  // по умолчанию
    0   => '✖',
    1   => '✔'
];

$row[] = html_writer::select(
    $options,
    $name,
    $status === null ? '' : $status,
    null,
    ['class' => $class]
);

    }

    $table->data[] = $row;

}

echo html_writer::table($table);

// кнопка сохранения
echo '<input type="submit" value="Сохранить" class="btn btn-primary">';
echo '</form>';

// JS для динамического изменения цвета при выборе
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".attendance-select").forEach(function(select) {
        select.addEventListener("change", function() {
            this.classList.remove("present","absent","none");
            if (this.value == "1") {
                this.classList.add("present");
            } else if (this.value == "0") {
                this.classList.add("absent");
            } else {
                this.classList.add("none");
            }
        });
    });
});
</script>';

echo $OUTPUT->footer();
