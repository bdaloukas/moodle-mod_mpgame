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
 * @author  bdaloukas
 * @version $Id: restore_game_activity_task.class.php,v 1.3 2012/07/25 11:16:04 bdaloukas Exp $
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/mpgame/backup/moodle2/restore_mpgame_stepslib.php'); // Because it exists (must).

/**
 * game restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_mpgame_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Game only has one structure step.
        $this->add_step(new restore_mpgame_activity_structure_step('mpgame_structure', 'mpgame.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * game logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('mpgame', 'add', 'view.php?id={course_module}', '{mpgame}');
        $rules[] = new restore_log_rule('mpgame', 'update', 'view.php?id={course_module}', '{mpgame}');
        $rules[] = new restore_log_rule('mpgame', 'view', 'view.php?id={course_module}', '{mpgame}');
        $rules[] = new restore_log_rule('mpgame', 'choose', 'view.php?id={course_module}', '{mpgame}');
        $rules[] = new restore_log_rule('mpgame', 'choose again', 'view.php?id={course_module}', '{mpgame}');
        $rules[] = new restore_log_rule('mpgame', 'report', 'report.php?id={course_module}', '{mpgame}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        // Fix old wrong uses (missing extension).
        $rules[] = new restore_log_rule('mpgame', 'view all', 'index?id={course}', null,
                                        null, null, 'index.php?id={course}');
        $rules[] = new restore_log_rule('mpgame', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

    public function after_restore() {
        // Do something at end of restore.
        global $DB;

        // Get the blockid.
        $mpgameid = $this->get_activityid();

    }
}
