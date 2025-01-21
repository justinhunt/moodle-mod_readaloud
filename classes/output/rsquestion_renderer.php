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

namespace mod_readaloud\output;


defined('MOODLE_INTERNAL') || die();

use mod_readaloud\constants;
use mod_readaloud\utils;
use mod_readaloud\quizhelper;

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_readaloud
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rsquestion_renderer extends \plugin_renderer_base {


    /**
     * Return HTML to display add first page links
     * @param \context $context
     * @param int $tableid
     * @return string
     */
    public function add_edit_page_links($context, $tableid) {
        global $CFG;
        $itemid = 0;
        $config = get_config(constants::M_COMPONENT);

        $output = $this->output->heading(get_string("whatdonow", "readaloud"), 3);
        $links = [];

        $qtypes = [constants::TYPE_PAGE, constants::TYPE_MULTICHOICE];
        $qtypes[] = constants::TYPE_MULTIAUDIO;
        $qtypes[] = constants::TYPE_SHORTANSWER;
        $qtypes[] = constants::TYPE_LGAPFILL;
        $qtypes[] = constants::TYPE_TGAPFILL;
        $qtypes[] = constants::TYPE_SGAPFILL;
        $qtypes[] = constants::TYPE_FREESPEAKING;
        $qtypes[] = constants::TYPE_FREEWRITING;

        // If modaleditform is true adding and editing item types is done in a popup modal. Thats good ...
        // but when there is a lot to be edited , a standalone page is better. The modaleditform flag is acted on on additemlink template and rsquestionmanager js
        $modaleditform = false; //$config->modaleditform == "1";
        foreach($qtypes as $qtype){
            $data = ['wwwroot' => $CFG->wwwroot, 'type' => $qtype, 'itemid' => $itemid, 'cmid' => $this->page->cm->id,
              'label' => get_string('add' . $qtype . 'item', constants::M_COMPONENT), 'modaleditform' => $modaleditform];
            $links[] = $this->render_from_template('mod_readaloud/additemlink', $data);
        }

        $props = ['contextid' => $context->id, 'tableid' => $tableid, 'modaleditform' => $modaleditform, 'wwwroot' => $CFG->wwwroot, 'cmid' => $this->page->cm->id];
        $this->page->requires->js_call_amd(constants::M_COMPONENT . '/rsquestionmanager', 'init', [$props]);

        return $this->output->box($output.implode("", $links), 'generalbox firstpageoptions mod_readaloud_link_box_container');

    }


    function setup_datatables($tableid) {
        global $USER;

        $tableprops = [];
        $columns = [];
        // for cols .. .'itemname', 'itemtype', 'itemtags','timemodified', 'action'
        $columns[0] = ['orderable' => false];
        $columns[1] = ['orderable' => false];
        $columns[2] = ['orderable' => false];
        $columns[3] = ['orderable' => false];
        $columns[4] = ['orderable' => false];
        $columns[5] = ['orderable' => false];
        $tableprops['columns'] = $columns;
        $tableprops['dom'] = 'lBfrtip';

        // default ordering
        $order = [];
        $order[0] = [1, "asc"];
        $tableprops['order'] = $order;

        // here we set up any info we need to pass into javascript
        $opts = [];
        $opts['tableid'] = $tableid;
        $opts['tableprops'] = $tableprops;
        $this->page->requires->js_call_amd(constants::M_COMPONENT . "/datatables", 'init', [$opts]);
        $this->page->requires->css( new \moodle_url('https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css'));
        $this->page->requires->css( new \moodle_url('https://cdn.datatables.net/buttons/3.2.0/css/buttons.dataTables.min.css'));
        $this->page->requires->strings_for_js(['bulkdelete', 'bulkdeletequestion'], constants::M_COMPONENT);
    }


    function show_noitems_message($itemsvisible) {
        $message = $this->output->heading(get_string('noitems', constants::M_COMPONENT), 3, 'main');
        $displayvalue = $itemsvisible ? 'none' : 'block';
        $ret = \html_writer::div($message , constants::M_NOITEMS_CONT, ['id' => constants::M_NOITEMS_CONT, 'style' => 'display: '.$displayvalue]);
        return $ret;
    }

    /**
     * Return the html table of items
     * @param array homework objects
     * @param integer $courseid
     * @return string html of table
     */
    public function show_items_list($items, $readaloud, $cm, $visible) {

        // new code
        $data = [];
        $data['tableid'] = constants::M_ITEMS_TABLE;
        $data['display'] = $visible ? 'block' : 'none';
        $itemsarray = [];
        foreach (array_values($items) as $i => $item) {
            $arrayitem = (Array)$item;
            $arrayitem['index'] = ($i + 1);
            //due to odd  data in the field from legacy times we need to check for empty or oddstrings
            $arrayitem['typelabel'] = empty($arrayitem['type']) || strlen($arrayitem['type']) < 4 ? 'unknown' : get_string($arrayitem['type'], constants::M_COMPONENT);
            $itemsarray[] = $arrayitem;
        }
        $data['items'] = $itemsarray;

        $uppix = new \pix_icon('t/up', get_string('up'));
        $downpix = new \pix_icon('t/down', get_string('down'));
        $data['up'] = $uppix->export_for_pix();
        $data['down'] = $downpix->export_for_pix();

        return $this->render_from_template('mod_readaloud/itemlist', $data);

    }

        /**
     *  Show quiz container
     */
    public function show_quiz($quizhelper, $moduleinstance) {

        // quiz data
        $quizdata = $quizhelper->fetch_test_data_for_js();
        $itemshtml = [];
        foreach($quizdata as $item){
            $itemshtml[] = $this->render_from_template(constants::M_COMPONENT . '/' . $item->type, $item);
        }

        $finisheddiv = \html_writer::div("" , constants::M_QUIZ_FINISHED,
            ['id' => constants::M_QUIZ_FINISHED]);

        $placeholderdiv = \html_writer::div('', constants::M_QUIZ_PLACEHOLDER . ' ' . constants::M_QUIZ_SKELETONBOX,
            ['id' => constants::M_QUIZ_PLACEHOLDER]);

        $quizclass = constants::M_QUIZ_CONTAINER . ' ' . $moduleinstance->csskey . ' '. constants::M_COMPONENT . '_' . $moduleinstance->containerwidth;
        $quizattributes = ['id' => constants::M_QUIZ_CONTAINER];
        if(!empty($moduleinstance->lessonfont)){
            $quizattributes['style'] = "font-family: '$moduleinstance->lessonfont', serif;";
        }
        $quizdiv = \html_writer::div($finisheddiv.implode('', $itemshtml) , $quizclass, $quizattributes);

        $ret = $placeholderdiv  . $quizdiv;
        return $ret;
    }


    function fetch_quiz_amd($cm, $moduleinstance, $previewquestionid=0, $canreattempt=false, $embed=0) {
        global $CFG, $USER;
        // any html we want to return to be sent to the page
        $rethtml = '';

        // here we set up any info we need to pass into javascript

        $recopts = [];
        // recorder html ids
        $recopts['recorderid'] = constants::M_RECORDERID;
        $recopts['recordingcontainer'] = constants::M_RECORDING_CONTAINER;
        $recopts['recordercontainer'] = constants::M_RECORDER_CONTAINER;

        // activity html ids
        $recopts['passagecontainer'] = constants::M_PASSAGE_CONTAINER;
        $recopts['instructionscontainer'] = constants::M_INSTRUCTIONS_CONTAINER;
        $recopts['recordbuttoncontainer'] = constants::M_RECORD_BUTTON_CONTAINER;
        $recopts['startbuttoncontainer'] = constants::M_START_BUTTON_CONTAINER;
        $recopts['hider'] = constants::M_HIDER;
        $recopts['progresscontainer'] = constants::M_PROGRESS_CONTAINER;
        $recopts['feedbackcontainer'] = constants::M_FEEDBACK_CONTAINER;
        $recopts['wheretonextcontainer'] = constants::M_WHERETONEXT_CONTAINER;
        $recopts['quizcontainer'] = constants::M_QUIZ_CONTAINER;
        $recopts['errorcontainer'] = constants::M_ERROR_CONTAINER;

        // first confirm we are authorised before we try to get the token
        $config = get_config(constants::M_COMPONENT);
        if (empty($config->apiuser) || empty($config->apisecret)) {
            $errormessage = get_string('nocredentials', constants::M_COMPONENT,
                    $CFG->wwwroot . constants::M_PLUGINSETTINGS);
            return $this->show_problembox($errormessage);
        } else {
            // fetch token
            $token = utils::fetch_token($config->apiuser, $config->apisecret);

            // check token authenticated and no errors in it
            $errormessage = utils::fetch_token_error($token);
            if (!empty($errormessage)) {
                return $this->show_problembox($errormessage);
            }
        }
        $recopts['token'] = $token;
        $recopts['owner'] = hash('md5', $USER->username);
        $recopts['region'] = $moduleinstance->region;
        $recopts['ttslanguage'] = $moduleinstance->ttslanguage;
        $recopts['stt_guided'] = $moduleinstance->transcriber == constants::TRANSCRIBER_GUIDED;

        $recopts['courseurl'] = $CFG->wwwroot . '/course/view.php?id=' .
            $moduleinstance->course  . '#section-'. ($cm->section - 1);

        $recopts['useanimatecss'] = true; //$config->animations == constants::M_ANIM_FANCY;

        // to show a post item results panel
        $recopts['showitemreview'] = $moduleinstance->showitemreview ? true : false;

        // the activity URL for returning to on finished
        $activityurl = new \moodle_url(constants::M_URL . '/view.php',
            ['n' => $moduleinstance->id]);

        // add embedding url param if we are embedded
        if ($embed > 0) {
            $activityurl->param('embed', $embed);
        }
        // set the activity url
        $recopts['activityurl'] = $activityurl->out();

        // the reattempturl if its ok
        $recopts['reattempturl'] = "";
        if ($canreattempt) {
            $activityurl->param('retake', '1');
            $recopts['reattempturl'] = $activityurl->out();
        }

        // show back to course button if we are not in an iframe
        if ($config->enablesetuptab ||
            $moduleinstance->pagelayout == 'embedded' ||
            $moduleinstance->pagelayout == 'popup' ||
            $embed > 0) {
            $recopts['backtocourse'] = '';
        } else {
            $recopts['backtocourse'] = true;
        }

        // quiz data
        $quizhelper = new quizhelper($cm);
        $quizdata = $quizhelper->fetch_test_data_for_js($this);
        if ($previewquestionid) {
            foreach ($quizdata as $item) {
                if ($item->id == $previewquestionid) {
                    $item->preview = true;
                    $recopts['quizdata'] = [$item];
                    break;
                }
            }
        } else {
            $recopts['quizdata'] = $quizdata;
        }

        // this inits the M.mod_minilesson thingy, after the page has loaded.
        // we put the opts in html on the page because moodle/AMD doesn't like lots of opts in js
        // convert opts to json
        $jsonstring = json_encode($recopts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        if (($jsonstring) === false) {
            $err = json_last_error();
        }
        $widgetid = constants::M_RECORDERID . '_opts_9999';
        $optshtml = \html_writer::tag('input', '', ['id' => 'amdopts_' . $widgetid, 'type' => 'hidden', 'value' => $jsonstring]);

        // the recorder div
        $rethtml = $rethtml . $optshtml;

        $opts = ['cmid' => $cm->id, 'widgetid' => $widgetid];
        $this->page->requires->js_call_amd("mod_readaloud/quizcontroller", 'init', [$opts]);

        // these need to be returned and echo'ed to the page
        return $rethtml;
    }

}
