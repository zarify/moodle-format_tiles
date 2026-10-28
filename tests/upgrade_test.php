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
 * Upgrade steps unit tests for format tiles.
 *
 * @package    format_tiles
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles;

use format_tiles\local\format_option;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->dirroot . '/course/format/tiles/db/upgrade.php');
require_once($CFG->dirroot . '/course/format/tiles/tests/helperlib.php');

/**
 * Tests for the format_tiles upgrade savepoints.
 *
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('xmldb_format_tiles_upgrade')]
final class upgrade_test extends \advanced_testcase {
    /**
     * Seed a course format option row and return its id.
     *
     * @param int $courseid
     * @param string $name
     * @param string $value
     * @param int $sectionid
     * @return int
     */
    private function seed_format_option(int $courseid, string $name, string $value, int $sectionid = 0): int {
        global $DB;
        $conditions = ['courseid' => $courseid, 'format' => 'tiles', 'sectionid' => $sectionid, 'name' => $name];
        $id = $DB->get_field('course_format_options', 'id', $conditions);
        if ($id) {
            $DB->set_field('course_format_options', 'value', $value, ['id' => $id]);
            return (int)$id;
        }
        return $DB->insert_record('course_format_options', (object)($conditions + ['value' => $value]));
    }

    /**
     * The 2018080103 step renames legacy option names and removes stale data.
     */
    public function test_upgrade_2018080103_renames_and_cleans_options(): void {
        global $DB;
        $this->resetAfterTest();

        // The savepoint checks the stored plugin version, which must be below the step version.
        set_config('version', 2018080102, 'format_tiles');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'tiles']);
        $coursetwo = $generator->create_course(['format' => 'tiles']);
        $section = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 1]);
        $sectiontwo = $DB->get_field('course_sections', 'id', ['course' => $coursetwo->id, 'section' => 1]);
        $user = $generator->create_user();

        // The new format is installed, so remove the modern defaults a legacy site would not have.
        $DB->delete_records('course_format_options', ['courseid' => $course->id, 'name' => 'defaulttileicon']);
        $DB->delete_records('course_format_options', ['courseid' => $course->id, 'name' => 'tileicon']);

        // Renamed options.
        $this->seed_format_option($course->id, 'tiletopleftthistile', 'user', $section);
        $this->seed_format_option($course->id, 'defaulttiletopleftdisplay', 'bookmark-o');

        // Stale options which must be removed.
        $this->seed_format_option($course->id, 'tileicon', 'course default');
        $this->seed_format_option($course->id, 'tileoutcomeid', '0');
        $this->seed_format_option($course->id, 'headertextabovetiles', 'hello');
        $this->seed_format_option($course->id, 'prefixtitlewithnumber', '1');

        // Legacy boolean options, one per course because the option name is unique per section.
        $this->seed_format_option($course->id, 'courseusebarforheadings', 'standard');
        $this->seed_format_option($coursetwo->id, 'courseusebarforheadings', 'yes');

        // Legacy icon names.
        $this->seed_format_option($coursetwo->id, 'tileicon', 'handshake', $sectiontwo);
        $this->seed_format_option($coursetwo->id, 'defaulttileicon', 'calendar-check');

        // Browser storage preferences.
        set_user_preference('format_tiles_filterbutton_1', 1, $user);
        set_user_preference('format_tiles_collapseseczero_2', 1, $user);

        // Removed plugin settings.
        foreach (['persistfilterbuttons', 'fontimporttext', 'fontfamilytext', 'displaytileitems', 'showiconslist'] as $name) {
            set_config($name, 1, 'format_tiles');
        }

        $this->assertTrue(xmldb_format_tiles_upgrade(2018080102));

        // Renamed options.
        $this->assertSame(
            'user',
            $DB->get_field('course_format_options', 'value', [
                'courseid' => $course->id, 'format' => 'tiles', 'sectionid' => $section, 'name' => 'tileicon',
            ])
        );
        $this->assertFalse($DB->record_exists('course_format_options', ['name' => 'tiletopleftthistile']));
        $this->assertSame(
            'bookmark-o',
            $DB->get_field('course_format_options', 'value', [
                'courseid' => $course->id, 'format' => 'tiles', 'sectionid' => 0, 'name' => 'defaulttileicon',
            ])
        );
        $this->assertFalse($DB->record_exists('course_format_options', ['name' => 'defaulttiletopleftdisplay']));

        // Stale options removed.
        $this->assertNotContains('course default', $DB->get_fieldset_select(
            'course_format_options',
            'value',
            'courseid = ? AND name = ?',
            [$course->id, 'tileicon']
        ));
        $this->assertNotContains('0', $DB->get_fieldset_select(
            'course_format_options',
            'value',
            'courseid = ? AND name = ?',
            [$course->id, 'tileoutcomeid']
        ));
        $this->assertFalse($DB->record_exists('course_format_options', ['name' => 'headertextabovetiles']));
        $this->assertFalse($DB->record_exists('course_format_options', ['name' => 'prefixtitlewithnumber']));

        // Legacy booleans normalised.
        $this->assertEquals(0, $DB->get_field('course_format_options', 'value', [
            'courseid' => $course->id, 'name' => 'courseusebarforheadings', 'sectionid' => 0,
        ]));
        $this->assertEquals(1, $DB->get_field('course_format_options', 'value', [
            'courseid' => $coursetwo->id, 'name' => 'courseusebarforheadings', 'sectionid' => 0,
        ]));

        // Legacy icon names renamed.
        $this->assertContains('handshake-o', $DB->get_fieldset_select(
            'course_format_options',
            'value',
            'courseid = ? AND name = ?',
            [$coursetwo->id, 'tileicon']
        ));
        $this->assertContains('calendar-check-o', $DB->get_fieldset_select(
            'course_format_options',
            'value',
            'courseid = ? AND name = ?',
            [$coursetwo->id, 'defaulttileicon']
        ));

        // Browser preferences and removed settings are gone.
        $this->assertFalse($DB->record_exists('user_preferences', [
            'userid' => $user->id, 'name' => 'format_tiles_filterbutton_1',
        ]));
        $this->assertFalse($DB->record_exists('user_preferences', [
            'userid' => $user->id, 'name' => 'format_tiles_collapseseczero_2',
        ]));
        foreach (['persistfilterbuttons', 'fontimporttext', 'fontfamilytext', 'displaytileitems', 'showiconslist'] as $name) {
            $this->assertFalse(get_config('format_tiles', $name), "Config $name should have been removed");
        }
    }

    /**
     * The 2024020200 step creates the new tile options table and queues a migration per legacy course.
     */
    public function test_upgrade_2024020200_queues_legacy_migration(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('version', 2024020199, 'format_tiles');

        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['format' => 'tiles']);
        $course2 = $generator->create_course(['format' => 'tiles']);
        $topiccourse = $generator->create_course(['format' => 'topics']);

        // The real upgrade runs on a site without the new table yet, so remove it for this test.
        $table = new \xmldb_table('format_tiles_tile_options');
        $DB->get_manager()->drop_table($table);

        helper_set_legacy_tilephoto($course1->id, 1, 'placeholder_1.jpg');
        helper_set_legacy_tile_icon($course2->id, 2, 'map-o');
        // A leftover tiles option name on a non-tiles course must not queue a pointless migration.
        $DB->insert_record('course_format_options', (object)[
            'courseid' => $topiccourse->id,
            'format' => 'topics',
            'sectionid' => 0,
            'name' => 'tileicon',
            'value' => 'user',
        ]);

        $this->assertTrue(xmldb_format_tiles_upgrade(2024020199));

        // The table is created by the step.
        $this->assertTrue($DB->get_manager()->table_exists('format_tiles_tile_options'));

        $tasks = $DB->get_records('task_adhoc', [
            'component' => 'format_tiles',
            'classname' => '\format_tiles\task\migrate_legacy_data',
        ]);
        $queuedcourseids = [];
        foreach ($tasks as $task) {
            $queuedcourseids[] = (int)json_decode($task->customdata)->courseid;
        }
        sort($queuedcourseids);
        $expected = [(int)$course1->id, (int)$course2->id];
        sort($expected);
        $this->assertSame($expected, $queuedcourseids);
    }

    /**
     * The 2026102700 step re-queues legacy migrations whose adhoc tasks were lost.
     */
    public function test_upgrade_2026102700_requeues_lost_migration_tasks(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('version', 2026102201, 'format_tiles');

        $course = $this->getDataGenerator()->create_course(['format' => 'tiles'], ['createsections' => true]);
        helper_set_legacy_tilephoto($course->id, 1, 'placeholder_1.jpg');
        helper_set_legacy_tile_icon($course->id, 2, 'map-o');

        // The new options table already exists: the site upgraded to 5.2.0 long ago, but the tasks it queued were lost.
        $this->assertTrue($DB->get_manager()->table_exists('format_tiles_tile_options'));
        $DB->delete_records('task_adhoc', [
            'component' => 'format_tiles',
            'classname' => '\format_tiles\task\migrate_legacy_data',
        ]);

        $this->assertTrue(xmldb_format_tiles_upgrade(2026102201));

        $tasks = $DB->get_records('task_adhoc', [
            'component' => 'format_tiles',
            'classname' => '\format_tiles\task\migrate_legacy_data',
        ]);
        $this->assertCount(1, $tasks);
        $this->assertEquals($course->id, (int)json_decode(reset($tasks)->customdata)->courseid);

        // The migration itself is done by the task, so the legacy rows are still there.
        $this->assertTrue($DB->record_exists('course_format_options', [
            'courseid' => $course->id,
            'format' => 'tiles',
            'name' => 'tilephoto',
        ]));
    }

    /**
     * The 2026102700 step must not queue a second migration on sites upgrading from before 2024020200,
     * where the 2024020200 step queues it in the same run.
     */
    public function test_upgrade_2026102700_does_not_duplicate_pre_2024020200_queueing(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('version', 2024010100, 'format_tiles');

        $course = $this->getDataGenerator()->create_course(['format' => 'tiles'], ['createsections' => true]);
        helper_set_legacy_tilephoto($course->id, 1, 'placeholder_1.jpg');

        // A site upgrading from 2024 does not have the new options table yet.
        $DB->get_manager()->drop_table(new \xmldb_table('format_tiles_tile_options'));
        $DB->delete_records('task_adhoc', [
            'component' => 'format_tiles',
            'classname' => '\format_tiles\task\migrate_legacy_data',
        ]);

        $this->assertTrue(xmldb_format_tiles_upgrade(2024010100));

        $this->assertTrue($DB->get_manager()->table_exists('format_tiles_tile_options'));
        $this->assertCount(1, $DB->get_records('task_adhoc', [
            'component' => 'format_tiles',
            'classname' => '\format_tiles\task\migrate_legacy_data',
        ]));
    }

    /**
     * The 2025060267 step enables subsection progress counting by default.
     */
    public function test_upgrade_2025060267_sets_progress_default(): void {
        $this->resetAfterTest();
        unset_config('progressincludesubsections', 'format_tiles');
        set_config('version', 2025052752, 'format_tiles');

        $this->assertTrue(xmldb_format_tiles_upgrade(2025052752));

        $this->assertEquals(1, get_config('format_tiles', 'progressincludesubsections'));
    }

    /**
     * Upgrading from the previous release (5.1.0.2) must not change any stored data.
     */
    public function test_changeover_from_5102_is_a_noop(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();

        // 5.1.0.2 was plugin version 2026012570.
        set_config('version', 2026012570, 'format_tiles');

        $course = $this->getDataGenerator()->create_course(['format' => 'tiles'], ['createsections' => true]);
        $sectionid = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 1]);
        $user = $this->getDataGenerator()->create_user();

        $photooptionid = $this->seed_format_option($course->id, 'tilephoto', 'placeholder_1.jpg', $sectionid);
        $iconoptionid = $this->seed_format_option($course->id, 'tileicon', 'user', $sectionid);
        $baroptionid = $this->seed_format_option($course->id, 'courseusebarforheadings', 'standard');
        set_user_preference('format_tiles_filterbutton_1', 1, $user);

        // A photo migrated to the new table, with its stored file.
        $tileoptionid = $DB->insert_record('format_tiles_tile_options', (object)[
            'courseid' => $course->id,
            'optiontype' => format_option::OPTION_SECTION_PHOTO,
            'elementid' => $sectionid,
            'optionvalue' => 'placeholder_1.jpg',
        ]);
        $context = \context_course::instance($course->id);
        $fs = get_file_storage();
        $fs->create_file_from_pathname((object)[
            'contextid' => $context->id,
            'component' => 'format_tiles',
            'filearea' => 'tilephoto',
            'itemid' => $sectionid,
            'filepath' => '/tilephoto/',
            'filename' => 'placeholder_1.jpg',
            'mimetype' => 'image/jpeg',
        ], "$CFG->dirroot/course/format/tiles/tests/fixtures/images/placeholder_1.jpg");

        $before = [
            'photooption' => $DB->get_record('course_format_options', ['id' => $photooptionid], '*', MUST_EXIST),
            'iconoption' => $DB->get_record('course_format_options', ['id' => $iconoptionid], '*', MUST_EXIST),
            'baroption' => $DB->get_record('course_format_options', ['id' => $baroptionid], '*', MUST_EXIST),
            'tileoption' => $DB->get_record('format_tiles_tile_options', ['id' => $tileoptionid], '*', MUST_EXIST),
            'preference' => get_user_preferences('format_tiles_filterbutton_1', null, $user),
        ];

        $this->assertTrue(xmldb_format_tiles_upgrade(2026012570));

        $this->assertEquals(
            $before['photooption'],
            $DB->get_record('course_format_options', ['id' => $photooptionid], '*', MUST_EXIST)
        );
        $this->assertEquals(
            $before['iconoption'],
            $DB->get_record('course_format_options', ['id' => $iconoptionid], '*', MUST_EXIST)
        );
        $this->assertEquals(
            $before['baroption'],
            $DB->get_record('course_format_options', ['id' => $baroptionid], '*', MUST_EXIST),
            'The legacy bar option must not be normalised by an upgrade step from the current release'
        );
        $this->assertEquals(
            $before['tileoption'],
            $DB->get_record('format_tiles_tile_options', ['id' => $tileoptionid], '*', MUST_EXIST)
        );
        $this->assertSame($before['preference'], get_user_preferences('format_tiles_filterbutton_1', null, $user));
        $storedfile = $fs->get_file(
            $context->id,
            'format_tiles',
            'tilephoto',
            $sectionid,
            '/tilephoto/',
            'placeholder_1.jpg'
        );
        $this->assertNotEmpty($storedfile);
        $this->assertGreaterThan(0, $storedfile->get_filesize());
        // The 2026102700 step only re-queues the legacy migration for this course (its rows are migrated by the task),
        // so the upgrade itself still changes no stored data.
        $tasks = $DB->get_records('task_adhoc', [
            'component' => 'format_tiles',
            'classname' => '\format_tiles\task\migrate_legacy_data',
        ]);
        $this->assertCount(1, $tasks);
        $this->assertEquals($course->id, (int)json_decode(reset($tasks)->customdata)->courseid);
        $this->assertEquals(2026102700, get_config('format_tiles', 'version'));
    }
}
