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
 */

defined('MOODLE_INTERNAL') || die();

function mpgame_add_instance( $mpgame) {
    global $DB;

    $mpgame->timemodified = time();
    mpgame_before_add_or_update( $mpgame);

    // May have to add extra stuff in here.

    $id = $DB->insert_record('mpgame', $mpgame);

    $game = $DB->get_record_select( 'mpgame', "id=$id");

    // Do the processing required after an add or an update.
    // Old game_grade_item_update( $mpgame).

    return $id;
}

function mpgame_update_instance($mpgame) {
    global $DB;

    $mpgame->timemodified = time();
    $mpgame->id = $mpgame->instance;

    if (isset( $mpgame->introformat)) {
        if ($mpgame->introformat == null) {
            $mpgame->introformat = 1;
        }
    }
    mpgame_before_add_or_update( $mpgame);

    if (!$DB->update_record("mpgame", $mpgame)) {
        return false;
    }

    // Do the processing required after an add or an update.
    // Old mpgame_grade_item_update( $game);.

    return true;
}

function mpgame_before_add_or_update(&$mpgame) {
    $draftitemid = $mpgame->questionfileid;
    if (isset( $mpgame->id)) {
        $cmg = get_coursemodule_from_instance('mpgame', $mpgame->id, $mpgame->course);
        $modcontext = mpgame_get_context_module_instance( $cmg->id);
        $attachmentoptions = array('subdirs' => 0, 'maxbytes' => 9999999, 'maxfiles' => 1);
        file_save_draft_area_files($draftitemid, $modcontext->id, 'mod_mpgame', 'questionfile', $mpgame->id,
                    array('subdirs' => 0, 'maxbytes' => 9999999, 'maxfiles' => 1));
    }
}

function mpgame_get_context_module_instance( $moduleid) {
    if (class_exists( 'context_module')) {
        return context_module::instance( $moduleid);
    }

    return get_context_instance( CONTEXT_MODULE, $moduleid);
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $quiznode
 * @return void
 */
function mpgame_extend_settings_navigation($settings, $gamenode) {
    global $PAGE, $CFG, $DB;

    $context = $PAGE->cm->context;

    if (!has_capability('mod/mpgame:viewreports', $context)) {
        return;
    }

    if (has_capability('mod/mpgame:view', $context)) {
        $url = new moodle_url('/mod/mpgame/view.php', array('id' => $PAGE->cm->id));
        $gamenode->add(get_string('info', 'mpgame'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/info', ''));
    }

    if (has_capability('mod/mpgame:manage', $context)) {
        $url = new moodle_url('/course/modedit.php', array('update' => $PAGE->cm->id, 'return' => true, 'sesskey' => sesskey()));
        $gamenode->add(get_string('edit', 'moodle', ''), $url, navigation_node::TYPE_SETTING,
            null, null, new pix_icon('t/edit', ''));
    }
}

/* Returns an array of game type objects to construct
   menu list when adding new game  */
require($CFG->dirroot.'/version.php');
if ($branch >= '31') {
    define('MPGAME_USE_GET_SHORTCUTS', '1');
}

if (!defined('MPGAME_USE_GET_SHORTCUTS')) {
    function mpgame_get_types() {
        global $DB;

        $config = get_config('mpgame');

        $types = array();

        $type = new stdClass;
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->type = "mpgame_group_start";
        $type->typestr = '--'.get_string( 'modulenameplural', 'mpgame');
        $types[] = $type;

        // Grandprix.
        $hide = ( isset( $config->hidegrandprix) ? ($config->hidegrandprix != 0) : false);

        if ($hide == false) {
            $type = new stdClass;
            $type->modclass = MOD_CLASS_ACTIVITY;
            $type->type = "mpgame&amp;type=grandprix";
            $type->typestr = get_string('mpgame_grandprix', 'mpgame');
            $types[] = $type;
        }

        // Quiz.
        $hide = ( isset( $config->hidequiz) ? ($config->hidequiz != 0) : false);

        if ($hide == false) {
            $type = new stdClass;
            $type->modclass = MOD_CLASS_ACTIVITY;
            $type->type = "mpgame&amp;type=quiz";
            $type->typestr = get_string('mpgame_quiz', 'mpgame');
            $types[] = $type;
        }

        $type = new stdClass;
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->type = "mpgame_group_end";
        $type->typestr = '--';
        $types[] = $type;

        return $types;
    }
}

if (defined('MPGAME_USE_GET_SHORTCUTS')) {
    /**
     * Returns an array of game type objects to construct
     * menu list when adding new mpgame
     *
     */
    function mpgame_get_shortcuts($defaultitem) {
        global $DB, $CFG;

        $config = get_config('mpgame');
        $types = array();

        // Grandprix.
        $hide = ( isset( $config->hidegrandprix) ? ($config->hidegrandprix != 0) : false);
        if ($hide == false) {
            $type = new stdClass;
            $type->archetype = MOD_CLASS_ACTIVITY;
            $type->type = "mpgame&type=grandprix";
            $type->name = preg_replace('/.*type=/', '', $type->type);
            $type->title = get_string('mpgame_grandprix', 'mpgame');
            $type->link = new moodle_url($defaultitem->link, array('type' => $type->name));
            if (empty($type->help) && !empty($type->name) &&
                get_string_manager()->string_exists('help' . $type->name, 'mpgame')) {
                    $type->help = get_string('help' . $type->name, 'mpgame');
            }
            $types[] = $type;
        }

        // Quiz.
        $hide = ( isset( $config->hidequiz) ? ($config->hidequiz != 0) : false);
        if ($hide == false) {
            $type = new stdClass;
            $type->archetype = MOD_CLASS_ACTIVITY;
            $type->type = "mpgame&type=quiz";
            $type->name = preg_replace('/.*type=/', '', $type->type);
            $type->title = get_string('mpgame_quiz', 'mpgame');
            $type->link = new moodle_url($defaultitem->link, array('type' => $type->name));
            if (empty($type->help) && !empty($type->name) &&
                get_string_manager()->string_exists('help' . $type->name, 'mpgame')) {
                    $type->help = get_string('help' . $type->name, 'mpgame');
            }
            $types[] = $type;
        }

        return $types;
    }
}

function mpgame_delete_instance($mpgameid) {
    global $CFG, $DB;

    $tables = array( 'mpgame_grandprix', 'mpgame_grandprix_hits', 'mpgame_grandprix_logins', 'mpgame_grandprix_questions',
        'mpgame_grandprix_rounds', 'mpgame_grandprix_rounds_user', 'mpgame_grandprix_users',
        'mpgame_quiz', 'mpgame_quiz_computers', 'mpgame_quiz_hits', 'mpgame_quiz_logins', 'mpgame_quiz_logins',
        'mpgame_quiz_rounds', 'mpgame_quiz_rounds_questions', 'mpgame_quiz_rounds_users', 'mpgame_quiz_users');

    foreach ($tables as $t) {
        $sql = "DELETE FROM {$CFG->prefix}{$t} WHERE mpgameid=$mpgameid";
        if (!$DB->execute( $sql)) {
            return false;
        }
    }

    return true;
}

/**
 * @uses FEATURE_GRADE_HAS_GRADE
 * @return bool True if quiz supports feature
 */
function mpgame_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
                return true;
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_RATE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

function mpgame_get_question_file( $mpgame) {
    if ($mpgame->questionfile != '') {
        return file_get_contents( $mpgame->questionfile);
    }
    $cmg = get_coursemodule_from_instance('mpgame', $mpgame->id, $mpgame->course);
    $modcontext = mpgame_get_context_module_instance( $cmg->id);

    $fs = get_file_storage();
    $files = $fs->get_area_files($modcontext->id, 'mod_mpgame', 'questionfile', $mpgame->id);
    $f = false;
    foreach ($files as $f) {
        if ($f->is_directory()) {
            continue;
        }
        break;
    }

    if ($f === false) {
        print_error( 'No file specified');
    }

    return $f;
}
