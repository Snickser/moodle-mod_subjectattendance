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
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * The class for plugin.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_subjectattendance_mod_form extends moodleform_mod {
    /**
     * Configuration form
     */
    public function definition() {
        global $DB, $USER;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $attrs = ['wrap' => 'virtual', 'rows' => 8, 'cols' => 60];
        $mform->addElement('textarea', 'subjectslist', get_string('subjectslist', 'subjectattendance'), $attrs);
        $mform->setType('subjectslist', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('subjectslist', 'subjectslist', 'subjectattendance');

        if (!empty($this->current->instance)) {
            $subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $this->current->instance], 'id ASC');
            if ($subjects) {
                $lines = [];
                foreach ($subjects as $s) {
                    $lines[] = $s->name;
                }
                $mform->setDefault('subjectslist', implode("\n", $lines));
            }
        }

        $context = context_user::instance($USER->id);
        $roles = get_default_enrol_roles($context);
        $select = $mform->addElement(
            'select',
            'excluderoles',
            get_string('excluderoles', 'subjectattendance'),
            $roles,
            ['size' => 5]
        );
        $select->setMultiple(true);
        $mform->setType('excluderoles', PARAM_TEXT);
        if (isset($this->_customdata['excluderoles'])) {
            $mform->setDefault('excluderoles', $this->_customdata['excluderoles']);
        }

        $options = [
        0 => '✖ ⭘ ✔',
        1 => '❌ ⚠ ✅',
        2 => '🟥 🟨 🟩',
        3 => '🔴 🟡 🟢',
        4 => '🥉 🥈 🥇',
        5 => '🚷 ♿ 💯',
        6 => '"2" "3" "5"',
        ];
        $select = $mform->addElement(
            'select',
            'types',
            get_string('types', 'subjectattendance'),
            $options,
            ['size' => 8]
        );

        $options = [
        0 => get_string('no', 'core'),
        1 => get_string('always', 'core'),
        2 => get_string('toonly', 'core', '❌ ⚠'),
        3 => get_string('toonly', 'core', '❌'),
        ];
        $select = $mform->addElement(
            'select',
            'notify',
            get_string('notify', 'subjectattendance'),
            $options,
        );
        $mform->addHelpButton('notify', 'notify', 'subjectattendance');

        $options = [
        0 => get_string('no'),
        1 => get_string('yes'),
        2 => get_string('emptyignorecolumn', 'subjectattendance'),
        3 => get_string('emptyignorerow', 'subjectattendance'),
        ];
        $select = $mform->addElement(
            'select',
            'emptyignore',
            get_string('emptyignore', 'subjectattendance'),
            $options,
        );

        $mform->addElement(
            'advcheckbox',
            'numbered',
            get_string('numbered', 'subjectattendance'),
        );

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
