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
 * readaloud module admin settings and defaults
 *
 * @package    mod
 * @subpackage readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/mod/readaloud/lib.php');

if ($ADMIN->fulltree) {



	 $settings->add(new admin_setting_configtextarea('mod_readaloud/defaultwelcome',
        get_string('welcomelabel', 'readaloud'), get_string('welcomelabel_details', MOD_READALOUD_LANG), get_string('defaultwelcome',MOD_READALOUD_LANG), PARAM_TEXT));
	 $settings->add(new admin_setting_configtextarea('mod_readaloud/defaultfeedback',
        get_string('feedbacklabel', 'readaloud'), get_string('feedbacklabel_details', MOD_READALOUD_LANG), get_string('defaultfeedback',MOD_READALOUD_LANG), PARAM_TEXT));
		
	 $settings->add(new admin_setting_configtext('mod_readaloud/targetwpm',
        get_string('targetwpm', MOD_READALOUD_LANG), get_string('targetwpm_details', MOD_READALOUD_LANG), 100, PARAM_INT));
		
	$settings->add(new admin_setting_configcheckbox('mod_readaloud/allowearlyexit', 
	 get_string('allowearlyexit', MOD_READALOUD_LANG), get_string('allowearlyexit_defaultdetails',MOD_READALOUD_LANG), 0));	
		
	 $settings->add(new admin_setting_configcheckbox('mod_readaloud/enabletts', 
	 get_string('enabletts', MOD_READALOUD_LANG), get_string('enabletts_details',MOD_READALOUD_LANG), 0));
	 
	 $langoptions = \mod_readaloud\utils::get_lang_options();
	 $settings->add(new admin_setting_configselect('mod_readaloud/ttslanguage', get_string('ttslanguage', MOD_READALOUD_LANG), '', 'en', $langoptions));
	 
	 $settings->add(new admin_setting_configtext('mod_readaloud/itemsperpage',
        get_string('itemsperpage', MOD_READALOUD_LANG), get_string('itemsperpage_details', MOD_READALOUD_LANG), 40, PARAM_INT));
}
