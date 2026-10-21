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
 * Contains the default section controls output class.
 *
 * @package   format_tiles
 * @copyright 2022 David Watson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_tiles\output\courseformat\content\section;

use context_course;
use core_courseformat\output\local\content\section\controlmenu as controlmenu_base;
use core_courseformat\base as course_format;
use core\output\action_menu\link_secondary;
use core\output\pix_icon;

/**
 * Base class to render a course section menu.
 *
 * @package   format_tiles
 * @copyright 2022 David Watson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends controlmenu_base {
    /** @var course_format the course format class */
    protected $format;

    /** @var \section_info the course section class */
    protected $section;

    /**
     * Generate the edit control items of a section.
     *
     * This method must remain public until the final deprecation of section_edit_control_items.
     *
     * @return array of edit control items
     */
    public function section_control_items() {

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $sectionreturn = $format->get_sectionnum();
        $parentcontrols = parent::section_control_items();

        if ($section->section === 0) {
            return $parentcontrols;
        }

        // Unset the view item that points to /course/section.php.
        unset($parentcontrols['view']);

        $coursecontext = context_course::instance($course->id);

        if ($sectionreturn) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = [];

        $controls['setphoto'] = new link_secondary(
            url:  new \moodle_url(
                '/course/format/tiles/editor/editimage.php',
                ['sectionid' => $section->id]
            ),
            icon: new pix_icon('i/messagecontentimage', ''),
            text: get_string('setbackgroundphoto', 'format_tiles'),
            attributes: [
                'class' => 'editing_update', 'data-tiles-action' => 'launch-tiles-icon-picker',
                'data-section' => $section->section,
                'data-true-sectionid' => $section->id,
            ],
        );

        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            $controls['highlight'] = $this->get_section_highlight_item();
        }

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = [];
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Retrieves the highlight (marker) item for the section control menu.
     *
     * Adapted from the core format_topics section controlmenu (copyright 2020 Ferran Recio, GPL v3 or later)
     * so the tiles format does not depend on the format_topics plugin.
     *
     * @return link_secondary The menu item if applicable, otherwise null.
     */
    protected function get_section_highlight_item(): link_secondary {
        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();
        $sectionreturn = $format->get_sectionnum();

        $highlightoff = get_string('highlightoff');
        $highlightofficon = 'i/marked';

        $highlighton = get_string('highlight');
        $highlightonicon = 'i/marker';

        if ($course->marker == $section->sectionnum) {  // Show the "light globe" on/off.
            $action = 'section_unhighlight';
            $icon = $highlightofficon;
            $name = $highlightoff;
            $attributes = [
                'class' => 'editing_highlight',
                'data-action' => 'sectionUnhighlight',
                'data-sectionreturn' => $sectionreturn,
                'data-id' => $section->id,
                'data-icon' => $highlightofficon,
                'data-swapname' => $highlighton,
                'data-swapicon' => $highlightonicon,
            ];
        } else {
            $action = 'section_highlight';
            $icon = $highlightonicon;
            $name = $highlighton;
            $attributes = [
                'class' => 'editing_highlight',
                'data-action' => 'sectionHighlight',
                'data-sectionreturn' => $sectionreturn,
                'data-id' => $section->id,
                'data-icon' => $highlightonicon,
                'data-swapname' => $highlightoff,
                'data-swapicon' => $highlightofficon,
            ];
        }

        $url = $this->format->get_update_url(
            action: $action,
            ids: [$section->id],
            returnurl: $this->baseurl,
            returnsection: $this->section,
            returnoptions: $this->returnoptions,
        );

        return new link_secondary(
                url: $url,
                icon: new pix_icon($icon, ''),
                text: $name,
                attributes: $attributes,
        );
    }
}
