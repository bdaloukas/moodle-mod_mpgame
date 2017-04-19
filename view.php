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

// This page prints a particular instance of MultiGame.

require_once(dirname(__FILE__) . '/../../config.php');
require( 'locallib.php');


$id = optional_param('id', 0, PARAM_INT); // Course Module ID.

if (! $cm = get_coursemodule_from_id('mpgame', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

// Check login and get context.
require_login($course->id, false, $cm);

$sql = "SELECT * FROM {$CFG->prefix}mpgame WHERE id=$cm->instance";
$mpgame = $DB->get_record_sql( $sql);

$context = mpgame_get_context_module_instance( $cm->id);


// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/mpgame/view.php', array('id' => $cm->id));

$edit = optional_param('edit', -1, PARAM_BOOL);
if ($edit != -1 && $PAGE->user_allowed_editing()) {
    $USER->editing = $edit;
}

$title = $course->shortname . ': ' . format_string($mpgame->name);

if ($PAGE->user_allowed_editing() && !empty($CFG->showblocksonmodpages)) {
    $buttons = '<table><tr><td><form method="get" action="view.php"><div>'.
        '<input type="hidden" name="id" value="'.$cm->id.'" />'.
        '<input type="hidden" name="edit" value="'.($PAGE->user_is_editing() ? 'off' : 'on').'" />'.
        '<input type="submit" value="'.
        get_string($PAGE->user_is_editing() ? 'blockseditoff' : 'blocksediton').
        '" /></div></form></td></tr></table>';
    $PAGE->set_button($buttons);
}

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Print game name and description.
echo $OUTPUT->heading(format_string($mpgame->name));

// Display information about this game.
echo $OUTPUT->box_start('quizinfo');
    mpgame_show( $id);
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

function mpgame_show( $id) {
    global $mpgame;

    if ($mpgame->gamekind == 'quiz') {
        return mpgame_quiz_show( $id);
    } else if ($mpgame->gamekind == 'grandprix') {
        return mpgame_grandprix_show( $id);
    }
}

function mpgame_quiz_show( $id) {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz WHERE mpgameid={$mpgame->id} ORDER BY id";
    $recs = $DB->get_records_sql( $sql);
    if (count( $recs) == 0) {
        $newrec = new StdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->name = $mpgame->name;

        $DB->insert_record( 'mpgame_quiz', $newrec);

        $recs = $DB->get_records_sql( $sql);
    }

    $sql = "SELECT id,name,gamekind FROM {$CFG->prefix}mpgame WHERE id={$mpgame->id} ORDER BY id";
    $recs = $DB->get_records_sql( $sql);
    foreach ($recs as $rec) {
        if ($rec->name == '') {
            $rec->name = $rec->id;
        }
        echo "<a href=\"{$CFG->wwwroot}/mod/mpgame/quiz/admin.php?id=$id&mpgameid={$mpgame->id}&quizid={$rec->id}\">admin</a> ";
        echo " <a href=\"{$CFG->wwwroot}/mod/mpgame/quiz/client.php?id=$id&mpgameid={$mpgame->id}&quizid={$rec->id}\">play</a> ";
        echo " <a href=\"{$CFG->wwwroot}/mod/mpgame/quiz/results.php?id=$id&quizid={$rec->id}\">results</a>";
    }
}

function mpgame_grandprix_show( $id) {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix WHERE mpgameid={$mpgame->id} ORDER BY id";
    $recs = $DB->get_records_sql( $sql);
    if (count( $recs) == 0) {
        $newrec = new StdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->name = $mpgame->name;

        $DB->insert_record( 'mpgame_grandprix', $newrec);

        $recs = $DB->get_records_sql( $sql);
    }

    $sql = "SELECT id,name FROM {$CFG->prefix}mpgame_grandprix WHERE mpgameid={$mpgame->id} ORDER BY id";
    $recs = $DB->get_records_sql( $sql);
    foreach ($recs as $rec) {
        if ($rec->name == '') {
            $rec->name = $rec->id;
        }
        $url = $CFG->wwwroot."/mod/mpgame/grandprix/admin.php?id=$id&mpgameid={$mpgame->id}&grandprixid={$rec->id}";
        echo "<a href=\"$url\">admin</a> ";

        $url = "{$CFG->wwwroot}/mod/mpgame/grandprix/client.php?id=$id&mpgameid={$mpgame->id}&grandprixid={$rec->id}"
        echo "<a href=\"$url\">play</a> ";

        echo "<a href=\"{$CFG->wwwroot}/mod/mpgame/grandprix/results.php?id=$id&grandprixid={$rec->id}\">results</a>";
    }
}
