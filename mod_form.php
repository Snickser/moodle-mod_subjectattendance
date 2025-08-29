<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_subjectattendance_mod_form extends moodleform_mod {
    public function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor();

        // атрибуты как массив — надёжнее
        $attrs = array('wrap' => 'virtual', 'rows' => 8, 'cols' => 60);
        $mform->addElement('textarea', 'subjectslist', get_string('subjectslist', 'subjectattendance'), $attrs);
        // сохраняем переносы строк — лучше PARAM_RAW_TRIMMED
        $mform->setType('subjectslist', PARAM_RAW_TRIMMED);

        // при редактировании подгружаем уже существующие предметы в textarea
        if (!empty($this->current->instance)) {
            $subjects = $DB->get_records('subjectattendance_subjects', ['attendanceid' => $this->current->instance], 'id');
            if ($subjects) {
                $lines = array();
                foreach ($subjects as $s) {
                    $lines[] = $s->name;
                }
                $mform->setDefault('subjectslist', implode("\n", $lines));
            }
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
