<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:31
 */

namespace mod_readaloud\local\itemform;

use \mod_readaloud\constants;

class freewritingform extends baseform {

    public $type = constants::TYPE_FREEWRITING;

    public function custom_definition() {
        global $CFG;

        $this->add_itemsettings_heading();
        $mform = $this->_form;
        $this->add_static_text('instructions', '', get_string('freewritingdesc', constants::M_COMPONENT));
        $this->add_numericboxresponse(constants::TOTALMARKS, get_string('totalmarks', constants::M_COMPONENT), true);
        $mform->setDefault(constants::TOTALMARKS, 5);
        $this->add_static_text('freewritingtotalmarks_instructions', '', get_string('fw_totalmarks_instructions', constants::M_COMPONENT));
        $this->add_numericboxresponse(constants::TARGETWORDCOUNT, get_string('targetwordcount_title', constants::M_COMPONENT), false);
        $mform->setDefault(constants::TARGETWORDCOUNT, 60);
        $this->add_textarearesponse(constants::AIGRADE_INSTRUCTIONS, get_string('aigrade_instructions', constants::M_COMPONENT), true);
        $mform->setDefault(constants::AIGRADE_INSTRUCTIONS, get_string('freewriting_default_aigrade', constants::M_COMPONENT));
        $this->add_textarearesponse(constants::AIGRADE_FEEDBACK, get_string('aigrade_feedback', constants::M_COMPONENT), true);
        $mform->setDefault(constants::AIGRADE_FEEDBACK, get_string('freewriting_default_aigradefeedback', constants::M_COMPONENT));
        // Feedback language.
        $this->add_languageselect(constants::AIGRADE_FEEDBACK_LANGUAGE,
            get_string('aigrade_feedback_language', constants::M_COMPONENT),
            constants::M_LANG_ENUS
        );

        $this->add_relevanceoptions(constants::RELEVANCE, get_string('relevancetype', constants::M_COMPONENT),
            constants::RELEVANCETYPE_NONE);
        $this->add_textarearesponse(constants::AIGRADE_MODELANSWER, get_string('aigrade_modelanswer', constants::M_COMPONENT), false);
        $m35 = $CFG->version >= 2018051700;
        if ($m35) {
            $mform->hideIf(constants::AIGRADE_MODELANSWER, constants::RELEVANCE, 'neq', constants::RELEVANCETYPE_MODELANSWER);
        } else {
            $mform->disabledIf(constants::AIGRADE_MODELANSWER, constants::RELEVANCE, 'neq', constants::RELEVANCETYPE_MODELANSWER);
        }

        $this->add_timelimit(constants::TIMELIMIT, get_string(constants::TIMELIMIT, constants::M_COMPONENT));
    }
}