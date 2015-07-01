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
 * Defines all the backup steps that will be used by {@link backup_readaloud_activity_task}
 *
 * @package     mod_readaloud
 * @category    backup
 * @copyright   2015 Justin Hunt (poodllsupport@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/readaloud/lib.php');

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_readaloud_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the readaloud element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing readaloud instance
        $oneactivity = new backup_nested_element(MOD_READALOUD_MODNAME, array('id'), array(
            'course','name','intro','introformat','someinstancesetting','gradeoptions','maxattempts','mingrade',
			'timecreated','timemodified'
			));
		
		//attempts
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'),array(
			MOD_READALOUD_MODNAME ."id","course","userid","status","sessionscore","audiofile","timecreated","timemodified"
		));

		
		// Build the tree.
        $oneactivity->add_child($attempts);
        $attempts->add_child($attempt);
		


        // Define sources.
        $oneactivity->set_source_table(MOD_READALOUD_TABLE, array('id' => backup::VAR_ACTIVITYID));

        //sources if including user info
        if ($userinfo) {
			$attempt->set_source_table(MOD_READALOUD_USERTABLE,
											array(MOD_READALOUD_MODNAME . 'id' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');


        // Define file annotations.
        // intro file area has 0 itemid.
        $oneactivity->annotate_files(MOD_READALOUD_FRANKY, 'intro', null);

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($oneactivity);
		

    }
}
