<?php
/**
 * logout.php
 * destroy session and redirect to front page
 * 
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */

$error = false;

session_start();

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/plugins/plugin.runner.php');

$conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
if (!$conn) {
    $error = "Connection to Database failed";
    trigger_error("Database Connection faild: " . $conn->error, E_USER_ERROR);
} else {
    $p = new PluginRunner($ACTIVE_PLUGINS);
    if($p->onBeforeLogout($_SESSION['access_token'], $_SESSION['person_id'])) {
        if($conn->query("DELETE FROM `access_tokens` WHERE `access_token`='" . $conn->real_escape_string($_SESSION['access_token']) . "' OR `expires_at` <= NOW()") === TRUE) {
            $conn->close();

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
                header('Location: login.php');
            } else {
                $error = "Logged out from the system, but some plugins failed";
            }
        } else {
            $error = "Database Error";
            trigger_error("Database Error: " . $conn->error, E_USER_ERROR);
            $conn->close();
        }
    } else {
        $conn->close();
        $error = "Logout failed";
    }
}

if($error): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AIESEC Identity | Login</title>
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
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?>