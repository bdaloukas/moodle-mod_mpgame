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
 * The client for mpgame quiz
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( 'lib.php');
require( '../locallib.php');

mpgame_quiz_require_login();
if ($mpgame->userid == -2) {
    mpgame_redirect( 'results.php');
    die;
}

if ($mpgame->userid < 0) {
    die( get_string( 'only_students', 'mpgame')." code={$mpgame->computercode} id={$mpgame->computerid} ");
}

if (array_key_exists( 'answer', $_GET)) {
    mpgame_quiz_onanswer( $_GET[ 'answer']);
    die;
}

echo mpgame_getheader( get_string( 'show_questions', 'mpgame')., 'questions.css').'<body>';

mpgame_quiz_showform();

function mpgame_quiz_showform() {
    global $CFG, $DB, $mpgame;

    mpgame_quiz_computetimeruser( 0, '', $mpgame->quiz->roundid, $mpgame->quiz->rquestionid,
    $resttime, $questiontext, $md5, $kindquestion, $infoanswer, $valueanswer, $needrefresh);

    if ($resttime <= 0) {
        $divanswervisibility = ' style="visibility: hidden"';
    } else {
        $divanswervisibility = '';
    }

    $rec = false;
    for ($step = 1; $step <= 2; $step++) {
        $sql = "SELECT id FROM {$CFG->prefix}mpgame_quiz_rounds_users ru ".
        "WHERE ru.roundid={$mpgame->quiz->roundid} AND userid={$mpgame->userid}";
        $rec = $DB->get_record_sql( $sql);
        if ($rec != false) {
            break;
        }

        echo '<br><b>'.get_string('you_are_not_in_this_round', 'mpgame').'</b><br><br>';
        mpgame_delete_session();
        mpgame_quiz_require_login( 'client.php');
    }
    if ($rec === false) {
        die( "Can't auto login");
    }

    echo '<b>'.$_SESSION[ 'mpgame_name'].'</b><br><br>';

    $divtimerhtml = "<h1><font color=\"red\">".get_string( 'rest_time', 'mpgame').": $resttime ".
    get_string( 'seconds', 'mpgame')."</font></h1>";
    $divquestionhtml = $questiontext;
?>
    <script type="text/JavaScript">
        var kind="<?php echo $kindquestion;?>";
        var md5="<?php echo $md5;?>";
    </script>
    
    <div id="divtimer"> 
        <?php echo $divtimerhtml; ?>
    </div>

<div width="100%" id="divinfoanswer">
    <?php echo $infoanswer; ?>
</div>
<br>
    <div width="100%" height="100%" id="divquestiontext"> 
        <?php echo $divquestionhtml; ?>
    </div>
<br>

<div id="divanswer" <?php echo $divanswervisibility;?> align="center" >
        <input type="text" id="answer" name="answer" value="<?php echo $valueanswer;?>">
</div>

    <script type="text/JavaScript">

        var txt=document.getElementById( "answer");
        txt.focus();
        var length = txt.value.length;
        txt.setSelectionRange(length, length);

        timedRefresh();
        var counter=1;

        function timedRefresh() 
        {
            timervar = setInterval(function () {OnTimer();}, 500);
        }

        function OnTimer()
        {
            clearInterval( timervar);

            var elemAnswer = document.getElementById( "answer");

            if( kind == 'M')
            {
                var s = elemAnswer.value;
                elemAnswer.value = s.substr(s.length - 1);
            }

            var oReq = new XMLHttpRequest();
            oReq.onload = reqListenerMD5;
            counter = counter + 1;

            var url = "timeruser.php?counter=" + counter + "&answer="+elemAnswer.value;
            url += "&rquestionid=<?php echo $mpgame->quiz->rquestionid?>";
            url += "&roundid=<?php echo $mpgame->quiz->roundid?>";

            oReq.open("get", url, true);
            oReq.send();

            timedRefresh();
        }

    function reqListenerMD5() 
    {
        var ret = this.responseText;

        var pos=ret.indexOf( "#");
        if (pos >= 0) {
<?php
    // Format: time#answer#md5#need_refresh.
?>
            var timerest = ret.substr( 0, pos);
            ret = ret.substr( pos+1);

            pos = ret.indexOf( "#");

            var question = ret.substr( 0, pos);
            ret = ret.substr( pos+1);

            pos = ret.indexOf( "#");

            var readmd5 = ret.substr( 0, pos);
            var needrefresh = ret.substr( pos+1);

            if( needrefresh > 0)
            {
                location.href = 'client.php';
            }

            var f = document.getElementById( "divtimer");
            var s = "<h1><font color=\"red\"><?php echo get_string( 'rest_time', 'mpgame'); ?>': ' + timerest;
            f.innerHTML = s + ' <?php echo get_string( 'seconds', 'mpgame');?></font></h1>";

            var f2 = document.getElementById( "divinfoanswer");
            if( f2.innerHTML != question)
                f2.innerHTML = question;

            f = document.getElementById( "divanswer");

            if (timerest == 0)
                f.style.visibility = 'hidden';
            else
            {
                f.style.visibility = 'visible';
                f = document.getElementById( "answer");
                f.focus();
            }

            if (md5 != readmd5)
            {
                location.href = 'client.php';
            }
        }
    }

    </script>
<?php
}

function mpgame_quiz_onanswer( $answer) {
    global $CFG, $mpgame;

    $rquestionid = $_GET[ 'rquestionid'];
    if ($questiondid != $mpgame->quiz->rquestionid) {
        return;     // Is from previous question?
    }

    $sql = "SELECT * FROM {$CFG->prefix}mpgame_quiz_rounds_questions WHERE id={$mpgame->rquestionid}";
    $mpgame->question = $DB->get_record_sql( $sql);

    if (time() < strtotime( $question[ 'timefinish']) + 2) {
        $timeout = 0;
    } else {
        $timeout = 1;
    }
    $todelete = $timeout;

    $newrec = new StdClass;
    $newrec->mpgameid = $mpgame->id;
    $newrec->userid = $mpgame->userid;
    $newrec->ip = mpgame_GetMyIP();
    $newrec->rquestionid = $mpgame->rquestionid;
    $newrec->answer = $mpgame->answer;
    $newrec->timeout = $timeout;
    $newrec->todelete = $todelete;
    $newrec->timehit = date('Y-m-d H:i:s');
    $DB->insert_record( 'mpgame_quiz_hits', $newrec);

    if ($timeout) {
        echo get_string( 'answer_out_of_time', 'mpgame');
    }
    for (;;) {
        $sql = "SELECT min(id) as minid,max(id) as maxid ".
        " FROM {$CFG->prefix}mpgame_quiz_hits ".
        " WHERE rquestionid=$mpgame->rquestionid AND userid=$mpgame->userid AND todelete=0";
        $recs = $DB->get_record_sql( $sql);
        if (($rec != false) and ($rec->minid != $rec->maxid)) {
            $sql = "UDPATE {$CFG->prefix}mpgame_quiz_hits ".
            " SET todelete=1 ".
            " WHERE rquestionid=$mpgame->rquestionid AND userid=$mpgame->userid AND id < $rec->maxid";
            $DB->execute( $sql);
        }
    }
    echo get_string( 'you_type', 'mpgame').': <b>'.$answer.'</b>';
}
