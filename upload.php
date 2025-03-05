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
 * Upload form for CAA document approval
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/repository/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$category = required_param('category', PARAM_ALPHA);
$doc_type = required_param('doc_type', PARAM_ALPHA);

$cm = get_coursemodule_from_id('caadocapproval', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$caadocapproval = $DB->get_record('caadocapproval', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caadocapproval:submit', $context);

// Initialize the caadocapproval module
$caadocmodule = new \mod_caadocapproval\module($cm, $course, $caadocapproval);

// Get categories
$categories = caadocapproval_get_categories();

// Handle category without underscores
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
        throw new moodle_exception('invalidcategory', 'caadocapproval');
    }
}

// Validate category and doc_type
if (!isset($categories[$category]) || !isset($categories[$category]['required_docs'][$doc_type])) {
    throw new moodle_exception('invalidcategorydoctype', 'caadocapproval');
}

// Set up the page
$PAGE->set_url('/mod/caadocapproval/upload.php', array('id' => $cm->id, 'category' => $category, 'doc_type' => $doc_type));
$PAGE->set_title(format_string($caadocapproval->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Create the upload form
class mod_caadocapproval_upload_form extends moodleform {
    
    protected function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $category = $this->_customdata['category'];
        $doc_type = $this->_customdata['doc_type'];
        $doc_title = $this->_customdata['doc_title'];
        
        // Document information
        $mform->addElement('header', 'documentheader', get_string('upload_document', 'caadocapproval', $doc_title));
        
        // File picker
        $fileoptions = array(
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => 1,
            'accepted_types' => array('.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx')
        );
        
        $mform->addElement('filepicker', 'documentfile', get_string('file'), null, $fileoptions);
        $mform->addRule('documentfile', null, 'required', null, 'client');
        
        // Hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_ALPHA);
        
        $mform->addElement('hidden', 'doc_type');
        $mform->setType('doc_type', PARAM_ALPHA);
        
        // Add action buttons
        $this->add_action_buttons(true, get_string('upload', 'caadocapproval'));
    }
}

// Setup form
$customdata = array(
    'category' => $category,
    'doc_type' => $doc_type,
    'doc_title' => $categories[$category]['required_docs'][$doc_type]
);

$mform = new mod_caadocapproval_upload_form(null, $customdata);

// Set default data
$formdata = new stdClass();
$formdata->id = $cm->id;
$formdata->category = $category;
$formdata->doc_type = $doc_type;
$mform->set_data($formdata);

// Process the form
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $category)));
    
} else if ($data = $mform->get_data()) {
    // Save the uploaded file
    $fs = get_file_storage();
    $draftitemid = $data->documentfile;
    
    if (empty($draftitemid)) {
        redirect(new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $category)),
                get_string('nofileselected', 'repository'), null, \core\output\notification::NOTIFY_ERROR);
    }
    
    $usercontext = context_user::instance($USER->id);
    
    // Get file info from draft area
    $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);
    
    if (empty($draftfiles)) {
        redirect(new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $category)),
                get_string('nofileselected', 'repository'), null, \core\output\notification::NOTIFY_ERROR);
    }
    
    $file = reset($draftfiles);  // Get first (and only) file
    $filename = $file->get_filename();
    
    // Prepare file record
    $filerecord = new stdClass();
    $filerecord->contextid = $context->id;
    $filerecord->component = 'mod_caadocapproval';
    $filerecord->filearea = 'document';
    $filerecord->filepath = '/';
    $filerecord->filename = $filename;
    $filerecord->itemid = time(); // Use timestamp as itemid
    $filerecord->source = $filename;
    
    // Create new file from draft file
    $newfile = $fs->create_file_from_storedfile($filerecord, $file);
    
    if ($newfile) {
        // Create document record
        $filepath = '/'.$filerecord->filearea.'/'.$filerecord->itemid.$filerecord->filepath.$filerecord->filename;
        $docid = $caadocmodule->add_document($category, $doc_type, $filename, $filepath, $USER->id);
        
        if ($docid) {
            redirect(new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $category)),
                    get_string('document_uploaded', 'caadocapproval'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $category)),
                    get_string('document_upload_failed', 'caadocapproval'), null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        redirect(new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $category)),
                get_string('filenotfound', 'caadocapproval'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Display the form
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('upload_document', 'caadocapproval', $categories[$category]['required_docs'][$doc_type]));
$mform->display();
echo $OUTPUT->footer();