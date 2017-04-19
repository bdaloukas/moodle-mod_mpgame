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
 *  Randomizes users
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');

require( 'header_admin.php');

if (array_key_exists( 'randomseed', $_POST)) {
    mpgame_grandprix_random_OnSubmit();
} else if (array_key_exists( 'print', $_GET)) {
    mpgame_grandprix_random_print();
} else {
    mpgame_grandprix_random_ShowForm();
}

function mpgame_grandprix_random_showform() {
    global $mpgame;

    echo mpgame_getheader( get_string( 'start_random', 'mpgame'));

    echo '<table cellpadding=0 border=0>';
    echo '<form name="MainForm" method="post" action="random.php">';

    echo '<tr><td>'.get_string( 'randomseed', 'mpgame').': </td>';
    echo '<td><input name="randomseed" type="text" id="randomseed"></td></tr>';

    echo '<tr><td>'.get_string( 'count_digits', 'mpgame').': </td>';
    echo '<td><input name="count" type="text" id="count"></td></tr>';

    echo '<tr><td></td><td><center><br>';
    echo '<input type="submit" name="submit" value="'.get_string( 'start_random', 'mpgame').'"></td>';
    echo '</table></form>';

    echo '<script type="text/JavaScript">
		document.forms[\'MainForm\'].elements[\'randomseed\'].focus();
    </script>';
}

function mpgame_grandprix_random_onsubmit() {
    global $CFG, $DB, $mpgame;

    echo mpgame_getheader( get_string( 'start_random', 'mpgame'));

    $countdigits = $_POST[ 'count'];
    $randomseed = $_POST[ 'randomseed'];

    $sql = "SELECT u.id FROM {$CFG->prefix}mpgame_grandprix_users u, {$CFG->prefix}mpgame_grandprix_rounds_users ru ".
    " WHERE ru.userid=u.id AND ru.roundid={$mpgame->grandprix->roundid}";
    $recs = $DB->get_records_sql( $sql);
    foreach ($recs as $rec) {
        $updrec = new StdClass;
        $updrec->id = $rec->id;
        $s = substr( md5( $randomseed.'-'.$rec->id), - $countdigits);
        $a = array('a', 'b', 'c', 'd', 'e', 'f');
        foreach ($a as $c) {
            $nums = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
            $num = array_rand( $nums);
            $s = str_replace( $c, $num, $s);
        }
        $updrec->pw = $s;
        $updrec->password = md5( $updrec->pw);
        $DB->update_record( 'mpgame_grandprix_users', $updrec);
    }
}

function mpgame_grandprix_random_print() {
    global $CFG, $DB, $mpgame;

    $sql = "SELECT u.id,u.username,u.pw,u.name ".
    " FROM {$CFG->prefix}mpgame_grandprix_users u, {$CFG->prefix}mpgame_grandprix_rounds_users ru ".
    " WHERE ru.userid=u.id AND ru.roundid={$mpgame->grandprix->roundid}";
    $recs = $DB->get_records_sql( $sql);
    echo '<table cellpadding=10>';
    $line = 0;
    foreach ($recs as $rec) {
        $line++;
        if ($line % 3 == 1) {
            echo '<tr width=100%><td width=33%>';
        } else if ($line % 3 == 2) {
            echo '</td><td width=33%>';
        } else {
            echo '</td><td width=33>';
        }

        echo 'Θα μπείτε στη διεύθυνση http://192.168.1.10<br>';
        echo get_string( 'username', 'mpgame').': <b>'.$rec->username.'</b><br>';
        echo get_string( 'password', 'mpgame').': <b>'.$rec->pw.'</b><br>';
        echo '<b>'.$rec->name.'</b><br><br>';

        if ($line % 3 == 0) {
            echo '</tr>';
        }
    }
    echo '</tr></table>';
}
