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
require_once($CFG->dirroot . '/mod/readaloud/lib.php');

use mod_readaloud\constants;
use mod_readaloud\utils;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtextarea(constants::M_COMPONENT . '/defaultwelcome',
            get_string('welcomelabel', constants::M_COMPONENT), get_string('welcomelabel_details', constants::M_COMPONENT),
            get_string('defaultwelcome', constants::M_COMPONENT), PARAM_TEXT));
    $settings->add(new admin_setting_configtextarea(constants::M_COMPONENT . '/defaultfeedback',
            get_string('feedbacklabel', constants::M_COMPONENT), get_string('feedbacklabel_details', constants::M_COMPONENT),
            get_string('defaultfeedback', constants::M_COMPONENT), PARAM_TEXT));

    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . '/targetwpm',
            get_string('targetwpm', constants::M_COMPONENT), get_string('targetwpm_details', constants::M_COMPONENT), 100,
            PARAM_INT));

    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . '/apiuser',
            get_string('apiuser', constants::M_COMPONENT), get_string('apiuser_details', constants::M_COMPONENT), '', PARAM_TEXT));

    $cloudpoodllapiuser = get_config(constants::M_COMPONENT, 'apiuser');
    $cloudpoodllapisecret = get_config(constants::M_COMPONENT, 'apisecret');
    $showbelowapisecret = '';
    // if we have an API user and secret we fetch token
    if(!empty($cloudpoodllapiuser) && !empty($cloudpoodllapisecret)) {
        $tokeninfo = utils::fetch_token_for_display($cloudpoodllapiuser, $cloudpoodllapisecret);
        $showbelowapisecret = $tokeninfo;
        // if we have no API user and secret we show a "fetch from elsewhere on site" or "take a free trial" link
    }else{
        $amddata = ['apppath' => $CFG->wwwroot . '/' .constants::M_URL];
        $cpcomponents = ['filter_poodll', 'qtype_cloudpoodll', 'mod_wordcards', 'mod_solo', 'mod_minilesson', 'mod_englishcentral', 'mod_pchat',
            'atto_cloudpoodll', 'tinymce_cloudpoodll', 'assignsubmission_cloudpoodll', 'assignfeedback_cloudpoodll'];
        foreach($cpcomponents as $cpcomponent){
            switch($cpcomponent){
                case 'filter_poodll':
                    $apiusersetting = 'cpapiuser';
                    $apisecretsetting = 'cpapisecret';
                    break;
                case 'mod_englishcentral':
                    $apiusersetting = 'poodllapiuser';
                    $apisecretsetting = 'poodllapisecret';
                    break;
                default:
                    $apiusersetting = 'apiuser';
                    $apisecretsetting = 'apisecret';
            }
            $cloudpoodllapiuser = get_config($cpcomponent, $apiusersetting);
            if(!empty($cloudpoodllapiuser)){
                $cloudpoodllapisecret = get_config($cpcomponent, $apisecretsetting);
                if(!empty($cloudpoodllapisecret)){
                    $amddata['apiuser'] = $cloudpoodllapiuser;
                    $amddata['apisecret'] = $cloudpoodllapisecret;
                    break;
                }
            }
        }
        $showbelowapisecret = $OUTPUT->render_from_template( constants::M_COMPONENT . '/managecreds', $amddata);
    }

    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . '/apisecret',
            get_string('apisecret', constants::M_COMPONENT), $showbelowapisecret, '', PARAM_TEXT));

    // Cloud Poodll Server.
    $settings->add(new admin_setting_configtext(constants::M_COMPONENT .  '/cloudpoodllserver',
        get_string('cloudpoodllserver', constants::M_COMPONENT),
        get_string('cloudpoodllserver_details', constants::M_COMPONENT),
        constants::M_DEFAULT_CLOUDPOODLL, PARAM_URL));

    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT . '/enableai',
            get_string('enableai', constants::M_COMPONENT), get_string('enableai_details', constants::M_COMPONENT), 1));

    // we removed this to simplify things, can bring back as feature later
    $accadjustoptions = \mod_readaloud\utils::get_accadjust_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . '/accadjustmethod',
            get_string('accadjustmethod', constants::M_COMPONENT),
            get_string('accadjustmethod_details', constants::M_COMPONENT),
            constants::ACCMETHOD_NONE, $accadjustoptions));

    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . '/accadjust',
            get_string('accadjust', constants::M_COMPONENT), get_string('accadjust_details', constants::M_COMPONENT), 0,
            PARAM_INT));

    $regions = \mod_readaloud\utils::get_region_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . '/awsregion',
            get_string('awsregion', constants::M_COMPONENT),
            get_string('awsregion_details', constants::M_COMPONENT), 'useast1', $regions));

    $expiredays = \mod_readaloud\utils::get_expiredays_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . '/expiredays',
            get_string('expiredays', constants::M_COMPONENT), '', '365', $expiredays));

    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT . '/allowearlyexit',
            get_string('allowearlyexit', constants::M_COMPONENT),
            get_string('allowearlyexit_defaultdetails', constants::M_COMPONENT), 1));


    // Passage Transcriber options
    $name = 'stricttranscribe';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::TRANSCRIBER_GUIDED;
    $options = utils::fetch_options_transcribers();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    // Line Transcriber options
    $name = 'transcriber';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::TRANSCRIBER_GUIDED;
    $options = utils::fetch_options_transcribers();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT .  '/alternatestreaming',
    get_string('alternatestreaming', constants::M_COMPONENT), get_string('alternatestreaming_details', constants::M_COMPONENT), 0));

    

    // Activity Step settings
    $stepoptions = [constants::STEP_LISTEN => new lang_string('enablepreview', constants::M_COMPONENT),
        constants::STEP_PRACTICE => new lang_string('enablelandr', constants::M_COMPONENT),
        constants::STEP_SHADOW => new lang_string('enableshadow', constants::M_COMPONENT),
        constants::STEP_READ => new lang_string('enableread', constants::M_COMPONENT),
        constants::STEP_QUIZ => new lang_string('enablequiz', constants::M_COMPONENT)];

    $stepdefaults =
        [constants::STEP_LISTEN => 1, constants::STEP_PRACTICE => 1, constants::STEP_SHADOW => 0, constants::STEP_READ => 1, constants::STEP_QUIZ => 1];
    // create a binary string of the defaults, eg 1101
    // $stepdefaults = decbin(constants::STEP_LISTEN + constants::STEP_PRACTICE + constants::STEP_READ);
    $settings->add(new admin_setting_configmulticheckbox(constants::M_COMPONENT . '/activitysteps',
        get_string('activitysteps', constants::M_COMPONENT),
        get_string('activitystepsdetails', constants::M_COMPONENT), $stepdefaults, $stepoptions));


    // Default recorders
    $recoptions = utils::fetch_options_recorders();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT .'/defaultrecorder',
             get_string('defaultrecorder', constants::M_COMPONENT),
             get_string('defaultrecorder_details', constants::M_COMPONENT), constants::REC_ONCE, $recoptions));



    // session score method
    $name = 'sessionscoremethod';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::SESSIONSCORE_NORMAL;
    $options = \mod_readaloud\utils::get_sessionscore_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));


    // machine grade method
    $name = 'machinegrademethod';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::MACHINEGRADE_HYBRID;
    $options = \mod_readaloud\utils::get_machinegrade_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));

    // Evaluation view (what students see after an attempt)
    $name = 'humanpostattempt';
    $label = get_string('evaluationview', constants::M_COMPONENT);
    $details = get_string('evaluationview_details', constants::M_COMPONENT);
    $default = constants::POSTATTEMPT_EVALERRORS;
    $options = \mod_readaloud\utils::get_postattempt_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));
    /*
        $settings->add(new admin_setting_configselect(constants::M_COMPONENT .  '/machinepostattempt',
            get_string('machinepostattempt', constants::M_COMPONENT),
            get_string('machinepostattempt_details',constants::M_COMPONENT),
            constants::POSTATTEMPT_EVAL, $postattempt_options));
        */

    /*
    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT .  '/enabletts',
    get_string('enabletts', constants::M_COMPONENT), get_string('enabletts_details',constants::M_COMPONENT), 0));
    */

    // Language options
    $name = 'ttslanguage';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_LANG_ENUS;
    $options = \mod_readaloud\utils::get_lang_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));

    // TTS voice
    $name = 'ttsvoice';
    $label = get_string($name, constants::M_COMPONENT);
    $details = "";
    $default = "Amy";
    $options = \mod_readaloud\utils::fetch_ttsvoice_options('useast1');
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));

    // Items per page options
    $name = 'itemsperpage';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = 10;
    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . "/$name",
            $label, $details, $default, PARAM_INT));


    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT .  '/disableshadowgrading',
        get_string('disableshadowgrading', constants::M_COMPONENT), get_string('disableshadowgrading_details', constants::M_COMPONENT), 0));

    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT .  '/enablesetuptab',
            get_string('enablesetuptab', constants::M_COMPONENT), get_string('enablesetuptab_details', constants::M_COMPONENT), 0));

    // Native Language Setting
    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT .  '/setnativelanguage',
        get_string('enablenativelanguage', constants::M_COMPONENT), get_string('enablenativelanguage_details', constants::M_COMPONENT), 1));


    // St Dashboard Id
    $name = 'stdashboardid';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = 0;
    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . "/$name",
            $label, $details, $default, PARAM_INT));


}
