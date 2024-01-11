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

use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\filters\{date, number, select, text};
use core_reportbuilder\local\helpers\format;
use html_writer;
use lang_string;
use stdClass;

/**
 * Pulse notification entity base for report source.
 */
class category extends base {

    /**
     * Database tables that this entity uses and their default aliases.
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'category' => 'ctg',
        ];
    }

    /**
     * The default machine-readable name for this entity that will be used in the internal names of the columns/filters.
     *
     * @return string
     */
    protected function get_default_entity_name(): string {
        return 'category';
    }

    /**
     * The default title for this entity in the list of columns/filters in the report builder.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entitycategory', 'tool_skills');
    }

    /**
     * Initialise the entity, adding all course and custom course fields
     *
     * @return base
     */
    public function initialise(): base {

        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_condition($filter)
                ->add_filter($filter);
        }

        return $this;
    }

    /**
     * Return syntax for joining on the context table
     *
     * @return string
     */
    public function get_context_join(): string {
        $coursealias = $this->get_table_alias('course');
        $contextalias = $this->get_table_alias('context');

        return "LEFT JOIN {context} {$contextalias}
            ON {$contextalias}.contextlevel = " . CONTEXT_COURSE . "
           AND {$contextalias}.instanceid = {$coursealias}.id";
    }

    /**
     * Course fields.
     *
     * @return array
     */
    protected function get_category_fields(): array {

        return [
            'name' => new lang_string('categoryname', 'tool_skills'),
            'idnumber' => new lang_string('categoryidnumber', 'tool_skills'),
            'description' => new lang_string('description'),
            'coursecount' => new lang_string('coursecount', 'tool_skills'),
            'visible' => new lang_string('categoryvisiblity', 'tool_skills'),
            'timemodified' => new lang_string('timemodified', 'tool_skills'),
            'depth' => new lang_string('depth', 'tool_skills'),
            'path' => new lang_string('path', 'tool_skills'),
            'theme' => new lang_string('forcetheme'),
        ];
    }

    /**
     * Check if this field is sortable
     *
     * @param string $fieldname
     * @return bool
     */
    protected function is_sortable(string $fieldname): bool {
        // Some columns can't be sorted, like longtext or images.
        $nonsortable = [
            'description',
        ];

        return !in_array($fieldname, $nonsortable);
    }

    /**
     * Return appropriate column type for given user field
     *
     * @param string $coursefield
     * @return int
     */
    protected function get_category_field_type(string $coursefield): int {
        switch ($coursefield) {
            case 'visible':
                $fieldtype = column::TYPE_BOOLEAN;
                break;
            case 'timemodified':
                $fieldtype = column::TYPE_TIMESTAMP;
                break;
            case 'description':
                $fieldtype = column::TYPE_LONGTEXT;
                break;
            case 'coursecount':
                $fieldtype = column::TYPE_INTEGER;
                break;
            case 'idnumber':
            case 'depth':
            case 'path':
            case 'theme':
            default:
                $fieldtype = column::TYPE_TEXT;
                break;
        }

        return $fieldtype;
    }

    /**
     * Returns list of all available columns.
     *
     * These are all the columns available to use in any report that uses this entity.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        global $DB;

        $categoryfields = $this->get_category_fields();
        $tablealias = $this->get_table_alias('category');

        foreach ($categoryfields as $coursefield => $coursefieldlang) {
            $columntype = $this->get_category_field_type($coursefield);

            $columnfieldsql = "{$tablealias}.{$coursefield}";
            if ($columntype === column::TYPE_LONGTEXT && $DB->get_dbfamily() === 'oracle') {
                $columnfieldsql = $DB->sql_order_by_text($columnfieldsql, 1024);
            }

            $column = (new column(
                $coursefield,
                $coursefieldlang,
                $this->get_entity_name()
            ))
                ->add_joins($this->get_joins())
                ->set_type($columntype)
                ->add_field($columnfieldsql, $coursefield)
                ->add_callback([$this, 'format'], $coursefield)
                ->set_is_sortable($this->is_sortable($coursefield));

            // Join on the context table so that we can use it for formatting these columns later.
           /*  if ($coursefield === 'summary' || $coursefield === 'shortname' || $coursefield === 'fullname') {
                $column->add_join($this->get_context_join())
                    ->add_field("{$tablealias}.id", 'courseid')
                    ->add_fields(context_helper::get_preload_record_columns_sql($contexttablealias));
            } */

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Returns list of all available filters
     *
     * @return array
     */
    protected function get_all_filters(): array {
        global $DB;

        $filters = [];
        $tablealias = $this->get_table_alias('category');

        $fields = $this->get_category_fields();
        foreach ($fields as $field => $name) {
            $filterfieldsql = "{$tablealias}.{$field}";
            if ($this->get_category_field_type($field) === column::TYPE_LONGTEXT) {
                $filterfieldsql = $DB->sql_cast_to_char($filterfieldsql);
            }

            $optionscallback = [static::class, 'get_options_for_' . $field];
            if (is_callable($optionscallback)) {
                $filterclass = select::class;
            } else if ($this->get_category_field_type($field) === column::TYPE_BOOLEAN) {
                $filterclass = boolean_select::class;
            } else if ($this->get_category_field_type($field) === column::TYPE_TIMESTAMP) {
                $filterclass = date::class;
            } else {
                $filterclass = text::class;
            }

            $filter = (new filter(
                $filterclass,
                $field,
                $name,
                $this->get_entity_name(),
                $filterfieldsql
            ))
            ->add_joins($this->get_joins());

            // Populate filter options by callback, if available.
            if (is_callable($optionscallback)) {
                $filter->set_options_callback($optionscallback);
            }

            $filters[] = $filter;
        }

        // We add our own custom course selector filter.
        /* $filters[] = (new filter(
            course_selector::class,
            'courseselector',
            new lang_string('courseselect', 'core_reportbuilder'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))
            ->add_joins($this->get_joins()); */

        return $filters;
    }

    /**
     * Formats the course field for display.
     *
     * @param mixed $value Current field value.
     * @param stdClass $row Complete row.
     * @param string $fieldname Name of the field to format.
     * @return string
     */
    public function format($value, stdClass $row, string $fieldname): string {

        if ($this->get_category_field_type($fieldname) === column::TYPE_TIMESTAMP) {
            return format::userdate($value, $row);
        }

        $options = $this->get_options_for($fieldname);
        if ($options !== null && array_key_exists($value, $options)) {
            return $options[$value];
        }

        if ($this->get_category_field_type($fieldname) === column::TYPE_BOOLEAN) {
            return format::boolean_as_text($value);
        }

        if (in_array($fieldname, ['name'])) {

            return format_string($value, true, ['escape' => false]);
        }

        if (in_array($fieldname, ['description'])) {
            if (!$row->courseid) {
                return '';
            }
            context_helper::preload_from_record($row);
            $context = \context_course::instance($row->id);
            $description = file_rewrite_pluginfile_urls($row->description, 'pluginfile.php', $context->id, 'coursecategory', 'description', null);
            return format_text($description);
        }

        return s($value);
    }

    /**
     * Gets list of options if the filter supports it
     *
     * @param string $fieldname
     * @return null|array
     */
    protected function get_options_for(string $fieldname): ?array {
        static $cached = [];
        if (!array_key_exists($fieldname, $cached)) {
            $callable = [static::class, 'get_options_for_' . $fieldname];
            if (is_callable($callable)) {
                $cached[$fieldname] = $callable();
            } else {
                $cached[$fieldname] = null;
            }
        }
        return $cached[$fieldname];
    }
    /**
     * List of options for the field theme.
     *
     * @return array
     */
    public static function get_options_for_theme(): array {
        $options = [];

        $themeobjects = get_list_of_themes();
        foreach ($themeobjects as $key => $theme) {
            if (empty($theme->hidefromselector)) {
                $options[$key] = get_string('pluginname', "theme_{$theme->name}");
            }
        }

        return $options;
    }
}
