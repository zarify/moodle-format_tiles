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
 * Unit tests for the Tiles fragment callbacks.
 *
 * @package    format_tiles
 * @copyright  2026 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/format/tiles/lib.php');

/**
 * Unit tests for the Tiles fragment callbacks.
 *
 * @package    format_tiles
 * @copyright  2026 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('format_tiles_output_fragment_get_cm_list')]
#[\PHPUnit\Framework\Attributes\CoversFunction('format_tiles_output_fragment_get_cm_content')]
final class format_tiles_fragment_test extends \advanced_testcase {
    /**
     * Create a tiles course with a page activity in section 1.
     *
     * @param array $courseoptions extra course fields
     * @return array [course, sectionid, page]
     */
    private function create_course_with_page(array $courseoptions = []): array {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course(
            array_merge(['format' => 'tiles', 'numsections' => 2, 'courseusesubtiles' => 1], $courseoptions),
            ['createsections' => true]
        );
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Visible page',
            'content' => '<p>Modal page content</p>',
        ]);
        $sectionid = get_fast_modinfo($course)->get_section_info(1)->id;

        // The page theme is set up by course creation (the icon set reads $PAGE->theme), which locks set_course(),
        // so set the context and URL the fragments need directly.
        $PAGE->set_context(\context_course::instance($course->id));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);

        return [$course, $sectionid, $page];
    }

    /**
     * A user with no enrolment in the course must not be able to read a section's activity list.
     */
    public function test_get_cm_list_rejects_non_enrolled_user(): void {
        $this->resetAfterTest();
        [, $sectionid] = $this->create_course_with_page();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\require_login_exception::class);
        format_tiles_output_fragment_get_cm_list(['sectionid' => $sectionid]);
    }

    /**
     * A student must not be able to read the activity list of a hidden section.
     */
    public function test_get_cm_list_rejects_hidden_section_for_student(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $sectionid] = $this->create_course_with_page();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $DB->set_field('course_sections', 'visible', 0, ['id' => $sectionid]);
        rebuild_course_cache($course->id, true);

        $this->expectException(\moodle_exception::class);
        format_tiles_output_fragment_get_cm_list(['sectionid' => $sectionid]);
    }

    /**
     * The activity list fragment only serves courses using this format.
     */
    public function test_get_cm_list_rejects_non_tiles_course(): void {
        global $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['format' => 'topics'], ['createsections' => true]);
        $sectionid = get_fast_modinfo($course)->get_section_info(1)->id;
        $PAGE->set_context(\context_course::instance($course->id));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $this->expectException(\moodle_exception::class);
        format_tiles_output_fragment_get_cm_list(['sectionid' => $sectionid]);
    }

    /**
     * An enrolled user gets the rendered activity list for a visible section.
     */
    public function test_get_cm_list_renders_activity_list_for_enrolled_user(): void {
        $this->resetAfterTest();
        [$course, $sectionid] = $this->create_course_with_page();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $html = format_tiles_output_fragment_get_cm_list(['sectionid' => $sectionid]);

        $this->assertStringContainsString('format_tiles_section_content', $html);
        $this->assertStringContainsString('Visible page', $html);
    }

    /**
     * The module content fragment only accepts a module context.
     */
    public function test_get_cm_content_rejects_wrong_context_level(): void {
        $this->resetAfterTest();
        [$course, , $page] = $this->create_course_with_page();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $this->expectException(\invalid_parameter_exception::class);
        format_tiles_output_fragment_get_cm_content(['contextid' => \context_system::instance()->id]);
    }

    /**
     * An enrolled user gets the page content for a visible page activity.
     */
    public function test_get_cm_content_renders_modal_body_for_visible_page(): void {
        $this->resetAfterTest();
        [$course, , $page] = $this->create_course_with_page();
        // The page module has to be allowed through the fragment (site setting).
        set_config('modalmodules', 'page,resource,url,folder', 'format_tiles');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $html = format_tiles_output_fragment_get_cm_content([
            'contextid' => \context_module::instance($page->cmid)->id,
        ]);

        $this->assertStringContainsString('Modal page content', $html);
    }
}
