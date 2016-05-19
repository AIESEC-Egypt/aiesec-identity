<?php
/**
 * logout.php
 * destroy session and redirect to front page
 * 
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */

session_start();

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/plugins/plugin.runner.php');

$p = new PluginRunner($ACTIVE_PLUGINS);
if($p->onBeforeLogout($_SESSION['access_token'], $_SESSION['person_id'])) {
    // Unset all of the session variables.
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();

    if($p->onAfterLogout()) {
        header('Location: index.php');
    } else {
        echo "Logged out from the system, but some plugins failed";
    }
} else {
    echo "Logout failed";
}

