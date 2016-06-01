<?php
/**
 * index.php
 * main controller for AIESEC identity
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
namespace AIESEC\Identity;

// require composer autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// check that config file exists
if(!file_exists(dirname(__DIR__) . '/config.php')) {
    die("System not configured");
}

// load config
require_once dirname(__DIR__) . '/config.php';

// check that session path exists
if(!is_dir(SESSION_PATH) || !is_writeable(SESSION_PATH)) {
    trigger_error("Session folder does not exists or is not writeable", E_USER_ERROR);
    die("configuration error");
}

// adjust session parameters
ini_set("session.gc_maxlifetime", SESSION_LIFETIME);
ini_set("session.gc_divisor", "1");
ini_set("session.gc_probability", "1");
session_save_path(SESSION_PATH);

// start session
session_start();

// check that $ACTIVE_PLUGINS isset
if(!isset($ACTIVE_PLUGINS)) {
    trigger_error("Plugins are not configured. Maybe you forgot the namespace in your config file.", E_USER_WARNING);
    $ACTIVE_PLUGINS = array();
}

// instantiate plugin runner
Plugins::init($ACTIVE_PLUGINS);

// run the action
try {
    switch (getParam('action', 'me')) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] == "POST") {
                LoginController::login($_POST['username'], $_POST['password']);
            } else {
                Template::run('loginform', ['title' => 'login']);
            }
            break;

        case 'logout':
            LoginController::logout();
            break;

        case 'authorize':
            if (isset($_SESSION['gis-identity-session']) && file_exists($_SESSION['gis-identity-session'])) {
                switch (getParam('response_type')) {
                    case 'token':
                        OAuthController::tokenFlow(getParam('client_id'), getParam('redirect_uri'), getParam('scope'), getParam('state'));
                        break;

                    case 'customToken':
                        Template::$json = true;
                        header("Access-Control-Allow-Origin: *");
                        OAuthController::customTokenFlow(getParam('client_id'), getParam('client_secret'), getParam('user_id'), getParam('state'));
                        break;

                    default:
                        Template::run('error', ['code' => 400, 'message' => 'Response type not implemented']);
                }
            } else {
                $_SESSION = array();
                $_SESSION['redirect'] = 'index.php?action=authorize&response_type=' . urlencode(getParam('response_type')) . '&redirect_uri=' . urlencode(getParam('redirect_uri')) . '&client_id=' . urlencode(getParam('client_id')) . '&scope=' . urlencode(getParam('scope')) . '&state=' . urlencode(getParam('state'));
                header('Location: index.php?action=login');
            }
            break;

        case 'current_person':
            Template::$json = true;
            header("Access-Control-Allow-Origin: *");
            if (!isset($_GET['access_token']) || strlen($_GET['access_token']) > 255) {
                header('HTTP/1.0 401 Unauthorized');
                Template::run('error', ['code' => 401, 'message' => 'no or invalid access token']);
            } else {
                $p = PersonController::currentPerson($_GET['access_token']);
                if ($p !== false) {
                    Template::output(json_encode($p));
                } else {
                    Template::run('error', ['code' => 500, 'message' => 'Server Error']);
                }
            }
            break;

        case 'me':
            if (!isset($_SESSION['gis-identity-session']) || !file_exists($_SESSION['gis-identity-session'])) {
                header('Location: index.php?action=login');
            } else {
                $scopes = PersonController::getScopes($_SESSION['person_id']);
                if (is_array($scopes)) {
                    $sites = Plugins::onListSites($scopes, $_SESSION['person_id']);
                    if ($sites === false) {
                        Template::run('error', ['code' => 500, 'message' => 'Plugin failure']);
                    } else {
                        Template::run('me', ['scopes' => $scopes, 'sites' => $sites]);
                    }
                } else {
                    Template::run('error', ['code' => 500, 'message' => 'There was an error']);
                }
            }
            break;

        default:
            Template::run('error', ['code' => 404, 'message' => 'action not implemented']);
            break;
    }
    DBController::cleanUp();
} catch (Error $e) {
    $e->output();
}



function getParam($name, $default = "") {
    if(isset($_GET[$name])) {
        return $_GET[$name];
    } else {
        return $default;
    }
}