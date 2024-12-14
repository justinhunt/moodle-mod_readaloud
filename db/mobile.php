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

/**
 * Mobile app areas for Poodll ReadAloud
 *
 * Documentation: {@link https://moodledev.io/general/app/development/plugins-development-guide}
 *
 * @package    mod_readaloud
 * @copyright  2024 Poodll
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_readaloud' => [
        'handlers' => [
            'coursereadaloud' => [
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_course_view', // Main function in \mod_readaloud\output\mobile.
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/readaloud/pix/icon.svg',
                    'class' => '',
                ],
            ],
        ],
    ],
];
