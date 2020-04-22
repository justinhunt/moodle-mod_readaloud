<?php


/**
 * External class.
 *
 * @package mod_readaloud
 * @author  Justin Hunt - Poodll.com
 */

use \mod_readaloud\utils;
use \mod_readaloud\diff;

class mod_readaloud_external extends external_api {


    public static function submit_regular_attempt_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT),
                'filename' => new external_value(PARAM_TEXT),
                'rectime' => new external_value(PARAM_INT)
        ]);
    }

    public static function submit_regular_attempt($cmid,$filename,$rectime) {
        global $DB;

        $params = self::validate_parameters(self::submit_regular_attempt_parameters(),
                array('cmid'=>$cmid,'filename'=>$filename,'rectime'=>$rectime));


        $cm = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $readaloud = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);
        $modulecontext = context_module::instance($cm->id);

        //make database items and adhoc tasks
        $success = false;
        $message = '';
        $attemptid = utils::create_attempt($filename, $rectime, $readaloud);
        if ($attemptid) {
            if (\mod_readaloud\utils::can_transcribe($readaloud)) {
                $success = utils::register_aws_task($readaloud->id, $attemptid, $modulecontext->id);
                if (!$success) {
                    $message = "Unable to create adhoc task to fetch transcriptions";
                }
            } else {
                $success = true;
            }
        } else {
            $message = "Unable to add update database with submission";
        }

        //handle return to Moodle
        $ret = new stdClass();
        if ($success) {
            $ret->success = true;
        } else {
            $ret->success = false;
            $ret->message = $message;
        }

        return json_encode($ret);
    }

    public static function submit_regular_attempt_returns() {
        return new external_value(PARAM_RAW);
    }


    //---------------------------------------
    public static function compare_passage_to_transcript_parameters() {
        return new external_function_parameters([
                'language' => new external_value(PARAM_TEXT),
                'passage' => new external_value(PARAM_TEXT),
                'transcript' => new external_value(PARAM_TEXT),
                'alternatives' => new external_value(PARAM_RAW)
        ]);
    }

    public static function compare_passage_to_transcript($language,$passage,$transcript, $alternatives) {
        global $DB;

        //turn the passage and transcript into an array of words
        $passagebits = diff::fetchWordArray($passage);
        $alternatives = diff::fetchAlternativesArray($alternatives);
        $transcriptbits = diff::fetchWordArray($transcript);
        $wildcards = diff::fetchWildcardsArray($alternatives);

        //fetch sequences of transcript/passage matched words
        // then prepare an array of "differences"
        $passagecount = count($passagebits);
        $transcriptcount = count($transcriptbits);
        $sequences = diff::fetchSequences($passagebits, $transcriptbits, $alternatives, $language);
        //fetch diffs
        $debug=false;
        $diffs = diff::fetchDiffs($sequences, $passagecount, $transcriptcount, $debug);
        $diffs = diff::applyWildcards($diffs, $passagebits, $wildcards);


        //from the array of differences build error data, match data, markers, scores and metrics
        $errors = new \stdClass();
        $currentword = 0;

        //loop through diffs
        // (could do a for loop here .. since diff count = passage words count for now index is $currentword
        foreach ($diffs as $diff) {
            $currentword++;
            switch ($diff[0]) {
                case Diff::UNMATCHED:
                    //we collect error info so we can count and display them on passage
                    $error = new \stdClass();
                    $error->word = $passagebits[$currentword - 1];
                    $error->wordnumber = $currentword;
                    $errors->{$currentword} = $error;
                    break;

                case Diff::MATCHED:
                    //do need to do anything here
                    break;

                default:
                    //do nothing
                    //should never get here
            }
        }

        //finalise and serialise session errors
        $sessionerrors = json_encode($errors);

        return $sessionerrors;

    }
    public static function compare_passage_to_transcript_returns() {
        return new external_value(PARAM_RAW);
    }
    //---------------------------------------

    public static function submit_streaming_attempt_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT),
                'filename' => new external_value(PARAM_TEXT),
                'rectime' => new external_value(PARAM_INT),
                'awsresults' => new external_value(PARAM_RAW),
        ]);
    }

    public static function submit_streaming_attempt($cmid,$filename,$rectime, $awsresults) {
        global $DB;

        $params = self::validate_parameters(self::submit_streaming_attempt_parameters(),
                array('cmid'=>$cmid,'filename'=>$filename,'rectime'=>$rectime,'awsresults'=> $awsresults));
        extract($params);

        $cm = get_coursemodule_from_id('readaloud', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $readaloud = $DB->get_record('readaloud', array('id' => $cm->instance), '*', MUST_EXIST);
        $modulecontext = context_module::instance($cm->id);

        //make database items and adhoc tasks
        $success = false;
        $message = '';
        $attemptid = utils::create_attempt($filename, $rectime, $readaloud);
        if (!$attemptid) {
            $message = "Unable to add update database with submission";
        }else{
            $success=true;
        }

        if($success){
            $processed_awsresults = utils::parse_streaming_results($awsresults);
            $aigrade = new \mod_readaloud\aigrade($attemptid, $modulecontext->id,$processed_awsresults);
            if ($aigrade) {
                if (!$aigrade->has_attempt()) {
                    $message ='No attempt could be found when processing transcript';
                    $success=false;
                }

                if (!$aigrade->has_transcripts()) {
                    $message ='Processing of transcript failed';
                    $success=false;
                }else{
                    $success=true;
                }

            } else {
                $message ='Unable to create AI grade for some reason';
                $success=false;
            }
        }

        //handle return to Moodle
        $ret = new stdClass();
        if ($success) {
            $ret->success = true;
        } else {
            $ret->success = false;
            $ret->message = $message;
        }

        return json_encode($ret);
    }

    public static function submit_streaming_attempt_returns() {
        return new external_value(PARAM_RAW);
    }

    //---------------------------------------

    public static function fetch_streaming_diffs_parameters() {
        return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT),
                'awsresults' => new external_value(PARAM_RAW),
        ]);
    }

    public static function fetch_streaming_diffs($cmid, $awsresults) {
        global $DB;

        $params = self::validate_parameters(self::fetch_streaming_diffs_parameters(), array('cmid'=>$cmid,'awsresults'=> $awsresults));
        extract($params);


        return true;
    }

    public static function fetch_streaming_diffs_returns() {
        return new external_value(PARAM_RAW);
    }
}
