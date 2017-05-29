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
 * Run every one seconds on clients
 *
 * @package   mpgame
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require( '../../../config.php');
require( '../locallib.php');
require( '../quiz/lib.php');

mpgame_quiz_require_login( 'admin.php');

$counter = $_GET[ 'counter'];
$answer = $_GET[ 'answer'];
$roundid = $_GET[ 'roundid'];
$rquestionid = $_GET[ 'rquestionid'];

mpgame_quiz_ComputeTimerUser( $counter, $answer, $roundid, $rquestionid,
    $resttime, $questiontext, $md5, $kind, $infoanswer, $valueanswer, $needrefresh);
$ret = $resttime.'#'.$infoanswer.'#'.$md5.'#'.$needrefresh;
echo $ret;
