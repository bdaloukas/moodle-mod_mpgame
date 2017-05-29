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
 * Checks every half a second.
 *
 */

require( '../../../config.php');
require( 'lib.php');
require( 'libmd5.php');
require( '../locallib.php');

mpgame_grandprix_require_login();

if ($mpgame->grandprix->questionid != 0) {
    $sql = "SELECT * FROM {$CFG->prefix}mpgame_grandprix_questions WHERE id={$mpgame->grandprix->questionid}";
    $mpgame->question = $DB->get_record_sql( $sql);
}

mpgame_grandprix_ComputeTimerStudent( $resttime, $question, $questiontext, $md5questiontext, $infoanswer);

echo $md5questiontext;
echo '#'.$questiontext;
