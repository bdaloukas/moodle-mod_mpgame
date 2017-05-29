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
 *  Imports students
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( 'lib.php');
require( '../locallib.php');

echo mpgame_getheader( get_string( 'import_students_grandprix', 'mpgame'));

mpgame_grandprix_require_login();

if (array_key_exists( 'importstudents', $_POST)) {
    mpgame_grandprix_ShowForm_ImportStudents_Screen();
} else if ( array_key_exists( 'do', $_GET)) {
    mpgame_grandprix_ShowForm_ImportStudents_Do();
} else {
    mpgame_grandprix_ShowForm_ImportStudents();
}

function mpgame_grandprix_showform_importstudents() {
    echo '<form name="formimportstudents" id="formimportstudents" method="post" action="import.php">';
    echo get_string( 'import_students_grandprix', 'mpgame').': <textarea id="students" name="students" rows="20" cols="100"></textarea>';
    echo '<input type="checkbox" name="do" value="do">Εισαγωγή στη βάση<br>';
    echo '<input type="submit" name = "importstudents" value="'.get_string( 'import_students_grandprix', 'mpgame').'">';
    echo '</form>';
}

function mpgame_grandprix_showform_importstudents_do_compute( &$ret) {
    $s = $_POST[ 'students'] ."\n";

    $s = str_replace( "\r", "\n", $s);
    $a = explode( "\n", $s);

    $ret = array();

    foreach ($a as $s) {
        if ($s == '') {
            continue;
        }
        $s = trim( str_replace( "  ", " ", $s));

        $ret[] = $s;
    }
}

function mpgame_grandprix_showform_importstudents_screen() {
    global $CFG, $DB, $mpgame;

    mpgame_grandprix_ShowForm_ImportStudents_Do_Compute( $data);
    echo "<table border=1>";
    $count = 1;
    foreach ($data as $line) {
        echo '<tr>';
        echo '<td>'.($count++).'</td>';
        echo '<td>'.$line.'</td>';
        echo '</tr>';
    }
    echo '</table>';

    if (!array_key_exists( 'do', $_POST)) {
        return;
    }
    $sql = "SELECT MAX(username) as iusername FROM {$CFG->prefix}mpgame_grandprix_users ".
    " WHERE mpgameid={$mpgame->id}";
    $rec = $DB->get_record_sql( $sql);
    $iusername = $rec->iusername;

    $sql = "SELECT MAX(sortorder) as isortorder FROM {$CFG->prefix}mpgame_grandprix_users ".
    " WHERE mpgameid={$mpgame->id}";
    $rec = $DB->get_record_sql( $sql);
    $isortorder = $rec->isortorder;

    foreach ($data as $line) {
        $newrec = new StdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->grandprixid = $mpgame->grandprixid;
        $newrec->name = $line;
        $newrec->sortorder = ++$isortorder;
        $newrec->username = ++$iusername;
        $DB->insert_record( 'mpgame_grandprix_users', $newrec);
    }

    $cmg = get_coursemodule_from_instance('mpgame', $mpgame->id, $mpgame->course);
    $url = "{$CFG->wwwroot}/mod/mpgame/grandprix/admin.php?id={$cmg->id}&mpgameid={$mpgame->id}&grandprixid={$mpgame->grandprix->id}";
    echo "<a href=\"$url\">".get_string( 'continue', 'mpgame').'</a>';
}
