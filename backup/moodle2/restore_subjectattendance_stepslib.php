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
 * Defines the structure step to restore one mod_subjectattendance activity.
 *
 */
class restore_subjectattendance_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the structure to be restored.
     *
     * @return restore_path_element[].
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('subjectattendance', '/activity/subjectattendance');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process data
     *
     * @param \stdClass $data The data in object form
     * @return void
     */
    protected function process_subjectattendance($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        $newitemid = $DB->insert_record('subjectattendance', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Defines post-execution actions.
     */
    protected function after_execute() {
        // Add page related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_subjectattendance', 'intro', null);
    }
}
