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
use core_cohort\reportbuilder\local\entities\cohort;
use core_cohort\reportbuilder\local\entities\cohort_member;
use core_reportbuilder\local\filters\select;
use core_course\reportbuilder\local\entities\course_category;

/**
 * Notification datasource definition for the list of schedules.
 */
class skills extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('formtab', 'tool_skills');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        global $PAGE;

        $skillsentity = new \skilladdon_reports\local\entities\skills();

        $mainskillalias = $skillsentity->get_table_alias('tool_skills');
        $maxalias = $skillsentity->get_table_alias('tool_skills_levels_max');
        $skillcoursealias = $skillsentity->get_table_alias('tool_skills_courses');
        $userpointalias = $skillsentity->get_table_alias('tool_skills_userpoints');

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

        $maxleveljoin = "JOIN (
            SELECT skill, MAX(points) as maxpoints FROM {tool_skills_levels} GROUP BY skill
        ) {$maxalias} ON {$maxalias}.skill = {$mainskillalias}.id";
        $this->add_join($maxleveljoin);

        // Force the join to be added so that course fields can be added first.
        // $this->add_join($skillsentity->schedulejoin());

        // Add core user join.
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity);
        // User stats entity.
        $userstatsentity = new \skilladdon_reports\local\entities\skills_user_stats();
        $this->add_entity($userstatsentity);
        // User and user stats join.
        $joins['user'] = "LEFT JOIN {tool_skills_userpoints} {$userpointalias} ON {$userpointalias}.skill = {$mainskillalias}.id
        JOIN {user} {$useralias} ON {$useralias}.id = {$userpointalias}.userid";


        $cohortmementity = new cohort_member();
        $cohortmemalias = $cohortmementity->get_table_alias('cohort_members');
        $cohortentity = new cohort();
        $cohortentity = $cohortentity->set_table_alias('cohort', 'cht');
        $cohortalias = $cohortentity->get_table_alias('cohort');
        $cohortjoin = " JOIN {cohort_members} {$cohortmemalias} ON {$cohortmemalias}.userid = {$userpointalias}.userid
        JOIN {cohort} {$cohortalias} ON {$cohortalias}.id = {$cohortmemalias}.cohortid";
        $this->add_entity($cohortentity->add_join($cohortjoin));

        // Skill course entity.
        $coursentity = new course();
        $coursealias = $coursentity->get_table_alias('course');
        $joins['course'] = "LEFT JOIN {tool_skills_courses} {$skillcoursealias} ON {$skillcoursealias}.skill = {$mainskillalias}.id
            LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$skillcoursealias}.courseid";
        $this->add_entity($coursentity);

        // Category entity.
        $categoryentity = new course_category();
        $categoryalias = $categoryentity->get_table_alias('course_categories');
        $joins['category'] = "LEFT JOIN {course_categories} {$categoryalias} ON {$categoryalias}.id = {$coursealias}.category";
        $this->add_entity($categoryentity);

        // Modules entity.
        $activityentity = new \skilladdon_reports\local\entities\skills_activities();
        $courseactivity = $activityentity->get_table_alias('tool_skills_course_activity');
        // Joins activity.
        $joins['activity'] = "LEFT JOIN {tool_skills_course_activity} {$courseactivity}
            ON {$courseactivity}.skill = {$mainskillalias}.id AND {$courseactivity}.uponmodcompletion != 0 ";

        $this->add_entity($activityentity);

        // Skills activities entity.
        $modcompletionentity = new \skilladdon_reports\local\entities\skills_activities_completion();
        $this->add_entity($modcompletionentity);

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

        // Add the joins to the datasource related to the entity,
        // For some purpose, added the joins after the columns are added to the datasource instead of mention the join in columns.
        $this->update_column_joins($joins);
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

    /**
     * Perform some basic validation about expected class properties
     *
     * @throws coding_exception
     */
    protected function update_column_joins(array $joins): void {

        $courseactivityincluded = false;
        $activityjoin = false;
        $userjoin = false;
        $coursejoin = false;
        $categoryjoin = false;

        foreach ($this->get_active_columns() as $column) {

            // Category columns included.
            if ($column->get_entity_name() == 'course_category') {
                $coursejoin = true;
                $categoryjoin = true;
            }

            // Enable the course join flag.
            if ($column->get_entity_name() == 'course') {
                $coursejoin = true;
            }

            // Skills activities.
            if (in_array($column->get_entity_name(), ['skills_activities', 'skills_activities_completion'])) {
                $activityjoin = true;
            }

            // User field joined.
            if (in_array($column->get_entity_name(), ['skills_user_stats', 'user', 'cohort_member'])) {
                $userjoin = true;
            }
        }

        // User join, Include the users join first.
        if ($userjoin) {
            $this->add_join($joins['user']);
        }

        // Course join.
        if ($coursejoin) {
            $this->add_join($joins['course']);
        }
        // Add the category join.
        if ($categoryjoin) {
            $this->add_join($joins['category']);
        }

        // Activity join.
        if ($activityjoin) {
            $this->add_join($joins['activity']);
        }

        // Course and activity join enable then need to show the activites based on courses.
        if ($coursejoin && $activityjoin) {
            $coursentity = new course();
            $coursealias = $coursentity->get_table_alias('course');
            $activityentity = new \skilladdon_reports\local\entities\skills_activities();
            $courseactivity = $activityentity->get_table_alias('tool_skills_course_activity');
            // Add the condition.
            $this->add_base_condition_sql(" {$courseactivity}.courseid = {$coursealias}.id ");
        }
    }

}
