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
 * Services definition.
 *
 * @package mod_readaloud
 * @author  Justin Hunt - poodll.com
 */

$functions = [

    'mod_readaloud_submit_regular_attempt' => [
            'classname'   => 'mod_readaloud_external',
            'methodname'  => 'submit_regular_attempt',
            'description' => 'submits regular attempt.',
            'capabilities' => 'mod/readaloud:view',
            'type'        => 'write',
            'ajax'        => true,
    ],

    'mod_readaloud_submit_streaming_attempt' => [
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'submit_streaming_attempt',
        'description' => 'submits streaming attempt.',
        'capabilities' => 'mod/readaloud:view',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'mod_readaloud_fetch_streaming_diffs' => [
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'fetch_streaming_diffs',
        'description' => 'Fetches diffs for streaming transcription results',
        'capabilities' => 'mod/readaloud:view',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'mod_readaloud_compare_passage_to_transcript' => [
            'classname'   => 'mod_readaloud_external',
            'methodname'  => 'compare_passage_to_transcript',
            'description' => 'compares passage to transcript and returns diffs',
            'capabilities' => 'mod/readaloud:view',
            'type'        => 'read',
            'ajax'        => true,
    ],

    'mod_readaloud_check_for_results' => [
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'check_for_results',
                'description' => 'checks if results are in yet',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'read',
                'ajax'        => true,
    ],


    'mod_readaloud_delete_item' => [
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'delete_item',
        'description' => 'delete item.',
        'capabilities' => 'mod/readaloud:managequestions',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'mod_readaloud_move_item' => [
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'move_item',
        'description' => 'move item.',
        'capabilities' => 'mod/readaloud:managequestions',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'mod_readaloud_duplicate_item' => [
    'classname'   => 'mod_readaloud_external',
    'methodname'  => 'duplicate_item',
    'description' => 'duplicate item.',
    'capabilities' => 'mod/readaloud:managequestions',
    'type'        => 'write',
    'ajax'        => true,
    ],

    'mod_readaloud_report_quizstep_grade' => [
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'report_quizstep_grade',
                'description' => 'Reports the grade of a quiz step',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'write',
                'ajax'        => true,
        ],
    'mod_readaloud_evaluate_transcript' => [
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'evaluate_transcript',
                'description' => 'evaluate transcript',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'read',
                'ajax'        => true,
            ],
    'mod_readaloud_fetch_quiz_results' => [
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'fetch_quiz_results',
                'description' => 'fetch_quiz_results',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'read',
                'ajax'        => true,
            ],
     'mod_readaloud_report_activitystep_completion' => [
                'classname'   => 'mod_readaloud_external',
                'methodname'  => 'report_activitystep_completion',
                'description' => 'Reports the completion of an activity step',
                'capabilities' => 'mod/readaloud:view',
                'type'        => 'write',
                'ajax'        => true,
        ],
     'mod_readaloud_fetch_student_reading_report' => [
                    'classname'   => 'mod_readaloud_external',
                    'methodname'  => 'fetch_student_reading_report',
                    'description' => 'Fetch the marked passage+results for a student to see',
                    'capabilities' => 'mod/readaloud:view',
                    'type'        => 'read',
                    'ajax'        => true,
     ],

    'mod_readaloud_fetch_view_data' => [
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'fetch_view_data',
        'description' => 'Fetch the quiz and reading results for display in full report',
        'capabilities' => 'mod/readaloud:view',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'mod_readaloud_refresh_token' => [
        'classname'   => 'mod_readaloud_external',
        'methodname'  => 'refresh_token',
        'description' => 'refreshes the speech recognition token',
        'capabilities' => 'mod/readaloud:view',
        'type'        => 'read',
        'ajax'        => true,
    ],

];
