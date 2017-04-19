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
 *  Change round of the mpgame
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( 'lib.php');
require( '../locallib.php');

echo mpgame_GetHeader( get_string( 'set_rounds', 'mpgame'));

mpgame_grandprix_require_login();

mpgame_grandprix_startrounds();

function mpgame_grandprix_startrounds() {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT MAX(id) as id FROM {$CFG->prefix}mpgame_grandprix_rounds ".
    " WHERE mpgameid={$mpgame->id} AND grandprixid={$mpgame->grandprixid}";
    $rec = $DB->get_record_sql( $sql);
    if ($rec->id == 0) {
        $newrec = new StdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->grandprixid = $mpgame->grandprixid;
        $newrec->round = 1;
        $newrec->level = 0;
        $newrec->numquestions = 0;
        $newrec->numpass = 0;
        $mpgame->roundid = $DB->insert_record( 'mpgame_grandprix_rounds', $newrec);
    } else {
        $mpgame->roundid = $rec->id;
    }

    $updrec = new StdClass;
    $updrec->id = $mpgame->grandprix->id;
    $updrec->roundid = $mpgame->roundid;
    $DB->update_record( 'mpgame_grandprix', $updrec);

    $sql = "SELECT COUNT(*) as c FROM {$CFG->prefix}mpgame_grandprix_rounds_users ru, {$CFG->prefix}mpgame_grandprix_rounds r".
    " WHERE ru.roundid=r.id AND r.grandprixid={$mpgame->grandprixid}";
    $rec = $DB->get_record_sql( $sql);
    if ($rec->c != 0) {
        return;
    }

    $sql = "INSERT INTO {$CFG->prefix}mpgame_grandprix_rounds_users(mpgameid,roundid,userid,pass) ".
    " SELECT {$mpgame->id},{$mpgame->roundid},id,0 ".
    " FROM {$CFG->prefix}mpgame_grandprix_users ".
    " WHERE grandprixid={$mpgame->grandprixid}";
    $DB->execute( $sql);
}
