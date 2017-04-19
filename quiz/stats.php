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
 * Form for statistics on quiz
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function mpgame_quiz_onstatquestions() {
    mpgame_quiz_onstatquestions_easydifficult();
}

function mpgame_quiz_onstatquestions_easydifficult() {
    global $CFG, $DB, $mpgame;

    $sqlcountcorrect = "SELECT COUNT(*) ".
        " FROM {$CFG->prefix}mpgame_quiz_hits h ".
        " WHERE h.rquestionid=q.id AND todelete=0 AND graded=1 AND iscorrect=1";
    $sqlcounthit = "SELECT COUNT(*) ".
        " FROM {$CFG->prefix}mpgame_quiz_hits h ".
        " WHERE h.rquestionid=q.id AND todelete=0 AND graded=1";
    $sql = "SELECT q.id,r.round,q.questiontext,q.questioninfo,q.book,q.category,numquestion,correctanswer ".
        ",($sqlcountcorrect) as corrects".
        ",($sqlcounthit) as hits".
        " FROM {$CFG->prefix}mpgame_quiz_rounds_questions q,{$CFG->prefix}mpgame_quiz_rounds r ".
        " WHERE q.roundid=r.id AND r.quizid={$mpgame->quizid}".
        " ORDER BY q.roundid,q.id";
    $recs = $DB->get_records_sql( $sql);
    $mapall = $mapnobody = array();
    $line = 0;
    foreach ($recs as $rec) {
        $line = "<td>{$rec->round}</td><td>".($rec->numquestion - 1)."</td><td>{$rec->book}</td><td>{$rec->category}</td>";
        $line .= "<td>$rec->questiontext</td><td>$rec->correctanswer</td>";
        if ($rec->corrects == $rec->hits) {
            $mapall[] = $line;
        }

        if ($rec->corrects == 0) {
            $mapnobody[] = $line;
        }
    }
    if (count( $mapall)) {
        echo '<table border=1><tr><td><td colspan=10><center><b>'.get_string('quiz_answered_all', 'mpgame').'</td></tr>';
        $i = 1;
        foreach ($mapall as $line) {
            echo "<tr><td>$i</td>$line</tr>";
            $i++;
        }
        echo '</table>';
    }
    if (count( $mapnobody)) {
        echo "<table border=1><tr><td><td colspan=10><center><b>'.get_string('quiz_answered_none', 'mpgame').'</td></tr>";
        $i = 1;
        foreach ($mapnobody as $line) {
            echo "<tr><td>$i</td>$line</tr>";
            $i++;
        }
        echo '</table>';
    }
}
