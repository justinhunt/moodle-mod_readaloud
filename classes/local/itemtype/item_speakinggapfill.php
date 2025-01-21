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

namespace mod_readaloud\local\itemtype;

use mod_readaloud\constants;
use mod_readaloud\utils;
use templatable;
use renderable;

/**
 * Renderable class for a speakinggapfill item in a readaloud activity.
 *
 * @package    mod_readaloud
 * @copyright  2023 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_speakinggapfill extends item {

    //the item type
    public const ITEMTYPE = constants::TYPE_SGAPFILL;


    /**
     * The class constructor.
     *
     */
    public function __construct($itemrecord, $moduleinstance=false, $context=false){
        parent::__construct($itemrecord, $moduleinstance, $context);
        $this->needs_speechrec=true;
    }

    /**
     * Export the data for the mustache template.
     *
     * @param \renderer_base $output renderer to be used to render the action bar elements.
     * @return array
     */
    public function export_for_template(\renderer_base $output) {

        $testitem = new \stdClass();
        $testitem = $this->get_common_elements($testitem);
        $testitem = $this->get_text_answer_elements($testitem);
        $testitem = $this->get_polly_options($testitem);
        $testitem = $this->set_layout($testitem);

        // Sentences
        $sentences = [];
        if (isset($testitem->customtext1)) {
            $sentences = explode(PHP_EOL, $testitem->customtext1);
        }

        $testitem->sentences = $this->process_speakinggapfill_sentences($sentences);
        $testitem->readsentence = $this->itemrecord->{constants::READSENTENCE} == 1;
        $testitem->allowretry = $this->itemrecord->{constants::GAPFILLALLOWRETRY} == 1;
        $testitem->hidestartpage = $this->itemrecord->{constants::GAPFILLHIDESTARTPAGE} == 1;

        // Cloudpoodll
        $testitem = $this->set_cloudpoodll_details($testitem);

        return $testitem;
    }

    public static function validate_import($newrecord,$cm){
        $error = new \stdClass();
        $error->col = '';
        $error->message = '';

        if($newrecord->customtext1 == ''){
            $error->col = 'customtext1';
            $error->message = get_string('error:emptyfield', constants::M_COMPONENT);
            return $error;
        }

        //return false to indicate no error
        return false;
    }
        /*
    * This is for use with importing, telling import class each column's is, db col name, readaloud specific data type
    */
    public static function get_keycolumns()
    {
        //get the basic key columns and customize a little for instances of this item type
        $keycols = parent::get_keycolumns();
        $keycols['int4'] = ['jsonname' => 'promptvoiceopt', 'type' => 'voiceopts', 'optional' => true, 'default' => null, 'dbname' => constants::POLLYOPTION];
        $keycols['text5'] = ['jsonname' => 'promptvoice', 'type' => 'voice', 'optional' => true, 'default' => null, 'dbname' => constants::POLLYVOICE];
        $keycols['int3'] = ['jsonname' => 'allowretry', 'type' => 'boolean', 'optional' => true, 'default' => 0, 'dbname' => constants::GAPFILLALLOWRETRY];
        $keycols['int2'] = ['jsonname' => 'dictationstyle', 'type' => 'boolean', 'optional' => true, 'default' => 0, 'dbname' => constants::READSENTENCE];
        $keycols['text1'] = ['jsonname' => 'sentences', 'type' => 'stringarray', 'optional' => true, 'default' => [], 'dbname' => 'customtext1'];
        $keycols['int5'] = ['jsonname' => 'hidestartpage', 'type' => 'boolean', 'optional' => true, 'default' => 0, 'dbname' => constants::GAPFILLHIDESTARTPAGE];
        return $keycols;
    }

    public function update_create_langmodel($olditemrecord) {
        // If we need to generate a DeepSpeech model for this, then lets do that now.
        // We want to process the hashcode and lang model if it makes sense.

        $passage = '';

        // Sentences
        $sentences = [];
        if(isset($this->itemrecord->customtext1)) {
            $sentences = explode(PHP_EOL, $this->itemrecord->customtext1);
        }
        $sentencedata = $this->parse_gapfill_sentences($sentences);
        foreach ($sentencedata as $sentence) {
            $passage .= $sentence->prompt . ' ';
        }

        if (utils::needs_lang_model($this->moduleinstance, $passage)) {
            $newpassagehash = utils::fetch_passagehash($this->language, $passage);
            if ($newpassagehash) {
                //check if it has changed, if its a brand new one, if so register a langmodel
                if (!$olditemrecord || $olditemrecord->passagehash != ($this->region . '|' . $newpassagehash)) {

                    //build a lang model
                    $ret = utils::fetch_lang_model($passage, $this->language, $this->region);

                    //for doing a dry run
                    //$ret=new \stdClass();
                    //$ret->success=true;

                    if ($ret && isset($ret->success) && $ret->success) {
                        $this->itemrecord->passagehash = $this->region . '|' . $newpassagehash;
                        return true;
                    }
                }
            }
            //if we get here just set the new passage hash to the existing one
            $this->itemrecord->passagehash =$olditemrecord->passagehash;
        }else{
            //I think this will never get here
            $this->itemrecord->passagehash ='';
        }
        return false;
    }

}
