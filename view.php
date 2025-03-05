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
 * Displays the CAA document approval activity
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$d  = optional_param('d', 0, PARAM_INT);  // Instance ID
$action = optional_param('action', '', PARAM_ALPHA);
$category = optional_param('category', '', PARAM_ALPHA);
$docid = optional_param('docid', 0, PARAM_INT);
$doc_type = optional_param('doc_type', '', PARAM_ALPHA);

if ($id) {
    $cm             = get_coursemodule_from_id('caadocapproval', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $caadocapproval = $DB->get_record('caadocapproval', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($d) {
    $caadocapproval = $DB->get_record('caadocapproval', array('id' => $d), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $caadocapproval->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('caadocapproval', $caadocapproval->id, $course->id, false, MUST_EXIST);
} else {
    print_error('missingidorinstanceparameter');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Initialize the caadocapproval module
$caadocmodule = new \mod_caadocapproval\module($cm, $course, $caadocapproval);

// Get categories to check if the requested one exists
$categories = caadocapproval_get_categories();

// Handle category validation and fix underscore issues
if (!empty($category)) {
    // Try to find the category both with and without underscores
    if (!isset($categories[$category])) {
        // Try to match without considering underscores
        $found = false;
        foreach (array_keys($categories) as $cat_key) {
            // Check if the category matches when removing underscores
            if (str_replace('_', '', $cat_key) === $category) {
                $category = $cat_key; // Use the correct category key
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            \core\notification::warning(get_string('invalidcategory', 'caadocapproval'));
            $category = ''; // Reset to empty to show dashboard
        }
    }
}

// Process actions
if ($action) {
    // Check if the form was submitted
    if ($action === 'submit' && $docid) {
        require_capability('mod/caadocapproval:submit', $context);
        if ($caadocmodule->submit_document($docid)) {
            // Create a new redirect URL without the action parameter to avoid redirect loops
            $redirect_url = new moodle_url('/mod/caadocapproval/view.php', 
                                    array('id' => $cm->id, 'category' => $category));
            redirect($redirect_url, get_string('document_submitted', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Create a new redirect URL without the action parameter to avoid redirect loops
            $redirect_url = new moodle_url('/mod/caadocapproval/view.php', 
                                    array('id' => $cm->id, 'category' => $category));
            redirect($redirect_url, get_string('document_submit_failed', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_ERROR);
        }
    } else if ($action === 'approve' && $docid) {
        $level = required_param('level', PARAM_INT);
        $capability = 'mod/caadocapproval:approve_level' . $level;
        require_capability($capability, $context);
        
        if ($caadocmodule->approve_document($docid, $level)) {
            // Create a new redirect URL without the action parameter to avoid redirect loops
            $redirect_url = new moodle_url('/mod/caadocapproval/view.php', 
                                    array('id' => $cm->id, 'category' => $category));
            redirect($redirect_url, get_string('document_approved', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Create a new redirect URL without the action parameter to avoid redirect loops
            $redirect_url = new moodle_url('/mod/caadocapproval/view.php', 
                                    array('id' => $cm->id, 'category' => $category));
            redirect($redirect_url, get_string('document_approve_failed', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_ERROR);
        }
    } else if ($action === 'reject' && $docid) {
        // Find any approval capability
        $hasapprovalcap = false;
        for ($i = 1; $i <= 3; $i++) {
            if (has_capability('mod/caadocapproval:approve_level' . $i, $context)) {
                $hasapprovalcap = true;
                break;
            }
        }
        require_capability('mod/caadocapproval:approve_level1', $context);
        
        $comments = required_param('comments', PARAM_TEXT);
        if ($caadocmodule->reject_document($docid, $comments)) {
            // Create a new redirect URL without the action parameter to avoid redirect loops
            $redirect_url = new moodle_url('/mod/caadocapproval/view.php', 
                                    array('id' => $cm->id, 'category' => $category));
            redirect($redirect_url, get_string('document_rejected', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_ERROR);
        } else {
            // Create a new redirect URL without the action parameter to avoid redirect loops
            $redirect_url = new moodle_url('/mod/caadocapproval/view.php', 
                                    array('id' => $cm->id, 'category' => $category));
            redirect($redirect_url, get_string('document_reject_failed', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_ERROR);
        }
    } else if ($action === 'upload') {
        require_capability('mod/caadocapproval:submit', $context);
        
        // This will redirect to upload form handled by another file
        redirect(new moodle_url('/mod/caadocapproval/upload.php', 
                            array('id' => $cm->id, 'category' => $category, 'doc_type' => $doc_type)));
    }
}

// Trigger module viewed event after all actions and redirects
$event = \mod_caadocapproval\event\course_module_viewed::create(array(
    'objectid' => $cm->instance,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('caadocapproval', $caadocapproval);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $category));
$PAGE->set_title(format_string($caadocapproval->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here.
echo $OUTPUT->header();

// Intro content if any
if (!empty($caadocapproval->intro)) {
    echo $OUTPUT->box(format_module_intro('caadocapproval', $caadocapproval, $cm->id), 'generalbox', 'intro');
}

// Display the main content
$renderer = $PAGE->get_renderer('mod_caadocapproval');
if (!empty($category)) {
    // Display specific category
    echo $renderer->display_category_documents($caadocmodule, $category);
} else {
    // Display the dashboard with categories
    echo $renderer->display_categories($caadocmodule);
}

// Finish the page.
echo $OUTPUT->footer();