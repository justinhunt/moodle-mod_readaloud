<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace mod_readaloud\output;

use \mod_readaloud\constants;
use \mod_readaloud\utils;

class renderer extends \plugin_renderer_base {

    /**
     * Returns the header for the module
     *
     * @param mod $instance
     * @param string $currenttab current tab that is shown.
     * @param int    $item id of the anything that needs to be displayed.
     * @param string $extrapagetitle String to append to the page title.
     * @return string
     */
    public function header($moduleinstance, $cm, $currenttab = '', $itemid = null, $extrapagetitle = null) {
        global $CFG;

        $activityname = format_string($moduleinstance->name, true, $moduleinstance->course);
        if (empty($extrapagetitle)) {
            $title = $this->page->course->shortname.": ".$activityname;
        } else {
            $title = $this->page->course->shortname.": ".$activityname.": ".$extrapagetitle;
        }

        // Build the buttons
        $context = \context_module::instance($cm->id);

        /// Header setup
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
        $output = $this->output->header();

        if (has_capability('mod/readaloud:manageattempts', $context)) {
            //   $output .= $this->output->heading_with_help($activityname, 'overview', constants::M_COMPONENT);

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot.'/mod/readaloud/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        } else {
            $output .= $this->output->heading($activityname);
        }


        return $output;
    }

    /**
     * Return HTML to display limited header
     */
    public function notabsheader(){
        return $this->output->header();
    }


    /**
     *
     */
    public function reattemptbutton($moduleinstance){

        $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/view.php',
            array('n'=>$moduleinstance->id,'retake'=>1)),get_string('reattempt',constants::M_COMPONENT));

        $ret = \html_writer::div($button ,constants::M_CLASS  . '_afterattempt_cont');
        return $ret;

    }

    /**
     *
     */
    public function show_wheretonext($moduleinstance){

        $nextactivity = utils::fetch_next_activity($moduleinstance->activitylink);
        //show activity link if we are up to it
        if ($nextactivity->url) {
            $button= $this->output->single_button($nextactivity->url,$nextactivity->label);
        //else lets show a back to top link
        }else {
            $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/view.php',
                array('n' => $moduleinstance->id)), get_string('backtotop', constants::M_COMPONENT));
        }
        $ret = \html_writer::div($button ,constants::M_WHERETONEXT_CONTAINER);
        return $ret;

    }

    /**
     *
     */
    public function show_machineregradeallbutton($moduleinstance){
        $options=[];
        $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/gradesadmin.php',
            array('n'=>$moduleinstance->id, 'action'=>'machineregradeall')),get_string('machineregradeall',constants::M_COMPONENT),'post',$options);

        $ret = \html_writer::div($button ,constants::M_GRADESADMIN_CONTAINER);
        return $ret;
    }

    /**
     *
     */
    public function show_pushmachinegrades($moduleinstance){

        $sectiontitle= get_string("pushmachinegrades",constants::M_COMPONENT);
        $heading = $this->output->heading($sectiontitle, 4);

        if(utils::can_transcribe($moduleinstance) && $moduleinstance->machgrademethod==constants::MACHINEGRADE_MACHINE){
            $options=[];
        }else{
            $options=array('disabled'=>'disabled');
        }
        $button = $this->output->single_button(new \moodle_url(constants::M_URL . '/gradesadmin.php',
            array('n'=>$moduleinstance->id, 'action'=>'pushmachinegrades')),get_string('pushmachinegrades',constants::M_COMPONENT),'post',$options);

        $ret = \html_writer::div($heading . $button ,constants::M_GRADESADMIN_CONTAINER);
        return $ret;
    }

    /**
     * @param array an array of mistranscription objects (passageindex, passageword, mistranscription summary)
     * @return string an html table
     */
    public function show_all_mistranscriptions($items){

            global $CFG;

            //set up our table
            $tableattributes = array('class' => 'generaltable ' . constants::M_CLASS . '_table');

            $htmltable = new \html_table();
            $tableid = \html_writer::random_id(constants::M_COMPONENT);
            $htmltable->id = $tableid;
            $htmltable->attributes = $tableattributes;

            $head=array(get_string('passageindex',constants::M_COMPONENT),
                get_string('passageword',constants::M_COMPONENT),
                get_string('mistrans_count',constants::M_COMPONENT),
                get_string('mistranscriptions',constants::M_COMPONENT));

            $htmltable->head = $head;
            $rowcount=0;
            $total_mistranscriptions=0;
            foreach ($items as $row) {
                //if this was not a mistranscription, skip
                if(!$row->mistranscriptions){continue;}
                $rowcount++;
                $htr = new \html_table_row();

                $cell = new \html_table_cell($row->passageindex);
                $cell->attributes = array('class' => constants::M_CLASS . '_cell_passageindex');
                $htr->cells[] = $cell;

                $cell = new \html_table_cell($row->passageword);
                $cell->attributes = array('class' => constants::M_CLASS . '_cell_passageword');
                $htr->cells[] = $cell;

                $showmistranscriptions = "";
                $mistrans_count = 0;
                foreach($row->mistranscriptions as $badword=>$count){
                    if($showmistranscriptions != ""){$showmistranscriptions .= " | ";}
                    $showmistranscriptions .= $badword . "(" . $count . ")";
                    $mistrans_count+=$count;
                }
                $total_mistranscriptions+=$mistrans_count;

                $cell = new \html_table_cell($mistrans_count);
                $cell->attributes = array('class' => constants::M_CLASS . '_cell_mistrans_count');
                $htr->cells[] = $cell;

                $cell = new \html_table_cell($showmistranscriptions);
                $cell->attributes = array('class' => constants::M_CLASS . '_cell_mistranscriptions');
                $htr->cells[] = $cell;


                $htmltable->data[] = $htr;
            }
            $tabletitle= get_string("mistranscriptions_summary",constants::M_COMPONENT);
            $html = $this->output->heading($tabletitle, 4);
            if ($rowcount==0) {
                $html .= get_string("nomistranscriptions",constants::M_COMPONENT);
            }else{
                $html .= \html_writer::tag('span',get_string("total_mistranscriptions",
                    constants::M_COMPONENT,$total_mistranscriptions),array('class'=>constants::M_CLASS . '_totalmistranscriptions'));
                $html .= \html_writer::table($htmltable);

                //set up datatables
                $tableprops = new \stdClass();
                $opts =Array();
                $opts['tableid']=$tableid;
                $opts['tableprops']=$tableprops;
                $this->page->requires->js_call_amd( constants::M_COMPONENT. "/datatables", 'init', array($opts));
                $this->page->requires->css( new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));

            }
            return $html;
    }

    /**
     *
     */
    public function show_currenterrorestimate($errorestimate){
        $message = get_string("currenterrorestimate",constants::M_COMPONENT,$errorestimate);
        $ret = \html_writer::div($message ,constants::M_GRADESADMIN_CONTAINER);
        return $ret;

    }

    /**
     *
     */
    public function exceededattempts($moduleinstance){
        $message = get_string("exceededattempts",constants::M_COMPONENT,$moduleinstance->maxattempts);
        $ret = \html_writer::div($message ,constants::M_CLASS  . '_afterattempt_cont');
        return $ret;

    }

    public function show_ungradedyet(){
        $message = get_string("notgradedyet",constants::M_COMPONENT);
        $ret = \html_writer::div($message ,constants::M_CLASS  . '_ungraded_cont');
        return $ret;
    }

    /**
     *  Show grades admin heading
     */
    public function show_gradesadmin_heading($showtitle,$showinstructions) {
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .= \html_writer::div($showinstructions ,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext);
        return $ret;
    }



    /**
     *  Show instructions/welcome
     */
    public function show_welcome($showtext, $showtitle) {
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .= \html_writer::div($showtext ,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::M_INSTRUCTIONS_CONTAINER,array('id'=>constants::M_INSTRUCTIONS_CONTAINER));
        return $ret;
    }

    /**
     * Show the introduction text is as set in the activity description
     */
    public function show_intro($readaloud,$cm){
        $ret = "";
        if (trim(strip_tags($readaloud->intro))) {
            $ret .= $this->output->box_start('mod_introbox');
            $ret .= format_module_intro('readaloud', $readaloud, $cm->id);
            $ret .= $this->output->box_end();
        }
        return $ret;
    }

    /**
     * Show the reading passage after the attempt, basically set it to display on load and give it a background color
     */
    public function show_passage_postattempt($readaloud){
        $ret = "";
        $displaypassage =  utils::lines_to_brs($readaloud->passage);
        $ret .= \html_writer::div( $displaypassage ,constants::M_PASSAGE_CONTAINER . ' ' . constants::M_POSTATTEMPT,
            array('id'=>constants::M_PASSAGE_CONTAINER));
        return $ret;
    }

    /**
     * Show the reading passage
     */
    public function show_passage($readaloud,$cm){

        $ret = "";
        $displaypassage =  utils::lines_to_brs($readaloud->passage);
        $ret .= \html_writer::div( $displaypassage ,constants::M_PASSAGE_CONTAINER,
            array('id'=>constants::M_PASSAGE_CONTAINER));
        return $ret;
    }

    /**
     *  Show a progress circle overlay while uploading
     */
    public function show_progress($readaloud,$cm){
        $hider =  \html_writer::div('',constants::M_HIDER,array('id'=>constants::M_HIDER));
        $message =  \html_writer::tag('h4',get_string('processing',constants::M_COMPONENT),array());
        $spinner =  \html_writer::tag('i','',array('class'=>'fa fa-spinner fa-5x fa-spin'));
        $progressdiv = \html_writer::div($message . $spinner ,constants::M_PROGRESS_CONTAINER,
            array('id'=>constants::M_PROGRESS_CONTAINER));
        $ret = $hider . $progressdiv;
        return $ret;
    }

    public function show_humanevaluated_message(){
        $displaytext = get_string('humanevaluatedmessage',constants::M_COMPONENT);
        $ret= \html_writer::div($displaytext,constants::M_EVALUATED_MESSAGE,array('id'=>constants::M_EVALUATED_MESSAGE));
        return $ret;
    }

    public function show_machineevaluated_message(){
        $displaytext = get_string('machineevaluatedmessage',constants::M_COMPONENT);
        $ret= \html_writer::div($displaytext,constants::M_EVALUATED_MESSAGE,array('id'=>constants::M_EVALUATED_MESSAGE));
        return $ret;
    }

    /**
     * Show the feedback set in the activity settings
     */
    public function show_feedback($readaloud,$showtitle){
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .=  \html_writer::div($readaloud->feedback,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::M_FEEDBACK_CONTAINER,array('id'=>constants::M_FEEDBACK_CONTAINER));
        return $ret;
    }

    /**
     * Show the feedback set in the activity settings
     */
    public function show_feedback_postattempt($readaloud,$showtitle){
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .=  \html_writer::div($readaloud->feedback,constants::M_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::M_FEEDBACK_CONTAINER . ' ' . constants::M_POSTATTEMPT,array('id'=>constants::M_FEEDBACK_CONTAINER));
        return $ret;
    }

    /**
     * Show error (but when?)
     */
    public function show_error($readaloud,$cm){
        $displaytext = $this->output->box_start();
        $displaytext .= $this->output->heading(get_string('errorheader',constants::M_COMPONENT), 3, 'main');
        $displaytext .=  \html_writer::div(get_string('uploadconverterror',constants::M_COMPONENT),'',array());
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::M_ERROR_CONTAINER,array('id'=>constants::M_ERROR_CONTAINER));
        return $ret;
    }

    /**
     * The html part of the recorder (js is in the fetch_activity_amd)
     */
    public function show_recorder($moduleinstance, $token,$debug=false){
        global $CFG;

        //recorder
        //=======================================
        $hints = new \stdClass();
        $hints->allowearlyexit = $moduleinstance->allowearlyexit;
        $string_hints = base64_encode (json_encode($hints));
        $can_transcribe = \mod_readaloud\utils::can_transcribe($moduleinstance);
        $transcribe = $can_transcribe  ? $moduleinstance->transcriber : "0";
        $recorderdiv= \html_writer::div('', constants::M_CLASS  . '_center',
            array('id'=>constants::M_RECORDERID,
                'data-id'=>'therecorder',
                'data-parent'=>$CFG->wwwroot,
                'data-localloading'=>'auto',
                'data-localloader'=>'/mod/readaloud/poodllloader.html',
                'data-media'=>"audio",
                'data-appid'=>constants::M_COMPONENT,
                'data-type'=> $debug ? "upload" : "readaloud",
                'data-width'=> $debug ? "500":"360",
                'data-height'=>$debug ? "500":"210",
                //'data-iframeclass'=>"letsberesponsive",
                'data-updatecontrol'=>constants::M_UPDATE_CONTROL,
                'data-timelimit'=> $moduleinstance->timelimit,
                'data-transcode'=>"1",
                'data-transcribe'=>$transcribe,
                'data-language'=>$moduleinstance->ttslanguage,
                'data-expiredays'=>$moduleinstance->expiredays,
                'data-region'=>$moduleinstance->region,
                'data-fallback'=>'warning',
                'data-hints'=>$string_hints,
                'data-token'=>$token //localhost
                //'data-token'=>"643eba92a1447ac0c6a882c85051461a" //cloudpoodll
            )
        );
        $containerdiv= \html_writer::div($recorderdiv,constants::M_RECORDER_CONTAINER . " " . constants::M_CLASS  . '_center',
            array('id'=>constants::M_RECORDER_CONTAINER));
        //=======================================


        $recordingdiv = \html_writer::div($containerdiv ,constants::M_RECORDING_CONTAINER);

        //prepare output
        $ret = "";
        $ret .=$recordingdiv;
        //return it
        return $ret;
    }


    function fetch_activity_amd($cm, $moduleinstance){
        global $USER;
        //any html we want to return to be sent to the page
        $ret_html = '';

        //here we set up any info we need to pass into javascript

        $recopts =Array();
        //recorder html ids
        $recopts['recorderid'] = constants::M_RECORDERID;
        $recopts['recordingcontainer'] = constants::M_RECORDING_CONTAINER;
        $recopts['recordercontainer'] = constants::M_RECORDER_CONTAINER;

        //activity html ids
        $recopts['passagecontainer'] = constants::M_PASSAGE_CONTAINER;
        $recopts['instructionscontainer'] = constants::M_INSTRUCTIONS_CONTAINER;
        $recopts['recordbuttoncontainer'] =constants::M_RECORD_BUTTON_CONTAINER;
        $recopts['startbuttoncontainer'] =constants::M_START_BUTTON_CONTAINER;
        $recopts['hider']=constants::M_HIDER;
        $recopts['progresscontainer'] = constants::M_PROGRESS_CONTAINER;
        $recopts['feedbackcontainer'] = constants::M_FEEDBACK_CONTAINER;
        $recopts['wheretonextcontainer'] = constants::M_WHERETONEXT_CONTAINER;
        $recopts['errorcontainer'] = constants::M_ERROR_CONTAINER;
        $recopts['allowearlyexit'] =  $moduleinstance->allowearlyexit ? true :false;


        //we need an update control tp hold the recorded filename, and one for draft item id
        $ret_html = $ret_html . \html_writer::tag('input', '', array('id' => constants::M_UPDATE_CONTROL, 'type' => 'hidden'));


        //this inits the M.mod_readaloud thingy, after the page has loaded.
        //we put the opts in html on the page because moodle/AMD doesn't like lots of opts in js
        //convert opts to json
        $jsonstring = json_encode($recopts);
        $widgetid = constants::M_RECORDERID . '_opts_9999';
        $opts_html = \html_writer::tag('input', '', array('id' => 'amdopts_' . $widgetid, 'type' => 'hidden', 'value' => $jsonstring));

        //the recorder div
        $ret_html = $ret_html . $opts_html;

        $opts=array('cmid'=>$cm->id,'widgetid'=>$widgetid);
        $this->page->requires->js_call_amd("mod_readaloud/activitycontroller", 'init', array($opts));
        $this->page->requires->strings_for_js(array('gotnosound','done','beginreading'),constants::M_COMPONENT);

        //these need to be returned and echo'ed to the page
        return $ret_html;
    }

}