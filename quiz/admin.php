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
 * Form for adminstrating quiz
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( 'lib.php');
require( '../locallib.php');

$quizid = optional_param('quizid', 0, PARAM_INT);
if ($quizid != 0) {
    mpgame_delete_session();
}

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
if ( $id == 0) {
    if (array_key_exists( 'mpgame_cmid', $_SESSION)) {
        $id = $_SESSION[ 'mpgame_cmid'];
    }
}

if (!$cm = get_coursemodule_from_id('mpgame', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

// Check login and get context.
require_login($course->id, false, $cm);
$context = mpgame_get_context_module_instance( $cm->id);

require_capability('mod/mpgame:manage', $context);

mpgame_quiz_require_login( false);

$_SESSION[ 'mpgame_id'] = $mpgame->id;
$_SESSION[ 'mpgame_userid'] = -1;
$_SESSION[ 'mpgame_cmid'] = $id;

if (array_key_exists( 'txtround', $_POST)) {
    mpgame_quiz_OnSetRound( $_POST[ 'txtround']);
    mpgame_quiz_require_login();
}

$sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds WHERE id={$mpgame->quiz->roundid}";
$round = $DB->get_record_sql( $sql);
if ($round != false) {
    echo get_string( 'round', 'mpgame').": {$round->round} quizid={$mpgame->quizid}";
}
echo '<br>';

if ($mpgame->quiz->rquestionid) {
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions WHERE id={$mpgame->quiz->rquestionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
}

if (array_key_exists( 'statquestions', $_GET)) {
    require( 'stats.php');
    mpgame_quiz_onstatquestions();
    die;
}

if (array_key_exists( 'debugquestions', $_GET)) {
    mpgame_quiz_ondebugquestions();
    die;
} else if (array_key_exists( 'txttimer', $_POST)) {
    mpgame_quiz_onstarttimer( intval( $_POST[ 'txttimer']));
} else if (array_key_exists( 'grade', $_GET)) {
    mpgame_quiz_ongrade();
}

if (array_key_exists( 'delgrade', $_GET)) {
    mpgame_quiz_ondelgrade();
} else if (array_key_exists( 'txtcloseround', $_POST)) {
    // Close the round and continue some students.
    mpgame_quiz_oncloseround( intval( $_POST[ 'txtcloseround']), intval( $_POST[ 'txtcountquestions']));
} else if (array_key_exists( 'txtextra', $_POST)) {
    mpgame_quiz_onextrastudents( intval( $_POST[ 'txtextra']));
    mpgame_quiz_require_login();
} else if (array_key_exists( 'txtdisplay', $_POST)) {
    mpgame_quiz_onsetdisplay( intval( $_POST[ 'txtdisplay']));
    mpgame_quiz_require_login();
} else if (array_key_exists( 'txtcolumns', $_POST)) {
    mpgame_quiz_onsetcolumns( intval( $_POST[ 'txtcolumns']));
    mpgame_quiz_require_login();
} else if (array_key_exists( 'save', $_POST)) {
    mpgame_quiz_onsave();
}

$sql = "SELECT COUNT(*) as c FROM {$CFG->prefix}mpgame_quiz_users ".
" WHERE mpgameid={$mpgame->id} AND quizid={$mpgame->quizid}";
$rec = $DB->get_record_sql( $sql);
if ($rec->c == 0) {
    echo "<a href=\"import.php\">".get_string( 'import_students', 'mpgame').'</a> &nbsp; &nbsp; &nbsp;';
    die;
}

$sql = "SELECT COUNT(*) as c FROM {$CFG->prefix}mpgame_quiz_rounds WHERE mpgameid=$mpgame->id";
$rec = $DB->get_record_sql( $sql);
if ($rec->c == 0) {
    echo "<a href=\"random.php\">".get_string( 'start_random', 'mpgame').'</a> &nbsp; &nbsp; &nbsp;';
    die;
}

echo '<table border=1><tr><td>';
mpgame_quiz_showformround();
mpgame_quiz_showformsave();

if (($mpgame->quiz->roundid != 0) and ($mpgame->quiz->rquestionid != 0)) {
    mpgame_quiz_showformtimer();
}
echo '</td>';

if ($mpgame->quiz->roundid != 0) {
    echo '<td>';
    mpgame_quiz_Showformcloseround();
    mpgame_quiz_showformdisplay();
    mpgame_quiz_showformcolumns();
    echo '</td>';
}
echo '</tr></table>';

if (array_key_exists( 'newquestion', $_GET)) {
    // New question.
    mpgame_quiz_selectonequestion();
    mpgame_quiz_loadgameinfo();
    if ($mpgame->quiz->rquestionid == 0) {
        die( 'End of questions');
    }
    mpgame_redirect( 'admin.php');
}

if ($mpgame->quiz->roundid != 0) {
    if (($mpgame->quiz->rquestionid == 0) or ($mpgame->question->graded == 1)) {
        // Have to answer for new question.
        echo "<a href=\"admin.php?newquestion=1\">".get_string( 'new_question', 'mpgame').'</a> &nbsp; &nbsp; &nbsp;';
    }
}

if (isset( $mpgame->question)) {
    // We have an ungraded question.
    // Have to ask if want to start a new round or grade.
    $timefinish = strtotime( $mpgame->question->timefinish);

    if ($timefinish != 0) {
        // Have to grade.
        echo "<a href=\"admin.php?grade=1\">".get_string( 'set_grade', 'mpgame').'</a>';
        echo " &nbsp; &nbsp; &nbsp;<a href=\"admin.php?delgrade=1\">".get_string( 'zero_grade', 'mpgame').'</a>';
    }
}

echo '<div id="divinfo" width=100%></div>';

mpgame_quiz_showjavascript();

function mpgame_quiz_showformtimer() {
    echo '<form name="formtimer" id="formtimer" method="post" action="admin.php">';
    echo get_string( 'time_in_seconds', 'mpgame').': <input type="text" id="txttimer" name="txttimer" size="3" >';
    echo '<input type="submit" value="'.get_string( 'start', 'mpgame').'">';

    echo '</form>';

    echo '<script type="text/JavaScript">';
    echo 'var txt=document.getElementById( "txttimer");';
    echo 'txt.focus();';
    echo '</script>';
}

function mpgame_quiz_showformround() {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT DISTINCT r.round, ".
    " (SELECT COUNT(*) FROM {$CFG->prefix}mpgame_quiz_rounds_users ru WHERE ru.roundid=r.id AND pass=1) as countpass".
    " FROM {$CFG->prefix}mpgame_quiz_rounds_questions rq, {$CFG->prefix}mpgame_quiz_rounds r".
    " WHERE rq.roundid=r.id AND r.mpgameid={$mpgame->id} AND r.level={$mpgame->quiz->level} ORDER BY round";
    $recs = $DB->get_records_sql( $sql);
    $used = array();
    $pass = true;
    foreach ($recs as $rec) {
        $used[ $rec->round] = "{$rec->round}({$rec->countpass})";
        if ($rec->countpass == 0) {
            $pass = false;
        }
    }
    $sql = "SELECT DISTINCT r.round FROM {$CFG->prefix}mpgame_quiz_rounds_users ru, {$CFG->prefix}mpgame_quiz_rounds r".
    " WHERE ru.roundid=r.id AND r.mpgameid={$mpgame->id} AND r.level={$mpgame->quiz->level} ".
    " AND NOT EXISTS( SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions rq WHERE rq.roundid=r.id)".
    " ORDER BY round";
    $recs = $DB->get_records_sql( $sql);
    $notused = array();
    foreach ($recs as $rec) {
        $notused[ $rec->round] = $rec->round;
    }

    echo '<form name="formround" id="formround" method="post" action="admin.php">';
    echo get_string( 'change_round', 'mpgame').': <input type="text" id="txtround" name="txtround" size="2" >';
    echo '<input type="submit" value="'.get_string( 'set', 'mpgame').'"><br>';

    echo ' '.get_string( 'rounds_run', 'mpgame').': '.implode( $used, ',').' ';
    echo get_string( 'rounds_rest', 'mpgame').': '.implode( $notused, ',');
    if ((count( $notused) == 0) and $pass) {
        echo "<a href=\"{$CFG->wwwroot}/mod/mpgame/quiz/random.php\">".get_string( 'end_of_rounds', 'mpgame').'</a>';
    }
    echo '</form>';
}

function mpgame_quiz_showformsave() {
    echo '<form name="formsave" id="formsave" method="post" action="admin.php">';
    echo '<input type="submit" name="save" value="'.get_string( 'save', 'mpgame').'"><br>';
    echo '</form>';
}

function mpgame_quiz_showformcloseround() {
    global $round;

    echo '<form name="formcloseround" id="formcloseround" method="post" action="admin.php">';
    echo get_string( 'count_students_continue', 'mpgame');
    echo ': <input type="text" id="txtcloseround" name="txtcloseround" value="'.$round->numpass.'" size="2" >';
    echo get_string( 'count_questions', 'mpgame');
    echo ': <input type="text" id="txtcountquestions" name="txtcountquestions" value="'.$round->numquestions.'" size="2" >';
    echo '<input type="submit" value="'.get_string( 'update', 'mpgame').'">';
    echo '</form>';
}

function mpgame_quiz_showformdisplay() {
    global $mpgame;

    echo '<form name="formdisplay" id="formdisplay" method="post" action="admin.php">';
    echo get_string( 'results_commands', 'mpgame');
    echo ': <input type="text" id="txtdisplay" name="txtdisplay" size="2" value="'.$mpgame->quiz->displaycommand.'">';
    echo '0:default, 1=Μαθητές γύρου';
    echo '<input type="submit" value="'.get_string( 'set', 'mpgame').'">';
    echo '</form>';
}

function mpgame_quiz_showformcolumns() {
    global $mpgame;

    echo '<form name="formcolumns" id="formcolumns" method="post" action="admin.php">';
    echo get_string( 'cols', 'mpgame');
    echo ': <input type="text" id="txtcolumns" name="txtcolumns" value="'.$mpgame->quiz->displaycols.'" size="2" >';
    echo '<input type="submit" value="'.get_string( 'set', 'mpgame').'">';
    echo '</form>';
}

function mpgame_quiz_selectonequestion_computeused( &$map) {
    global $CFG, $DB, $mpgame;

    $mapused = array();

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions ".
    "WHERE quizid=$mpgame->quizid";
    $recs = $DB->get_records_sql( $sql);
    $used = array();
    foreach ($recs as $rec) {
        $question = $rec->question;
        unset( $map[ $question]);

        $used[ $question] = 1;
        if ($rec->roundid == $mpgame->quiz->roundid) {
            $key = $rec->sheet.'#'.$rec->category.'#'.$rec->kind;
            if (array_key_exists( $key, $mapused)) {
                $mapused[ $key]++;
            } else {
                $mapused[ $key] = 1;
            }
        }
    }

    return $mapused;
}

function mpgame_quiz_selectonequestion_computeall( $map) {
    $mapall = array();

    foreach ($map as $entry) {
        $key = $entry->sheet.'#'.$entry->category.'#'.$entry->kind;
        if (array_key_exists( $key, $mapall)) {
            $mapall[ $key]++;
        } else {
            $mapall[ $key] = 1;
        }
    }

    return $mapall;
}

function mpgame_quiz_selectonequestion_computewhere($mapused, $mapall, &$sheet, &$category, &$kind) {
    $sheet = $category = $kind = '';

    $map = array();
    foreach ($mapall as $key => $allcount) {
        if (array_key_exists( $key, $mapused)) {
            $thiscount = $mapused[ $key];
        } else {
            $thiscount = 0;
        }
        $percent = round( 1000 * $thiscount / $allcount);
        $map[ $key] = $percent;
    }
    if (count( $map) == 0) {
        return;
    }
    asort( $map);
    foreach ($map as $key => $percent) {
        break;  // Finds the smaller percent.
    }

    $a = explode( '#', $key);
    $sheet = $a[ 0];
    $category = $a[ 1];
    $kind = $a[ 2];
}

function mpgame_quiz_selectonequestion_computefind( $map, $sheet, $category, $kind) {
    $maptemp = array(); // This map stores all entries that match.

    foreach ($map as $line => $entry) {
        if (($sheet != '') and ($entry->sheet != $sheet)) {
            continue;
        }
        if (($category != '') and ($entry->category != $category)) {
            continue;
        }
        if (($kind != '') and ($entry->kind != $kind)) {
            continue;
        }

        $maptemp[] = $line;
    }

    if (count( $maptemp) == 0) {
        if ($category != '') {
            return mpgame_quiz_selectonequestion_computefind( $map, $sheet, '', $kind);
        } else if ($sheet != '') {
            return mpgame_quiz_selectonequestion_computefind( $map, '', $category, $kind);
        } else if ($kind != '') {
            return mpgame_quiz_selectonequestion_computefind( $map, $sheet, $category, '');
        } else if (($sheet != '') or ($category != '') or ($kind != '')) {
            return mpgame_quiz_selectonequestion_computefind( $map, '', '', '');
        } else {
            return false;
        }
    }

    return $map[ array_rand( $maptemp)];
}

function mpgame_quiz_selectonequestion() {
    global $CFG, $DB, $mpgame;

    $map = mpgame_quiz_parsequestions();

    // I compute how many questions are used to avoid using them again.
    $mapused = mpgame_quiz_selectonequestion_computeused( $map);

    $mapall = mpgame_quiz_selectonequestion_computeall( $map);

    mpgame_quiz_selectonequestion_computewhere( $mapused, $mapall, $sheet, $category, $kind);
    $entry = mpgame_quiz_selectonequestion_computefind( $map, $sheet, $category, $kind);

    $sql = "SELECT max(numquestion) as max FROM {$CFG->prefix}mpgame_quiz_rounds_questions WHERE roundid={$mpgame->quiz->roundid}";
    $rec = $DB->get_record_sql( $sql);
    if ($rec === false) {
        $numquestion = 1;
    } else {
        $numquestion = $rec->max + 1;
    }

    $questiontext  = mpgame_quiz_writequestion( $entry, $correctanswer, $questiontext2);
    $md5 = md5( $questiontext);
    $questioninfo = implode( '@', $entry->answers);
    $correctanswer = trim( strip_tags( $correctanswer));
    $newrec = new StdClass;
    $newrec->mpgameid = $mpgame->id;
    $newrec->quizid = $mpgame->quizid;
    $newrec->roundid = $mpgame->quiz->roundid;
    $newrec->numquestion = $numquestion;
    $newrec->question = $entry->line;
    $newrec->sheet = $entry->sheet;
    $newrec->category = $entry->category;
    $newrec->kind = $kind;
    $newrec->questiontext = $questiontext;
    $newrec->questiontext2 = $questiontext2;
    $newrec->md5questiontext = md5( $questiontext);
    $newrec->questioninfo = $questioninfo;
    $newrec->correctanswer = $correctanswer;
    $newrec->timestart = date('Y-m-d H:i:s');
    $mpgame->rquestionid = $DB->insert_record( 'mpgame_quiz_rounds_questions', $newrec);

    $updrec = new Stdclass;
    $updrec->id = $mpgame->quizid;
    $updrec->rquestionid = $mpgame->rquestionid;
    $DB->update_record( 'mpgame_quiz', $updrec);
}

function mpgame_quiz_writequestion( $entry, &$correctanswer, &$questiontext2) {
    global $CFG;

    switch (mpgame_quiz_computekindquestion( $entry)) {
        case 'M':
            $s = mpgame_quiz_writequestionm( $entry, $correctanswer, $questiontext2);
            break;
        case 'S':
            $s = mpgame_quiz_writequestions( $entry, $correctanswer, $questiontext2);
            break;
        default:
            die( 'Wrong question');
            break;
    }

    return $s;
}

function mpgame_quiz_writequestions( $entry, &$correctanswer, &$questiontext2) {
    $s = '<b>'.$entry->question.': ';

    $correctanswer = trim( $entry->answers[ 0]);

    $questiontext2 = $s;

    return $s;
}

function mpgame_quiz_writequestionm( $entry, &$correctanswer, &$questiontext2) {
    $a = array();
    for ($i = 0; $i < count( $entry->questions); $i++) {
        $a[ $i] = $entry->questions[ $i];
    }
    mpgame_shuffle_assoc( $a);

    $s = '<b>'.$entry->question.'</b>';
    $num = 1;
    foreach ($a as $id => $answer) {
        $s .= '<br>';
        if ($id == 0) {
            $correctanswer = $num;
        }
        $s .= ($num++).'. '.$answer;
    }

    $questiontext2 = '<b>'.$entry->question.'</b>';
    $questiontext2 .= '<table border=1><tr>';
    $num = 1;
    foreach ($a as $id => $answer) {
        $questiontext2 .= '<td>'.($num++).'. '.$answer.'</td>';
    }
    $questiontext2 .= '</tr></table>';

    return $s;
}

function mpgame_quiz_onstarttimer( $time) {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions rq WHERE id={$mpgame->quiz->rquestionid}";
    $mpgame->question = $DB->get_record_sql( $sql);

    $updrec = new StdClass;
    $updrec->id = $mpgame->question->id;
    $updrec->timefinish = date('Y-m-d H:i:s', time() + $time);
    $DB->update_record( 'mpgame_quiz_rounds_questions', $updrec);
}

function mpgame_quiz_ongrade() {
    global $CFG, $DB, $mpgame;

    $resttime = strtotime( $mpgame->question->timefinish) - time();
    if ($resttime > 0) {
        echo get_string( 'not_finished_time', 'mpgame').'<br>';
        return;
    }

    $sql = "SELECT id, TRIM(UPPER(correctanswer)) as ucorrectanswer ".
    "FROM {$CFG->prefix}mpgame_quiz_rounds_questions WHERE id={$mpgame->quiz->rquestionid}";
    $rec = $DB->get_record_sql( $sql);

    $correct = $rec->ucorrectanswer;
    $sql = "SELECT *,TRIM(UPPER(answer)) as uanswer ".
    " FROM {$CFG->prefix}mpgame_quiz_hits ".
    " WHERE roundid={$mpgame->quiz->roundid} AND rquestionid={$mpgame->quiz->rquestionid} AND todelete=0 ".
    " ORDER BY timehit, id DESC";
    $recs = $DB->get_records_sql( $sql);
    if ($recs === false) {
        return;
    }

    $countcorrect = 0;
    $timeprev = 0;

    foreach ($recs as $hit) {
        if ($hit->uanswer != $correct) {
            $grade = 0;
            $iscorrect = 0;
        } else {
            $iscorrect = 1;
            // The answer is correct.
            $timenew = strtotime( $hit->timehit);
            if ($timenew != $timeprev) {
                $countcorrect++;
            }

            $timeprev = $timenew;
            $grade = 1;
        }
        $updrec = new StdClass;
        $updrec->id = $hit->id;
        $updrec->graded = 1;
        $updrec->grade = $grade;
        $updrec->iscorrect = $iscorrect;
        $DB->update_record( 'mpgame_quiz_hits', $updrec);
    }

    $updrec = new StdClass;
    $updrec->id = $mpgame->quiz->rquestionid;
    $updrec->graded = 1;
    $DB->update_record( 'mpgame_quiz_rounds_questions', $updrec);
}

function mpgame_quiz_ondelgrade() {
    global $CFG, $DB, $mpgame;

    $sql = "UPDATE {$CFG->prefix}mpgame_quiz_hits SET graded=1,grade=0 ".
    " WHERE rquestionid={$mpgame->quiz->rquestionid} AND mpgameid={$mpgame->id} AND roundid={$mpgame->quiz->roundid}";
    $DB->execute( $sql);

    $sql = "UPDATE {$CFG->prefix}mpgame_quiz_rounds_questions SET graded=1 WHERE id={$mpgame->quiz->rquestionid}";
    $DB->execute( $sql);
}

function mpgame_quiz_showjavascript() {
?>
    <script type="text/JavaScript">
        timedRefresh();

        function timedRefresh() 
        {
            timervar = setInterval(function () {OnTimer();}, 1000);
        }

        function OnTimer()
        {
            clearInterval( timervar);

            var oReq = new XMLHttpRequest();
            oReq.onload = reqListener;
            oReq.open("get", "timeradmin.php", true);
            oReq.send();

            timedRefresh();
        }

    function reqListener() {
        var ret = this.responseText;
        var pos=ret.indexOf( "#");

        var timerest = ret.substr( 0, pos);
        ret = ret.substr( pos+1);

        var f = document.getElementById( "divinfo");
        f.innerHTML = ret;
    }
</script>
<?php
}

function mpgame_quiz_onsetround( $round) {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT id FROM {$CFG->prefix}mpgame_quiz_rounds ".
    " WHERE mpgameid={$mpgame->id} AND quizid={$mpgame->quizid} AND round=$round";
    $rec = $DB->get_record_sql( $sql);
    if ($rec === false) {
        die( get_string( 'round_not_found', 'mpgame').' '.$round);
    }
    $updrec = new StdClass;
    $updrec->id = $mpgame->quizid;
    $updrec->roundid = $rec->id;
    $updrec->round = $round;
    $updrec->rquestionid = 0;
    $DB->update_record( 'mpgame_quiz', $updrec);

    echo get_string( 'round_changed', 'mpgame').' '.$round.'<br>';
}

function mpgame_quiz_onsetdisplay( $display) {
    global $CFG, $DB, $mpgame;

    $updrec = new StdClass;
    $updrec->id = $mpgame->quizid;
    $updrec->displaycommand = $display;
    $DB->update_record( 'mpgame_quiz', $updrec);

    echo get_string( 'display_command_sent', 'mpgame').' '.$display.'<br>';
}

function mpgame_quiz_onsetcolumns( $columns) {
    global $CFG, $DB, $mpgame;

    $updrec = new StdClass;
    $updrec->id = $mpgame->quizid;
    $updrec->displaycols = $columns;
    $DB->update_record( 'mpgame_quiz', $updrec);

    echo get_string( 'cols_set_at', 'mpgame').' '.$columns.'.<br>';
}

function mpgame_quiz_oncloseround( $numpass, $numquestions) {
    global $CFG, $DB, $mpgame;

    $updrec = new StdClass;
    $updrec->id = $mpgame->quiz->roundid;
    $updrec->numquestions = $numquestions;
    $updrec->numpass = $numpass;
    $DB->update_record( 'mpgame_quiz_rounds', $updrec);

    $ret = mpgame_quiz_ComputeTopStudents( $numpass, false, $mapequal);

    $ids = implode( $ret, ',');
    if ($ids != '') {
        $sql = "UPDATE {$CFG->prefix}mpgame_quiz_rounds_users SET pass=1 ".
        " WHERE roundid={$mpgame->quiz->roundid} AND userid IN ($ids)";
        $DB->execute( $sql);echo "<hr>$sql<br>";

        $sql = "UPDATE {$CFG->prefix}mpgame_quiz_rounds_users SET pass=0 ".
        " WHERE roundid={$mpgame->quiz->roundid} AND NOT userid IN ($ids)";
        $DB->execute( $sql);echo "<hr>$sql<br>";

        if (count( $mapequal)) {
            $ids = implode( $mapequal, ',');
            $sql = "UPDATE {$CFG->prefix}mpgame_quiz_rounds_users SET pass=3 ".
            " WHERE roundid={$mpgame->quiz->roundid} AND userid IN ($ids)";
            $DB->execute( $sql);echo "<hr>$sql<br>";
        }
    }
}

function mpgame_quiz_computetopstudents( $count, $isextra, &$mapequal) {
    global $CFG, $DB, $mpgame;

    $roundid = $mpgame->quiz->roundid;

    $sql = mpgame_quiz_results_getsql($isextra, false, false);
    $recs = $DB->get_records_sql( $sql);

    $ret = array();
    $prevkey = $prevcorrect = $prevcorrect2 = '';
    $n = 0;
    foreach ($recs as $rec) {
        $n++;
        $key = "$rec->correct#{$rec->correct}#{$rec->correct2}";
        if ($key != $prevkey) {
            $position = $n;
            $prevkey = $key;
        }

        if ($position <= $count) {
            $ret[] = $rec->id;
        } else {
            if ($rec->correct != $prevcorrect) {
                break;
            }
            if ($rec->correct2 != $prevcorrect2) {
                break;
            }
            $mapequal[] = $rec->id;
        }
        $prevcorrect = $rec->correct;
        $prevcorrect2 = $rec->correct2;
    }

    return $ret;
}

function mpgame_quiz_ondebugquestions() {
    $map = mpgame_quiz_parsequestions();
    echo "<table border=1>\r\n";
    echo '<tr><td>'.get_string( 'number', 'mpgame').'</td><td><b>'.get_string( 'book', 'mpgame').'</td>';
    echo '<td><b>'.get_string( 'question', 'mpgame')."</b>\r\n";
    for ($i = 1; $i <= 4; $i++) {
        echo '</td><td><b>'.get_string( 'answer', 'mpgame').$i.'</td>';
    }
    echo "</tr>\r\n";
    $mapcategory = $mapcategorykind = $mapsort = array();
    foreach ($map as $line => $entry) {
        $s = '<tr>';
        $s .= '<td><center>'.$line.'</td>';
        $s .= '<td>'.$entry->category.'</td>';
        $s .= '<td>'.$entry->question.'</td>';
        foreach ($entry->answers as $answer) {
            $s .= "<td>$answer</td>";
        }
        $s .= '</tr>';

        $key = count( $mapsort);
        $mapsort[ $key] = $s;

        $categorykind = $entry->category.'-'.mpgame_quiz_ComputeKindQuestion( $entry->answers);

        if ( array_key_exists( $entry->category, $mapcategory)) {
            $mapcategory[ $entry->category]++;
        } else {
            $mapcategory[ $entry->category] = 1;
        }

        if (array_key_exists( $categorykind, $mapcategorykind)) {
            $mapcategorykind[ $categorykind]++;
        } else {
            $mapcategorykind[ $categorykind] = 1;
        }
    }
    ksort( $mapsort);
    foreach ($mapsort as $line) {
        echo $line;
    }
    echo '</table>';

    echo get_string( 'category_questions', 'mpgame').': ';
    $count = 0;
    foreach ($mapcategory as $category => $value) {
        echo "$category:$value ";
        $count += $value;
    }

    echo get_string( 'kind_questions', 'mpgame').': ';
    foreach ($mapcategorykind as $kind => $value) {
        echo "$kind:$value ";
    }
    echo '<br>'.get_string( 'sum', 'mpgame').": $count<br>";
}

function mpgame_quiz_onsave() {
    global $DB, $mpgame;

    $updrec = new Stdclass;
    $updrec->id = $mpgame->quizid;
    $updrec->savefile = date("Y-m-d H-i-s");
    $DB->update_record( 'mpgame_quiz', $updrec);
}

function mpgame_quiz_parsequestions_htm( $s) {
    global $mpgame;

    $map = $mapcss = array();
    $pos = strpos( $s, '<body ');
    if ($pos != false) {
        $head = substr( $s, 0, $pos);
        $s = substr( $s, $pos);
        $mapcss = mpgame_ParseQuestons_html_head( $head);
    }

    $counter = 0;
    $pos = stripos( strtoupper( $s), '<TABLE');
    if ($pos === false) {
        die( 'There is not exists the table');
        return $map;
    }

    $pos2 = strpos( $s, '>', $pos);
    if ($pos2 == false) {
        return $map;
    }

    $s = substr( $s, $pos2 + 1);

    // Have to read each line.
    $line = 0;
    $first = true;
    $sheet = '';
    for (;;) {
        $pos = stripos( $s, '<TR');
        if ($pos === false) {
            break;
        }

        $pos2 = stripos( strtoupper( $s), '</TR', $pos);
        if ($pos2 === false) {
            break;
        }
        $tr = substr( $s, $pos + 4, $pos2 - $pos - 4);
        $s = substr( $s, $pos2 + 4);

        // Have to read all cols.
        $a = array();
        $row = 0;
        $a = array();
        $line++;
        for (;;) {
            $pos = stripos( strtoupper( $tr), '<TD');

            if ($pos === false) {
                break;
            }
            $pos2 = strpos( $tr, '>', $pos);

            $pos3 = stripos( strtoupper( $tr), '</TD>', $pos);
            $pos4 = strpos( $tr, '>', $pos3);

            $td = substr( $tr, $pos2 + 1, $pos4 - $pos2 - 5);
            $td = str_replace( '&nbsp;', ' ', $td);
            if ($row == 0) {
                $td = strip_tags( $td);
            }
            $tr = substr( $tr, $pos4 + 1);
            if ($td == '') {
                continue;
            }
            if (trim( strip_tags( $td)) == '') {
                continue;
            }
            $row++;
            $a[] = mpgame_ParseQuestion_RepairCSS( trim($td), $mapcss);
        }
        $question = trim( strip_tags($a[ 1]));
        if (strlen( $question) < 4) {
            continue;
        }
        while (count( $a)) {
            $last = trim( strip_tags( $a[ count( $a) - 1]));
            if (strlen( $last) < 3) {
                unset( $a[ count( $a) - 1]);
            } else {
                break;
            }
        }

        if ($first) {
            $first = false;
        } else {
            $entry = new StdClass;
            $entry->sheet = $sheet;
            $entry->category = $a[ 0];
            $entry->question = $a[ 1];
            $entry->answers = array();
            for ($i = 2; $i < count( $a); $i++) {
                $entry->answers[] = $a[ $i];
            }
            $entry->kind = mpgame_quiz_computekindquestion( $entry->answers);
            $entry->line = ++$counter;
            $map[ $counter] = $entry;
        }
    }
    return $map;
}

// Read question from html.
function mpgame_quiz_parsequestions() {
    global $mpgame;

    $file = $mpgame->questionfile;

    $pos = strrpos( $file, '.');
    $ext = strtolower( substr( $file, $pos + 1));

    if (($ext == 'htm') or ($ext == 'html')) {
        return mpgame_quiz_parseQuestions_htm( file_get_contents( $file));
    } 
    
    if ($mpgame->questionfileid != 0) {
        $f = mpgame_get_question_file( $mpgame);
    } else {
        $f = false;
    }
    if ($f === false) {
        die( "Not set questionfileid");
    }

    return mpgame_quiz_parsequestions_htm( $f->get_content());
}
