<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_readaloud\local\itemform;

///////////////////////////////////////////////////////////////////////////
//
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//
///////////////////////////////////////////////////////////////////////////

/**
 * Forms for readaloud Activity
 *
 * @package    mod_readaloud
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

// why do we need to include this?
require_once($CFG->libdir . '/formslib.php');

use mod_readaloud\constants;
use mod_readaloud\utils;

/**
 * Abstract class that item type's inherit from.
 *
 * This is the abstract class that add item type forms must extend.
 *
 * @abstract
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class baseform extends \moodleform {

    /**
     * This is used to identify this itemtype.
     * @var string
     */
    public $type;

    /**
     * The simple string that describes the item type e.g. audioitem, textitem
     * @var string
     */
    public $typestring;


    /**
     * An array of options used in the htmleditor
     * @var array
     */
    protected $editoroptions = [];

    /**
     * An array of options used in the filemanager
     * @var array
     */
    protected $filemanageroptions = [];

    /**
     * An array of options used in the filemanager
     * @var array
     */
    protected $moduleinstance = null;


    /**
     * True if this is a standard item of false if it does something special.
     * items are standard items
     * @var bool
     */
    protected $standard = true;

    /**
     * Each item type can and should override this to add any custom elements to
     * the basic form that they want
     */
    public function custom_definition() {
    }

    /**
     * Used to determine if this is a standard item or a special item
     * @return bool
     */
    final public function is_standard() {
        return (bool)$this->standard;
    }

    /**
     * Add the required basic elements to the form.
     *
     * This method adds the basic elements to the form including title and contents
     * and then calls custom_definition();
     */
    final public function definition() {
        global $CFG, $OUTPUT;

        $m35 = $CFG->version >= 2018051700;
        $mform = $this->_form;
        $this->editoroptions = $this->_customdata['editoroptions'];
        $this->filemanageroptions = $this->_customdata['filemanageroptions'];
        $this->moduleinstance = $this->_customdata['moduleinstance'];

        $mform->addElement('header', 'typeheading', get_string('createaitem', constants::M_COMPONENT, get_string($this->type, constants::M_COMPONENT)));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        if ($this->standard === true) {
            $mform->addElement('hidden', 'type');
            $mform->setType('type', PARAM_TEXT);

            $mform->addElement('hidden', 'itemorder');
            $mform->setType('itemorder', PARAM_INT);

            $mform->addElement('text', 'name', get_string('itemtitle', constants::M_COMPONENT), ['size' => 70]);
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', get_string('required'), 'required', null, 'client');
            $typelabel = get_string($this->type, constants::M_COMPONENT);
            $mform->setDefault('name', get_string('newitem', constants::M_COMPONENT, $typelabel));

            // Question instructions
            $mform->addElement('text', constants::TEXTINSTRUCTIONS, get_string('iteminstructions', constants::M_COMPONENT), ['size' => 70]);
            $mform->setType(constants::TEXTINSTRUCTIONS, PARAM_RAW);

            // Question text
            $mform->addElement('textarea', constants::TEXTQUESTION, get_string('itemcontents', constants::M_COMPONENT), ['wrap' => 'virtual', 'style' => 'width: 100%;']);
            $mform->setType(constants::TEXTQUESTION, PARAM_RAW);
            // add layout
            $this->add_layoutoptions();
            switch($this->type) {

                case constants::TYPE_PAGE:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        '');
                    break;

                case constants::TYPE_MULTIAUDIO:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('multiaudio_instructions1', constants::M_COMPONENT));
                    break;

                case constants::TYPE_MULTICHOICE:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('multichoice_instructions1', constants::M_COMPONENT));
                    break;

                case constants::TYPE_SHORTANSWER:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('shortanswer_instructions1', constants::M_COMPONENT));
                    break;

                // listening gapfill
                case constants::TYPE_LGAPFILL:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('lg_instructions1', constants::M_COMPONENT));
                    break;

                // typing gapfill
                case constants::TYPE_TGAPFILL:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('tg_instructions1', constants::M_COMPONENT));
                    break;
                // speaking gapfill
                case constants::TYPE_SGAPFILL:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('sg_instructions1', constants::M_COMPONENT));
                    break;

                // free writing
                case constants::TYPE_FREEWRITING:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('freewriting_instructions1', constants::M_COMPONENT));
                    break;

                // free speaking
                case constants::TYPE_FREESPEAKING:
                    $mform->setDefault(constants::TEXTINSTRUCTIONS,
                        get_string('freespeaking_instructions1', constants::M_COMPONENT));
                    break;

            }

            // add the media prompts chooser and fields
            $mform->addElement('header', 'mediapromptsheading', get_string('mediaprompts', constants::M_COMPONENT));
            $this->add_media_prompts();
            $mform->setExpanded('mediapromptsheading', true);

        }//end of if standard = true

        // visibility
        // $mform->addElement('selectyesno', 'visible', get_string('visible'));
        $mform->addElement('hidden', 'visible', 1);
        $mform->setType('visible', PARAM_INT);

        $this->custom_definition();

        // add the action buttons
        $mform->closeHeaderBefore('cancel');
        $this->add_action_buttons(get_string('cancel'), get_string('saveitem', constants::M_COMPONENT));

    }

    protected function add_itemsettings_heading() {
        // add the heading
        $this->_form->addElement('header', 'itemsettingsheading', get_string('itemsettingsheadings', constants::M_COMPONENT));
        $this->_form->setExpanded('itemsettingsheading');
    }

    final protected function add_static_text($name, $label = null, $text='') {

        $this->_form->addElement('static', $name, $label, $text);

    }

    final protected function add_repeating_textboxes($name, $repeatno=5) {
        global $DB;

        $additionalfields = 1;
        $repeatarray = [];
        $repeatarray[] = $this->_form->createElement('text', $name, get_string($name. 'no', constants::M_COMPONENT));
        // $repeatarray[] = $this->_form->createElement('text', 'limit', get_string('limitno', constants::M_COMPONENT));
        // $repeatarray[] = $this->_form->createElement('hidden', $name . 'id', 0);
        /*
        if ($this->_instance){
            $repeatno = $DB->count_records('choice_options', array('choiceid'=>$this->_instance));
            $repeatno += $additionalfields;
        }
        */

        $repeateloptions = [];
        $repeateloptions[$name]['default'] = '';
        // $repeateloptions[$name]['disabledif'] = array('limitanswers', 'eq', 0);
        // $repeateloptions[$name]['rule'] = 'numeric';
        $repeateloptions[$name]['type'] = PARAM_TEXT;

        $repeateloptions[$name]['helpbutton'] = [$name . '_help', constants::M_COMPONENT];
        $this->_form->setType($name, PARAM_CLEANHTML);

        // $this->_form->setType($name .'id', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatno,
                $repeateloptions, $name .'_repeats', $name . '_add_fields',
                $additionalfields, "add", true);
    }

    final protected function add_showtextpromptoptions($name, $label, $default=constants::TEXTPROMPT_DOTS) {
        $options = utils::fetch_options_textprompt();
        return $this->add_dropdown($name, $label, $options, $default);
    }
    final protected function add_showignorepuncoptions($name, $label, $default=constants::TEXTPROMPT_DOTS) {
        $options = utils::fetch_options_yesno();
        return $this->add_dropdown($name, $label, $options, $default);
    }

    final protected function add_showlistorreadoptions($name, $label, $default=constants::LISTENORREAD_READ) {
        $options = utils::fetch_options_listenorread();
        return $this->add_dropdown($name, $label, $options, $default);
    }

    final protected function add_dropdown($name, $label, $options, $default=false) {

        $this->_form->addElement('select', $name, $label, $options);
        if($default !== false) {
            $this->_form->setDefault($name, $default);
        }

    }

    protected function add_media_prompts() {
        global $CFG, $OUTPUT;
        $m35 = true;

        // cut down on the code by using media item types array to pre-prepare fieldsets and media prompt selector
        $mediaprompts = ['addmedia', 'addttsaudio', 'addtextarea'];
        $keyfields = ['addmedia' => constants::MEDIAQUESTION,
            'addttsaudio' => constants::TTSQUESTION,
            'addtextarea' => constants::QUESTIONTEXTAREA];
        $fulloptions = [];
        $fieldsettops = [];
        $fieldsetbottom = "</fieldset>";
        foreach($mediaprompts as $mediaprompt){
            // dropdown options for media prompt selector
            $fulloptions[$mediaprompt] = get_string($mediaprompt, constants::M_COMPONENT);
            // fieldset
            $panelopts["mediatype"] = $mediaprompt;
            $panelopts["legend"] = get_string($mediaprompt, constants::M_COMPONENT);
            $panelopts["keyfield"] = $keyfields[$mediaprompt];
            $panelopts["instructions"] = get_string($mediaprompt . '_instructions', constants::M_COMPONENT);
            $fieldsettops[$mediaprompt] = $OUTPUT->render_from_template('mod_readaloud/mediapromptfieldset', $panelopts);
        }

        // lets make life easy with short access to $this->_form
        $mform = $this->_form;

        // add media prompt selector
        $useoptions = [0 => get_string('choosemediaprompt', constants::M_COMPONENT)] + $fulloptions;
        $mform->addElement('select', 'mediaprompts', get_string('mediaprompts', constants::M_COMPONENT), $useoptions);

        // Question media upload
        $mform->addElement('html', $fieldsettops['addmedia'], []);
        $this->add_media_upload(constants::MEDIAQUESTION, get_string('itemmedia', constants::M_COMPONENT));
        $mform->addElement('html', $fieldsetbottom, []);

        // Question text to speech
        $mform->addElement('html', $fieldsettops['addttsaudio'], []);
        $mform->addElement('textarea', constants::TTSQUESTION, get_string('itemttsquestion', constants::M_COMPONENT), ['wrap' => 'virtual', 'style' => 'width: 100%;']);
        $mform->setType(constants::TTSQUESTION, PARAM_RAW);
        $this->add_voiceselect(constants::TTSQUESTIONVOICE, get_string('itemttsquestionvoice', constants::M_COMPONENT));
        $this->add_voiceoptions(constants::TTSQUESTIONOPTION, get_string('choosevoiceoption', constants::M_COMPONENT));
        $mform->addElement('advcheckbox', constants::TTSAUTOPLAY, get_string('autoplay', constants::M_COMPONENT), '');
        $mform->addElement('html', $fieldsetbottom, []);

        // Question itemtextarea
        $mform->addElement('html', $fieldsettops['addtextarea'], []);
        $someid = \html_writer::random_id();
        $edoptions = constants::ITEMTEXTAREA_EDOPTIONS;
        // a bug prevents hideif working, but putting it in a group works dandy
        $groupelements = [];
        $groupelements[] = &$mform->createElement('editor', constants::QUESTIONTEXTAREA . '_editor',
                get_string('itemtextarea', constants::M_COMPONENT),
                ['id' => $someid, 'wrap' => 'virtual', 'style' => 'width: 100%;', 'rows' => '5'],
                $edoptions);
        $this->_form->setDefault(constants::QUESTIONTEXTAREA . '_editor', ['text' => '', 'format' => FORMAT_HTML]);
        $mform->setType(constants::QUESTIONTEXTAREA, PARAM_RAW);
        $mform->addGroup($groupelements, 'groupelements', get_string('itemtextarea', constants::M_COMPONENT), [' '], false);
        $mform->addElement('html', $fieldsetbottom, []);

    }

    final protected function add_media_upload($name, $label, $required = false) {

        $this->_form->addElement('filemanager',
                           $name,
                           $label,
                           null,
                           $this->filemanageroptions
                           );

    }

    final protected function add_media_prompt_upload($label = null, $required = false) {
        return $this->add_media_upload(constants::AUDIOPROMPT, $label, $required);
    }


    /**
     * Convenience function: Adds an response editor
     *
     * @param int $count The count of the element to add
     * @param string $label, null means default
     * @param bool $required
     * @return void
     */
    final protected function add_editorarearesponse($count, $label = null, $required = false) {
        if ($label === null) {
            $label = get_string('response', constants::M_COMPONENT);
        }
        // edoptions = array('noclean'=>true)
        $this->_form->addElement('editor', constants::TEXTANSWER .$count. '_editor', $label, ['rows' => '4', 'columns' => '80'], $this->editoroptions);
        $this->_form->setDefault(constants::TEXTANSWER .$count. '_editor', ['text' => '', 'format' => FORMAT_MOODLE]);
        if ($required) {
            $this->_form->addRule(constants::TEXTANSWER .$count. '_editor', get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * Convenience function: Adds a text area response
     *
     * @param $name_or_count The name or count of the element to add
     * @param string $label, null means default
     * @param bool $required
     * @return void
     */
    final protected function add_textarearesponse($nameorcount, $label = null, $required = false) {
        if ($label === null) {
            $label = get_string('response', constants::M_COMPONENT);
        }

        // Set the form element name
        if(is_number($nameorcount) || empty($nameorcount)){
            $element = constants::TEXTANSWER . $nameorcount;
        }else{
            $element = $nameorcount;
        }

        $this->_form->addElement('textarea', $element , $label, ['rows' => '4', 'columns' => '140', 'style' => 'width: 600px']);
        if ($required) {
            $this->_form->addRule($element, get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * Convenience function: Adds a textbox
     *
     * @param int  $name_or_count The name or count of the element to add
     * @param string $label, null means default
     * @param bool $required
     * @return void
     */
    final protected function add_textboxresponse($nameorcount, $label = null, $required = false) {
        if ($label === null) {
            $label = get_string('response', constants::M_COMPONENT);
        }

        // Set the form element name
        if(is_number($nameorcount) || empty($nameorcount)){
            $element = constants::TEXTANSWER . $nameorcount;
        }else{
            $element = $nameorcount;
        }

        $this->_form->addElement('text', $element, $label, ['size' => '60']);
        $this->_form->setType($element, PARAM_TEXT);
        if ($required) {
            $this->_form->addRule($element, get_string('required'), 'required', null, 'client');
        }
    }

       /**
        * Convenience function: Adds a number only textbox
        *
        * @param int $name_or_count The name or count of the element to add
        * @param string $label, null means default
        * @param bool $required
        * @return void
        */
    final protected function add_numericboxresponse($nameorcount, $label = null, $required = false) {
        if ($label === null) {
            $label = get_string('response', constants::M_COMPONENT);
        }

        // Set the form element name
        if(is_number($nameorcount) || empty($nameorcount)){
            $element = constants::CUSTOMINT . $nameorcount;
        }else{
            $element = $nameorcount;
        }

        $this->_form->addElement('text', $element, $label, ['size' => '8']);
        $this->_form->setType( $element, PARAM_INT);
        $this->_form->setDefault( $element, 0);
        $this->_form->addRule( $element, get_string('numberonly', constants::M_COMPONENT), 'numeric', null, 'client');
        if ($required) {
            $this->_form->addRule( $element, get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * Convenience function: Adds layout hint. Width of a single answer
     *
     * @param string $label, null means default
     * @return void
     */
    final protected function add_correctanswer($label = null) {
        if ($label === null) {
            $label = get_string('correctanswer', constants::M_COMPONENT);
        }
        $options = [];
        $options['1'] = 1;
        $options['2'] = 2;
        $options['3'] = 3;
        $options['4'] = 4;
        $this->_form->addElement('select', constants::CORRECTANSWER, $label, $options);
        $this->_form->setDefault(constants::CORRECTANSWER, 1);
        $this->_form->setType(constants::CORRECTANSWER, PARAM_INT);
    }

    /**
     * Convenience function: Adds a dropdown list of voices
     *
     * @param string $label, null means default
     * @return void
     */
    final protected function add_layoutoptions() {
        $layoutoptions = [constants::LAYOUT_AUTO => get_string('layoutauto', constants::M_COMPONENT),
            constants::LAYOUT_HORIZONTAL => get_string('layouthorizontal', constants::M_COMPONENT),
            constants::LAYOUT_VERTICAL => get_string('layoutvertical', constants::M_COMPONENT),
            constants::LAYOUT_MAGAZINE => get_string('layoutmagazine', constants::M_COMPONENT)];
        $name = constants::LAYOUT;
        $this->add_dropdown($name, get_string('chooselayout', constants::M_COMPONENT), $layoutoptions, constants::LAYOUT_AUTO);
    }

    /**
     * Convenience function: Adds a dropdown list of voices
     *
     * @param string $label, null means default
     * @return void
     */
    final protected function add_voiceselect($name, $label = null, $hideiffield=false, $hideifvalue=false) {
        global $CFG;
        $showall = true;
        $allvoiceoptions = utils::get_tts_voices_bylang($this->moduleinstance->ttslanguage, $showall);
        $defaultvoice = array_pop($allvoiceoptions );
        $this->add_dropdown($name, $label, $allvoiceoptions, $defaultvoice);
        if($hideiffield !== false) {
            $m35 = $CFG->version >= 2018051700;
            if ($m35) {
                $this->_form->hideIf($name, $hideiffield, 'eq', $hideifvalue);
            } else {
                $this->_form->disabledIf($name, $hideiffield, 'eq', $hideifvalue);
            }
        }
    }

    /**
     * Convenience function: Adds a dropdown list of voice options
     *
     * @param string $label, null means default
     * @return void
     */
    final protected function add_voiceoptions($name, $label = null,  $hideiffield=false, $hideifvalue=false, $nossml=false) {
        global $CFG;
        $voiceoptions = utils::get_ttsspeed_options();
        $this->add_dropdown($name, $label, $voiceoptions);
        $m35 = $CFG->version >= 2018051700;
        if($hideiffield !== false) {
            $m35 = $CFG->version >= 2018051700;
            if ($m35) {
                $this->_form->hideIf($name, $hideiffield, 'eq', $hideifvalue);
            } else {
                $this->_form->disabledIf($name, $hideiffield, 'eq', $hideifvalue);
            }
        }
    }

    final protected function add_relevanceoptions($name, $label, $default=false) {
        global $CFG;
        $relevanceoptions = utils::get_relevance_options();
        $this->add_dropdown($name, $label, $relevanceoptions, $default);
    }

    /**
     * Convenience function: Adds a yesno dropdown
     *
     * @param string $label, null means default
     * @return void
     */
    final protected function add_confirmchoice($name, $label = null) {
        global $CFG;
        if(empty($label)){$label = get_string('confirmchoice_formlabel', constants::M_COMPONENT);
        }
        $this->_form->addElement('selectyesno', $name, $label);
        $this->_form->setDefault( $name, 0);
    }

    /**
     * Convenience function: Adds a dropdown list of tts language
     *
     * @param string $label, null means default
     * @return void
     */
    final protected function add_languageselect($name, $label = null, $default = false) {
        $langoptions = utils::get_lang_options();
        $this->add_dropdown($name, $label, $langoptions, $default);
    }

    /**
     * A function that gets called upon init of this object by the calling script.
     *
     * This can be used to process an immediate action if required. Currently it
     * is only used in special cases by non-standard item types.
     *
     * @return bool
     */
    public function construction_override($itemid,  $readaloud) {
        return true;
    }

    /**
     * Time limit element
     *
     * @param string $name
     * @param string $label
     * @param bool|int $default
     * @return void
     */
    final protected function add_timelimit($name, $label, $default=false) {
        $this->_form->addElement('duration', $name, $label, ['optional' => true, 'defaultunit' => 1]);
        if ($default !== false) {
            $this->_form->setDefault($name, $default);
        }
    }

    /**
     * Allow retry element
     *
     * @param string $name
     * @param string $label
     * @param bool|int $default
     * @return void
     */
    final protected function add_allowretry($name, $detailslabel = null,  $default=0) {
        $this->_form->addElement('advcheckbox', $name,
            get_string('allowretry', constants::M_COMPONENT),
            $detailslabel, [], [0, 1]);
        if ($default !== 0) {
            $this->_form->setDefault($name, 1);
        }
    }

    /**
     * Hide start page element.
     *
     * @param string $name
     * @param string $label
     * @param bool|int $default
     * @return void
     */
    final protected function add_hidestartpage($name, $detailslabel = null,  $default=0) {
        $this->_form->addElement('advcheckbox', $name,
            get_string('hidestartpage', constants::M_COMPONENT),
            $detailslabel, [], [0, 1]);
        if ($default !== 0) {
            $this->_form->setDefault($name, 1);
        }
    }

    final protected function add_aliencount($name, $label, $default) {
        $alienoptions = [
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
        ];
        $this->add_dropdown($name, $label, $alienoptions, $default);
    }
}
