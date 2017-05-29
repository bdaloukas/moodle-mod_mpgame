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
 * Run every one seconds on clients
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require( '../../../config.php');
require( '../locallib.php');
require( '../quiz/lib.php');

mpgame_quiz_require_login();

if ($mpgame->userid != -1) {
    echo GetHeader('login');
    die( 'No persmission');
}

$sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions WHERE id={$mpgame->quiz->rquestionid}";
$mpgame->question = $DB->get_record_sql( $sql);

if ($mpgame->question != false) {
    $resttime = strtotime( $mpgame->question->timefinish) - time();
    if ($resttime < 0) {
        $resttime = 0;
    }
    $numquestion = $mpgame->question->numquestion;
} else {
    $resttime = 0;
}
echo $resttime.'#';

ShowResults( $resttime);

function showresults( $resttime) {
    global $CFG, $DB, $mpgame, $resttime;

    if ( $resttime > 0) {
        echo get_string( 'rest_time', 'mpgame').': '.$resttime.' '.get_string( 'seconds', 'mpgame').'.<br><br>';
    }

    $mapgrade = $mapanswer = $maptime = array();

    $mapkatataksi = array();

    if ($mpgame->question != false) {
        echo '<b>'.get_string( 'question', 'mpgame'), '</b>: '.$mpgame->question->questiontext2;
    }

    if ($resttime <= 0) {
        if ($mpgame->question != false) {
            if (strtotime( $mpgame->question->timefinish)) {
                echo '<br><b>'.get_string( 'correct_answer_was', 'mpgame').'</b>: '.$mpgame->question->correctanswer.'<br>';
            }
        }
    }

    $sql = "SELECT h.id,h.userid,h.answer,h.grade,h.timehit ".
        " FROM {$CFG->prefix}mpgame_quiz_hits h,{$CFG->prefix}mpgame_quiz_rounds_users ru ".
        " WHERE h.userid=ru.userid AND h.roundid=ru.roundid AND h.todelete=0 ".
        " AND ru.roundid={$mpgame->quiz->roundid} AND h.rquestionid={$mpgame->quiz->rquestionid}";
    $recs = $DB->get_records_sql( $sql);
    foreach ($recs as $rec) {
        $userid = $rec->userid;
        $mapgrade[ $userid] = $rec->grade;
        $s = $rec->answer;
        if ($rec->grade) {
            $s = '<b>'.$s.'</b>';
        }
        $mapanswer[ $userid] = $s;

        $time = substr( $rec->timehit, 10);
        if ($rec->grade) {
            $time = '<b>'.$time.'</b>';
        }
        $maptime[ $userid] = $time;
    }

    $sql = "SELECT id,userid,grade,numquestion,iscorrect ".
    " FROM {$CFG->prefix}mpgame_quiz_hits ".
    " WHERE mpgameid={$mpgame->id} AND roundid={$mpgame->quiz->roundid} AND todelete=0 AND graded=1 AND iscorrect=1".
    " ORDER by numquestion";
    $recs = $DB->get_records_sql( $sql);
    $map = array();
    $mapsum = $mapq = array();
    $maxnum = 0;
    if ($mpgame->question != false) {
        $maxnum = $mpgame->question->numquestion;
    }

    $sqlcorrect = "SELECT COUNT(*) FROM {$CFG->prefix}mpgame_quiz_hits h2 WHERE h2.userid=ru.userid ".
    " AND h2.roundid=ru.roundid AND todelete=0 AND h2.graded=1 AND h2.iscorrect=1";
    $sqltimecorrect = "SELECT SUM(TIMESTAMPDIFF(SECOND, rq.timestart, h2.timehit)) ".
    " FROM {$CFG->prefix}mpgame_quiz_hits h2, {$CFG->prefix}mpgame_quiz_rounds_questions rq ".
    " WHERE h2.userid=ru.userid AND h2.roundid=ru.roundid AND h2.todelete=0 AND h2.iscorrect=1 ".
    " AND rq.id=h2.rquestionid AND h2.roundid=rq.roundid";
    $sql = "SELECT ru.userid,ru.computercode,u.lastname,u.firstname,u.school,ru.pass,u.timelogin,u.roundid".
    ", ($sqlcorrect) as correct ".
    ", ($sqltimecorrect) as timecorrect".
    " FROM {$CFG->prefix}mpgame_quiz_users u, {$CFG->prefix}mpgame_quiz_rounds r,{$CFG->prefix}mpgame_quiz_rounds_users ru ".
    " WHERE ru.roundid={$mpgame->quiz->roundid} AND ru.userid=u.id AND r.id=ru.roundid".
    " ORDER BY ru.computercode";
    $recs = $DB->get_records_sql( $sql);
    echo '<table border=1 cellspacing="0">';
    echo '<tr><td><b>ΑΑ</b></td><td><b>'.get_string( 'admin_student_school', 'mpgame').'</b></td>';
    echo '<td><b>'.get_string( 'quiz_sum_grade', 'mpgame').'</b></td>';
    echo '<td><b>'.get_string( 'quiz_sum_time', 'mpgame').'</td>';
    echo '<td><b>'.get_string( 'answer', 'mpgame').'</b></td>';
    echo '<td><b>'.get_string( 'time', 'mpgame').'</b></td>';

    echo '</tr>';
    $line = 0;

    foreach ($recs as $rec) {
        $userid = $rec->userid;

        echo '<tr>';
        echo '<td>'.(++$line).'/'.$userid.'</td>';
        echo '<td>';
        echo mpgame_quiz_GetFontPass( $rec->pass);
        echo $rec->lastname.' '.$rec->firstname;
        if ($rec->timelogin) {
            if ($rec->roundid == $mpgame->quiz->roundid) {
                echo '*';
            }
        }
        echo '<br>'.$rec->school;
        if ($rec->pass) {
            echo '</b>';
        }
        echo '</td>';
        echo "<td><center><b>{$rec->correct}</center></td>";
        echo "<td><center><b>{$rec->timecorrect}</center></td>";

        if (array_key_exists( $userid, $mapanswer)) {
            $answer = $mapanswer[ $userid];
        } else {
            $answer = '';
        }

        if ($answer == '') {
            $answer = '&nbsp;';
        }
        echo "<td><center>$answer</center></td>";

        if (array_key_exists( $userid, $maptime)) {
            $time = $maptime[ $userid];
        } else {
            $time = '';
        }

        if ($time == '') {
            $time = '&nbsp;';
        }
        echo "<td>$time</td>";
        echo "</tr>\r\n";
    }
    echo '</table>';
}
