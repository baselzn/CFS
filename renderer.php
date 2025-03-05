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
 * Renderer for caadocapproval module
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/caadocapproval/locallib.php');

/**
 * Renderer class for caadocapproval module
 */
class mod_caadocapproval_renderer extends plugin_renderer_base {
    
    /**
     * Display categories and documents
     * 
     * @param \mod_caadocapproval\module $module The module instance
     * @return string The rendered HTML
     */
    public function display_categories(\mod_caadocapproval\module $module) {
        $output = '';
        
        $categories = $module->get_categories();
        $cm = $module->get_cm();
        
        // Output as tabs
        $currenttab = optional_param('category', '', PARAM_ALPHA);
        $tabs = array();
        foreach ($categories as $cat_key => $category) {
            $tabs[] = new tabobject(
                $cat_key,
                new moodle_url('/mod/caadocapproval/view.php', array('id' => $cm->id, 'category' => $cat_key)),
                $category['title']
            );
        }
        
        $output .= print_tabs(array($tabs), $currenttab, null, null, true);
        
        // Display dashboard
        $output .= $this->display_dashboard($module);
        
        return $output;
    }
    
    /**
     * Display the dashboard/overview
     * 
     * @param \mod_caadocapproval\module $module The module instance
     * @return string The rendered HTML
     */
    protected function display_dashboard(\mod_caadocapproval\module $module) {
        global $USER;
        
        $output = '';
        $output .= $this->output->heading(get_string('dashboard_title', 'caadocapproval'), 3);
        
        $categories = $module->get_categories();
        $cm = $module->get_cm();
        
        // Get all documents
        $all_documents = $module->get_all_documents();
        
        // Generate summary statistics
        $stats = array(
            'total' => 0,
            'drafts' => 0,
            'submitted' => 0,
            'approved_l1' => 0,
            'approved_l2' => 0,
            'approved_final' => 0,
            'rejected' => 0,
            'my_pending_approval' => 0
        );
        
        foreach ($all_documents as $cat_key => $documents) {
            foreach ($documents as $doc) {
                $stats['total']++;
                
                switch ($doc->status) {
                    case CAADOCAPPROVAL_STATUS_DRAFT:
                        $stats['drafts']++;
                        break;
                    case CAADOCAPPROVAL_STATUS_SUBMITTED:
                        $stats['submitted']++;
                        // Check if user is an approver
                        if ($module->can_approve(1) && caadocapproval_is_approver($doc->id, $USER->id, 1)) {
                            $stats['my_pending_approval']++;
                        }
                        break;
                    case CAADOCAPPROVAL_STATUS_APPROVED_L1:
                        $stats['approved_l1']++;
                        // Check if user is an approver
                        if ($module->can_approve(2) && caadocapproval_is_approver($doc->id, $USER->id, 2)) {
                            $stats['my_pending_approval']++;
                        }
                        break;
                    case CAADOCAPPROVAL_STATUS_APPROVED_L2:
                        $stats['approved_l2']++;
                        // Check if user is an approver
                        if ($module->can_approve(3) && caadocapproval_is_approver($doc->id, $USER->id, 3)) {
                            $stats['my_pending_approval']++;
                        }
                        break;
                    case CAADOCAPPROVAL_STATUS_APPROVED_FINAL:
                        $stats['approved_final']++;
                        break;
                    case CAADOCAPPROVAL_STATUS_REJECTED:
                        $stats['rejected']++;
                        break;
                }
            }
        }
        
        // Display stats
        $output .= html_writer::start_div('caadocapproval-dashboard');
        
        // Stats boxes in a grid
        $output .= html_writer::start_div('row');
        
        // Total documents
        $output .= html_writer::start_div('col-md-4');
        $output .= html_writer::start_div('card mb-4 bg-light');
        $output .= html_writer::start_div('card-body text-center');
        $output .= html_writer::tag('h4', $stats['total'], array('class' => 'card-title'));
        $output .= html_writer::tag('p', get_string('total_documents', 'caadocapproval'), array('class' => 'card-text'));
        $output .= html_writer::end_div(); // card-body
        $output .= html_writer::end_div(); // card
        $output .= html_writer::end_div(); // col
        
        // Approved documents
        $output .= html_writer::start_div('col-md-4');
        $output .= html_writer::start_div('card mb-4 bg-success text-white');
        $output .= html_writer::start_div('card-body text-center');
        $output .= html_writer::tag('h4', $stats['approved_final'], array('class' => 'card-title'));
        $output .= html_writer::tag('p', get_string('approved_documents', 'caadocapproval'), array('class' => 'card-text'));
        $output .= html_writer::end_div(); // card-body
        $output .= html_writer::end_div(); // card
        $output .= html_writer::end_div(); // col
        
        // Pending approvals
        if ($stats['my_pending_approval'] > 0) {
            $output .= html_writer::start_div('col-md-4');
            $output .= html_writer::start_div('card mb-4 bg-warning');
            $output .= html_writer::start_div('card-body text-center');
            $output .= html_writer::tag('h4', $stats['my_pending_approval'], array('class' => 'card-title'));
            $output .= html_writer::tag('p', get_string('my_pending_approvals', 'caadocapproval'), array('class' => 'card-text'));
            $output .= html_writer::end_div(); // card-body
            $output .= html_writer::end_div(); // card
            $output .= html_writer::end_div(); // col
        } else {
            // In progress documents
            $output .= html_writer::start_div('col-md-4');
            $output .= html_writer::start_div('card mb-4 bg-info text-white');
            $output .= html_writer::start_div('card-body text-center');
            $output .= html_writer::tag('h4', $stats['submitted'] + $stats['approved_l1'] + $stats['approved_l2'], array('class' => 'card-title'));
            $output .= html_writer::tag('p', get_string('in_progress_documents', 'caadocapproval'), array('class' => 'card-text'));
            $output .= html_writer::end_div(); // card-body
            $output .= html_writer::end_div(); // card
            $output .= html_writer::end_div(); // col
        }
        
        $output .= html_writer::end_div(); // row
        
        // Category summary
        $output .= html_writer::tag('h4', get_string('category_summary', 'caadocapproval'), array('class' => 'mt-4'));
        
        $output .= html_writer::start_tag('table', array('class' => 'table table-striped'));
        $output .= html_writer::start_tag('thead');
        $output .= html_writer::start_tag('tr');
        $output .= html_writer::tag('th', get_string('category', 'caadocapproval'));
        $output .= html_writer::tag('th', get_string('total_documents', 'caadocapproval'));
        $output .= html_writer::tag('th', get_string('approved_documents', 'caadocapproval'));
        $output .= html_writer::tag('th', get_string('pending_documents', 'caadocapproval'));
        $output .= html_writer::tag('th', get_string('actions', 'caadocapproval'));
        $output .= html_writer::end_tag('tr');
        $output .= html_writer::end_tag('thead');
        
        $output .= html_writer::start_tag('tbody');
        foreach ($categories as $cat_key => $category) {
            $cat_stats = array(
                'total' => 0,
                'approved' => 0,
                'pending' => 0,
            );
            
            if (isset($all_documents[$cat_key])) {
                foreach ($all_documents[$cat_key] as $doc) {
                    $cat_stats['total']++;
                    if ($doc->status == CAADOCAPPROVAL_STATUS_APPROVED_FINAL) {
                        $cat_stats['approved']++;
                    } else if ($doc->status != CAADOCAPPROVAL_STATUS_DRAFT && 
                              $doc->status != CAADOCAPPROVAL_STATUS_REJECTED) {
                        $cat_stats['pending']++;
                    }
                }
            }
            
            $output .= html_writer::start_tag('tr');
            $output .= html_writer::tag('td', $category['title']);
            $output .= html_writer::tag('td', $cat_stats['total']);
            $output .= html_writer::tag('td', $cat_stats['approved']);
            $output .= html_writer::tag('td', $cat_stats['pending']);
            
            // Actions - Ensure we're using the correct category key with underscores
            $view_url = new moodle_url('/mod/caadocapproval/view.php', 
                                    array('id' => $cm->id, 'category' => $cat_key));
            $output .= html_writer::start_tag('td');
            $output .= html_writer::link($view_url, get_string('view_category', 'caadocapproval'), 
                                       array('class' => 'btn btn-sm btn-primary'));
            $output .= html_writer::end_tag('td');
            
            $output .= html_writer::end_tag('tr');
        }
        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');
        
        $output .= html_writer::end_div(); // caadocapproval-dashboard
        
        return $output;
    }
    
    /**
     * Display documents for a category
     * 
     * @param \mod_caadocapproval\module $module The module instance
     * @param string $cat_key The category key
     * @param array $category The category data (optional)
     * @return string The rendered HTML
     */
    public function display_category_documents(\mod_caadocapproval\module $module, $cat_key, $category = null) {
        global $DB, $USER;
        
        $categories = $module->get_categories();
        if (!isset($categories[$cat_key])) {
            return $this->output->notification(get_string('invalidcategory', 'caadocapproval'), 'notifyerror');
        }
        
        if ($category === null) {
            $category = $categories[$cat_key];
        }
        
        $output = '';
        $output .= $this->output->heading($category['title'], 3);
        
        $cm = $module->get_cm();
        $documents = $module->get_documents_by_category($cat_key);
        
        // Group documents by type
        $docs_by_type = array();
        foreach ($category['required_docs'] as $doc_key => $doc_title) {
            $docs_by_type[$doc_key] = array();
        }
        
        foreach ($documents as $doc) {
            if (isset($docs_by_type[$doc->doc_type])) {
                $docs_by_type[$doc->doc_type][] = $doc;
            }
        }
        
        // Display document upload buttons if user can submit
        if ($module->can_submit()) {
            $output .= html_writer::start_div('caadocapproval-upload-buttons mb-4');
            
            foreach ($category['required_docs'] as $doc_key => $doc_title) {
                // Ensure we use the correct category key with underscores
                $upload_url = new moodle_url('/mod/caadocapproval/view.php', 
                                          array('id' => $cm->id, 'category' => $cat_key, 
                                                'action' => 'upload', 'doc_type' => $doc_key));
                
                $output .= html_writer::link($upload_url, 
                                         get_string('upload_document', 'caadocapproval', $doc_title), 
                                         array('class' => 'btn btn-primary mr-2 mb-2'));
            }
            
            $output .= html_writer::end_div();
        }
        
        // Display documents by type
        foreach ($category['required_docs'] as $doc_key => $doc_title) {
            $output .= html_writer::tag('h4', $doc_title, array('class' => 'mt-4'));
            
            if (empty($docs_by_type[$doc_key])) {
                $output .= html_writer::tag('p', get_string('no_documents', 'caadocapproval'), 
                                          array('class' => 'text-muted'));
                continue;
            }
            
            // Table of documents
            $output .= html_writer::start_tag('table', array('class' => 'table table-hover'));
            $output .= html_writer::start_tag('thead');
            $output .= html_writer::start_tag('tr');
            $output .= html_writer::tag('th', get_string('document', 'caadocapproval'));
            $output .= html_writer::tag('th', get_string('status', 'caadocapproval'));
            $output .= html_writer::tag('th', get_string('submitted_by', 'caadocapproval'));
            $output .= html_writer::tag('th', get_string('last_updated', 'caadocapproval'));
            $output .= html_writer::tag('th', get_string('actions', 'caadocapproval'));
            $output .= html_writer::end_tag('tr');
            $output .= html_writer::end_tag('thead');
            
            $output .= html_writer::start_tag('tbody');
            foreach ($docs_by_type[$doc_key] as $doc) {
                $output .= html_writer::start_tag('tr');
                
                // Document name
                $output .= html_writer::tag('td', $doc->filename);
                
                // Status
                $status_label = caadocapproval_get_status_label($doc->status);
                $status_class = caadocapproval_get_status_class($doc->status);
                $output .= html_writer::tag('td', html_writer::tag('span', $status_label, array('class' => $status_class)));
                
                // Submitted by
                $submitter = $DB->get_record('user', array('id' => $doc->userid), 'id, firstname, lastname');
                $output .= html_writer::tag('td', fullname($submitter));
                
                // Last updated
                $output .= html_writer::tag('td', userdate($doc->timemodified, get_string('strftimedatetime', 'langconfig')));
                
                // Actions
                $output .= html_writer::start_tag('td');
                
                // View document
                $view_url = new moodle_url('/mod/caadocapproval/view_document.php', 
                                        array('id' => $cm->id, 'docid' => $doc->id));
                $output .= html_writer::link($view_url, 
                                          html_writer::tag('i', '', array('class' => 'fa fa-eye')), 
                                          array('class' => 'btn btn-sm btn-info mr-1', 
                                                'title' => get_string('view_document', 'caadocapproval')));
                
                // Submit for approval (if draft and owner or can submit any)
                if ($doc->status == CAADOCAPPROVAL_STATUS_DRAFT && 
                    ($doc->userid == $USER->id || has_capability('mod/caadocapproval:submitany', $module->get_context()))) {
                    
                    // Ensure we use the correct category key with underscores
                    $submit_url = new moodle_url('/mod/caadocapproval/view.php', 
                                              array('id' => $cm->id, 'category' => $cat_key, 
                                                    'action' => 'submit', 'docid' => $doc->id));
                    
                    $output .= html_writer::link($submit_url, 
                                              html_writer::tag('i', '', array('class' => 'fa fa-paper-plane')), 
                                              array('class' => 'btn btn-sm btn-primary mr-1', 
                                                    'title' => get_string('submit_for_approval', 'caadocapproval')));
                }
                
                // Approve document (if appropriate level and user is approver)
                $next_level = caadocapproval_get_next_approval_level($doc);
                if ($next_level > 0 && 
                    has_capability('mod/caadocapproval:approve_level' . $next_level, $module->get_context()) &&
                    caadocapproval_is_approver($doc->id, $USER->id, $next_level)) {
                    
                    $approve_url = new moodle_url('/mod/caadocapproval/approve.php', 
                                               array('id' => $cm->id, 'docid' => $doc->id, 
                                                     'level' => $next_level));
                    
                    $output .= html_writer::link($approve_url, 
                                              html_writer::tag('i', '', array('class' => 'fa fa-check')), 
                                              array('class' => 'btn btn-sm btn-success mr-1', 
                                                    'title' => get_string('approve', 'caadocapproval')));
                    
                    $reject_url = new moodle_url('/mod/caadocapproval/reject.php', 
                                              array('id' => $cm->id, 'docid' => $doc->id));
                    
                    $output .= html_writer::link($reject_url, 
                                              html_writer::tag('i', '', array('class' => 'fa fa-times')), 
                                              array('class' => 'btn btn-sm btn-danger', 
                                                    'title' => get_string('reject', 'caadocapproval')));
                }
                
                $output .= html_writer::end_tag('td');
                $output .= html_writer::end_tag('tr');
            }
            $output .= html_writer::end_tag('tbody');
            $output .= html_writer::end_tag('table');
        }
        
        return $output;
    }
}