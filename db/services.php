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

);
