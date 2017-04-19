<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for creating and modifying a mpgame
 *
 * @package   mpgame
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once( $CFG->dirroot.'/course/moodleform_mod.php');
require( 'locallib.php');

class mod_mpgame_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB, $COURSE;

        $config = get_config('mpgame');

        $mform =& $this->_form;
        $id = $this->_instance;

        if (!empty($this->_instance)) {
            if ($g = $DB->get_record('mpgame', array('id' => $id))) {
                $gamekind = $g->gamekind;
            } else {
                print_error('incorrect mpgame');
            }
        } else {
            $gamekind = required_param('type', PARAM_ALPHA);
        }

        if ($gamekind == '') {
            $gamekind = 'quiz';
        }
        // Hidden elements.
        $mform->addElement('hidden', 'gamekind', $gamekind);
        $mform->setDefault('gamekind', $gamekind);
        $mform->setType('gamekind', PARAM_ALPHA);
        $mform->addElement('hidden', 'type', $gamekind);
        $mform->setDefault('type', $gamekind);
        $mform->setType('type', PARAM_ALPHA);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', 'Name', array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        if (!isset( $g)) {
            $mform->setDefault('name', get_string( 'mpgame_'.$gamekind, 'mpgame'));
        }
        $mform->addRule('name', null, 'required', null, 'client');

        // Common settings to all games.

        if ( ($gamekind == 'grandprix') or ($gamekind == 'quiz')) {
            $attachmentoptions = array('subdirs' => false, 'maxfiles' => 1);
            $mform->addElement('filepicker', 'questionfileid', get_string('question_file', 'mpgame'), $attachmentoptions);
        }
        // Header/Footer options.

        $features = new stdClass;
        $this->standard_coursemodule_elements($features);

        // Buttons.
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    public function set_data($defaultvalues) {
        global $DB;

        if (!isset( $defaultvalues->gamekind)) {
            $defaultvalues->gamekind = $defaultvalues->type;
        }

        parent::set_data($defaultvalues);
    }
}
