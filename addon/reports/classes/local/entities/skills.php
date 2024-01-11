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
class skills extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {

        return [
            'user' => 'sku',
            'context' => 'skctx',
            'course' => 'skc',
            'tool_skills' => 'sk',
            'tool_skills_levels' => 'sksl',
            'tool_skills_courses' => 'sksc',
            'tool_skills_userpoints' => 'skup',
            'tool_skills_levels_max' => 'sklm',
            'cohort_members' => 'skchtm',
            'cohort' => 'skcht'
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('pluginname', 'tool_skills');
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

    protected function include_skills_columns(&$columns) {

        $skillsalias = $this->get_table_alias('tool_skills');
        $maxalias = $this->get_table_alias('tool_skills_levels_max');

        // Name of the skill.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$skillsalias}.name")
        ->add_callback(static function ($value, $row): string {
            return format_string($value);
        });

        // Key of the skill.
        $columns[] = (new column(
            'key',
            new lang_string('identitykey', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$skillsalias}.identitykey");

        // Description of the skill.
        $columns[] = (new column(
            'description',
            new lang_string('description'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$skillsalias}.description")
        ->add_callback(static function ($value, $row): string {
            return format_text($value);
        });

        // Availbility of the skill.
        $columns[] = (new column(
            'status',
            new lang_string('status'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$skillsalias}.status")
        ->add_callback(static function ($value, $row): string {
            return $value == \tool_skills\skills::STATUS_ENABLE
                ? get_string('enabled', 'tool_skills') : get_string('disabled', 'tool_skills');
        });

        // Color of the skill.
        $columns[] = (new column(
            'color',
            new lang_string('skillcolor', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$skillsalias}.color")
        ->add_callback(static function ($value, $row): string {
            return $value.\html_writer::tag('span', '', ['class' => 'p-1', 'style' => 'background-color:'.$value]);
        });

        // Maximum point for the skill.
        $columns[] = (new column(
            'maximum',
            new lang_string('maximum', 'tool_skills'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$maxalias}.maxpoints")
        /* ->add_callback(static function ($value, $row): string {
            return \tool_skills\skills::get($value)->get_points_to_earnskill();
        }) */;

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
    public function schedulejoin() {

        // $notificationschalias = $this->get_table_alias('pulseaction_notification_sch');

        // $autoinstancesalias = $this->get_table_alias('pulse_autoinstances');
        // $autotemplatesalias = $this->get_table_alias('pulse_autotemplates');
        // $autotemplatesinsalias = $this->get_table_alias('pulse_autotemplates_ins');
        // $notificationinsalias = $this->get_table_alias('pulseaction_notification_ins');
        // $notificationalias = $this->get_table_alias('pulseaction_notification');

        // return "
        //     JOIN {pulse_autoinstances} {$autoinstancesalias} ON {$autoinstancesalias}.id = {$notificationschalias}.instanceid
        //     JOIN {pulse_autotemplates} {$autotemplatesalias} ON {$autotemplatesalias}.id = {$autoinstancesalias}.templateid
        //     JOIN {pulse_autotemplates_ins} {$autotemplatesinsalias}
        //         ON {$autotemplatesinsalias}.instanceid = {$autoinstancesalias}.id
        //     JOIN {pulseaction_notification_ins} {$notificationinsalias}
        //         ON {$notificationinsalias}.instanceid = {$notificationschalias}.instanceid
        //     JOIN {pulseaction_notification} {$notificationalias}
        //         ON {$notificationalias}.templateid = {$autoinstancesalias}.templateid";
    }
}
