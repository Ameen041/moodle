<?php

namespace local_ops\external;

use core\session\exception;
use core_external\external_function_parameters as Core_externalExternal_function_parameters;
use core_external\external_multiple_structure as Core_externalExternal_multiple_structure;
use core_external\external_single_structure as Core_externalExternal_single_structure;
use core_external\external_value as Core_externalExternal_value;
use core_external\external_api as exterapi;
use core_external\external_warnings;
use stdClass;

class register_enroll_student extends \core_external\external_api
{
    public static function enroll_student_parameters()
    {
        return new Core_externalExternal_function_parameters(
            array(
                'username' => new Core_externalExternal_value(PARAM_TEXT, 'username'),
                'courses' => new Core_externalExternal_multiple_structure(
                    new Core_externalExternal_value(PARAM_TEXT, 'courseid')
                )
            )
        );
    }
    public static function enroll_student($username, $courses = array())
    {
        global $DB, $CFG;
        try {
            $user = $DB->get_record('user', ['username' => $username]);
            $plugin = enrol_get_plugin('apply');
            foreach ($courses as $c) {
                $enrolmethds = $DB->get_records('enrol', array('enrol' => 'apply', 'courseid' => $c));
                foreach ($enrolmethds as $instance) {
                    $timestart = time();
                    $timeend = $timestart + $instance->enrolperiod;
                    $plugin->enrol_user($instance, $user->id, 5, $timestart, $timeend, ENROL_USER_SUSPENDED);
                }
            }
            return $result = array(
                'status' => 'success',
                'message' => var_dump($plugin)
            );
        } catch (Exception $e) {
            return $result = array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }
    public static function enroll_student_returns()
    {

        return new Core_externalExternal_single_structure(
            array(
                // 'success' => new Core_externalExternal_value(PARAM_BOOL, 'True if the user was created false otherwise'),
                'status'  => new Core_externalExternal_value(PARAM_RAW, 'staus'),
                'message' => new Core_externalExternal_value(PARAM_RAW, 'error message'),
            )
        );
    }
    // Register new student
    public static function register_student_parameters()
    {
        return new Core_externalExternal_function_parameters(
            array(
                'username' => new Core_externalExternal_value(PARAM_TEXT, 'username'),
                'password' => new Core_externalExternal_value(PARAM_TEXT, 'password'),
                'firstname' => new Core_externalExternal_value(PARAM_TEXT, 'first name'),
                'lastname' => new Core_externalExternal_value(PARAM_TEXT, 'last name'),
                'email' => new Core_externalExternal_value(PARAM_TEXT, 'email'),
                'arabfullname' => new Core_externalExternal_value(PARAM_TEXT, 'arabic full name'),
                'mobnum' => new Core_externalExternal_value(PARAM_TEXT, 'mobile number'),
                'birthdate' => new Core_externalExternal_value(PARAM_TEXT, 'birthdate'),
            )
        );
    }
    public static function register_student($username, $password, $firstname, $lastname, $email, $arabfullname, $mobnum,$birthdate)
    {
        global $DB, $CFG;
        $customfields = array();
        
        $customfields[0] = ['type' => 'text', 'name' => 'profile_field_arabname', 'value' => $arabfullname];
        $customfields[1] = ['type' => 'text', 'name' => 'profile_field_m_num', 'value' => $mobnum];
        $customfields[2] = ['type' => 'datetime', 'name' => 'profile_field_brthdate','value'=>strtotime($birthdate)];
        $result = exterapi::call_external_function('auth_email_signup_user', [
            'username' => $username, 'password' => $password,
            'firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'customprofilefields' => $customfields
        ]);

        // if user created successfully
        try {
            if($result['error'] && $result["exception"]->errorcode != "auth_emailnoemail"){
                $messages = array();
            $messages[0] = ["field" => 'error', "message" => $result["exception"]->debuginfo];
            $result = array(
                'status' => 'error',
                'messages' => $messages
            );
            }
            else if ((!$result["error"] && $result["data"]["success"])
                || $result["error"] && $result["exception"]->errorcode == "auth_emailnoemail"
            ) {
                $messages = array();
                $messages[0] = ["field" => '', "message" => ''];
                $result = array(
                    'status' => 'success',
                    'messages' => $messages
                );
            } else if (!$result["error"]["success"]) {
                $messages = array();
              //  $messages[0] = ["field" => 'unknown', "message" => var_dump($result)];
                foreach ($result["data"]["warnings"] as $warn) {
                    $messages[] = ["field" => $warn["item"], "message" => $warn["message"]];
                }
                $result = array(
                    'status' => 'warnings',
                    'messages' => $messages
                );
            }

            return $result;
        } catch (Exception $e) {
            $messages = array();
            $messages[0] = ["field" => null, "message" => $e->getMessage()];
            $result = array(
                'status' => 'error',
                'messages' => $messages
            );
            return $result;
        }
    }
    public static function register_student_returns()
    {

        return new Core_externalExternal_single_structure(
            array(
                'status' => new Core_externalExternal_value(PARAM_TEXT, 'success or error'),
                'messages' => new Core_externalExternal_multiple_structure(
                    new Core_externalExternal_multiple_structure(
                        new Core_externalExternal_value(PARAM_TEXT, 'field'),
                        new Core_externalExternal_value(PARAM_TEXT, 'message')
                    )
                ),
            )
        );
    }
}
