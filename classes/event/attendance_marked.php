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

namespace mod_subjectattendance\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a student's attendance is marked via AJAX.
 *
 * @package    mod_subjectattendance
 */
class attendance_marked extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u'; // update (или 'c' если всегда создаём новую запись)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'subjectattendance_log';
    }

    public static function get_name() {
        return get_string('eventattendance_marked', 'mod_subjectattendance');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' marked attendance "
             . "for student with id '{$this->relateduserid}' "
             . "in subject '{$this->other['subjectid']}' "
             . "with status '{$this->other['status']}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/subjectattendance/view.php', ['id' => $this->contextinstanceid]);
    }
}
