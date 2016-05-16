<?php
/**
 * current_person.php
 * returns the person object behind an access token
 *
 * @author: Karl Johann Schubert <karljohann@familieschubi.de>
 */

if(!isset($_GET['access_token'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo '{"error": "401", "msg": "no access token"}';
} else {

}