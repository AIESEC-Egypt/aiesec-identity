<?php
namespace AIESEC\Identity;

use Exception;
use GISwrapper\AuthProviderCombined;
use GISwrapper\AuthProviderEXPA;
use GISwrapper\AuthProviderOP;
use GISwrapper\GIS;
use GISwrapper\InvalidCredentialsException;

/**
 * Class OAuthController
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
class OAuthController
{
    /**
     * performs the oAuth2 token flow
     *
     * @param String $clientId
     * @param String $redirectUri
     * @param String $scopes
     */
    public static function tokenFlow($clientId, $redirectUri, $scopes, $state) {
        if (!isset($_SESSION['gis-identity-session']) || !file_exists($_SESSION['gis-identity-session'])) {
            $_SESSION = array();
            $_SESSION['redirect'] = 'index.php?action=authorize&response_type=token&redirect_uri=' . urlencode($redirectUri) . '&client_id=' . urlencode($clientId) . '&scope=' . urlencode($scopes) . '&state=' . urlencode($state);
            header('Location: index.php?action=login');
        } else {
            // check that the redirect url is allowed for the client id
            if (self::checkRedirectUri($clientId, $redirectUri)) {
                // get the actual scopes of the current user
                $actual = PersonController::getScopes($_SESSION['person_id']);
                if (is_array($actual)) {
                    // check that the user has the needed scopes
                    if (self::checkScopes($actual, $scopes)) {
                        // generate token
                        try {
                            $token = self::getToken($_SESSION['gis-identity-session']);
                        } catch (InvalidCredentialsException $e) {
                            unlink($_SESSION['gis-identity-session']);
                            $_SESSION = array();
                            $_SESSION['redirect'] = 'index.php?action=authorize&response_type=token&redirect_uri=' . urlencode($redirectUri) . '&client_id=' . urlencode($clientId) . '&scope=' . urlencode($scopes) . '&state=' . urlencode($state);
                            header('Location: index.php?action=login');
                        }

                        // check token
                        if ($token === false) {
                            Template::run('error', ['code' => 500, 'message' => 'Could not retrieve token']);
                        } else {
                            header('Location: ' . $redirectUri . '?access_token=' . $token[0] . '&expires_at=' . urlencode(date('c', $token[1])) . '&expires_in=' . ($token[1] - time()) . '&state=' . urlencode($state));
                        }
                    } else {
                        Template::run('error', ['code' => 403, 'message' => 'You do not have enough rights to access this site']);
                    }
                } else {
                    Template::run('error', ['code' => 500, 'message' => 'Could not retrieve the users scopes']);
                }
            } else {
                Template::run('error', ['code' => 400, 'message' => 'invalid client id or redirect uri']);
            }
        }
    }

    /**
     * the custom token flow is a not official specified flow which allows permitted clients to retrieve an access token for a specified person even if this person is not active currently
     * this only works when the GIS Identity session is still valid
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param int $userId
     * @param string $state
     * @return bool
     * @throws Error
     */
    public static function customTokenFlow($clientId, $clientSecret, $userId, $state) {
        // check that client secret is correct
        if(self::checkClientSecret($clientId, $clientSecret)) {
            // get the session file
            $data = DBController::query("SELECT `session_file` FROM `persons` WHERE `id`=" . intval($userId))->fetch_row();

            // check if the session file still exists
            if(isset($data[0]) && file_exists($data[0])) {
                $error = null;

                // try to generate a token, save any Exception to check if there are any valid access tokens in the database before we throw it
                try {
                    $token = self::getToken($data[0]);
                } catch(Exception $e) {
                    $error = $e;
                }

                // when we didn't got a token
                if(!is_array($token) || !is_null($error)) {
                    // try to get a token from the database
                    $res = DBController::query("SELECT `access_token`, UNIX_TIMESTAMP(`expires_at`) FROM `access_tokens` WHERE `person_id`=" . intval($userId) . " AND `expires_at` > NOW() ORDER BY `expires_at` DESC LIMIT 1");
                    if ($res->num_rows === 1) {
                        $token = $res;
                    } else {
                        if ($error === null) {
                            Template::run('error', ['code' => 503, 'message' => 'no token available']);
                        } else {
                            if ($e instanceof InvalidCredentialsException) {
                                unlink($data[0]);
                                Template::run('error', ['code' => 503, 'message' => 'no valid session for this user available']);
                            } else {
                                throw $error;
                            }
                        }
                        return false;
                    }
                }

                $data = ['data' => ['user_id' => $userId, 'access_token' => $token[0], 'expires_at' => date('c', $token[1]), 'expires_in' => ($token[1]) - time()]];

                Template::output(json_encode($data));

                return true;
            } else {
                Template::run('error', ['code' => 503, 'message' => 'no session for this user available']);
                return false;
            }
        } else {
            throw new Error(401, "wrong client secret or client id not authorized to use this flow.");
        }
    }

    /**
     * Check that the redirect uri is allowed for the cliend id
     *
     * @param string $clientId
     * @param string $redirectUri
     * @return bool
     * @throws Error
     */
    private static function checkRedirectUri($clientId, $redirectUri) {
        $res = DBController::query("SELECT COUNT(*) FROM `sites` LEFT JOIN `redirect_uris` ON `redirect_uris`.`site_id`=`sites`.`id` WHERE `sites`.`client_id`='" . DBController::escape($clientId) . "' AND `redirect_uris`.`redirect_uri` LIKE '" . DBController::escape($redirectUri) . "%'");
        if($res->num_rows > 0 && $res->fetch_row()[0] > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * check that clientId and clientSecret match
     *
     * @param $clientId
     * @param $clientSecret
     * @return bool
     * @throws Error
     */
    private static function checkClientSecret($clientId, $clientSecret) {
        $res = DBController::query("SELECT COUNT(*) FROM `sites` WHERE `client_id`='" . DBController::escape($clientId) . "' AND `client_secret` = SHA1('" . DBController::escape($clientSecret) . "')");
        if($res->num_rows > 0 && $res->fetch_row()[0] > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * parses the url encoded scopes in expected and checks if they are fulfilled by $actual
     *
     * @param array $actual
     * @param string $expected
     * @return bool
     */
    private static function checkScopes($actual, $expected) {
        // check if there are scopes expected
        if(strlen($expected) > 0) {
            // explode scopes into an array. They are divided by a whitespace
            $needed = explode(" ", $expected);

            // check for each expected scope
            foreach ($needed as $scope) {
                if (strpos($scope, ':') > 1) {  // if there is a specific role requested (via scope:role)
                    // get the role
                    $s = explode(':', $scope);

                    if (!isset($actual[$s[0]])) {
                        // return false if user not even have the scope
                        return false;
                    } elseif (!in_array($s[1], $actual[$s[0]])) {
                        // otherwise return false if the user is missing the role in the scope
                        return false;
                    }
                } else {    // if just the scope is needed
                    if (!isset($actual[$scope])) {
                        // return false if user does not have the scope
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * get a token from an existing session and add it to the database
     *
     * @param string $session filepath of session file
     * @param string $type optional 'EXPA' and 'OP' to use the respective AuthProviders. Standard 'combined'
     * @return array|bool
     * @throws InvalidCredentialsException
     */
    private static function getToken($session, $type = 'combined') {
        // create right Authentication Provider
        switch($type) {
            case 'EXPA':
                $user = new AuthProviderEXPA($session, null, VERIFY_PEER);
                break;

            case 'OP':
                $user = new AuthProviderOP($session, null, VERIFY_PEER);
                break;

            default:
                $user = new AuthProviderCombined($session, null, VERIFY_PEER);
                break;
        }

        // generate token
        try {
            $token = $user->getToken();
        } catch (InvalidCredentialsException $e) {
            throw $e;
        } catch (Exception $e) {
            return false;
        }

        // check token
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

        PersonController::addAccessToken($token, $user->getExpiresAt(), $current_person);
        return [$token, $user->getExpiresAt()];
    }
}