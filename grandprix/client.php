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
 * Client for grandprix
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( 'lib.php');
require( '../locallib.php');
require( 'libmd5.php');

echo mpgame_getHeader( get_string( 'showquestions', 'mpgame')).'<body>';

mpgame_grandprix_require_login();

if ($mpgame->grandprix->questionid != 0) {
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_questions WHERE id={$mpgame->grandprix->questionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
}

$answer = optional_param('answer', "", PARAM_INT);
if ($answer != '') {
    mpgame_grandprix_onanswer( $answer);
}
mpgame_showform();

function mpgame_showform() {
    mpgame_grandprix_computetimerstudent( $resttime, $question, $questiontext, $md5, $infoanswer);

    if ($resttime <= 0) {
        $divanswervisibility = ' style="visibility: hidden"';
    } els e{
        $divanswervisibility = '';
    }
    $divtimerhtml = get_string( 'timerest1', 'mpgame').' '.$resttime.' '.get_string( 'timerest2', 'mpgame');
    $divquestionhtml = $questiontext;
?>
    <div id="divmd5" style="visibility: hidden">
        <?php echo $md5;?>
    </div>

    <div id="divtimer"> 
        <?php echo $divtimerhtml; ?>
    </div>

    <br>
    <div width="100%" height="100%" id="divquestiontext"> 
    <?php echo $divquestionhtml; ?>
    </div>
    </b>
    <br>
    
<div width="100%" id="divquestion">
<?php echo get_string( 'question', 'mpgame').": $question &nbsp;&nbsp;$infoanswer<br>"; ?>
</div>

<div id="divanswer" <?php echo $divanswervisibility;?> >
    <form name="formanswer" id="formanswer" method="get" action="client.php">
        <?php echo get_string( 'grandprix_prompt', 'mpgame'); ?>: <input type="text" id="answer" name="answer">
    </form>
</div>

<br>
    <script type="text/JavaScript">

        document.getElementById("answer").oninput = function() {OnInputAnswer()};         
        document.forms['formanswer'].elements['answer'].focus();

        timedRefresh( 500);

        function timedRefresh(timeoutPeriod) 
        {
            setTimeout("OnTimer();",timeoutPeriod);
        }

        function OnTimer()
        {
            var oReq = new XMLHttpRequest();
            oReq.onload = reqListenerMD5;
            oReq.open("get", "timerstudentmd5.php", true);
            oReq.send();

            timedRefresh( 1000);
        }

        function OnInputAnswer()
        {
            var f = document.getElementById("answer");

            var s = f.value;
            if( s.length == 0)
                return;

            var ch = s.substr( s.length-1, 1);
            if( ch >= "1")
            {
                if( ch <= "4")
                {
                    window.location.assign( 'client.php?answer=' + ch);
                    return false;
                }
            }
            f.value = "";
        }

    function reqListenerMD5() 
    {
        var ret = this.responseText;

        var pos=ret.indexOf( "#");
        if (pos != 0)
        {
            var timerest = parseInt( ret.substr( 0, pos));
            ret = ret.substr( pos+1);
            pos = ret.indexOf( "#");

            var question = ret.substr( 0, pos);
            var md5 = ret.substr( pos+1);

            var f = document.getElementById( "divtimer");
            var url = "<?php echo get_string( 'timerest1', 'mpgame');?> " + timerest.toString();
            f.innerHTML = url + " <?php echo get_string( 'timerest2', 'mpgame');?>";

            var f2 = document.getElementById( "divquestion");
            if( f2.innerHTML != question)
                f2.innerHTML = question;

            f = document.getElementById( "divanswer");
            if( timerest == '0')
                f.style.visibility = 'hidden';
            else
            {
                f.style.visibility = 'visible';
                f = document.forms['formanswer'].elements['answer'];
                f.focus();
                f.select();
            }

            var f3 = document.getElementById( "divmd5");
            if( f3.innerHTML != md5)
            {
                //Need to read the new question
                var oReq = new XMLHttpRequest();
                oReq.onload = reqListener;
                oReq.open("get", "timerstudent.php", true);
                oReq.send();
            }
        }
    }

    function reqListener() 
    {
        var ret = this.responseText;

        var pos=ret.indexOf( "#");
        if( pos != 0)
        {
            var md5 = ret.substr( 0, pos);
            var questiontext = ret.substr( pos+1);

            var f3 = document.getElementById( "divquestiontext");
            f3.innerHTML = questiontext;

            var f4 = document.getElementById( "divmd5");
            f4.innerHTML = md5;
        }
    }

    </script>
<?php
}

function mpgame_grandprix_onanswer( $answer) {
    global $CFG, $DB, $mpgame;

    if (time() < $mpgame->question->timefinish + 2) {
        $timeout = 0;
    } else {
        $timeout = 1;
    }
    $todelete = $timeout;

    $newrec = new stdClass();
    $newrec->mpgameid = $mpgame->id;
    $newrec->grandprixid = $mpgame->grandprixid;
    $newrec->userid = $mpgame->userid;
    $newrec->ip = mpgame_GetMyIP();
    $newrec->questionid = $mpgame->grandprix->questionid;
    $newrec->answer = $answer;
    $newrec->grade = 0;
    $newrec->graded = 0;
    $newrec->timeout = $timeout;
    $newrec->todelete = $timeout;
    $newrec->timehit = date('Y-m-d H:i:s');
    $DB->insert_record( 'mpgame_grandprix_hits', $newrec);

    if ($timeout) {
        echo get_string( 'answeroutoftime', 'mpgame');
    }

    for (;;) {
        $sql = "SELECT MIN(id) as minid, MAX(id) as maxid ".
        " FROM {$CFG->prefix}mpgame_grandprix_hits ".
        " WHERE questionid={$mpgame->grandprix->questionid} AND userid={$mpgame->userid} AND todelete=0 ".
        " AND grandprixid={$mpgame->grandprixid}";
        $rec = $DB->get_record_sql( $sql);
        if (rec === false) {
            break;
        }
        if ($rec->minid == $rec->maxid) {
            break;
        }
        $sql = "UPDATE {$CFG->prefix}mpgame_grandprix_hits SET todelete=1 WHERE id={$rec->minid}";
        $DB->execute( $sql);
    }
}
