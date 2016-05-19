<?php
/**
 * authorize.php
 * token based oAuth2 flow
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */

session_start();
$error = false;

if(!isset($_SESSION['access_token']) || $_SESSION['expires_at'] <= time()) {
    $_SESSION = array();
    $_SESSION['redirect'] = 'authorize.php?response_type=' . urlencode(getParam('response_type')) . '&redirect_uri=' . urlencode(getParam('redirect_uri')) . '&client_id=' . urlencode(getParam('client_id')) . '&scope=' . urlencode(getParam('scope')) . '&state=' . urlencode(getParam('state'));
    header('Location: login.php');
} else {
    require_once(dirname(__FILE__) . '/config.php');

    // check response type
    if(getParam('response_type') == "token") {

        // check client id
        if(isset($_GET['client_id']) && strlen($_GET['client_id']) > 0 && isset($SITES[$_GET['client_id']])) {

            // check redirect uri
            $RUallowed = false;
            $RU = str_replace('../', '' , $_GET['redirect_uri']);
            foreach($SITES[$_GET['client_id']]['URLS'] as $url) {
                if(!$RUallowed) {
                    if(substr($RU, 0, strlen($url)) == $url) $RUallowed = true;
                }
            }
            if($RUallowed) {
                // check scope if necessary
                if(getParam('scope') != "") {
                    $conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
                    if (!$conn) {
                        $error = "Connection to Database failed";
                        trigger_error("Could not connect to database: " . $conn->error, E_USER_ERROR);
                    } else {
                        if ($result = $conn->query("SELECT `scope`.`name` as scope, GROUP_CONCAT(`role`.`name` SEPARATOR ';') as roles FROM `persons_scopes` LEFT JOIN `scopes` scope ON `scope`.`id`=`persons_scopes`.`scope_id` LEFT JOIN `roles` role ON `role`.`id`=`persons_scopes`.`role_id` WHERE `persons_scopes`.`person_id` = " . intval($_SESSION['person_id']) . " AND (`expires_at` > NOW() OR `expires_at` IS NULL) GROUP BY `persons_scopes`.`scope_id`")) {
                            $conn->close();

                            $scopes = array();
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_row()) {
                                    $scopes[$row[0]] = explode(';', $row[1]);
                                }
                            }

                            $needed = explode(" ", $_GET['scope']);
                            $missed = false;
                            foreach($needed as $scope) {
                                if(!$missed) {
                                    if(strpos($scope, ':') > 1) {
                                        $s = explode(':', $scope);
                                        if(!isset($scopes[$s[0]])) {
                                            $missed = true;
                                        } elseif(!in_array($s[1], $scopes[$s[0]])) {
                                            $missed = true;
                                        }
                                    } else {
                                        if(!isset($scopes[$scope])) $missed = true;
                                    }
                                }
                            }
                            if($missed) {
                                $error = "You don't have enough rights to access this site";
                            } else {
                                header('Location: ' . $RU . '?access_token=' . $_SESSION['access_token'] . '&expires_at=' . urlencode(date('c', $_SESSION['expires_at'])) . '&expires_in=' . ($_SESSION['expires_at'] - time()));
                            }
                        } else {
                            $error = "Database Error";
                            trigger_error("Could not retrieve scopes: " . $conn->error, E_USER_ERROR);
                            $conn->close();
                        }
                    }
                } else {
                    header('Location: ' . $RU . '?access_token=' . $_SESSION['access_token'] . '&expires_at=' . urlencode(date('c', $_SESSION['expires_at'])) . '&expires_in=' . ($_SESSION['expires_at'] - time()));
                }
            } else {
                $error = "Malformed Request. Invalid Redirect URL";
            }
        } else {
            $error = "Malformed Request. Invalid Client Id, or the site you want to access is not allowed to use this system";
        }
    } else {
        $error = "Malformed Request. Response Type not implemented";
    }
}

function getParam($name, $default = "") {
    if(isset($_GET[$name])) {
        return $_GET[$name];
    } else {
        return $default;
    }
}
?>

<?php if($error): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AIESEC Identity | Error</title>
    <!-- Bootstrap core CSS-->
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries-->
    <!--if lt IE 9
    script(src='https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js')
    script(src='https://oss.maxcdn.com/respond/1.4.2/respond.min.js')
    -->
    <style>
        header {
            background: #235192;
            padding: 21px 0 10px;
            font: bold 13px/15px Helvetica, Arial, sans-serif;
            position: relative;
        }
    </style>
</head>
<body>
    <header>
        <div class="container"><a href="/"><img alt="Logo" src="assets/logo.png"></a></div>
    </header>
    <br>
    <div class="container">
        <div class="row">
            <div class="col-xs-12 alert alert-danger">
                <?php echo $error; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?>
