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
        $modaleditform = false; // $config->modaleditform == "1";
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
            // due to odd  data in the field from legacy times we need to check for empty or oddstrings
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

        // Determine container width based on passage presence or not.
        switch($moduleinstance->showquiz){
            case constants::M_SHOWQUIZ_NOPASSAGE:
                $containerwidth = 'compact';
                break;
            case constants::M_SHOWQUIZ_PASSAGE:
            default:
                $containerwidth = 'wide';
        }
        $quizclass = constants::M_QUIZ_CONTAINER . ' ' . $moduleinstance->csskey . ' '. constants::M_COMPONENT . '_' . $containerwidth;
        $quizattributes = ['id' => constants::M_QUIZ_CONTAINER];
        if(!empty($moduleinstance->lessonfont)){
            $quizattributes['style'] = "font-family: '$moduleinstance->lessonfont', serif;";
        }
        $quizdiv = \html_writer::div($finisheddiv.implode('', $itemshtml) , $quizclass, $quizattributes);

        $ret = $placeholderdiv  . $quizdiv;
        return $ret;
    }

    public function show_quiz_preview($quizhelper, $qid) {

        // quiz data
        $quizdata = $quizhelper->fetch_test_data_for_js();
        $itemshtml = [];
        foreach($quizdata as $item) {
            if ($item->id == $qid) {
                $itemshtml[] = $this->render_from_template(constants::M_COMPONENT . '/' . $item->type, $item);
            }
        }

        $quizdiv = \html_writer::div(implode('', $itemshtml) , constants::M_QUIZ_CONTAINER,
                ['id' => constants::M_QUIZ_CONTAINER]);

        $ret = $quizdiv;
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

        $recopts['useanimatecss'] = true; // $config->animations == constants::M_ANIM_FANCY;

        // to show a post item results panel
        $recopts['showqreview'] = $moduleinstance->showqreview ? true : false;

        // the activity URL for returning to on finished
        $activityurl = new \moodle_url(constants::M_URL . '/quiz.php',
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

        // this inits the M.mod_readaloud thingy, after the page has loaded.
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


    /**
     *  Finished View
     */
    public function show_finished_results($quizhelper, $latestattempt, $cm, $canattempt, $embed) {
        global $CFG, $DB;
        $ans = [];
        // quiz data
        $quizdata = $quizhelper->fetch_test_data_for_js();

        // config
        $config = get_config(constants::M_COMPONENT);
        $course = $DB->get_record('course', ['id' => $latestattempt->courseid]);
        $moduleinstance = $DB->get_record(constants::M_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);

        // Steps data.
        $steps = json_decode($latestattempt->qdetails)->steps;

        // prepare results for display
        if (!is_array($steps)) {
            $steps = utils::remake_quizsteps_as_array($steps);
        }
        $results = array_filter($steps, function($step){return $step->hasgrade;
        });
        $useresults = [];
        foreach ($results as $result) {

            $items = $DB->get_record(constants::M_QTABLE, ['id' => $quizdata[$result->index]->id]);
            $result->title = $items->name;

            // Question Text
            $itemtext = file_rewrite_pluginfile_urls($items->{constants::TEXTQUESTION},
                'pluginfile.php', $context->id, constants::M_COMPONENT,
                constants::TEXTQUESTION_FILEAREA, $items->id);
            $itemtext = format_text($itemtext, FORMAT_MOODLE, ['context' => $context]);
            $result->questext = $itemtext;
            $result->itemtype = $quizdata[$result->index]->type;
            $result->resultstemplate = $result->itemtype .'results';

            // Correct answer.
            switch($result->itemtype){

                case constants::TYPE_SHORTANSWER:
                case constants::TYPE_LGAPFILL:
                case constants::TYPE_TGAPFILL:
                case constants::TYPE_SGAPFILL:
                    $result->hascorrectanswer = true;
                    $result->correctans = $quizdata[$result->index]->sentences;
                    $result->hasanswerdetails = false;
                    break;

                case constants::TYPE_MULTIAUDIO:
                case constants::TYPE_MULTICHOICE:
                    $result->hascorrectanswer = true;
                    $result->hasincorrectanswer = true;
                    $result->hasanswerdetails = false;
                    $correctanswers = [];
                    $incorrectanswers = [];
                    $correctindex = $quizdata[$result->index]->correctanswer;
                    for ($i = 1; $i < 5; $i++) {
                        if (!isset($quizdata[$result->index]->{"customtext" . $i})) {continue;}
                        if ($i == $correctindex) {
                            $correctanswers[] = ['sentence' => $quizdata[$result->index]->{"customtext" . $i}];
                        } else {
                            $incorrectanswers[] = ['sentence' => $quizdata[$result->index]->{"customtext" . $i}];
                        }
                    }
                    $result->correctans = $correctanswers;
                    $result->incorrectans = $incorrectanswers;
                    break;

                case constants::TYPE_FREEWRITING:
                case constants::TYPE_FREESPEAKING:
                    $result->hascorrectanswer = false;
                    $result->hasincorrectanswer = false;
                    if (isset($result->resultsdata)) {
                        $result->hasanswerdetails = true;
                        // the free writing and reading both need to be told to show no reattempt button
                        $result->resultsdata->noreattempt = true;
                        $result->resultsdatajson = json_encode($result->resultsdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } else {
                        $result->hasanswerdetails = false;
                    }
                    break;

                default:
                    $result->hascorrectanswer = false;
                    $result->hasincorrectanswer = false;
                    $result->hasanswerdetails = false;
                    $result->correctans = [];
                    $result->incorrectans = [];
            }

            $result->index++;
            // Every item stars.
            if ($result->grade == 0) {
                $ystarcnt = 0;
            } else if ($result->grade < 19) {
                $ystarcnt = 1;
            } else if ($result->grade < 39) {
                $ystarcnt = 2;
            } else if ($result->grade < 59) {
                $ystarcnt = 3;
            } else if ($result->grade < 79) {
                $ystarcnt = 4;
            } else {
                $ystarcnt = 5;
            }
            $result->yellowstars = array_fill(0, $ystarcnt, true);
            $gstarcnt = 5 - $ystarcnt;
            $result->graystars = array_fill(0, $gstarcnt, true);

            $useresults[] = $result;
        }

        // output results and back to course button
        $tdata = new \stdClass();

        // Course name at top of page.
        $tdata->coursename = $course->fullname;
        // Item stars.
        if ($latestattempt->qscore == 0) {
            $ystarcnt = 0;
        } else if ($latestattempt->qscore < 19) {
            $ystarcnt = 1;
        } else if ($latestattempt->qscore < 39) {
            $ystarcnt = 2;
        } else if ($latestattempt->qscore < 59) {
            $ystarcnt = 3;
        } else if ($latestattempt->qscore < 79) {
            $ystarcnt = 4;
        } else {
            $ystarcnt = 5;
        }
        $tdata->yellowstars = array_fill(0, $ystarcnt, true);
        $gstarcnt = 5 - $ystarcnt;
        $tdata->graystars = array_fill(0, $gstarcnt, true);

        $tdata->total = $latestattempt->qscore;
        $tdata->courseurl = $CFG->wwwroot . '/course/view.php?id=' .
            $latestattempt->courseid . '#section-'. ($cm->section - 1);

        // depending on finish screen settings
        switch($moduleinstance->qfinishscreen){
            case constants::FINISHSCREEN_FULL:
            case constants::FINISHSCREEN_CUSTOM:
                $tdata->results = $useresults;
                $tdata->showfullresults = true;
                break;

            case constants::FINISHSCREEN_SIMPLE:
            default:
                $tdata->results = [];
        }

        // Output reattempt button.
        if ($canattempt) {
            $reattempturl = new \moodle_url( constants::M_URL . '/quiz.php',
                    ['n' => $latestattempt->readaloudid, 'retake' => 1, 'embed' => $embed]);
            $tdata->reattempturl = $reattempturl->out();
        }
        // Show back to course button if we are not in a tab or embedded.
        if (!$config->enablesetuptab && $embed == 0 &&
            $moduleinstance->pagelayout !== 'embedded' &&
            $moduleinstance->pagelayout !== 'popup') {
            $tdata->backtocourse = true;
        }

        if ($moduleinstance->finishscreen == constants::FINISHSCREEN_CUSTOM) {
            // here we fetch the mustache engine, reset the loader to string loader
            // render the custom finish screen, and restore the original loader
            $mustache = $this->get_mustache();
            $oldloader = $mustache->getLoader();
            $mustache->setLoader(new \Mustache_Loader_StringLoader());
            $tpl = $mustache->loadTemplate($moduleinstance->finishscreencustom);
            $finishedcontents = $tpl->render($tdata);
            $mustache->setLoader($oldloader);
        } else {
            $finishedcontents = $this->render_from_template(constants::M_COMPONENT . '/quizfinished', $tdata);
        }

        // put it all in a div and return it
        $finisheddiv = \html_writer::div($finishedcontents , constants::M_QUIZ_FINISHED,
                ['id' => constants::M_QUIZ_FINISHED, 'style' => 'display: block']);

        return  $finisheddiv;
    }

}
