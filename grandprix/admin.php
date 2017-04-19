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
 * Form for adminstrating grandprix
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( 'header_admin.php');

if (array_key_exists( 'debugquestions', $_GET)) {
    mpgame_grandprix_Ondebugquestions();
    die;
}

$sql = "SELECT COUNT(*) as c FROM {$CFG->prefix}mpgame_grandprix_users ".
" WHERE mpgameid={$mpgame->id} AND grandprixid={$mpgame->grandprixid}";
$rec = $DB->get_record_sql( $sql);
if ($rec->c == 0) {
    echo "<a href=\"import.php\">".get_string( 'import_students', 'mpgame')."</a> &nbsp; &nbsp; &nbsp;";
    die;
}

$sql = "SELECT COUNT(*) as c FROM {$CFG->prefix}mpgame_grandprix_rounds ".
" WHERE mpgameid={$mpgame->id} AND grandprixid={$mpgame->grandprixid}";
$rec = $DB->get_record_sql( $sql);
if ($rec->c == 0) {
    echo "<a href=\"rounds.php\">".get_string( 'set_rounds', 'mpgame').'</a> &nbsp; &nbsp; &nbsp;';
    die;
}

echo get_string( 'round', 'mpgame').": {$mpgame->grandprix->roundid}<br>";

if ($mpgame->grandprix->questionid != 0) {
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_questions WHERE id={$mpgame->grandprix->questionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
}

// Do the work.
if (array_key_exists( 'question', $_POST)) {
    mpgame_grandprix_admin_setquestion();
} else if (array_key_exists('duration', $_POST)) {
    mpgame_grandprix_admin_OnStartTimer();
} else if (array_key_exists('grade', $_POST)) {
    mpgame_granprix_admin_OnGradeAnswers();
} else if (array_key_exists('displaycols', $_POST)) {
    mpgame_grandprix_admin_OnSetParams();
}
mpgame_grandprix_admin_setfocus( 'question');

echo '<table border=1>';
echo '<tr><td>';
// Check params to show the correct screen.

mpgame_grandprix_admin_showform_question();
$focus = 'question';

if (isset( $mpgame->question)) {
    mpgame_grandprix_admin_showform_duration();
    $focus = 'duration';
    if ($mpgame->question->timefinish != 0) {
        mpgame_grandprix_admin_showform_grade();
        $focus = 'grade';
    }
}
mpgame_grandprix_admin_setfocus( $focus);

echo '</td><td>';
mpgame_grandprix_admin_showform_params();
echo '</td></tr></table>';
if (isset( $mpgame->question)) {
    mpgame_grandprix_admin_showframe();
}

function mpgame_grandprix_admin_showform_params() {
    global $mpgame;

    echo '<form name="form_params" method="post" action="admin.php">';

    $a = array( 0, 1, 2, 3, 4);
    echo '<table border=0>';

    echo '<tr><td>'.get_string( 'displaycols', 'mpgame').':</td>';
    echo '<td><input name="displaycols" type="text" id="displaycols" size="2" value="'.$mpgame->grandprix->displaycols.'"></td>';
    echo '<td>&nbsp;&nbsp;</td>';
    echo '<td>'.get_string('displaycount', 'mpgame').':</td>';
    echo '<td><input name="displaycount" type="text" id="displaycount" size="2" '.
    'value="'.$mpgame->grandprix->displaycount.'"></td></tr>';

    echo '<tr><td>'.get_string( 'displaytop', 'mpgame').':</td>';
    echo '<td><input name="displaytop" type="text" id="displaytop" size="2" value="'.$mpgame->grandprix->displaytop.'"></td>';
    echo '<td>&nbsp;</td>';
    echo '<td>'.get_string( 'displaysort', 'mpgame').':</td>';
    echo '<td>';
    echo '<select name="displaysort">';
    $a = array( '' => get_string( 'sort_name', 'mpgame'), 'grade' => get_string( 'sort_grades', 'mpgame'));
    foreach ($a as $key => $value) {
        echo "<option value=\"{$key}\"";
        if ($key == $mpgame->grandprix->displaysort) {
            echo ' selected';
        }
        echo ">{$value}</option>";
    }
    echo '</select></td></tr>';

    echo '<tr><td>'.get_string( 'displaytimerefresh', 'mpgame').':</td>';
    echo '<td><input name="displaytimerefresh" type="text" id="displaytimerefresh" size="2" '.
    'value="'.$mpgame->grandprix->displaytimerefresh.'"></td>';
    echo '<td>&nbsp;</td>';
    echo '<td>'.get_string( 'countquestions', 'mpgame').':</td>';
    echo '<td><input name="countquestions" type="text" id="countquestions" size="2" '.
    'value="'.$mpgame->grandprix->countquestions.'"></td></tr>';

    echo '<tr><td>'.get_string( 'displayinfo', 'mpgame').'</td><td><select name="displayinfo">';
    $a = array( '1' => get_string( 'yes'), '0' => get_string( 'no'));
    foreach ($a as $key => $value) {
        echo "<option value=\"{$key}\"";
        if ($key == $mpgame->grandprix->displayinfo) {
            echo ' selected';
        }
        echo ">{$value}</option>";
    }
    echo '</select></td></tr>';
    echo '<tr><td colspan=5><center><input type="submit" value="'.get_string( 'change', 'mpgame').'"></td></tr>';
    echo '</table></form>';
}

function mpgame_grandprix_admin_showform_grade() {
    global $mpgame;

    echo '<form name="form_grade" method="post" action="admin.php">';

    echo get_string( 'correct_answer_is', 'mpgame').':';
    echo '<input name="countquestions" type="text" id="correct" size="2" value="'.$mpgame->question->correct.'"><br>';

    echo get_string( 'grades', 'mpgame').':<input name="grade" type="text" id="grade" size="2" '.
    'value="'.$mpgame->question->grade.'">';
    echo '<input type="submit" value="'.get_string( 'set_grade', 'mpgame').'">';
    echo '</form>';
}

function mpgame_grandprix_admin_showframe() {
    global $mpgame;

    echo "<iframe src=\"timeradmin.php\" width=\"100%\" id=\"timerframe\"></iframe>";

    echo "<div id=\"questionframe\">{$mpgame->question->questiontext}</div>";
?>               
    <script type="text/JavaScript">
    function timedRefresh(timeoutPeriod) {
        setTimeout("OnTimer();",timeoutPeriod);
    }
    
    function OnTimer()
    {
        var f = document.getElementById('timerframe');
        f.src = f.src;
    
        timedRefresh(1000);
    }
    timedRefresh(1000);
    </script>
<?php
}

function mpgame_grandprix_admin_onstarttimer() {
    global $DB, $mpgame;

    $duration = $_POST[ 'duration'];

    $updrec = new StdClass;
    $updrec->id = $mpgame->grandprix->questionid;
    $updrec->duration = $duration;
    $updrec->timefinish = time() + $duration;

    $DB->update_record( 'mpgame_grandprix_questions', $updrec);

    $mpgame->question->duration = $duration;
    $mpgame->question->timefinish = $updrec->timefinish;
}

function mpgame_grandprix_admin_onsetparams() {
    global $DB, $mpgame;

    $updrec = new StdClass;
    $updrec->id = $mpgame->grandprixid;
    $updrec->displaycols = $_POST[ 'displaycols'];
    $updrec->displaysort = $_POST[ 'displaysort'];
    $updrec->displaycount = $_POST[ 'displaycount'];
    $updrec->displaytop = $_POST[ 'displaytop'];
    $updrec->displaytimerefresh = $_POST[ 'displaytimerefresh'];
    $updrec->countquestions = $_POST[ 'countquestions'];
    $updrec->displayinfo = $_POST[ 'displayinfo'];

    $DB->update_record( 'mpgame_grandprix', $updrec);

    mpgame_grandprix_loadgameinfo();
}

function mpgame_grandprix_admin_showform_question() {
    global $mpgame;

    echo '<form name="form_question" method="post" action="admin.php">';

    if (isset( $mpgame->question)) {
        $question = $mpgame->question->numquestion;
    } else {
        $question = '';
    }
    echo get_string( 'question', 'mpgame').': ';
    echo '<input name="question" type="text" id="question" size="2" value="'.$question.'"> ';

    echo ' <input type="submit" name="show" value="'.get_string( 'show', 'mpgame').'">';
    echo '</form>';
}

function mpgame_grandprix_admin_setfocus( $name) {
    echo '<script type="text/JavaScript">';
    echo "document.forms['form_{$name}']['{$name}'].focus();\r\n";
    echo '</script>';
}

function mpgame_grandprix_admin_showform_duration() {
    global $mpgame;

    echo '<form name="form_duration" method="post" action="admin.php">';

    echo get_string( 'results_duration', 'mpgame').': ';
    echo '<input name="duration" type="text" id="duration" size="3" value="'.$mpgame->question->duration.'"> sec';

    echo '<input type="submit" name="submit" value="'.get_string( 'start', 'mpgame').'">';
    echo '</form>';
}

function mpgame_grandprix_admin_setquestion() {
    global $CFG, $DB, $mpgame;

    $numquestion = $_POST[ 'question'];

    $map = mpgame_grandprix_ParseQuestions();
    if (!array_key_exists( $numquestion, $map)) {
        die( get_string( 'question_not_found', 'mpgame').' '.$numquestion);
    }
    $entry = $map[ $numquestion];

    $sql = "SELECT id FROM {$CFG->prefix}mpgame_grandprix_questions ".
    " WHERE grandprixid={$mpgame->grandprixid} AND numquestion=$numquestion";
    $rec = $DB->get_record_sql( $sql);

    $newrec = new StdClass;

    if ($rec === false) {
        $newrec->mpgameid = $mpgame->id;
        $newrec->grandprixid = $mpgame->grandprixid;
        $newrec->numquestion = $numquestion;
        $newrec->timefinish = 0;
    } else {
        $newrec->id = $rec->id;
    }
    $newrec->duration = $entry->duration;
    $newrec->questiontext = $entry->question;
    $newrec->md5questiontext = md5( $entry->question);
    $newrec->grade = $entry->grade;
    $newrec->timechange = date('Y-m-d H:i:s');;

    if ($rec === false) {
        $newrec->id = $DB->insert_record( 'mpgame_grandprix_questions', $newrec);
    } else {
        $DB->update_record( 'mpgame_grandprix_questions', $newrec);
    }
    $mpgame->grandprix->questionid = $newrec->id;

    $updrec = new StdClass;
    $updrec->id = $mpgame->grandprixid;
    $updrec->questionid = $mpgame->grandprix->questionid;
    $DB->update_record( 'mpgame_grandprix', $updrec);

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_questions WHERE id={$mpgame->grandprix->questionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
}

function mpgame_granprix_admin_ongradeanswers() {
    global $CFG, $DB, $mpgame;

    $resttime = $mpgame->question->timefinish - time();

    if ($resttime >= 0) {
        echo '<font color="red">'.get_string( 'cant_grade_time', 'mpgame').'</font>';
        return;
    }

    $correct = $_POST[ 'correct'];
    $grade = $_POST[ 'grade'];

    if ($correct == 0) {
        echo '<font color="red">'.get_string( 'no_give_correct', 'mpgame').'</font>';
        return;
    }

    if ($grade == 0) {
        echo '<font color="red">'.get_string( 'no_give_grade', 'mpgame').'</font>';
        return;
    }

    $updrec = new StdClass;
    $updrec->id = $mpgame->grandprix->questionid;
    $updrec->correct = $correct;
    $updrec->grade = $grade;
    $DB->update_record( 'mpgame_grandprix_questions', $updrec);

    $sql = "UPDATE {$CFG->prefix}mpgame_grandprix_hits ".
    " SET grade=0, graded=1 ".
    " WHERE grandprixid={$mpgame->grandprixid} AND questionid={$mpgame->grandprix->questionid} AND answer<>$correct";
    $DB->execute( $sql);

    $sql = "UPDATE {$CFG->prefix}mpgame_grandprix_hits ".
    " SET grade=$grade, graded=1 ".
    " WHERE grandprixid={$mpgame->grandprixid} AND questionid={$mpgame->grandprix->questionid} AND answer=$correct";
    $DB->execute( $sql);

    $mpgame->question->correct = $correct;
    $mpgame->question->grade = $grade;
}

function mpgame_grandprix_parsequestions_htm( $s) {
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
        die( 'There is no TABLE tag');
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

        // Have to read columns.
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

        if (count($a) < 4) {
            continue;
        }
        if (strlen( trim( strip_tags($a[ 3]))) < 3) {
            continue;
        }

        if ($first) {
            $first = false;
        } else {
            $entry = new StdClass;
            $entry->line = trim( strip_tags($a[ 0]));
            $entry->duration = $a[ 1];
            $entry->grade = $a[ 2];
            $entry->question = $a[ 3];
            $map[ $entry->line] = $entry;
        }
    }

    return $map;
}

function mpgame_grandprix_ondebugquestions() {
    $map = mpgame_grandprix_parsequestions();
    echo "<table border=1>\r\n";
    echo '<tr><td><b>'.get_string( 'number', 'mpgame').'</td><td><b>'.get_string( 'results_duration', 'mpgame').'</td>';
    echo '<td><b>'.get_string( 'results_grades', 'mpgame').'</b><td><b>'.get_string( 'question', 'mpgame').'</td></tr>\r\n';
    foreach ($map as $line => $entry) {
        $s = '<tr>';
        $s .= '<td><center>'.$entry->line.'</td>';
        $s .= '<td><center>'.$entry->duration.'</td>';
        $s .= '<td><center>'.$entry->grade.'</td>';
        $s .= '<td>'.$entry->question.'</td>';

        echo $s.'</tr>';
    }
    echo '</table>';
}

// Read question from html.
function mpgame_grandprix_parsequestions() {
    global $mpgame;

    $file = $mpgame->questionfile;

    $pos = strrpos( $file, '.');
    $ext = strtolower( substr( $file, $pos + 1));

    if (($ext == 'htm') or ($ext == 'html')) {
        return mpgame_grandprix_ParseQuestions_htm( file_get_contents( $file));
    }
    if ($mpgame->questionfileid != 0) {
        $f = mpgame_get_question_file( $mpgame);
    } else {
        $f = false;
    }
    if ($f === false) {
        die( "Not set questionfileid");
    }

    return mpgame_grandprix_ParseQuestions_htm( $f->get_content());
}
