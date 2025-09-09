<?php
// This file is part of Moodle - https://moodle.org/.
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
 * Event class for attendance CSV export.
 *
 * @package     mod_subjectattendance
 * @category    event
 * @copyright   2025 Alex Orlov
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subjectattendance\event;

/**
 * Event triggered when attendance data is exported to CSV.
 *
 * This event is fired when a user downloads attendance data
 * from the module `subjectattendance` as a CSV file.
 *
 * @package     mod_subjectattendance
 * @category    event
 */
class attendance_exported extends \core\event\base {
    /**
     * Init method to set basic properties.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'subjectattendance';
    }

    /**
     * Returns the localized event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventattendance_exported', 'mod_subjectattendance');
    }

    /**
     * Returns a description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' exported attendance data to CSV "
             . "for activity with id '{$this->objectid}' in course '{$this->courseid}'.";
    }

    /**
     * Returns a URL related to the event.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/subjectattendance/view.php', ['id' => $this->contextinstanceid]);
    }
}
