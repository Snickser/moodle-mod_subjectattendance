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
 * Define the complete structure for backup, with file and id annotations.
 *
 */
class backup_subjectattendance_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the structure of the resulting xml file.
     *
     * @return backup_nested_element The structure wrapped by the common 'activity' element.
     */
    protected function define_structure() {
        $subjectattendance = new backup_nested_element('subjectattendance', ['id'], [
            'course', 'name', 'types', 'excluderoles', 'intro', 'introformat', 'timemodified']);

        $subjectattendance->set_source_table('subjectattendance', ['id' => backup::VAR_ACTIVITYID]);

        // Define file annotations.
        $subjectattendance->annotate_files('mod_subjectattendance', 'intro', null); // This file areas haven't itemid.

        return $this->prepare_activity_structure($subjectattendance);
    }
}
