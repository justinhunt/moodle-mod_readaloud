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

use context_module;
use mod_readaloud\mobile_auth;
use mod_readaloud\constants;

/**
 * Class mobile
 *
 * @package    mod_readaloud
 * @copyright  2024 Poodll
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Mobile course view.
     *
     * @param array $args The arguments.
     * @return array The result.
     */
    public static function mobile_course_view($args) {
        global $DB, $CFG, $OUTPUT, $USER;

        $cmid = $args['cmid'];
        if (!$CFG->allowframembedding) {
            $context = \context_system::instance();
            if (has_capability('moodle/site:config', $context)) {
                $template = 'mod_readaloud/mobile_no_iframe_embedding';
            } else {
                $template = 'mod_readaloud/mobile_contact_siteadmin';
            }
            return [
                'templates' => [
                    [
                        'id' => 'noiframeembedding',
                        'html' => $OUTPUT->render_from_template($template, []),
                    ],
                ],
            ];
        }

        // Verify course context.
        $cm = get_coursemodule_from_id('readaloud', $cmid);
        if (!$cm) {
            throw new moodle_exception('invalidcoursemodule');
        }
        $course = $DB->get_record('course', ['id' => $cm->course]);
        if (!$course) {
            throw new moodle_exception('coursemisconf');
        }
        require_course_login($course, false, $cm, true, true);
        $context = context_module::instance($cm->id);
        require_capability('mod/readaloud:view', $context);

        list($token, $secret) = mobile_auth::create_embed_auth_token();

        // Store secret in database.
        $auth = $DB->get_record(constants::M_AUTHTABLE, [
            'user_id' => $USER->id,
        ]);
        $currenttimestamp = time();
        if ($auth) {
            $DB->update_record(constants::M_AUTHTABLE, [
                'id'         => $auth->id,
                'secret'     => $token,
                'created_at' => $currenttimestamp,
            ]);
        } else {
            $DB->insert_record(constants::M_AUTHTABLE, [
                'user_id'    => $USER->id,
                'secret'     => $token,
                'created_at' => $currenttimestamp,
            ]);
        }

        $data = [
            'cmid'    => $cmid,
            'wwwroot' => $CFG->wwwroot,
            'user_id' => $USER->id,
            'secret'  => urlencode($secret),
        ];

        return [
            'templates'  => [
                [
                    'id'   => 'main',
                    'html' => $OUTPUT->render_from_template('mod_readaloud/mobile_view_page', $data),
                ],
            ],
        ];
    }
}

