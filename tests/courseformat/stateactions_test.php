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
 * Tiles course format state actions unit tests.
 *
 * @package    format_tiles
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles\courseformat;

use core_courseformat\stateupdates;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the tiles stateactions highlight/unhighlight methods.
 *
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_tiles\courseformat\stateactions::class)]
final class stateactions_test extends \advanced_testcase {
    /**
     * Enrol a user into a course and login as this user.
     *
     * @param stdClass $course the course object
     * @param string $rolename the rolename
     */
    private function enrol_user(stdClass $course, string $rolename): void {
        if ($rolename == 'admin') {
            $this->setAdminUser();
        } else {
            $user = $this->getDataGenerator()->create_user();
            if ($rolename != 'unenroled') {
                $this->getDataGenerator()->enrol_user($user->id, $course->id, $rolename);
            }
            $this->setUser($user);
        }
    }

    /**
     * Create a tiles course with sections and return a map of section number to section id.
     *
     * @return array The course and a section number to section id map.
     */
    private function create_tiles_course_with_sections(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 4, 'format' => 'tiles'],
            ['createsections' => true]
        );

        $sectionids = [];
        foreach ($DB->get_records('course_sections', ['course' => $course->id], 'section') as $section) {
            $sectionids[$section->section] = $section->id;
        }

        return [$course, $sectionids];
    }

    /**
     * Tests for the section_highlight method.
     *
     * @param string $rolename The role of the user that will execute the method.
     * @param bool $expectedexception If this call will raise an exception.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('basic_role_provider')]
    public function test_section_highlight(string $rolename, bool $expectedexception = false): void {
        global $DB;
        $this->resetAfterTest(true);

        [$course, $sectionids] = $this->create_tiles_course_with_sections();

        // Mark section 1 first, so the highlight of another section also refreshes the old one.
        \core_courseformat\formatactions::section($course->id)->set_marker(
            get_fast_modinfo($course->id)->get_section_info(1),
            true
        );

        $this->enrol_user($course, $rolename);

        $updates = new stateupdates(course_get_format($course->id));

        // Only one section can be highlighted: the rest of the ids are ignored.
        $methodparam = [
            $sectionids[2],
            $sectionids[3],
            $sectionids[4],
        ];

        if ($expectedexception) {
            $this->expectException(moodle_exception::class);
        }

        $actions = new stateactions();
        $actions->section_highlight($updates, $course, $methodparam);

        // Check the state returned after executing the action.
        $updatelist = $updates->jsonSerialize();
        $this->assertCount(2, $updatelist);
        $fieldsbyid = [];
        foreach ($updatelist as $update) {
            $this->assertEquals('section', $update->name);
            $this->assertEquals('put', $update->action);
            $fieldsbyid[$update->fields->id] = $update->fields;
        }
        $this->assertArrayHasKey($sectionids[2], $fieldsbyid);
        $this->assertTrue((bool)$fieldsbyid[$sectionids[2]]->current);
        $this->assertEquals(2, $fieldsbyid[$sectionids[2]]->number);
        $this->assertArrayHasKey($sectionids[1], $fieldsbyid);
        $this->assertFalse((bool)$fieldsbyid[$sectionids[1]]->current);

        // Check the database marker.
        $this->assertEquals(2, $DB->get_field('course', 'marker', ['id' => $course->id]));
    }

    /**
     * Highlighting the already highlighted section does nothing.
     */
    public function test_section_highlight_already_marked_is_noop(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$course, $sectionids] = $this->create_tiles_course_with_sections();

        \core_courseformat\formatactions::section($course->id)->set_marker(
            get_fast_modinfo($course->id)->get_section_info(1),
            true
        );

        $this->setAdminUser();
        $updates = new stateupdates(course_get_format($course->id));

        // The first id is the one that would be highlighted, and it is already marked.
        (new stateactions())->section_highlight($updates, $course, [$sectionids[1], $sectionids[2]]);

        $this->assertCount(0, $updates->jsonSerialize());
        $this->assertEquals(1, $DB->get_field('course', 'marker', ['id' => $course->id]));
    }

    /**
     * Tests for the section_unhighlight method.
     *
     * @param string $rolename The role of the user that will execute the method.
     * @param bool $expectedexception If this call will raise an exception.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('basic_role_provider')]
    public function test_section_unhighlight(string $rolename, bool $expectedexception = false): void {
        global $DB;
        $this->resetAfterTest(true);

        [$course, $sectionids] = $this->create_tiles_course_with_sections();

        // Highlight section 1.
        \core_courseformat\formatactions::section($course->id)->set_marker(
            get_fast_modinfo($course->id)->get_section_info(1),
            true
        );

        $this->enrol_user($course, $rolename);

        $updates = new stateupdates(course_get_format($course->id));

        // The unhighlight action also refreshes any other sections passed to it.
        $methodparam = [
            $sectionids[3],
            $sectionids[4],
        ];

        if ($expectedexception) {
            $this->expectException(moodle_exception::class);
        }

        $actions = new stateactions();
        $actions->section_unhighlight($updates, $course, $methodparam);

        // The previously highlighted section and all the extra sections are returned.
        $returnedsectionnumbers = [1, 3, 4];
        $updatelist = $updates->jsonSerialize();
        $this->assertCount(3, $updatelist);
        foreach ($updatelist as $update) {
            $this->assertEquals('section', $update->name);
            $this->assertEquals('put', $update->action);
            $this->assertContains($update->fields->number, $returnedsectionnumbers);
            $this->assertFalse((bool)$update->fields->current);
        }

        // Check the database marker.
        $this->assertEquals(0, $DB->get_field('course', 'marker', ['id' => $course->id]));
    }

    /**
     * Data provider for basic role tests.
     *
     * @return \Generator the testing scenarios
     */
    public static function basic_role_provider(): \Generator {
        yield 'admin' => [
            'rolename' => 'admin',
            'expectedexception' => false,
        ];
        yield 'editingteacher' => [
            'rolename' => 'editingteacher',
            'expectedexception' => false,
        ];
        yield 'teacher' => [
            'rolename' => 'teacher',
            'expectedexception' => true,
        ];
        yield 'student' => [
            'rolename' => 'student',
            'expectedexception' => true,
        ];
        yield 'guest' => [
            'rolename' => 'guest',
            'expectedexception' => true,
        ];
        yield 'unenroled' => [
            'rolename' => 'unenroled',
            'expectedexception' => true,
        ];
    }
}
