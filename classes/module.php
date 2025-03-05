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
 * Main module class for caadocapproval
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_caadocapproval;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__FILE__)).'/lib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

/**
 * Main module class
 */
class module {
    /** @var \stdClass The course module */
    private $cm;
    
    /** @var \stdClass The course */
    private $course;
    
    /** @var \stdClass The caadocapproval instance */
    private $instance;
    
    /** @var \context_module The module context */
    private $context;
    
    /**
     * Constructor
     * 
     * @param \stdClass $cm The course module
     * @param \stdClass $course The course
     * @param \stdClass $instance The caadocapproval instance
     */
    public function __construct($cm, $course, $instance) {
        $this->cm = $cm;
        $this->course = $course;
        $this->instance = $instance;
        $this->context = \context_module::instance($cm->id);
    }
    
    /**
     * Get document categories
     * 
     * @return array The categories array
     */
    public function get_categories() {
        return caadocapproval_get_categories();
    }
    
    /**
     * Get documents by category
     * 
     * @param string $category The category key
     * @return array The document records
     */
    public function get_documents_by_category($category) {
        global $DB;
        
        $params = array(
            'caadocapproval' => $this->instance->id,
            'category' => $category
        );
        
        return $DB->get_records('caadocapproval_documents', $params, 'doc_type ASC, timemodified DESC');
    }
    
    /**
     * Get all documents
     * 
     * @return array The document records, organized by category
     */
    public function get_all_documents() {
        $categories = $this->get_categories();
        $result = array();
        
        foreach ($categories as $cat_key => $category) {
            $result[$cat_key] = $this->get_documents_by_category($cat_key);
        }
        
        return $result;
    }
    
    /**
     * Get document by ID
     * 
     * @param int $docid The document ID
     * @return \stdClass|false The document record or false if not found
     */
    public function get_document($docid) {
        global $DB;
        
        $params = array(
            'id' => $docid,
            'caadocapproval' => $this->instance->id
        );
        
        return $DB->get_record('caadocapproval_documents', $params);
    }
    
    /**
     * Add a new document
     * 
     * @param string $category The category key
     * @param string $doc_type The document type key
     * @param string $filename The filename
     * @param string $filepath The file path in Moodle file system
     * @param int $userid The user ID who uploaded the document
     * @return int|false The new document ID or false on failure
     */
    public function add_document($category, $doc_type, $filename, $filepath, $userid) {
        global $DB;
        
        // Validate category and doc_type
        $categories = $this->get_categories();
        if (!isset($categories[$category]) || 
            !isset($categories[$category]['required_docs'][$doc_type])) {
            return false;
        }
        
        $document = new \stdClass();
        $document->course = $this->course->id;
        $document->caadocapproval = $this->instance->id;
        $document->category = $category;
        $document->doc_type = $doc_type;
        $document->filename = $filename;
        $document->filepath = $filepath;
        $document->userid = $userid;
        $document->status = CAADOCAPPROVAL_STATUS_DRAFT;
        $document->timecreated = time();
        $document->timemodified = time();
        
        return $DB->insert_record('caadocapproval_documents', $document);
    }
    
    /**
     * Submit document for approval
     * 
     * @param int $docid The document ID
     * @return bool Success status
     */
    public function submit_document($docid) {
        global $DB, $USER;
        
        $document = $this->get_document($docid);
        if (!$document) {
            return false;
        }
        
        // Check permissions
        if ($document->userid != $USER->id && 
            !has_capability('mod/caadocapproval:submitany', $this->context)) {
            return false;
        }
        
        // Only drafts can be submitted
        if ($document->status != CAADOCAPPROVAL_STATUS_DRAFT) {
            return false;
        }
        
        $document->status = CAADOCAPPROVAL_STATUS_SUBMITTED;
        $document->timemodified = time();
        
        $result = $DB->update_record('caadocapproval_documents', $document);
        
        if ($result) {
            // Trigger event
            $event = \mod_caadocapproval\event\document_submitted::create(array(
                'objectid' => $docid,
                'context' => $this->context,
                'userid' => $USER->id
            ));
            $event->add_record_snapshot('caadocapproval_documents', $document);
            $event->trigger();
            
            // Notify approvers
            caadocapproval_notify_approvers($document, 1);
        }
        
        return $result;
    }
    
    /**
     * Approve document
     * 
     * @param int $docid The document ID
     * @param int $level The approval level (1-3)
     * @return bool Success status
     */
    public function approve_document($docid, $level) {
        global $DB, $USER;
        
        $document = $this->get_document($docid);
        if (!$document) {
            return false;
        }
        
        // Check if user has approval rights for this level
        $capability = 'mod/caadocapproval:approve_level' . $level;
        if (!has_capability($capability, $this->context)) {
            return false;
        }
        
        // Check if document is in the correct state for this approval level
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
                return false;
        }
        
        if ($document->status != $expected_status) {
            return false;
        }
        
        // Update status based on approval level
        switch ($level) {
            case 1:
                $document->status = CAADOCAPPROVAL_STATUS_APPROVED_L1;
                break;
            case 2:
                $document->status = CAADOCAPPROVAL_STATUS_APPROVED_L2;
                break;
            case 3:
                $document->status = CAADOCAPPROVAL_STATUS_APPROVED_FINAL;
                break;
        }
        
        $document->timemodified = time();
        
        // Record the approval
        $approval = new \stdClass();
        $approval->documentid = $docid;
        $approval->userid = $USER->id;
        $approval->level = $level;
        $approval->timeapproved = time();
        $approval->comments = '';
        
        $DB->insert_record('caadocapproval_approvals', $approval);
        
        $result = $DB->update_record('caadocapproval_documents', $document);
        
        if ($result) {
            // Trigger approval event
            $event = \mod_caadocapproval\event\document_approved::create([
                'objectid' => $docid,
                'context' => $this->context,
                'userid' => $USER->id,
                'other' => ['level' => $level]
            ]);
            $event->add_record_snapshot('caadocapproval_documents', $document);
            $event->trigger();
            
            // If not final approval, notify next level approvers
            if ($document->status != CAADOCAPPROVAL_STATUS_APPROVED_FINAL) {
                caadocapproval_notify_approvers($document, $level + 1);
            } else {
                caadocapproval_notify_owner($document, 'approved');
            }
        }
        
        return $result;
    }
    
    /**
     * Reject document
     * 
     * @param int $docid The document ID
     * @param string $comments Rejection comments
     * @return bool Success status
     */
    public function reject_document($docid, $comments) {
        global $DB, $USER;
        
        $document = $this->get_document($docid);
        if (!$document) {
            return false;
        }
        
        // Check if user has any approval rights
        $hasapprovalcap = false;
        for ($i = 1; $i <= 3; $i++) {
            if (has_capability('mod/caadocapproval:approve_level' . $i, $this->context)) {
                $hasapprovalcap = true;
                break;
            }
        }
        
        if (!$hasapprovalcap) {
            return false;
        }
        
        // Document must be in a reviewable state
        if ($document->status == CAADOCAPPROVAL_STATUS_DRAFT || 
            $document->status == CAADOCAPPROVAL_STATUS_APPROVED_FINAL ||
            $document->status == CAADOCAPPROVAL_STATUS_REJECTED) {
            return false;
        }
        
        $document->status = CAADOCAPPROVAL_STATUS_REJECTED;
        $document->timemodified = time();
        
        // Record the rejection
        $rejection = new \stdClass();
        $rejection->documentid = $docid;
        $rejection->userid = $USER->id;
        $rejection->timerejected = time();
        $rejection->comments = $comments;
        
        $DB->insert_record('caadocapproval_rejections', $rejection);
        
        $result = $DB->update_record('caadocapproval_documents', $document);
        
        if ($result) {
            // Trigger rejection event
            $event = \mod_caadocapproval\event\document_rejected::create([
                'objectid' => $docid,
                'context' => $this->context,
                'userid' => $USER->id
            ]);
            $event->add_record_snapshot('caadocapproval_documents', $document);
            $event->trigger();
            
            // Notify document owner
            caadocapproval_notify_owner($document, 'rejected', $comments);
        }
        
        return $result;
    }
    
    /**
     * Get approvers for a document category and type
     * 
     * @param string $category The category key
     * @param string $doc_type The document type key
     * @param int $level The approval level
     * @return array Array of user records
     */
    public function get_approvers($category, $doc_type, $level) {
        global $DB;
        
        // Get configured approvers from module instance settings
        $field = "approvers_{$category}_{$doc_type}_l{$level}";
        
        if (empty($this->instance->$field)) {
            return array();
        }
        
        $approver_ids = explode(',', $this->instance->$field);
        if (empty($approver_ids)) {
            return array();
        }
        
        // Get user records
        list($in_sql, $params) = $DB->get_in_or_equal($approver_ids);
        return $DB->get_records_select('user', "id $in_sql", $params);
    }
    
    /**
     * Check if a user can submit documents
     * 
     * @return bool True if the user can submit documents
     */
    public function can_submit() {
        return has_capability('mod/caadocapproval:submit', $this->context);
    }
    
    /**
     * Check if a user can approve at a specific level
     * 
     * @param int $level The approval level
     * @return bool True if the user can approve at the given level
     */
    public function can_approve($level) {
        return has_capability('mod/caadocapproval:approve_level' . $level, $this->context);
    }
    
    /**
     * Check if a user can manage the activity
     * 
     * @return bool True if the user can manage the activity
     */
    public function can_manage() {
        return has_capability('mod/caadocapproval:manage', $this->context);
    }
    
    /**
     * Get the course module
     * 
     * @return stdClass The course module
     */
    public function get_cm() {
        return $this->cm;
    }
    
    /**
     * Get the course
     * 
     * @return stdClass The course
     */
    public function get_course() {
        return $this->course;
    }
    
    /**
     * Get the caadocapproval instance
     * 
     * @return stdClass The caadocapproval instance
     */
    public function get_instance() {
        return $this->instance;
    }
    
    /**
     * Get the module context
     * 
     * @return context_module The module context
     */
    public function get_context() {
        return $this->context;
    }
}