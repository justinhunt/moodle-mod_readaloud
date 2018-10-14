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

use \mod_readaloud\constants;

if ($ADMIN->fulltree) {

	 $settings->add(new admin_setting_configtextarea('mod_readaloud/defaultwelcome',
        get_string('welcomelabel', 'readaloud'), get_string('welcomelabel_details', constants::MOD_READALOUD_LANG), get_string('defaultwelcome',constants::MOD_READALOUD_LANG), PARAM_TEXT));
	 $settings->add(new admin_setting_configtextarea('mod_readaloud/defaultfeedback',
        get_string('feedbacklabel', 'readaloud'), get_string('feedbacklabel_details', constants::MOD_READALOUD_LANG), get_string('defaultfeedback',constants::MOD_READALOUD_LANG), PARAM_TEXT));
		
	 $settings->add(new admin_setting_configtext('mod_readaloud/targetwpm',
        get_string('targetwpm', constants::MOD_READALOUD_LANG), get_string('targetwpm_details', constants::MOD_READALOUD_LANG), 100, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_readaloud/apiuser',
        get_string('apiuser', constants::MOD_READALOUD_LANG), get_string('apiuser_details', constants::MOD_READALOUD_LANG), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_readaloud/apisecret',
        get_string('apisecret', constants::MOD_READALOUD_LANG), get_string('apisecret_details', constants::MOD_READALOUD_LANG), '', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('mod_readaloud/enableai',
        get_string('enableai', constants::MOD_READALOUD_LANG), get_string('enableai_details',constants::MOD_READALOUD_LANG), 1));
/*
 * //we removed this to simplify things, can bring back as feature later
    $autoacc_options = \mod_readaloud\utils::get_autoaccmethod_options();
    $settings->add(new admin_setting_configselect('mod_readaloud/accadjustmethod',
        get_string('accadjustmethod', constants::MOD_READALOUD_LANG),
        get_string('accadjustmethod_details',constants::MOD_READALOUD_LANG),
        constants::ACCMETHOD_NONE, $autoacc_options));


    $settings->add(new admin_setting_configtext('mod_readaloud/accadjust',
        get_string('accadjust', constants::MOD_READALOUD_LANG), get_string('accadjust_details', constants::MOD_READALOUD_LANG), 0, PARAM_INT));
*/
    $regions = \mod_readaloud\utils::get_region_options();
    $settings->add(new admin_setting_configselect('mod_readaloud/awsregion', get_string('awsregion', constants::MOD_READALOUD_LANG), '', 'useast1', $regions));

    $expiredays = \mod_readaloud\utils::get_expiredays_options();
    $settings->add(new admin_setting_configselect('mod_readaloud/expiredays', get_string('expiredays', constants::MOD_READALOUD_LANG), '', '365', $expiredays));


    $settings->add(new admin_setting_configcheckbox('mod_readaloud/allowearlyexit',
	 get_string('allowearlyexit', constants::MOD_READALOUD_LANG), get_string('allowearlyexit_defaultdetails',constants::MOD_READALOUD_LANG), 0));

    $machinegradeoptions = \mod_readaloud\utils::get_machinegrade_options();
    $settings->add(new admin_setting_configselect('mod_readaloud/machinegrademethod', get_string('machinegrademethod', constants::MOD_READALOUD_LANG),
        get_string('machinegrademethod_help', constants::MOD_READALOUD_LANG), constants::MACHINEGRADE_MACHINE, $machinegradeoptions));

    $postattempt_options = \mod_readaloud\utils::get_postattempt_options();
    $settings->add(new admin_setting_configselect('mod_readaloud/humanpostattempt',
        get_string('evaluationview', constants::MOD_READALOUD_LANG),
        get_string('evaluationview_details',constants::MOD_READALOUD_LANG),
        constants::POSTATTEMPT_EVALERRORS, $postattempt_options));
/*
    $settings->add(new admin_setting_configselect('mod_readaloud/machinepostattempt',
        get_string('machinepostattempt', constants::MOD_READALOUD_LANG),
        get_string('machinepostattempt_details',constants::MOD_READALOUD_LANG),
        constants::POSTATTEMPT_EVAL, $postattempt_options));
    */

    /*
	 $settings->add(new admin_setting_configcheckbox('mod_readaloud/enabletts', 
	 get_string('enabletts', constants::MOD_READALOUD_LANG), get_string('enabletts_details',constants::MOD_READALOUD_LANG), 0));
	 */

	 $langoptions = \mod_readaloud\utils::get_lang_options();
	 $settings->add(new admin_setting_configselect('mod_readaloud/ttslanguage', get_string('ttslanguage', constants::MOD_READALOUD_LANG), '', 'en', $langoptions));
	 
	 $settings->add(new admin_setting_configtext('mod_readaloud/itemsperpage',
        get_string('itemsperpage', constants::MOD_READALOUD_LANG), get_string('itemsperpage_details', constants::MOD_READALOUD_LANG), 10, PARAM_INT));

}
