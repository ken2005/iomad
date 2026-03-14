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
 * External API for local_crewerp_api plugin - DEBUG MODE.
 *
 * @package    local_crewerp_api
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/url/lib.php');
require_once($CFG->dirroot . '/mod/label/lib.php');
require_once($CFG->dirroot . '/mod/scorm/lib.php');
require_once($CFG->dirroot . '/mod/scorm/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * External API class for CrewERP Bridge - DEBUG MODE.
 * 
 * This version removes try/catch blocks to allow native Moodle exceptions
 * to bubble up for debugging purposes.
 */
class local_crewerp_api_external extends external_api {

    /**
     * Helper function to add module to course using Standard Moodle API.
     * 
     * DEBUG MODE: "Tracer Version" - Each step is wrapped in try/catch to pinpoint exact failure location.
     * 
     * This version wraps EVERY single step (1 to 10) in a try/catch (\Throwable $e) block.
     * If an error occurs, it throws a specific exception saying "CRASH AT STEP X",
     * allowing us to pinpoint the exact line causing the issue.
     *
     * @param stdClass $module Module data object
     * @param string $modulename Name of the module (e.g., 'url', 'label')
     * @return int Course module ID
     * @throws moodle_exception Native Moodle exceptions will bubble up with step information
     */
    private static function add_to_course($module, $modulename) {
        global $DB, $CFG;

        // STEP 1: LOAD COURSE
        try {
            $course = $DB->get_record('course', array('id' => $module->course), '*', MUST_EXIST);
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 1 (Get Course): " . $e->getMessage());
        }

        // STEP 2: SECTIONS
        try {
            $target_section = isset($module->section) ? (int)$module->section : 0;
            if ($target_section > 0) {
                course_create_sections_if_missing($course, $target_section);
            }
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 2 (Sections): " . $e->getMessage());
        }

        // STEP 3: PREPARE DATA
        try {
            $clean_modulename = str_replace('mod_', '', $modulename);
            $module->modulename = $clean_modulename;
            $module_type_id = $DB->get_field('modules', 'id', array('name' => $clean_modulename), MUST_EXIST);
            $module->module = $module_type_id;
            $module->section = $target_section;
            $module->visible = 1;
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 3 (Data Prep): " . $e->getMessage());
        }

        // STEP 4: LOAD LIB
        try {
            $libfile = $CFG->dirroot . "/mod/" . $clean_modulename . "/lib.php";
            if (file_exists($libfile)) {
                require_once($libfile);
            } else {
                throw new \Exception("Library not found: $libfile");
            }
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 4 (Load Lib): " . $e->getMessage());
        }

        // STEP 5: ADD INSTANCE (HYBRID BYPASS)
        $instance_id = 0;
        try {
            // FIX LABEL NAME
            if ($clean_modulename === 'label') {
                if (!isset($module->name) || empty($module->name)) {
                    $module->name = substr(strip_tags($module->intro), 0, 30) ?: 'Label';
                }
            }
            $module->name = (string)$module->name;
            
            // --- BYPASS LOGIC START ---
            if ($clean_modulename === 'quiz') {
                // FORCE MANUAL INSERT FOR QUIZ to avoid hook crashes
                $instance_id = $DB->insert_record('quiz', $module);
            } else {
                // STANDARD WAY for Label, URL, SCORM
                $add_instance_function = $clean_modulename . '_add_instance';
                if (function_exists($add_instance_function)) {
                    $instance_id = call_user_func($add_instance_function, $module, null);
                } else {
                    throw new \Exception("Function $add_instance_function not found");
                }
            }
            // --- BYPASS LOGIC END ---

        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 5 (Add Instance): " . $e->getMessage());
        }

        // STEP 6: SANITIZE
        $cm_record = new \stdClass();
        try {
            $cm_record->course = (int)$course->id;
            $cm_record->module = (int)$module_type_id;
            $cm_record->instance = (int)$instance_id;
            $cm_record->section = (int)$target_section;
            $cm_record->visible = 1;
            $cm_record->modulename = (string)$clean_modulename;
            if (isset($module->name)) {
                $cm_record->name = (string)$module->name;
            }
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 6 (Sanitize): " . $e->getMessage());
        }

        // STEP 7: ADD COURSE MODULE
        $cmid = 0;
        try {
            $cmid = add_course_module($cm_record);
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 7 (Add CM): " . $e->getMessage());
        }

        // STEP 8: CLEAN FETCH (Kept for safety, though less critical with bypass)
        try {
            $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 8 (Fetch CM): " . $e->getMessage());
        }

        // STEP 9: ADD TO SECTION (MANUAL DB BYPASS)
        try {
            // Get the actual section record from DB
            $section_record = $DB->get_record('course_sections', array('course' => $course->id, 'section' => $target_section), '*', MUST_EXIST);
            
            // 1. Update the sequence (comma separated list of CM IDs)
            $sequence = trim($section_record->sequence);
            if (empty($sequence)) {
                $sequence = (string)$cmid;
            } else {
                $sequence .= "," . $cmid;
            }
            $DB->set_field('course_sections', 'sequence', $sequence, array('id' => $section_record->id));
            
            // 2. Link the CM to the correct section ID (not just section number)
            $DB->set_field('course_modules', 'section', $section_record->id, array('id' => $cmid));
            
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 9 (Manual Section Add): " . $e->getMessage());
        }

        // STEP 10: REBUILD CACHE (Crucial after manual DB edit)
        try {
            rebuild_course_cache($course->id, true);
        } catch (\Throwable $e) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP 10 (Cache): " . $e->getMessage());
        }

        return $cmid;
    }

    /**
     * Returns description of method parameters for create_label.
     *
     * @return external_function_parameters
     */
    public static function create_label_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'sectionnum' => new external_value(PARAM_INT, 'Section ID'),
                'content' => new external_value(PARAM_RAW, 'HTML Content'),
            )
        );
    }

    /**
     * Create a new Label module - DEBUG MODE.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section number
     * @param string $content HTML content
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function create_label($courseid, $sectionnum, $content) {
        global $DB;

        // Validate parameters
        $params = self::validate_parameters(self::create_label_parameters(), compact('courseid', 'sectionnum', 'content'));
        
        // Validate context
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('mod/label:addinstance', $context);
        
        $module = new stdClass();
        $module->course = $params['courseid'];
        $module->section = $params['sectionnum'];
        $module->intro = $params['content'];
        $module->introformat = FORMAT_HTML;
        
        // RAW CALL - No Try/Catch - Let native exceptions bubble up
        $cmid = self::add_to_course($module, 'label');
        
        return array('id' => $cmid, 'status' => 'success');
    }

    /**
     * Returns description of method result value for create_label.
     *
     * @return external_single_structure
     */
    public static function create_label_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Course module ID'),
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    // =========================================================================
    // CREATE URL
    // =========================================================================
    /**
     * Returns description of method parameters for create_url.
     *
     * @return external_function_parameters
     */
    public static function create_url_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'sectionnum' => new external_value(PARAM_INT, 'Section ID'),
                'name' => new external_value(PARAM_TEXT, 'Link Name'),
                'externalurl' => new external_value(PARAM_URL, 'External URL'),
                'intro' => new external_value(PARAM_RAW, 'Description', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Create a new URL module.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section ID
     * @param string $name Link Name
     * @param string $externalurl External URL
     * @param string $intro Description
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function create_url($courseid, $sectionnum, $name, $externalurl, $intro = '') {
        $params = self::validate_parameters(self::create_url_parameters(), compact('courseid', 'sectionnum', 'name', 'externalurl', 'intro'));

        $module = new \stdClass();
        $module->course = $params['courseid'];
        $module->section = $params['sectionnum'];
        $module->name = $params['name'];
        $module->externalurl = $params['externalurl'];
        $module->intro = $params['intro'];
        $module->introformat = 1;

        // Use our robust helper
        $cmid = self::add_to_course($module, 'url');

        return array('id' => $cmid, 'status' => 'success');
    }

    /**
     * Returns description of method result value for create_url.
     *
     * @return external_single_structure
     */
    public static function create_url_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Module ID'),
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    /**
     * Returns description of method parameters for update_url.
     *
     * @return external_function_parameters
     */
    public static function update_url_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'name' => new external_value(PARAM_TEXT, 'Module name', VALUE_DEFAULT, null),
                'url' => new external_value(PARAM_URL, 'URL to link to', VALUE_DEFAULT, null),
                'intro' => new external_value(PARAM_RAW, 'Introduction text', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Update an existing URL module - DEBUG MODE.
     *
     * @param int $cmid Course module ID
     * @param string|null $name Module name
     * @param string|null $url URL to link to
     * @param string|null $intro Introduction text
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function update_url($cmid, $name = null, $url = null, $intro = null) {
        // DEBUG MODE - Stub
        return array('status' => 'debug_mode');
    }

    /**
     * Returns description of method result value for update_url.
     *
     * @return external_single_structure
     */
    public static function update_url_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    /**
     * Returns description of method parameters for update_label.
     *
     * @return external_function_parameters
     */
    public static function update_label_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'content' => new external_value(PARAM_RAW, 'HTML content for the label'),
            )
        );
    }

    /**
     * Update an existing Label module - DEBUG MODE.
     *
     * @param int $cmid Course module ID
     * @param string $content HTML content
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function update_label($cmid, $content) {
        // DEBUG MODE - Stub
        return array('status' => 'debug_mode');
    }

    /**
     * Returns description of method result value for update_label.
     *
     * @return external_single_structure
     */
    public static function update_label_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    /**
     * Returns description of method parameters for create_scorm.
     *
     * @return external_function_parameters
     */
    public static function create_scorm_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'sectionid' => new external_value(PARAM_INT, 'Section number'),
                'name' => new external_value(PARAM_TEXT, 'Module name'),
                'draftitemid' => new external_value(PARAM_INT, 'Draft file area item ID'),
            )
        );
    }

    /**
     * Create a new SCORM module - DEBUG MODE.
     *
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function create_scorm() {
        // DEBUG MODE - Stub
        return array('id' => 0, 'status' => 'debug_mode');
    }

    /**
     * Returns description of method result value for create_scorm.
     *
     * @return external_single_structure
     */
    public static function create_scorm_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Course module ID'),
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    // =========================================================================
    // CREATE QUIZ
    // =========================================================================
    /**
     * Returns description of method parameters for create_quiz.
     *
     * @return external_function_parameters
     */
    public static function create_quiz_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'sectionnum' => new external_value(PARAM_INT, 'Section ID'),
                'name' => new external_value(PARAM_TEXT, 'Quiz Name'),
                'intro' => new external_value(PARAM_RAW, 'Intro/Description', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Create a new Quiz module.
     *
     * @param int $courseid Course ID
     * @param int $sectionnum Section ID
     * @param string $name Quiz Name
     * @param string $intro Intro/Description
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function create_quiz($courseid, $sectionnum, $name, $intro = '') {
        // 1. Validate parameters
        $params = self::validate_parameters(self::create_quiz_parameters(), compact('courseid', 'sectionnum', 'name', 'intro'));

        // 2. Build the Object
        $module = new \stdClass();
        $module->course = $params['courseid'];
        $module->section = $params['sectionnum'];
        $module->name = $params['name'];
        $module->intro = $params['intro'];
        $module->introformat = 1;

        // --- NUCLEAR DEFAULTS: SATISFY ALL DB CONSTRAINTS ---

        // A. Timing & Access
        $module->timeopen = 0;
        $module->timeclose = 0;
        $module->timelimit = 0;
        $module->overduehandling = 'autosubmit';
        $module->graceperiod = 0;
        $module->password = '';
        $module->subnet = '';
        $module->browsersecurity = '-';
        $module->delay1 = 0;
        $module->delay2 = 0;

        // B. Grading & Attempts
        $module->preferredbehaviour = 'deferredfeedback';
        $module->attempts = 0;       // Unlimited
        $module->attemptonlast = 0;
        $module->grademethod = 1;    // GRADEHIGHEST
        $module->decimalpoints = 2;
        $module->questiondecimalpoints = -1;
        $module->grade = 10;
        $module->sumgrades = 0;

        // C. Layout & Navigation
        $module->questionsperpage = 1;
        $module->navmethod = 'free';
        $module->shuffleanswers = 1;
        $module->showuserpicture = 0;
        $module->showblocks = 0;

        // D. Review Options (Crucial Bitmasks)
        // These control what students see after the quiz.
        // Values: 69888 (Attempt), 4352 (Marks/Feedback/Correctness)
        $module->reviewattempt = 69888;
        $module->reviewcorrectness = 4352;
        $module->reviewmarks = 4352;
        $module->reviewspecificfeedback = 4352;
        $module->reviewgeneralfeedback = 4352;
        $module->reviewrightanswer = 4352;
        $module->reviewoverallfeedback = 4352;

        // E. Completion & Extra
        $module->completion = 1;
        $module->completionview = 0;
        $module->completionexpected = 0;
        $module->completionattemptsexhausted = 0;
        $module->completionpass = 0;
        $module->allowofflineattempts = 0;
        $module->canredoquestions = 0;

        // F. Timestamps
        $module->timecreated = time();
        $module->timemodified = time();

        // 3. Call the Robust Helper (Bypass/Sanitization)
        $cmid = self::add_to_course($module, 'quiz');

        return array('id' => $cmid, 'status' => 'success');
    }

    /**
     * Returns description of method result value for create_quiz.
     *
     * @return external_single_structure
     */
    public static function create_quiz_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Module ID'),
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    /**
     * Returns description of method parameters for delete_module.
     *
     * @return external_function_parameters
     */
    public static function delete_module_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            )
        );
    }

    /**
     * Delete a course module - DEBUG MODE.
     *
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function delete_module() {
        // DEBUG MODE - Stub
        return array('status' => 'debug_mode');
    }

    /**
     * Returns description of method result value for delete_module.
     *
     * @return external_single_structure
     */
    public static function delete_module_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    /**
     * Returns description of method parameters for update_section.
     *
     * @return external_function_parameters
     */
    public static function update_section_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'sectionnum' => new external_value(PARAM_INT, 'Section number'),
                'name' => new external_value(PARAM_TEXT, 'Section name'),
            )
        );
    }

    /**
     * Update a course section name - DEBUG MODE.
     *
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function update_section() {
        // DEBUG MODE - Stub
        return array('status' => 'debug_mode');
    }

    /**
     * Returns description of method result value for update_section.
     *
     * @return external_single_structure
     */
    public static function update_section_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Status message'),
            )
        );
    }

    // =========================================================================
    // ASSIGN TEACHER (STANDARD)
    // =========================================================================
    /**
     * Returns description of method parameters for assign_teacher.
     *
     * @return external_function_parameters
     */
    public static function assign_teacher_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
            )
        );
    }

    /**
     * Assign a user as "Editing Teacher" to a course.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return array
     * @throws moodle_exception Native Moodle exceptions will bubble up
     */
    public static function assign_teacher($courseid, $userid) {
        global $DB, $CFG;

        // 1. Validate parameters
        $params = self::validate_parameters(self::assign_teacher_parameters(), compact('courseid', 'userid'));

        // 2. CRITICAL FIX: Load the correct Enrolment Library
        require_once($CFG->dirroot . '/lib/enrollib.php');

        // 3. Verify entities
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        $user = $DB->get_record('user', array('id' => $params['userid']), '*', MUST_EXIST);

        // 4. Get Standard "Editing Teacher" Role
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'), MUST_EXIST);

        // 5. Handle Enrolment (Manual Method)
        $enrol = enrol_get_plugin('manual');
        
        // Robustness: Check if manual plugin is enabled generally, if not, we can't proceed easily without force-enabling it
        if (!$enrol) {
            throw new moodle_exception('generalexceptionmessage', 'error', '', 'Manual enrolment plugin is disabled on this site.');
        }

        $instances = enrol_get_instances($course->id, true);
        $manualinstance = null;

        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                $manualinstance = $instance;
                break;
            }
        }

        // Auto-create manual instance if missing
        if (!$manualinstance) {
            $instanceid = $enrol->add_instance($course);
            $manualinstance = $DB->get_record('enrol', array('id' => $instanceid), '*', MUST_EXIST);
        }

        // 6. Enrol the user
        $enrol->enrol_user($manualinstance, $user->id, $roleid);

        return array(
            'status' => 'success',
            'message' => "User {$user->username} (ID: $userid) assigned as Teacher to Course {$course->shortname}"
        );
    }

    /**
     * Returns description of method result value for assign_teacher.
     *
     * @return external_single_structure
     */
    public static function assign_teacher_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Status'),
                'message' => new external_value(PARAM_TEXT, 'Message'),
            )
        );
    }
}
