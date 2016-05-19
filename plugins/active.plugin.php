<?php

/**
 * Class active
 * Plugin to determine if user is an active AIESECer and handle the active scope
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */
class active implements Plugin {

    function onLogin($user, $mysql)
    {
        if(count($user->getCurrentPerson()->current_positions) > 0) {
            if(!$this->removeRole($mysql, $user->getCurrentPerson()->person->id, "active", "no")) return false;
            $end_date = 0;
            foreach($user->getCurrentPerson()->current_positions as $p) {
                if(strtotime($p->end_date) > $end_date) $end_date = strtotime($p->end_date);
            }
            if(!$this->addRole($mysql, $user->getCurrentPerson()->person->id, "active", "yes", date('c', $end_date))) return false;
            return true;
        } else {
            if($this->removeRole($mysql, $user->getCurrentPerson()->person->id, "active", "yes") && $this->addRole($mysql, $user->getCurrentPerson()->person->id, "active", "no")) {
                return true;
            } else {
                return false;
            }
        }
    }

    function onFirstLogin($user, $mysql)
    {
        return true;
    }

    function onListSites($scopes, $personId, $mysql)
    {
        $r = array(array('https://opportunities.aiesec.org', 'Opportunities Portal'));
        if(isset($scopes['active']) && in_array('yes', explode(';', $scopes['active']))) {
            $r[] = array('https://experience.aiesec.org', 'EXPA');
        }
        return $r;
    }

    function onBeforeLogout($accessToken, $personId)
    {
        return true;
    }

    function onAfterLogout()
    {
        return true;
    }

    function onCurrentPerson($person)
    {
        return $person;
    }

    private function getId($conn, $type, $name) {
        $table = "";
        switch($type) {
            case 'scope':
                $table = '`scopes`';
                break;

            case 'role':
                $table = '`roles`';
                break;

            default:
                return false;
        }
        if($result = $conn->query("SELECT `id` FROM " . $table . " WHERE `name`='" . $conn->real_escape_string($name) . "' LIMIT 1")) {
            if($result->num_rows == 1) {
                return $result->fetch_row()[0];
            } else {
                if($conn->query("INSERT INTO " . $table . " SET `name`='" . $conn->real_escape_string($name) . "'") === TRUE) {
                    return mysqli_insert_id($conn);
                } else {
                    trigger_error("Database Error: " . $conn->error, E_USER_ERROR);
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    private function addRole($conn, $personId, $scope, $role, $expires_at = null) {
        $scope = $this->getId($conn, 'scope', $scope);
        $role = $this->getId($conn, 'role', $role);
        if($scope === false || $role === false) {
            echo $scope."\n";
            echo $role."\n";
            return false;
        } else {
            if($conn->query("INSERT IGNORE INTO `persons_scopes` (`person_id`, `scope_id`, `role_id`, `expires_at`) VALUES (" . intval($personId) . ", " . intval($scope) . ", " . intval($role) . ", " . (($expires_at == null) ? "NULL" : "'" . $expires_at . "'") . ")")) {
                return true;
            } else {
                trigger_error("Database Error: " . $conn->error, E_USER_ERROR);
                return false;
            }
        }
    }

    private function removeRole($conn, $personId, $scope, $role) {
        $scope = $this->getId($conn, 'scope', $scope);
        $role = $this->getId($conn, 'role', $role);
        if($scope === false || $role === false) {
            echo $scope."\n";
            echo $role."\n";
            return false;
        } else {
            if($conn->query("DELETE FROM `persons_scopes` WHERE `person_id`=" . intval($personId) . " AND `scope_id`=" . intval($scope) . " AND `role_id`=" . intval($role) . " LIMIT 1")) {
                return true;
            } else {
                trigger_error("Database Error: " . $conn->error, E_USER_ERROR);
                return false;
            }
        }
    }
}