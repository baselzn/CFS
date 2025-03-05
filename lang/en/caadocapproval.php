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
 * English strings for caadocapproval
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General strings
$string['modulename'] = 'CAA Document Approval';
$string['modulenameplural'] = 'CAA Document Approvals';
$string['modulename_help'] = 'The CAA Document Approval module enables the management and approval of course documents required by the Commission for Academic Accreditation (CAA).';
$string['caadocapprovalname'] = 'Name';
$string['caadocapprovalname_help'] = 'The name of the CAA Document Approval activity instance.';
$string['caadocapproval'] = 'CAA Document Approval';
$string['pluginadministration'] = 'CAA Document Approval administration';
$string['pluginname'] = 'CAA Document Approval';
$string['invalidcategory'] = 'Invalid category selected';
$string['nocaadocapprovals'] = 'There are no CAA Document Approval instances in this course';
$string['choose'] = 'Choose users...';

// Category strings
$string['category_course_specs'] = 'Course Specifications';
$string['category_teaching_materials'] = 'Teaching Materials';
$string['category_assessment_tools'] = 'Assessment Tools';
$string['category_course_delivery'] = 'Course Delivery';
$string['category_quality_assurance'] = 'Quality Assurance';
$string['category'] = 'Category';
$string['category_summary'] = 'Category Summary';

// Document type strings
$string['doc_syllabus'] = 'Syllabus';
$string['doc_learning_outcomes'] = 'Learning Outcomes';
$string['doc_assessment_scheme'] = 'Assessment Scheme';
$string['doc_textbooks'] = 'Textbooks';
$string['doc_lecture_notes'] = 'Lecture Notes';
$string['doc_multimedia'] = 'Multimedia Resources';
$string['doc_exams'] = 'Exams';
$string['doc_assignments'] = 'Assignments';
$string['doc_rubrics'] = 'Rubrics';
$string['doc_schedule'] = 'Course Schedule';
$string['doc_teaching_methods'] = 'Teaching Methods';
$string['doc_student_engagement'] = 'Student Engagement';
$string['doc_student_feedback'] = 'Student Feedback';
$string['doc_peer_review'] = 'Peer Review';
$string['doc_improvement_plan'] = 'Improvement Plan';

// Form strings
$string['select_approvers'] = 'Select approvers for this document type';
$string['level1_approvers'] = 'Level 1 Approvers';
$string['level1_approvers_help'] = 'Users who can approve this document at the first level, typically department coordinators or course leaders.';
$string['level2_approvers'] = 'Level 2 Approvers';
$string['level2_approvers_help'] = 'Users who can approve this document at the second level, typically department heads or program directors.';
$string['level3_approvers'] = 'Level 3 Approvers';
$string['level3_approvers_help'] = 'Users who can provide final approval for this document, typically deans or quality assurance officers.';

// Status strings
$string['status'] = 'Status';
$string['status_draft'] = 'Draft';
$string['status_submitted'] = 'Submitted for Approval';
$string['status_approved_l1'] = 'Level 1 Approved';
$string['status_approved_l2'] = 'Level 2 Approved';
$string['status_approved_final'] = 'Approved';
$string['status_rejected'] = 'Rejected';
$string['status_unknown'] = 'Unknown Status';

// Dashboard strings
$string['dashboard_title'] = 'CAA Document Approval Dashboard';
$string['total_documents'] = 'Total Documents';
$string['approved_documents'] = 'Approved Documents';
$string['pending_documents'] = 'Pending Documents';
$string['in_progress_documents'] = 'In Progress Documents';
$string['my_pending_approvals'] = 'Pending My Approval';

// Document related strings
$string['document'] = 'Document';
$string['documents'] = 'Documents';
$string['no_documents'] = 'No documents have been uploaded yet.';
$string['submitted_by'] = 'Submitted By';
$string['last_updated'] = 'Last Updated';
$string['document_type'] = 'Document Type';
$string['filename'] = 'Filename';

// Action strings
$string['upload_document'] = 'Upload {$a}';
$string['upload'] = 'Upload';
$string['view_document'] = 'View Document';
$string['view_documents'] = 'View Documents';
$string['view_category'] = 'View Documents';
$string['submit_for_approval'] = 'Submit for Approval';
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['actions'] = 'Actions';

// Upload related strings
$string['invalidcategorydoctype'] = 'Invalid category or document type';
$string['filenotfound'] = 'File not found';
$string['document_uploaded'] = 'Document uploaded successfully';
$string['document_upload_failed'] = 'Failed to upload document';
$string['file'] = 'File';

// Notification strings
$string['document_submitted'] = 'Document successfully submitted for approval.';
$string['document_submit_failed'] = 'Failed to submit document for approval.';
$string['document_approved'] = 'Document approved successfully.';
$string['document_approve_failed'] = 'Failed to approve document.';
$string['document_rejected'] = 'Document rejected.';
$string['document_reject_failed'] = 'Failed to reject document.';
$string['nofileselected'] = 'No file was selected for upload.';

// Document view strings
$string['document_details'] = 'Document Details';
$string['document_preview'] = 'Document Preview';
$string['file_preview_unavailable'] = 'Preview not available for this file type.';
$string['download_file'] = 'Download File';
$string['file_not_found'] = 'File not found.';

// Event strings
$string['event_document_submitted'] = 'Document submitted';
$string['event_document_approved'] = 'Document approved';
$string['event_document_rejected'] = 'Document rejected';

// Document status and approval strings
$string['level1_approver'] = 'Level 1 Approver';
$string['level2_approver'] = 'Level 2 Approver';
$string['level3_approver'] = 'Level 3 Approver';
$string['approval_level'] = 'Approval Level';
$string['approved_by'] = 'Approved By';
$string['approval_date'] = 'Approval Date';
$string['comments'] = 'Comments';
$string['approval_comments'] = 'Approval Comments';
$string['approval_options'] = 'Approval Options';
$string['approval_history'] = 'Approval History';
$string['reject_document'] = 'Reject Document';
$string['rejection_reason'] = 'Rejection Reason';
$string['rejection_comments'] = 'Rejection Comments';
$string['rejection_history'] = 'Rejection History';
$string['rejected_by'] = 'Rejected By';
$string['rejection_date'] = 'Rejection Date';
$string['no_approvals_yet'] = 'No approvals yet.';
$string['back_to_category'] = 'Back to Category';
$string['approve_document'] = 'Approve Document';
$string['submission_date'] = 'Submission Date';
$string['invalidapprovalrequest'] = 'Invalid approval request.';
$string['invalidapprovalstate'] = 'Document is not in an appropriate state for this approval level.';
$string['invaliddocument'] = 'Invalid document ID.';
$string['notanapprover'] = 'You are not an approver for this document.';

// Notification strings
$string['notification_approval_subject'] = '[{$a->coursename}] Document requires your approval: {$a->doctype}';
$string['notification_approval_message'] = 'Dear {$a->approver},

A document in the course "{$a->coursename}" requires your approval.

Category: {$a->category}
Document: {$a->doctype}
Submitted by: {$a->submitter}

Please visit the following link to review and approve/reject the document:
{$a->url}

This is an automated message from the CAA Document Approval system.';

$string['notification_approved_subject'] = '[{$a->coursename}] Document approved: {$a->doctype}';
$string['notification_approved_message'] = 'Dear {$a->owner},

Your document in the course "{$a->coursename}" has been approved.

Category: {$a->category}
Document: {$a->doctype}
Approved by: {$a->reviewer}

You can view the document status here:
{$a->url}

This is an automated message from the CAA Document Approval system.';

$string['notification_rejected_subject'] = '[{$a->coursename}] Document rejected: {$a->doctype}';
$string['notification_rejected_message'] = 'Dear {$a->owner},

Your document in the course "{$a->coursename}" has been rejected.

Category: {$a->category}
Document: {$a->doctype}
Rejected by: {$a->reviewer}
Comments: {$a->comments}

You can view the document status and resubmit here:
{$a->url}

This is an automated message from the CAA Document Approval system.';

// Capability strings
$string['caadocapproval:addinstance'] = 'Add a new CAA Document Approval activity';
$string['caadocapproval:view'] = 'View CAA Document Approval activity';
$string['caadocapproval:submit'] = 'Submit documents for approval';
$string['caadocapproval:submitany'] = 'Submit any documents for approval';
$string['caadocapproval:approve_level1'] = 'Approve documents at level 1';
$string['caadocapproval:approve_level2'] = 'Approve documents at level 2';
$string['caadocapproval:approve_level3'] = 'Provide final approval for documents';
$string['caadocapproval:manage'] = 'Manage CAA Document Approval activity';

// User outline strings
$string['useroutline'] = 'Submitted {$a->submitted} documents, {$a->approved} approved';