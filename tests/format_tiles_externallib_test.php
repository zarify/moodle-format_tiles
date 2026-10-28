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
 * External tiles format functions unit tests.
 *
 * @package    format_tiles
 * @category   external
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External course functions unit tests
 *
 * @package    format_tiles
 * @category   external
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversMethod(\format_tiles\external\external::class, 'set_image')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\format_tiles\external\external::class, 'set_session_width')]
final class format_tiles_externallib_test extends \externallib_advanced_testcase {
    /**
     * The format options to use when setting up a course in tiles format.
     * @var array
     */
    private $tilescourseformatoptions = [
        'shortname' => 'aname',
        'fullname' => 'A Name',
        'numsections' => 5,
        'format' => 'tiles',
        'defaulttileicon' => 'user-o',
        'basecolour' => '#700000',
        'courseusesubtiles' => 1,
        'courseshowtileprogress' => 0,
        'displayfilterbar' => 1,
        'usesubtilesseczero' => 0,
        'courseusebarforheadings' => 1,
    ];

    /**
     * Test the setting of a course/tile icon using the web service.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     */
    public function test_set_image(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course($this->tilescourseformatoptions);

        // Teacher can do it.
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user(
            $teacher->id,
            $course->id,
            $teacherrole->id
        );
        $this->setUser($teacher);
        $contextid = \context_course::instance($course->id)->id;
        $roleid = $this->assignUserCapability('moodle/course:update', $contextid);

        $newicon = 'number_1';
        $sectionid = get_fast_modinfo($course)->get_section_info_all()[1]->id;

        $result = \format_tiles\external\external::set_image(
            $course->id,
            $sectionid,
            $newicon,
            'tileicon',
            0,
            0
        );
        $result = \core_external\external_api::clean_returnvalue(\format_tiles\external\external::set_image_returns(), $result);
        // Check for 0 warnings.
        $this->assertTrue($result['status']);

        // Check it worked and data made it to db.
        $dbvalue = \format_tiles\local\format_option::get(
            $course->id,
            \format_tiles\local\format_option::OPTION_SECTION_ICON,
            $sectionid
        );

        $this->assertEquals($newicon, $dbvalue);

        // Call without required capability.
        $this->unassignUserCapability('moodle/course:update', $contextid, $roleid);
        $this->expectException('required_capability_exception');
        $result = \format_tiles\external\external::set_image(
            $course->id,
            $sectionid,
            $newicon,
            'tileicon'
        );
    }

    /**
     * A student and a guest must not be able to set a tile image.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_image_rejects_student_and_guest(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course($this->tilescourseformatoptions);
        $sectionid = get_fast_modinfo($course)->get_section_info_all()[1]->id;

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        try {
            \format_tiles\external\external::set_image($course->id, $sectionid, 'number_1', 'tileicon');
            $this->fail('Expected a required_capability_exception for a student');
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
        $this->assertEmpty(\format_tiles\local\format_option::get(
            $course->id,
            \format_tiles\local\format_option::OPTION_SECTION_ICON,
            $sectionid
        ));

        $this->setGuestUser();
        try {
            \format_tiles\external\external::set_image($course->id, $sectionid, 'number_1', 'tileicon');
            $this->fail('Expected a require_login_exception for a guest');
        } catch (\require_login_exception $e) {
            $this->assertSame('requireloginerror', $e->errorcode);
        }
        $this->assertEmpty(\format_tiles\local\format_option::get(
            $course->id,
            \format_tiles\local\format_option::OPTION_SECTION_ICON,
            $sectionid
        ));
    }

    /**
     * A non-editing teacher must not be able to change a tile icon.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_image_rejects_non_editing_teacher(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course($this->tilescourseformatoptions);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $sectionid = get_fast_modinfo($course)->get_section_info_all()[1]->id;
        $this->setUser($teacher);
        $context = \context_course::instance($course->id);
        $this->assertTrue(has_capability('moodle/course:viewhiddenactivities', $context));

        try {
            \format_tiles\external\external::set_image($course->id, $sectionid, 'number_1', 'tileicon');
            $this->fail('Expected a required_capability_exception');
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
        $this->assertEmpty(\format_tiles\local\format_option::get(
            $course->id,
            \format_tiles\local\format_option::OPTION_SECTION_ICON,
            $sectionid
        ));
    }

    /**
     * An editing teacher may change a tile icon.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_image_allows_editing_teacher(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course($this->tilescourseformatoptions);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $sectionid = get_fast_modinfo($course)->get_section_info_all()[1]->id;
        $this->setUser($teacher);

        $result = \format_tiles\external\external::set_image($course->id, $sectionid, 'number_1', 'tileicon');
        $result = \core_external\external_api::clean_returnvalue(
            \format_tiles\external\external::set_image_returns(),
            $result
        );
        $this->assertTrue($result['status']);
        $this->assertSame('number_1', \format_tiles\local\format_option::get(
            $course->id,
            \format_tiles\local\format_option::OPTION_SECTION_ICON,
            $sectionid
        ));
    }


    /**
     * Test the setting of a session screen width variable using the web service.
     * @throws \dml_exception
     * @throws \invalid_response_exception
     * @throws \moodle_exception
     */
    public function test_set_session_width(): void {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course($this->tilescourseformatoptions);

        // Teacher can do it.
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user(
            $teacher->id,
            $course->id,
            $teacherrole->id
        );
        $this->setUser($teacher);
        $contextid = \context_course::instance($course->id)->id;
        $roleid = $this->assignUserCapability('moodle/course:update', $contextid);

        $result = \format_tiles\external\external::set_session_width($course->id, 1366);
        $result = \core_external\external_api::clean_returnvalue(
            \format_tiles\external\external::set_session_width_returns(),
            $result
        );
        // Check for 0 warnings.
        $this->assertTrue($result['status']);

        // Check it worked and data made it to $SESSION.
        $sessionvarname = 'format_tiles_width_' . $course->id;
        $this->assertEquals(1366, $SESSION->$sessionvarname);

        // Student also allowed to do it.
        $student1 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user(
            $student1->id,
            $course->id,
            $studentrole->id
        );
        $this->setUser($student1);

        $result = \format_tiles\external\external::set_session_width($course->id, 1366);
        $result = \core_external\external_api::clean_returnvalue(
            \format_tiles\external\external::set_session_width_returns(),
            $result
        );
        // Check for 0 warnings.
        $this->assertTrue($result['status']);

        // Check it worked and data made it to $SESSION.
        $sessionvarname = 'format_tiles_width_' . $course->id;
        $this->assertEquals(1366, $SESSION->$sessionvarname);

        // Fail when the user is not allow to access the course (enrolled) or is not teacher.
        $this->setGuestUser();
        $this->expectException('require_login_exception');

        $result = \format_tiles\external\external::set_session_width($course->id, 1366);
        $result = \core_external\external_api::clean_returnvalue(
            \format_tiles\external\external::set_session_width_returns(),
            $result
        );
    }

    /**
     * Put a copy of a plugin fixture image into a user's draft file area, as the file picker would.
     *
     * @param int $userid owner of the draft area
     * @param int $draftitemid draft area item id
     * @param string $filename fixture file name
     * @return void
     */
    private function create_draft_image(int $userid, int $draftitemid, string $filename): void {
        global $CFG;
        get_file_storage()->create_file_from_pathname([
            'contextid' => \context_user::instance($userid)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], "$CFG->dirroot/course/format/tiles/tests/fixtures/images/$filename");
    }

    /**
     * A teacher must not be able to copy a file out of another user's draft area.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_image_draftfile_rejects_foreign_draft(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course($this->tilescourseformatoptions);
        $sectionid = get_fast_modinfo($course)->get_section_info_all()[1]->id;
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Draft item ids need a real user (not the guest), so pick one as the teacher.
        $this->setUser($teacher);
        $draftitemid = file_get_unused_draft_itemid();
        $this->create_draft_image($student->id, $draftitemid, 'placeholder_1.jpg');

        $this->setUser($teacher);
        try {
            \format_tiles\external\external::set_image(
                $course->id,
                $sectionid,
                'placeholder_1.jpg',
                'draftfile',
                \context_user::instance($student->id)->id,
                $draftitemid
            );
            $this->fail("Expected an invalid_parameter_exception when using another user's draft area");
        } catch (\invalid_parameter_exception $e) {
            $this->assertSame('invalidparameter', $e->errorcode);
        }

        // Nothing may have been copied into the course.
        $this->assertEmpty(get_file_storage()->get_area_files(
            \context_course::instance($course->id)->id,
            'format_tiles',
            'tilephoto',
            $sectionid,
            'filename',
            false
        ));
    }

    /**
     * A photo picked from the teacher's own draft area must be stored in the course context,
     * which is the context the pluginfile callback serves and checks.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_image_draftfile_stores_in_course_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course($this->tilescourseformatoptions);
        $sectionid = get_fast_modinfo($course)->get_section_info_all()[1]->id;
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->setUser($teacher);
        $draftitemid = file_get_unused_draft_itemid();
        $this->create_draft_image($teacher->id, $draftitemid, 'placeholder_1.jpg');

        $result = \format_tiles\external\external::set_image(
            $course->id,
            $sectionid,
            'placeholder_1.jpg',
            'draftfile',
            \context_user::instance($teacher->id)->id,
            $draftitemid
        );
        $result = \core_external\external_api::clean_returnvalue(
            \format_tiles\external\external::set_image_returns(),
            $result
        );
        $this->assertTrue($result['status']);

        $coursecontextid = \context_course::instance($course->id)->id;
        $files = get_file_storage()->get_area_files(
            $coursecontextid,
            'format_tiles',
            'tilephoto',
            $sectionid,
            'filename',
            false
        );
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame(
            $file->get_filename(),
            \format_tiles\local\format_option::get(
                $course->id,
                \format_tiles\local\format_option::OPTION_SECTION_PHOTO,
                $sectionid
            )
        );
        $this->assertStringContainsString('/' . $coursecontextid . '/format_tiles/tilephoto/', $result['imageurl']);
    }

    /**
     * Tile click logging must only accept a course context and a real section number.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_log_tile_click_rejects_non_course_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course($this->tilescourseformatoptions);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $coursecontextid = \context_course::instance($course->id)->id;
        $this->setUser($student);

        try {
            \format_tiles\external\external::log_tile_click(\context_user::instance($student->id)->id, 0, 0);
            $this->fail('Expected an invalid_parameter_exception for a non-course context');
        } catch (\invalid_parameter_exception $e) {
            $this->assertSame('invalidparameter', $e->errorcode);
        }

        // A negative section number is not a section of the course.
        $this->expectException(\invalid_parameter_exception::class);
        \format_tiles\external\external::log_tile_click($coursecontextid, -1, 0);
    }
}
