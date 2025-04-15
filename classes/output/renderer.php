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
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace mod_readaloud\output;

use context_module;
use mod_readaloud\constants;
use mod_readaloud\utils;
use mod_readaloud\quizhelper;
use ReflectionClass;

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

        $activityname = format_string($moduleinstance->name, true, $moduleinstance->course);
        if (empty($extrapagetitle)) {
            $title = $this->page->course->shortname . ": " . $activityname;
        } else {
            $title = $this->page->course->shortname . ": " . $activityname . ": " . $extrapagetitle;
        }

        // Build the buttons.
        $context = \context_module::instance($cm->id);

        // Header setup.
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
        $output = $this->output->header();

        if (!$moduleinstance->foriframe && $embed !== 2) {
            $thetitle = $this->output->heading($activityname, 3, 'main');
            $displaytext = \html_writer::div($thetitle, constants::M_CLASS . '_center');
            $output .= $displaytext;
        }

        if (has_capability('mod/readaloud:viewreports', $context) && $embed !== 2) {
            // $output .= $this->output->heading_with_help($activityname, 'overview', constants::M_COMPONENT);

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot . '/mod/readaloud/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        }

        return $output;
    }

    public function show_no_content($cm, $showsetup) {
        $displaytext = $this->output->box_start();
        $displaytext .= $this->output->heading(get_string('nopassage', constants::M_COMPONENT), 3, 'main');
        if ($showsetup) {
            $displaytext .= \html_writer::div(get_string('letsaddpassage', constants::M_COMPONENT), '', []);
            $displaytext .= $this->output->single_button(new \moodle_url(constants::M_URL . '/setup.php',
                    ['id' => $cm->id]), get_string('addpassage', constants::M_COMPONENT));
        } else {
            $displaytext .= \html_writer::div(get_string('waitforpassage', constants::M_COMPONENT), '', []);
        }
        $displaytext .= $this->output->box_end();
        $ret = \html_writer::div($displaytext, constants::M_CLASS . '_nopassage_msg', ['id' => constants::M_CLASS . '_nopassage_msg']);

        return $ret;
    }


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

    // public function show_quiz($moduleinstance, $items) {
    // global $CFG;
    // $data = [];
    // $data['items'] = $items;

    // Finally render template and return.
    // return $this->render_from_template('mod_readaloud/quiz', $data);
    // }

    /**
     * Show the small report.
     *
     * @param mixed $moduleinstance The module instance.
     * @param mixed $attempt The attempt.
     * @param mixed $aigrade The AI grade.
     * @param int $embed The embed parameter, default is 0, set to 2 if authenticated via token.
     * @return string The HTML for the small report.
     */
    // public function show_smallreport($moduleinstance, $attempt=false, $aigrade=false, $embed=0) {

    // Template data for small report.
    // $tdata = [];
    // Show grades and stats.
    // $showstats = $moduleinstance->humanpostattempt != constants::POSTATTEMPT_NONE;
    // $showgrades = $moduleinstance->targetwpm > 0 && $showstats && $moduleinstance->humanpostattempt != constants::POSTATTEMPT_EVALERRORSNOGRADE;
    // If this is in gradebook or not.
    // $notingradebook = $attempt->dontgrade > 0;

    // //     // Attempt has been graded yet?
    // $have_humaneval = $attempt->sessiontime != null;
    // $have_aieval = $aigrade && $aigrade->has_transcripts();
    // $graded = $have_humaneval || $have_aieval;

    // Star rating.
    // if ($attempt && $graded) {
    // Stars.
    // if ($showgrades) {
    // $rating = utils::fetch_rating($attempt, $aigrade); // 0,1,2,3,4 or 5.
    // } else {
    // $rating = 5;
    // }
    // $ready = $rating > -1;
    // $stars = [];
    // for ($star = 0; $star < 5; $star++) {
    // $stars[] = $rating > $star ? 'fa-star' : 'fa-star-o';
    // }
    // $tdata['stars'] = $stars;

    // Stats.
    // $stats = utils::fetch_small_reportdata($attempt, $aigrade);
    // $tdata['wpm'] = $stats->wpm;
    // $tdata['acc'] = $stats->accuracy;
    // $tdata['totalwords'] = $stats->sessionendword;
    // $tdata['notingradebook'] = $notingradebook;

    // } else {
    // $ready = false;
    // }

    // if ($ready) {
    // $tdata['ready'] = true;
    // }

    // Audio filename.
    // $tdata['src'] = '';
    // if ($ready && $attempt->filename) {
    // We set the filename here. If attempt is not ready yet, audio may not be ready, so we blank it here
    // and set it from JS pinging every 500ms or so till audio is ready.
    // $tdata['src'] = $attempt->filename;
    // }

    // If there is no remote transcriber
    // we do not want to get users hopes up by trying to fetch a transcript with ajax.
    // if (utils::can_transcribe($moduleinstance)) {
    // $remotetranscribe = true;
    // } else {
    // $remotetranscribe = false;
    // }

    // Full report button.
    // $fullreportcaption = $showstats ? get_string('fullreport', constants::M_COMPONENT) : get_string('fullreportnoeval', constants::M_COMPONENT);
    // $fullreportbutton = $this->output->single_button(new \moodle_url(constants::M_URL . '/view.php',
    // [
    // 'n' => $moduleinstance->id,
    // 'reviewattempts' => 1,
    // 'embed' => $embed,
    // ]
    // ), $fullreportcaption);
    // $tdata['fullreportbutton'] = $fullreportbutton;
    // $tdata['showgrades'] = $showgrades;
    // $tdata['showstats'] = $showstats;
    // $tdata['remotetranscribe'] = $remotetranscribe;

    // Finally render template.
    // $ret = $this->render_from_template('mod_readaloud/smallreport', $tdata);

    // JS to refresh small report.
    // $opts = [];
    // $opts['filename'] = $attempt->filename;
    // $opts['attemptid'] = $attempt ? $attempt->id : false;
    // $opts['ready'] = $ready;
    // $opts['remotetranscribe'] = $remotetranscribe;
    // $opts['showgrades'] = $showgrades;
    // $opts['showstats'] = $showstats;
    // $opts['notingradebook'] = $notingradebook;
    // $this->page->requires->js_call_amd(constants::M_COMPONENT . "/smallreporthelper", 'init', [$opts]);
    // $this->page->requires->strings_for_js(['secs_till_check', 'notgradedyet', 'evaluatedmessage', 'checking', 'notaddedtogradebook'], constants::M_COMPONENT);

    // return $ret;
    // }

    /**
     * Returns the template data for the small report partial.
     *
     * @param object $moduleinstance
     * @param object|false $attempt
     * @param object|false $aigrade
     * @param int $embed
     * @return mixed
     */
    protected function get_smallreport_data($moduleinstance, $modulecontext, $cm, $attempts = false, $attempt = false, $aigrade = false ) {


        // Template data for small report.
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
                $rating = utils::fetch_rating($attempt, $aigrade); // 0,1,2,3,4 or 5.
            } else {
                $rating = 5;
            }
            $ready = $rating > -1;
            $stars = [];
            for ($star = 0; $star < 5; $star++) {
                $stars[] = $rating > $star ? 'fa-solid fa-star' : 'fa-regular fa-star';
            }
            $tdata['stars'] = $stars;

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
        }else{
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

        // Get Full Report and merge it into tdata
        $tdata['fullreportcontainer']  = constants::M_FULLREPORT_CONTAINER;
        $fullreportdata = $this->get_full_student_report_data(
            $moduleinstance,
            $modulecontext,
            $attempts
        );
        $tdata = array_merge($tdata, $fullreportdata);

        // JavaScript to initiate small report
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
        $tdata['configjsonstring'] = json_encode($opts);
        $tdata['configcontrolid'] = constants::M_SMALLREPORT_CONTAINER . '_opts';


        $this->page->requires->js_call_amd(constants::M_COMPONENT . "/smallreporthelper", 'init', [['configcontrolid' => $tdata['configcontrolid']]]);
        $this->page->requires->strings_for_js(
            ['secs_till_check', 'notgradedyet', 'evaluatedmessage', 'checking', 'notaddedtogradebook'],
            constants::M_COMPONENT
        );

        return $tdata;
    }


    /**
     * Show the return to menu button.
     *
     * @param int $embed The embed parameter, default is 0, set to 2 if authenticated via token.
     * @return string The HTML for the return to menu button.
     */
    public function show_returntomenu_button($embed) {
        $returnbutton = \html_writer::tag('button', "<i class='fa fa-arrow-left'></i> ".get_string("returnmenu", constants::M_COMPONENT),
                [
                    'class' => constants::M_CLASS . '_center btn-block btn btn-secondary ' . constants::M_RETURNMENU,
                    'type' => 'button',
                    'style' => 'display: none', 'id' => constants::M_RETURNMENU,
                    'embed' => $embed,
                ]
            );

        return $returnbutton;
    }

    /**
     *
     */
    public function jump_tomenubutton($moduleinstance, $embed=0) {

        $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/view.php',
                [
                    'n' => $moduleinstance->id,
                    'reviewattempts' => 0,
                    'embed' => $embed]),
                    get_string('returntomenu',
                    constants::M_COMPONENT));

        $ret = \html_writer::div($button, constants::M_CLASS . '_afterattempt_cont');

        return $ret;
    }

    public function show_wheretonextdel($moduleinstance, $embed = 0) {
        $nextactivity = utils::fetch_next_activity($moduleinstance->activitylink);

        // Back to menu button data.
        $backtotop = [
            'url' => (new \moodle_url(constants::M_URL . '/view.php', [
                'n' => $moduleinstance->id,
                'embed' => $embed,
            ]))->out(),
            'label' => get_string("backtotop", constants::M_COMPONENT),
        ];

        // Prepare data for template.
        return [
            'backtotop' => $backtotop,
            'nextactivity' => !empty($nextactivity->url) ? [
                'url' => $nextactivity->url->out(),
                'label' => $nextactivity->label,
            ] : null,
        ];
    }

    /**
     * Show the "Where to Next" section.
     *
     * @param object $moduleinstance The module instance.
     * @param int $embed The embed parameter, default is 0.
     * @return array The data for the "Where to Next" section.
     */
    public function show_wheretonext($moduleinstance, $embed = 0): array {
        $nextactivity = utils::fetch_next_activity($moduleinstance->activitylink);

        return [
            'backtotop_url' => (new \moodle_url(constants::M_URL . '/view.php', [
                'n' => $moduleinstance->id,
                'embed' => $embed,
            ]))->out(false),
            'nextactivity_url' => !empty($nextactivity->url) ? $nextactivity->url->out(false) : null,
            'nextactivity_label' => !empty($nextactivity->label) ? $nextactivity->label : null,
        ];
    }

    /**
     *
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
     *
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
     *
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
     * @param array an array of mistranscription objects (passageindex, passageword, mistranscription summary)
     * @return string an html table
     */
    public function show_all_mistranscriptions($items) {

        global $CFG;

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

    public function show_landr($moduleinstance, $token) {
        // Recorder modal title.
        $title = get_string('landrreading', constants::M_COMPONENT);

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
        ];

        // Do we need a streaming token?
        $alternatestreaming = get_config(constants::M_COMPONENT, 'alternatestreaming');
        $isenglish = strpos($moduleinstance->ttslanguage, 'en') === 0;
        if ($isenglish) {
            $data['speechtoken'] = utils::fetch_streaming_token($moduleinstance->region);
            $data['speechtokentype'] = 'assemblyai';
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

        // Render content from listenandrepeat.mustache.
        $content = $this->render_from_template('mod_readaloud/listenandrepeat', $data);

        return [
            'title' => $title,
            'content' => $content,
            'containertag' => 'landr_container',
        ];
    }

    /**
     *
     */
    public function show_currenterrorestimate($errorestimate) {
        $message = get_string("currenterrorestimate", constants::M_COMPONENT, $errorestimate);
        $ret = \html_writer::div($message, constants::M_ADMINTAB_CONTAINER);

        return $ret;
    }

    public function show_ungradedyet() {
        $message = get_string("notgradedyet", constants::M_COMPONENT);
        $ret = \html_writer::div($message, constants::M_CLASS . '_ungraded_cont');

        return $ret;
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
     * Show the introduction text is as set in the activity description
     */
    public function show_intro($readaloud, $cm) {
        $ret = "";
        if (utils::super_trim(strip_tags($readaloud->intro))) {
            $ret .= $this->output->box_start(constants::M_INTRO_CONTAINER . ' ' . constants::M_CLASS . '_center ');
            $ret .= format_module_intro('readaloud', $readaloud, $cm->id);
            $ret .= $this->output->box_end();
        }

        return $ret;
    }

    /**
     * Show the reading passage after the attempt, basically set it to display on load and give it a background color
     */
    public function show_passage_postattempt($readaloud, $collapsespaces=false) {
        $ret = "";
        $displaypassage = utils::lines_to_brs($readaloud->passage);

        // For some languages we do not want spaces. Japanese, Chinese. For now this is manual.
        // TODO: auto determine when to use collapsespaces.
        $collapsespaces = $collapsespaces ? ' reviewmode collapsespaces' : '';

        $ret .= \html_writer::div($displaypassage, constants::M_PASSAGE_CONTAINER . ' '
                . constants::M_POSTATTEMPT . $collapsespaces,
                ['id' => constants::M_PASSAGE_CONTAINER]);

        return $ret;
    }

    public function render_hiddenaudioplayer($audiourl=false) {
        $src = $audiourl ? $audiourl : '';
        $audioplayer = \html_writer::tag('audio', '',
                ['src' => $src, 'id' => constants::M_HIDDEN_PLAYER, 'class' => constants::M_HIDDEN_PLAYER, 'crossorigin' => 'anonymous']);

        return $audioplayer;
    }

    /**
     * Show the reading passage
     */
    public function show_passage($readaloud, $cm) {

        $ret = "";
        $displaypassage = utils::lines_to_brs($readaloud->passage);
        $ret .= \html_writer::div($displaypassage, constants::M_PASSAGE_CONTAINER,
                ['id' => constants::M_PASSAGE_CONTAINER]);

        return $ret;
    }

    public function show_evaluated_message() {
        $displaytext = get_string('evaluatedmessage', constants::M_COMPONENT);
        $ret = \html_writer::div($displaytext, constants::M_EVALUATED_MESSAGE. ' ' . constants::M_CLASS . '_center', ['id' => constants::M_EVALUATED_MESSAGE]);

        return $ret;
    }


    // /**
    // * The html part of the recorder (js is in the fetch_activity_amd)
    // */
    // public function show_recorder($moduleinstance, $token, $debug = false) {
    // global $CFG, $USER;

    // Recorder.
    // =======================================
    // $hints = new \stdClass();
    // If there is no time limit, or allow early exit is on, we need a stop button.
    // $hints->allowearlyexit = $moduleinstance->allowearlyexit || !$moduleinstance->timelimit;
    // The readaloud recorder now handles juststart setting.
    // If the user has selected, just start, ok.
    // $hints->juststart = $moduleinstance->recorder == constants::REC_ONCE ? 1 : 0;

    // If we are shadowing we also want to tell the recorder
    // so that it can disable noise supression and echo cancellation.
    // $hints->shadowing = $moduleinstance->enableshadow ? 1 : 0;

    // if ($moduleinstance->recorder == constants::REC_ONCE) {
    // $moduleinstance->recorder = constants::REC_READALOUD;
    // }

    // $can_transcribe = \mod_readaloud\utils::can_transcribe($moduleinstance);

    // We no longer want to use AWS streaming transcription.
    // switch ($moduleinstance->transcriber){
    // case constants::TRANSCRIBER_STRICT:
    // case constants::TRANSCRIBER_GUIDED:
    // default:
    // $transcribe = $can_transcribe ? "1" : "0";
    // $speechevents = "0";
    // }

    // We encode any hints.
    // $string_hints = base64_encode(json_encode($hints));
    // Get passage hash as key for transcription vocab.
    // We sneakily add "[region]|" when we save passage hash .. so if user changes region ..we re-generate lang model.
    // $transcribevocab = 'none';
    // $thefullhash = $moduleinstance->usecorpus == constants::GUIDEDTRANS_CORPUS ? $moduleinstance->corpushash : $moduleinstance->passagehash;
    // if (!empty($thefullhash) && !$moduleinstance->stricttranscribe) {
    // $hashbits = explode('|', $thefullhash);
    // if (count($hashbits) == 2) {
    // $transcribevocab = $hashbits[1];
    // } else {
    // In the early days there was no region prefix, so we just use the passagehash as is.
    // $transcribevocab = $moduleinstance->passagehash;
    // }
    // }

    // For now we just use the passage as transcribevocab if its guided and language is minz (maori).
    // $iswhisper = utils::is_whisper($moduleinstance->ttslanguage);
    // if ($transcribevocab == 'none' && $iswhisper && !$moduleinstance->stricttranscribe) {
    // If we are using whisper we want to send a prompt to OpenAI.
    // $transcribevocab = $moduleinstance->passage;
    // }

    // $recorderdiv = \html_writer::div('', constants::M_CLASS . '_center',
    // array('id' => constants::M_RECORDERID,
    // 'data-id' => constants::M_RECORDERID,
    // 'data-parent' => $CFG->wwwroot,
    // 'data-localloading' => 'auto',
    // 'data-localloader' => '/mod/readaloud/poodllloader.html',
    // 'data-media' => "audio",
    // 'data-appid' => constants::M_COMPONENT,
    // 'data-owner' => hash('md5', $USER->username),
    // 'data-type' => $debug ? "upload" : $moduleinstance->recorder,
    // 'data-width' => $debug ? "500" : "210",
    // 'data-height' => $debug ? "500" : "150",
    // 'data-iframeclass'=>"letsberesponsive",
    // 'data-updatecontrol' => constants::M_UPDATE_CONTROL,
    // 'data-timelimit' => $moduleinstance->timelimit,
    // 'data-transcode' => "1",
    // 'data-transcribe' => $transcribe,
    // 'data-language' => $moduleinstance->ttslanguage,
    // 'data-expiredays' => $moduleinstance->expiredays,
    // 'data-region' => $moduleinstance->region,
    // 'data-fallback' => 'warning',
    // 'data-speechevents' => $speechevents,
    // 'data-hints' => $string_hints,
    // 'data-token' => $token, // localhost
    // 'data-transcribevocab' => $transcribevocab
    // 'data-token'=>"643eba92a1447ac0c6a882c85051461a" //cloudpoodll
    // )
    // );
    // $containerdiv = \html_writer::div($recorderdiv, constants::M_RECORDER_CONTAINER . " " . constants::M_CLASS . '_center',
    // array('id' => constants::M_RECORDER_CONTAINER));
    // =======================================

    // $recordingdiv = \html_writer::div($containerdiv, constants::M_RECORDING_CONTAINER);

    // Prepare output.
    // $ret = "";
    // $ret .= $recordingdiv;
    // Return it.
    // return $ret;
    // }
    /**
     * Show the recorder.
     *
     * @param object $moduleinstance The module instance.
     * @param string $token The token.
     * @param bool $debug Whether to enable debug mode.
     * @return array The recorder data.
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
     * Show open and close dates for the activity.
     *
     * @param object $moduleinstance The module instance.
     * @return string The HTML content for the open and close dates.
     */
    public function show_open_close_dates($moduleinstance) {
        $tdata = [];
        if ($moduleinstance->viewstart > 0) {
            $tdata['opendate'] = $moduleinstance->viewstart;
        }
        if ($moduleinstance->viewend > 0) {
            $tdata['closedate'] = $moduleinstance->viewend;
        }
        $ret = $this->output->render_from_template( constants::M_COMPONENT . '/openclosedates', $tdata);

        return $ret;
    }

    /**
     * Fetches the activity AMD configuration.
     *
     * @param object $cm The course module object.
     * @param object $moduleinstance The module instance object.
     * @param string $token The token for authentication.
     * @param int $embed The embed parameter, default is 0.
     * @return array The activity AMD configuration.
     */
    public function fetch_activity_amd($cm, $moduleinstance, $token, $embed=0, $latestattempt=null) {
        global $CFG, $USER;
        // Any html we want to return to be sent to the page.
        $rethtml = '';

        // Here we set up any info we need to pass into javascript.
        $recopts = [];

        // Recorder html ids.
        $recopts['recordercontainer'] = constants::M_RECORDER_CONTAINER;
        $recopts['recorderid'] = constants::M_RECORDERID;
        $recopts['recordingcontainer'] = constants::M_RECORDING_CONTAINER;

        // Activity html ids.
        $recopts['activityinstructionscontainer'] = constants::M_ACTIVITYINSTRUCTIONS_CONTAINER;
        $recopts['allowearlyexit'] = $moduleinstance->allowearlyexit ? true : false;
        $recopts['breaks'] = $moduleinstance->modelaudiobreaks;
        $recopts['steps'] = constants::STEPS;
        $recopts['stepsenabled'] = utils::get_steps_enabled_state($moduleinstance);
        $recopts['stepscomplete'] = utils::get_steps_complete_state($moduleinstance, $latestattempt);
        $recopts['errorcontainer'] = constants::M_ERROR_CONTAINER;
        $recopts['feedbackcontainer'] = constants::M_FEEDBACK_CONTAINER;
        $recopts['hider'] = constants::M_HIDER;
        $recopts['hiddenaudioplayer'] = constants::M_HIDDEN_PLAYER;
        $recopts['instructionscontainer'] = constants::M_INSTRUCTIONS_CONTAINER;
        $recopts['landrinstructionscontainer'] = constants::M_LANDRINSTRUCTIONS_CONTAINER;
        $recopts['menubuttonscontainer'] = constants::M_MENUBUTTONS_CONTAINER;
        $recopts['menuinstructionscontainer'] = constants::M_MENUINSTRUCTIONS_CONTAINER;
        $recopts['modelaudioplayer'] = constants::M_MODELAUDIO_PLAYER;
        $recopts['modeimagecontainer'] = constants::M_MODE_IMAGE_CONTAINER;
        $recopts['modejourneycontainer'] = constants::M_MODE_JOURNEY_CONTAINER;
        $recopts['passagecontainer'] = constants::M_PASSAGE_CONTAINER;
        $recopts['previewinstructionscontainer'] = constants::M_PREVIEWINSTRUCTIONS_CONTAINER;
        $recopts['progresscontainer'] = constants::M_PROGRESS_CONTAINER;
        $recopts['quizcontainer'] = constants::M_QUIZ_CONTAINER;
        $recopts['quizcontainerwrap'] = constants::M_QUIZ_CONTAINER_WRAP;
        $recopts['quizitemscontainer'] = constants::M_QUIZ_ITEMS_CONTAINER;
        $recopts['quizplaceholder'] = constants::M_QUIZ_PLACEHOLDER;
        $recopts['homecontainer'] = constants::M_HOME_CONTAINER;
        $recopts['recordbuttoncontainer'] = constants::M_RECORD_BUTTON_CONTAINER;
        $recopts['smallreportcontainer'] = constants::M_SMALLREPORT_CONTAINER;
        $recopts['fullreportcontainer']  = constants::M_FULLREPORT_CONTAINER;
        $recopts['startbuttoncontainer'] = constants::M_START_BUTTON_CONTAINER;
        $recopts['wheretonextcontainer'] = constants::M_WHERETONEXT_CONTAINER;

        $recopts['audioplayerclass'] = constants::M_MODELAUDIO_PLAYER;
        $recopts['playbutton'] = constants::M_PLAY_BTN;
        $recopts['homebutton'] = constants::M_HOME;
        $recopts['startlandrbutton'] = constants::M_STARTLANDR;
        $recopts['startpreviewbutton'] = constants::M_STARTPREVIEW;
        $recopts['startreadingbutton'] = constants::M_STARTNOSHADOW;
        $recopts['startreportbutton'] = constants::M_STARTREPORT;
        $recopts['readagainbutton'] = constants::M_READAGAIN;
        $recopts['fullreportbutton'] = constants::M_FULLREPORT;
        $recopts['startshadowbutton'] = constants::M_STARTSHADOW;
        $recopts['startquizbutton'] = constants::M_STARTQUIZ;
        $recopts['quizresultscontainer'] = constants::M_QUIZ_FINISHED;
        $recopts['stopandplay'] = constants::M_STOPANDPLAY;
        $recopts['stopbutton'] = constants::M_STOP_BTN;
        $recopts['returnmenubutton'] = constants::M_RETURNMENU;
        $recopts['ttsvoice'] = $moduleinstance->ttsvoice;

        $recopts['phonetics'] = '';
        if ($moduleinstance->phonetic && !empty($moduleinstance->phonetic)) {
            $recopts['phonetics'] = explode(' ', $moduleinstance->phonetic);
        }

        $recopts['transcriber'] = $moduleinstance->transcriber;
        // This will force browser recognition to use Poodll (not chrome or other browser speech).
        if ($recopts['transcriber'] == constants::TRANSCRIBER_GUIDED) {
            $recopts['stt_guided'] = true;
        } else {
            $recopts['stt_guided'] = false;
        }

        $recopts['appid'] = constants::M_COMPONENT;
        $recopts['expiretime'] = 300;// Max expire time is 300 seconds.
        $recopts['language'] = $moduleinstance->ttslanguage;
        $recopts['owner'] = hash('md5', $USER->username);
        $recopts['parent'] = $CFG->wwwroot;
        $recopts['region'] = $moduleinstance->region;
        $recopts['token'] = $token;

        // quiz data
        $quizhelper = new quizhelper($cm);
        $recopts['quizdata'] = $quizhelper->fetch_quiz_items_for_js($this);

        // We need an update control to hold the recorded filename, and one for draft item id.
        // $rethtml = $rethtml . \html_writer::tag('input', '', ['id' => constants::M_UPDATE_CONTROL, 'type' => 'hidden']);

        // This inits the M.mod_readaloud thingy, after the page has loaded.
        // We put the opts in html on the page because moodle/AMD doesn't like lots of opts in js.
        // Convert opts to json.
        $jsonstring = json_encode($recopts);
        $widgetid = constants::M_RECORDERID . '_opts_9999';
        // $optshtml =
        // \html_writer::tag('input', '', ['id' => 'amdopts_' . $widgetid, 'type' => 'hidden', 'value' => $jsonstring]);

        // The recorder div.
        // $rethtml = $rethtml . $optshtml;

        // $opts = ['cmid' => $cm->id, 'widgetid' => $widgetid];

        // // $this->page->requires->js_call_amd("mod_readaloud/activitycontroller", 'init', [$opts]);
        // $this->page->requires->js_call_amd("mod_readaloud/quizcontroller", 'init', [$opts]);
        $this->page->requires->strings_for_js(['gotnosound', 'done', 'beginreading'], constants::M_COMPONENT);

        // These need to be returned and echo'ed to the page.
        // return $rethtml;
        return [
            'widgetid' => $widgetid,
            'jsonstring' => $jsonstring,
        ];
    }


    /**
     * Return HTML to display message about problem
     */
    public function show_problembox($msg) {
        $output = '';
        $output .= $this->output->box_start(constants::M_COMPONENT . '_problembox');
        $output .= $this->notification($msg, 'warning');
        $output .= $this->output->box_end();

        return $output;
    }

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

    /*
     * Show attempt for review by student. called from view php
     *
     *
     */
    public function get_full_student_report_data($moduleinstance, $modulecontext, $attempts) {

        // Fetch passage renderer
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


        //For passage rendering
        $extraclasses = "readmode";

         // For Japanese (and later other languages we collapse spaces).
        $collapsespaces = false;
        if ($moduleinstance->ttslanguage == constants::M_LANG_JAJP) {
            $collapsespaces = true;
            $extraclasses .= " collapsespaces";
        }

        //initiate return
        $ret = [];

        // Show an attempt summary if we have more than one attempt and we are not the guest user.
        // This is a chart of the attempts
        if (count($attempts) > 1 && !isguestuser()) {
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

                        // Show the attempt summary. (table data of averages and highest)
                        $ret['attemptssummary'] = $attemptsummarydata;
                        $ret['attemptshowgrades'] = $showgradesinchart;

                        // Show the chart of attempt results
                        $chartdata = utils::fetch_attempt_chartdata($moduleinstance);
                        $renderedchart = $this->fetch_rendered_attemptchart($chartdata, $showgradesinchart);
                        $ret['attemptschart'] =  $renderedchart;
                    }
            }
        }

        // Show feedback summary.
        $ret['generalfeedback'] = $moduleinstance->feedback;


        //render the passage itself (to be marked up in JS)
        if ($havehumaneval || $haveaieval) {
            // We used to distingush between humanpostattempt and machinepostattempt but we simplified it,
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
     * Show the reading passage for print, just a dummy function for now
     * TO DO implement this
     */
    public function fetch_passage_forprint($moduleinstance, $cm, $markeduppassage) {

        $comptest = new \mod_readaloud\comprehensiontest($cm);

        // Passage picture.
        if ($moduleinstance->passagepicture) {
            $zeroitem = new \stdClass();
            $zeroitem->id = 0;
            $picurl = $comptest->fetch_media_url(constants::PASSAGEPICTURE_FILEAREA, $zeroitem);
            $picture = \html_writer::img($picurl, '', ['role' => 'decoration']);
            $picturecontainer = \html_writer::div($picture, constants::M_COMPONENT . '-passage-pic');
        } else {
            $picturecontainer = '';
        }

        // Passage.
        if ($markeduppassage) {
            $passage = $markeduppassage;
        } else {
            $passage = utils::lines_to_brs($moduleinstance->passage);
        }

        $ret = "";
        $ret .= \html_writer::div( $picturecontainer . $passage, constants::M_PASSAGE_CONTAINER . ' '  . constants::M_MSV_MODE . ' '  . constants::M_POSTATTEMPT,
                ['id' => constants::M_PASSAGE_CONTAINER]);

        return $ret;
    }

    /**
     * Get all constants from the constants class.
     *
     * @return array
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
     * Get the data for the view page.
     *
     * @param mixed $moduleinstance The module instance.
     * @param mixed $cm The course module.
     * @param mixed $context The context.
     * @param mixed $canattempt Whether the user can attempt the activity.
     * @param mixed $attempts The attempts.
     * @param mixed $config The configuration.
     * @param mixed $embed The embed option.
     * @return array The view page data.
     */
    public function get_view_page_data(
        $moduleinstance,
        $cm,
        $modulecontext,
        $canattempt,
        $attempts,
        $config,
        $embed,
        $token,
        $latestattempt,
        $latestaigrade,
        $debug
        ) {
        global $CFG;

        // TODO: remove moodle/mod/readaloud/templates/openclosedates.mustache

        // Need to check why this outputs twice.
        $showintro = ($CFG->version < 2022041900) ? $this->show_intro($moduleinstance, $cm) : '';

        // $welcomemessage = $canattempt ? get_string('welcomemenu', constants::M_COMPONENT) :
        // get_string('exceededattempts', constants::M_COMPONENT, $moduleinstance->maxattempts);

        $welcomemessage = get_string('welcomemenu', constants::M_COMPONENT) .
        ($canattempt ? '' : '<br>' . get_string('exceededattempts', constants::M_COMPONENT, $moduleinstance->maxattempts));

        // Render the passage.
        $mode = 'noquiz';
        if ($mode === 'quiz') {
            $modequiz = true;
        }else{
            $modequiz = false;
        }
        $stepsenabled = utils::get_steps_enabled_state($moduleinstance);
        $stepsopen = utils::get_steps_open_state($moduleinstance, $latestattempt);
        $stepscomplete = utils::get_steps_complete_state($moduleinstance, $latestattempt);

        // Render the passage.
        $widgetid = constants::M_RECORDERID . '_opts_9999';
        $opts = [
            'cmid'        => $cm->id,
            'widgetid'    => $widgetid,
            'stepsenabled' => $stepsenabled,
            'stepsopen'    => $stepsopen,
            'stepscomplete' => $stepscomplete,
        ];
        $extraclasses = 'readmode hide'; // TODO: Should we add these directly to template?
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

        // Render the landr html.
        $landr = $this->show_landr($moduleinstance, $token);

        // Fetch data for JS.
        $activityamddata = $this->fetch_activity_amd($cm, $moduleinstance, $token, $embed, $latestattempt);

        // Fetchquiz data for JS.
        $rsquestionrenderer = $this->page->get_renderer(constants::M_COMPONENT, 'rsquestion');
        $quizamddata = $rsquestionrenderer->fetch_quiz_amd($cm, $moduleinstance, $previewquestionid = 0, $canreattempt = false, $embed = 0);

        // Quiz html.
        $rsquestionrenderer = $this->page->get_renderer(\mod_readaloud\constants::M_COMPONENT, 'rsquestion');
        $quizhelper = new quizhelper($cm);
        $quizhtml = $rsquestionrenderer->show_quiz($quizhelper, $moduleinstance, $latestattempt, $cm);

        $currenttime = time();

        $activityisclosed = ($moduleinstance->viewend > 0 && $currenttime > $moduleinstance->viewend);
        $activitynotopenyet = ($moduleinstance->viewstart > 0 && $currenttime < $moduleinstance->viewstart);
        $canpreview = has_capability('mod/readaloud:preview', $modulecontext);
        $closedate = $moduleinstance->viewend > 0 ? $moduleinstance->viewend : null;
        $feedback = !empty($moduleinstance->feedback) ? $moduleinstance->feedback : null;
        $hasopenclosedates = $moduleinstance->viewend > 0 || $moduleinstance->viewstart > 0;
        $instructions = !empty($moduleinstance->welcome) ? $moduleinstance->welcome : null;
        $modevisibility = $this->get_mode_visibility($moduleinstance, $canattempt, $latestattempt);
        $opendate = $moduleinstance->viewstart > 0 ? $moduleinstance->viewstart : null;
        $smallreport = $this->get_smallreport_data($moduleinstance, $modulecontext, $cm, $attempts, $latestattempt, $latestaigrade);
        $wheretonext = $this->show_wheretonext($moduleinstance, $embed);

        return array_merge([
            'activityamddata' => $activityamddata,
            'attempts' => $attempts,
            'canattempt' => $modevisibility['canattempt'],
            'canshadowattempt' => $modevisibility['canshadowattempt'],
            'enablenoshadow' => $modevisibility['enablenoshadow'],
            'hasaudiobreaks' => $modevisibility['hasaudiobreaks'],
            'embed' => $embed,
            'steps' => constants::STEPS,
            'stepsenabled' => $stepsenabled,
            'stepsopen' => $stepsopen,
            'stepscomplete'  => $stepscomplete,
            'error' => false, // cannot find any code calling show_error.
            'feedback' => $feedback,
            'landr' => $landr,
            'instructions' => $instructions,
            'mode' => null,
            'modequiz' => $modequiz,
            'moduleinstance' => $moduleinstance,
            'openclosedates' => [
                'activityisclosed' => $activityisclosed,
                'activitynotopenyet' => $activitynotopenyet,
                'canpreview' => $canpreview,
                'closedate' => $closedate,
                'hasopenclosedates' => $hasopenclosedates,
                'opendate' => $opendate,
            ],
            'passagehtml' => isset($passagehtml) ? $passagehtml : null,
            'progress' => true, // TEMP.
            'quizamddata' => isset($quizamddata) ? $quizamddata : null,
            'quizhtml' => isset($quizhtml) ? $quizhtml : null,
            'recorder' => $recorder,
            'showintro' => $showintro,
            'smallreport' => $smallreport,
            'stopandplay' => true, // TEMP.
            'welcomemessage' => $welcomemessage,
            'wheretonext' => $wheretonext,
        ], $this->get_all_constants());
    }
}
