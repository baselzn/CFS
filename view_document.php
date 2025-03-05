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
 * View document details for CAA document approval
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$docid = required_param('docid', PARAM_INT); // Document ID

$cm = get_coursemodule_from_id('caadocapproval', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$caadocapproval = $DB->get_record('caadocapproval', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caadocapproval:view', $context);

// Initialize the caadocapproval module
$caadocmodule = new \mod_caadocapproval\module($cm, $course, $caadocapproval);

// Get document
$document = caadocapproval_get_document($docid);
if (!$document || $document->caadocapproval != $caadocapproval->id) {
    print_error('invaliddocument', 'caadocapproval');
}

// Set up the page
$PAGE->set_url('/mod/caadocapproval/view_document.php', array('id' => $cm->id, 'docid' => $docid));
$PAGE->set_title(format_string($caadocapproval->name . ': ' . $document->filename));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here
echo $OUTPUT->header();

// Get category and document type information
$categories = caadocapproval_get_categories();
$category_title = $categories[$document->category]['title'];
$doc_type_title = $categories[$document->category]['required_docs'][$document->doc_type];

echo $OUTPUT->heading($document->filename);

// Document details
echo html_writer::start_div('caadocapproval-document-details mb-4');
echo html_writer::tag('h4', get_string('document_details', 'caadocapproval'));

echo html_writer::start_tag('table', array('class' => 'table table-bordered'));
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('category', 'caadocapproval'));
echo html_writer::tag('td', $category_title);
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('document_type', 'caadocapproval'));
echo html_writer::tag('td', $doc_type_title);
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('status', 'caadocapproval'));
$status_label = caadocapproval_get_status_label($document->status);
$status_class = caadocapproval_get_status_class($document->status);
echo html_writer::tag('td', html_writer::tag('span', $status_label, array('class' => $status_class)));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('submitted_by', 'caadocapproval'));
$submitter = $DB->get_record('user', array('id' => $document->userid), 'id, firstname, lastname');
echo html_writer::tag('td', fullname($submitter));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('submission_date', 'caadocapproval'));
echo html_writer::tag('td', userdate($document->timecreated, get_string('strftimedatetime', 'langconfig')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('last_updated', 'caadocapproval'));
echo html_writer::tag('td', userdate($document->timemodified, get_string('strftimedatetime', 'langconfig')));
echo html_writer::end_tag('tr');

echo html_writer::end_tag('table');
echo html_writer::end_div();

// Document viewer
echo html_writer::start_div('caadocapproval-document-preview mb-4');
echo html_writer::tag('h4', get_string('document_preview', 'caadocapproval'));

// Display document
$fs = get_file_storage();
$filepath_parts = explode('/', $document->filepath);
$filename = array_pop($filepath_parts);
$itemid = intval($filepath_parts[2]); // Extract itemid from filepath
$file = $fs->get_file($context->id, 'mod_caadocapproval', 'document', $itemid, '/', $filename);

if ($file) {
    // Handle different file types
    $mimetype = $file->get_mimetype();
    if (strpos($mimetype, 'pdf') !== false) {
        // PDF viewer
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                                           $file->get_filearea(), $file->get_itemid(),
                                           $file->get_filepath(), $file->get_filename());
        echo '<div class="d-flex justify-content-center">';
        echo '<iframe src="'.$url.'" width="800" height="600" class="border"></iframe>';
        echo '</div>';
    } else {
        // Download link for other file types
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                                           $file->get_filearea(), $file->get_itemid(),
                                           $file->get_filepath(), $file->get_filename());
        echo '<p>'.get_string('file_preview_unavailable', 'caadocapproval').'</p>';
        echo '<a href="'.$url.'" class="btn btn-primary">'.get_string('download_file', 'caadocapproval').'</a>';
    }
} else {
    echo html_writer::tag('div', get_string('file_not_found', 'caadocapproval'), array('class' => 'alert alert-warning'));
}

echo html_writer::end_div();

// Approval history
echo html_writer::start_div('caadocapproval-approval-history mb-4');
echo html_writer::tag('h4', get_string('approval_history', 'caadocapproval'));

$approvals = caadocapproval_get_approvals($docid);
if (!empty($approvals)) {
    echo html_writer::start_tag('table', array('class' => 'table table-striped'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('approval_level', 'caadocapproval'));
    echo html_writer::tag('th', get_string('approved_by', 'caadocapproval'));
    echo html_writer::tag('th', get_string('approval_date', 'caadocapproval'));
    echo html_writer::tag('th', get_string('comments', 'caadocapproval'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    foreach ($approvals as $approval) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', get_string('level'.$approval->level.'_approver', 'caadocapproval'));
        
        $approver = $DB->get_record('user', array('id' => $approval->userid), 'id, firstname, lastname');
        echo html_writer::tag('td', fullname($approver));
        
        echo html_writer::tag('td', userdate($approval->timeapproved, get_string('strftimedatetime', 'langconfig')));
        echo html_writer::tag('td', $approval->comments);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo html_writer::tag('p', get_string('no_approvals_yet', 'caadocapproval'), array('class' => 'text-muted'));
}
echo html_writer::end_div();

// Rejection history
$rejections = caadocapproval_get_rejections($docid);
if (!empty($rejections)) {
    echo html_writer::start_div('caadocapproval-rejection-history mb-4');
    echo html_writer::tag('h4', get_string('rejection_history', 'caadocapproval'));
    
    echo html_writer::start_tag('table', array('class' => 'table table-striped'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('rejected_by', 'caadocapproval'));
    echo html_writer::tag('th', get_string('rejection_date', 'caadocapproval'));
    echo html_writer::tag('th', get_string('rejection_reason', 'caadocapproval'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    foreach ($rejections as $rejection) {
        echo html_writer::start_tag('tr');
        
        $rejector = $DB->get_record('user', array('id' => $rejection->userid), 'id, firstname, lastname');
        echo html_writer::tag('td', fullname($rejector));
        
        echo html_writer::tag('td', userdate($rejection->timerejected, get_string('strftimedatetime', 'langconfig')));
        echo html_writer::tag('td', $rejection->comments);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

// Action buttons
echo html_writer::start_div('caadocapproval-actions mt-4');

$category_url = new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $document->category));
echo html_writer::link($category_url, get_string('back_to_category', 'caadocapproval'), array('class' => 'btn btn-secondary mr-2'));

// Submit button for draft documents
if ($document->status == CAADOCAPPROVAL_STATUS_DRAFT && 
    ($document->userid == $USER->id || has_capability('mod/caadocapproval:submitany', $context))) {
    
    $submit_url = new moodle_url('/mod/caadocapproval/view.php', 
                              array('id' => $cm->id, 'category' => $document->category, 
                                    'action' => 'submit', 'docid' => $docid));
    
    echo html_writer::link($submit_url, get_string('submit_for_approval', 'caadocapproval'), 
                         array('class' => 'btn btn-primary mr-2'));
}

// Approve button for appropriate level and approver
$next_level = caadocapproval_get_next_approval_level($document);
if ($next_level > 0 && 
    has_capability('mod/caadocapproval:approve_level' . $next_level, $context) &&
    caadocapproval_is_approver($docid, $USER->id, $next_level)) {
    
    $approve_url = new moodle_url('/mod/caadocapproval/approve.php', 
                               array('id' => $cm->id, 'docid' => $docid, 
                                     'level' => $next_level));
    
    echo html_writer::link($approve_url, get_string('approve', 'caadocapproval'), 
                         array('class' => 'btn btn-success mr-2'));
    
    $reject_url = new moodle_url('/mod/caadocapproval/reject.php', 
                              array('id' => $cm->id, 'docid' => $docid));
    
    echo html_writer::link($reject_url, get_string('reject', 'caadocapproval'), 
                         array('class' => 'btn btn-danger'));
}

echo html_writer::end_div();

// Finish the page
echo $OUTPUT->footer();