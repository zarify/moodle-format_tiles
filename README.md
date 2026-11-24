# Tiles Course Format

### Author: David Watson http://evolutioncode.uk

This is the "Tiles" course format plugin for Moodle.

## Installation and Use
This plugin should be installed in a sub directory of /course/format/ called tiles

Once installed:

- it will be available for an editing teacher to select as the format for a given course under Course Administration > Edit Settings > Course Format

- if this is done, the format will be displayed to a user when they enter that course (as student or teacher)

- an Administrator will be able to see the plugins settings under Site Administration > Plugins > Course Formats > Tiles

## This fork

This repository is a self-maintained fork of bycs-lp/moodle-format_tiles (upstream last release
5.1.0.2, January 2026), kept for the school site on the `master-forked` branch, which is this
fork's default branch. The base commit is upstream `b20b26c`.

**Supported Moodle versions:** 5.3 (LTS) – 6.0, declared as `$plugin->supported = [503, 600]`.
Moodle 4.5 already provides `\core_courseformat\formatactions::section()` and the
`core_courseformat\local\sectionactions` class. The 5.3 minimum is for the methods this fork
calls on that class: `set_marker()`, `remove_all_markers()` and `set_visibility()`. Those
methods are absent in Moodle 4.5, so lowering the version requirement would not make the fork
compatible.

Verified on Moodle 6.0dev (Build 20261021, core commit `46c042e7d`) and on Moodle 5.3 LTS with the
plugin's PHPUnit suite (`tests/`, including `tests/courseformat/` and `tests/privacy/`), its Behat
suite (`--tags=@format_tiles`) and a browser smoke test of the tile grid, activity modals, section
highlight/hide and dark mode. `tests/COVERAGE.md` records what the suite guards and what is still a
known gap.

## Moodle 6 compatibility

The fork removes every dependency on APIs that core has announced for final deprecation in 6.0:

- section marker and visibility actions (`course_set_marker()`, `set_section_visible()`) now use
  `\core_courseformat\formatactions::section($courseid)` with `set_marker()`,
  `remove_all_markers()` and `set_visibility()`;
- the tile highlight/unhighlight state actions and the section highlight menu item are local
  implementations adapted from core `format_topics` (GPL), so the format no longer needs the
  `format_topics` plugin installed;
- the format's own `content\cm\controlmenu` override was deleted (its method was already unused);
  core's `get_cm_control_items()` output is reached through the standard output-class fallback;
- the modal scripts report rejected modal creation, guard against concurrent icon-picker modals,
  restore keyboard focus after a manual completion toggle and no longer widen the PDF modal on
  every height check; the full-width toggle was removed because its header button overlapped the
  completion button in the Moodle 6.0 modal header;
- templates use the core `editsection` string (`editsummary` was removed in Moodle 5.2).

Two watch-points remain, both announced by core but not yet executed:

1. `numsections` / `maxsections` are still read when building the "new course" form. Core plans to
   remove them (MDL-85284, MDL-85272); when that lands, revisit `create_edit_form_elements()` in
   `lib.php` and `course_section_manager::get_max_sections()`.
2. This plugin carries copies of `core_courseformat/local/*` templates and edits the modal header
   DOM (`.modal-header`, `button.btn-close`). Re-port them when core changes those templates or
   `lib/templates/modal.mustache`.

## Boost drawer buttons on Moodle 5.3+

Since [MDL-88411](https://moodle.atlassian.net/browse/MDL-88411) the Boost course-index and
block-drawer openers are anchored to the content card instead of the screen edges. The Tiles
overview removes Boost's width cap on the course area, so on a wide window the closed openers are
drawn over the course header and the outer tiles. No banner is needed for that overlap.

The buttons belong to the Boost theme; this format neither renders nor positions them. To keep them
at the screen edges, append this block to the theme's Raw SCSS and save it under **Site
administration → Appearance → Themes → Edit theme settings 'Boost' → Advanced settings → Raw
SCSS** (the second Raw SCSS field, not **Raw initial SCSS**):

```scss
/* BEGIN Boost drawer edge placement */
#page.drawers .drawer-toggles .drawer-left-toggle {
    left: 0 !important;
    right: auto !important;
}

#page.drawers .drawer-toggles .drawer-right-toggle {
    left: auto !important;
    right: 0 !important;
}

#page.drawers .drawer-toggles .drawer-toggler > .btn {
    margin-inline: 0;
}

#page.drawers .drawer-toggles .drawer-left-toggle > .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

#page.drawers .drawer-toggles .drawer-right-toggle > .btn {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

// Keep the desktop panels at the same viewport edges as their openers.
// Boost anchors the open panels from state selectors such as
// body.limitedwidth:has(#page.drawers.show-drawer-right) .drawer.drawer-right,
// whose :has() arguments carry two IDs, so plain ID matching cannot outrank them.
@include media-breakpoint-up(lg) {
    body:is(.pagelayout-standard, .limitedwidth) #page-wrapper {
        > #theme_boost-drawers-courseindex {
            left: 0 !important;
            right: auto !important;
        }

        > #theme_boost-drawers-blocks {
            left: auto !important;
            right: 0 !important;
        }
    }
}
/* END Boost drawer edge placement */
```

SCSS already in that field is kept, and only the marked block is replaced when the snippet is
updated. The buttons return to their Moodle 4.5 placement at the screen edges, and the panels they
open sit at those same edges instead of beside the content card. Panel width, height and behaviour
are unchanged.

## Re-testing per Moodle release

Click an activity inside a tile and assert `.modal.show.embed_cm_modal` opens with a clean browser
console, then exercise **Highlight** and **Hide** on a section from the tile's section menu.

## Compatibility and Support
This version of the plugin is compatible with the Moodle version specified in the plugin's version.php file (under $plugin->supported).  Other versions are available for prior versions of Moodle.

It supports the Privacy API.

It is intended to support the current versions of the most popular browsers such as Chrome, Firefox, desktop Safari and mobile iOS and Android.

## Licence
This plugin is part of Moodle - http://moodle.org/

Moodle is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

Moodle is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Moodle.  If not, see http://www.gnu.org/licenses

Icons: some icons are custom to this plugin, but the majority are from Font Awesome version 4.7 under License SIL OFL 1.1.  See https://fontawesome.com/license and specifically https://fontawesome.com/v4.7.0/license