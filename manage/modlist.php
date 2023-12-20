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
 * Tool Skills - Manage course module skills list.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_skills\moduleskills;

 // Require config.
require(__DIR__.'/../../../../config.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

// Get parameters.
$courseid = required_param('courseid', PARAM_INT);
$skillid = optional_param('skill', null, PARAM_INT);
$modid = optional_param('modid', null, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

if ($skillid && $courseid) {
    $skillcourse = $DB->get_record('tool_skills_courses', ['skill' => $skillid, 'courseid' => $courseid]);
}

// Optional params.
$action = optional_param('action', null, PARAM_ALPHAEXT);

// Get system context.
$context = \context_course::instance($courseid);

// Login check required.
require_login();
// Access checks.
require_capability('tool/skills:managecourseskills', $context);


// Prepare the page (to make sure that all necessary information is already set even if we just handle the actions as a start).
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/skills/manage/modlist.php', ['courseid' => $courseid, 'modid' => $modid]));
$PAGE->set_cacheable(false);
$PAGE->set_course($course);
//$PAGE->set_pagetype('mod-'.$)
$PAGE->set_heading(format_string($course->fullname));

// Further prepare the page.
$PAGE->set_title(get_string('moduleskills', 'tool_skills'));
$PAGE->navbar->add(get_string('mycourses', 'core'), new moodle_url('/course/index.php'));
$PAGE->navbar->add(format_string($course->shortname), new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('skills', 'tool_skills'),
    new moodle_url('/admin/tool/skills/manage/modlist.php', ['courseid' => $courseid, 'modid' => $modid]));

// Build skills table.
$table = new \tool_skills\table\module_skills_table($courseid, $modid);
$table->define_baseurl($PAGE->url);

// Header.
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('assignmodskills', 'tool_skills'));

// Skills description.
echo get_string('assignmodeskills_desc', 'tool_skills');

$countmenus = $DB->count_records('tool_skills');
if ($countmenus < 1) {

    $table->out(0, true);
} else {
    $table->out(50, true);
    $PAGE->requires->js_call_amd('tool_skills/modskills', 'init', ['courseid' => $courseid, 'modid' => $modid]);
}

// Footer.
echo $OUTPUT->footer();