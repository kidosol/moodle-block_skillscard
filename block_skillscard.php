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
 * Skills Card block.
 *
 * @package    block_skillscard
 * @copyright  2022 Tengku Alauddin <din@pukunui.com>
 * @author     Vinny Stocker <vinny@pukunui.com>
 * @copyright  2026 Pukunui Malaysia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Skills Card block implementation.
 */
class block_skillscard extends block_base {

    /**
     * Initialise the block title.
     */
    public function init() {
        $this->title = get_string('skillscard', 'block_skillscard');
    }

    /**
     * Allow multiple instances of this block.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Get the block content.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $userid = optional_param('id', $USER->id, PARAM_INT);

        // Security check: Only admins can view other people's cards.
        if ($userid != $USER->id && !is_siteadmin()) {
            return null;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Optimized SQL: Join the scale table here so we don't query inside the loop.
        $sql = "SELECT mc.id, 
                       c.shortname AS compname, 
                       mc.grade, 
                       s.scale AS scalerecord
                  FROM {competency_usercomp} mc
                  JOIN {competency} c ON c.id = mc.competencyid
             LEFT JOIN {competency_framework} cf ON cf.id = c.competencyframeworkid
             LEFT JOIN {scale} s ON s.id = COALESCE(c.scaleid, cf.scaleid)
                 WHERE mc.userid = :userid
              ORDER BY c.shortname ASC";

        $skillscard = $DB->get_records_sql($sql, ['userid' => $userid]);

        if (empty($skillscard)) {
            $this->content->text = get_string('noskillscard', 'block_skillscard');
            return $this->content;
        }

        $items = '';
        foreach ($skillscard as $sc) {
            $grade = '';
            if (!empty($sc->scalerecord) && !empty($sc->grade)) {
                $scales = explode(',', $sc->scalerecord);
                // Scales in Moodle are 1-indexed.
                $grade = $scales[$sc->grade - 1] ?? '';
            }

            $skillname = format_string($sc->compname);

            // Icon with Bootstrap padding and float classes.
            $icon = html_writer::tag('i', '', [
                'class' => 'fa fa-trophy fa-5x text-primary float-left p-2',
            ]);

            $content = html_writer::span($icon);
            $content .= html_writer::empty_tag('br');

            if ($grade !== '') {
                $content .= html_writer::tag('div', 
                    get_string('rank', 'block_skillscard') . ' ' . s($grade), 
                    ['class' => 'font-weight-bold']
                );
            }

            $content .= html_writer::tag('div', 
                get_string('competency', 'block_skillscard') . ' ' . $skillname
            );

            // Item wrapper using Bootstrap 'text-center' and 'clearfix'.
            $items .= html_writer::tag('li', $content, [
                'class' => 'text-center clearfix mb-3',
            ]);
        }

        $this->content->text = html_writer::tag('ul', $items, [
            'class' => 'list-unstyled',
        ]);

        return $this->content;
    }
}
