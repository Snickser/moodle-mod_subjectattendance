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

/**
 * Indicates which features are supported by the module.
 *
 * @param string $feature The feature constant.
 * @return mixed True/false/null or specific constant depending on feature.
 */
function subjectattendance_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ADMINISTRATION;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        default:
            return null;
    }
}

/**
 * Creates a new subject attendance instance.
 *
 * @param stdClass $data Data from the form.
 * @param mod_form|null $mform The form instance (optional).
 * @return int New instance ID.
 */
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
                [$name, $position] = array_pad(explode('|', trim($line)), 2, 0);
                $DB->insert_record('subjectattendance_subjects', (object)[
                    'attendanceid' => $id,
                    'name' => $name,
                    'position' => $position,
                ]);
            }
        }
    }

    subjectattendance_grade_item_update($data);

    return $id;
}

/**
 * Updates an existing subject attendance instance.
 *
 * @param stdClass $data Data from the form (includes 'instance').
 * @param mod_form|null $mform The form instance (optional).
 * @return bool True on success.
 */
function subjectattendance_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->excluderoles = implode(',', $data->excluderoles);
    $DB->update_record('subjectattendance', $data);

    // Load old subjects.
    $oldsubjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $data->id]);
    $oldnames = [];
    foreach ($oldsubjects as $s) {
        $oldnames[$s->name] = [$s->id, $s->position];
    }

    // Parse new subjects from list.
    $lines = preg_split('/\r?\n/', trim($data->subjectslist));
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, fn($line) => $line !== '');

    $newsubjectids = [];

    foreach ($lines as $line) {
        [$name, $position] = array_pad(explode('|', trim($line)), 2, 0);
        if (isset($oldnames[$name])) {
            if ($oldnames[$name][1] !== $position) {
                $DB->update_record(
                    'subjectattendance_subjects',
                    ['id' => $oldnames[$name][0], 'position' => $position]
                );
            }
            unset($oldnames[$name]);
        } else {
            $DB->insert_record('subjectattendance_subjects', (object)[
                'attendanceid' => $data->id,
                'name' => $name,
                'position' => $position,
            ]);
        }
    }

    // Delete old subjects not in the new list.
    if (!empty($oldnames)) {
        $DB->delete_records_list('subjectattendance_subjects', 'id', array_column($oldnames, 0));
    }

    subjectattendance_update_grades($data);

    return true;
}

/**
 * Deletes a subject attendance instance and its related data.
 *
 * @param int $id Instance ID.
 * @return bool True on success.
 */
function subjectattendance_delete_instance($id) {
    global $DB;

    $subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $id]);
    $subjectids = array_keys($subjects);

    if ($subjectids) {
        [$insql, $params] = $DB->get_in_or_equal($subjectids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('subjectattendance_log', "subjectid $insql", $params);
    }

    $DB->delete_records('subjectattendance_subjects', ['attendanceid' => $id]);
    $DB->delete_records('subjectattendance', ['id' => $id]);

    return true;
}

/**
 * Returns a display option symbol based on the given type and status.
 *
 * @param int $type   Display type (0–6). Defaults to 0.
 * @param string|int $status Status key (''|0|1|2). Empty string or integer value.
 * @return string|false The corresponding symbol if found, or false if not defined.
 */
function get_displayoption($type = 0, $status = '') {
    $displayoptions = [
    0 => ['' => '', 0 => '✖', 1 => '⭘', 2 => '✔'],
    1 => ['' => '', 0 => '❌', 1 => '⚠️', 2 => '✅'],
    2 => ['' => '', 0 => '🟥 ', 1 => '🟨 ', 2 => '🟩 '],
    3 => ['' => '', 0 => '🔴', 1 => '🟡 ', 2 => '🟢 '],
    4 => ['' => '', 0 => '🥉', 1 => '🥈', 2 => '🥇'],
    5 => ['' => '', 0 => '🚷', 1 => '♿', 2 => '💯'],
    6 => ['' => '', 0 => '2', 1 => '3', 2 => '5'],
    ];
    if (isset($displayoptions[$type][$status])) {
        return $displayoptions[$type][$status];
    } else {
        return false;
    }
}

/**
 * Create or update grade item for the activity.
 *
 * @param stdClass $subjectattendance Activity instance
 * @param mixed $grades Grade data or null
 * @return int
 */
function subjectattendance_grade_item_update($subjectattendance, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!isset($subjectattendance->id)) {
        return null;
    }

    $params['itemname'] = $subjectattendance->name;

    if ($subjectattendance->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = (float)($quiz->grade ?? 5);
        $params['grademin']  = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/subjectattendance',
        $subjectattendance->course,
        'mod',
        'subjectattendance',
        $subjectattendance->id,
        0,
        $grades,
        $params
    );
}

/**
 * Update grades in gradebook.
 *
 * @param stdClass $subjectattendance Activity instance
 * @param int $userid User ID, or 0 for all users
 * @param bool $nullifnone Return empty grades if nothing found
 * @return int
 */
function subjectattendance_update_grades($subjectattendance, $userid = 0, $nullifnone = true) {
    global $DB;

    if (empty($subjectattendance->id) || empty($subjectattendance->course)) {
        return false;
    }

    $grades = null;

    if ($userid) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = subjectattendance_calculate_user_grade($subjectattendance, $userid);
        $grades = [$userid => $grade];
    } else {
        $sql = "SELECT DISTINCT l.userid
                  FROM {subjectattendance_log} l
                  JOIN {subjectattendance_subjects} s ON s.id = l.subjectid
                 WHERE s.attendanceid = :attendanceid";

        $userids = $DB->get_fieldset_sql($sql, ['attendanceid' => $subjectattendance->id]);

        if ($userids) {
            $grades = [];
            foreach ($userids as $uid) {
                $grade = new stdClass();
                $grade->userid = $uid;
                $grade->rawgrade = subjectattendance_calculate_user_grade($subjectattendance, $uid);
                $grades[$uid] = $grade;
            }
        }
    }

    if (!$grades && $nullifnone) {
        $grades = [];
    }

    return subjectattendance_grade_item_update($subjectattendance, $grades);
}

/**
 * Calculate grade for one user based on attendance records.
 *
 * @param int $attendanceid Activity instance ID
 * @param int $userid User ID
 * @return float|null
 */
function subjectattendance_calculate_user_grade($subjectattendance, $userid) {
    global $DB;

    $sql = "SELECT s.id AS subjectid, l.status
              FROM {subjectattendance_subjects} s
         LEFT JOIN {subjectattendance_log} l
                ON l.subjectid = s.id
               AND l.userid = :userid
             WHERE s.attendanceid = :attendanceid
          ORDER BY s.id ASC";

    $records = $DB->get_records_sql($sql, [
        'userid' => $userid,
        'attendanceid' => $subjectattendance->id,
    ]);

    if (!$records) {
        return null;
    }

    $grademax = isset($subjectattendance->grade) ? (float)$subjectattendance->grade : 5;
    $ignoreempty = !empty($subjectattendance->emptyignore);

    $totalitems = 0;
    $earneditems = 0;

    foreach ($records as $record) {
        if ($record->status === null) {
            if ($ignoreempty) {
                continue;
            }

            $totalitems++;
            continue;
        }

        $totalitems++;

        switch ((int)$record->status) {
            case 2:
            case 1:
                $earneditems++;
                break;

            case 0:
            default:
                break;
        }
    }

    if ($totalitems === 0) {
        return null;
    }

    return round(($earneditems / $totalitems) * $grademax, $subjectattendance->decimalpoints);
}
