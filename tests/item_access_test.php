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

namespace mod_readaloud;

use mod_readaloud\local\itemtype\item;

/**
 * Access checks on editing a reading activity's items.
 *
 * @package    mod_readaloud
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_readaloud_external
 * @covers     \mod_readaloud\local\itemtype\item::delete_item
 */
final class item_access_test extends \advanced_testcase {
    /** @var \stdClass an activity's course module */
    protected $cm;

    /** @var \stdClass a second activity's course module, in another course */
    protected $othercm;

    /** @var \stdClass a student in the first course */
    protected $student;

    /** @var \stdClass an editing teacher in the first course */
    protected $teacher;

    /**
     * Load the library files.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/mod/readaloud/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');
    }

    /**
     * Two courses with an activity each, a student and a teacher in the first.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $this->cm = $this->create_activity($course);
        $this->othercm = $this->create_activity($gen->create_course());
        $this->student = $gen->create_and_enrol($course, 'student');
        $this->teacher = $gen->create_and_enrol($course, 'editingteacher');
    }

    /**
     * Create an empty activity without calling the speech services that add_instance uses.
     *
     * @param \stdClass $course the course
     * @return \stdClass the course module
     */
    protected function create_activity(\stdClass $course): \stdClass {
        global $DB;
        $instanceid = $DB->insert_record(constants::M_TABLE, (object) [
            'course' => $course->id,
            'name' => 'Reading',
            'timelimit' => 0,
            'timecreated' => time(),
        ]);
        $cmid = add_course_module((object) [
            'course' => $course->id,
            'module' => $DB->get_field('modules', 'id', ['name' => constants::M_MODNAME]),
            'instance' => $instanceid,
            'section' => 0,
        ]);
        course_add_cm_to_section($course, $cmid, 0);
        return get_coursemodule_from_id(constants::M_MODNAME, $cmid, 0, false, MUST_EXIST);
    }

    /**
     * Add a page item to an activity.
     *
     * @param \stdClass $cm the activity's course module
     * @return int the item id
     */
    protected function create_item(\stdClass $cm): int {
        global $DB;
        return $DB->insert_record(constants::M_QTABLE, (object) [
            'readaloudid' => $cm->instance,
            'type' => constants::TYPE_PAGE,
            'name' => 'Item',
            'itemorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * The activity's module context id.
     *
     * @param \stdClass $cm the course module
     * @return int
     */
    protected function contextid(\stdClass $cm): int {
        return \context_module::instance($cm->id)->id;
    }

    /**
     * A student cannot delete, move or duplicate items.
     *
     * @dataProvider item_function_provider
     * @param string $function the external function
     */
    public function test_item_functions_denied_for_student(string $function): void {
        $itemid = $this->create_item($this->cm);
        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        switch ($function) {
            case 'delete_item':
                \mod_readaloud_external::delete_item($this->contextid($this->cm), $itemid, constants::TYPE_PAGE);
                break;
            case 'move_item':
                \mod_readaloud_external::move_item($this->contextid($this->cm), $itemid, 'up');
                break;
            case 'duplicate_item':
                \mod_readaloud_external::duplicate_item($this->contextid($this->cm), $itemid);
                break;
        }
    }

    /**
     * Data for test_item_functions_denied_for_student.
     *
     * @return array
     */
    public static function item_function_provider(): array {
        return [
            'delete' => ['delete_item'],
            'move' => ['move_item'],
            'duplicate' => ['duplicate_item'],
        ];
    }

    /**
     * A teacher cannot delete another activity's item through their own activity.
     */
    public function test_delete_item_from_other_activity_refused(): void {
        global $DB;
        $otheritemid = $this->create_item($this->othercm);
        $this->setUser($this->teacher);
        try {
            \mod_readaloud_external::delete_item($this->contextid($this->cm), $otheritemid, constants::TYPE_PAGE);
            $this->fail('An item of another activity was deleted');
        } catch (\moodle_exception $e) {
            $this->assertTrue($DB->record_exists(constants::M_QTABLE, ['id' => $otheritemid]));
        }
    }

    /**
     * A teacher can still delete their own activity's item.
     */
    public function test_delete_item_allowed_for_teacher(): void {
        global $DB;
        $itemid = $this->create_item($this->cm);
        $this->setUser($this->teacher);
        \mod_readaloud_external::delete_item($this->contextid($this->cm), $itemid, constants::TYPE_PAGE);
        $this->assertFalse($DB->record_exists(constants::M_QTABLE, ['id' => $itemid]));
    }

    /**
     * item::delete_item() itself refuses an item of another activity.
     */
    public function test_item_delete_item_checks_activity(): void {
        global $DB;
        $otheritemid = $this->create_item($this->othercm);
        $this->expectException(\moodle_exception::class);
        try {
            item::delete_item($otheritemid, \context_module::instance($this->cm->id));
        } finally {
            $this->assertTrue($DB->record_exists(constants::M_QTABLE, ['id' => $otheritemid]));
        }
    }

    /**
     * A student cannot load the item preview fragment.
     */
    public function test_preview_fragment_denied_for_student(): void {
        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        readaloud_output_fragment_preview(['context' => \context_module::instance($this->cm->id), 'itemid' => 0]);
    }
}
