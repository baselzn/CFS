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
 * Private caadocapproval module utility functions
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/lib.php');

/**
 * Gets the document status label
 * 
 * @param int $status The status code
 * @return string The localized status label
 */
function caadocapproval_get_status_label($status) {
    switch ($status) {
        case CAADOCAPPROVAL_STATUS_DRAFT:
            return get_string('status_draft', 'caadocapproval');
        case CAADOCAPPROVAL_STATUS_SUBMITTED:
            return get_string('status_submitted', 'caadocapproval');
        case CAADOCAPPROVAL_STATUS_APPROVED_L1:
            return get_string('status_approved_l1', 'caadocapproval');
        case CAADOCAPPROVAL_STATUS_APPROVED_L2:
            return get_string('status_approved_l2', 'caadocapproval');
        case CAADOCAPPROVAL_STATUS_APPROVED_FINAL:
            return get_string('status_approved_final', 'caadocapproval');
        case CAADOCAPPROVAL_STATUS_REJECTED:
            return get_string('status_rejected', 'caadocapproval');
        default:
            return get_string('status_unknown', 'caadocapproval');
    }
}

/**
 * Gets status CSS class for styling
 * 
 * @param int $status The status code
 * @return string CSS class name
 */
function caadocapproval_get_status_class($status) {
    switch ($status) {
        case CAADOCAPPROVAL_STATUS_DRAFT:
            return 'badge badge-secondary';
        case CAADOCAPPROVAL_STATUS_SUBMITTED:
            return 'badge badge-info';
        case CAADOCAPPROVAL_STATUS_APPROVED_L1:
            return 'badge badge-warning';
        case CAADOCAPPROVAL_STATUS_APPROVED_L2:
            return 'badge badge-warning';
        case CAADOCAPPROVAL_STATUS_APPROVED_FINAL:
            return 'badge badge-success';
        case CAADOCAPPROVAL_STATUS_REJECTED:
            return 'badge badge-danger';
        default:
            return 'badge badge-light';
    }
}

/**
 * Gets document data
 * 
 * @param int $docid The document ID
 * @return object|false The document data or false if not found
 */
function caadocapproval_get_document($docid) {
    global $DB;
    return $DB->get_record('caadocapproval_documents', array('id' => $docid));
}

/**
 * Check if a user is an approver for the document
 * 
 * @param int $docid The document ID
 * @param int $userid The user ID
 * @param int $level The approval level
 * @return bool True if the user is an approver
 */
function caadocapproval_is_approver($docid, $userid, $level) {
    global $DB;
    
    $document = caadocapproval_get_document($docid);
    if (!$document) {
        return false;
    }
    
    $caadocapproval = $DB->get_record('caadocapproval', array('id' => $document->caadocapproval));
    if (!$caadocapproval) {
        return false;
    }
    
    $field = "approvers_{$document->category}_{$document->doc_type}_l{$level}";
    if (empty($caadocapproval->$field)) {
        return false;
    }
    
    $approvers = explode(',', $caadocapproval->$field);
    return in_array($userid, $approvers);
}

/**
 * Gets the next approval level for a document
 * 
 * @param object $document The document record
 * @return int The next approval level (1-3) or 0 if already fully approved/rejected
 */
function caadocapproval_get_next_approval_level($document) {
    switch ($document->status) {
        case CAADOCAPPROVAL_STATUS_SUBMITTED:
            return 1;
        case CAADOCAPPROVAL_STATUS_APPROVED_L1:
            return 2;
        case CAADOCAPPROVAL_STATUS_APPROVED_L2:
            return 3;
        default:
            return 0; // Already approved or rejected
    }
}

/**
 * Get document approvals
 * 
 * @param int $docid The document ID
 * @return array Array of approval records
 */
function caadocapproval_get_approvals($docid) {
    global $DB;
    return $DB->get_records('caadocapproval_approvals', array('documentid' => $docid), 'timeapproved ASC');
}

/**
 * Get document rejections
 * 
 * @param int $docid The document ID
 * @return array Array of rejection records
 */
function caadocapproval_get_rejections($docid) {
    global $DB;
    return $DB->get_records('caadocapproval_rejections', array('documentid' => $docid), 'timerejected DESC');
}

/**
 * Send notifications to approvers
 * 
 * @param object $document The document record
 * @param int $level Approval level
 */
function caadocapproval_notify_approvers($document, $level) {
    global $DB, $CFG;
    
    require_once($CFG->dirroot.'/lib/moodlelib.php');
    
    $caadocapproval = $DB->get_record('caadocapproval', array('id' => $document->caadocapproval));
    if (!$caadocapproval) {
        return;
    }
    
    $course = $DB->get_record('course', array('id' => $document->course));
    if (!$course) {
        return;
    }
    
    $cm = get_coursemodule_from_instance('caadocapproval', $caadocapproval->id, $course->id);
    if (!$cm) {
        return;
    }
    
    $submitter = $DB->get_record('user', array('id' => $document->userid));
    if (!$submitter) {
        return;
    }
    
    $categories = caadocapproval_get_categories();
    $categoryTitle = $categories[$document->category]['title'];
    $docType = $categories[$document->category]['required_docs'][$document->doc_type];
    
    $field = "approvers_{$document->category}_{$document->doc_type}_l{$level}";
    if (empty($caadocapproval->$field)) {
        return;
    }
    
    $approverids = explode(',', $caadocapproval->$field);
    
    // URL to the document
    $url = new moodle_url('/mod/caadocapproval/view.php', array(
        'id' => $cm->id,
        'category' => $document->category,
        'docid' => $document->id
    ));
    
    foreach ($approverids as $approverid) {
        $approver = $DB->get_record('user', array('id' => $approverid));
        if (!$approver) {
            continue;
        }
        
        $subject = get_string('notification_approval_subject', 'caadocapproval', array(
            'coursename' => $course->shortname,
            'doctype' => $docType
        ));
        
        $message = get_string('notification_approval_message', 'caadocapproval', array(
            'approver' => fullname($approver),
            'coursename' => $course->fullname,
            'category' => $categoryTitle,
            'doctype' => $docType,
            'submitter' => fullname($submitter),
            'url' => $url->out(false)
        ));
        
        $messagehtml = text_to_html($message, false, false, true);
        
        email_to_user($approver, core_user::get_support_user(), $subject, $message, $messagehtml);
    }
}

/**
 * Send notification to document owner
 * 
 * @param object $document The document record
 * @param string $status 'approved' or 'rejected'
 * @param string $comments Optional comments
 */
function caadocapproval_notify_owner($document, $status, $comments = '') {
    global $DB, $CFG, $USER;
    
    require_once($CFG->dirroot.'/lib/moodlelib.php');
    
    $owner = $DB->get_record('user', array('id' => $document->userid));
    if (!$owner) {
        return;
    }
    
    $course = $DB->get_record('course', array('id' => $document->course));
    if (!$course) {
        return;
    }
    
    $cm = get_coursemodule_from_instance('caadocapproval', $document->caadocapproval, $course->id);
    if (!$cm) {
        return;
    }
    
    $categories = caadocapproval_get_categories();
    $categoryTitle = $categories[$document->category]['title'];
    $docType = $categories[$document->category]['required_docs'][$document->doc_type];
    
    // URL to the document
    $url = new moodle_url('/mod/caadocapproval/view.php', array(
        'id' => $cm->id,
        'category' => $document->category,
        'docid' => $document->id
    ));
    
    $subject = get_string('notification_'.$status.'_subject', 'caadocapproval', array(
        'coursename' => $course->shortname,
        'doctype' => $docType
    ));
    
    $message = get_string('notification_'.$status.'_message', 'caadocapproval', array(
        'owner' => fullname($owner),
        'coursename' => $course->fullname,
        'category' => $categoryTitle,
        'doctype' => $docType,
        'reviewer' => fullname($USER),
        'comments' => $comments,
        'url' => $url->out(false)
    ));
    
    $messagehtml = text_to_html($message, false, false, true);
    
    email_to_user($owner, core_user::get_support_user(), $subject, $message, $messagehtml);
}
