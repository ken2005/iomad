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
 * Web service function definitions for local_crewerp_api.
 *
 * @package    local_crewerp_api
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_crewerp_api_create_url' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'create_url',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Create a new URL module in a course section',
        'type'        => 'write',
        'capabilities' => 'mod/url:addinstance',
        'ajax'        => false,
    ),
    'local_crewerp_api_update_url' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'update_url',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Update an existing URL module',
        'type'        => 'write',
        'capabilities' => 'mod/url:addinstance',
        'ajax'        => false,
    ),
    'local_crewerp_api_create_label' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'create_label',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Create a new Label module in a course section',
        'type'        => 'write',
        'capabilities' => 'mod/label:addinstance',
        'ajax'        => false,
    ),
    'local_crewerp_api_update_label' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'update_label',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Update an existing Label module',
        'type'        => 'write',
        'capabilities' => 'mod/label:addinstance',
        'ajax'        => false,
    ),
    'local_crewerp_api_create_scorm' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'create_scorm',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Create a new SCORM module in a course section',
        'type'        => 'write',
        'capabilities' => 'mod/scorm:addinstance',
        'ajax'        => false,
    ),
    'local_crewerp_api_create_quiz' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'create_quiz',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Create a new Quiz module in a course section',
        'type'        => 'write',
        'capabilities' => 'mod/quiz:addinstance',
        'ajax'        => false,
    ),
    'local_crewerp_api_delete_module' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'delete_module',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Delete a course module',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities',
        'ajax'        => false,
    ),
    'local_crewerp_api_update_section' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'update_section',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Update a course section name',
        'type'        => 'write',
        'capabilities' => 'moodle/course:update',
        'ajax'        => false,
    ),
    'local_crewerp_api_assign_teacher' => array(
        'classname'   => 'local_crewerp_api_external',
        'methodname'  => 'assign_teacher',
        'classpath'   => 'local/crewerp_api/externallib.php',
        'description' => 'Assign a user as Editing Teacher to a course',
        'type'        => 'write',
        'capabilities' => 'moodle/course:enrolreview',
        'ajax'        => false,
    ),
);

$services = array(
    'CrewERP Service' => array(
        'functions' => array(
            'local_crewerp_api_create_url',
            'local_crewerp_api_update_url',
            'local_crewerp_api_create_label',
            'local_crewerp_api_update_label',
            'local_crewerp_api_create_scorm',
            'local_crewerp_api_create_quiz',
            'local_crewerp_api_delete_module',
            'local_crewerp_api_update_section',
            'local_crewerp_api_assign_teacher',
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'crewerp_service',
    ),
);
