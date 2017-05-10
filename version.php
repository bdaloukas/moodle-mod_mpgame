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
 * Code fragment to define the version of game
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 **/

defined('MOODLE_INTERNAL') || die();

if (!isset( $plugin)) {
    $plugin = new stdClass;
    $useplugin = 0;
} else if ($plugin == 'mod_mpgame') {
    $plugin = new stdClass;
    $useplugin = 1;
} else {
    $useplugin = 2;
}

$plugin->component = 'mod_mpgame';  // Full name of the plugin (used for diagnostics).
$plugin->version   = 2017051001;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2010112400;  // Requires Moodle 2.0.
$plugin->cron      = 0;           // Period for cron to check this module (secs).
$plugin->release   = '2017-05-10';

if ($useplugin != 2) {
    $module = $plugin;
}
