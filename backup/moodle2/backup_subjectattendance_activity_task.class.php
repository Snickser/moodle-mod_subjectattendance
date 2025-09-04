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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/subjectattendance/backup/moodle2/backup_subjectattendance_stepslib.php');

/**
 * The task that provides all the steps to perform a complete backup is defined here.
 *
 */
class backup_subjectattendance_activity_task extends backup_activity_task {
    /**
     * Defines particular settings for the plugin.
     */
    protected function define_my_settings() {
        // No special settings for this activity.
    }

    /**
     * Defines particular steps for the backup process.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_subjectattendance_activity_structure_step(
            'subjectattendance_structure',
            'subjectattendance.xml'
        ));
    }

    /**
     * Codes the transformations to perform in the activity in order to get transportable (encoded) links.
     *
     * @param string $content
     * @return string.
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, "/");

        // Link to subjectattendance view by moduleid.
        $content = preg_replace(
            "/(" . $base . "\/mod\/subjectattendance\/view.php\?id=)([0-9]+)/",
            '$@SUBJECTATTENDANCEVIEWBYID*$2@$',
            $content
        );

        return $content;
    }
}
