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
                $DB->insert_record('subjectattendance_subjects', (object)[
                    'attendanceid' => $id,
                    'name' => $line,
                ]);
            }
        }
    }
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
        $oldnames[$s->name] = $s->id;
    }

    // Parse new subjects from list.
    $lines = preg_split('/\r?\n/', trim($data->subjectslist));
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, fn($line) => $line !== '');

    $newsubjectids = [];

    foreach ($lines as $line) {
        if (isset($oldnames[$line])) {
            $newsubjectids[] = $oldnames[$line];
            unset($oldnames[$line]);
        } else {
            $newsubjectids[] = $DB->insert_record('subjectattendance_subjects', (object)[
                'attendanceid' => $data->id,
                'name' => $line,
            ]);
        }
    }

    // Delete old subjects not in the new list.
    if (!empty($oldnames)) {
        $DB->delete_records_list('subjectattendance_subjects', 'id', array_values($oldnames));
    }

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
