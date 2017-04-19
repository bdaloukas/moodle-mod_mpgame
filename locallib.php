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
 * Capability definitions for the mpgame module.
 *
 * For naming conventions, see lib/db/access.php.
 */

defined('MOODLE_INTERNAL') || die();

function mpgame_getheader( $title) {
    $s = "";
    $s .= "<HTML> <head>";
    $s .= "<title>$title</title>";
    $s .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    $s .= "</head>\n";

    return $s;
}

function mpgame_onlogin2( $filephp, $username, $password) {
    global $CFG;

    echo mpgame_getheader( '');

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_users WHERE username='$username'";
    $user = $DB->get_record_sql( $sql);

    if ($user == false) {
        mpgame_showformlogin( $filephp, '<b>'.get_string( 'wrong_username_password', 'mpgame').'</b>');
        die;
    }

    if ($user->password != '') {
        if (md5( $password) != $user->password) {
            ShowFormLogin( $filephp, '<b>'.get_string( 'wrong_username_password', 'mpgame').'</b>');
            die;
        }
    }

    $newrec = new stdClass();
    $newrec->userid = $user->id;
    $newrec->hostname = gethostname();
    $newrec->ip = mpgame_GetMyIP();
    $DB->insert_record( $newrec, 'logins');

    $sql = "UPDATE {$CFG->prefix}users SET lastip='$ip' WHERE id=$userid";
    $DB->execute( $sql);

    $_SESSION[ 'mpgame_userid'] = $user->id;
    $_SESSION[ 'mpgame_id'] = $user->mpgameid;
}

function mpgame_getmyip() {
    if (array_key_exists( "HTTP_X_FORWARDED_FOR", $_SERVER)) {
        return $_SERVER[ "HTTP_X_FORWARDED_FOR"];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function mpgame_redirect( $url) {
    ?>
        <script type="text/JavaScript">
            location.href = '<?php echo $url;?>';
        </script>
    <?php
}

function mpgame_loadgameinfo() {
    global $CFG, $DB, $mpgame;

    if (!array_key_exists( 'mpgame_id', $_SESSION)) {
        $id = required_param('id', PARAM_INT);   // It stores the mpgameid.
        if (! $cm = get_coursemodule_from_id('mpgame', $id)) {
            print_error('invalidcoursemodule');
        }
        $mpgameid = $cm->instance;

        $_SESSION[ 'mpgame_id'] = $mpgameid;
    } else {
        $mpgameid = $_SESSION[ 'mpgame_id'];
    }

    $sql = "SELECT * FROM {$CFG->prefix}mpgame WHERE id=$mpgameid";
    $mpgame = $DB->get_record_sql( $sql);
    if ( $mpgame === false) {
        die( "Can't find $mpgameid");
        return false;
    }
}

function mpgame_delete_session() {
    foreach ($_SESSION as $key => $value) {
        if ($key == 'mpgame_computercode') {
            continue;
        }
        if (substr( $key, 0, 7) == 'mpgame_') {
            unset( $_SESSION[ $key]);
        }
    }
}

function mpgame_parsequestons_html_head( $s) {
    $map = array();

    $pos = strpos( $s, '<style ');
    if ($pos === false) {
        return $map;
    }
    $s = substr( $s, $pos);
    for (;;) {
        $pos1 = strpos( $s, '{');
        if ($pos1 === false) {
            break;
        }
        $pos2 = strpos( $s, '}', $pos1 + 1);
        if ($pos2 === false) {
            break;
        }

        $name = strip_tags( trim( substr( $s, 0, $pos1)));
        $style = trim( substr( $s, $pos1 + 1, $pos2 - $pos1 - 1));

        $s = substr( $s, $pos2 + 1);

        $map[ $name] = $style;
    }

    return $map;
}

function mpgame_parsequestion_repaircss( $s, $mapcss) {
    $s = trim( $s);
    if (substr( $s, 0, 3) == '<p>') {
        $s = substr( $s, 3);
    }
    if (substr( $s, -4) == '</p>') {
        $s = substr( $s, 0, strlen( $s) - 4);
    }
    $search = '<span class="';
    for (;;) {
        $pos = strpos( $s, $search);
        if ($pos === false) {
            break;
        }
        $pos2 = strpos( $s, '"', $pos + strlen($search));
        if ($pos2 === false) {
            break;
        }
        $name = '.'.substr( $s, $pos + strlen($search), $pos2 - $pos - strlen($search));

        if (array_key_exists( $name, $mapcss)) {
            $style = '<span style="'.$mapcss[ $name].'"';
        } else {
            $style = '<span';
        }
        $s = substr( $s, 0, $pos).$style.substr( $s, $pos2 + 1);
    }

    return $s;
}

function mpgame_shuffle_assoc( &$array) {
    $keys = array_keys($array);

    shuffle( $keys);

    $new = array();

    foreach ($keys as $key) {
        $new[$key] = $array[$key];
    }
    $array = $new;
}
