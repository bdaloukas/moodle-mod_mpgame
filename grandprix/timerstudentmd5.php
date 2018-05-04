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
 *  Checks every half second
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');

require( 'libmd5.php');

if (!mpgame_grandprix_md5_LoadGameInfo()) {
    die;
}

if ( $mpgame->grandprix->questionid != 0) {
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_questions WHERE id={$mpgame->grandprix->questionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
}

mpgame_grandprix_ComputeTimerStudent( $resttime, $question, $questiontext, $md5, $infoanswer);

echo $resttime.'#';
if( isset( $mpgame->question)) {
    echo get_string( 'question', 'mpgame').": {$mpgame->question->numquestion} &nbsp;&nbsp;".$infoanswer;
}
echo '#'.$md5;

function mpgame_grandprix_md5_loadgameinfo() {
    global $CFG, $DB, $mpgame;

    if (!array_key_exists( 'mpgame_id', $_SESSION)) {
        return false;
    }

    $mpgameid = $_SESSION[ 'mpgame_id'];

    $sql = "SELECT * FROM {$CFG->prefix}mpgame WHERE id=$mpgameid";
    $mpgame = $DB->get_record_sql( $sql);
    if ( $mpgame === false) {
        return false;
    }

    if (array_key_exists( 'mpgame_grandprixid', $_SESSION)) {
        $mpgame->grandprixid = $_SESSION[ 'mpgame_grandprixid'];
    } else {
        return false;
    }
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix WHERE id = {$mpgame->grandprixid}";

    $mpgame->grandprix = $DB->get_record_sql( $sql);

    if ($mpgame->grandprix === false) {
        return false;
    }

    if (!array_key_exists( 'mpgame_userid', $_SESSION)) {
        return false;
    }

    $mpgame->userid = $_SESSION[ 'mpgame_userid'];

    return true;
}
