<?php
namespace AIESEC\Identity\Plugin;

use AIESEC\Identity\DBController;
use \AIESEC\Identity\Plugin;

/**
 * Class active
 * Plugin to determine if user is an active AIESECer and handle the active scope
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */
class active implements Plugin {

    function onLogin($authProvider, $current_person)
    {
        // check if person has active positions
        if(count($current_person->current_positions) > 0) {
            // remove active:no
            $this->removeRole($current_person->person->id, "active", "no");

            // search for latest end date
            $end_date = 0;
            foreach($current_person->current_positions as $p) {
                if(strtotime($p->end_date) > $end_date) $end_date = strtotime($p->end_date);
            }

            // add active:yes
            $this->addRole($current_person->person->id, "active", "yes", date('c', $end_date));
        } else {
            // if there are no positions remove active:yes and add active:no
            $this->removeRole($current_person->person->id, "active", "yes");
            $this->addRole($current_person->person->id, "active", "no");
        }

        return true;
    }

    function onFirstLogin($authProvider, $current_person)
    {
        return true;
    }

    function onListSites($scopes, $personId)
    {
        $r = array(array('https://opportunities.aiesec.org', 'Opportunities Portal'));
        if(isset($scopes['active']) && in_array('yes', $scopes['active'])) {
            $r[] = array('https://experience.aiesec.org', 'EXPA');
        }
        return $r;
    }

    function onBeforeLogout($personId)
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

    private function getId($type, $name) {
        $table = "";
        switch ($type) {
            case 'scope':
                $table = '`scopes`';
                break;

            case 'role':
                $table = '`roles`';
                break;

            default:
                return false;
        }
        $result = DBController::query("SELECT `id` FROM " . $table . " WHERE `name`='" . DBController::escape($name) . "' LIMIT 1");
        if ($result->num_rows == 1) {
            return $result->fetch_row()[0];
        } else {
            return DBController::insert("INSERT INTO " . $table . " SET `name`='" . DBController::escape($name) . "'");
        }
    }

    private function addRole($personId, $scope, $role, $expires_at = null) {
        $scope = $this->getId('scope', $scope);
        $role = $this->getId('role', $role);

        DBController::insert("INSERT IGNORE INTO `persons_scopes` (`person_id`, `scope_id`, `role_id`, `expires_at`) VALUES (" . intval($personId) . ", " . intval($scope) . ", " . intval($role) . ", " . (($expires_at == null) ? "NULL" : "'" . $expires_at . "'") . ")");
    }

    private function removeRole($personId, $scope, $role) {
        $scope = $this->getId('scope', $scope);
        $role = $this->getId('role', $role);

        DBController::delete("DELETE FROM `persons_scopes` WHERE `person_id`=" . intval($personId) . " AND `scope_id`=" . intval($scope) . " AND `role_id`=" . intval($role) . " LIMIT 1");
    }
}