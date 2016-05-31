<?php
namespace AIESEC\Identity;

/**
 * Class PersonController
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
class PersonController
{
    /**
     * Checks if a person exists in the database
     * when returning false, also check DBController::hasError()
     *
     * @param int $id person id
     * @return bool
     */
    public static function exists($id) {
        $res = DBController::query("SELECT `id` from `persons` WHERE `id`=" . intval($id) . " LIMIT 1;");
        if($res && $res->num_rows == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if a person exists in the database and sets the last_login date to NOW()
     * when returning false, also check DBController::hasError()
     *
     * @param int $id person id
     * @return bool
     */
    public static function existsLoginUpdate($id, $session_file) {
        $res = DBController::update("UPDATE `persons` SET `last_login`=NOW(), `session_file`='" . DBController::escape($session_file) . "' WHERE `id`=" . intval($id). " LIMIT 1;");
        if($res === 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a person to the database and run the onFirstLogin Hook
     *
     * @param \GISwrapper\AuthProvider $authProvider
     * @param object $person current person object
     * @param string $session_file file path to GIS identity session
     * @return bool
     * @throws Error
     */
    public static function firstLogin($authProvider, $person, $session_file) {
        $query = "INSERT INTO `persons` (`id`, `email`, `first_name`, `middle_name`, `last_name`, `full_name`,  `last_login`, `session_file`) VALUES (";
        $query .= intval($person->person->id) . ", ";
        $query .= "'" . DBController::escape($person->person->email) . "', ";
        $query .= "'" . DBController::escape($person->person->first_name) . "', ";
        $query .= (isset($person->person->middle_name)) ? "'" . DBController::escape($person->person->middle_name) . "', " : "NULL, ";
        $query .= "'" . DBController::escape($person->person->last_name) . "', ";
        $query .= "'" . DBController::escape($person->person->full_name) . "', ";
        $query .= "NOW(), ";
        $query .= "'" . DBController::escape(realpath($session_file)) . "');";

        DBController::insert($query);

        if(Plugins::onFirstLogin($authProvider, $person)) {
            return true;
        } else {
            throw new Error(500, "Plugin prevented user creation");
        }
    }

    /**
     * returns the scope of the given person
     *
     * @param int $id person id
     * @return array
     */
    public static function getScopes($id) {
        $result = DBController::query("SELECT `scope`.`name` as scope, GROUP_CONCAT(`role`.`name` SEPARATOR ';') as roles FROM `persons_scopes` LEFT JOIN `scopes` scope ON `scope`.`id`=`persons_scopes`.`scope_id` LEFT JOIN `roles` role ON `role`.`id`=`persons_scopes`.`role_id` WHERE `persons_scopes`.`person_id` = " . intval($id) . " AND (`expires_at` > NOW() OR `expires_at` IS NULL) GROUP BY `persons_scopes`.`scope_id`");
        $scopes = array();
        while ($row = $result->fetch_row()) {
            $scopes[$row[0]] = explode(';', $row[1]);
        }
        return $scopes;
    }

    /**
     * add as access token and the corresponding current person to the database
     *
     * @param string $token
     * @param int $expires_at
     * @param object $person
     * @return bool
     */
    public static function addAccessToken($token, $expires_at, $person) {
        $query = "INSERT INTO `access_tokens` (`access_token`, `expires_at`, `person_id`, `person`, `current_offices`, `current_positions`, `current_teams`) VALUES (";
        $query .= "'" . DBController::escape($token) . "', ";
        $query .= "FROM_UNIXTIME(" . intval($expires_at) . "), ";
        $query .= intval($person->person->id) . ", ";
        $query .= "'" . DBController::escape(json_encode($person->person)) . "', ";
        $query .= "'" . DBController::escape(json_encode($person->current_offices)) . "', ";
        $query .= "'" . DBController::escape(json_encode($person->current_positions)) . "', ";
        $query .= "'" . DBController::escape(json_encode($person->current_teams)) . "'";
        $query .= ");";

        DBController::insert($query);

        return true;
    }

    /**
     * returns the person object for the specified access token
     *
     * @param string $token access tokens
     * @return array
     * @throws Error
     */
    public static function currentPerson($token)
    {
        // get current person from database
        $result = DBController::query("SELECT * FROM `access_tokens` WHERE `access_token`='" . DBController::escape($token) . "' AND `expires_at` > NOW() LIMIT 1");
        if ($result->num_rows === 1) {
            $data = mysqli_fetch_assoc($result);

            // get scopes
            $scopes = self::getScopes($data['person_id']);

            // build object
            $person = array(
                'person' => json_decode($data['person']),
                'current_offices' => json_decode($data['current_offices']),
                'current_positions' => json_decode($data['current_positions']),
                'current_teams' => json_decode($data['current_teams']),
                'scopes' => $scopes,
                'expires_at' => date('c', strtotime($data['expires_at']))
            );

            // run plugin hook
            $person = Plugins::onCurrentPerson($person);
            if($person === FALSE) {
                throw new Error(500, "Plugin failure");
            } else {
                return $person;
            }
        } else {
            throw new Error(401, "invalid access token");
        }
    }
}