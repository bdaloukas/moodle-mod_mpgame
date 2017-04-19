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
 * Library quiz
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function mpgame_quiz_writetologin() {
    global $DB, $mpgame;

    $ip = mpgame_GetMyIP();

    $newrec = new stdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->quizid = $mpgame->quizid;
        $newrec->ip = $ip;
        $newrec->userid = $mpgame->userid;
        $newrec->timelogin = date('Y-m-d H:i:s', time());
    $id = $DB->insert_record( 'mpgame_quiz_logins', $newrec);

    $updrec = new StdClass;
    $updrec->id = $mpgame->userid;
    $updrec->lastip = $ip;
    $updrec->timelogin = date('Y-m-d H:i:s', time());
    $updrec->roundid = $mpgame->quiz->roundid;

    $DB->update_record( 'mpgame_quiz_users', $updrec);
}

// Returns the position that is randomed each number.
function mpgame_quiz_makeklirosi( $laxeio, $n) {
    $ret = array();
    $temp = array();

    if ($n == 0) {
        return;
    }

    for ($i = 1; $i <= $n; $i++) {
        $temp[] = $i;
    }

    $count = 0;
    while ($n) {
        $pos = $laxeio % $n;

        $x = $temp[ $pos];
        $ret[ $x] = ++$count;
        array_splice( $temp, $pos, 1);
        $n--;
    }
    ksort( $ret);

    return $ret;
}

function mpgame_quiz_computetimeruser( $counter, $studentanswer, $studentroundid, $studentrquestionid,
&$resttime, &$questiontext, &$md5, &$kindquestion, &$infoanswer, &$valueanswer, &$needrefresh) {
    global $CFG, $DB, $mpgame;

    if ($studentroundid != $mpgame->quiz->roundid) {
        // Changed round, so must login the next student.
        $needrefresh = 1;
        return;
    }

    if ($studentrquestionid != $mpgame->quiz->rquestionid) {
        $needrefresh = 1;
        return;
    }

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_users ".
    " WHERE roundid={$mpgame->quiz->roundid} AND userid={$mpgame->userid}";
    $rec = $DB->get_record_sql( $sql);

    if ($rec === false) {
        // Changed round, so must login the next student.
        $needrefresh = 1;
        return;
    }

    if (!isset( $mpgame->question)) {
        return;
    }

    $needrefresh = 0;

    $sql = "SELECT rq.* ".
    " FROM {$CFG->prefix}mpgame_quiz_rounds_questions rq, {$CFG->prefix}mpgame_quiz_rounds_users ru ".
    " WHERE rq.id={$mpgame->quiz->rquestionid} AND ru.userid={$mpgame->userid} ".
    " AND ru.roundid=rq.roundid AND ru.roundid={$mpgame->quiz->roundid}";
    $recs = $DB->get_record_sql( $sql);

    $resttime = strtotime( $mpgame->question->timefinish) - time();

    if ($resttime < 0) {
        $resttime = 0;
    }
    $questiontext = $mpgame->question->questiontext;
    $kindquestion = $mpgame->question->kind;
    $md5 = $mpgame->question->md5questiontext;

    if ($mpgame->quiz->rquestionid == 0) {
        return;
    }
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_hits ".
    " WHERE quizid={$mpgame->quizid} AND rquestionid={$mpgame->quiz->rquestionid} AND userid={$mpgame->userid}".
    " ORDER BY id DESC LIMIT 1";
    $rec = $DB->get_record_sql( $sql);

    if ($rec === false) {
        if ($studentanswer == '') {
            return;
        }
    }

    $infoanswer = '';
    $count = 0;

    if (($counter != 0) or ($studentanswer != '')) {
        if (($rec === false) or ($studentanswer != $rec->answer)) {
            // Have to record the answer.
            mpgame_quiz_appendhit( $counter, $studentanswer);
            $rec = $DB->get_record_sql( $sql);
        }
    }

    if ($rec->timeout) {
        $infoanswer = get_string( 'answer_out_of_time', 'mpgame'). '. ';
    }
    $valueanswer = $rec->answer;
    $infoanswer .= get_string( 'you_typed', 'mpgame').': <b>'.$valueanswer.'</b>';

    if ($rec->graded) {
        if ($rec->grade == 1) {
            $s = '1 βαθμός';
        } else {
            $s = $rec->grade.' βαθμοί';
        }
        if ($rec->grade) {
            $infoanswer = ' '.get_string( 'correct_answer', 'mpgame').' ('.$s.')';
        } else {
            $infoanswer = ' '.get_string( 'correct_answer_was', 'mpgame').': <b>'.$mpgame->question->correctanswer.'</b>';
        }
    }
}

function mpgame_quiz_appendhit( $counter, $answer) {
    global $DB, $CFG, $mpgame;

    if (time() < strtotime( $mpgame->question->timefinish) + 2) {
        $timeout = 0;
    } else {
        $timeout = 1;
    }
    $todelete = $timeout;

    $newrec = new StdClass;
    $newrec->mpgameid = $mpgame->id;
    $newrec->quizid = $mpgame->quizid;
    $newrec->userid = $mpgame->userid;
    $newrec->roundid = $mpgame->quiz->roundid;
    $newrec->numquestion = $mpgame->question->numquestion;
    $newrec->ip = mpgame_GetMyIP();
    $newrec->rquestionid = $mpgame->quiz->rquestionid;
    $newrec->answer = $answer;
    $newrec->timeout = $timeout;
    $newrec->todelete = $todelete;
    $newrec->timehit = date('Y-m-d H:i:s');
    $id = $DB->insert_record( 'mpgame_quiz_hits', $newrec);

    $sql = "UPDATE {$CFG->prefix}mpgame_quiz_hits ".
    " SET todelete=1 ".
    " WHERE id <> $id AND roundid={$mpgame->quiz->roundid} AND rquestionid={$mpgame->quiz->rquestionid} ".
    " AND userid={$mpgame->userid} AND todelete=0";
    $DB->execute( $sql);
}

function mpgame_quiz_loadgameinfo() {
    global $CFG, $DB, $mpgame;

    if (array_key_exists( 'mpgame_quizid', $_SESSION)) {
        $mpgame->quizid = $_SESSION[ 'mpgame_quizid'];
    } else {
        $mpgame->quizid = required_param('quizid', PARAM_INT);   // It stores the mpgameid.
    }
    if ($mpgame->quizid == 0) {
        die( 'Δεν ορίστηκε το quizid');
    }
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz WHERE id = {$mpgame->quizid}";

    $mpgame->quiz = $DB->get_record_sql( $sql);

    if ($mpgame->quiz->mpgameid != $mpgame->id) {
        die( "Wrong mpgameid: $mpgame->id");
    }
    if ($mpgame->quiz != false) {
        $_SESSION[ 'mpgame_quizid'] = $mpgame->quizid;
    } else {
        unset( $_SESSION[ 'mpgame_quizid']);
    }
}

function mpgame_quiz_require_login( $autologin=true) {
    global $CFG, $DB, $mpgame;

    mpgame_loadgameinfo();

    mpgame_quiz_loadgameinfo( $mpgame->id);

    if (array_key_exists( 'mpgame_userid', $_SESSION)) {
        $mpgame->userid = $_SESSION[ 'mpgame_userid'];
        return;
    }

    if ($autologin == false) {
        return;
    }

    // Have to check if exists autologin.
    if (mpgame_quiz_checkautologin()) {
        if ($mpgame->quiz->rquestionid) {
            $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions WHERE id={$mpgame->quiz->rquestionid}";
            $mpgame->question = $DB->get_record_sql( $sql);
        }
        return;
    }
}

function mpgame_quiz_checkautologin() {
    global $CFG, $DB, $mpgame;

    $ip = $_SERVER[ 'REMOTE_ADDR'];
    $useragent = $_SERVER[ 'HTTP_USER_AGENT'];
    $sql = "SELECT id,computercode FROM {$CFG->prefix}mpgame_quiz_computers ".
    " WHERE mpgameid={$mpgame->id} AND quizid={$mpgame->quizid} AND ip=\"$ip\" AND ".
    " useragent=\"$useragent\"";
    $computer = $DB->get_record_sql( $sql);

    $mpgame->user = new StdClass;

    if ($computer === false) {
        // Have to insert.
        $newrec = new stdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->quizid = $mpgame->quizid;
        $newrec->ip = $ip;
        $newrec->useragent = $useragent;
        $id = $DB->insert_record( 'mpgame_quiz_computers', $newrec);

        echo "New computer: $id ip=$ip<br>";
        die;
        return false;
    }
    $mpgame->computerid = $computer->id;
    $mpgame->computercode = $computer->computercode;
    if ($mpgame->computercode < 0) {
        $mpgame->userid = $mpgame->computercode;
        $mpgame->user->level = -$mpgame->computercode;
        return true;
    }
    if ($mpgame->computercode == 0) {
        die( get_string( 'computercode_not_set', 'mpgame')." id={$computer->id} ip=$ip");
    }
    $sql = "SELECT ru.userid, u.lastname, u.firstname, u.school, u.level ".
    " FROM {$CFG->prefix}mpgame_quiz_rounds_users ru, {$CFG->prefix}mpgame_quiz_users u ".
    " WHERE ru.roundid={$mpgame->quiz->roundid} AND computercode={$mpgame->computercode} AND ru.userid=u.id";
    $rec = $DB->get_record_sql( $sql);

    if ($rec === false) {
        echo get_string( 'not_used_computer', 'mpgame')." : $mpgame->computercode<br>";
        $mpgame->userid = 0;
        return false;
    }
    $mpgame->userid = $rec->userid;
    $mpgame->user->level = $rec->level;

    mpgame_quiz_WriteToLogin();

    // Have to update the table logins.
    if ($mpgame->userid != 0) {
        $_SESSION[ 'mpgame_name'] = get_string( 'computer', 'mpgame').' ('.$mpgame->computercode.') - '.
        $rec->lastname.' '.$rec->firstname.' ('.$rec->school.')';
        return true;
    }
}

function mpgame_quiz_results_getsql($isextra, $onlypass, $sortalpha, $allrounds=false) {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT numquestions FROM {$CFG->prefix}mpgame_quiz_rounds WHERE id={$mpgame->quiz->roundid}";
    $round = $DB->get_record_sql( $sql);

    $sqlcorrect = "SELECT COUNT(*) ".
    " FROM {$CFG->prefix}mpgame_quiz_hits h ".
    " WHERE h.roundid=ru.roundid AND h.userid=ru.userid AND todelete=0 AND iscorrect=1";
    if ($round->numquestions > 0) {
        $round->numquestions++;
        $sqlcorrect2 = $sqlcorrect . " AND numquestion>{$round->numquestions}";
        $sqlcorrect .= " AND numquestion<={$round->numquestions}";
    } else {
        $sqlcorrect2 = '';
    }
    $sqltimecorrect = "SELECT SUM(TIMESTAMPDIFF(SECOND, rq.timestart, h2.timehit)) ".
    " FROM {$CFG->prefix}mpgame_quiz_hits h2, {$CFG->prefix}mpgame_quiz_rounds_questions rq ".
    " WHERE h2.userid=ru.userid AND h2.roundid=ru.roundid AND h2.todelete=0 AND h2.iscorrect=1 ".
    " AND rq.id=h2.rquestionid AND h2.roundid=rq.roundid";

    $sql = "SELECT u.id,u.lastname,u.firstname,u.school,ru.computercode,r.round,ru.pass,LEFT(u.school,2) as sch";
    $sql .= " ,($sqlcorrect) as correct";
    $sql .= " ,($sqlcorrect2) as correct2";
    $sql .= " ,($sqltimecorrect) as timecorrect";
    $sql .= " FROM {$CFG->prefix}mpgame_quiz_rounds_users ru, {$CFG->prefix}mpgame_quiz_rounds r, ".
    "{$CFG->prefix}mpgame_quiz_users u ".
    " WHERE ru.roundid=r.id AND r.quizid={$mpgame->quizid} AND ru.userid=u.id ";

    if ( $allrounds == false) {
        $sql .= "AND ru.roundid={$mpgame->quiz->roundid}";
    } else {
        $sql .= "AND r.level={$mpgame->quiz->level}";
    }

    if ( $isextra and $onlypass) {
        $sql .= " AND pass=2";
    } else if ($isextra and ($onlypass == false)) {
        $sql .= " AND pass <> 1 AND ($sqlcorrect) > 0";
    }
    if ($isextra or ($sortalpha == false)) {
        $sql .= " ORDER BY ($sqlcorrect) DESC, ($sqlcorrect2) DESC, ($sqltimecorrect) ";
    } else {
        $sql .= " ORDER BY u.lastname,u.firstname,u.school";
    }

    echo "<hr>$sql<br>";

    return $sql;
}

function mpgame_quiz_getfontpass( $pass) {
    if ($pass == 1) {
        return '<b><font color="red">';
    } else if ($pass == 2) {
        return '<b><font color="green">';
    } else if ($pass == 3) {
        return '<b><font color="blue">';
    } else if ($pass == 0) {
        return '<font color="black">';
    } else if ($pass == -1) {
        return '</b></font>';
    }
}

function mpgame_quiz_computekindquestion( $answers) {
    $count = count( $answers);

    if ($count > 1) {
        return 'M';
    } else if ($count == 1) {
        return 'S';
    } else {
        echo '<hr> '.get_string( 'problem_kind_question', 'mpgame').': $count ';
        die;
        return '';
    }
}
