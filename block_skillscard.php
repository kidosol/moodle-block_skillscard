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

        $id = optional_param('id', 0, PARAM_INT);
        $user = $USER;

        // Load user.
        if (is_siteadmin() && $id) {
            $user = $DB->get_record('user', ['id' => $id], '*', MUST_EXIST);
        } else if ($id) {
            return null;
        }

        $this->content         = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        // Get data. 
        $sql = "SELECT mc.id, COALESCE(c.scaleid, cf.scaleid, 0) AS scaleidx, c.shortname AS compname, mc.grade AS grade
                  FROM {competency_usercomp} mc
                  JOIN {user} mu ON mu.id = mc.userid
                  JOIN {competency} c ON c.id = mc.competencyid
             LEFT JOIN {competency_framework} cf ON cf.id = c.competencyframeworkid
                 WHERE mc.userid = :userid
              ORDER BY mc.userid";

        $skillscard = $DB->get_records_sql($sql, ['userid' => $user->id]);
        if (empty($skillscard)) {
            $this->content->text = get_string('noskillscard', 'block_skillscard');
            return $this->content;
        }

        // Render data.
        $items = '';
        foreach ($skillscard as $sc) {
            $values = $DB->get_field('scale', 'scale', ['id' => $sc->scaleidx]);
            $scales = $values ? explode(',', $values) : [];
            $grade = $scales[$sc->grade - 1] ?? '';
            $skill = format_text($sc->compname, FORMAT_PLAIN);

            $icon = html_writer::tag('i', '', [
                'class' => 'fa fa-trophy fa-5x text-primary',
                'style' => 'float: left; padding: 10px;',
            ]);
            $content = html_writer::tag('span', $icon) . html_writer::empty_tag('br');
            if ($grade !== '') {
                $content .= get_string('rank', 'block_skillscard') . ' ' . s($grade) . html_writer::empty_tag('br');
            }
            $content .= get_string('competency', 'block_skillscard') . ' ' . $skill;

            $items .= html_writer::tag('li', $content, ['style' => 'text-align: center;']);
            $items .= html_writer::tag('div', '', ['style' => 'clear: both;']);
        }

        $this->content->text .= html_writer::tag('ul', $items, ['style' => 'list-style: none;']);
        return $this->content;
    }
}
