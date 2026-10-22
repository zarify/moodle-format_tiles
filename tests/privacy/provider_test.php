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
 * Privacy provider tests for format tiles.
 *
 * @package    format_tiles
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the format tiles privacy provider.
 *
 * @copyright  2025 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_tiles\privacy\provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * The metadata lists both user preferences.
     */
    public function test_get_metadata(): void {
        $collection = new collection('format_tiles');
        $collection = provider::get_metadata($collection);
        $names = [];
        foreach ($collection->get_collection() as $item) {
            $names[] = $item->get_name();
        }
        sort($names);
        $this->assertSame([
            'format_tiles_high_contrast_mode',
            'format_tiles_stopjsnav',
        ], $names);
    }

    /**
     * Both preferences are exported with their descriptions.
     */
    public function test_export_user_preferences(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        set_user_preference('format_tiles_stopjsnav', 1, $user);
        set_user_preference('format_tiles_high_contrast_mode', 1, $user);

        provider::export_user_preferences($user->id);

        $writer = writer::with_context(\context_system::instance());
        $this->assertTrue($writer->has_any_data());
        $preferences = $writer->get_user_preferences('format_tiles');
        $this->assertCount(2, (array)$preferences);
        $this->assertEquals(get_string('yes'), $preferences->format_tiles_stopjsnav->value);
        $this->assertEquals(get_string('yes'), $preferences->format_tiles_high_contrast_mode->value);
        $this->assertEquals(
            get_string('privacy:metadata:preference:format_tiles_stopjsnav', 'format_tiles'),
            $preferences->format_tiles_stopjsnav->description
        );
        $this->assertEquals(
            get_string('privacy:metadata:preference:format_tiles_high_contrast_mode', 'format_tiles'),
            $preferences->format_tiles_high_contrast_mode->description
        );
    }

    /**
     * A user who has never changed the preferences exports them with their defaults.
     */
    public function test_export_user_preferences_without_preferences(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);

        $writer = writer::with_context(\context_system::instance());
        $preferences = $writer->get_user_preferences('format_tiles');
        $this->assertCount(2, (array)$preferences);
        $this->assertEquals(get_string('no'), $preferences->format_tiles_stopjsnav->value);
        $this->assertEquals(get_string('no'), $preferences->format_tiles_high_contrast_mode->value);
    }
}
