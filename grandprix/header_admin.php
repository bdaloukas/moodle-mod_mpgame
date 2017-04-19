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
 * Library for administator.
 *
 */

defined('MOODLE_INTERNAL') || die();

require( 'lib.php');
require( '../locallib.php');

$grandprixid = optional_param('grandprixid', 0, PARAM_INT);
if ($grandprixid != 0) {
    mpgame_Delete_Session();
}

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
if ($id == 0) {
    if (array_key_exists( 'mpgame_cmid', $_SESSION)) {
        $id = $_SESSION[ 'mpgame_cmid'];
    }
}

if (!$cm = get_coursemodule_from_id('mpgame', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

// Check login and get context.
require_login($course->id, false, $cm);
$context = mpgame_get_context_module_instance( $cm->id);

require_capability('mod/mpgame:manage', $context);

echo mpgame_getheader( get_string( 'title_administration', 'mpgame'));

mpgame_loadgameinfo();

mpgame_grandprix_loadgameinfo();

$_SESSION[ 'mpgame_id'] = $mpgame->id;
$_SESSION[ 'mpgame_userid'] = -1;
$_SESSION[ 'mpgame_cmid'] = $id;
