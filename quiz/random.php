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
 * Randomizes students
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( 'lib.php');
require( '../locallib.php');

mpgame_quiz_require_login();

if (array_key_exists( 'count', $_POST)) {
    mpgame_quiz_random_OnSubmit();
} else if (array_key_exists( 'count', $_GET)) {
    mpgame_quiz_OnConfirm();
} else if (array_key_exists( 'seed', $_GET)) {
    mpgame_quiz_ShowForm_SetSeed();
} else if (array_key_exists( 'seed', $_POST)) {
    mpgame_quiz_OnSubmit_SetSeed();
} else {
    if ($mpgame->quiz->randomseed == 0) {
        echo "<a href=\"{$CFG->wwwroot}/mod/mpgame/quiz/random.php?seed=1\">".get_string( 'set_randomseed', 'mpgame').'</a>';
    } else {
        mpgame_quiz_random_ShowForm();
    }
}

function mpgame_quiz_random_showform() {
    global $mpgame, $CFG;

    echo mpgame_getheader( get_string( 'random_title', 'mpgame'));

    $level = $mpgame->quiz->level;

    $students = mpgame_quiz_random_computeusers( $level, true);
    $count = count($students);
    echo get_string( 'students', 'mpgame').": $count<br>";
    if ($count == 0) {
        die( get_string( 'no_students', 'mpgame'));
    }
    echo '<table cellpadding=0 border=0>';
    echo '<form name="MainForm" method="post" action="random.php">';

    echo '<tr><td>'.get_string( 'count_computers', 'mpgame').': </td>';
    echo '<td><input name="count" type="text" id="count"></td></tr>';

    echo '<tr><td></td><td><center><br>';
    echo '<input type="submit" name="submit" value="'.get_string( 'start_random', 'mpgame').'"></td>';
    echo '</table></form>';

    echo '<script type="text/JavaScript">
        document.forms[\'MainForm\'].elements[\'count\'].focus();
        </script>';
}

function mpgame_quiz_showform_setseed() {
    global $mpgame, $CFG;

    echo mpgame_GetHeader( get_string( 'set_randomseed', 'mpgame'));

    echo '<table cellpadding=0 border=0>';
    echo '<form name="MainForm" method="post" action="random.php">';

    echo '<tr><td>'.get_string( 'randomseed', 'mpgame').': </td>';
    echo '<td><input name="num" type="text" id="num"></td></tr>';

    echo '<tr><td></td><td><center><br>';
    echo '<input type="submit" name="seed" value="'.get_string( 'start_random', 'mpgame').'"></td>';
    echo '</table></form>';

    echo '<script type="text/JavaScript">
        document.forms[\'MainForm\'].elements[\'num\'].focus();
	    </script>';
}

function mpgame_quiz_random_onsubmit() {
    global $CFG, $DB, $mpgame;

    echo mpgame_getheader( get_string( 'random_title', 'mpgame'));

    $numcomputers = $_POST[ 'count'];
    $students = mpgame_quiz_random_computeusers( $mpgame->quiz->level, true);
    $numstudents = count( $students);

    $klirosi = mpgame_quiz_makeklirosi( $mpgame->quiz->randomseed, $numstudents);

    mpgame_quiz_random_computenum( $numcomputers, $numstudents, $numgroups);

    echo "<a href=\"random.php?count=$numcomputers\">".get_string( 'quiz_set', 'mpgame').'</a>';
}

function mpgame_quiz_random_computenum( $numcomputers, $numstudents, &$nugroups) {
    global $mpgame;

    $nugroups = ceil( $numstudents / $numcomputers);

    echo get_string( 'quiz_students', 'mpgame').': '.$numstudents.' ';
    echo get_string( 'quiz_computers', 'mpgame').' : '.$numcomputers.' ';
    echo get_string( 'quiz_groups', 'mpgame').': '.$nugroups.' ';
    echo get_string( 'quiz_level', 'mpgame').': '.$mpgame->quiz->level.'<br>';
}

function mpgame_quiz_onsubmit_setseed() {
    global $CFG, $DB, $mpgame;

    echo mpgame_GetHeader( get_string( 'set_randomseed', 'mpgame'));

    $randomseed = $_POST[ 'num'];

    $updrec = new StdClass;
    $updrec->id = $mpgame->quizid;
    $updrec->randomseed = $randomseed;
    $DB->update_record( 'mpgame_quiz', $updrec);

    echo "Έγινε ο ορισμός του randomseed<br>";
    echo "<a href=\"random.php\">".get_string( 'continue', 'mpgame').'</a>';
}

function mpgame_quiz_onconfirm() {
    global $CFG, $DB, $mpgame;

    echo mpgame_getheader( get_string( 'random_title', 'mpgame'));

    $level = $mpgame->quiz->level;

    $numcomputers = $_GET[ 'count'];

    $students = mpgame_quiz_random_ComputeUsers( $level, true);
    $numstudents = count( $students);

    if ($numstudents == 0) {
        die( get_string( 'no_students', 'mpgame'));
    }
    mpgame_quiz_random_computenum( $numcomputers, $numstudents, $numgroups);

    $tempklirosi = mpgame_quiz_MakeKlirosi( $mpgame->quiz->randomseed, $numstudents);
    $klirosi = array();
    foreach ($tempklirosi as $num => $pos) {
        $klirosi[ $pos] = $num;
    }
    ksort( $klirosi);

    $levelnew = $level + 1;

    if (mpgame_quiz_random_computeusers( $levelnew, false)) {
        die ("The level $levelnew has already computed");
    }
    $sql = "SELECT MAX(round) as r FROM {$CFG->prefix}mpgame_quiz_rounds ".
        " WHERE mpgameid={$mpgame->id} AND quizid={$mpgame->quizid}";
    $rec = $DB->get_record_sql( $sql);
    if ($rec == false) {
        $round = 0;
    } else {
        $round = $rec->r;
    }
    $acount = array();
    if ($numstudents <= $numcomputers) {
        $acount[] = $numstudents;
    } else {
        $cspace = $numgroups * $numcomputers - $numstudents;
        for ($i = 1; $i <= $numgroups - $cspace; $i++) {
            $acount[] = $numcomputers;
        }
        for ($i = 1; $i <= $cspace; $i++) {
            $acount[] = $numcomputers - 1;
        }
    }

    $sum = 0;
    foreach ($acount as $count) {
        $sum += $count;
    }
    if ($sum != $numstudents) {
        die( 'Problem at computing students per group');
    }

    $pos = 0;

    for ($i = 1; $i <= $numgroups; $i++) {
        $nextpos = $pos + $acount[ $i - 1];
        echo get_string( 'quiz_group', 'mpgame')." $i: ".($pos + 1)."-$nextpos ".get_string( 'count', 'mpgame');
        echo ': '.$acount[ $i - 1].'<br>';

        $newrec = new StdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->quizid = $mpgame->quizid;
        $newrec->round = ++$round;
        $newrec->level = $levelnew;
        $roundid = $DB->insert_record( 'mpgame_quiz_rounds', $newrec);

        $sql = "DELETE FROM {$CFG->prefix}mpgame_quiz_rounds_users ".
            " WHERE roundid=$roundid";
        $DB->execute( $sql);

        $computercode = 0;
        for ($j = $pos; $j < $nextpos; $j++) {
            $computercode++;
            $klirosipos = $klirosi[ $j + 1];
            $u = $students[ $klirosipos];

            $newrec = new StdClass;
            $newrec->mpgameid = $mpgame->id;
            $newrec->roundid = $roundid;
            $newrec->userid = $u;
            $newrec->computercode = $computercode;
            $DB->insert_record( 'mpgame_quiz_rounds_users', $newrec);
        }
        $pos = $nextpos;
    }

    $sql = "UPDATE {$CFG->prefix}mpgame_quiz_users SET timelogin=NULL WHERE mpgameid={$mpgame->id}";
    $DB->execute( $sql);

    $updrec = new StdClass;
        $updrec->id = $mpgame->quizid;
        $updrec->level = $level + 1;
        $updrec->questionid = 0;
        $updrec->roundid = 0;
    $DB->update_record( 'mpgame_quiz', $updrec);

    echo "<a href=\"{$CFG->wwwroot}/mod/mpgame/quiz/admin.php\">".get_string( 'continue', 'mpgame').'</a>';
}

function mpgame_quiz_random_computeusers( $level, $onlypass) {
    global $CFG, $DB, $mpgame;

    if ($level == 0) {
        $sql = "SELECT id FROM {$CFG->prefix}mpgame_quiz_users ".
            "WHERE level=0 AND mpgameid={$mpgame->id} AND quizid={$mpgame->quizid}";
    } else {
        $sql = "SELECT ru.userid as id FROM {$CFG->prefix}mpgame_quiz_rounds_users ru,{$CFG->prefix}mpgame_quiz_rounds r ".
            "WHERE r.level=$level AND ru.roundid=r.id AND r.mpgameid={$mpgame->id} AND r.quizid={$mpgame->quizid}";
        if ($onlypass) {
            $sql .= " AND ru.pass > 0";
        }
    }
    $recs = $DB->get_records_sql( $sql);
    $ret = array();
    $i = 1;
    foreach ($recs as $rec) {
        $ret[ $i++] = $rec->id;
    }

    return $ret;
}
