<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:31
 */

namespace mod_readaloud\local\itemform;

use mod_readaloud\constants;

class listeninggapfillform extends baseform {

    public $type = constants::TYPE_LGAPFILL;

    public function custom_definition() {
        $this->add_itemsettings_heading();
        $this->add_voiceselect(constants::POLLYVOICE, get_string('choosevoice', constants::M_COMPONENT));

        $nossml = true;
        $hideiffield = false;
        $hideifvalue = false;
        $this->add_voiceoptions(constants::POLLYOPTION, get_string('choosevoiceoption', constants::M_COMPONENT),
         $hideiffield, $hideifvalue, $nossml);

        $this->add_static_text('instructions', '', get_string('listeninggapfillitemsdesc', constants::M_COMPONENT));
        $this->add_textarearesponse(1, get_string('sentenceprompts', constants::M_COMPONENT), true);
        $this->add_timelimit(constants::TIMELIMIT, get_string(constants::TIMELIMIT, constants::M_COMPONENT));
        $this->add_allowretry(constants::GAPFILLALLOWRETRY, get_string('allowretry_desc', constants::M_COMPONENT));
        $this->add_hidestartpage(constants::GAPFILLHIDESTARTPAGE, get_string('hidestartpage_desc', constants::M_COMPONENT));
    }
}