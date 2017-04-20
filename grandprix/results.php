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
 *  Show results of grandpix
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( '../locallib.php');
require( 'lib.php');

$id = required_param('id', PARAM_INT); // Use mpgameid.

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

mpgame_LoadGameInfo();

mpgame_grandprix_LoadGameInfo( $mpgame->id);

if ($mpgame->grandprix->questionid != 0) {
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_questions WHERE id={$mpgame->grandprix->questionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
}
if ($mpgame->grandprix->displaytimerefresh == 0) {
    $mpgame->grandprix->displaytimerefresh = 5;
}

echo mpgame_GetHeader( get_string( 'results', 'mpgame'));

$grades = mpgame_grandprix_results_ComputeSumGrades( $katataksi, $extra);

if (isset( $mpgame->question)) {
    $resttime = $mpgame->question->timefinish - time();
    if ( $mpgame->grandprix->displayinfo) {
        echo get_string( 'question', 'mpgame').': <b>'.$mpgame->question->numquestion.'</b> &nbsp;&nbsp;';
    }
} else {
    $resttime = 0;
}

if ($resttime > 0) {
    echo get_string( 'resttime', 'mpgame').': <b>'.$resttime.'</b> &nbsp;';
}

$sql = "SELECT COUNT(*) as c ".
" FROM {$CFG->prefix}mpgame_grandprix_users u, {$CFG->prefix}mpgame_grandprix_rounds_users ru".
" WHERE ru.roundid={$mpgame->grandprix->roundid} AND ru.userid=u.id";
$rec = $DB->get_record_sql( $sql);
$countsx = $rec->c;

if ($mpgame->grandprix->displayinfo) {
    if ($mpgame->grandprix->questionid != 0) {
        $sql = "SELECT COUNT(DISTINCT userid) as c FROM {$CFG->prefix}mpgame_grandprix_hits ".
        " WHERE questionid={$mpgame->grandprix->questionid} AND grandprixid={$mpgame->grandprixid}";
        $rec = $DB->get_record_sql( $sql);
        echo ' &nbsp;&nbsp; '.get_string( 'students_answer', 'mpgame').': <b>'.$rec->c.'</b>';
    }
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.get_string( 'count_students', 'mpgame').': <b>'.$countsx.'</b>';
}

mpgame_grandprix_results_ShowSumData( $rec, $grades, $katataksi, $extra, $countsx);

function flusharray( &$a, &$lines, $maxquestion, $countq, $userid, $katataksi) {
    $j = 0;

    if ($katataksi == false) {
        $s = '';
    } else {
        $s = '<td><center>'.$katataksi[ $userid].'</center></td>';
    }

    for ($i = $maxquestion; $i > 0; $i--) {
        if (array_key_exists( $i, $a)) {
            $s .= '<td><center>'.$a[ $i].'</td>';
        } else {
            $s .= '<td><center>0</center></td>';
        }
        ++$j;

        if ($j >= $countq) {
            break;
        }
    }
    $lines[ $userid] = $s;

    $a = array();
}

function mpgame_grandprix_results_computesumgrades_computescore( &$grades, &$extra) {
    global $CFG, $DB, $mpgame;

    $grades = $extra = array();
    if ($mpgame->grandprix->countquestions != 0) {
        $sql = "SELECT h.userid,COUNT(*) as countgrade ".
        " FROM {$CFG->prefix}mpgame_grandprix_hits h ,{$CFG->prefix}mpgame_grandprix_questions q";
        $sql .= " WHERE h.todelete=0 AND h.grade>0 AND h.grandprixid={$mpgame->grandprixid}";
        $sql .= " AND q.id=h.questionid AND q.numquestion > {$mpgame->grandprix->countquestions}";
        $sql .= " GROUP BY userid";
        $recs = $DB->get_records_sql( $sql);
        foreach ($recs as $rec) {
            $extra[ $rec->userid] = $rec->countgrade;
        }
    }

    $sql = "SELECT h.userid,SUM(h.grade) as sumgrade,COUNT(*) as countgrade ".
    " FROM {$CFG->prefix}mpgame_grandprix_hits h ";
    if ($mpgame->grandprix->countquestions != 0) {
        $sql .= ",{$CFG->prefix}mpgame_grandprix_questions q";
    }
    $sql .= " WHERE h.todelete=0 AND h.grade>0 AND h.grandprixid={$mpgame->grandprixid}";
    if ($mpgame->grandprix->countquestions != 0) {
        $sql .= " AND q.id=h.questionid AND q.numquestion <= {$mpgame->grandprix->countquestions}";
    }
    $sql .= " GROUP BY userid";
    $recs = $DB->get_records_sql( $sql);

    $grades = $grades2 = $scores = array();
    foreach ($recs as $rec) {
        $grades[ $rec->userid] = $rec->sumgrade;
        if (array_key_exists( $rec->userid, $extra)) {
            $e = $extra[ $rec->userid];
        } else {
            $e = 0;
        }
        $score = sprintf( '%10d-%10d-%10d', $rec->sumgrade, $rec->countgrade, $e);
        $scores[ $rec->userid] = $score;
    }

    $sql = "SELECT u.* ".
    " FROM {$CFG->prefix}mpgame_grandprix_users u, {$CFG->prefix}mpgame_grandprix_rounds_users ru".
    " WHERE ru.roundid={$mpgame->grandprix->roundid} AND ru.userid=u.id".
    " ORDER BY u.sortorder";
    $recs = $DB->get_records_sql( $sql);
    foreach ($recs as $rec) {
        if (!array_key_exists( $rec->id, $scores)) {
            $scores[ $rec->id] = 0;
        }
    }

    return $scores;
}

function mpgame_grandprix_results_computesumgrades( &$katataksi, &$extra) {
    $scores = mpgame_grandprix_results_computesumgrades_computescore( $grades, $extra);
    arsort( $scores);

    $doublescore = array();
    foreach ($scores as $userid => $score) {
        if (!array_key_exists( $score, $doublescore)) {
            $doublescore[ $score] = 1;
        } else {
            $doublescore[ $score] += 1;
        }
    }

    $katataksi = array();
    $seira = 0;
    $lastscore = -1;
    foreach ($scores as $userid => $score) {
        if ($doublescore[ $score] == 1) {
            $katataksi[ $userid] = ++$seira;
        } else {
            ++$seira;
            if ($score != $lastscore) {
                $lastseira = $seira;
                $lastscore = $score;
            }

            $katataksi[ $userid] = $lastseira;
        }
    }

    return $grades;
}

?>               
    <script type="text/JavaScript">
    function timedRefresh(timeoutPeriod) {
        setTimeout("OnTimer();",timeoutPeriod);

        var field = document.forms['formTop'].elements['top'];
        field.focus();
        field.select();
    }

    function OnTimer() {
        location.href = 'results.php?id=<?php echo $id;?>';

        timedRefresh( <?php echo 1000 * $mpgame->grandprix->displaytimerefresh; ?>);
    }
    timedRefresh( <?php echo 1000 * $mpgame->grandprix->displaytimerefresh; ?>);
    </script>
<?php

function mpgame_grandprix_results_showsumdata( $not, $grades, $katataksi, $extra, $count) {
    global $CFG, $DB, $mpgame;

    if (($mpgame->grandprix->displaytop < $count) and ($mpgame->grandprix->displaytop != 0)) {
        $count = $mpgame->grandprix->displaytop;
    }
    if (isset( $mpgame->question)) {
        $maxquestion = $mpgame->question->numquestion;
    } else {
        $maxquestion = 0;
    }
    if ($mpgame->grandprix->displaycount == 0) {
        $mpgame->grandprix->displaycount = $maxquestion;
    }

    $lines = array();

    $sql = "SELECT h.id,h.userid,q.numquestion,SUM(h.grade) as sumgrade ".
    " FROM {$CFG->prefix}mpgame_grandprix_hits h, {$CFG->prefix}mpgame_grandprix_questions q ".
    " WHERE h.questionid=q.id AND h.todelete=0 AND h.grandprixid={$mpgame->grandprixid}".
    " AND q.numquestion > ".($maxquestion - $mpgame->grandprix->displaycount).
    " GROUP BY h.userid,q.numquestion ORDER BY h.userid,q.numquestion DESC";
    $recs = $DB->get_records_sql( $sql);

    $a = array();
    $userid = 0;
    foreach ($recs as $rec) {
        if ($userid != $rec->userid) {
            if ($userid != 0) {
                FlushArray( $a, $lines, $maxquestion, $mpgame->grandprix->displaycount, $userid, false);
            }
            $userid = $rec->userid;
        }
        $a[ $rec->numquestion] = $rec->sumgrade;
    }
    flusharray( $a, $lines, $maxquestion, $mpgame->grandprix->displaycount, $userid, false);

    $sql = "SELECT u.id,name,sortorder ".
    " FROM {$CFG->prefix}mpgame_grandprix_users u, {$CFG->prefix}mpgame_grandprix_rounds_users ru".
    " WHERE ru.roundid={$mpgame->grandprix->roundid} AND ru.userid=u.id ".
    " ORDER BY sortorder";
    $recs = $DB->get_records_sql( $sql);

    $line = 0;
    $sortlines = array();
    foreach ($recs as $rec) {
        $userid = $rec->id;
        $s = '<td>'.$rec->name.'</td>';

        if (array_key_exists( $userid, $grades)) {
            $sum = $grades[ $userid];
        } else {
            $sum = 0;
        }
        if (array_key_exists( $userid, $extra)) {
            $sum .= '/'.$extra[ $userid];
        }

        if (array_key_exists( $userid, $katataksi)) {
            $seira = $katataksi[ $userid];
        } else {
            $seira = '&nbsp;';
        }
        if ($seira == 1) {
            $seira = '<font color="red"><b>'.$seira.'</b></font>';
        } else if ($seira == 2) {
            $seira = '<font color="green"><b>'.$seira.'</b></font>';
        } else if ($seira == 3) {
            $seira = '<font color="blue"><b>'.$seira.'</b></font>';
        }
        $s .= "<td><center>$seira</td><td><center>$sum</td>";

        $userid = $rec->id;
        if (array_key_exists( $userid, $lines)) {
            $s .= $lines[ $userid];
        } else {
            $j = 0;
            for ($i = $maxquestion; $i > 0; $i--) {
                $s .= '<td><center>0</td>';
                if (++$j >= $mpgame->grandprix->displaycount) {
                    break;
                }
            }
        }

        if ($mpgame->grandprix->displaysort == 'grade') {
            $key = sprintf( '%10d-%10d', $katataksi[ $userid], $rec->sortorder).$rec->name;
        } else {
            $key = sprintf( '%10d', $rec->sortorder).$rec->name;
        }
        $sortlines[ $key] = $s;
    }
    ksort( $sortlines);

    mpgame_grandprix_results_showtable( $count, $sortlines, $maxquestion);
}

function mpgame_grandprix_results_showtable( $count, $sortlines, $maxquestion) {
    global $mpgame;

    $cols = $mpgame->grandprix->displaycols;
    if ($cols == 0) {
        $cols = 1;
    }

    $lines = array();
    for ($i = 1; $i < $cols; $i++) {
        $lines[ $i] = array();
    }
    $line = 0;
    $rows = ceil( $count / $cols);

    foreach ($sortlines as $s) {
        if ($line >= $count) {
            break;
        }

        $col = 1 + floor( $line / $rows);
        $lines[ $col][] = $s;

        $line++;
    }

    $colsgrade = 0;
    for ($k = $maxquestion; $k > 0; $k--) {
        if (++$colsgrade >= $mpgame->grandprix->displaycount) {
            break;
        }
    }

    echo '<table border=1>';
    echo '<tr>';
    for ($i = 1; $i <= $cols; $i++) {
        echo '<td colspan=2>&nbsp;</td>';
        echo "<td colspan=".($colsgrade + 1)."><b><center>".get_string( 'results_grades', 'mpgame').'</center></b></td>';
        if ($i != $cols) {
            echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
        }
    }
    echo '</tr><tr>';

    for ($i = 1; $i <= $cols; $i++) {
        echo '<td><b>'.get_string( 'results_name', 'mpgame').'</td>';
        echo '<td><b>'.get_string( 'position', 'mpgame').'</td>';
        echo '<td><b>'.get_string( 'results_sum', 'mpgame').'</td>';
        $j = 0;
        for ($k = $maxquestion; $k > 0; $k--) {
            echo '<td><b><center>'.$k.'</center></b></td>';
            if (++$j >= $mpgame->grandprix->displaycount) {
                break;
            }
        }
        if ($i != $cols) {
            echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
        }
    }
    echo '</tr>';

    for ($i = 0; $i < $rows; $i++) {
        echo '<tr>';

        for ($col = 1; $col <= $cols; $col++) {
            if (array_key_exists( $i, $lines[ $col])) {
                echo $lines[ $col][ $i];
                if ($col != $cols) {
                    echo '<td>&nbsp;</td>';
                }
            }
        }
        echo'</tr>';
    }

    echo '</table>';
}
