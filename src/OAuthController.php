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
        // check that the redirect url is allowed for the client id
        if(self::checkRedirectUri($clientId, $redirectUri)) {
            // get the actual scopes of the current user
            $actual = PersonController::getScopes($_SESSION['person_id']);
            if(is_array($actual)) {
                // check that the user has the needed scopes
                if(self::checkScopes($actual, $scopes)) {
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
                    if($token === false) {
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

    /**
     * Check that the redirect uri is allowed for the cliend id
     *
     * @param string $clientId
     * @param string $redirectUri
     * @return bool
     */
    public static function checkRedirectUri($clientId, $redirectUri) {
        $res = DBController::query("SELECT COUNT(*) FROM `sites` LEFT JOIN `redirect_uris` ON `redirect_uris`.`site_id`=`sites`.`id` WHERE `sites`.`client_id`='" . DBController::escape($clientId) . "' AND `redirect_uris`.`redirect_uri` LIKE '" . DBController::escape($redirectUri) . "%'");
        if($res && $res->num_rows > 0 && $res->fetch_row()[0] > 0) {
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
    public static function checkScopes($actual, $expected) {
        if(strlen($expected) > 0) {
            $needed = explode(" ", $expected);
            $ok = true;
            foreach ($needed as $scope) {
                if ($ok) {
                    if (strpos($scope, ':') > 1) {
                        $s = explode(':', $scope);
                        if (!isset($actual[$s[0]])) {
                            $ok = false;
                        } elseif (!in_array($s[1], $actual[$s[0]])) {
                            $ok = false;
                        }
                    } else {
                        if (!isset($actual[$scope])) $ok = false;
                    }
                } else {
                    break;
                }
            }
            return $ok;
        } else {
            return true;
        }
    }

    /**
     * get a token from an existing session and add it to the database
     *
     * @param string $session filepath of session file
     * @param string $type optional 'EXPA' and 'OP' to use the respective AuthProviders. Standard 'combined'
     * @return array|bool
     * @throws InvalidCredentialsException
     */
    public static function getToken($session, $type = 'combined') {
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

        if(PersonController::addAccessToken($token, $user->getExpiresAt(), $current_person)) {
            return [$token, $user->getExpiresAt()];
        } else {
            return false;
        }
    }
}