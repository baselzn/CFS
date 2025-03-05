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
 * The main caadocapproval configuration form
 *
 * @package     mod_caadocapproval
 * @copyright   2025, Your Name
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_caadocapproval_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB, $COURSE;
        
        $mform = $this->_form;

        // General settings
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('name', 'caadocapproval'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        $this->standard_intro_elements();
        
        // CAA Categories and Approvers
        $categories = caadocapproval_get_categories();
        
        // Get all potential approvers from course - only enrolled users with specific roles
        $context = context_course::instance($COURSE->id);
        
        // Get all enrolled users with a role that can view the course
        $enrolled_users = get_enrolled_users($context, 'moodle/course:view', 0, 'u.*', 'u.lastname, u.firstname');
        
        // Create options array for autocomplete with course users only
        $course_users = array();
        foreach ($enrolled_users as $user) {
            $course_users[$user->id] = fullname($user);
        }
        
        foreach ($categories as $cat_key => $category) {
            $mform->addElement('header', 'header_'.$cat_key, $category['title']);
            
            foreach ($category['required_docs'] as $doc_key => $doc_title) {
                $mform->addElement('static', 'static_'.$doc_key, $doc_title, 
                                 get_string('select_approvers', 'caadocapproval'));
                
                // User autocomplete options - shared for all selectors
                $options = [
                    'multiple' => true,
                    'noselectionstring' => get_string('choose', 'caadocapproval'),
                    'casesensitive' => false
                ];
                
                // Level 1 approvers - use select element with course users
                $mform->addElement('autocomplete', "approvers_{$cat_key}_{$doc_key}_l1", 
                                 get_string('level1_approvers', 'caadocapproval'), 
                                 $course_users, $options);
                $mform->addHelpButton("approvers_{$cat_key}_{$doc_key}_l1", 'level1_approvers', 'caadocapproval');
                
                // Level 2 approvers - use select element with course users
                $mform->addElement('autocomplete', "approvers_{$cat_key}_{$doc_key}_l2", 
                                 get_string('level2_approvers', 'caadocapproval'), 
                                 $course_users, $options);
                $mform->addHelpButton("approvers_{$cat_key}_{$doc_key}_l2", 'level2_approvers', 'caadocapproval');
                
                // Level 3 approvers - use select element with course users
                $mform->addElement('autocomplete', "approvers_{$cat_key}_{$doc_key}_l3", 
                                 get_string('level3_approvers', 'caadocapproval'), 
                                 $course_users, $options);
                $mform->addHelpButton("approvers_{$cat_key}_{$doc_key}_l3", 'level3_approvers', 'caadocapproval');
            }
        }
        
        // Add standard elements
        $this->standard_coursemodule_elements();
        
        // Add standard buttons
        $this->add_action_buttons();
    }

    /**
     * Process data before displaying the form
     *
     * @param array $default_values the default values
     */
    function data_preprocessing(&$default_values) {
        if (isset($this->current->instance)) {
            $categories = caadocapproval_get_categories();
            foreach ($categories as $cat_key => $category) {
                foreach ($category['required_docs'] as $doc_key => $doc_title) {
                    for ($level = 1; $level <= 3; $level++) {
                        $field = "approvers_{$cat_key}_{$doc_key}_l{$level}";
                        if (!empty($default_values[$field])) {
                            // Convert comma-separated IDs to array for autocomplete field
                            $default_values[$field] = explode(',', $default_values[$field]);
                        }
                    }
                }
            }
        }
    }
}