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
 * The document approved event class
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_caadocapproval\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The document approved event class
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int level: the approval level
 * }
 *
 * @package    mod_caadocapproval
 * @copyright  2025, Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document_approved extends \core\event\base {
    
    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'caadocapproval_documents';
    }
    
    /**
     * Return localised event name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_document_approved', 'caadocapproval');
    }
    
    /**
     * Returns description of what happened
     *
     * @return string
     */
    public function get_description() {
        $level = isset($this->other['level']) ? $this->other['level'] : '?';
        return "The user with id '$this->userid' approved the document with id '$this->objectid' at level '$level'";
    }
    
    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/caadocapproval/view_document.php', array(
            'id' => $this->contextinstanceid,
            'docid' => $this->objectid
        ));
    }
    
    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        
        if (!isset($this->other['level'])) {
            throw new \coding_exception('The \'level\' value must be set in other.');
        }
        
        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
