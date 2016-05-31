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
    /**
     * @var \mysqli
     */
    private static $_conn;

    /**
     * @return \mysqli
     * @throws Error
     */
    public static function getConnection() {
        if(!isset(self::$_conn)) {
            self::$_conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
        }
        if (!self::$_conn) {
            trigger_error("Could not connect to database: " . self::$_conn->error, E_USER_ERROR);
            throw new Error(500, "Database connection faild");
        } else {
            return self::$_conn;
        }
    }

    /**
     * Deletes invalid access tokens from the database
     *
     * @return bool
     * @throws Error
     */
    public static function cleanUp() {
        if (self::getConnection()->query("DELETE FROM `access_tokens` WHERE `expires_at` <= NOW()") !== TRUE) {
            trigger_error("Could not delete old access tokens: " . self::$_lastError, E_USER_WARNING);
            return false;
        } else {
            return true;
        }
    }

    /**
     * run a select query and check for success
     *
     * @param string $query
     * @return \mysqli_result
     * @throws Error
     */
    public static function query($query) {
        $res = self::getConnection()->query($query);
        if($res !== FALSE) {
            return $res;
        } else {
            trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
            throw new Error(500, "Database Error");
        }
    }


    /**
     * run an update query and check for success
     *
     * @param string $query
     * @return int number of affected rows
     * @throws Error
     */
    public static function update($query) {
        if(self::getConnection()->query($query) === TRUE) {
            return self::$_conn->affected_rows;
        } else {
            trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
            throw new Error(500, "Database Error");
        }
    }

    /**
     * run an insert query and check for success
     *
     * @param string $query
     * @return int insert id
     * @throws Error
     */
    public static function insert($query) {
        if(self::getConnection()->query($query) === TRUE) {
            return self::$_conn->insert_id;
        } else {
            trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
            throw new Error(500, "Database Error");
        }
    }

    /**
     * run a delete query and check for success
     *
     * @param string $query
     * @return bool
     * @throws Error
     */
    public static function delete($query) {
        if(self::getConnection()->query($query) === TRUE) {
            return true;
        } else {
            trigger_error("Database Error: " . self::$_conn->error, E_USER_ERROR);
            throw new Error(500, "Database Error");
        }
    }

    /**
     * escape a query parameter
     *
     * @param string $var
     * @return string
     * @throws Error
     */
    public static function escape($var) {
        return self::getConnection()->real_escape_string($var);
    }
}