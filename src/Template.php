<?php
namespace AIESEC\Identity;

/**
 * Class Template
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
class Template
{
    private static $_executed = false;
    public static $json = false;

    public static function run($name, $params = array()) {
        if(!self::$_executed) {
            if(self::$json) {
                header('Content-Type: application/json');
                $path = dirname(__DIR__) . '/templates/' . $name . '.json.php';
            } else {
                $path = dirname(__DIR__) . '/templates/' . $name . '.php';
            }

            if(file_exists($path)) {
                foreach($params as $key => $value) {
                    ${$key} = $value;
                }
                require $path;
            } else {
                trigger_error("Template $name does not exists.", E_USER_ERROR);
            }
            self::$_executed = true;
        } else {
            return false;
        }
    }

    public static function output($val) {
        if(!self::$_executed) {
            if(self::$json) header('Content-Type: application/json');
            echo $val;
            return true;
        } else {
            return false;
        }
    }
}