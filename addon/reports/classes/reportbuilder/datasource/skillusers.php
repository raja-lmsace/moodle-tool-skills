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
 * Pulse notification datasource for the schedules.
 *
 * @package   skilladdon_reports
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace skilladdon_reports\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\filters\select;

/**
 * Notification datasource definition for the list of schedules.
 */
class skillusers extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('skillusersreport', 'tool_skills');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        global $PAGE;

        $skillsentity = new \skilladdon_reports\local\entities\skills();

        $mainskillalias = $skillsentity->get_table_alias('tool_skills');
        $maxalias = $skillsentity->get_table_alias('tool_skills_levels_max');
        $levelalias = $skillsentity->get_table_alias('tool_skills_levels');

        $this->set_main_table('tool_skills', $mainskillalias);
        $this->add_entity($skillsentity);

        $maxleveljoin = "JOIN (
            SELECT skill, MAX(points) as maxpoints FROM {tool_skills_levels} GROUP BY skill
        ) {$maxalias} ON {$maxalias}.skill = {$mainskillalias}.id";
        $this->add_join($maxleveljoin);

        $statsentity = new \skilladdon_reports\local\entities\skills_stats();
        $coursealias = $statsentity->get_table_alias('tool_skills_courses_count');
        $this->add_join($statsentity->skill_stats_join());

        $this->add_entity($statsentity);

        // Support for 4.2.
        if (method_exists($this, 'add_all_from_entities')) {
            $this->add_all_from_entities();
        } else {
            // Add all the entities used in notification datasource. moodle 4.0 support.
            $this->add_columns_from_entity($skillsentity->get_entity_name());
            $this->add_filters_from_entity($skillsentity->get_entity_name());
            $this->add_conditions_from_entity($skillsentity->get_entity_name());

            // $this->add_columns_from_entity($userentity->get_entity_name());
            // $this->add_filters_from_entity($userentity->get_entity_name());
            // $this->add_conditions_from_entity($userentity->get_entity_name());

            // $this->add_columns_from_entity($coursentity->get_entity_name());
            // $this->add_filters_from_entity($coursentity->get_entity_name());
            // $this->add_conditions_from_entity($coursentity->get_entity_name());
        }

        // Init the script to show the notification content in the modal.
        // $params = ['contextid' => \context_system::instance()->id];
        // $PAGE->requires->js_call_amd('pulseaction_notification/chaptersource', 'reportModal', $params);
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {

        return [
            // 'course:fullname',
            // 'notification:messagetype',
            // 'notification:subject',
            // 'user:fullname',
            // 'notification:timecreated',
            // 'notification:scheduletime',
            // 'notification:status'
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return array
     */
    public function get_default_filters(): array {
        return [];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return array
     */
    public function get_default_conditions(): array {
        return [];
    }

}
