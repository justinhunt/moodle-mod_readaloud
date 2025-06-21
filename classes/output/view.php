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
 * Class containing data for the view page.
 *
 * @package    mod_readaloud
 * @copyright  2025 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_readaloud\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class containing data for the view page.
 *
 * @package    mod_readaloud
 * @copyright  2025 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view implements renderable, templatable {
    /** @var stdClass $cm course module info */
    public $cm;
    /** @var stdClass $config plugin configuration */
    public $config;
    /** @var int $debug debug flag */
    public $debug;
    /** @var int $embed embed mode flag */
    public $embed;
     /** @var stdClass $moduleinstance the readaloud instance record */
     public $modulecontext;
     /** @var stdClass $moduleinstance the readaloud instance record */
     public $moduleinstance;
    /** @var int $reviewattempts number of review attempts */
    public $reviewattempts;

    /**
     * Constructor for the view_page class.
     *
     * @param cm_info   $cm             The course module.
     * @param stdClass  $config         Plugin configuration.
     * @param int       $debug          Debug flag.
     * @param int       $embed          Embed flag.
     * @param context   $modulecontext  The context.
     * @param stdClass  $moduleinstance The module instance.
     * @param int       $reviewattempts Review‐attempts.
     */
    public function __construct(
        $cm,
        $config,
        $debug,
        $embed,
        $modulecontext,
        $moduleinstance,
        $reviewattempts
        ) {
        $this->cm = $cm;
        $this->config = $config;
        $this->debug = $debug;
        $this->embed = $embed;
        $this->modulecontext = $modulecontext;
        $this->moduleinstance = $moduleinstance;
        $this->reviewattempts = $reviewattempts;
    }

    /**
     * Export data for template (renderer builds full context).
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE;

        $renderer = $PAGE->get_renderer('mod_readaloud');
        $context = $renderer->get_view_page_data(
            $this->cm,
            $this->config,
            $this->debug,
            $this->embed,
            $this->modulecontext,
            $this->moduleinstance,
            $this->reviewattempts
        );
        return (object) $context;
    }
}
