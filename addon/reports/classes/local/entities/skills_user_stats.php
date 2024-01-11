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
 * Pulse notification entities for report builder.
 *
 * @package   skilladdon_reports
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace skilladdon_reports\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\filters\{date, number, select, text};
use lang_string;

/**
 * Pulse notification entity base for report source.
 */
class skills_user_stats extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {

        return [
            'tool_skills' => 'sk',
            'tool_skills_levels' => 'sksl',
            'tool_skills_courses' => 'sksc',
            'tool_skills_userpoints' => 'skup',
            'tool_skills_levels_max' => 'sklm',
            'tool_skills_courses_count' => 'skcc',
            'tool_skills_userpoints_count' => 'skupc'
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('userskillentity', 'tool_skills');
    }

    /**
     * Initialise the notification datasource columns and filter, conditions.
     *
     * @return base
     */
    public function initialise(): base {

        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        list($filters, $conditions) = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        foreach ($conditions as $condition) {
            $this->add_condition($condition);
        }

        return $this;
    }

    /**
     * List of columns available for this notfication datasource.
     *
     * @return array
     */
    protected function get_all_columns(): array {

        $columns = [];

        $skillalias = $this->get_table_alias('tool_skills');
        $userpoints = $this->get_table_alias('tool_skills_userpoints');
        $skillcoursealias = $this->get_table_alias('tool_skills_userpoints');

        // Points user earned for this skill.
        $columns[] = (new column(
            'points',
            new lang_string('pointsearned', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$userpoints}.points")
        ->add_callback(static function ($value, $row): string {
            return $value ?: 0;
        });

        // User points created time.
        $columns[] = (new column(
            'userpointstimecreated',
            new lang_string('timecreated'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$userpoints}.timecreated")
        ->add_callback(static function ($value, $row): string {
            return $value ? userdate($value, get_string('strftimedatetime', 'langconfig')) : '-';
        });

        // Proficients of the skill.
        $columns[] = (new column(
            'userpointstimemodified',
            new lang_string('timemodified', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$userpoints}.timemodified")
        ->add_callback(static function ($value, $row): string {
            return $value ? userdate($value, get_string('strftimedatetime', 'langconfig')) : '-';
        });

        return $columns;
    }


    /**
     * Defined filters for the notification entities.
     *
     * @return array
     */
    protected function get_all_filters(): array {
        global $DB;

        return [[], []];
    }

}
