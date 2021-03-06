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
 * class backup_mpgame_activity_task
 * @author
 * @version $Id: backup_mpgame_activity_task.class.php,v 1.2 2012/07/25 11:16:04 bdaloukas Exp $
 * @package game
 **/
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mpgame/backup/moodle2/backup_mpgame_stepslib.php'); // Because it exists (must).
require_once($CFG->dirroot . '/mod/mpgame/backup/moodle2/backup_mpgame_settingslib.php'); // Because it exists (optional).

/**
 * game backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_mpgame_activity_task extends backup_activity_task {

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
        $this->add_step(new backup_mpgame_activity_structure_step('mpgame_structure', 'mpgame.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of games.
        $search = "/(".$base."\/mod\/mpgame\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MPGAMEINDEX*$2@$', $content);

        // Link to game view by moduleid.
        $search = "/(".$base."\/mod\/,[mpgame\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MPGAMEVIEWBYID*$2@$', $content);

        return $content;
    }
}
