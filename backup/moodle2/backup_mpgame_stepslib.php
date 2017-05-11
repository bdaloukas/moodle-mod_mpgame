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
 * @package mod_game
 * @subpackage backup-moodle2
 * @author bdaloukas
 * @version $Id: backup_game_stepslib.php,v 1.5 2012/07/25 11:16:04 bdaloukas Exp $
 */

/**
 * Define all the backup steps that will be used by the backup_game_activity_task
 */

/**
 * Define the complete game structure for backup, with file and id annotations
 */

defined('MOODLE_INTERNAL') || die();

class backup_mpgame_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $mpgame = new backup_nested_element('mpgame', array('id'), array(
        'gamekind', 'name', 'intro', 'introformat', 'course', 'sourcemodule', 'timemodified', 'grade', 'decimalpoints',
        'questionfile', 'questionfileid'));

        $quizcomputerss = new backup_nested_element('mpgame_quiz_computerss');
        $quizcomputers = new backup_nested_element('mpgame_quiz_computers', array('id'), array(
        'mpgameid', 'quizid', 'ip', 'useragent', 'computercode', 'datetimeinsert'));

        $quizloginss = new backup_nested_element('mpgame_quiz_loginss');
        $quizlogins = new backup_nested_element('mpgame_quiz_logins', array('id'), array(
        'mpgameid', 'quizid', 'userid', 'timelogin', 'ip'));

        $quizuserss = new backup_nested_element('mpgame_quiz_userss');
        $quizusers = new backup_nested_element('mpgame_quiz_users', array('id'), array(
        'mpgameid', 'quizid', 'lastname', 'firstname', 'lastip', 'timelogin', 'level', 'school', 'roundid'));

        $grandprixloginss = new backup_nested_element('mpgame_grandprix_loginss');
        $grandprixlogins = new backup_nested_element('mpgame_grandprix_logins', array('id'), array(
        'mpgameid', 'grandprixid', 'userid', 'timelogin', 'ip'));

        $grandprixuserss = new backup_nested_element('mpgame_grandprix_userss');
        $grandprixusers = new backup_nested_element('mpgame_grandprix_users', array('id'), array(
        'mpgameid', 'grandprixid', 'username', 'name', 'sortorder', 'lastip', 'timelogin',
        'pw', 'password', 'width', 'height'));

        $grandprixs = new backup_nested_element('mpgame_grandprixs');
        $grandprix = new backup_nested_element('mpgame_grandprix', array('id'), array(
        'mpgameid', 'roundid', 'name', 'questionid', 'displaykind', 'displaysort',
        'displaycount', 'displaytop', 'displaytimerefresh', 'displayinfo', 'countquestions'));

        $grandprixroundss = new backup_nested_element('mpgame_grandprix_roundss');
        $grandprixrounds = new backup_nested_element('mpgame_grandprix_rounds', array('id'), array(
        'mpgameid', 'grandprixid', 'round', 'level', 'numquestions', 'numpass'));

        $grandprixhitss = new backup_nested_element('mpgame_grandprix_hitss');
        $grandprixhits = new backup_nested_element('mpgame_grandprix_hits', array('id'), array(
        'mpgameid', 'grandprixid', 'userid', 'ip', 'questionid', 'answer', 'grade', 'graded',
        'timeout', 'todelete', 'timehit'));

        $grandprixquestionss = new backup_nested_element('mpgame_grandprix_questionss');
        $grandprixquestions = new backup_nested_element('mpgame_grandprix_questions', array('id'), array(
        'mpgameid', 'grandprixid', 'numquestion', 'correct', 'grade', 'numanswers', 'questiontext',
        'md5questiontext', 'duration', 'timefinish', 'timechange'));

        $quizs = new backup_nested_element('mpgame_quizs');
        $quiz = new backup_nested_element('mpgame_quiz', array('id'), array(
        'mpgameid', 'name', 'level', 'roundid', 'roundnum', 'randomseed', 'rquestionid',
        'displaycommand', 'displaycols', 'displayextra', 'savefile', 'savefile2', 'text'));

        $quizhitss = new backup_nested_element('mpgame_quiz_hitss');
        $quizhits = new backup_nested_element('mpgame_quiz_hits', array('id'), array(
        'mpgameid', 'quizid', 'userid', 'hostname', 'ip', 'roundid', 'rquestionid',
        'numquestion', 'answer', 'grade', 'graded', 'timeout', 'todelete', 'timehit', 'iscorrect'));

        $quizroundss = new backup_nested_element('mpgame_quiz_roundss');
        $quizrounds = new backup_nested_element('mpgame_quiz_rounds', array('id'), array(
            'mpgameid', 'quizid', 'round', 'level', 'numquestions', 'numpass'));

        $quizroundsquestionss = new backup_nested_element('mpgame_quiz_rounds_questionss');
        $quizroundsquestions = new backup_nested_element('mpgame_quiz_rounds_questions', array('id'), array(
        'mpgameid', 'quizid', 'roundid', 'timestart', 'numquestion', 'timefinish', 'question', 'sheet',
        'category', 'kind', 'questiontext', 'questiontext2', 'md5questiontext', 'correctanswer', 'questioninfo', 'graded'));

        $quizroundsuserss = new backup_nested_element('mpgame_quiz_rounds_userss');
        $quizroundsusers = new backup_nested_element('mpgame_quiz_rounds_users', array('id'), array(
        'quizid', 'roundid', 'userid', 'computercode', 'pass'));

        $grandprixroundsusers = new backup_nested_element('mpgame_grandprix_rounds_users');
        $grandprixroundsuser = new backup_nested_element('mpgame_grandprix_rounds_user', array('id'), array(
        'mpgameid', 'roundid', 'userid', 'pass'));

        // Build the tree.

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $mpgame->add_child( $grandprixs);
            $grandprixs->add_child( $grandprix);

            $grandprix->add_child( $grandprixhitss);
            $grandprixhitss->add_child( $grandprixhits);

            $grandprix->add_child( $grandprixloginss);
            $grandprixloginss->add_child( $grandprixlogins);

            $grandprix->add_child( $grandprixquestionss);
            $grandprixquestionss->add_child( $grandprixquestions);

            $grandprix->add_child( $grandprixroundss);
            $grandprixroundss->add_child( $grandprixrounds);

            $grandprix->add_child( $grandprixuserss);
            $grandprixuserss->add_child( $grandprixusers);

            $grandprixrounds->add_child( $grandprixroundsusers);
            $grandprixroundsusers->add_child( $grandprixroundsuser);

            $mpgame->add_child( $quizs);
            $quizs->add_child( $quiz);

            $quiz->add_child( $quizcomputerss);
            $quizcomputerss->add_child( $quizcomputers);

            $quiz->add_child( $quizhitss);
            $quizhitss->add_child( $quizhits);

            $quiz->add_child( $quizloginss);
            $quizloginss->add_child( $quizlogins);

            $quiz->add_child( $quizroundss);
            $quizroundss->add_child( $quizrounds);

            $quiz->add_child( $quizroundsquestionss);
            $quizroundsquestionss->add_child( $quizroundsquestions);

            $quiz->add_child( $quizroundsuserss);
            $quizroundsuserss->add_child( $quizroundsusers);

            $quiz->add_child( $quizuserss);
            $quizuserss->add_child( $quizusers);
        }

        // Define sources.
        $mpgame->set_source_table('mpgame', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $grandprix->set_source_table('mpgame_grandprix', array('mpgameid' => backup::VAR_ACTIVITYID));

            $grandprixhits->set_source_table('mpgame_grandprix_hits',
            array('mpgameid' => backup::VAR_ACTIVITYID, 'grandprixid' => backup::VAR_PARENTID));

            $grandprixlogins->set_source_table('mpgame_grandprix_logins',
            array( 'mpgameid' => backup::VAR_ACTIVITYID, 'grandprixid' => backup::VAR_PARENTID));

            $grandprixquestions->set_source_table('mpgame_grandprix_questions',
            array( 'mpgameid' => backup::VAR_ACTIVITYID, 'grandprixid' => backup::VAR_PARENTID));

            $grandprixrounds->set_source_table('mpgame_grandprix_rounds',
            array( 'id' => backup::VAR_ACTIVITYID, 'grandprixid' => backup::VAR_PARENTID));

            $grandprixroundsusers->set_source_table('mpgame_grandprix_rounds_user',
            array( 'id' => backup::VAR_ACTIVITYID, 'roundid' => backup::VAR_PARENTID));

            $grandprixusers->set_source_table('mpgame_grandprix_users',
            array( 'id' => backup::VAR_ACTIVITYID, 'grandprixid' => backup::VAR_PARENTID));

            $quiz->set_source_table('mpgame_quiz', array( 'id' => backup::VAR_ACTIVITYID));

            $quizcomputers->set_source_table('mpgame_quiz_computers',
            array( 'id' => backup::VAR_ACTIVITYID, 'quizid' => backup::VAR_PARENTID));

            $quizhits->set_source_table('mpgame_quiz_hits',
            array( 'id' => backup::VAR_ACTIVITYID, 'quizid' => backup::VAR_PARENTID));

            $quizlogins->set_source_table('mpgame_quiz_logins',
            array( 'id' => backup::VAR_ACTIVITYID, 'quizid' => backup::VAR_PARENTID));

            $quizrounds->set_source_table('mpgame_quiz_rounds',
            array( 'id' => backup::VAR_ACTIVITYID, 'quizid' => backup::VAR_PARENTID));

            $quizroundsquestions->set_source_table('mpgame_quiz_rounds_questions',
            array( 'id' => backup::VAR_ACTIVITYID, 'quizid' => backup::VAR_PARENTID));

            $quizroundsusers->set_source_table('mpgame_quiz_rounds_users',
            array( 'id' => backup::VAR_ACTIVITYID, 'quizid' => backup::VAR_PARENTID));

            $quizusers->set_source_table('mpgame_quiz_users',
            array( 'id' => backup::VAR_ACTIVITYID, 'quizid' => backup::VAR_PARENTID));
        }
        // Define id annotations.

        // Return the root element (game), wrapped into standard activity structure.
        $mpgame->annotate_files('mod_mpgame', 'questonfileid', null); // This file area hasn't itemid.

        return $this->prepare_activity_structure( $mpgame);
    }
}
