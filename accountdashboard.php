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
 * Account dashboard page showing Cloud Poodll subscription and usage data.
 *
 * @package    mod_readaloud
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_readaloud\constants;
use mod_readaloud\utils;

require_once("../../config.php");
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('mod_readaloud_accountdashboard');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('accountdashboard', constants::M_COMPONENT), 3);

$params = [];
$result = utils::call_cloudpoodll('local_cpapi_fetch_user_report', $params);
if (!$result || !isset($result->returnMessage) || !($usagedata = json_decode($result->returnMessage))) {
    echo get_string('failedfetchsubreport', constants::M_COMPONENT);
    echo $OUTPUT->footer();
    return;
}

$renderer = $PAGE->get_renderer(constants::M_COMPONENT);
$renderer->display_usage_report($usagedata);

echo $OUTPUT->footer();
