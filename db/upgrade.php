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
 * Upgrade script for mod_gwpayments
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_subjectattendance_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025090103) {
        $table = new xmldb_table('subjectattendance');
        $field = new xmldb_field('excluderoles', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025090103, 'subjectattendance');
    }

    if ($oldversion < 2025090400) {
        $table = new xmldb_table('subjectattendance');
        $field = new xmldb_field('types', XMLDB_TYPE_INTEGER, '1', null, null, null, 1, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2025090400, 'subjectattendance');
    }

    return true;
}
