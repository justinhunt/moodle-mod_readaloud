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
 * The main readaloud configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

use \mod_readaloud\constants;

/**
 * Module instance settings form
 */
class mod_readaloud_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
    	global $CFG;

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('readaloudname', constants::MOD_READALOUD_LANG), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'readaloudname', constants::MOD_READALOUD_LANG);

         // Adding the standard "intro" and "introformat" fields
        if($CFG->version < 2015051100){
        	$this->add_intro_editor();
        }else{
        	$this->standard_intro_elements();
		}
		
		//time target
		$mform->addElement('duration', 'timelimit', get_string('timelimit',constants::MOD_READALOUD_LANG));
		$mform->setDefault('timelimit',60);
		
		//add other editors
		//could add files but need the context/mod info. So for now just rich text
		$config = get_config(constants::MOD_READALOUD_FRANKY);
		
		//The pasage
		//$edfileoptions = readaloud_editor_with_files_options($this->context);
		$ednofileoptions = readaloud_editor_no_files_options($this->context);
		$opts = array('rows'=>'15', 'columns'=>'80');
		$mform->addElement('editor','passage_editor',get_string('passagelabel',constants::MOD_READALOUD_LANG),$opts, $ednofileoptions);
		
		//welcome and feedback
		$opts = array('rows'=>'6', 'columns'=>'80');
		$mform->addElement('editor','welcome_editor',get_string('welcomelabel',constants::MOD_READALOUD_LANG),$opts, $ednofileoptions);
		$mform->addElement('editor','feedback_editor',get_string('feedbacklabel',constants::MOD_READALOUD_LANG),$opts, $ednofileoptions);
		
		//defaults
		$mform->setDefault('passage_editor',array('text'=>'', 'format'=>FORMAT_MOODLE));		
		$mform->setDefault('welcome_editor',array('text'=>$config->defaultwelcome, 'format'=>FORMAT_MOODLE));
		$mform->setDefault('feedback_editor',array('text'=>$config->defaultfeedback, 'format'=>FORMAT_MOODLE));
		
		//types
		$mform->setType('passage_editor',PARAM_RAW);
		$mform->setType('welcome_editor',PARAM_RAW);
		$mform->setType('feedback_editor',PARAM_RAW);
		
		// Adding targetwpm field
        $mform->addElement('text', 'targetwpm', get_string('targetwpm', constants::MOD_READALOUD_LANG), array('size'=>'8'));
        $mform->setType('targetwpm', PARAM_INT);
		$mform->setDefault('targetwpm',$config->targetwpm);
		
		//allow early exit
		$mform->addElement('advcheckbox', 'allowearlyexit', get_string('allowearlyexit', constants::MOD_READALOUD_LANG), get_string('allowearlyexit_details', constants::MOD_READALOUD_LANG));
		$mform->setDefault('allowearlyexit',$config->allowearlyexit);

        //Enable AI
        $mform->addElement('advcheckbox', 'enableai', get_string('enableai', constants::MOD_READALOUD_LANG), get_string('enableai_details', constants::MOD_READALOUD_LANG));
        $mform->setDefault('enableai',$config->enableai);

        // Adding Acc Adjust field
        $mform->addElement('text', 'accadjust', get_string('accadjust', constants::MOD_READALOUD_LANG), array('size'=>'8'));
        $mform->setType('accadjust', PARAM_INT);
        $mform->setDefault('accadjust',$config->accadjust);

		//Attempts
        $attemptoptions = array(0 => get_string('unlimited', constants::MOD_READALOUD_LANG),
                            1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',);
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', constants::MOD_READALOUD_LANG), $attemptoptions);
		
		//tts options
        $langoptions = \mod_readaloud\utils::get_lang_options();
        $mform->addElement('select', 'ttslanguage', get_string('ttslanguage', constants::MOD_READALOUD_LANG), $langoptions);
        $mform->setDefault('ttslanguage',$config->ttslanguage);


        //region
        $regionoptions = \mod_readaloud\utils::get_region_options();
        $mform->addElement('select', 'region', get_string('region', constants::MOD_READALOUD_LANG), $regionoptions);
        $mform->setDefault('region',$config->awsregion);

        //expiredays
        $expiredaysoptions = \mod_readaloud\utils::get_expiredays_options();
        $mform->addElement('select', 'expiredays', get_string('expiredays', constants::MOD_READALOUD_LANG), $expiredaysoptions);
        $mform->setDefault('expiredays',$config->expiredays);
		
		 // Grade.
        $this->standard_grading_coursemodule_elements();
        
        //grade options
        $gradeoptions = array(constants::MOD_READALOUD_GRADEHIGHEST => get_string('gradehighest',constants::MOD_READALOUD_LANG),
                            constants::MOD_READALOUD_GRADELOWEST => get_string('gradelowest', constants::MOD_READALOUD_LANG),
                            constants::MOD_READALOUD_GRADELATEST => get_string('gradelatest', constants::MOD_READALOUD_LANG),
                            constants::MOD_READALOUD_GRADEAVERAGE => get_string('gradeaverage', constants::MOD_READALOUD_LANG),
							constants::MOD_READALOUD_GRADENONE => get_string('gradenone', constants::MOD_READALOUD_LANG));
        $mform->addElement('select', 'gradeoptions', get_string('gradeoptions', constants::MOD_READALOUD_LANG), $gradeoptions);
		$mform->setDefault('gradeoptions',constants::MOD_READALOUD_GRADELATEST);
		
		

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
	
	
    /**
     * This adds completion rules
	 * The values here are just dummies. They don't work in this project until you implement some sort of grading
	 * See lib.php readaloud_get_completion_state()
     */
	 function add_completion_rules() {
		$mform =& $this->_form;  
		$config = get_config(constants::MOD_READALOUD_FRANKY);
    
		//timer options
        //Add a place to set a mimumum time after which the activity is recorded complete
       $mform->addElement('static', 'mingradedetails', '',get_string('mingradedetails', constants::MOD_READALOUD_LANG));
       $options= array(0=>get_string('none'),20=>'20%',30=>'30%',40=>'40%',50=>'50%',60=>'60%',70=>'70%',80=>'80%',90=>'90%',100=>'40%');
       $mform->addElement('select', 'mingrade', get_string('mingrade', constants::MOD_READALOUD_LANG), $options);
	   
		return array('mingrade');
	}
	
	function completion_rule_enabled($data) {
		return ($data['mingrade']>0);
	}
	
	public function data_preprocessing(&$form_data) {
		//$edfileoptions = readaloud_editor_with_files_options($this->context);
		$ednofileoptions = readaloud_editor_no_files_options($this->context);
		$editors  = readaloud_get_editornames();
		 if ($this->current->instance) {
			$itemid = 0;
			foreach($editors as $editor){
				$form_data = file_prepare_standard_editor((object)$form_data,$editor, $ednofileoptions, $this->context,constants::MOD_READALOUD_FRANKY,$editor, $itemid);
			}
		}
	}
}
