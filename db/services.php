<?php
/**
 * Services definition.
 *
 * @package mod_readaloud
 * @author  Justin Hunt - poodll.com
 */

$functions = array(

    'mod_readaloud_submit_regular_attempt' => array(
            'classname'   => 'mod_readaloud_external',
            'methodname'  => 'submit_regular_attempt',
            'description' => 'submits regular attempt.',
            'capabilities'=> 'mod/readaloud:view',
            'type'        => 'write',
            'ajax'        => true,
    ),

    'mod_readaloud_submit_streaming_attempt' => array(
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'submit_streaming_attempt',
        'description' => 'submits streaming attempt.',
        'capabilities'=> 'mod/readaloud:view',
        'type'        => 'write',
        'ajax'        => true,
    ),

    'mod_readaloud_fetch_streaming_diffs' => array(
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'fetch_streaming_diffs',
        'description' => 'Fetches diffs for streaming transcription results',
        'capabilities'=> 'mod/readaloud:view',
        'type'        => 'read',
        'ajax'        => true,
    ),

    'mod_readaloud_compare_passage_to_transcript' => array(
            'classname'   => 'mod_readaloud_external',
            'methodname'  => 'compare_passage_to_transcript',
            'description' => 'compares passage to transcript and returns diffs',
            'capabilities'=> 'mod/readaloud:view',
            'type'        => 'read',
            'ajax'        => true,
    ),

    'mod_readaloud_check_for_results' => array(
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'check_for_results',
                'description' => 'checks if results are in yet',
                'capabilities'=> 'mod/readaloud:view',
                'type'        => 'read',
                'ajax'        => true,
    ),

    
    'mod_readaloud_delete_item' => array(
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'delete_item',
        'description' => 'delete item.',
        'capabilities' => 'mod/readaloud:managequestions',
        'type'        => 'write',
        'ajax'        => true,
),

'mod_readaloud_move_item' => array(
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'move_item',
        'description' => 'move item.',
        'capabilities' => 'mod/readaloud:managequestions',
        'type'        => 'write',
        'ajax'        => true,
),

'mod_readaloud_duplicate_item' => array(
    'classname'   => 'mod_readaloud_external',
    'methodname'  => 'duplicate_item',
    'description' => 'duplicate item.',
    'capabilities' => 'mod/readaloud:managequestions',
    'type'        => 'write',
    'ajax'        => true,
),

'mod_readaloud_report_quizstep_grade' => array(
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'report_quizstep_grade',
                'description' => 'Reports the grade of a quiz step',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'write',
                'ajax'        => true,
        ),
'mod_readaloud_evaluate_transcript' => array(
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'evaluate_transcript',
                'description' => 'evaluate transcript',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'read',
                'ajax'        => true,
            ),
'mod_readaloud_fetch_quiz_results' => array(
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'fetch_quiz_results',
                'description' => 'fetch_quiz_results',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'read',
                'ajax'        => true,
            ),
 'mod_readaloud_report_activitystep_completion' => array(
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'report_activitystep_completion',
                'description' => 'Reports the completion of an activity step',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'write',
                'ajax'        => true,
        ),
 'mod_readaloud_fetch_student_reading_report' => array(
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'fetch_student_reading_report',
                'description' => 'Fetch the marked passage+results for a student to see',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'read',
                'ajax'        => true,
 ),
);
