<?php
namespace AIESEC\Identity;

/**
 * Class DBController
 * provides MySQL functionality
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
class DBController
{
    private static $_conn;
    private static $_lastError;
    private static $_error;

    public static function getConnection() {
        if(!isset(self::$_conn)) {
            self::$_conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
            if (!self::$_conn) {
                Template::run('error', ['message' => "Connection to Database failed", 'code' => 500]);
                trigger_error("Could not connect to database: " . self::$_conn->error, E_USER_ERROR);
                return false;
            }
        }
        return self::$_conn;
    }

    public static function cleanUp() {
        if(self::getConnection()) {
            if (self::$_conn->query("DELETE FROM `access_tokens` WHERE `expires_at` <= NOW()") !== TRUE) {
                self::$_error = true;
                self::$_lastError = self::$_conn->error;
                trigger_error("Could not delete old access tokens: " . self::$_lastError, E_USER_WARNING);
                return false;
            } else {
                self::$_error = self::$_lastError = false;
                return true;
            }
        } else {
            return false;
        }
    }

    public static function query($query) {
        if(self::getConnection()) {
            if($res = self::$_conn->query($query)) {
                self::$_error = self::$_lastError = false;
                return $res;
            } else {
                self::$_error = true;
                self::$_lastError = self::$_conn->error;
                trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
                return false;
            }
        } else {
            return false;
        }
    }

    public static function update($query) {
        if(self::getConnection()) {
            if(self::$_conn->query($query) === TRUE) {
                self::$_error = self::$_lastError = false;
                return self::$_conn->affected_rows;
            } else {
                self::$_error = true;
                self::$_lastError = self::$_conn->error;
                trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
                return false;
            }
        } else {
            return false;
        }
    }

    public static function insert($query) {
        if(self::getConnection()) {
            if(self::$_conn->query($query) === TRUE) {
                self::$_error = self::$_lastError = false;
                return self::$_conn->insert_id;
            } else {
                self::$_error = true;
                self::$_lastError = self::$_conn->error;
                trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
                return false;
            }
        } else {
            return false;
        }
    }

    public static function delete($query) {
        if(self::getConnection()) {
            if(self::$_conn->query($query) === TRUE) {
                self::$_error = self::$_lastError = false;
                return true;
            } else {
                self::$_error = true;
                self::$_lastError = self::$_conn->error;
                trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
                return false;
            }
        } else {
            return false;
        }
    }

    public static function hasError() {
        return self::$_error;
    }

    public static function escape($var) {
        if(self::getConnection()) {
            return self::$_conn->real_escape_string($var);
        }
    }
}