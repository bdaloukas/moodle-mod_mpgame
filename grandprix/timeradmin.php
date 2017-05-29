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
 * Checks every one second.
 *
 */

require( '../../../config.php');
require( '../locallib.php');
require( 'lib.php');

mpgame_grandprix_require_login();

if ($mpgame->grandprix->questionid == 0) {
    die;
}

$sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_questions WHERE id={$mpgame->grandprix->questionid}";
$mpgame->question = $DB->get_record_sql( $sql);

$resttime = $mpgame->question->timefinish - time();

if ($resttime >= 0) {
    echo get_string( 'rest_time', 'mpgame').': '.$resttime.'<br>';
}
$sql = "SELECT COUNT(DISTINCT userid) as c FROM {$CFG->prefix}mpgame_grandprix_hits ".
" WHERE questionid={$mpgame->grandprix->questionid} AND grandprixid={$mpgame->grandprixid}";
$rec = $DB->get_record_sql( $sql);
echo 'Απάντησαν: '.$rec->c.' σχολεία';

if ($resttime <= 0) {
    mpgame_grandprix_ShowSxoleiaNoAnswer();
}

function mpgame_grandprix_showsxoleianoanswer() {
    global $CFG, $DB, $mpgame;

    $sql2 = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_hits h ".
    " WHERE h.userid=u.id AND h.grandprixid={$mpgame->grandprixid} AND h.questionid={$mpgame->grandprix->questionid}";
    $sql = "SELECT u.id,u.name FROM {$CFG->prefix}mpgame_grandprix_users u ".
    " WHERE grandprixid={$mpgame->grandprixid} AND NOT EXISTS($sql2)";
    $recs = $DB->get_records_sql( $sql);
    $s = '';
    foreach ($recs as $rec) {
        $s .= ', '.$rec->name;
    }
    if ($s != '') {
        echo '<br><b>'.get_string( 'not_answer', 'mpgame').': '.substr( $s, 2);
    }
}
