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
use core_reportbuilder\local\helpers\format;
use html_writer;
use lang_string;

/**
 * Pulse notification entity base for report source.
 */
class skills_stats extends base {

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
        return new lang_string('skillstats', 'tool_skills');
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
        $this->include_skills_columns($columns);


        return $columns;
    }

    /**
     * Undocumented function
     *
     * @param [type] $columns
     * @return void
     */
    protected function include_skills_columns(&$columns) {

        $skillalias = $this->get_table_alias('tool_skills');
        $skillcoursealias = $this->get_table_alias('tool_skills_courses_count');
        $userpoints = $this->get_table_alias('tool_skills_userpoints_count');

        // Name of the skill.
        $columns[] = (new column(
            'coursescount',
            new lang_string('coursesused', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$skillcoursealias}.coursescount")
        ->add_callback(static function ($value, $row): string {
            return $value ?: 0;
        });

        // Key of the skill.
        $columns[] = (new column(
            'userscount',
            new lang_string('skillusers', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$userpoints}.userscount")
        ->add_callback(static function ($value, $row): string {
            return $value ?: 0;
        });

        // Proficients of the skill.
        $columns[] = (new column(
            'proficientusers',
            new lang_string('skillproficients', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$skillalias}.id")
        ->add_callback(static function ($value, $row): string {
            $userslist = \tool_skills\skills::get($value)->get_proficient();
            return !empty($userslist) ? count($userslist) : 0;
        });

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

    /**
     * Schedule join sql.
     *
     * @return string
     */
    public function skill_stats_join() {

        $coursecountalias = $this->get_table_alias('tool_skills_courses_count');
        $userpointsalias = $this->get_table_alias('tool_skills_userpoints_count');

        $skillalias = $this->get_table_alias('tool_skills');

        return " LEFT JOIN (
                SELECT skill, count(*) as coursescount FROM {tool_skills_courses} tsc GROUP BY tsc.skill
            ) {$coursecountalias} ON {$coursecountalias}.skill = {$skillalias}.id
            LEFT JOIN (
                SELECT skill, count(*) as userscount FROM {tool_skills_userpoints} tsc GROUP BY tsc.skill
            ) {$userpointsalias} ON {$userpointsalias}.skill = {$skillalias}.id
            ";

    }
}
