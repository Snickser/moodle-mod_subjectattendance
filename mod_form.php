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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_subjectattendance_mod_form extends moodleform_mod {
    public function definition() {
        global $DB, $USER;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // устаревшее: $this->add_intro_editor();
        // новое:
        $this->standard_intro_elements();

        // textarea для предметов
        $attrs = ['wrap' => 'virtual', 'rows' => 8, 'cols' => 60];
        $mform->addElement('textarea', 'subjectslist', get_string('subjectslist', 'subjectattendance'), $attrs);
        $mform->setType('subjectslist', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('subjectslist', 'subjectslist', 'subjectattendance');

        // подгружаем существующие предметы при редактировании
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
            ['size' => 8]
        );
        $select->setMultiple(true);
        $mform->setType('excluderoles', PARAM_TEXT); 
        if (isset($this->_customdata['excluderoles'])) {
	    $mform->setDefault('excluderoles', $this->_customdata['excluderoles']);
	}

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
