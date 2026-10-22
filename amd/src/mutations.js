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
 * Format tiles mutations.
 *
 * An instance of this class is used to add custom mutations to the course editor.
 * To make sure the addMutations method finds the proper functions, all mutations must be
 * declared as class attributes, not simple methods: several plugins can add extra
 * mutations to the course editor.
 *
 * Adapted from the core format_topics mutations module (copyright 2022 Ferran Recio,
 * GPL v3 or later) so that the tiles format does not depend on the format_topics plugin.
 *
 * @module      format_tiles/mutations
 * @copyright   2025 David Watson {@link http://evolutioncode.uk}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core_courseformat/courseeditor',
    'core_courseformat/local/courseeditor/mutations',
    'core_courseformat/local/content/actions',
], function(CourseEditor, DefaultMutations, CourseActions) {
    "use strict";

    class TilesMutations extends DefaultMutations {

        /**
         * Highlight a section.
         *
         * It is important to note this mutation method is declared as a class attribute,
         * see the class jsdoc for more details on why.
         *
         * @param {StateManager} stateManager the current state manager
         * @param {array} sectionIds the list of section ids
         */
        sectionHighlight = async function(stateManager, sectionIds) {
            const logEntry = this._getLoggerEntry(
                stateManager,
                'section_highlight',
                sectionIds,
                {component: 'format_tiles'}
            );
            const course = stateManager.get('course');
            this.sectionLock(stateManager, sectionIds, true);
            const updates = await this._callEditWebservice('section_highlight', course.id, sectionIds);
            stateManager.processUpdates(updates);
            this.sectionLock(stateManager, sectionIds, false);
            stateManager.addLoggerEntry(await logEntry);
        };

        /**
         * Unhighlight a section.
         *
         * It is important to note this mutation method is declared as a class attribute,
         * see the class jsdoc for more details on why.
         *
         * @param {StateManager} stateManager the current state manager
         * @param {array} sectionIds the list of section ids
         */
        sectionUnhighlight = async function(stateManager, sectionIds) {
            const logEntry = this._getLoggerEntry(
                stateManager,
                'section_unhighlight',
                sectionIds,
                {component: 'format_tiles'}
            );
            const course = stateManager.get('course');
            this.sectionLock(stateManager, sectionIds, true);
            const updates = await this._callEditWebservice('section_unhighlight', course.id, sectionIds);
            stateManager.processUpdates(updates);
            this.sectionLock(stateManager, sectionIds, false);
            stateManager.addLoggerEntry(await logEntry);
        };
    }

    return {
        /**
         * Register the tiles mutations and content actions.
         */
        init: function() {
            const courseEditor = CourseEditor.getCurrentCourseEditor();
            if (!courseEditor) {
                return;
            }
            // Some plugin (activity or block) may have their own mutations already registered,
            // which is why addMutations is used instead of setMutations here.
            courseEditor.addMutations(new TilesMutations());
            // Add direct mutation content actions.
            CourseActions.addActions({
                sectionHighlight: 'sectionHighlight',
                sectionUnhighlight: 'sectionUnhighlight',
            });
        }
    };
});
