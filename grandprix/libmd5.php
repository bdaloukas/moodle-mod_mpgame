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
 * Library.
 *
 */

defined('MOODLE_INTERNAL') || die();

function mpgame_grandprix_computetimerstudent( &$resttime, &$numquestion, &$questiontext, &$md5, &$infoanswer) {
    global $CFG, $DB, $mpgame;

    if( !isset( $mpgame->question)) {
        $mpgame->question = new StdClass;
        $mpgame->question->timefinish = time();
        $mpgame->question->md5questiontext = '';
        $mpgame->question->questiontext = '';
        $mpgame->question->numquestion = 0;
    }

    $resttime = $mpgame->question->timefinish - time();

    if ($resttime < 0) {
        $resttime = 0;
    }

    $numquestion = $mpgame->question->numquestion;
    $questiontext = $mpgame->question->questiontext;
    $md5 = $mpgame->question->md5questiontext = '';

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_users WHERE id={$mpgame->userid}";
    $user = $DB->get_record_sql( $sql);
    if ($user === false) {
        die( $sql);
    }

    if (array_key_exists( "HTTP_X_FORWARDED_FOR", $_SERVER)) {
        $ip = $_SERVER[ "HTTP_X_FORWARDED_FOR"];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if ($user->lastip != $ip) {
        die( 'Λάθος IP: '.$ip.' user='.$user->username);
    }

    $questionid = 0;
    if( isset( $mpgame->grandprix->questionid)) {
        $questionid = $mpgame->grandprix->questionid;
    }
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_hits ".
    " WHERE questionid=$questionid AND todelete=0 AND userid={$mpgame->userid}";
    $rec = $DB->get_record_sql( $sql);
    $infoanswer = '';
    if ($rec === false) {
        $infoanswer = '<b>'.$user->name.'</b>: '.get_string( 'no_answer', 'mpgame');
    } else {
        $answer = $rec->answer;
        $infoanswer = '<b>'.$user->name.'</b> '.get_string( 'your_answer', 'mpgame').': <b>'.$answer.'</b>';
        $infoanswer .= ' '.get_string( 'correct_answer', 'mpgame');
    }
}
