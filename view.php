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

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_subjectattendance
 * @copyright   2025 Alex Orlov <snickser@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('subjectattendance', $id, 0, false, MUST_EXIST);

require_login($cm->course, false, $cm);

$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$attendance = $DB->get_record('subjectattendance', ['id' => $cm->instance], '*', MUST_EXIST);

$context = context_module::instance($cm->id);
require_capability('mod/subjectattendance:view', $context);

// Trigger event.
subjectattendance_view($attendance, $course, $cm, $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/subjectattendance/view.php', ['id' => $cm->id]);
$PAGE->set_title($course->shortname . ': ' . $attendance->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($attendance->name));

$subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $attendance->id], '', 'id, name');

if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
    if (is_siteadmin()) {
        $allgroups = groups_get_all_groups($course->id);
    } else {
        $allgroups = groups_get_all_groups($course->id, $USER->id);
    }
    $selectedgroup = optional_param('group', 0, PARAM_INT);

    if ($selectedgroup && !array_key_exists($selectedgroup, $allgroups)) {
        $selectedgroup = groups_get_course_group($course, true);
    }

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

    if ($selectedgroup && $selectedgroup != 0) {
        $students = get_enrolled_users($context, '', $selectedgroup);
    } else {
        $students = get_enrolled_users($context);
    }
} else {
    $students[] = $USER;
}

$subjectids = array_keys($subjects);
$logs = $DB->get_records_list('subjectattendance_log', 'subjectid', $subjectids);
$logmap = [];
foreach ($logs as $l) {
    if (!isset($logmap[$l->userid])) {
        $logmap[$l->userid] = [];
    }
    $logmap[$l->userid][$l->subjectid] = $l->status;
}

$table = new html_table();
$table->head = array_merge([get_string('fullname')], array_map(fn($s) => $s->name, $subjects), [get_string('stats')]);
$table->attributes['class'] = 'generaltable';

$sumabsent = 0;
$sumpresent = 0;
$sumpartial = 0;

foreach ($students as $student) {
    if (!empty($attendance->excluderoles)) {
        $roleids = array_map(fn($r) => $r->roleid, get_user_roles($context, $student->id, true));
        $matches = array_intersect($roleids, explode(',', $attendance->excluderoles));
        if ($matches) {
            continue;
        }
    }

    $row = [fullname($student)];

    $countabsent = 0;
    $countpresent = 0;
    $countpartial = 0;

    foreach ($subjects as $subject) {
        $status = isset($logmap[$student->id][$subject->id]) ? $logmap[$student->id][$subject->id] : null;
        $name = "status[{$student->id}][{$subject->id}]";

        $class = 'attendance-select';
        if ($status === '2') {
            $class .= ' present';
            $countpresent++;
        } else if ($status === '1') {
            $class .= ' partial';
            $countpartial++;
        } else if ($status === '0') {
            $class .= ' absent';
            $countabsent++;
        } else {
            $class .= ' none';
        }

        $options = [
        0 => [
            ''  => '',
            0   => '✖',
            1   => '⭘',
        2   => '✔',
        ],
        1 => [
            ''  => '',
            0   => '❌',
            1   => '⚠️',
            2   => '✅',
        ],
        2 => [
            ''  => '',
            0   => '🟥',
            1   => '🟨',
            2   => '🟩',
        ],
        3 => [
            ''  => '',
            0   => '🔴',
            1   => '🟡',
            2   => '🟢',
        ],
        4 => [
            ''  => '',
            0   => '🥉',
            1   => '🥈',
            2   => '🥇',
            ],
        5 => [
            ''  => '',
            0   => '🚷',
            1   => '♿',
            2   => '💯',
            ],
        6 => [
            ''  => '',
            0   => '2',
            1   => '3',
            2   => '5',
            ],
        ];
        if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
            $row[] = html_writer::select(
                $options[$attendance->types],
                $name,
                $status === null ? '' : (string)$status,
                null,
                [
                'class' => $class,
                'data-studentid' => $student->id,
                'data-subjectid' => $subject->id,
                'data-cmid' => $cm->id,
                'data-attendanceid' => $attendance->id,
                ]
            );
        } else {
            $row[] = html_writer::tag('div', $options[$attendance->types][$status], ['class' => $class]);
        }
    }

    $row[] = '<div class="attendance-summary">' .
    ($countpresent ? "<div style='flex: 1; background: #c8e6c9;'>$countpresent</div>" : null) .
    ($countpartial ? "<div style='flex: 1; background: #fff9c4;'>$countpartial</div>" : null) .
    ($countabsent ? "<div style='flex: 1; background: #ffcdd2;'>$countabsent</div>" : null) .
    '</div>';

    $sumabsent += $countabsent;
    $sumpresent += $countpresent;
    $sumpartial += $countpartial;

    $table->data[] = $row;
}

if ($sumpresent + $sumpartial + $sumabsent) {
    $summ = get_string('total') . '<div class="attendance-total-summary">' .
    ($sumpresent ? "<div style='flex: 1; background: #c8e6c9;'>$sumpresent</div>" : null) .
    ($sumpartial ? "<div style='flex: 1; background: #fff9c4;'>$sumpartial</div>" : null) .
    ($sumabsent ? "<div style='flex: 1; background: #ffcdd2;'>$sumabsent</div>" : null) .
    '</div>';
    $table->data[] = array_merge(array_map(fn($s) => '', $subjects), [''], [$summ]);
}

echo html_writer::table($table);

$url = new moodle_url('export.php', ['id' => $cm->id]);
echo html_writer::link(
    $url,
    get_string('exportcsv', 'subjectattendance'),
    ['class' => 'btn btn-secondary', 'target' => '_blank']
);

if (has_capability('mod/subjectattendance:mark', $context, $USER->id)) {
    $PAGE->requires->js_call_amd('mod_subjectattendance/attendance', 'init');
}

echo $OUTPUT->footer();
