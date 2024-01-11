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
class skills_activities extends base {

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
            'tool_skills_course_activity' => 'sksa',
            'tool_skills_levels_max' => 'sklm',
            'tool_skills_courses_count' => 'skcc',
            'tool_skills_userpoints_count' => 'skupc',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('activitiesentity', 'tool_skills');
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
        $skillcoursealias = $this->get_table_alias('tool_skills_courses');
        $userpoints = $this->get_table_alias('tool_skills_userpoints');
        $skillmodsalias = $this->get_table_alias('tool_skills_course_activity');
        $levelalias = $this->get_table_alias('tool_skills_levels');

        // Name of the skill.
        $columns[] = (new column(
            'activityname',
            new lang_string('activityname', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$skillmodsalias}.modid")
        ->add_callback(static function ($value, $row): string {
            $mod = get_coursemodule_from_id('', $value, $row->courseid ?? 0);
            return $mod->name ?? '';
        });

        // Key of the skill.
        $columns[] = (new column(
            'modname',
            new lang_string('modname', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$skillmodsalias}.modid")
        ->add_callback(static function ($value, $row): string {
            return get_coursemodule_from_id('', $value)->modname ?: '';
        });

        // Proficients of the skill.
        $columns[] = (new column(
            'activitydescription',
            new lang_string('description', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$skillmodsalias}.modid")
        ->add_callback(static function ($value, $row): string {
            return format_string(get_coursemodule_from_id('', $value)->intro);
        });

        // Upon completion of module.
        $columns[] = (new column(
            'uponmodcompletion',
            new lang_string('uponmodcompletion', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$skillmodsalias}.uponmodcompletion")
        ->add_callback(static function ($value, $row): string {
            $options = \skilladdon_activityskills\form\course_mod_form::get_uponcompletion_options();
            return $options[$value] ?? '';
        });

        // Points earned for the completion of mods.
        $columns[] = (new column(
            'modpoints',
            new lang_string('completionpoints', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$skillmodsalias}.points");

        // Mod configured to reach the level.
        $columns[] = (new column(
            'activitylevel',
            new lang_string('completionlevel', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_join("LEFT JOIN {tool_skills_levels} {$levelalias} ON {$levelalias}.id = {$skillmodsalias}.level")
        ->add_field("{$levelalias}.name")
        ->add_callback(static function ($value, $row): string {
            return format_string($value);
        });

        // Upon completion of module.
        $columns[] = (new column(
            'modstimemodified',
            new lang_string('timemodified', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_joins($this->get_joins())
        ->add_field("{$skillmodsalias}.timemodified")
        ->add_callback(static function ($value, $row): string {
            return $value ? userdate($value, get_string('strftimedatetime', 'langconfig')) : '-';
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

}
