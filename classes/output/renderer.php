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

        if (has_capability('mod/readaloud:manage', $context)) {
            //   $output .= $this->output->heading_with_help($activityname, 'overview', constants::MOD_READALOUD_LANG);

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

        $button = $this->output->single_button(new \moodle_url(constants::MOD_READALOUD_URL . '/view.php',
            array('n'=>$moduleinstance->id,'retake'=>1)),get_string('reattempt',constants::MOD_READALOUD_FRANKY));

        $ret = \html_writer::div($button ,constants::MOD_READALOUD_CLASS  . '_afterattempt_cont');
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
            $button = $this->output->single_button(new \moodle_url(constants::MOD_READALOUD_URL . '/view.php',
                array('n' => $moduleinstance->id)), get_string('backtotop', constants::MOD_READALOUD_FRANKY));
        }
        $ret = \html_writer::div($button ,constants::MOD_READALOUD_WHERETONEXT_CONTAINER);
        return $ret;

    }

    /**
     *
     */
    public function show_machineregradeallbutton($moduleinstance){
        $options=[];
        $button = $this->output->single_button(new \moodle_url(constants::MOD_READALOUD_URL . '/gradesadmin.php',
            array('n'=>$moduleinstance->id, 'action'=>'machineregradeall')),get_string('machineregradeall',constants::MOD_READALOUD_FRANKY),'post',$options);

        $ret = \html_writer::div($button ,constants::MOD_READALOUD_GRADESADMIN_CONTAINER);
        return $ret;
    }

    /**
     *
     */
    public function show_pushmachinegradesbutton($moduleinstance){

        if(utils::can_transcribe(moduleinstance) && $moduleinstance->machgrademethod==constants::MACHINEGRADE_MACHINE){
            $options=[];
        }else{
            $options=array('disabled'=>'disabled');
        }
        $button = $this->output->single_button(new \moodle_url(constants::MOD_READALOUD_URL . '/gradesadmin.php',
            array('n'=>$moduleinstance->id, 'action'=>'pushmachinegrades')),get_string('pushmachinegrades',constants::MOD_READALOUD_FRANKY),'post',$options);

        $ret = \html_writer::div($button ,constants::MOD_READALOUD_GRADESADMIN_CONTAINER);
        return $ret;
    }

    /**
     *
     */
    public function show_currenterrorestimate($errorestimate){
        $message = get_string("currenterrorestimate",constants::MOD_READALOUD_LANG,$errorestimate);
        $ret = \html_writer::div($message ,constants::MOD_READALOUD_GRADESADMIN_CONTAINER);
        return $ret;

    }

    /**
     *
     */
    public function exceededattempts($moduleinstance){
        $message = get_string("exceededattempts",constants::MOD_READALOUD_LANG,$moduleinstance->maxattempts);
        $ret = \html_writer::div($message ,constants::MOD_READALOUD_CLASS  . '_afterattempt_cont');
        return $ret;

    }

    public function show_ungradedyet(){
        $message = get_string("notgradedyet",constants::MOD_READALOUD_LANG);
        $ret = \html_writer::div($message ,constants::MOD_READALOUD_CLASS  . '_ungraded_cont');
        return $ret;
    }

    /**
     *  Show grades admin heading
     */
    public function show_gradesadmin_heading($showtitle,$showinstructions) {
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .= \html_writer::div($showinstructions ,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext);
        return $ret;
    }



    /**
     *  Show instructions/welcome
     */
    public function show_welcome($showtext, $showtitle) {
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .= \html_writer::div($showtext ,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::MOD_READALOUD_INSTRUCTIONS_CONTAINER,array('id'=>constants::MOD_READALOUD_INSTRUCTIONS_CONTAINER));
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
        $ret .= \html_writer::div( $readaloud->passage ,constants::MOD_READALOUD_PASSAGE_CONTAINER . ' ' . constants::MOD_READALOUD_POSTATTEMPT,
            array('id'=>constants::MOD_READALOUD_PASSAGE_CONTAINER));
        return $ret;
    }

    /**
     * Show the reading passage
     */
    public function show_passage($readaloud,$cm){

        $ret = "";
        $ret .= \html_writer::div( $readaloud->passage ,constants::MOD_READALOUD_PASSAGE_CONTAINER,
            array('id'=>constants::MOD_READALOUD_PASSAGE_CONTAINER));
        return $ret;
    }

    /**
     *  Show a progress circle overlay while uploading
     */
    public function show_progress($readaloud,$cm){
        $hider =  \html_writer::div('',constants::MOD_READALOUD_HIDER,array('id'=>constants::MOD_READALOUD_HIDER));
        $message =  \html_writer::tag('h4',get_string('processing',constants::MOD_READALOUD_LANG),array());
        $spinner =  \html_writer::tag('i','',array('class'=>'fa fa-spinner fa-5x fa-spin'));
        $progressdiv = \html_writer::div($message . $spinner ,constants::MOD_READALOUD_PROGRESS_CONTAINER,
            array('id'=>constants::MOD_READALOUD_PROGRESS_CONTAINER));
        $ret = $hider . $progressdiv;
        return $ret;
    }

    public function show_humanevaluated_message(){
        $displaytext = get_string('humanevaluatedmessage',constants::MOD_READALOUD_LANG);
        $ret= \html_writer::div($displaytext,constants::MOD_READALOUD_EVALUATED_MESSAGE,array('id'=>constants::MOD_READALOUD_EVALUATED_MESSAGE));
        return $ret;
    }

    public function show_machineevaluated_message(){
        $displaytext = get_string('machineevaluatedmessage',constants::MOD_READALOUD_LANG);
        $ret= \html_writer::div($displaytext,constants::MOD_READALOUD_EVALUATED_MESSAGE,array('id'=>constants::MOD_READALOUD_EVALUATED_MESSAGE));
        return $ret;
    }

    /**
     * Show the feedback set in the activity settings
     */
    public function show_feedback($readaloud,$showtitle){
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .=  \html_writer::div($readaloud->feedback,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::MOD_READALOUD_FEEDBACK_CONTAINER,array('id'=>constants::MOD_READALOUD_FEEDBACK_CONTAINER));
        return $ret;
    }

    /**
     * Show the feedback set in the activity settings
     */
    public function show_feedback_postattempt($readaloud,$showtitle){
        $thetitle =  $this->output->heading($showtitle, 3, 'main');
        $displaytext =  \html_writer::div($thetitle ,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_start();
        $displaytext .=  \html_writer::div($readaloud->feedback,constants::MOD_READALOUD_CLASS  . '_center');
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::MOD_READALOUD_FEEDBACK_CONTAINER . ' ' . constants::MOD_READALOUD_POSTATTEMPT,array('id'=>constants::MOD_READALOUD_FEEDBACK_CONTAINER));
        return $ret;
    }

    /**
     * Show error (but when?)
     */
    public function show_error($readaloud,$cm){
        $displaytext = $this->output->box_start();
        $displaytext .= $this->output->heading(get_string('errorheader',constants::MOD_READALOUD_LANG), 3, 'main');
        $displaytext .=  \html_writer::div(get_string('uploadconverterror',constants::MOD_READALOUD_LANG),'',array());
        $displaytext .= $this->output->box_end();
        $ret= \html_writer::div($displaytext,constants::MOD_READALOUD_ERROR_CONTAINER,array('id'=>constants::MOD_READALOUD_ERROR_CONTAINER));
        return $ret;
    }

    /**
     * The html part of the recorder (js is in the fetch_activity_amd)
     */
    public function show_recorder($moduleinstance, $token){
        global $CFG;

        //recorder
        //=======================================
        $hints = new \stdClass();
        $hints->allowearlyexit = $moduleinstance->allowearlyexit;
        $string_hints = base64_encode (json_encode($hints));
        $can_transcribe = \mod_readaloud\utils::can_transcribe($moduleinstance);
        $transcribe = $can_transcribe  ? "1" : "0";
        $recorderdiv= \html_writer::div('', constants::MOD_READALOUD_CLASS  . '_center',
            array('id'=>constants::MOD_READALOUD_RECORDERID,
                'data-id'=>'therecorder',
                'data-parent'=>$CFG->wwwroot,
                'data-localloading'=>'auto',
                'data-localloader'=>'/mod/readaloud/poodllloader.html',
                'data-media'=>"audio",
                'data-appid'=>"readaloud",
                'data-type'=>"readaloud",
                'data-width'=>"360",
                'data-height'=>"210",
                //'data-iframeclass'=>"letsberesponsive",
                'data-updatecontrol'=>constants::MOD_READALOUD_UPDATE_CONTROL,
                'data-timelimit'=> $moduleinstance->timelimit,
                'data-transcode'=>"1",
                'data-transcribe'=>$transcribe,
                'data-transcribelanguage'=>$moduleinstance->ttslanguage,
                'data-expiredays'=>$moduleinstance->expiredays,
                'data-region'=>$moduleinstance->region,
                'data-fallback'=>'warning',
                'data-hints'=>$string_hints,
                'data-token'=>$token //localhost
                //'data-token'=>"643eba92a1447ac0c6a882c85051461a" //cloudpoodll
            )
        );
        $containerdiv= \html_writer::div($recorderdiv,constants::MOD_READALOUD_RECORDER_CONTAINER . " " . constants::MOD_READALOUD_CLASS  . '_center',
            array('id'=>constants::MOD_READALOUD_RECORDER_CONTAINER));
        //=======================================


        $recordingdiv = \html_writer::div($containerdiv ,constants::MOD_READALOUD_RECORDING_CONTAINER);

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
        $recopts['recorderid'] = constants::MOD_READALOUD_RECORDERID;
        $recopts['recordingcontainer'] = constants::MOD_READALOUD_RECORDING_CONTAINER;
        $recopts['recordercontainer'] = constants::MOD_READALOUD_RECORDER_CONTAINER;

        //activity html ids
        $recopts['passagecontainer'] = constants::MOD_READALOUD_PASSAGE_CONTAINER;
        $recopts['instructionscontainer'] = constants::MOD_READALOUD_INSTRUCTIONS_CONTAINER;
        $recopts['recordbuttoncontainer'] =constants::MOD_READALOUD_RECORD_BUTTON_CONTAINER;
        $recopts['startbuttoncontainer'] =constants::MOD_READALOUD_START_BUTTON_CONTAINER;
        $recopts['hider']=constants::MOD_READALOUD_HIDER;
        $recopts['progresscontainer'] = constants::MOD_READALOUD_PROGRESS_CONTAINER;
        $recopts['feedbackcontainer'] = constants::MOD_READALOUD_FEEDBACK_CONTAINER;
        $recopts['wheretonextcontainer'] = constants::MOD_READALOUD_WHERETONEXT_CONTAINER;
        $recopts['errorcontainer'] = constants::MOD_READALOUD_ERROR_CONTAINER;
        $recopts['allowearlyexit'] =  $moduleinstance->allowearlyexit ? true :false;


        //we need an update control tp hold the recorded filename, and one for draft item id
        $ret_html = $ret_html . \html_writer::tag('input', '', array('id' => constants::MOD_READALOUD_UPDATE_CONTROL, 'type' => 'hidden'));


        //this inits the M.mod_readaloud thingy, after the page has loaded.
        //we put the opts in html on the page because moodle/AMD doesn't like lots of opts in js
        //convert opts to json
        $jsonstring = json_encode($recopts);
        $widgetid = constants::MOD_READALOUD_RECORDERID . '_opts_9999';
        $opts_html = \html_writer::tag('input', '', array('id' => 'amdopts_' . $widgetid, 'type' => 'hidden', 'value' => $jsonstring));

        //the recorder div
        $ret_html = $ret_html . $opts_html;

        $opts=array('cmid'=>$cm->id,'widgetid'=>$widgetid);
        $this->page->requires->js_call_amd("mod_readaloud/activitycontroller", 'init', array($opts));
        $this->page->requires->strings_for_js(array('gotnosound','recordnameschool','done','beginreading'),constants::MOD_READALOUD_LANG);

        //these need to be returned and echo'ed to the page
        return $ret_html;
    }

}