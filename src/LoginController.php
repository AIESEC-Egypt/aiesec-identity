<?php
namespace AIESEC\Identity;

use GISwrapper\AuthProviderCombined;
use GISwrapper\AuthProviderEXPA;
use GISwrapper\AuthProviderOP;
use GISwrapper\GIS;
use Exception;

/**
 * Class LoginController
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
class LoginController
{
    /**
     * @param String $username
     * @param String $password
     * @param string $type optional 'EXPA' or 'OP' to use those AuthProviders. Standard is 'combined'.
     * @throws Error
     */
    public static function login($username, $password, $type = "combined") {
        // create right Authentication Provider
        switch($type) {
            case 'EXPA':
                $user = new AuthProviderEXPA($username, $password, VERIFY_PEER);
                break;

            case 'OP':
                $user = new AuthProviderOP($username, $password, VERIFY_PEER);
                break;

            default:
                $user = new AuthProviderCombined($username, $password, VERIFY_PEER);
                break;
        }
        // set session
        $user->setSession(dirname(dirname(__FILE__)) . '/sessions/' . md5(microtime()) . ".txt");

        // try to login to GIS Identity
        try {
            $user->getToken();
        } catch (\GISwrapper\InvalidCredentialsException $e) {
            Template::run('loginform', ['error' => "Invalid Credentials"]);
            return false;
        } catch (Exception $e) {
            Template::run('loginform', ['error' => "There was an unknown error while checking your credentials. Most probably this is a temporary error."]);
            return false;
        }

        // get current person object
        switch($type) {
            case 'EXPA':
            case 'OP':
                $gis = new GIS($user);
                $current_person = $gis->current_person->get();
                break;

            default:
                $current_person = $user->getCurrentPerson();
                break;
        }

        // check if person exists
        if(!PersonController::existsLoginUpdate($current_person->person->id, $user->getSession())) {
            PersonController::firstLogin($user, $current_person, $user->getSession());
        }
        
        // run Login Hook
        if(Plugins::onLogin($user, $current_person)) {
            // generate redirect link
            $redirect = 'index.php?action=me';
            if(isset($_SESSION['redirect'])) $redirect = $_SESSION['redirect'];

            // clear session
            $_SESSION = array();

            // set session data
            $_SESSION['person_id'] = intval($user->getCurrentPerson()->person->id);
            $_SESSION['full_name'] = $user->getCurrentPerson()->person->full_name;
            $_SESSION['gis-identity-session'] = $user->getSession();

            // redirect
            header('Location: ' . $redirect);
        } else {
            throw new Error(500, "Plugin prevented login");
        }
    }

    /**
     * @throws Error
     */
    public static function logout() {
        if(Plugins::onBeforeLogout($_SESSION['person_id'])) {
            // delete all access tokens of that person from the database
            DBController::delete("DELETE FROM `access_tokens` WHERE `person_id`='" . intval($_SESSION['person_id']) . "' OR `expires_at` <= NOW()");

            // remove GIS Identity Session
            if(file_exists($_SESSION['gis-identity-session'])) {
                unlink($_SESSION['gis-identity-session']);
            }

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

            if(Plugins::onAfterLogout()) {
                header('Location: index.php?action=login');
            } else {
                throw new Error(500, "Logged out from the system, but some plugins failed");
            }
        } else {
            throw new Error(500, "Plugin prevented logout");
        }
    }
}