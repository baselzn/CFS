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
 * Document approval page for CAA document approval
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
$level = required_param('level', PARAM_INT); // Approval level

$cm = get_coursemodule_from_id('caadocapproval', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$caadocapproval = $DB->get_record('caadocapproval', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check capability for this level
$capability = 'mod/caadocapproval:approve_level' . $level;
require_capability($capability, $context);

// Initialize the caadocapproval module
$caadocmodule = new \mod_caadocapproval\module($cm, $course, $caadocapproval);

// Get document
$document = caadocapproval_get_document($docid);
if (!$document || $document->caadocapproval != $caadocapproval->id) {
    print_error('invaliddocument', 'caadocapproval');
}

// Check if user is an approver for this document/level
if (!caadocapproval_is_approver($docid, $USER->id, $level)) {
    print_error('notanapprover', 'caadocapproval');
}

// Check document is in the correct state for this approval level
$expected_status = 0;
switch ($level) {
    case 1:
        $expected_status = CAADOCAPPROVAL_STATUS_SUBMITTED;
        break;
    case 2:
        $expected_status = CAADOCAPPROVAL_STATUS_APPROVED_L1;
        break;
    case 3:
        $expected_status = CAADOCAPPROVAL_STATUS_APPROVED_L2;
        break;
    default:
        print_error('invalidapprovalrequest', 'caadocapproval');
}

if ($document->status != $expected_status) {
    print_error('invalidapprovalstate', 'caadocapproval');
}

// Set up the page
$PAGE->set_url('/mod/caadocapproval/approve.php', array('id' => $cm->id, 'docid' => $docid, 'level' => $level));
$PAGE->set_title(format_string($caadocapproval->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Create the approval form
class mod_caadocapproval_approval_form extends moodleform {
    
    protected function definition() {
        $mform = $this->_form;
        $document = $this->_customdata['document'];
        $level = $this->_customdata['level'];
        $categories = caadocapproval_get_categories();
        
        // Document information
        $mform->addElement('header', 'documentheader', get_string('document_details', 'caadocapproval'));
        
        $category = $categories[$document->category]['title'];
        $doctype = $categories[$document->category]['required_docs'][$document->doc_type];
        
        $mform->addElement('static', 'category', get_string('category', 'caadocapproval'), $category);
        $mform->addElement('static', 'doctype', get_string('document_type', 'caadocapproval'), $doctype);
        $mform->addElement('static', 'filename', get_string('filename', 'caadocapproval'), $document->filename);
        
        // Approval options
        $mform->addElement('header', 'approvalheader', get_string('approval_options', 'caadocapproval'));
        
        $mform->addElement('textarea', 'comments', get_string('approval_comments', 'caadocapproval'), 
                          array('rows' => 5, 'cols' => 50));
        $mform->setType('comments', PARAM_TEXT);
        
        // Hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'docid');
        $mform->setType('docid', PARAM_INT);
        
        $mform->addElement('hidden', 'level');
        $mform->setType('level', PARAM_INT);
        
        // Add buttons
        $this->add_action_buttons(true, get_string('approve', 'caadocapproval'));
        
        // Add reject button
        $mform->registerNoSubmitButton('reject');
        $mform->addElement('submit', 'reject', get_string('reject', 'caadocapproval'), array('class' => 'btn btn-danger'));
    }
    
    /**
     * Add validation for comment field when rejecting
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // If reject button was clicked, comments are required
        if (!empty($data['reject']) && empty($data['comments'])) {
            $errors['comments'] = get_string('required');
        }
        
        return $errors;
    }
}

// Setup form
$customdata = array(
    'document' => $document,
    'level' => $level
);

$mform = new mod_caadocapproval_approval_form(null, $customdata);

// Set default data
$formdata = new stdClass();
$formdata->id = $cm->id;
$formdata->docid = $docid;
$formdata->level = $level;
$mform->set_data($formdata);

// Process the form
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/caadocapproval/view.php', 
                         array('id' => $cm->id, 'category' => $document->category)));
    
} else if ($data = $mform->get_data()) {
    if (!empty($data->reject)) {
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
    } else {
        // Approve document
        if ($caadocmodule->approve_document($docid, $level)) {
            redirect(new moodle_url('/mod/caadocapproval/view.php', 
                                 array('id' => $cm->id, 'category' => $document->category)),
                    get_string('document_approved', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('/mod/caadocapproval/view.php', 
                                 array('id' => $cm->id, 'category' => $document->category)),
                    get_string('document_approve_failed', 'caadocapproval'), 
                    null, \core\output\notification::NOTIFY_ERROR);
        }
    }
}

// Display the document viewer and approval form
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('approve_document', 'caadocapproval'));

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

// Display approval form
$mform->display();
echo $OUTPUT->footer();
