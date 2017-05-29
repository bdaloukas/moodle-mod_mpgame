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
 * Library.
 *
 */

defined('MOODLE_INTERNAL') || die();

function mpgame_grandprix_require_login() {
    global $CFG, $DB, $mpgame;

    mpgame_LoadGameInfo();

    mpgame_grandprix_LoadGameInfo();

    if (array_key_exists( 'mpgame_userid', $_SESSION)) {
        $mpgame->userid = $_SESSION[ 'mpgame_userid'];
        return;
    }

    if (array_key_exists( 'username', $_POST)) {
        mpgame_grandprix_onlogin2( $_POST[ 'username'], $_POST[ 'password']);
        return mpgame_grandprix_require_login();
    }

    mpgame_grandprix_showformlogin();
    die;
}

function mpgame_grandprix_showformlogin( $msg='') {
    echo mpgame_GetHeader( 'Είσοδος');

    if ($msg != '') {
        echo '<br><b>'.$msg.'<br><br>';
    }

    $filephp = $_SERVER[ 'SCRIPT_NAME'];
    $pos = strrpos( $filephp, '/');
    $filephp = substr( $filephp, $pos + 1);

    echo '<table cellpadding=0 border=0>';
    echo '<form name="MainForm" method="post" action="'.$filephp.'">';

    echo '<tr><td>'.get_string( 'username', 'mpgame').':</td>';
    echo '<td><input name="username" id="username" type="text" id="username"> ';
    echo '</td></tr>';

    echo '<tr><td>'.get_string( 'password', 'mpgame').':</td>';
    echo '<td><input name="password" type="password" id="password"> ';
    echo '</td></tr>';
    echo '<tr><td></td><td><center><br><input type="submit" value="'.get_string('login', 'mpgame').'"></td>';
    echo '</table>';
    echo '</form>';
?>
    <script type="text/JavaScript">
    document.forms['MainForm'].elements['username'].focus();
    </script>
<?php
}

function mpgame_getparampost( $name, $default='') {
    if (array_key_exists( $name, $_POST)) {
        return $_POST[ $name];
    } else {
        return '';
    }
}

function mpgame_grandprix_onlogin2( $username, $password) {
    global $CFG, $DB, $mpgame;

    echo mpgame_GetHeader( '');

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_users ".
        " WHERE grandprixid={$mpgame->grandprixid} AND username='$username'";
    $mpgame->user = $DB->get_record_sql( $sql);
    if ($mpgame->user == false) {
        mpgame_grandprix_ShowFormLogin( '<b>Λάθος όνομα χρήστη</b>');
        die;
    }

    if ($mpgame->user->password != '') {
        if ($mpgame->user->password != md5( $password)) {
            mpgame_grandprix_ShowFormLogin( '<b>'.get_string( 'wrong_password', 'mpgame').'</b>');
            die;
        }
    }
    $mpgame->userid = $mpgame->user->id;
    $_SESSION[ 'mpgame_userid'] = $mpgame->userid;

    mpgame_grandprix_WriteToLogin();
}

function mpgame_grandprix_writetologin() {
    global $DB, $mpgame;

    $ip = mpgame_GetMyIP();

    $newrec = new stdClass;
        $newrec->mpgameid = $mpgame->id;
        $newrec->grandprix = $mpgame->grandprix;
        $newrec->ip = $ip;
        $newrec->userid = $mpgame->userid;
        $newrec->timelogin = date('Y-m-d H:i:s', time());
    $id = $DB->insert_record( 'mpgame_grandprix_logins', $newrec);

    $updrec = new StdClass;
    $updrec->id = $mpgame->userid;
    $updrec->lastip = $ip;
    $updrec->timelogin = date('Y-m-d H:i:s', time());

    $DB->update_record( 'mpgame_grandprix_users', $updrec);
}

function mpgame_grandprix_loadgameinfo() {
    global $CFG, $DB, $mpgame;

    if (array_key_exists( 'mpgame_grandprixid', $_SESSION)) {
        $mpgame->grandprixid = $_SESSION[ 'mpgame_grandprixid'];
    } else {
        $mpgame->grandprixid = optional_param('grandprixid', 0, PARAM_INT);   // It stores the mpgameid.
    }
    if ($mpgame->grandprixid == 0) {
        die( 'There is no grandprixid');
    }
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix WHERE id = {$mpgame->grandprixid}";

    $mpgame->grandprix = $DB->get_record_sql( $sql);

    if ($mpgame->grandprix != false) {
        $_SESSION[ 'mpgame_grandprixid'] = $mpgame->grandprixid;
    } else {
        unset( $_SESSION[ 'mpgame_grandprixid']);
    }
}
