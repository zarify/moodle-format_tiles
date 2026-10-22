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
 * Tiles section control menu unit tests.
 *
 * @package    format_tiles
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles\output\courseformat\content\section;

use core\output\action_menu\link_secondary;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the tiles section control menu highlight item.
 *
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_tiles\output\courseformat\content\section\controlmenu::class)]
final class controlmenu_test extends \advanced_testcase {
    /**
     * Create a tiles course and return it with its modinfo.
     *
     * @return array The course and the course modinfo.
     */
    private function create_course(): array {
        $course = $this->getDataGenerator()->create_course(
            ['numsections' => 3, 'format' => 'tiles'],
            ['createsections' => true]
        );
        return [$course, get_fast_modinfo($course->id)];
    }

    /**
     * Get the control items for a section.
     *
     * @param stdClass $course The course.
     * @param \section_info $section The section.
     * @return array The control items.
     */
    private function get_control_items(stdClass $course, \section_info $section): array {
        $controlmenu = new controlmenu(course_get_format($course->id), $section);
        return $controlmenu->section_control_items();
    }

    /**
     * An editing user sees the highlight item for a normal section.
     */
    public function test_highlight_item_for_unmarked_section(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, $modinfo] = $this->create_course();
        $section = $modinfo->get_section_info(2);
        $items = $this->get_control_items($course, $section);

        $this->assertArrayHasKey('highlight', $items);
        $this->assertInstanceOf(link_secondary::class, $items['highlight']);
        $link = $items['highlight'];
        $this->assertEquals(get_string('highlight'), $link->text);
        $this->assertEquals('sectionHighlight', $link->attributes['data-action']);
        $this->assertEquals($section->id, $link->attributes['data-id']);
        $this->assertStringContainsString('editing_highlight', $link->attributes['class']);
        $this->assertEquals(get_string('highlightoff'), $link->attributes['data-swapname']);
        $this->assertEquals('i/marked', $link->attributes['data-swapicon']);

        // The non-JS fallback must point at the core format update script with a valid session key.
        $this->assertStringEndsWith('/course/format/update.php', $link->url->get_path());
        $this->assertEquals('section_highlight', $link->url->get_param('action'));
        $this->assertEquals($section->id, $link->url->get_param('id'));
        $this->assertEquals($course->id, $link->url->get_param('courseid'));
        $this->assertEquals(sesskey(), $link->url->get_param('sesskey'));

        // The icon picker item is still emitted.
        $this->assertArrayHasKey('setphoto', $items);
        $this->assertEquals(
            'launch-tiles-icon-picker',
            $items['setphoto']->attributes['data-tiles-action']
        );
    }

    /**
     * A marked section offers the unhighlight action instead.
     */
    public function test_highlight_item_for_marked_section(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, $modinfo] = $this->create_course();
        $section = $modinfo->get_section_info(2);

        \core_courseformat\formatactions::section($course->id)->set_marker($section, true);

        $items = $this->get_control_items($course, $section);
        $link = $items['highlight'];
        $this->assertEquals(get_string('highlightoff'), $link->text);
        $this->assertEquals('sectionUnhighlight', $link->attributes['data-action']);
        $this->assertEquals(get_string('highlight'), $link->attributes['data-swapname']);
        $this->assertEquals('i/marker', $link->attributes['data-swapicon']);
        $this->assertEquals('section_unhighlight', $link->url->get_param('action'));
        $this->assertEquals($section->id, $link->url->get_param('id'));
    }

    /**
     * Section zero is not highlighted from its own control menu.
     */
    public function test_section_zero_has_no_highlight_item(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, $modinfo] = $this->create_course();
        $items = $this->get_control_items($course, $modinfo->get_section_info(0));

        $this->assertArrayNotHasKey('highlight', $items);
        $this->assertNotEmpty($items);
    }

    /**
     * A student does not get the highlight item.
     */
    public function test_student_has_no_highlight_item(): void {
        $this->resetAfterTest();

        [$course, $modinfo] = $this->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $items = $this->get_control_items($course, $modinfo->get_section_info(2));
        $this->assertArrayNotHasKey('highlight', $items);
    }
}
