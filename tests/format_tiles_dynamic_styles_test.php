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
 * Unit tests for the Tiles dynamic CSS output.
 *
 * @package    format_tiles
 * @copyright  2026 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the Tiles dynamic CSS output.
 *
 * @package    format_tiles
 * @copyright  2026 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_tiles\local\dynamic_styles::class)]
final class format_tiles_dynamic_styles_test extends \advanced_testcase {
    /**
     * The custom CSS admin setting is rendered inside a style element, so it must not be able
     * to close that element (or the surrounding script) and inject markup.
     */
    public function test_customcss_cannot_break_out_of_style_tag(): void {
        global $OUTPUT;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['format' => 'tiles'], ['createsections' => true]);
        set_config(
            'customcss',
            'body { color: red; }</style><script>alert(1)</script>',
            'format_tiles'
        );

        $css = $OUTPUT->render_from_template(
            'format_tiles/dynamic_styles',
            \format_tiles\local\dynamic_styles::data_for_template($course->id)
        );

        $this->assertStringContainsString('body { color: red; }', $css);
        $this->assertFalse(stripos($css, '</style'));
        $this->assertFalse(stripos($css, '</script'));
    }

    /**
     * The palette must repoint the high contrast text colour in dark mode, so tiles stay readable.
     */
    public function test_dark_mode_palette_repoints_high_contrast_text(): void {
        global $OUTPUT;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['format' => 'tiles'], ['createsections' => true]);

        $css = $OUTPUT->render_from_template(
            'format_tiles/dynamic_styles',
            \format_tiles\local\dynamic_styles::data_for_template($course->id)
        );

        $this->assertStringContainsString('[data-bs-theme="dark"]', $css);
        $this->assertStringContainsString('--format-tiles-high-contrast-black: #f8f9fa', $css);
        $this->assertMatchesRegularExpression('/--format-tiles-colour: rgb\(/', $css);
    }
}
