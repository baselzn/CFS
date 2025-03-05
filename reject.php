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
 * Document rejection page for CAA document approval
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$docid = required_param('docid', PARAM_INT); // Document ID

$cm = get_coursemodule_from_id('caadocapproval', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$caadocapproval = $DB->get_record('caadocapproval', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check if user has any approval rights
$hasapprovalcap = false;
for ($i = 1; $i <= 3; $i++) {
    if (has_capability('mod/caadocapproval:approve_level' . $i, $context)) {
        $hasapprovalcap = true;
        break;
    }
}

if (!$hasapprovalcap) {
    print_error('nopermissions', 'error', '', 'reject document');
}

// Initialize the caadocapproval module
$caadocmodule = new \mod_caadocapproval\module($cm, $course, $caadocapproval);

// Get document
$document = caadocapproval_get_document($docid);
if (!$document || $document->caadocapproval != $caadocapproval->id) {
    print_error('invaliddocument', 'caadocapproval');
}

// Document must be in a reviewable state (not draft, not already approved/rejected)
if ($document->status == CAADOCAPPROVAL_STATUS_DRAFT || 
    $document->status == CAADOCAPPROVAL_STATUS_APPROVED_FINAL ||
    $document->status == CAADOCAPPROVAL_STATUS_REJECTED) {
    print_error('invalidrejectionstate', 'caadocapproval');
}

// Check if user is an approver for the current approval level
$next_level = caadocapproval_get_next_approval_level($document);
if ($next_level == 0 || !caadocapproval_is_approver($docid, $USER->id, $next_level)) {
    print_error('notanapprover', 'caadocapproval');
}

// Set up the page
$PAGE->set_url('/mod/caadocapproval/reject.php', array('id' => $cm->id, 'docid' => $docid));
$PAGE->set_title(format_string($caadocapproval->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Create the rejection form
class mod_caadocapproval_rejection_form extends moodleform {
    
    protected function definition() {
        $mform = $this->_form;
        $document = $this->_customdata['document'];
        $categories = caadocapproval_get_categories();
        
        // Document information
        $mform->addElement('header', 'documentheader', get_string('document_details', 'caadocapproval'));
        
        $category = $categories[$document->category]['title'];
        $doctype = $categories[$document->category]['required_docs'][$document->doc_type];
        
        $mform->addElement('static', 'category', get_string('category', 'caadocapproval'), $category);
        $mform->addElement('static', 'doctype', get_string('document_type', 'caadocapproval'), $doctype);
        $mform->addElement('static', 'filename', get_string('filename', 'caadocapproval'), $document->filename);
        
        // Rejection reason
        $mform->addElement('header', 'rejectionheader', get_string('rejection_reason', 'caadocapproval'));
        
        $mform->addElement('textarea', 'comments', get_string('rejection_comments', 'caadocapproval'), 
                          array('rows' => 5, 'cols' => 50));
        $mform->setType('comments', PARAM_TEXT);
        $mform->addRule('comments', null, 'required', null, 'client');
        
        // Hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'docid');
        $mform->setType('docid', PARAM_INT);
        
        // Add buttons
        $this->add_action_buttons(true, get_string('reject', 'caadocapproval'));
    }
}

// Setup form
$customdata = array(
    'document' => $document
);

$mform = new mod_caadocapproval_rejection_form(null, $customdata);

// Set default data
$formdata = new stdClass();
$formdata->id = $cm->id;
$formdata->docid = $docid;
$mform->set_data($formdata);

// Process the form
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/caadocapproval/view.php', 
                         array('id' => $cm->id, 'category' => $document->category)));
    
} else if ($data = $mform->get_data()) {
    // Reject document
    if ($caadocmodule->reject_document($docid, $data->comments)) {
        redirect(new moodle_url('/mod/caadocapproval/view.php', 
                             array('id' => $cm->id, 'category' => $document->category)),
                get_string('document_rejected', 'caadocapproval'), 
                null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/mod/caadocapproval/view.php', 
                             array('id' => $cm->id, 'category' => $document->category)),
                get_string('document_reject_failed', 'caadocapproval'), 
                null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Display the document viewer and rejection form
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reject_document', 'caadocapproval'));

// Display document viewer
$fs = get_file_storage();
$filepath_parts = explode('/', $document->filepath);
$filename = array_pop($filepath_parts);
$itemid = intval($filepath_parts[2]); // Extract itemid from filepath
$file = $fs->get_file($context->id, 'mod_caadocapproval', 'document', $itemid, '/', $filename);

if ($file) {
    echo '<div class="caadocapproval-document-preview mb-4">';
    echo $OUTPUT->heading($document->filename, 4);
    
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
    
    echo '</div>';
}

// Display rejection form
$mform->display();
echo $OUTPUT->footer();
