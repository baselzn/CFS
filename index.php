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
 * Display information about all the caadocapproval modules in the requested course
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT); // Course ID

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);
$PAGE->set_pagelayout('incourse');

// Get all required strings
$strname = get_string('name');
$strcaadocapprovals = get_string('modulenameplural', 'caadocapproval');
$strcaadocapproval  = get_string('modulename', 'caadocapproval');

// Set page properties
$PAGE->set_url('/mod/caadocapproval/index.php', array('id' => $id));
$PAGE->set_title("$course->shortname: $strcaadocapprovals");
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strcaadocapprovals);

echo $OUTPUT->header();
echo $OUTPUT->heading($strcaadocapprovals);

// Get all the appropriate data
if (!$caadocapprovals = get_all_instances_in_course('caadocapproval', $course)) {
    notice(get_string('nocaadocapprovals', 'caadocapproval'), new moodle_url('/course/view.php', array('id' => $course->id)));
    echo $OUTPUT->footer();
    die();
}

// Print the list of instances
$usesections = course_format_uses_sections($course->format);
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array($strsectionname, $strname);
    $table->align = array('center', 'left');
} else {
    $table->head  = array($strname);
    $table->align = array('left');
}

foreach ($caadocapprovals as $caadocapproval) {
    if (!$caadocapproval->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/caadocapproval/view.php', array('id' => $caadocapproval->coursemodule)),
            format_string($caadocapproval->name, true),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/caadocapproval/view.php', array('id' => $caadocapproval->coursemodule)),
            format_string($caadocapproval->name, true));
    }

    if ($usesections) {
        $table->data[] = array(get_section_name($course, $caadocapproval->section), $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();