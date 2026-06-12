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

/**
 * File containing the renderer class for the ReadAloud module.
 *
 * @package   mod_readaloud
 * @copyright 2025 Justin Hunt (poodllsupport@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_readaloud\output;

use context_module;
use mod_readaloud\constants;
use mod_readaloud\utils;
use mod_readaloud\quizhelper;
use ReflectionClass;
use cm_info;
use moodle_url;

/**
 * Renderer class for the ReadAloud module.
 *
 * Provides methods to render module content and custom UI elements.
 *
 * @package   mod_readaloud
 * @copyright 2025 Justin Hunt (poodllsupport@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Returns the header for the module
     *
     * @param mod $instance
     * @param string $currenttab current tab that is shown.
     * @param int $item id of the anything that needs to be displayed.
     * @param string $extrapagetitle String to append to the page title.
     * @return string
     */
    public function header($moduleinstance, $cm, $currenttab = '', $itemid = null, $extrapagetitle = null) {
        global $CFG;

        switch($this->page->pagelayout) {
            case 'popup':
                $embed = 2;
                break;
            case 'embedded':
                $embed = 1;
                break;
            default:
                $embed = 0;
        }

        $this->page->set_heading($this->page->course->fullname);
        $output = $this->output->header();

        $context = \context_module::instance($cm->id);
        // FIXME: Temp hide the tabs whilst building the new UI.
        if (has_capability('mod/readaloud:viewreports', $context) && $embed !== 2) {
            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot . '/mod/readaloud/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        }

        return $output;
    }

    /**
     * Render a line chart for attempt data (WPM, accuracy, optional grades).
     *
     * @param stdClass $chartdata  Chart series and labels
     * @param bool     $showgrades Include session scores if true
     * @return string Rendered chart HTML or empty string
     */
    public function fetch_rendered_attemptchart($chartdata, $showgrades) {
        global $CFG;
        // If no chart data or lower than Moodle 3.2 we do not show the chart.
        if (!$chartdata || $CFG->version < 2016120500) {
            return '';
        }

        $chart = new \core\chart_line();
        $chart->add_series($chartdata->wpmseries);
        $chart->add_series($chartdata->accuracyseries);
        if ($showgrades) {
            $chart->add_series($chartdata->sessionscoreseries);
        }
        $chart->set_labels($chartdata->labelsdata);
        $renderedchart = $this->output->render($chart);

        return $renderedchart;
    }

    /**
     * Returns the template data for the read report.
     *
     * @param object $moduleinstance
     * @param object|false $attempt
     * @param object|false $aigrade
     * @param int $embed
     * @return mixed
     */
    protected function get_readreport_data($moduleinstance, $modulecontext, $cm, $attempts = false, $attempt = false, $aigrade = false ) {

        // Template data for read report.
        $tdata = [];

        // Show grades and stats.
        $showstats  = $moduleinstance->humanpostattempt != constants::POSTATTEMPT_NONE;
        $showgrades = ($moduleinstance->targetwpm > 0 && $showstats &&
                    $moduleinstance->humanpostattempt != constants::POSTATTEMPT_EVALERRORSNOGRADE);
        // If this is in gradebook or not.
        $notingradebook = $attempt ? $attempt->dontgrade > 0 : false;

        // Attempt has been graded yet?
        $havehumaneval = $attempt ? ($attempt->sessiontime != null) : false;
        $haveaieval   = ($aigrade && $aigrade->has_transcripts());
        $graded        = $havehumaneval || $haveaieval;

        // Star rating.
        if ($attempt && $graded) {
            if ($showgrades) {
                $rating = utils::fetch_rating($attempt, $aigrade); // Rating: 0 - 10 (0 - 5 stars with half-star increments).
            } else {
                $rating = 10;
            }
            $ready = $rating > -1;
            $tdata['stars'] = utils::render_stars($rating);

            // Stats.
            $stats = utils::fetch_small_reportdata($attempt, $aigrade);
            $tdata['wpm']         = $stats->wpm;
            $tdata['acc']         = $stats->accuracy;
            $tdata['totalwords']  = $stats->sessionendword;
            $tdata['notingradebook'] = $notingradebook;
        } else {
            $stats = false;
            $ready = false;
        }

        if ($ready) {
            $tdata['ready'] = true;
        }

        // Audio filename.
        $tdata['src'] = '';
        if ($ready && $attempt && $attempt->filename) {
            // If the attempt is not ready, audio may not be available yet.
             $filename = $attempt->filename;
        } else {
            // If the attempt is not ready, audio may not be available yet.
            $filename = '';
        }
        $tdata['src'] = $filename;

        // Determine whether remote transcription is allowed.
        $remotetranscribe = utils::can_transcribe($moduleinstance);

        $tdata['fullreportbutton'] = constants::M_FULLREPORT;
        $tdata['readagainbutton'] = constants::M_READAGAIN;
        $tdata['showgrades'] = $showgrades;
        $tdata['showstats']  = $showstats;
        $tdata['remotetranscribe'] = $remotetranscribe;

        // Get Full Report and merge it into tdata.
        $tdata['fullreportcontainer']  = constants::M_FULLREPORT_CONTAINER;
        $fullreportdata = $this->get_full_student_report_data(
            $moduleinstance,
            $modulecontext,
            $attempts,
            $cm
        );
        $tdata = array_merge($tdata, $fullreportdata);

        // JavaScript to initiate read report.
        $opts = [
            'filename'         => $filename,
            'attemptid'        => $attempt ? $attempt->id : false,
            'cmid'             => $cm->id,
            'ready'            => $ready,
            'remotetranscribe' => $remotetranscribe,
            'showgrades'       => $showgrades,
            'showstats'        => $showstats,
            'notingradebook'   => $notingradebook,
            'sessionerrors'    => $stats ? $stats->sessionerrors : false,
            'sessionmatches'    => $stats ? $stats->sessionmatches : false,
            'sessionendword'    => $stats ? $stats->sessionendword : false,
        ];

        $tdata = array_merge($tdata, $opts);
        return $tdata;
    }

    /**
     *  Show grades admin heading
     */
    public function show_admintab_heading($showtitle, $showinstructions) {
        $thetitle = $this->output->heading($showtitle, 3, 'main');
        $displaytext = \html_writer::div($thetitle, constants::M_CLASS . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .= \html_writer::div($showinstructions, constants::M_CLASS . '_center');
        $displaytext .= $this->output->box_end();
        $ret = \html_writer::div($displaytext);

        return $ret;
    }

    /**
     * Render a "Machine Regrade All" button for the given module instance.
     *
     * @param stdClass $moduleinstance The module instance
     * @return string HTML for the button
     */
    public function show_machineregradeallbutton($moduleinstance) {
        $options = [];
        $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/admintab.php',
                ['n' => $moduleinstance->id, 'action' => 'machineregradeall']),
                get_string('machineregradeall', constants::M_COMPONENT), 'post', $options);

        $ret = \html_writer::div($button, constants::M_ADMINTAB_CONTAINER);

        return $ret;
    }

    /**
     * Render the "Push Corpus" details and button for the given module instance.
     *
     * @param stdClass $moduleinstance The module instance
     * @return string HTML for the details and button
     */
    public function show_pushcorpusdetails($moduleinstance) {

        $pushcorpusdetails = \html_writer::div(get_string('pushcorpus_details', constants::M_COMPONENT));
        $options = [];
        $pushcorpusbutton = $this->output->single_button(new \moodle_url(constants::M_URL . '/admintab.php',
            ['n' => $moduleinstance->id, 'action' => 'pushcorpus']),
            get_string('pushcorpus_button', constants::M_COMPONENT), 'post', $options);

        $ret = \html_writer::div($pushcorpusdetails . $pushcorpusbutton, constants::M_ADMINTAB_CONTAINER);

        return $ret;
    }

    /**
     * Render the "Push All to Gradebook" heading and button for the given module instance.
     *
     * @param stdClass $moduleinstance The module instance
     * @return string HTML for the heading and button
     */
    public function show_pushalltogradebook($moduleinstance) {

        $sectiontitle = get_string("pushalltogradebook", constants::M_COMPONENT);
        $heading = $this->output->heading($sectiontitle, 4);

        if (utils::can_transcribe($moduleinstance) &&
                ($moduleinstance->machgrademethod == constants::MACHINEGRADE_HYBRID ||
                $moduleinstance->machgrademethod == constants::MACHINEGRADE_MACHINEONLY)) {
            $options = [];
        } else {
            $options = ['disabled' => 'disabled'];
        }
        $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/admintab.php',
                ['n' => $moduleinstance->id, 'action' => 'pushalltogradebook']),
                get_string('pushalltogradebook', constants::M_COMPONENT), 'post', $options);

        $ret = \html_writer::div($heading . $button, constants::M_ADMINTAB_CONTAINER);

        return $ret;
    }

    /**
     * Render a table summarising all mistranscriptions.
     *
     * Each row shows the passage index, passage word, count of mistranscriptions,
     * and the list of mistranscribed words with counts.
     *
     * @param array $items Array of items containing mistranscription data
     * @return string HTML for the mistranscriptions table
     */
    public function show_all_mistranscriptions($items) {

        // Set up our table.
        $tableattributes = ['class' => 'generaltable ' . constants::M_CLASS . '_table'];

        $htmltable = new \html_table();
        $tableid = \html_writer::random_id(constants::M_COMPONENT);
        $htmltable->id = $tableid;
        $htmltable->attributes = $tableattributes;

        $head = [get_string('passageindex', constants::M_COMPONENT),
                get_string('passageword', constants::M_COMPONENT),
                get_string('mistrans_count', constants::M_COMPONENT),
                get_string('mistranscriptions', constants::M_COMPONENT)];

        $htmltable->head = $head;
        $rowcount = 0;
        $totalmistranscriptions = 0;
        foreach ($items as $row) {
            // If this was not a mistranscription, skip.
            if (!$row->mistranscriptions) {
                continue;
            }
            $rowcount++;
            $htr = new \html_table_row();

            $cell = new \html_table_cell($row->passageindex);
            $cell->attributes = ['class' => constants::M_CLASS . '_cell_passageindex'];
            $htr->cells[] = $cell;

            $cell = new \html_table_cell($row->passageword);
            $cell->attributes = ['class' => constants::M_CLASS . '_cell_passageword'];
            $htr->cells[] = $cell;

            $showmistranscriptions = "";
            $mistranscount = 0;
            foreach ($row->mistranscriptions as $badword => $count) {
                if ($showmistranscriptions != "") {
                    $showmistranscriptions .= " | ";
                }
                $showmistranscriptions .= $badword . "(" . $count . ")";
                $mistranscount += $count;
            }
            $totalmistranscriptions += $mistranscount;

            $cell = new \html_table_cell($mistranscount);
            $cell->attributes = ['class' => constants::M_CLASS . '_cell_mistrans_count'];
            $htr->cells[] = $cell;

            $cell = new \html_table_cell($showmistranscriptions);
            $cell->attributes = ['class' => constants::M_CLASS . '_cell_mistranscriptions'];
            $htr->cells[] = $cell;

            $htmltable->data[] = $htr;
        }
        $tabletitle = get_string("mistranscriptions_summary", constants::M_COMPONENT);
        $html = $this->output->heading($tabletitle, 4);
        if ($rowcount == 0) {
            $html .= get_string("nomistranscriptions", constants::M_COMPONENT);
        } else {
            $html .= \html_writer::tag('span', get_string("total_mistranscriptions",
                    constants::M_COMPONENT, $totalmistranscriptions),
                    ['class' => constants::M_CLASS . '_totalmistranscriptions']);
            $html .= \html_writer::table($htmltable);

            // Set up datatables.
            $tableprops = new \stdClass();
            $opts = [];
            $opts['tableid'] = $tableid;
            $opts['tableprops'] = $tableprops;
            $this->page->requires->js_call_amd(constants::M_COMPONENT . "/datatables", 'init', [$opts]);
            $this->page->requires->css(new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));

        }

        return $html;
    }

    /**
     * Get practice recorder data for template rendering.
     * Returns data array instead of pre-rendered HTML to support dynamic template rendering.
     *
     * @param object $moduleinstance The module instance.
     * @param string $token The token.
     * @param bool $pressed Whether the button is pressed.
     * @return array The practice recorder data with prefixed keys.
     */
    public function show_practice($moduleinstance, $token, bool $pressed = false) {

        // Recorder data.
        $data = [
            'uniqueid' => 'readaloud_ttrecorder',
            'language' => $moduleinstance->ttslanguage,
            'region' => $moduleinstance->region,
            'waveheight' => 75,
            'maxtime' => 15000,
            'asrurl' => utils::fetch_lang_server_url($moduleinstance->region, 'transcribe'),
            'rtl' => in_array($moduleinstance->ttslanguage, [
                constants::M_LANG_ARAE,
                constants::M_LANG_ARSA,
                constants::M_LANG_FAIR,
                constants::M_LANG_HEIL,
            ]),
            'name'  => 'recordbutton',
            'label' => get_string('recordbutton', constants::M_COMPONENT),
            'pressed' => $pressed ? 'true' : 'false',
            'passagehash' => '',
            'speechtoken' => '',
            'speechtokentype' => '',
            'forcestreaming' => false,
        ];

        // Do we need a streaming token?
        $alternatestreaming = get_config(constants::M_COMPONENT, 'alternatestreaming');
        $isenglish = strpos($moduleinstance->ttslanguage, 'en') === 0;
        if ($isenglish) {
            $tokenobject = utils::fetch_streaming_token($moduleinstance->region);
            if ($tokenobject) {
                $data['speechtoken'] = $tokenobject->token;
                $data['speechtokenregion'] = $tokenobject->region;
                $data['speechtokenvalidseconds'] = $tokenobject->validseconds;
                $data['speechtokentype'] = $tokenobject->tokentype;
            } else {
                $data['speechtoken'] = false;
                $data['speechtokenregion'] = '';
                $data['speechtokenvalidseconds'] = 0;
                $data['speechtokentype'] = '';
            }
            if ($alternatestreaming) {
                $data['forcestreaming'] = true;
            }
        }

        // Extract passagehash if applicable.
        $thefullhash = $moduleinstance->usecorpus == constants::GUIDEDTRANS_CORPUS
            ? $moduleinstance->corpushash
            : $moduleinstance->passagehash;

        if (!empty($thefullhash)) {
            $hashbits = explode('|', $thefullhash);
            if (count($hashbits) === 2) {
                $data['passagehash'] = $hashbits[1];
            }
        }

        return [
            'practice' => $data,
            'practice_rtl' => $data['rtl'],
        ];
    }

    /**
     * Render the current error estimate inside a container.
     *
     * @param float $errorestimate The current error estimate value
     * @return string HTML containing the error estimate
     */
    public function show_currenterrorestimate($errorestimate) {
        $message = get_string("currenterrorestimate", constants::M_COMPONENT, $errorestimate);
        $ret = \html_writer::div($message, constants::M_ADMINTAB_CONTAINER);

        return $ret;
    }

    /**
     * Render a hidden HTML5 audio player.
     *
     * @param string|false $audiourl URL of the audio file, or false for empty source
     * @return string HTML for the hidden audio player
     */
    public function render_hiddenaudioplayer($audiourl=false) {
        $src = $audiourl ? $audiourl : '';
        $audioplayer = \html_writer::tag('audio', '',
                ['src' => $src, 'id' => constants::M_HIDDEN_PLAYER, 'class' => constants::M_HIDDEN_PLAYER, 'crossorigin' => 'anonymous']);

        return $audioplayer;
    }

    /**
     * Render a message indicating that the item has been evaluated.
     *
     * @return string HTML containing the evaluated message
     */
    public function show_evaluated_message() {
        $displaytext = get_string('evaluatedmessage', constants::M_COMPONENT);
        $ret = \html_writer::div($displaytext, constants::M_EVALUATED_MESSAGE. ' ' . constants::M_CLASS . '_center', ['id' => constants::M_EVALUATED_MESSAGE]);

        return $ret;
    }

    /**
     * Prepare and return data required to render a ReadAloud recorder.
     *
     * @param stdClass $moduleinstance The module instance containing recorder settings
     * @param string   $token          The token for authentication
     * @param bool     $debug          Whether to enable debug mode.
     * @return array Associative array of recorder configuration parameters
     */
    public function show_recorder($moduleinstance, $token, $debug = false) {
        global $CFG, $USER;

        $hints = new \stdClass();
        $hints->allowearlyexit = $moduleinstance->allowearlyexit || !$moduleinstance->timelimit;
        $hints->juststart = $moduleinstance->recorder == constants::REC_ONCE ? 1 : 0;
        $hints->shadowing = $moduleinstance->enableshadow ? 1 : 0;

        if ($moduleinstance->recorder == constants::REC_ONCE) {
            $moduleinstance->recorder = constants::REC_READALOUD;
        }

        $cantranscribe = \mod_readaloud\utils::can_transcribe($moduleinstance);

        switch ($moduleinstance->transcriber) {
            case constants::TRANSCRIBER_STRICT:
            case constants::TRANSCRIBER_GUIDED:
            default:
                $transcribe = $cantranscribe ? "1" : "0";
                $speechevents = "0";
        }

        $stringhints = base64_encode(json_encode($hints));

        $transcribevocab = 'none';
        $thefullhash = $moduleinstance->usecorpus == constants::GUIDEDTRANS_CORPUS ? $moduleinstance->corpushash : $moduleinstance->passagehash;
        if (!empty($thefullhash) && !$moduleinstance->stricttranscribe) {
            $hashbits = explode('|', $thefullhash);
            if (count($hashbits) == 2) {
                $transcribevocab = $hashbits[1];
            } else {
                $transcribevocab = $moduleinstance->passagehash;
            }
        }

        $iswhisper = utils::is_whisper($moduleinstance->ttslanguage);
        if ($transcribevocab == 'none' && $iswhisper && !$moduleinstance->stricttranscribe) {
            $transcribevocab = $moduleinstance->passage;
        }

        return [
            'wwwroot' => $CFG->wwwroot,
            'cloudpoodllurl' => utils::get_cloud_poodll_server(),
            'owner' => hash('md5', $USER->username),
            'recorder_type' => $debug ? "upload" : $moduleinstance->recorder,
            'recorder_width' => $debug ? "500" : "210",
            'recorder_height' => $debug ? "500" : "150",
            'timelimit' => $moduleinstance->timelimit,
            'transcribe' => $transcribe,
            'language' => $moduleinstance->ttslanguage,
            'expiredays' => $moduleinstance->expiredays,
            'region' => $moduleinstance->region,
            'speechevents' => $speechevents,
            'hints' => $stringhints,
            'token' => $token,
            'transcribevocab' => $transcribevocab,
        ];
    }

    /**
     * This function embeds the AMD data on the page for restoring from html(json).
     *
     * @param stdClass $cm             The course module object.
     * @param array    $activitydata   The data to pass to JavaScript.
     * @return array Associative array containing the info needed to pass to js to restore data from html(json)
     */
    public function embed_activitydata_on_page($cm, $activitydata) {
        global $CFG, $USER;

        // This inits the M.mod_readaloud thingy, after the page has loaded.
        // We put the opts in html on the page because moodle/AMD doesn't like lots of opts in js.
        // Convert opts to json.
        $jsonstring = json_encode($activitydata);
        $widgetid = constants::M_RECORDERID . '_opts_9999';

        $this->page->requires->strings_for_js(['gotnosound', 'done', 'beginreading'], constants::M_COMPONENT);

        return [
            'widgetid' => $widgetid,
            'jsonstring' => $jsonstring,
            'modeview' => constants::M_COMPONENT . '_modeview_' . $cm->id,
        ];
    }


    /**
     * Fetch the AMD configuration for the activity.
     *
     * This function prepares all data required by the front-end JavaScript, including
     * recorder settings, activity containers, quiz data, and template context.
     *
     * @param stdClass $cm             The course module object.
     * @param stdClass $moduleinstance The module instance object.
     * @param string   $token          The token for authentication.
     * @param int      $embed          The embed parameter, default is 0.
     * @param stdClass $latestattempt  The latest attempt object.
     * @param array    $templatecontext The full template context to pass to JavaScript.
     * @param bool     $hasquizquestions Whether the activity has quiz questions (default false).
     * @return array Associative array containing the activity AMD configuration.
     */
    public function fetch_activity_amd($cm, $moduleinstance, $token, $embed=0, $latestattempt=null, $templatecontext=[], $hasquizquestions = false) {
        global $CFG, $USER;

        // Here we set up any info we need to pass into javascript.
        $adata = [];

        // Recorder html ids.
        $adata['recordercontainer'] = constants::M_RECORDER_CONTAINER;
        $adata['recorderid'] = constants::M_RECORDERID;
        $adata['recordingcontainer'] = constants::M_RECORDING_CONTAINER;

        // Activity html ids.
        $adata['activityinstructionscontainer'] = constants::M_ACTIVITYINSTRUCTIONS_CONTAINER;
        $adata['allowearlyexit'] = $moduleinstance->allowearlyexit ? true : false;
        $adata['breaks'] = $moduleinstance->modelaudiobreaks;
        $adata['steps'] = constants::STEPS;
        $adata['stepsenabled'] = utils::get_steps_enabled_state($moduleinstance);
        $adata['stepscomplete'] = utils::get_steps_complete_state($moduleinstance, $latestattempt);
        $adata['stepsopen'] = utils::get_steps_open_state($moduleinstance, $latestattempt, $hasquizquestions);
        $adata['quizreattempt'] = $moduleinstance->quizreattempt ? true : false;
        $adata['readreattempt'] = $moduleinstance->readreattempt ? true : false;
        $adata['errorcontainer'] = constants::M_ERROR_CONTAINER;
        $adata['feedbackcontainer'] = constants::M_FEEDBACK_CONTAINER;
        $adata['hider'] = constants::M_HIDER;
        $adata['hiddenaudioplayer'] = constants::M_HIDDEN_PLAYER;
        $adata['instructionscontainer'] = constants::M_INSTRUCTIONS_CONTAINER;
        $adata['practiceinstructionscontainer'] = constants::M_PRACTICEINSTRUCTIONS_CONTAINER;
        $adata['menubuttonscontainer'] = constants::M_MENUBUTTONS_CONTAINER;
        $adata['menuinstructionscontainer'] = constants::M_MENUINSTRUCTIONS_CONTAINER;
        $adata['modelaudioplayer'] = constants::M_MODELAUDIO_PLAYER;
        $adata['modejourneycontainer'] = constants::M_MODE_JOURNEY_CONTAINER;
        $adata['passagecontainer'] = constants::M_PASSAGE_CONTAINER;
        $adata['practicecontainerwrap'] = constants::M_PRACTICE_CONTAINER_WRAP;
        $adata['previewinstructionscontainer'] = constants::M_PREVIEWINSTRUCTIONS_CONTAINER;
        $adata['quizcontainer'] = constants::M_QUIZ_CONTAINER;
        $adata['quizcontainerwrap'] = constants::M_QUIZ_CONTAINER_WRAP;
        $adata['quizitemscontainer'] = constants::M_QUIZ_ITEMS_CONTAINER;
        $adata['quizplaceholder'] = constants::M_QUIZ_PLACEHOLDER;
        $adata['homecontainer'] = constants::M_HOME_CONTAINER;
        $adata['recordbuttoncontainer'] = constants::M_RECORD_BUTTON_CONTAINER;
        $adata['readreportcontainer'] = constants::M_READREPORT_CONTAINER;
        $adata['fullreportcontainer']  = constants::M_FULLREPORT_CONTAINER;
        $adata['startbuttoncontainer'] = constants::M_START_BUTTON_CONTAINER;
        $adata['modeview'] = constants::M_COMPONENT . '_modeview_' . $cm->id;

        $adata['audioplayerclass'] = constants::M_MODELAUDIO_PLAYER;
        $adata['playbutton'] = constants::M_PLAY_BTN;
        $adata['homebutton'] = constants::M_HOME;
        $adata['startlandrbutton'] = constants::M_STARTLANDR;
        $adata['startpreviewbutton'] = constants::M_STARTPREVIEW;
        $adata['startreadingbutton'] = constants::M_STARTNOSHADOW;
        $adata['startreportbutton'] = constants::M_STARTREPORT;
        $adata['readagainbutton'] = constants::M_READAGAIN;
        $adata['fullreportbutton'] = constants::M_FULLREPORT;
        $adata['startshadowbutton'] = constants::M_STARTSHADOW;
        $adata['startquizbutton'] = constants::M_STARTQUIZ;
        $adata['quizresultscontainer'] = constants::M_QUIZ_FINISHED;
        $adata['stopandplay'] = constants::M_STOPANDPLAY;
        $adata['quitlisteningbutton'] = constants::M_QUITLISTENING;
        $adata['stopbutton'] = constants::M_STOP_BTN;
        $adata['recordbutton'] = constants::M_RECORD_BTN;
        $adata['returnmenubutton'] = constants::M_RETURNMENU;
        $adata['ttsvoice'] = $moduleinstance->ttsvoice;

        $adata['phonetics'] = '';
        if ($moduleinstance->phonetic && !empty($moduleinstance->phonetic)) {
            $adata['phonetics'] = explode(' ', $moduleinstance->phonetic);
        }

        $adata['transcriber'] = $moduleinstance->transcriber;
        // This will force browser recognition to use Poodll (not chrome or other browser speech).
        if ($adata['transcriber'] == constants::TRANSCRIBER_GUIDED) {
            $adata['stt_guided'] = true;
        } else {
            $adata['stt_guided'] = false;
        }

        $adata['appid'] = constants::M_COMPONENT;
        $adata['expiretime'] = 300;// Max expire time is 300 seconds.
        $adata['language'] = $moduleinstance->ttslanguage;
        $adata['owner'] = hash('md5', $USER->username);
        $adata['parent'] = $CFG->wwwroot;
        $adata['region'] = $moduleinstance->region;
        $adata['token'] = $token;

        // Quiz data.
        $quizhelper = new quizhelper($cm);
        $adata['quizdata'] = $quizhelper->fetch_quiz_items_for_js($this);

        // Store the full template context for use in JavaScript when rendering templates. dynamically.
        $adata['templatecontext'] = $templatecontext;

        return $adata;
    }

    /**
     * Render a problem/warning message inside a container.
     *
     * @param string $msg The message to display
     * @return string HTML for the problem box
     */
    public function show_problembox($msg) {
        $output = '';
        $output .= $this->output->box_start(constants::M_COMPONENT . '_problembox');
        $output .= $this->notification($msg, 'warning');
        $output .= $this->output->box_end();

        return $output;
    }

    /**
     * Render a menu of "push" buttons for various module settings.
     *
     * Each button allows pushing a specific type of data (passage, TTS audio, timelimit, etc.)
     * for the given course module.
     *
     * @param stdClass $cm The course module object
     * @return string HTML for the push buttons menu
     */
    public function push_buttons_menu($cm) {
        $templateitems = [];
        $pushthings = ['passage', 'ttsmodelaudio', 'timelimit', 'targetwpm', 'questions', 'alternatives', 'modes', 'gradesettings', 'canexitearly'];

        foreach ($pushthings as $pushthing) {
            switch($pushthing){
                case 'passage': $action = constants::M_PUSH_PASSAGE;
                    break;
                case 'ttsmodelaudio': $action = constants::M_PUSH_TTSMODELAUDIO;
                    break;
                case 'timelimit': $action = constants::M_PUSH_TIMELIMIT;
                    break;
                case 'targetwpm': $action = constants::M_PUSH_TARGETWPM;
                    break;
                case 'questions': $action = constants::M_PUSH_QUESTIONS;
                    break;
                case 'alternatives': $action = constants::M_PUSH_ALTERNATIVES;
                    break;
                case 'modes': $action = constants::M_PUSH_MODES;
                    break;
                case 'gradesettings': $action = constants::M_PUSH_GRADESETTINGS;
                    break;
                case 'canexitearly': $action = constants::M_PUSH_CANEXITEARLY;
                    break;
            }
            $templateitems[] = [
                'title' => get_string('push' . $pushthing, constants::M_COMPONENT),
                'description' => get_string('push' . $pushthing .'_desc', constants::M_COMPONENT),
                'content' => $this->output->single_button(new \moodle_url( constants::M_URL . '/push.php',
                    ['id' => $cm->id, 'action' => $action]), get_string('push' . $pushthing, constants::M_COMPONENT)),
            ];
        }

        // Generate and return menu.
        $ret = $this->output->render_from_template( constants::M_COMPONENT . '/manybuttonsmenu', ['items' => $templateitems]);

        return $ret;
    }

    /**
     * Get the full report data for a student, including attempt summaries, charts, feedback, and passage rendering.
     *
     * @param stdClass $moduleinstance The module instance object.
     * @param context_module $modulecontext The course module context.
     * @param array $attempts Array of attempt objects for the student.
     * @return array Associative array containing evaluation status, rendered passage, attempt summaries, charts, and feedback.
     */
    public function get_full_student_report_data($moduleinstance, $modulecontext, $attempts, $cm) {

        // Fetch passage renderer.
        $passagerenderer = $this->page->get_renderer(constants::M_COMPONENT, 'passage');

        // Fetch attempt information.
        if ($attempts) {
            $latestattempt = current($attempts);

            if (utils::can_transcribe($moduleinstance)) {
                $latestaigrade = new \mod_readaloud\aigrade($latestattempt->id, $modulecontext->id);
            } else {
                $latestaigrade = false;
            }

            $havehumaneval = $latestattempt->sessiontime != null;
            $haveaieval = $latestaigrade && $latestaigrade->has_transcripts();
        } else {
            $latestattempt = false;
            $havehumaneval = false;
            $haveaieval = false;
            $latestaigrade = false;
        }

        // For passage rendering.
        $extraclasses = "reviewmode";

         // For Japanese (and later other languages we collapse spaces).
        $collapsespaces = false;
        if ($moduleinstance->ttslanguage == constants::M_LANG_JAJP) {
            $collapsespaces = true;
            $extraclasses .= " collapsespaces";
        }

        // Initiate return.
        $ret = [];

        // Show an attempt summary if we have more than one attempt and we are not the guest user.
        // This is a chart of the attempts.
        if (count($attempts) >= 1 && !isguestuser()) {
            // If we can calculate a grade, lets do it.
            $showgradesinchart = $moduleinstance->targetwpm > 0;

            switch ($moduleinstance->humanpostattempt) {
                case constants::POSTATTEMPT_NONE:
                    // No progress charts or data tables if not showing eval or errors.
                    break;

                case constants::POSTATTEMPT_EVALERRORSNOGRADE:
                    $showgradesinchart = false;
                    // No break here .. we want to flow on.
                case constants::POSTATTEMPT_EVAL:
                case constants::POSTATTEMPT_EVALERRORS:
                    $attemptsummarydata = utils::fetch_attempt_summary($moduleinstance);
                    if ($attemptsummarydata) {
                        $ret['hasattemptsummary'] = true;

                        // Show the attempt summary. (table data of averages and highest).
                        $ret['attemptssummary'] = $attemptsummarydata;
                        $ret['attemptshowgrades'] = $showgradesinchart;

                        // Show the chart of attempt results.
                        // TO DO delete this we dont need the chart

                        // $chartdata = utils::fetch_attempt_chartdata($moduleinstance);
                        // $renderedchart = $this->fetch_rendered_attemptchart($chartdata, $showgradesinchart);
                        // $ret['attemptschart'] = $renderedchart;
                    }
            }
        }

        // Get quiz results data.
        if ($latestattempt && !empty($latestattempt->qdetails)) {
            $qdetailsobj = json_decode($latestattempt->qdetails);
            if ($qdetailsobj !== null) {
                $quizhelper = new quizhelper($cm);
                $quizresults = utils::fetch_quiz_results($quizhelper, $latestattempt, $cm);
                $ret = array_merge($ret, (array)$quizresults);
            }
        }

        // Show feedback summary.
        $ret['generalfeedback'] = $moduleinstance->feedback;

        // Render the passage itself (to be marked up in JS).
        if ($havehumaneval || $haveaieval) {
            // We used to distingiush between humanpostattempt and machinepostattempt but we simplified it,
            // and just use the human value for all.
            switch ($moduleinstance->humanpostattempt) {
                case constants::POSTATTEMPT_NONE:
                    $thepassage = $passagerenderer->render_passage($moduleinstance->passagesegments, $moduleinstance->ttslanguage, constants::M_PASSAGE_CONTAINER, $extraclasses);
                    break;
                case constants::POSTATTEMPT_EVAL:
                case constants::POSTATTEMPT_EVALERRORS:
                case constants::POSTATTEMPT_EVALERRORSNOGRADE:
                    $evaluationstatus = true;
                    $passagehelper = new \mod_readaloud\passagehelper($latestattempt->id, $modulecontext->id);
                    $thepassage = $passagerenderer->render_attempted_passage($passagehelper, $moduleinstance->ttslanguage, $collapsespaces);
                    break;

            }
        } else {
            $evaluationstatus = false;
            $thepassage = $passagerenderer->render_passage($moduleinstance->passagesegments, $moduleinstance->ttslanguage, constants::M_PASSAGE_CONTAINER, $extraclasses);
        }
        $ret['evaluationstatus'] = $evaluationstatus;
        $ret['thepassage'] = $thepassage;

        return $ret;
    }

    /**
     * Prepare the data for the play/stop button.
     *
     * @param bool $pressed Whether the play button is currently pressed.
     * @return array{name:string,label:string,pressed:bool} Button context for Mustache.
     */
    public function get_playbutton(bool $pressed = false): array {
        return [
            'name'    => 'playbutton',
            'label'   => get_string('playbutton', constants::M_COMPONENT),
            'pressed' => $pressed ? 'true' : 'false',
        ];
    }

    /**
     * Get all constants defined in the constants class.
     *
     * @return array Associative array of constant names and their values
     */
    private function get_all_constants() {
        $reflection = new \ReflectionClass(constants::class);

        return $reflection->getConstants();
    }

    /**
     * Get the mode visibility data.
     *
     * @param mixed $moduleinstance The module instance.
     * @param mixed $canattempt Whether the user can attempt the activity.
     * @return array The mode visibility data.
     */
    private function get_mode_visibility($moduleinstance, $canattempt, $latestattempt) {
        $hasaudiobreaks = !empty($moduleinstance->modelaudiobreaks);
        $disableshadowgrading = get_config(constants::M_COMPONENT, 'disableshadowgrading');

        return [
            // Feature availability.
            'enablenoshadow' => (bool)$canattempt,
            // Permission-based availability.
            'canattempt' => (bool)$canattempt,
            'canshadowattempt' => $canattempt && $disableshadowgrading,

            // Other conditions.
            'hasaudiobreaks' => (bool)$hasaudiobreaks,
        ];
    }

    /**
     * Return the activity‐completion context for use in templates.
     *
     * @param renderer_base $output The renderer instance.
     * @return array Empty if no completion, or the flat array from export_for_template()
     */
    protected function get_activity_completion_data(\renderer_base $output): array {
        global $USER;
        if (!$this->page->activityrecord) {
            return [];
        }
        $cm = $this->page->cm;
        $userid = $USER->id;

        $completiondetails = \core_completion\cm_completion_details::get_instance($cm, $userid);
        $activitycompletion = new \core_course\output\activity_completion($cm, $completiondetails);

        return (array)$activitycompletion->export_for_template($output);
    }


    /**
     * Return the activity‐dates context for use in templates.
     *
     * @param renderer_base $output The renderer instance.
     * @return array empty if no dates, or the flat array from export_for_template()
     */
    protected function get_activity_dates_data(\renderer_base $output): array {
        global $USER;
        if (!$this->page->activityrecord) {
            return [];
        }
        $cm = $this->page->cm;
        $userid = $USER->id;

        $activitydates = \core\activity_dates::get_dates_for_module($cm, $userid);
        $activitydates = new \core_course\output\activity_dates($activitydates);

        return (array)$activitydates->export_for_template($output);
    }

    /**
     * Return the activity dates and completion data for templates.
     *
     * @param renderer_base $output The renderer instance.
     * @param context_module $modulecontext The module context.
     * @param stdClass $moduleinstance The module instance object.
     * @return array Empty if no data, or the flat array from export_for_template().
     */
    protected function get_activity_header_data(\renderer_base $output, $modulecontext, $moduleinstance, $stepsenabled = [], $stepscomplete = []): array {
        global $USER;
        if (!$this->page->activityrecord) {
            return [];
        }
        $cm = $this->page->cm;
        $userid = $USER->id;

        // Activity dates.
        $activitydates = \core\activity_dates::get_dates_for_module($cm, $userid);
        $activitydates = new \core_course\output\activity_dates($activitydates);
        $activitydatesdata = (array) $activitydates->export_for_template($output);
        // Activity completion.
        $completiondetails = \core_completion\cm_completion_details::get_instance($cm, $userid);
        $activitycompletion = new \core_course\output\activity_completion($cm, $completiondetails);
        $activitycompletiondata = (array) $activitycompletion->export_for_template($output);

        // Calculate progress bar percentage.
        $progressbar = $this->calculate_progress_percentage($stepsenabled, $stepscomplete);

        $activityheader = array_merge(
            $activitydatesdata,
            $activitycompletiondata,
            $progressbar
        );

        return $activityheader;
    }

    /**
     * Calculate progress bar percentage based on enabled and completed steps.
     *
     * @param array $stepsenabled Array of enabled steps from get_steps_enabled_state()
     * @param array $stepscomplete Array of completed steps from get_steps_complete_state()
     * @return array Progress bar data with percentagevalue and percentlabelvalue
     */
    protected function calculate_progress_percentage($stepsenabled, $stepscomplete) {
        $enabledcount = 0;
        $completedcount = 0;

        foreach ($stepsenabled as $stepkey => $stepvalue) {
            // Skip step_report (value is 0).
            if ($stepkey === 'step_report' || $stepvalue === 0) {
                continue;
            }
            $enabledcount++;

            if (isset($stepscomplete[$stepkey]) && $stepscomplete[$stepkey] === true) {
                $completedcount++;
            }
        }

        $percentage = $enabledcount > 0 ? ($completedcount / $enabledcount) * 100 : 0;

        return [
            'percentagevalue' => round($percentage, 2),
            'percentlabelvalue' => sprintf("%.2f %%", $percentage),
        ];
    }

    /**
     * Build and return the steps data for display.
     *
     * @param stdClass $moduleinstance    The current module instance.
     * @param bool     $canattempt        Initial “can attempt” flag; will be updated based on mode visibility.
     * @param stdClass $latestattempt     The user’s latest attempt record.
     * @param bool      $hasquizquestions Whether quiz has questions (default: false)
     * @return stdClass[]                 Array of step‐data objects.
     */
    protected function get_steps_data($moduleinstance, $canattempt, $latestattempt, $stepsenabled,
    $stepsopen, $stepscomplete, $hasquizquestions = false) {
        global $CFG;

        $modevisibility   = $this->get_mode_visibility($moduleinstance, $canattempt, $latestattempt);
        $canattempt       = $modevisibility['canattempt'];
        $canshadowattempt = $modevisibility['canshadowattempt'];
        $hasaudiobreaks   = $modevisibility['hasaudiobreaks'];

        $stepnumbers   = constants::STEPS;

        $stepdefs = [
            'step_listen'   => ['mode' => 'preview',       'label' => 'mode_listen',   'icon' => 'listen'],
            'step_practice' => ['mode' => 'landr',         'label' => 'mode_practice', 'icon' => 'practice'],
            'step_shadow'   => ['mode' => 'shadow',        'label' => 'mode_shadow',   'icon' => 'shadow'],
            'step_read'     => ['mode' => 'startnoshadow', 'label' => 'mode_read',     'icon' => 'read'],
            'step_quiz'     => ['mode' => 'quiz',          'label' => 'mode_quiz',     'icon' => 'quiz'],
            'step_report'   => ['mode' => 'fullreport',    'label' => 'mode_report',   'icon' => 'report'],
        ];
        $statusicons = [
            'locked'   => 'locked',
            'complete' => 'checked',
            'current'  => 'current',
        ];

        $stepsdata = [];

        foreach ($stepdefs as $key => $def) {
            if (empty($stepsenabled[$key])
                || ($key === 'step_practice' && ! $hasaudiobreaks)
                || ($key === 'step_shadow'   && ! $canshadowattempt)
                || ($key === 'step_read'     && ! $canattempt)
                || ($key === 'step_quiz'     && !$hasquizquestions)
            ) {
                continue;
            }

            $open     = !empty($stepsopen[$key]);
            $complete = !empty($stepscomplete[$key]);

            if (!$open) {
                $status = 'locked';
            } else if ($complete) {
                $status = 'complete';
            } else {
                $status = 'current';
            }

            // CSS classes.
            $classes = trim(implode(' ', [
                $status,
                $status === 'locked' ? 'no-click' : '',
            ]));

            // Get the mode icon.
            $iconname = $def['icon'] . '.svg';
            $pixdir   = $CFG->dirroot . '/mod/readaloud/pix/';
            $svgfile  = $pixdir . $iconname;
            $modeicon = is_readable($svgfile)
                ? file_get_contents($svgfile)
                : '';

            $label = get_string($def['label'], 'mod_readaloud');

            // Get the status icon.
            $iconkey = $statusicons[$status];
            $statusicon = $this->output
                ->image_url($iconkey, constants::M_COMPONENT)
                ->out(false);

            // Warning text.
            $warningtext = '';
            if ($key === 'step_practice' && ! $hasaudiobreaks) {
                $warningtext = get_string('modelaudiowarning', 'mod_readaloud');
            } else if ($key === 'step_read' && ! $canattempt) {
                $warningtext = get_string('exceededallattempts', 'mod_readaloud');
            } else if ($key === 'step_quiz' && ! empty($stepscomplete['step_quiz'])) {
                $warningtext = get_string('quizcompletedwarning', 'mod_readaloud');
            }

            // Build stepsdata.
            $stepsdata[] = (object)[
                'stepnumber'  => $stepnumbers[$key],
                'mode'        => $def['mode'],
                'classes'     => $classes,
                'statusicon'  => $statusicon,
                'label'       => $label,
                'warningtext' => $warningtext,
                'modeicon'    => $modeicon,
            ];
        }

        return $stepsdata;
    }

    /**
     * Get all data required for the activity view page template.
     *
     * This includes activity header, attempts, passage rendering, recorder,
     * quiz data, read reports, feedback, and AMD configuration.
     *
     * @param cm_info   $cm             The course module.
     * @param stdClass  $config         Plugin configuration.
     * @param int       $debug          Debug flag.
     * @param int       $embed          Embed flag.
     * @param context   $modulecontext  The module context.
     * @param stdClass  $moduleinstance The module instance.
     * @param int       $reviewattempts Review-attempts flag.
     * @return array Template context for rendering the view page.
     */
    public function get_view_page_data(
        $cm,
        $config,
        $debug,
        $embed,
        $modulecontext,
        $moduleinstance,
        $reviewattempts
    ) {
        // TODO: add in the : array once the imported functions are resolved.
        global $CFG, $DB, $USER;

        // The activity header (note: activity header data including progress bar will be set later after steps are calculated).
        $header = $this->page->activityheader;
        $corecourserenderer = $this->page->get_renderer('core_course');
        $headercontent = (array) $header->export_for_template($corecourserenderer);
        $passagepictureurl = utils::get_passage_picture($moduleinstance, $modulecontext);
        $preattemptinstructions = !empty($moduleinstance->welcome) ? $moduleinstance->welcome : null;
        $hasheadercontent = !empty($passagepictureurl) || !empty($headercontent['description']) || !empty($preattemptinstructions);

        // In the case that passage segments have not been set (usually from an upgrade from an earlier version) set those now.
        if ($moduleinstance->passagesegments === null) {
            $olditem = false;
            list($thephonetic, $thepassagesegments) = utils::update_create_phonetic_segments($moduleinstance, $olditem);
            if (!empty($thephonetic)) {
                $DB->update_record(constants::M_TABLE, ['id' => $moduleinstance->id, 'phonetic' => $thephonetic, 'passagesegments' => $thepassagesegments]);
                $moduleinstance->phonetic = $thephonetic;
                $moduleinstance->passagesegments = $thepassagesegments;
            }
        }
        // All attempts code.
        // Do we have attempts and ai data.
        $attempts = utils::fetch_user_attempts($moduleinstance);
        // $aievals = \mod_readaloud\utils::get_aieval_byuser($moduleinstance->id, $USER->id);

        // For Japanese (and later other languages we collapse spaces).
        $collapsespaces = false;
        if ($moduleinstance->ttslanguage == constants::M_LANG_JAJP) {
            $collapsespaces = true;
        }

        // Can attempt
        $canattempt = true;
        $canpreview = has_capability('mod/readaloud:preview', $modulecontext);
        if (!$canpreview && $moduleinstance->maxattempts > 0) {
            $gradeableattempts = 0;
            if ($attempts) {
                foreach ($attempts as $candidate) {
                    if ($candidate->dontgrade == 0) {
                        $gradeableattempts++;
                    }
                }
            }
            if ($attempts && $gradeableattempts >= $moduleinstance->maxattempts) {
                $canattempt = false;
            }
        }

        // Debug mode is for teachers only.
        if (!$canpreview) {
            $debug = false;
        }

        // Fetch attempt information.
        if ($attempts) {
            $latestattempt = current($attempts);

            if (\mod_readaloud\utils::can_transcribe($moduleinstance)) {
                $latestaigrade = new \mod_readaloud\aigrade($latestattempt->id, $modulecontext->id);
            } else {
                $latestaigrade = false;
            }

            $havehumaneval = $latestattempt->sessiontime != null;
            $haveaieval = $latestaigrade && $latestaigrade->has_transcripts();
        } else {
            $latestattempt = false;
            $havehumaneval = false;
            $haveaieval = false;
            $latestaigrade = false;
        }

        // If we are reviewing attempts we do that here and return.
        // If we are going to the dashboard we output that below.
        $passagerenderer = $this->page->get_renderer(constants::M_COMPONENT, 'passage');
        if ($attempts && $reviewattempts) {
            $attemptreviewhtml = $renderer->show_attempt_for_review($moduleinstance, $attempts,
            $havehumaneval, $haveaieval, $collapsespaces, $latestattempt, $token, $modulecontext, $passagerenderer, $embed);
            echo $attemptreviewhtml;
            return;
        }

        // Show read report.
        if ($attempts) {
            if (!$latestattempt) {
                $latestattempt = current($attempts);
            }
        }

        // Fetch a token and report a failure to a display item: $problembox.
        $problembox = '';
        $token = "";
        if (empty($config->apiuser) || empty($config->apisecret)) {
            $message = get_string('nocredentials', constants::M_COMPONENT,
            $CFG->wwwroot . constants::M_PLUGINSETTINGS);
            $problembox = $this->show_problembox($message);
        } else {
            // Fetch token.
            $token = utils::fetch_token($config->apiuser, $config->apisecret);

            // Check token authenticated and no errors in it.
            $errormessage = utils::fetch_token_error($token);
            if (!empty($errormessage)) {
                $problembox = $this->show_problembox($errormessage);
            }
        }

        // If we have a problem (usually with auth/token) we display and return.
        if (!empty($problembox)) {
            $problembox = true;
        }

        $modelaudiorenderer = $this->page->get_renderer(
        constants::M_COMPONENT,
        'modelaudio'
        );
        $modelaudiohtml = $modelaudiorenderer->render_modelaudio_player(
        $moduleinstance,
        $token,
        false
        );

        $welcomemessage = get_string('welcomemenu', constants::M_COMPONENT) .
        ($canattempt ? '' : '<br>' . get_string('exceededattempts', constants::M_COMPONENT, $moduleinstance->maxattempts));

        // Quiz html.
        $rsquestionrenderer = $this->page->get_renderer(\mod_readaloud\constants::M_COMPONENT, 'rsquestion');
        $quizhelper = new quizhelper($cm);
        $hasquizquestions = $quizhelper->fetch_item_count() > 0;
        $quizhtml = $rsquestionrenderer->show_quiz($quizhelper, $moduleinstance, $latestattempt, $cm);

        // Quiz finished data
        $quizfinished = utils::quiz_is_finished($moduleinstance, $latestattempt, $cm);
        $quizfinisheddata = $rsquestionrenderer->fetch_quizfinished_data($quizhelper, $moduleinstance, $latestattempt, $cm);

        // Render the passage.
        $mode = 'noquiz';
        if ($mode === 'quiz') {
            $modequiz = true;
        } else {
            $modequiz = false;
        }
        $stepsenabled = utils::get_steps_enabled_state($moduleinstance);
        $stepsopen = utils::get_steps_open_state($moduleinstance, $latestattempt, $hasquizquestions);
        $stepscomplete = utils::get_steps_complete_state($moduleinstance, $latestattempt);

        // Now that we have steps data, get activity header with progress bar.
        $activityheader = $this->get_activity_header_data(
            $corecourserenderer,
            $modulecontext,
            $moduleinstance,
            $stepsenabled,
            $stepscomplete
        );

        // Merge activity header data into headercontent for view.mustache → activity_header.mustache.
        $headercontent = array_merge(
            $headercontent,
            $activityheader,
            [
                'activityheader' => $hasheadercontent,
                'passagepictureurl' => $passagepictureurl,
                'preattemptinstructions' => $preattemptinstructions,
            ]
        );

        // Render the passage.
        $widgetid = constants::M_RECORDERID . '_opts_9999';
        $opts = [
            'cmid'        => $cm->id,
            'widgetid'    => $widgetid,
            'stepsenabled' => $stepsenabled,
            'stepsopen'    => $stepsopen,
            'stepscomplete' => $stepscomplete,
        ];
        $extraclasses = 'readmode'; // TODO: Should we add these directly to template?
        // For Japanese (and later other languages) we collapse spaces.
        $collapsespaces = false;
        if ($moduleinstance->ttslanguage == constants::M_LANG_JAJP) {
            $collapsespaces = true;
        }
        if ($collapsespaces) {
            $extraclasses .= ' collapsespaces';
        }
        $passagerenderer = $this->page->get_renderer(constants::M_COMPONENT, 'passage');
        $passagehtml = $passagerenderer->render_passage(
            $moduleinstance->passagesegments,
            $moduleinstance->ttslanguage,
            constants::M_PASSAGE_CONTAINER,
            $extraclasses
        );
        $this->page->requires->js_call_amd("mod_readaloud/activitycontroller", 'init', [$opts]);

        // Render the recorder.
        $recorder = $this->show_recorder($moduleinstance, $token, $debug);

        // Get practice recorder data.
        $practicedata = $this->show_practice($moduleinstance, $token);

        $canpreview = has_capability('mod/readaloud:preview', $modulecontext);
        $feedback = !empty($moduleinstance->feedback) ? $moduleinstance->feedback : null;
        $instructions = !empty($moduleinstance->welcome) ? $moduleinstance->welcome : null;
        $modevisibility = $this->get_mode_visibility($moduleinstance, $canattempt, $latestattempt);
        $readreport = $this->get_readreport_data($moduleinstance, $modulecontext, $cm, $attempts, $latestattempt, $latestaigrade);

        $stepsdata = $this->get_steps_data (
            $moduleinstance,
            $canattempt,
            $latestattempt,
            $stepsenabled,
            $stepsopen,
            $stepscomplete,
            $hasquizquestions
        );

        // Get full report data for the finalreport template.
        $fullreportdata = $this->get_full_student_report_data(
            $moduleinstance,
            $modulecontext,
            $attempts,
            $cm
        );

        // Build the full template context FIRST (before fetching AMD data).
        $templatecontext = array_merge(
            $activityheader, // Flatten activityheader data into root context for easier template access
            [
            'activityname' => $moduleinstance->name,
            'attempts' => $attempts,
            'backurl' => (new \moodle_url('/mod/readaloud/view.php', ['id' => $cm->id]))->out(false),
            'contextid' => $modulecontext->id,
            'canattempt' => $modevisibility['canattempt'],
            'canshadowattempt' => $modevisibility['canshadowattempt'],
             'debug' => $debug,
            'embed' => $embed,
            'enablenoshadow' => $modevisibility['enablenoshadow'],
            'error' => false, // cannot find any code calling show_error.
            'feedback' => $feedback,
            'fullreportdata' => $fullreportdata,
            'hasaudiobreaks' => $modevisibility['hasaudiobreaks'],
            'hasbody' => true, // TEMP.
            'hasheadercontent' => $hasheadercontent,
            'hasquizquestions' => $hasquizquestions,
            'headercontent' => $headercontent,
            'instructions' => $instructions,
            'modelaudiohtml' => $modelaudiohtml,
            'mode' => null,
            'modequiz' => $modequiz,
            'moduleinstance' => $moduleinstance,
            'passagehtml' => isset($passagehtml) ? $passagehtml : null,
            'passagepictureurl' => $passagepictureurl,
            'playbutton' => $this->get_playbutton(),
            'problembox' => $problembox,
            'quizamddata' => isset($quizamddata) ? $quizamddata : null,
            'quizhtml' => isset($quizhtml) ? $quizhtml : null,
            'quizfinished' => $quizfinished,
            'quizfinisheddata' => $quizfinisheddata,
            'reviewattempts' => $reviewattempts,
            'recorder' => $recorder,
            'readreport' => $readreport,
            'steps' => constants::STEPS,
            'stepscomplete' => $stepscomplete,
            'stepsdata' => $stepsdata,
            'stepsenabled' => $stepsenabled,
            'stepsopen' => $stepsopen,
            'stopandplay' => true, // TEMP.
            'token' => $token,
            'readreattempt' => $moduleinstance->readreattempt ? true : false,
            'welcomemessage' => $welcomemessage,
            'practice' => $practicedata['practice'],
            'practice_rtl' => $practicedata['practice_rtl'],
            ], $this->get_all_constants());

        // Fetch AMD data, passing the existing templatecontext to be stored in JSON for JavaScript.
        $activityamddata = $this->fetch_activity_amd($cm, $moduleinstance, $token,
            $embed, $latestattempt, $templatecontext, $hasquizquestions);
        $activityembedtags = $this->embed_activitydata_on_page($cm, $activityamddata);

        // Add the AMD data (well the embed tags) to the templatecontext.
        $templatecontext['activityamddata'] = $activityembedtags;

        // Return the complete templatecontext.
        return $templatecontext;
    }

    /**
     * Takes data from the Cloud Poodll usage report web service and renders it on the page.
     *
     * @param object $usagedata JSON decoded response from local_cpapi_fetch_user_report
     * @return void
     */
    public function display_usage_report($usagedata) {
        $reportdata = [];

        $mysubscriptions = [];

        if ($usagedata->usersubs) {
            foreach ($usagedata->usersubs as $subdata) {
                $subscriptionname = ($subdata->subscriptionname == ' ') ? "na" : strtolower(trim($subdata->subscriptionname));
                $mysubscriptions[] = ['name' => $subscriptionname,
                        'start_date' => date("m-d-Y", $subdata->timemodified),
                        'end_date' => date("m-d-Y", $subdata->expiredate)];
            }
        }

        $reportdata['subscription_check'] = count($mysubscriptions) > 0;
        $reportdata['subscriptions'] = $mysubscriptions;
        $reportdata['pusers'] = [];
        $reportdata['record'] = [];
        $reportdata['recordmin'] = [];
        $reportdata['recordtype'] = [];

        $threesixtyfiverecordtypevideo = 0;
        $oneeightyrecordtypevideo = 0;
        $ninetyrecordtypevideo = 0;
        $thirtyrecordtypevideo = 0;

        $threesixtyfiverecordtypeaudio = 0;
        $oneeightyrecordtypeaudio = 0;
        $ninetyrecordtypeaudio = 0;
        $thirtyrecordtypeaudio = 0;

        $threesixtyfiverecordmin = 0;
        $oneeightyrecordmin = 0;
        $ninetyrecordmin = 0;
        $thirtyrecordmin = 0;

        $threesixtyfiverecord = 0;
        $oneeightyrecord = 0;
        $ninetyrecord = 0;
        $thirtyrecord = 0;

        $threesixtyfivepuser = '';
        $oneeightypuser = '';
        $ninetypuser = '';
        $thirtypuser = '';

        // Monthly totals (12 x 30 day buckets, most recent first).
        $monthusertotals = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $monthpusers = ['', '', '', '', '', '', '', '', '', '', '', ''];
        $monthminutetotals = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $monthrecordtotals = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $monthaudiototals = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $monthvideototals = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        if ($usagedata->usersubs_details) {
            foreach ($usagedata->usersubs_details as $subdatadetails) {
                $timecreated = $subdatadetails->timecreated;

                for ($x = 0; $x < 12; $x++) {
                    $upperdays = -1 * $x * 30 . ' days';
                    $lowerdays = -1 * ($x + 1) * 30 . ' days';
                    if (($timecreated <= strtotime($upperdays)) && ($timecreated > strtotime($lowerdays))) {
                        $monthminutetotals[$x] = $monthminutetotals[$x] + ($subdatadetails->audio_min + $subdatadetails->video_min);
                        $monthaudiototals[$x] = $monthaudiototals[$x] + $subdatadetails->audio_file_count;
                        $monthvideototals[$x] = $monthvideototals[$x] + $subdatadetails->video_file_count;
                        $monthrecordtotals[$x] = $monthrecordtotals[$x] +
                            $subdatadetails->video_file_count + $subdatadetails->audio_file_count;
                        $monthvideototals[$x] = $monthvideototals[$x] + $subdatadetails->video_min;
                        $monthpusers[$x] = $monthpusers[$x] .= $subdatadetails->pusers;
                    }
                }

                if ($timecreated >= strtotime('-365 days')) {
                    $threesixtyfiverecordtypevideo += $subdatadetails->video_file_count;
                    $threesixtyfiverecordtypeaudio += $subdatadetails->audio_file_count;
                    $threesixtyfiverecordmin += ($subdatadetails->audio_min + $subdatadetails->video_min);
                    $threesixtyfiverecord += ($subdatadetails->video_file_count + $subdatadetails->audio_file_count);
                    $threesixtyfivepuser .= $subdatadetails->pusers;
                }

                if ($timecreated >= strtotime('-180 days')) {
                    $oneeightyrecordtypevideo += $subdatadetails->video_file_count;
                    $oneeightyrecordtypeaudio += $subdatadetails->audio_file_count;
                    $oneeightyrecordmin += ($subdatadetails->audio_min + $subdatadetails->video_min);
                    $oneeightyrecord += ($subdatadetails->video_file_count + $subdatadetails->audio_file_count);
                    $oneeightypuser .= $subdatadetails->pusers;
                }

                if ($timecreated >= strtotime('-90 days')) {
                    $ninetyrecordtypevideo += $subdatadetails->video_file_count;
                    $ninetyrecordtypeaudio += $subdatadetails->audio_file_count;
                    $ninetyrecordmin += ($subdatadetails->audio_min + $subdatadetails->video_min);
                    $ninetyrecord += ($subdatadetails->video_file_count + $subdatadetails->audio_file_count);
                    $ninetypuser .= $subdatadetails->pusers;
                }

                if ($timecreated >= strtotime('-30 days')) {
                    $thirtyrecordtypevideo += $subdatadetails->video_file_count;
                    $thirtyrecordtypeaudio += $subdatadetails->audio_file_count;
                    $thirtyrecordmin += ($subdatadetails->audio_min + $subdatadetails->video_min);
                    $thirtyrecord += ($subdatadetails->video_file_count + $subdatadetails->audio_file_count);
                    $thirtypuser .= $subdatadetails->pusers;
                }
            }
        }

        // Calc max month totals.
        $maxmonthpusers = 0;
        $maxmonthminutes = 0;
        $maxmonthaudio = 0;
        $maxmonthvideo = 0;
        $maxmonthrecordings = 0;
        for ($x = 0; $x < 12; $x++) {
            $monthusertotals[$x] = $this->count_pusers($monthpusers[$x]);
            if ($maxmonthpusers < $monthusertotals[$x]) {
                $maxmonthpusers = $monthusertotals[$x];
            }
            if ($maxmonthminutes < $monthminutetotals[$x]) {
                $maxmonthminutes = $monthminutetotals[$x];
            }
            if ($maxmonthaudio < $monthaudiototals[$x]) {
                $maxmonthaudio = $monthaudiototals[$x];
            }
            if ($maxmonthvideo < $monthvideototals[$x]) {
                $maxmonthvideo = $monthvideototals[$x];
            }
            if ($maxmonthrecordings < $monthrecordtotals[$x]) {
                $maxmonthrecordings = $monthrecordtotals[$x];
            }
        }

        // Calculate report summaries.
        $reportdata['pusers'] = [
                ['name' => '30', 'value' => $this->count_pusers($thirtypuser)],
                ['name' => '90', 'value' => $this->count_pusers($ninetypuser)],
                ['name' => '180', 'value' => $this->count_pusers($oneeightypuser)],
                ['name' => '365', 'value' => $this->count_pusers($threesixtyfivepuser)],
                ['name' => 'maxmonth', 'value' => $maxmonthpusers],
        ];

        $reportdata['record'] = [
                ['name' => '30', 'value' => $thirtyrecord],
                ['name' => '90', 'value' => $ninetyrecord],
                ['name' => '180', 'value' => $oneeightyrecord],
                ['name' => '365', 'value' => $threesixtyfiverecord],
                ['name' => 'maxmonth', 'value' => $maxmonthrecordings],
        ];

        $reportdata['recordmin'] = [
                ['name' => '30', 'value' => $thirtyrecordmin],
                ['name' => '90', 'value' => $ninetyrecordmin],
                ['name' => '180', 'value' => $oneeightyrecordmin],
                ['name' => '365', 'value' => $threesixtyfiverecordmin],
                ['name' => 'maxmonth', 'value' => $maxmonthminutes],
        ];

        $reportdata['recordtype'] = [
                ['name' => '30', 'video' => $thirtyrecordtypevideo, 'audio' => $thirtyrecordtypeaudio],
                ['name' => '90', 'video' => $ninetyrecordtypevideo, 'audio' => $ninetyrecordtypeaudio],
                ['name' => '180', 'video' => $oneeightyrecordtypevideo, 'audio' => $oneeightyrecordtypeaudio],
                ['name' => '365', 'video' => $threesixtyfiverecordtypevideo, 'audio' => $threesixtyfiverecordtypeaudio],
                ['name' => 'maxmonth', 'video' => $maxmonthvideo, 'audio' => $maxmonthaudio],
        ];

        // Tally usage per plugin for the pie chart.
        $plugintypes = [];
        if ($usagedata->usersubs_details) {
            foreach ($usagedata->usersubs_details as $subdatadetails) {
                $jsonarr = json_decode($subdatadetails->file_by_app, true);
                foreach ($jsonarr as $key => $val) {
                    $val = $jsonarr[$key]['audio'] + $jsonarr[$key]['video'];
                    if (isset($plugintypes[$key])) {
                        $plugintypes[$key] += $val;
                    } else {
                        $plugintypes[$key] = $val;
                    }
                }
            }
        }

        echo $this->output->render_from_template(constants::M_COMPONENT . '/mysubscriptionreport', $reportdata);

        if ($reportdata['subscription_check'] == true) {
            $pluginseries = new \core\chart_series('Plugin Usage', array_values($plugintypes));
            $pchart = new \core\chart_pie();
            $pchart->add_series($pluginseries);
            $pchart->set_labels(array_keys($plugintypes));
            echo $this->output->heading(get_string('per_plugin', constants::M_COMPONENT), 4);
            echo $this->output->render($pchart);
        }
    }

    /**
     * Count the unique users from a CSV list of users. Used by display_usage_report.
     *
     * @param string $pusers CSV list of user identifiers
     * @return int
     */
    public function count_pusers($pusers) {
        $pusers = trim($pusers);
        return count(array_unique(explode(',', $pusers)));
    }
}
