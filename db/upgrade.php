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
 * This file keeps track of upgrades to the readaloud module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute readaloud upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_readaloud_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // Add allowearlyexit field
    if ($oldversion < 2015071501) {

        // Define field introformat to be added to readaloud
        $table = new xmldb_table('readaloud');
        $field = new xmldb_field('allowearlyexit', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2015071501, 'readaloud');
    }

    // Add allowearlyexit field
    if ($oldversion < 2015071502) {

        // Define field grade to be added to readaloud
        $table = new xmldb_table('readaloud');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2015071502, 'readaloud');
    }

    // Add wcpm field
    if ($oldversion < 2015072201) {

        // Define field wpcm to be added to readaloud_attempt
        $table = new xmldb_table('readaloud_attempt');
        $field = new xmldb_field('wpm', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2015072201, 'readaloud');
    }

    // Add accuracy and targetwpm fields
    if ($oldversion < 2015072701) {

        // Define field wpcm to be added to readaloud_attempt
        $table = new xmldb_table('readaloud_attempt');
        $field = new xmldb_field('accuracy', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field wpcm to be added to readaloud_attempt
        $table = new xmldb_table('readaloud');
        $field = new xmldb_field('targetwpm', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '100');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2015072701, 'readaloud');
    }
    // Rename fedbackformat to feedbackformat
    if ($oldversion < 2016022102) {

        // Define field wpcm to be added to readaloud_attempt
        $table = new xmldb_table('readaloud');
        $field = new xmldb_field('fedbackformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Rename field to feedbackformat
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'feedbackformat');
        }

        upgrade_mod_savepoint(true, 2016022102, 'readaloud');
    }

    if ($oldversion < 2018060900) {
        $table = new xmldb_table('readaloud_ai_result');

        // Adding fields to table tool_dataprivacy_contextlist.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('readaloudid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('transcript', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('fulltranscript', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('wpm', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('accuracy', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sessionscore', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sessiontime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sessionerrors', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('sessionendword', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table tool_dataprivacy_contextlist.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for tool_dataprivacy_contextlist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('readaloud');
        $field = new xmldb_field('enableai', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2018060900, 'readaloud');
    }

    // Add expiredays and region to readaloud table
    if ($oldversion < 2018060902) {
        $table = new xmldb_table('readaloud');

        // Define field expiredays to be added to readaloud
        $field = new xmldb_field('expiredays', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '365');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field expiredays to be added to readaloud
        $field = new xmldb_field('region', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 'useast1');
        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018060902, 'readaloud');
    }

    // Add accadjust to readaloud table
    if ($oldversion < 2018071300) {
        $table = new xmldb_table('readaloud');

        //This allows the activity admin to compensate for a certain no. of errors to compensate for machine transcription errors
        $field = new xmldb_field('accadjust', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018071300, 'readaloud');
    }

    // Add session matches to databaase
    if ($oldversion < 2018073101) {
        $table = new xmldb_table('readaloud_ai_result');

        //records the matched words in the passage and their transcript location. For debugging, passage tweaking, and audio location
        $field = new xmldb_field('sessionmatches', XMLDB_TYPE_TEXT, null, null, null, null);

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018073101, 'readaloud');
    }

    // Add accadjustmethod to readaloud table
    if ($oldversion < 2018082400) {
        $table = new xmldb_table('readaloud');

        //This allows the activity admin to compensate for a certain no. of errors to compensate for machine transcription errors
        $field = new xmldb_field('accadjustmethod', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018082400, 'readaloud');
    }

    // Add accadjustmethod to readaloud table
    if ($oldversion < 2018082402) {
        $table = new xmldb_table('readaloud_ai_result');

        //This allows the activity admin to compensate for a certain no. of errors to compensate for machine transcription errors
        $field = new xmldb_field('errorcount', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // add field to AI table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        //add field to attempts table
        $table = new xmldb_table('readaloud_attempt');
        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018082402, 'readaloud');
    }
    // Add humanpostattempt and machinepostattempt to readaloud table
    if ($oldversion < 2018082403) {
        $table = new xmldb_table('readaloud');

        //This adds the post attempt display options for each of the evaluation methods (machine and human)
        $field1 = new xmldb_field('humanpostattempt', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '2');
        $field2 = new xmldb_field('machinepostattempt', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // add fields to readaloud table
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        upgrade_mod_savepoint(true, 2018082403, 'readaloud');
    }
    // Add "alternatives" to readaloud table
    if ($oldversion < 2018082404) {
        $table = new xmldb_table('readaloud');

        //This adds the post attempt display options for each of the evaluation methods (machine and human)
        $field = new xmldb_field('alternatives', XMLDB_TYPE_TEXT, null, null, null, null);

        // add fields to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018082404, 'readaloud');
    }

    // Add accadjustmethod to readaloud table
    if ($oldversion < 2018090700) {
        $table = new xmldb_table('readaloud');

        //This allows the activity admin to compensate for a certain no. of errors to compensate for machine transcription errors
        $field = new xmldb_field('machgrademethod', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018090700, 'readaloud');
    }

    // Add accadjustmethod to readaloud table
    if ($oldversion < 2018101200) {
        $table = new xmldb_table('readaloud');

        //This allows the activity admin to compensate for a certain no. of errors to compensate for machine transcription errors
        $field = new xmldb_field('activitylink', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018101200, 'readaloud');
    }

    // Add transcriber to readaloud table
    if ($oldversion < 2019052000) {
        $table = new xmldb_table('readaloud');

        //This allows the activity admin to compensate for a certain no. of errors to compensate for machine transcription errors
        $field = new xmldb_field('transcriber', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2019052000, 'readaloud');
    }

    // Add submitrawaudio to readaloud table
    if ($oldversion < 2019101400) {
        $table = new xmldb_table('readaloud');

        //This allows the activity admin to submit raw audio (as opposed to recording). Usually for some sort of disaster recovery
        $field = new xmldb_field('submitrawaudio', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2019101400, 'readaloud');
    }

    // Add sessionscoremethod to readaloud table.
    if ($oldversion < 2020021800) {
        $table = new xmldb_table('readaloud');

        //
        $field = new xmldb_field('sessionscoremethod', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2020021800, 'readaloud');
    }

    // Add sessionscoremethod to readaloud table.
    if ($oldversion < 2020022000) {
        $table = new xmldb_table('readaloud');

        $fields=array();
        //Model Audio URL
        $fields[] = new xmldb_field('modelaudiourl', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, null, null, null);
        //Model audio breaks (JSON)
        $fields[] = new xmldb_field('modelaudiobreaks', XMLDB_TYPE_TEXT, null, null, null, null);
        //TTS Voice
        $fields[] = new xmldb_field('ttsvoice', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 'Amy');
        //Enable preview mode
        $fields[] = new xmldb_field('enablepreview', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        //Enable shadow mode
        $fields[] = new xmldb_field('enableshadow', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // add fields to readaloud table
        foreach($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2020022000, 'readaloud');
    }

    // Add TTS Speed to readaloud table.
    if ($oldversion < 2020022900) {
        $table = new xmldb_table('readaloud');

        //
        $field = new xmldb_field('ttsspeed', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2020022900, 'readaloud');
    }

    // Add expiredays and region to readaloud table
    if ($oldversion < 2020060100) {
        $table = new xmldb_table('readaloud');

        // Define field expiredays to be added to readaloud
        $field = new xmldb_field('recorder', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 'readaloud');
        // add field to readaloud table
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020060100, 'readaloud');
    }


    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
