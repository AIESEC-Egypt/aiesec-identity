<?php
/**
 * current_person.php
 * returns the person object behind an access token
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */

header('Content-Type: application/json');

if(!isset($_GET['access_token']) || strlen($_GET['access_token']) > 255) {
    header('HTTP/1.0 401 Unauthorized');
    echo '{"error": "401", "msg": "no or invalid access token"}';
} else {
    require_once(dirname(__FILE__) . '/../config.php');
    require_once(dirname(__FILE__) . '/../plugins/plugin.runner.php');

    // Create connection
    $conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

    // Check connection
    if (!$conn) {
        echo '{"error": "500", "msg": "Database Error"}';
        trigger_error("Connection to database failed: " . mysqli_connect_error(), E_USER_ERROR);
    } else {
        // delete expired access tokens from database
        if ($conn->query("DELETE FROM `access_tokens` WHERE `expires_at` <= NOW()") !== TRUE) {
            trigger_error("Query to delete expired access tokens failed: " . $conn->mysqli_error(), E_USER_WARNING);
        }

        // get current person from database
        if ($result = $conn->query("SELECT * FROM `access_tokens` WHERE `access_token`='" . mysqli_real_escape_string($conn, $_GET['access_token']) . "' AND `expires_at` > NOW() LIMIT 1")) {
            if ($result->num_rows === 1) {
                $data = mysqli_fetch_assoc($result);
                $result->close();

                // get scopes and roles
                if ($result2 = $conn->query("SELECT `scope`.`name` as scope, GROUP_CONCAT(`role`.`name` SEPARATOR ';') as roles FROM `persons_scopes` LEFT JOIN `scopes` scope ON `scope`.`id`=`persons_scopes`.`scope_id` LEFT JOIN `roles` role ON `role`.`id`=`persons_scopes`.`role_id` WHERE `persons_scopes`.`person_id` = " . intval($data['person_id']) . " AND (`expires_at` > NOW() OR `expires_at` IS NULL) GROUP BY `persons_scopes`.`scope_id`")) {
                    $scopes = array();
                    if ($result2->num_rows > 0) {
                        while ($row = mysqli_fetch_assoc($result2)) {
                            $scopes[$row['scope']] = explode(";", $row['roles']);
                        }
                    }
                    $result2->close();
                    $conn->close();

                    // build object to return
                    $person = array(
                        'person' => json_decode($data['person']),
                        'current_offices' => json_decode($data['current_offices']),
                        'current_positions' => json_decode($data['current_positions']),
                        'current_teams' => json_decode($data['current_teams']),
                        'scopes' => $scopes,
                        'expires_at' => date('c', strtotime($data['expires_at']))
                    );

                    $p = new PluginRunner($ACTIVE_PLUGINS);
                    $person = $p->onCurrentPerson($person);

                    if($person === FALSE) {
                        echo '{"error": "500", "msg": "Plugin failure"}';
                    } else {
                        echo json_encode($person);
                    }
                } else {
                    $conn->close();
                    echo '{"error": "500", "msg": "Database Error"}';
                    trigger_error("Could not retrieve scopes and roles: " . $conn->mysqli_error(), E_USER_ERROR);
                }
            } else {
                $result->close();
                $conn->close();
                header('HTTP/1.0 401 Unauthorized');
                echo '{"error": "401", "msg": "invalid or expired access token"}';
            }
        }
    }
}