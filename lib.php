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
 * Library of interface functions and constants for module caadocapproval
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * CAA Document status constants
 */
define('CAADOCAPPROVAL_STATUS_DRAFT', 0);
define('CAADOCAPPROVAL_STATUS_SUBMITTED', 1);
define('CAADOCAPPROVAL_STATUS_APPROVED_L1', 2);
define('CAADOCAPPROVAL_STATUS_APPROVED_L2', 3);
define('CAADOCAPPROVAL_STATUS_APPROVED_FINAL', 4);
define('CAADOCAPPROVAL_STATUS_REJECTED', 5);

/**
 * CAA Document categories
 */
function caadocapproval_get_categories() {
    return [
        'course_specs' => [
            'title' => get_string('category_course_specs', 'caadocapproval'),
            'required_docs' => [
                'syllabus' => get_string('doc_syllabus', 'caadocapproval'),
                'learning_outcomes' => get_string('doc_learning_outcomes', 'caadocapproval'),
                'assessment_scheme' => get_string('doc_assessment_scheme', 'caadocapproval')
            ]
        ],
        'teaching_materials' => [
            'title' => get_string('category_teaching_materials', 'caadocapproval'),
            'required_docs' => [
                'textbooks' => get_string('doc_textbooks', 'caadocapproval'),
                'lecture_notes' => get_string('doc_lecture_notes', 'caadocapproval'),
                'multimedia' => get_string('doc_multimedia', 'caadocapproval')
            ]
        ],
        'assessment_tools' => [
            'title' => get_string('category_assessment_tools', 'caadocapproval'),
            'required_docs' => [
                'exams' => get_string('doc_exams', 'caadocapproval'),
                'assignments' => get_string('doc_assignments', 'caadocapproval'),
                'rubrics' => get_string('doc_rubrics', 'caadocapproval')
            ]
        ],
        'course_delivery' => [
            'title' => get_string('category_course_delivery', 'caadocapproval'),
            'required_docs' => [
                'schedule' => get_string('doc_schedule', 'caadocapproval'),
                'teaching_methods' => get_string('doc_teaching_methods', 'caadocapproval'),
                'student_engagement' => get_string('doc_student_engagement', 'caadocapproval')
            ]
        ],
        'quality_assurance' => [
            'title' => get_string('category_quality_assurance', 'caadocapproval'),
            'required_docs' => [
                'student_feedback' => get_string('doc_student_feedback', 'caadocapproval'),
                'peer_review' => get_string('doc_peer_review', 'caadocapproval'),
                'improvement_plan' => get_string('doc_improvement_plan', 'caadocapproval')
            ]
        ]
    ];
}

/**
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function caadocapproval_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the caadocapproval into the database
 *
 * @param stdClass $caadocapproval An object from the form in mod_form.php
 * @param mod_caadocapproval_mod_form $mform The form
 * @return int The id of the newly inserted caadocapproval record
 */
function caadocapproval_add_instance(stdClass $caadocapproval, mod_caadocapproval_mod_form $mform = null) {
    global $DB;
    
    $caadocapproval->timecreated = time();
    $caadocapproval->timemodified = time();
    
    // Process approvers data from form
    $categories = caadocapproval_get_categories();
    foreach ($categories as $cat_key => $category) {
        foreach ($category['required_docs'] as $doc_key => $doc_title) {
            for ($level = 1; $level <= 3; $level++) {
                $field = "approvers_{$cat_key}_{$doc_key}_l{$level}";
                if (isset($caadocapproval->$field) && is_array($caadocapproval->$field)) {
                    $caadocapproval->$field = implode(',', $caadocapproval->$field);
                }
            }
        }
    }
    
    $id = $DB->insert_record('caadocapproval', $caadocapproval);
    
    return $id;
}

/**
 * Updates an instance of the caadocapproval in the database
 *
 * @param stdClass $caadocapproval An object from the form in mod_form.php
 * @param mod_caadocapproval_mod_form $mform The form
 * @return boolean Success/Fail
 */
function caadocapproval_update_instance(stdClass $caadocapproval, mod_caadocapproval_mod_form $mform = null) {
    global $DB;
    
    $caadocapproval->timemodified = time();
    $caadocapproval->id = $caadocapproval->instance;
    
    // Process approvers data from form
    $categories = caadocapproval_get_categories();
    foreach ($categories as $cat_key => $category) {
        foreach ($category['required_docs'] as $doc_key => $doc_title) {
            for ($level = 1; $level <= 3; $level++) {
                $field = "approvers_{$cat_key}_{$doc_key}_l{$level}";
                if (isset($caadocapproval->$field) && is_array($caadocapproval->$field)) {
                    $caadocapproval->$field = implode(',', $caadocapproval->$field);
                } else if (!isset($caadocapproval->$field)) {
                    // If the field is not set (no approvers selected), set it to empty string
                    $caadocapproval->$field = '';
                }
            }
        }
    }
    
    return $DB->update_record('caadocapproval', $caadocapproval);
}

/**
 * Removes an instance of the caadocapproval from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function caadocapproval_delete_instance($id) {
    global $DB;
    
    if (!$caadocapproval = $DB->get_record('caadocapproval', array('id' => $id))) {
        return false;
    }
    
    // Delete all associated records
    $DB->delete_records('caadocapproval_documents', array('caadocapproval' => $id));
    
    // Delete document approvals and rejections
    $documents = $DB->get_records('caadocapproval_documents', array('caadocapproval' => $id), '', 'id');
    if (!empty($documents)) {
        list($sql, $params) = $DB->get_in_or_equal(array_keys($documents));
        $DB->delete_records_select('caadocapproval_approvals', "documentid $sql", $params);
        $DB->delete_records_select('caadocapproval_rejections', "documentid $sql", $params);
    }
    
    // Delete main instance
    $DB->delete_records('caadocapproval', array('id' => $id));
    
    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $caadocapproval The caadocapproval instance record
 * @return stdClass|null
 */
function caadocapproval_user_outline($course, $user, $mod, $caadocapproval) {
    global $DB;
    
    $return = new stdClass();
    
    // Count submitted documents
    $params = array(
        'caadocapproval' => $caadocapproval->id,
        'userid' => $user->id
    );
    $submittedcount = $DB->count_records('caadocapproval_documents', $params);
    
    // Count approved documents
    $params['status'] = CAADOCAPPROVAL_STATUS_APPROVED_FINAL;
    $approvedcount = $DB->count_records('caadocapproval_documents', $params);
    
    if ($submittedcount > 0) {
        $return->info = get_string('useroutline', 'caadocapproval', 
                        (object)array('submitted' => $submittedcount, 'approved' => $approvedcount));
        
        // Get last modified date
        $params = array(
            'caadocapproval' => $caadocapproval->id,
            'userid' => $user->id
        );
        $lastdoc = $DB->get_records('caadocapproval_documents', $params, 'timemodified DESC', 'timemodified', 0, 1);
        if (!empty($lastdoc)) {
            $lastdoc = reset($lastdoc);
            $return->time = $lastdoc->timemodified;
        }
        
        return $return;
    }
    
    return null;
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_caadocapproval_get_fontawesome_icon_map() {
    return [
        'mod_caadocapproval:document' => 'fa-file-alt',
        'mod_caadocapproval:approve' => 'fa-check-circle',
        'mod_caadocapproval:reject' => 'fa-times-circle',
        'mod_caadocapproval:pending' => 'fa-clock',
    ];
}

/**
 * Serves file from the document file areas
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just sends the file
 */
function caadocapproval_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if ($filearea != 'document') {
        return false;
    }

    // Check the relevant capability
    $canview = has_capability('mod/caadocapproval:view', $context);
    if (!$canview) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$filename) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_caadocapproval', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    // Send the file
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}