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
 *  Show results of quiz
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( '../locallib.php');
require( '../quiz/lib.php');

$id = required_param('id', PARAM_INT); // Is mpgameid.

if (! $cm = get_coursemodule_from_id('mpgame', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

// Check login and get context.
require_login($course->id, false, $cm);
$context = mpgame_get_context_module_instance( $cm->id);

require_capability('mod/mpgame:manage', $context);

mpgame_quiz_require_login( false);

echo mpgame_GetHeader( get_string( 'results', 'mpgame'));

$savefile = date("Y-m-d H-i-s");
$save = ( ($mpgame->quiz->savefile != '') and ($mpgame->quiz->savefile > $mpgame->quiz->savefile2));

if ($mpgame->quiz->rquestionid) {
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions WHERE id={$mpgame->quiz->rquestionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
} else {
    $mpgame->question = false;
}
$showquestion = false;
if ($mpgame->question != false) {
    $resttime = strtotime( $mpgame->question->timefinish) - time();
    if (($resttime <= 0) and ($mpgame->question->graded)) {
        $showquestion = true;   // Have graded.
    }
}

switch ($mpgame->quiz->displaycommand) {
    case 0:
        if ($save) {
            ob_start();
            echo mpgame_GetHeader( get_string( 'results', 'mpgame'));
        }
        mpgame_quiz_ShowResults( $showquestion, $save);
        if ($save) {
            $contents = ob_get_contents();
            mpgame_quiz_savetofile( $contents, $savefile);
            ob_end_clean();
            echo $contents;
        }
        break;
    case 1:
        mpgame_quiz_UsersRound( false);
        break;
    case 2:
        mpgame_quiz_UsersRound( true);
        break;
}

?>               
    <script type="text/JavaScript">
<?php
if (!array_key_exists( 'stop', $_GET)) {
    echo "timedRefresh();\r\n";
}
?>
    function timedRefresh() {
        setTimeout("OnTimer();", 1000);
    }
    
    function OnTimer()
    {
        location.href = 'results.php?id=<?php echo $id;?>';
    
        timedRefresh( );
    }
    </script>
<?php

function mpgame_quiz_results_computeinfoquestion( &$mapgrade, &$mapanswer, &$maptime) {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT h.userid,h.answer,h.grade,h.timehit ".
    " FROM {$CFG->prefix}mpgame_quiz_hits h,{$CFG->prefix}mpgame_quiz_rounds_users ru ".
    " WHERE h.userid=ru.userid AND h.roundid=ru.roundid AND h.todelete=0 ".
    " AND ru.roundid={$mpgame->quiz->roundid} AND h.rquestionid={$mpgame->quiz->rquestionid}";
    $recs = $DB->get_records_sql( $sql);
    foreach ($recs as $rec) {
        $userid = $rec->userid;
        $mapgrade[ $userid] = $rec->grade;
        $s = $rec->answer;
        if ($rec->grade) {
            $s = '<font color="red"><b>'.$s.'</b></font>';
        }
        $mapanswer[ $userid] = $s;

        $time = substr( $rec->timehit, 10);
        if ($rec->grade) {
            $time = '<font color="red"><b>'.$time.'</b></font>';
        }
        $maptime[ $userid] = $time;
    }
}

function mpgame_quiz_results_computeinfosum(&$mapposition, &$mapinfo, &$mapqcurrent, &$mapsumgrade,
&$mapsumgrade2, &$mapsumtime, &$maxnum) {
    global $CFG, $DB, $mpgame;

    $mapqcurrent = array();

    $sql = "SELECT numquestions FROM {$CFG->prefix}mpgame_quiz_rounds WHERE id={$mpgame->quiz->roundid}";
    $round = $DB->get_record_sql( $sql);

    $roundid = $mpgame->quiz->roundid;
    $sql = "SELECT h.id,h.userid,h.grade,h.numquestion,h.timehit,rq.timestart,h.iscorrect ".
    ", TIMESTAMPDIFF(SECOND, rq.timestart, h.timehit) as timecorrect".
    " FROM {$CFG->prefix}mpgame_quiz_hits h LEFT JOIN {$CFG->prefix}mpgame_quiz_rounds_questions rq ON rq.id=h.rquestionid  ".
    " WHERE h.roundid={$roundid} AND h.todelete=0 AND h.graded=1 AND iscorrect=1 ORDER by h.numquestion";
    $recs = $DB->get_records_sql( $sql);
    $mapcorrect = $mapsumgrade = $mapsumtime = array();
    $maxnum = 0;
    foreach ($recs as $rec) {
        $num = $rec->numquestion;
        $userid = $rec->userid;
        $key = $userid.'-'.$num;

        $mapcorrect[ $key] = 1;
        $maptimeanswer[ $key] = $rec->timecorrect;

        if ($num > $maxnum) {
            $maxnum = $num;
        }

        $ispenalty = (($round->numquestions > 0) and ($num > $round->numquestions + 1));
        $grade = ($rec->iscorrect ? 1 : 0);
        if (!array_key_exists( $userid, $mapsumgrade)) {
            $mapsumgrade[ $userid] = 0;
            $mapsumgrade2[ $userid] = 0;
            $mapsumtime[ $userid] = 0;
        }

        if (!$ispenalty) {
            $mapsumgrade[ $userid] += $grade;
        } else {
            $mapsumgrade2[ $userid] += $grade;
        }

        $mapsumtime[ $userid] += $rec->timecorrect;
    }
    $mapsort = array();
    foreach ($mapsumgrade as $userid => $sum) {
        $sum2 = $mapsumgrade2[ $userid];
        $value = sprintf( '%010d-%10d-%10d', pow( 10, 9) - $sum, '%010d-%10d', pow( 10, 9) - $sum2, $mapsumtime[ $userid]);
        $mapsort[ $userid] = $value;
    }
    asort( $mapsort);

    // Compute the position of each user.
    $position = 0;
    $n = 0;
    $mapposition = array();
    $prev = '';
    foreach ($mapsort as $userid => $value) {
        $n++;

        if ($value != $prev) {
            $position = $n;
        }
        $mapposition[ $userid] = $position;
        $prev = $value;
    }

    $mapinfo = array();
    foreach ($mapsort as $userid => $value) {
        $s = '';
        for ($num = 1; $num <= $maxnum; $num++) {
            $key = $userid.'-'.$num;
            if (!array_key_exists( $key, $mapcorrect)) {
                continue;
            }

            if ($s != '') {
                $s .= ', ';
            }
            if ($num == $maxnum) {
                $s .= '<b>';
            }
            $s .= ($num - 1);//.':';
            //$t = $maptimeanswer[ $key];
            //$s .= $t;
            if ($num == $maxnum) {
                $mapqcurrent[ $userid] = 1;;
            }
        }
        $mapinfo[ $userid] = $s;
    }
}

function mpgame_quiz_showresults( $showquestion) {
    global $CFG, $DB, $mpgame, $resttime;

    if ($resttime > 0) {
        echo get_string( 'time_rest1', 'mpgame').' '.$resttime.' '.get_string( 'time_rest2', 'mpgame').'<br><br>';
    }

    $mapgrade = $mapanswer = $maptime = array();

    $sql = "SELECT round FROM {$CFG->prefix}mpgame_quiz_rounds WHERE id={$mpgame->quiz->roundid}";
    $rec = $DB->get_record_sql( $sql);
    if ($rec != false) {
        $roundnum = $rec->round;
    } else {
        $roundnum = '';
    }
    $mapkatataksi = array();
    if ($showquestion) {
        $maxnum = $mpgame->question->numquestion;
        echo '<b>'.get_string( 'round', 'mpgame').': '.$roundnum.' ';
        echo get_string( 'question', 'mpgame').' '.($maxnum - 1).'</b>: '.$mpgame->question->questiontext2;
        echo '<br><b>'.get_string('correct_answer_was', 'mpgame').'</b>: '.$mpgame->question->correctanswer.'<br>';
    }

    if ($showquestion) {
        mpgame_quiz_results_computeinfoquestion( $mapgrade, $mapanswer, $maptime);
    }
    mpgame_quiz_results_computeinfosum( $mapposition, $mapinfo, $mapqcurrent, $mapsumgrade, $mapsumgrade2, $mapsumtime, $maxnum);

    $roundid = $mpgame->quiz->roundid;
    $sql = "SELECT ru.userid,ru.computercode,ru.pass,u.lastname,u.firstname,u.school,u.timelogin,u.roundid".
    " FROM {$CFG->prefix}mpgame_quiz_rounds_users ru, {$CFG->prefix}mpgame_quiz_users u".
    " WHERE ru.roundid={$roundid} AND ru.userid=u.id".
    " ORDER BY ru.computercode";
    $recs = $DB->get_records_sql( $sql);
    if ($showquestion == false) {
        echo '<h1>'.get_string( 'round', 'mpgame').': '.$roundnum;
        if ($maxnum >= 1) {
            echo ' '.get_string( 'question', 'mpgame').': '.($maxnum - 1);
        }
        echo '</h1>';
    }
    echo '<table border=1 cellspacing="0">';
    echo '<tr><td><b>ΑΑ</b></td><td><b>'.get_string( 'position', 'mpgame').'</td>';
    echo '<td><b>'.get_string( 'quiz_student_name', 'mpgame').'</b> / <b>'.get_string( 'quiz_school', 'mpgame').'</b></td>';
    echo '<td><b><center>'.get_string( 'question', 'mpgame')./*':'.get_string( 'results_duration', 'mpgame').*/'</td> ';

    if ($showquestion) {
        echo '<td><b>'.get_string( 'quiz_sum_grade', 'mpgame').'</td>';
        echo /* '<td><b>'.get_string( 'quiz_sum_time', 'mpgame').'</td>*/ '<td><b>'.get_string( 'answer', 'mpgame').'</b></td>';
        //echo '<td><b><center>'.get_string( 'time', 'mpgame').'</b></td>';
    } else {
        echo '<td><b>'.get_string( 'quiz_sum_grade', 'mpgame').'</td>';
        //echo '</td><td><b>'.get_string( 'quiz_sum_time', 'mpgame').'</td>';
    }

    echo '</tr>';
    $line = 0;
    foreach ($recs as $rec) {
        $userid = $rec->userid;

        echo '<tr>';
        echo '<td><center>'.(++$line).'</td>';
        echo '<td><center>';
        if (array_key_exists( $userid, $mapposition)) {
            echo $mapposition[ $userid];
        } else {
            echo count( $mapposition) + 1;
        }
        echo '</center></td>';

        // Name.
        echo '<td>';
        echo mpgame_quiz_GetFontPass( $rec->pass);
        if (strtotime( $rec->timelogin)) {
            if ($rec->roundid == $roundid) {
                echo '*';
            }
        }
        echo $rec->lastname.' '.$rec->firstname;
        echo '<br>'.$rec->school.'</td>';
        $sum = '';

        $c = 0;
        echo '<td>';
        if (array_key_exists( $userid, $mapinfo)) {
            echo $mapinfo[ $userid];
        } else {
            echo '&nbsp;';
        }
        echo '</td>';

        // Sum of grades.
        echo '<td><center>';
        echo mpgame_quiz_getfontpass( $rec->pass);
        if (array_key_exists( $userid, $mapsumgrade)) {
            echo $mapsumgrade[ $userid];
        } else {
            echo '&nbsp;';
        }
        
        if( $mapsumgrade2 != null) {
            if( array_key_exists( $userid, $mapsumgrade2)) {
                if ($mapsumgrade2[ $userid] > 0) {
                    echo '/'.$mapsumgrade2[ $userid];
                }
            }
        }
        echo '</td>';

/*        echo '<td><center>';
        if (array_key_exists( $userid, $mapsumtime)) {
            echo $mapsumtime[ $userid]. ' δευτ.';
        } else {
            echo '&nbsp;';
        }
        echo '</td>';
*/
        $font = '';

        if ($showquestion) {
            // Grade of question.
            if (array_key_exists( $userid, $mapqcurrent)) {
                $font = '<font color="red">';
            }
            if (array_key_exists( $userid, $mapanswer)) {
                $answer = $mapanswer[ $userid];
            } else {
                $answer = '';
            }
            if ($answer == '') {
                $answer = '&nbsp;';
            }
            echo "<td><center>{$font}$answer</center></td>";

            if (array_key_exists( $userid, $maptime)) {
                $time = $maptime[ $userid];
            } else {
                $time = '';
            }
            if ($time == '') {
                $time = '&nbsp;';
            }
            //echo "<td>{$font}$time</td>";
        }
        echo "</tr>\r\n";
    }
    echo '</table>';
}

function mpgame_quiz_usersround( $isextra) {
    global $CFG, $DB, $mpgame;

    $recs = $DB->get_records_sql( mpgame_quiz_results_getsql($isextra, false, true, true));
    $line = 0;
    $cols = ($isextra ? 1 : $mpgame->quiz->displaycols);echo "cols=$cols<br>";
    $count = count( $recs);
    $countpass1 = 0;
    echo '<table border=1><tr><td>';
    foreach ($recs as $rec) {
        $line++;
        for ($i = 1; $i < $cols; $i++) {
            if ($line == round($i * $count / $cols)) {
                echo '</td><td>';
            }
        }

        echo mpgame_quiz_getfontpass( $rec->pass);
        echo "{$rec->lastname} {$rec->firstname} Σ:{$rec->sch} Γ:{$rec->round} Y:{$rec->computercode}";
        if ($isextra) {
            echo ' '.get_string( 'quiz_corrects', 'mpgame').': '.$rec->correct.' ';
            echo get_string( 'quiz_time', 'mpgame').": {$rec->timecorrect}";
        }
        echo mpgame_quiz_getFontPass( -1);

        if ($rec->pass == 1) {
            $countpass1++;
        }

        if ($isextra and ($line >= 12)) {
            break;
        }
        echo '<br>';
    }
    echo '</td></tr></table>';
    echo get_string( 'sum', 'mpgame').": $count";
    if ($countpass1) {
        echo ' '.get_string( 'quiz_pass', 'mpgame').': '.$countpass1;
    }
}

function mpgame_quiz_savetofile( $s, $savefile) {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT round FROM {$CFG->prefix}mpgame_quiz_rounds WHERE id={$mpgame->quiz->roundid}";
    $round = $DB->get_record_sql( $sql);

    $tempfile = tempnam( sys_get_temp_dir(), 'mpgame');
    file_put_contents( $tempfile, $s);

    $newfile = $round->round.'-'.date("Y-m-d H-i-s").'.html';

    copy( $tempfile, '/home/administrator/temp/'.$newfile);

    $updrec = new Stdclass;
    $updrec->id = $mpgame->quizid;
    $updrec->savefile2 = $savefile;
    $DB->update_record( 'mpgame_quiz', $updrec);

    echo "<hr>$newfile<br>";
}
