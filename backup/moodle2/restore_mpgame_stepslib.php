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
 * @package mod_mpgame
 * @subpackage backup-moodle2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_game_activity_task
 */

/**
 * Structure step to restore one game activity
 */
defined('MOODLE_INTERNAL') || die();

class restore_mpgame_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('mpgame', '/activity/mpgame');

        if ($userinfo) {
            $paths[] = new restore_path_element('mpgame_grandprix', '/activity/mpgame/mpgame_grandprixs/mpgame_grandprix');

            $paths[] = new restore_path_element('mpgame_grandprix_hits',
            '/activity/mpgame/mpgame_grandprixs_hitss/mpgame_grandprix_hits');

            $paths[] = new restore_path_element('mpgame_grandprix_logins',
            '/activity/mpgame/mpgame_loginss/mpgame_grandprix_logins');

            $paths[] = new restore_path_element('mpgame_grandprix_questions',
            '/activity/mpgame/mpgame_questionss/mpgame_grandprix_questions');

            $paths[] = new restore_path_element('mpgame_grandprix_users',
            '/activity/mpgame/mpgame_grandprix_userss/mpgame_grandprix_users');

            $paths[] = new restore_path_element('mpgame_grandprix_rounds',
            '/activity/mpgame/mpgame_grandprix_roundss/mpgame_grandprix_rounds');

            $paths[] = new restore_path_element('mpgame_grandprix_rounds_user',
            '/activity/mpgame/mpgame_grandprix_rounds_users/mpgame_grandprix_rounds_user');

            $paths[] = new restore_path_element('mpgame_quiz', '/activity/mpgame/mpgame_quizs/mpgame_quiz');
            $paths[] = new restore_path_element('mpgame_quiz_computers',
            '/activity/mpgame/mpgame_quiz_computerss/mpgame_quiz_computers');
            $paths[] = new restore_path_element('mpgame_quiz_hits',
            '/activity/mpgame/mpgame_quiz_hitss/mpgame_quiz_hits');
            $paths[] = new restore_path_element('mpgame_quiz_logins',
            '/activity/mpgame/mpgame_quiz_loginss/mpgame_quiz_logins');
            $paths[] = new restore_path_element('mpgame_quiz_rounds',
            '/activity/mpgame/mpgame_quiz_roundss/mpgame_quiz_rounds');
            $paths[] = new restore_path_element('mpgame_quiz_rounds_questions',
            '/activity/mpgame/mpgame_quiz_rounds_questionss/mpgame_quiz_rounds_questions');
            $paths[] = new restore_path_element('mpgame_quiz_users',
            '/activity/mpgame/mpgame_quiz_userss/mpgame_quiz_users');
            $paths[] = new restore_path_element('mpgame_quiz_rounds_users',
            '/activity/mpgame/mpgame_quiz_rounds_userss/mpgame_quiz_rounds_users');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_mpgame($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the mpgame record.
        $newitemid = $DB->insert_record('mpgame', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_mpgame_grandprix($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->questionid = 0;
        $newitemid = $DB->insert_record('mpgame_grandprix', $data);

        $this->set_mapping('mpgame_grandprix', $oldid, $newitemid, true);
    }

    protected function process_mpgame_grandprix_hits($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->grandprixid = $this->get_new_parentid('mpgame_grandprix');
        $data->userid = $this->get_mappingid('mpgame_grandprix_users', $data->userid);
        $DB->insert_record('mpgame_grandprix_hits', $data);
    }

    protected function process_mpgame_grandprix_logins($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->grandprixid = $this->get_new_parentid('mpgame_grandprix');
        $data->userid = $this->get_mappingid('mpgame_grandprix_users', $data->userid);
        $DB->insert_record('mpgame_grandprix_hits', $data);
    }

    protected function process_mpgame_grandprix_questions($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->grandprixid = $this->get_new_parentid('mpgame_grandprix');
        $newitemid = $DB->insert_record('mpgame_grandprix_questions', $data);

        $this->set_mapping('mpgame_grandprix_questions', $oldid, $newitemid, true);
    }


    protected function process_mpgame_grandprix_rounds($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->grandprixid = $this->get_new_parentid('mpgame_grandprix');
        $newitemid = $DB->insert_record('mpgame_grandprix_rounds', $data);

        $this->set_mapping('mpgame_grandprix_rounds', $oldid, $newitemid, true);
    }

    protected function process_mpgame_grandprix_users($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->grandprixid = $this->get_new_parentid('mpgame_grandprix');
        $newitemid = $DB->insert_record('mpgame_grandprix_users', $data);

        $this->set_mapping('mpgame_grandprix_users', $oldid, $newitemid, true);
    }

    protected function process_mpgame_grandprix_rounds_user($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->grandprixid = $this->get_new_parentid('mpgame_grandprix');
        $data->userid = $this->get_mappingid('mpgame_grandprix_users', $data->userid);

        $newitemid = $DB->insert_record('mpgame_grandprix_rounds_user', $data);

        $this->set_mapping('mpgame_grandprix_rounds', $oldid, $newitemid, true);
    }

    protected function process_mpgame_quiz($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        // Have to change roundid, rquestionid.

        $newitemid = $DB->insert_record('mpgame_quiz', $data);

        $this->set_mapping('mpgame_quiz', $oldid, $newitemid, true);
    }

    protected function process_mpgame_quiz_computers($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->quizid = $this->get_new_parentid('quiz');
        $DB->insert_record('mpgame_quiz_computers', $data);
    }

    protected function process_mpgame_quiz_hits($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->quizid = $this->get_new_parentid('quiz');
        $data->userid = $this->get_mappingid('mpgame_quiz_users', $data->userid);

        $DB->insert_record('mpgame_quiz_hits', $data);
    }

    protected function process_mpgame_quiz_logins($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->quizid = $this->get_new_parentid('quiz');
        $data->userid = $this->get_mappingid('mpgame_quiz_users', $data->userid);
        $DB->insert_record('mpgame_quiz_logins', $data);
    }

    protected function process_mpgame_quiz_rounds($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->quizid = $this->get_new_parentid('quiz');
        $newitemid = $DB->insert_record('mpgame_quiz_rounds', $data);

        $this->set_mapping('mpgame_quiz_rounds', $oldid, $newitemid, true);
    }

    protected function process_mpgame_quiz_rounds_questions($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->quizid = $this->get_new_parentid('quiz');
        $data->roundid = $this->get_new_parentid('quiz_rounds');
        $newitemid = $DB->insert_record('mpgame_grandprix_hits', $data);

        $this->set_mapping('mpgame_quiz_rounds_questions', $oldid, $newitemid, true);
    }

    protected function process_mpgame_quiz_users($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->quizid = $this->get_new_parentid('quiz');
        $newitemid = $DB->insert_record('mpgame_quiz_users', $data);

        $this->set_mapping('mpgame_quiz_users', $oldid, $newitemid, true);
    }

    protected function process_mpgame_quiz_rounds_users($data) {
        global $DB;

        $data = (object)$data;

        $data->mpgameid = $this->get_new_parentid('mpgame');
        $data->quizid = $this->get_new_parentid('quiz');
        $data->userid = $this->get_new_parentid('mpgame_quiz_users');
        $DB->insert_record('mpgame_quiz_hits', $data);
    }

    protected function after_execute() {
        // Add mpgame related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_mpgame', 'questionfileid', null);
    }
}
